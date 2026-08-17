<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Transmission\TransmissionParticipationService;
use App\Domain\Identity\CoreIdentity;
use App\Models\Transmission;
use App\Models\TransmissionParticipant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class TransmissionParticipationController
{
    public function offer(Request $request, Transmission $transmission, TransmissionParticipationService $participation): RedirectResponse
    {
        $data = $request->validate(['role' => ['required', Rule::in(TransmissionParticipant::ROLES)]]);
        $participation->offer($transmission, $this->identity($request)->reference, $data['role']);

        return back()->with('status', 'Votre proposition de participation est enregistrée.');
    }

    public function invite(Request $request, Transmission $transmission, TransmissionParticipationService $participation): RedirectResponse
    {
        $data = $request->validate([
            'discovery_reference' => ['required', 'string'],
            'role' => ['required', Rule::in(TransmissionParticipant::ROLES)],
        ]);
        $participation->invite($transmission, $this->identity($request)->reference, $data['discovery_reference'], $data['role']);

        return back()->with('status', 'L’invitation est envoyée.');
    }

    public function acceptOffer(Request $request, Transmission $transmission, TransmissionParticipant $participant, TransmissionParticipationService $participation): RedirectResponse
    {
        $participation->acceptOffer($transmission, $this->identity($request)->reference, $participant);

        return back()->with('status', 'La proposition est acceptée.');
    }

    public function declineOffer(Request $request, Transmission $transmission, TransmissionParticipant $participant, TransmissionParticipationService $participation): RedirectResponse
    {
        $participation->declineOffer($transmission, $this->identity($request)->reference, $participant);

        return back()->with('status', 'La proposition est déclinée.');
    }

    public function acceptInvitation(Request $request, Transmission $transmission, TransmissionParticipant $participant, TransmissionParticipationService $participation): RedirectResponse
    {
        $participation->acceptInvitation($transmission, $this->identity($request)->reference, $participant);

        return back()->with('status', 'Vous avez accepté l’invitation.');
    }

    public function declineInvitation(Request $request, Transmission $transmission, TransmissionParticipant $participant, TransmissionParticipationService $participation): RedirectResponse
    {
        $participation->declineInvitation($transmission, $this->identity($request)->reference, $participant);

        return back()->with('status', 'Vous avez décliné l’invitation.');
    }

    public function withdraw(Request $request, Transmission $transmission, TransmissionParticipant $participant, TransmissionParticipationService $participation): RedirectResponse
    {
        $participation->withdraw($transmission, $this->identity($request)->reference, $participant);

        return back()->with('status', 'Votre proposition est retirée.');
    }

    public function leave(Request $request, Transmission $transmission, TransmissionParticipant $participant, TransmissionParticipationService $participation): RedirectResponse
    {
        $participation->leave($transmission, $this->identity($request)->reference, $participant);

        return back()->with('status', 'Vous avez quitté cette Transmission.');
    }

    public function remove(Request $request, Transmission $transmission, TransmissionParticipant $participant, TransmissionParticipationService $participation): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:3', 'max:1000']]);
        $participation->remove($transmission, $this->identity($request)->reference, $participant, $data['reason']);

        return back()->with('status', 'Le participant est retiré.');
    }

    private function identity(Request $request): CoreIdentity
    {
        return $request->attributes->get('dg_identity');
    }
}
