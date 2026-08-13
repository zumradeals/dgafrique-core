<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Application\Identity\PortalMemberSession;
use App\Infrastructure\GamadCore\Exceptions\CoreSessionRejectedException;
use App\Infrastructure\GamadCore\Exceptions\CoreProtocolException;
use App\Infrastructure\GamadCore\Exceptions\CoreUnavailableException;
use App\Infrastructure\GamadCore\GamadCoreClient;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class RequireCoreMember
{
    public function __construct(
        private PortalMemberSession $portalSession,
        private GamadCoreClient $core,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $bearer = $this->portalSession->bearer($request->session());
        $identity = $this->portalSession->identity($request->session());

        if ($bearer === null || $identity === null) {
            $this->portalSession->clear($request->session());

            return redirect()->route('login', ['next' => $request->getRequestUri()]);
        }

        try {
            $coreSession = $this->core->currentSession($bearer);
        } catch (CoreSessionRejectedException) {
            $this->portalSession->clear($request->session());

            return redirect()->route('login')->withErrors([
                'identifier' => 'Votre session a expiré. Veuillez vous reconnecter.',
            ]);
        } catch (CoreUnavailableException|CoreProtocolException) {
            return response()->view('errors.core-unavailable', [], 503, [
                'Cache-Control' => 'no-store',
                'Retry-After' => '30',
            ]);
        }

        if (!hash_equals($identity->reference, $coreSession->entity)) {
            $this->portalSession->clear($request->session());

            return redirect()->route('login')->withErrors([
                'identifier' => 'Cette session ne correspond plus à votre identité.',
            ]);
        }

        $this->portalSession->refresh($request->session(), $coreSession);
        $request->attributes->set('dg_identity', $identity);

        return $next($request);
    }
}
