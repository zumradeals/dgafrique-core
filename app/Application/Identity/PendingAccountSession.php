<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Domain\Identity\PendingAccountVerification;
use Illuminate\Session\Store;

final class PendingAccountSession
{
    private const KEY = 'dg_pending_account';

    public function put(Store $session, PendingAccountVerification $pending): void
    {
        $session->put(self::KEY, [
            'identity' => $pending->identity,
            'identifier_reference' => $pending->identifierReference,
            'verification_reference' => $pending->verificationReference,
            'destination' => $pending->destination,
            'channel' => $pending->channel,
            'expires_at' => $pending->expiresAt->format(DATE_ATOM),
            'delivered' => $pending->delivered,
        ]);
    }

    public function get(Store $session): ?PendingAccountVerification
    {
        $value = $session->get(self::KEY);
        if (!is_array($value)) {
            return null;
        }

        foreach (['identity', 'identifier_reference', 'verification_reference', 'destination', 'channel', 'expires_at'] as $field) {
            if (!isset($value[$field]) || !is_string($value[$field]) || $value[$field] === '') {
                return null;
            }
        }

        try {
            $expiresAt = new \DateTimeImmutable($value['expires_at']);
        } catch (\Throwable) {
            return null;
        }

        return new PendingAccountVerification(
            identity: $value['identity'],
            identifierReference: $value['identifier_reference'],
            verificationReference: $value['verification_reference'],
            destination: $value['destination'],
            channel: $value['channel'],
            expiresAt: $expiresAt,
            delivered: ($value['delivered'] ?? false) === true,
        );
    }

    public function forget(Store $session): void
    {
        $session->forget(self::KEY);
    }
}
