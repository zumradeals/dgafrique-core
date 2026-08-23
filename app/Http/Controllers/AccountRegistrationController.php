<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Identity\PendingAccountSession;
use App\Infrastructure\GamadCore\Exceptions\CoreAccountException;
use App\Infrastructure\GamadCore\Exceptions\CoreProtocolException;
use App\Infrastructure\GamadCore\Exceptions\CoreUnavailableException;
use App\Infrastructure\GamadCore\GamadCoreClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

final readonly class AccountRegistrationController
{
    public function __construct(private GamadCoreClient $core, private PendingAccountSession $pendingSession) {}

    public function create(): View { return view('auth.register'); }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:256'],
            'email' => ['required', 'email:rfc', 'max:256'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers(), 'max:4096'],
            'terms' => ['accepted'],
        ]);

        try {
            $pending = $this->core->createAccount($data['name'], 'EMAIL', mb_strtolower($data['email']), $data['password']);
        } catch (CoreAccountException $exception) {
            $message = $exception->status === 409
                ? 'Cette adresse possède déjà un compte. Connectez-vous ou reprenez sa vérification.'
                : 'Le compte ne peut pas être créé pour le moment. Réessayez dans un instant.';
            return back()->withInput($request->only('name', 'email'))->withErrors(['email' => $message]);
        } catch (CoreUnavailableException|CoreProtocolException) {
            return back()->withInput($request->only('name', 'email'))->withErrors(['email' => 'Le service de création est temporairement indisponible.']);
        }

        $this->pendingSession->put($request->session(), $pending);

        return redirect()->route('register.verify')->with('status', $pending->delivered
            ? 'Un code à 6 chiffres vient d’être envoyé à votre adresse.'
            : 'Le compte existe, mais le code n’a pas pu être livré. Demandez un nouvel envoi.');
    }

    public function verification(Request $request): View|RedirectResponse
    {
        $pending = $this->pendingSession->get($request->session());
        return $pending === null
            ? redirect()->route('register')->withErrors(['email' => 'Aucune vérification de compte n’est en cours.'])
            : view('auth.verify-account', ['pending' => $pending]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $pending = $this->pendingSession->get($request->session());
        if ($pending === null) {
            return redirect()->route('register')->withErrors(['email' => 'La vérification a expiré ou a été interrompue.']);
        }
        $data = $request->validate(['code' => ['required', 'digits:6']]);

        try {
            $this->core->verifyAccount($pending, $data['code']);
        } catch (CoreAccountException $exception) {
            return back()->withErrors(['code' => $exception->status === 422
                ? 'Ce code est incorrect, expiré ou déjà utilisé.'
                : 'La vérification ne peut pas être terminée maintenant.']);
        } catch (CoreUnavailableException|CoreProtocolException) {
            return back()->withErrors(['code' => 'Le service de vérification est temporairement indisponible.']);
        }

        $identifier = $pending->destination;
        $this->pendingSession->forget($request->session());

        return redirect()->route('login')->withInput(['identifier' => $identifier])
            ->with('status', 'Compte vérifié. Vous pouvez maintenant vous connecter.');
    }

    public function resend(Request $request): RedirectResponse
    {
        $pending = $this->pendingSession->get($request->session());
        if ($pending === null) {
            return redirect()->route('register')->withErrors(['email' => 'Aucune vérification à renvoyer.']);
        }

        try {
            $renewed = $this->core->resendAccountVerification($pending);
        } catch (CoreAccountException $exception) {
            return back()->withErrors(['code' => $exception->status === 429
                ? 'Le renvoi est trop rapide. Patientez avant de réessayer.'
                : 'Le nouveau code n’a pas pu être envoyé.']);
        } catch (CoreUnavailableException|CoreProtocolException) {
            return back()->withErrors(['code' => 'Le renvoi est temporairement indisponible.']);
        }

        $this->pendingSession->put($request->session(), $renewed);
        return back()->with('status', 'Un nouveau code a été envoyé.');
    }
}
