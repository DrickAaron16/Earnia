<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="Earnia Gaming API",
 *     version="1.0.0",
 *     description="API documentation for Earnia gaming platform - A comprehensive gaming platform with wallet management, tournaments, and real-time gameplay",
 *     @OA\Contact(
 *         email="support@earnia.com",
 *         name="Earnia Support Team"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 * 
 * @OA\Server(
 *     url="http://localhost:8000/api",
 *     description="Development Server"
 * )
 * 
 * @OA\Server(
 *     url="https://api.earnia.com/api",
 *     description="Production Server"
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Enter token in format: Bearer {token}"
 * )
 * 
 * @OA\Tag(
 *     name="Authentication",
 *     description="User authentication and profile management"
 * )
 * 
 * @OA\Tag(
 *     name="Wallet",
 *     description="Wallet management, deposits, withdrawals and transactions"
 * )
 * 
 * @OA\Tag(
 *     name="Games",
 *     description="Game management and gameplay sessions"
 * )
 * 
 * @OA\Tag(
 *     name="Tournaments",
 *     description="Tournament management and participation"
 * )
 * 
 * @OA\Tag(
 *     name="Matchmaking",
 *     description="Player matchmaking and game sessions"
 * )
 */
abstract class Controller
{
    //
}