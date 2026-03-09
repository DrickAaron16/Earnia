<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/register",
     *     summary="Register a new user",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"username","email","password","password_confirmation"},
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="username", type="string", example="johndoe"),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="phone", type="string", example="+237123456789"),
     *             @OA\Property(property="password", type="string", format="password", example="password123"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="password123"),
     *             @OA\Property(property="date_of_birth", type="string", format="date", example="1990-01-01"),
     *             @OA\Property(property="currency", type="string", example="XAF")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User registered successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User registered successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", ref="#/components/schemas/User"),
     *                 @OA\Property(property="token", type="string", example="1|abc123...")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = DB::transaction(function () use ($request) {
            $data = $request->validated();

            $user = User::create([
                'name' => $data['name'] ?? $data['username'],
                'username' => $data['username'] ?? $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'phone_number' => $data['phone_number'] ?? $data['phone'] ?? null,
                'country_code' => $data['country_code'] ?? null,
                'date_of_birth' => $data['date_of_birth'] ?? $data['birth_date'] ?? null,
                'birth_date' => $data['birth_date'] ?? $data['date_of_birth'] ?? null,
                'password' => $data['password'],
                'pseudo' => $data['pseudo'] ?? null,
                'status' => 'active',
                'level' => 1,
                'total_wins' => 0,
                'total_losses' => 0,
                'total_earnings' => 0.00,
            ]);

            Wallet::create([
                'user_id' => $user->id,
                'currency' => $data['currency'] ?? 'XAF',
                'available_balance' => 0.00,
                'locked_balance' => 0.00,
                'status' => 'active',
            ]);

            return $user;
        });

        event(new Registered($user));

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'User registered successfully',
            'data' => [
                'user' => $user->load('wallet'),
                'token' => $token,
            ],
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/login",
     *     summary="Login user",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Login successful"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", ref="#/components/schemas/User"),
     *                 @OA\Property(property="token", type="string", example="1|abc123...")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Invalid credentials",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="These credentials do not match our records.")
     *         )
     *     )
     * )
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $data = $request->validated();

        /** @var User|null $user */
        $user = User::query()
            ->where('email', $data['email'])
            ->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        if ($user->status !== 'active') {
            throw ValidationException::withMessages([
                'email' => __('Account unavailable.'),
            ]);
        }

        $user->forceFill(['last_login_at' => now()])->save();

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => $user->load('wallet'),
                'token' => $token,
            ],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/logout",
     *     summary="Logout user",
     *     tags={"Authentication"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Logged out successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Logged out successfully")
     *         )
     *     )
     * )
     */
    public function logout(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * @OA\Get(
     *     path="/user",
     *     summary="Get authenticated user",
     *     tags={"Authentication"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="User data retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/User")
     *         )
     *     )
     * )
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $request->user()->load('wallet'),
        ]);
    }

    /**
     * @OA\Put(
     *     path="/user",
     *     summary="Update user profile",
     *     tags={"Authentication"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="phone", type="string", example="+237123456789"),
     *             @OA\Property(property="pseudo", type="string", example="JohnGamer"),
     *             @OA\Property(property="avatar", type="string", example="https://example.com/avatar.jpg")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Profile updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="user", ref="#/components/schemas/User")
     *         )
     *     )
     * )
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $user->fill($request->validated());
        $user->save();

        return response()->json([
            'user' => $user->fresh()->load('wallet'),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/auth/firebase",
     *     summary="Firebase authentication (simplified)",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"firebase_token"},
     *             @OA\Property(property="firebase_token", type="string", example="firebase_jwt_token")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Firebase login successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Login successful"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", ref="#/components/schemas/User"),
     *                 @OA\Property(property="token", type="string", example="1|abc123...")
     *             )
     *         )
     *     )
     * )
     */
    public function firebaseLogin(Request $request): JsonResponse
    {
        $request->validate([
            'firebase_token' => 'required|string',
        ]);

        try {
            // Note: En production, vérifier le token avec Firebase Admin SDK
            // Pour l'instant, on décode le JWT pour extraire les infos
            
            // Décoder le token JWT (partie payload)
            $tokenParts = explode('.', $request->firebase_token);
            if (count($tokenParts) !== 3) {
                throw new \Exception('Invalid Firebase token format');
            }
            
            $payload = json_decode(base64_decode($tokenParts[1]), true);
            
            if (!$payload || !isset($payload['user_id'])) {
                throw new \Exception('Invalid Firebase token payload');
            }
            
            $firebaseUid = $payload['user_id'];
            $email = $payload['email'] ?? null;
            $name = $payload['name'] ?? $payload['email'] ?? 'User';
            $picture = $payload['picture'] ?? null;
            $phoneNumber = $payload['phone_number'] ?? null;
            
            // Créer ou récupérer l'utilisateur
            $user = User::firstOrCreate(
                ['firebase_uid' => $firebaseUid],
                [
                    'name' => $name,
                    'username' => $email ? explode('@', $email)[0] : 'user_' . substr($firebaseUid, 0, 8),
                    'email' => $email,
                    'phone_number' => $phoneNumber,
                    'avatar' => $picture,
                    'provider' => $payload['firebase']['sign_in_provider'] ?? 'firebase',
                    'status' => 'active',
                    'email_verified_at' => now(),
                    'password' => Hash::make(Str::random(32)),
                ]
            );

            // Créer le wallet si nécessaire
            if (!$user->wallet) {
                Wallet::create([
                    'user_id' => $user->id,
                    'currency' => 'XAF',
                    'available_balance' => 0.00,
                    'locked_balance' => 0.00,
                    'status' => 'active',
                ]);
            }

            // Mettre à jour la dernière connexion
            $user->forceFill(['last_login_at' => now()])->save();

            // Générer le token
            $token = $user->createToken('mobile')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'user' => $user->load('wallet'),
                    'token' => $token,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Firebase login failed: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * @OA\Post(
     *     path="/social",
     *     summary="Social authentication (Google, Apple, Facebook, Phone)",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"provider","firebase_token","user_data"},
     *             @OA\Property(property="provider", type="string", enum={"google","apple","facebook","phone"}, example="google"),
     *             @OA\Property(property="firebase_token", type="string", example="firebase_jwt_token"),
     *             @OA\Property(property="user_data", type="object",
     *                 @OA\Property(property="id", type="string", example="firebase_uid"),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="john@example.com"),
     *                 @OA\Property(property="avatar", type="string", example="https://example.com/avatar.jpg"),
     *                 @OA\Property(property="phone", type="string", example="+237123456789"),
     *                 @OA\Property(property="provider", type="string", example="google")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Social login successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Social login successful"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", ref="#/components/schemas/User"),
     *                 @OA\Property(property="token", type="string", example="1|abc123...")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Invalid Firebase token")
     *         )
     *     )
     * )
     */
    public function socialLogin(Request $request): JsonResponse
    {
        $request->validate([
            'provider' => 'required|string|in:google,apple,facebook,phone',
            'firebase_token' => 'required|string',
            'user_data' => 'required|array',
            'user_data.id' => 'required|string',
            'user_data.name' => 'required|string',
            'user_data.email' => 'nullable|email',
            'user_data.avatar' => 'nullable|url',
            'user_data.phone' => 'nullable|string',
        ]);

        try {
            // Note: Dans un environnement de production, vous devriez vérifier le token Firebase
            // avec l'API Firebase Admin SDK pour s'assurer qu'il est valide
            
            $userData = $request->user_data;
            $provider = $request->provider;
            
            // Créer ou récupérer l'utilisateur basé sur l'UID Firebase
            $user = User::firstOrCreate(
                ['firebase_uid' => $userData['id']],
                [
                    'name' => $userData['name'],
                    'username' => $userData['email'] ? explode('@', $userData['email'])[0] : $userData['name'],
                    'email' => $userData['email'],
                    'phone' => $userData['phone'] ?? null,
                    'avatar' => $userData['avatar'] ?? null,
                    'provider' => $provider,
                    'status' => 'active',
                    'level' => 1,
                    'total_wins' => 0,
                    'total_losses' => 0,
                    'total_earnings' => 0.00,
                    'email_verified_at' => now(), // Les comptes sociaux sont considérés comme vérifiés
                ]
            );

            // Créer le wallet si il n'existe pas
            if (!$user->wallet) {
                Wallet::create([
                    'user_id' => $user->id,
                    'currency' => 'XAF',
                    'available_balance' => 0.00,
                    'locked_balance' => 0.00,
                    'status' => 'active',
                ]);
            }

            // Mettre à jour la dernière connexion
            $user->forceFill(['last_login_at' => now()])->save();

            // Générer le token d'authentification
            $token = $user->createToken('mobile')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Social login successful',
                'data' => [
                    'user' => $user->load('wallet'),
                    'token' => $token,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Social login failed: ' . $e->getMessage(),
            ], 422);
        }
    }
}

