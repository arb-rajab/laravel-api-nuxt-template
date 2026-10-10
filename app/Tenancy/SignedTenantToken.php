<?php

namespace App\Tenancy;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Throwable;

/**
 * A stateless, purpose-scoped, tenant-carrying capability token, for public
 * links that must resolve a tenant without a slug or a session (for example
 * an emailed "manage this item" link). Every purpose is minted and verified
 * through this one class, never a separate signing scheme per purpose.
 *
 * Signing and tamper-evidence reuse Laravel's Crypt facade (AES-256-CBC +
 * HMAC keyed on APP_KEY). The payload is a JSON envelope, encrypted, then
 * base64url-encoded so it is safe as a single URL path segment.
 *
 * No storage: nothing is persisted or revocable by id. Verification is
 * signature + claims only. Use short expiries.
 */
final class SignedTenantToken
{
    public static function issue(
        string $purpose,
        string $tenantId,
        string $subject,
        ?Carbon $expiresAt = null,
    ): string {
        $payload = [
            'purpose' => $purpose,
            'tenant_id' => $tenantId,
            'subject' => $subject,
            'issued_at' => Carbon::now()->toIso8601String(),
            'expires_at' => $expiresAt?->toIso8601String(),
        ];

        return self::toUrlSafeBase64(Crypt::encryptString(json_encode($payload, JSON_THROW_ON_ERROR)));
    }

    /**
     * @return array{purpose: string, tenant_id: string, subject: string, issued_at: string, expires_at: ?string}
     *
     * @throws InvalidTenantTokenException on a bad signature, wrong purpose or expiry
     */
    public static function verify(string $token, string $expectedPurpose): array
    {
        try {
            $decrypted = Crypt::decryptString(self::fromUrlSafeBase64($token));
            $payload = json_decode($decrypted, true, flags: JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            // Caught broadly on purpose: this is an attacker-facing input
            // boundary, and any failure must fail closed as an invalid token.
            throw new InvalidTenantTokenException;
        }

        if (
            ! is_array($payload)
            || ($payload['purpose'] ?? null) !== $expectedPurpose
            || ! is_string($payload['tenant_id'] ?? null)
            || ! is_string($payload['subject'] ?? null)
        ) {
            throw new InvalidTenantTokenException;
        }

        if (is_string($payload['expires_at'] ?? null) && Carbon::parse($payload['expires_at'])->isPast()) {
            throw new InvalidTenantTokenException;
        }

        /** @var array{purpose: string, tenant_id: string, subject: string, issued_at: string, expires_at: ?string} $payload */
        return $payload;
    }

    private static function toUrlSafeBase64(string $value): string
    {
        return rtrim(strtr($value, '+/', '-_'), '=');
    }

    private static function fromUrlSafeBase64(string $value): string
    {
        $base64 = strtr($value, '-_', '+/');

        return $base64.str_repeat('=', (4 - strlen($base64) % 4) % 4);
    }
}
