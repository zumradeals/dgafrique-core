<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Domain\Identity\CoreAuthenticatedIdentity;
use App\Domain\Identity\CoreIdentity;
use App\Domain\Identity\CoreSession;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Session\Store;

final readonly class PortalMemberSession
{
    private const KEY = 'dg_core_member';

    public function __construct(private Encrypter $encrypter) {}

    public function establish(Request $request, CoreAuthenticatedIdentity $authenticated): void
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $request->session()->put(self::KEY, [
            'bearer' => $this->encrypter->encrypt($authenticated->bearerToken(), false),
            'reference' => $authenticated->identity->reference,
            'type' => $authenticated->identity->type,
            'label' => $authenticated->identity->label,
            'state' => $authenticated->identity->state,
            'source' => $authenticated->identity->source,
            'regime' => $authenticated->identity->regime,
            'assurance' => $authenticated->session->assurance,
            'expires_at' => $authenticated->session->expiresAt->format(DATE_ATOM),
        ]);
    }

    public function has(Store $session): bool
    {
        return is_array($session->get(self::KEY));
    }

    public function bearer(Store $session): ?string
    {
        $value = $session->get(self::KEY.'.bearer');
        if (!is_string($value) || $value === '') {
            return null;
        }

        try {
            $decrypted = $this->encrypter->decrypt($value, false);

            return is_string($decrypted) ? $decrypted : null;
        } catch (DecryptException) {
            return null;
        }
    }

    public function identity(Store $session): ?CoreIdentity
    {
        $value = $session->get(self::KEY);
        if (!is_array($value)) {
            return null;
        }

        foreach (['reference', 'type', 'label', 'source', 'regime'] as $field) {
            if (!isset($value[$field]) || !is_string($value[$field])) {
                return null;
            }
        }

        return new CoreIdentity(
            reference: $value['reference'],
            type: $value['type'],
            label: $value['label'],
            state: is_string($value['state'] ?? null) ? $value['state'] : null,
            source: $value['source'],
            regime: $value['regime'],
        );
    }

    public function refresh(Store $session, CoreSession $coreSession): void
    {
        $session->put(self::KEY.'.assurance', $coreSession->assurance);
        $session->put(self::KEY.'.expires_at', $coreSession->expiresAt->format(DATE_ATOM));
    }

    public function clear(Store $session): void
    {
        $session->invalidate();
        $session->regenerateToken();
    }
}
