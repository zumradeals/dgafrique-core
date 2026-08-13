<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Identity\PortalMemberSession;
use App\Application\Identity\SafeLocalDestination;
use App\Infrastructure\GamadCore\Exceptions\CoreProtocolException;
use App\Infrastructure\GamadCore\Exceptions\CoreSessionRejectedException;
use App\Infrastructure\GamadCore\Exceptions\CoreUnavailableException;
use App\Infrastructure\GamadCore\GamadCoreClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final readonly class MemberSessionController
{
    public function __construct(
        private GamadCoreClient $core,
        private PortalMemberSession $portalSession,
    ) {}

    public function create(Request $request): View|RedirectResponse
    {
        $next = SafeLocalDestination::sanitize($request->query('next'));
        if ($this->portalSession->has($request->session())) {
            return redirect()->to($next);
        }

        return view('auth.login', ['next' => $next]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'identifier' => ['required', 'string', 'max:512'],
            'secret' => ['required', 'string', 'max:4096'],
            'next' => ['nullable', 'string', 'max:2048'],
        ]);

        try {
            $authenticated = $this->core->authenticate($validated['identifier'], $validated['secret']);
        } catch (CoreSessionRejectedException) {
            return back()->withInput($request->only('identifier', 'next'))->withErrors([
                'identifier' => 'Identifiant ou moyen d’accès incorrect.',
            ]);
        } catch (CoreUnavailableException) {
            return back()->withInput($request->only('identifier', 'next'))->withErrors([
                'identifier' => 'La connexion est temporairement indisponible. Réessayez dans un instant.',
            ]);
        } catch (CoreProtocolException) {
            return back()->withInput($request->only('identifier', 'next'))->withErrors([
                'identifier' => 'La réponse du service d’identité est incohérente. La connexion a été interrompue.',
            ]);
        }

        $this->portalSession->establish($request, $authenticated);

        return redirect()->to(SafeLocalDestination::sanitize($validated['next'] ?? null));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $bearer = $this->portalSession->bearer($request->session());
        if ($bearer !== null) {
            try {
                $this->core->revokeSession($bearer);
            } catch (CoreUnavailableException) {
                // La déconnexion locale reste impérative. Core expirera le bearer,
                // qui n'est plus conservé ni réutilisable par le portail.
            }
        }

        $this->portalSession->clear($request->session());

        return redirect()->route('login')->with('status', 'Vous êtes déconnecté.');
    }
}
