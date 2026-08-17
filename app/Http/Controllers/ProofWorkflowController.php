<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Proof\ProofWorkflow;
use App\Domain\Identity\CoreIdentity;
use App\Models\Proof;
use App\Models\ProofWitness;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class ProofWorkflowController
{
    public function inviteWitness(Request $request, Proof $proof, ProofWorkflow $workflow): RedirectResponse
    {
        $data = $request->validate(['discovery_reference' => ['required', 'string']]);
        $workflow->inviteWitness($proof, $this->identity($request)->reference, $data['discovery_reference']);

        return back()->with('status', 'L’invitation à témoigner est envoyée.');
    }

    public function confirmWitness(Request $request, Proof $proof, ProofWitness $witness, ProofWorkflow $workflow): RedirectResponse
    {
        $workflow->confirmWitness($proof, $this->identity($request)->reference, $witness);

        return back()->with('status', 'Votre témoignage est confirmé. Cela ne certifie rien : c’est une corroboration humaine.');
    }

    public function declineWitness(Request $request, Proof $proof, ProofWitness $witness, ProofWorkflow $workflow): RedirectResponse
    {
        $workflow->declineWitness($proof, $this->identity($request)->reference, $witness);

        return back()->with('status', 'Vous avez décliné cette demande de témoignage.');
    }

    public function acknowledge(Request $request, Proof $proof, ProofWorkflow $workflow): RedirectResponse
    {
        $workflow->acknowledgeByContext($proof, $this->identity($request)->reference);

        return back()->with('status', 'La preuve est reconnue par le contexte — cela ne garantit pas sa véracité.');
    }

    public function dispute(Request $request, Proof $proof, ProofWorkflow $workflow): RedirectResponse
    {
        $data = $request->validate(['note' => ['nullable', 'string', 'max:1500']]);
        $workflow->dispute($proof, $this->identity($request)->reference, $data['note'] ?? null);

        return back()->with('status', 'La preuve est contestée.');
    }

    public function archive(Request $request, Proof $proof, ProofWorkflow $workflow): RedirectResponse
    {
        $workflow->archive($proof, $this->identity($request)->reference);

        return back()->with('status', 'La preuve est archivée. Elle reste conservée et peut être restaurée.');
    }

    public function restore(Request $request, Proof $proof, ProofWorkflow $workflow): RedirectResponse
    {
        $workflow->restore($proof, $this->identity($request)->reference);

        return back()->with('status', 'La preuve est restaurée.');
    }

    private function identity(Request $request): CoreIdentity
    {
        return $request->attributes->get('dg_identity');
    }
}
