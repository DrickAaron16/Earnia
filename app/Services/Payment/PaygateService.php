<?php

namespace App\Services\Payment;

use App\Models\Deposit;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class PaygateService
{
    public function __construct(
        protected ?string $merchantId = null,
        protected ?string $encryptionKey = null,
        protected ?string $baseUrl = null,
    ) {
        $this->merchantId ??= config('services.paygate.id');
        $this->encryptionKey ??= config('services.paygate.encryption_key');
        $this->baseUrl ??= rtrim(config('services.paygate.base_url'), '/');
    }

    public function initiateDeposit(
        Deposit $deposit,
        string $returnUrl,
        string $notifyUrl,
        ?string $customerEmail = null,
        ?string $customerPhone = null,
    ): array {
        if (! $this->merchantId || ! $this->encryptionKey) {
            throw new RuntimeException('Paygate credentials are missing.');
        }

        $payload = array_filter([
            'PAYGATE_ID' => $this->merchantId,
            'REFERENCE' => $deposit->external_reference,
            'AMOUNT' => (int) round($deposit->amount * 100),
            'CURRENCY' => config('services.paygate.currency', 'ZAR'),
            'RETURN_URL' => $returnUrl,
            'TRANSACTION_DATE' => now()->format('Y-m-d H:i:s'),
            'LOCALE' => config('services.paygate.locale', 'en-za'),
            'COUNTRY' => config('services.paygate.country', 'ZAF'),
            'EMAIL' => $customerEmail,
            'NOTIFY_URL' => $notifyUrl,
            'OPTIONAL1' => $deposit->id,
            'OPTIONAL2' => $customerPhone,
        ], static fn ($value) => $value !== null && $value !== '');

        $payload['CHECKSUM'] = $this->calculateChecksum($payload);

        $response = Http::asForm()->post($this->baseUrl . '/initiate.trans', $payload);

        if (! $response->ok()) {
            Log::error('Paygate initiation failed', ['response' => $response->body()]);
            throw new RuntimeException('Paygate initiation failed.');
        }

        parse_str($response->body(), $body);

        if (($body['RETURN_CODE'] ?? null) !== '000') {
            Log::warning('Paygate returned non-success code', ['body' => $body]);
            throw new RuntimeException($body['ERROR'] ?? 'Paygate returned an error.');
        }

        return [
            'pay_request_id' => $body['PAY_REQUEST_ID'] ?? null,
            'checksum' => $body['CHECKSUM'] ?? null,
            'redirect_url' => $this->baseUrl . '/process.trans?PAY_REQUEST_ID=' . ($body['PAY_REQUEST_ID'] ?? '') . '&CHECKSUM=' . ($body['CHECKSUM'] ?? ''),
            'raw' => $body,
        ];
    }

    public function verifyCallback(array $payload): array
    {
        $expectedChecksum = $this->calculateChecksum(Arr::except($payload, ['CHECKSUM']));
        $isValid = hash_equals($expectedChecksum, $payload['CHECKSUM'] ?? '');

        $statusCode = (string) ($payload['TRANSACTION_STATUS'] ?? '');

        return [
            'valid' => $isValid,
            'approved' => $isValid && $statusCode === '1',
            'status_code' => $statusCode,
            'reference' => $payload['REFERENCE'] ?? null,
        ];
    }

    public function calculateChecksum(array $data): string
    {
        ksort($data);

        return md5(http_build_query($data) . $this->encryptionKey);
    }
}


