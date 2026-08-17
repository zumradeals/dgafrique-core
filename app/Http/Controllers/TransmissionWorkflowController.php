<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Transmission\TransmissionWorkflow;
use App\Domain\Identity\CoreIdentity;
use App\Models\Transmission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class TransmissionWorkflowController
{
    public function start(Request $request, Transmission $transmission, TransmissionWorkflow $workflow): RedirectResponse
    {
        $workflow->start($transmission, $this->identity($request)->reference);

        return back()->with('status', 'La Transmission est démarrée.');
    }

    public function declareDone(Request $request, Transmission $transmission, TransmissionWorkflow $workflow): RedirectResponse
    {
        $data = $request->validate(['note' => ['nullable', 'string', 'max:1000']]);
        $workflow->declareDone($transmission, $this->identity($request)->reference, $data['note'] ?? null);

        return back()->with('status', 'Votre part est déclarée terminée. La clôture de la Transmission reste une décision distincte.');
    }

    public function confirmCompletion(Request $request, Transmission $transmission, TransmissionWorkflow $workflow): RedirectResponse
    {
        $data = $request->validate(['summary' => ['required', 'string', 'min:5', 'max:2000']]);
        $workflow->confirmCompletion($transmission, $this->identity($request)->reference, $data['summary']);

        return back()->with('status', 'La Transmission est confirmée comme terminée.');
    }

    public function validateByContext(Request $request, Transmission $transmission, TransmissionWorkflow $workflow): RedirectResponse
    {
        $data = $request->validate(['note' => ['nullable', 'string', 'max:2000']]);
        $workflow->validateByContext($transmission, $this->identity($request)->reference, $data['note'] ?? null);

        return back()->with('status', 'La réalisation de la Transmission est validée par le contexte.');
    }

    public function officializeContext(Request $request, Transmission $transmission, TransmissionWorkflow $workflow): RedirectResponse
    {
        $data = $request->validate(['visibility' => ['nullable', 'in:CONTEXT,PROGRAM']]);
        $workflow->officializeContext($transmission, $this->identity($request)->reference, $data['visibility'] ?? null);

        return back()->with('status', 'Le rattachement contextuel est officialisé.');
    }

    public function end(Request $request, Transmission $transmission, TransmissionWorkflow $workflow): RedirectResponse
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:1000']]);
        $workflow->end($transmission, $this->identity($request)->reference, $data['reason'] ?? null);

        return back()->with('status', 'La Transmission est arrêtée, sans validation de résultat.');
    }

    public function cancel(Request $request, Transmission $transmission, TransmissionWorkflow $workflow): RedirectResponse
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:1000']]);
        $workflow->cancel($transmission, $this->identity($request)->reference, $data['reason'] ?? null);

        return back()->with('status', 'La Transmission est annulée.');
    }

    private function identity(Request $request): CoreIdentity
    {
        return $request->attributes->get('dg_identity');
    }
}
