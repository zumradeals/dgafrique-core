<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Identity\PortalMemberSession;
use App\Infrastructure\GamadCore\Exceptions\CoreProductAccessDeniedException;
use App\Infrastructure\GamadCore\Exceptions\CoreProtocolException;
use App\Infrastructure\GamadCore\Exceptions\CoreSessionRejectedException;
use App\Infrastructure\GamadCore\Exceptions\CoreUnavailableException;
use App\Infrastructure\GamadCore\FederatedProductGateway;
use App\Models\Satellite;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class FederationContinuationController
{
    public function __construct(
        private FederatedProductGateway $federation,
        private PortalMemberSession $portalSession,
    ) {}

    public function __invoke(Request $request, string $satellite): Response
    {
        $record = Satellite::query()->where('slug', $satellite)->where('is_active', true)->first();
        abort_if($record === null, 404);

        $configured = $this->satelliteConfiguration($record);
        if ($configured === null) {
            return $this->failure(
                $record->slug,
                "L’accès à {$record->display_name} est temporairement indisponible. Réessayez dans un instant.",
                503,
            );
        }

        $bearer = $this->portalSession->bearer($request->session());
        if ($bearer === null) {
            $this->portalSession->clear($request->session());

            return redirect()->route('login', ['next' => $request->getRequestUri()]);
        }

        try {
            $access = $this->federation->open($configured['product_reference'], $bearer);
        } catch (CoreSessionRejectedException) {
            $this->portalSession->clear($request->session());

            return redirect()->route('login', ['next' => $request->getRequestUri()])->withErrors([
                'identifier' => 'Votre session a expiré. Veuillez vous reconnecter.',
            ]);
        } catch (CoreProductAccessDeniedException) {
            return $this->failure(
                $configured['slug'],
                "Votre compte ne peut pas ouvrir {$configured['display_name']} pour le moment.",
                403,
            );
        } catch (CoreUnavailableException) {
            return $this->failure(
                $configured['slug'],
                "{$configured['display_name']} ne peut pas être ouvert pour le moment. Votre connexion DG Afrique reste active.",
                503,
            );
        } catch (CoreProtocolException) {
            return $this->failure(
                $configured['slug'],
                "La passerelle vers {$configured['display_name']} a reçu une réponse incohérente. Aucun accès n’a été transmis.",
                502,
            );
        }

        $nonce = rtrim(strtr(base64_encode(random_bytes(18)), '+/', '-_'), '=');
        $headers = $this->securityHeaders(
            "default-src 'none'; base-uri 'none'; frame-ancestors 'none'; "
            ."form-action {$configured['callback_origin']}; script-src 'nonce-{$nonce}'; style-src 'nonce-{$nonce}'",
        );

        return response()->view('federation.handoff', [
            'displayName' => $configured['display_name'],
            'callbackUrl' => $configured['callback_url'],
            'token' => $access['token'],
            'nonce' => $nonce,
        ], 200, $headers);
    }

    /** @return array{slug: string, product_reference: string, display_name: string, callback_url: string, callback_origin: string}|null */
    private function satelliteConfiguration(Satellite $satellite): ?array
    {
        $productReference = trim($satellite->product_reference);
        $displayName = trim($satellite->display_name);
        $callbackUrl = trim((string) $satellite->callback_url);
        $parts = parse_url($callbackUrl);

        if (
            $productReference === ''
            || $displayName === ''
            || $callbackUrl === ''
            || !is_array($parts)
            || ($parts['scheme'] ?? null) !== 'https'
            || !is_string($parts['host'] ?? null)
            || $parts['host'] === ''
            || isset($parts['user'])
            || isset($parts['pass'])
        ) {
            return null;
        }

        $origin = 'https://'.$parts['host'];
        if (isset($parts['port']) && is_int($parts['port'])) {
            $origin .= ':'.$parts['port'];
        }

        return [
            'slug' => $satellite->slug,
            'product_reference' => $productReference,
            'display_name' => $displayName,
            'callback_url' => $callbackUrl,
            'callback_origin' => $origin,
        ];
    }

    private function failure(string $satelliteSlug, string $message, int $status): Response
    {
        return response()->view('federation.error', [
            'message' => $message,
            'satelliteSlug' => $satelliteSlug,
        ], $status, $this->securityHeaders(
            "default-src 'none'; base-uri 'none'; frame-ancestors 'none'",
        ));
    }

    /** @return array<string, string> */
    private function securityHeaders(string $contentSecurityPolicy): array
    {
        return [
            'Cache-Control' => 'no-store, max-age=0',
            'Pragma' => 'no-cache',
            'Content-Security-Policy' => $contentSecurityPolicy,
            'Referrer-Policy' => 'no-referrer',
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'X-Robots-Tag' => 'noindex, nofollow, noarchive',
        ];
    }
}
