<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Partnerships\PartnershipService;
use App\Domain\Identity\CoreIdentity;
use App\Models\Organization;
use App\Models\Partnership;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * CAP-065 — exposition minimale : proposer, consulter et gouverner un partenariat. Aucun
 * portail partenaire ; réutilise le middleware et les conventions de routes existantes.
 */
final class PartnershipController
{
    public function index(Request $request, PartnershipService $service): JsonResponse
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');

        $partnerships = Partnership::query()
            ->latest('created_at')
            ->limit(200)
            ->get()
            ->filter(fn (Partnership $partnership): bool => $service->canView($partnership, $identity->reference))
            ->values()
            ->map(fn (Partnership $partnership): array => $this->present($partnership));

        return response()->json(['partnerships' => $partnerships]);
    }

    public function store(Request $request, PartnershipService $service): RedirectResponse
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $data = $request->validate([
            'provider_type' => ['required', Rule::in([Partnership::PROVIDER_PERSON, Partnership::PROVIDER_ORGANIZATION])],
            'organization_reference' => ['nullable', 'uuid', 'required_if:provider_type,ORGANIZATION'],
            'context_type' => ['required', Rule::in([Partnership::CONTEXT_PROJECT, Partnership::CONTEXT_ZUMRA, Partnership::CONTEXT_NEED])],
            'context_reference' => ['required', 'uuid'],
            'capability_statement_id' => ['required', 'uuid'],
            'description' => ['nullable', 'string', 'max:2000'],
            'visibility' => ['required', Rule::in([Partnership::VISIBILITY_PRIVATE, Partnership::VISIBILITY_PUBLIC])],
        ]);

        $partnership = $service->propose($identity->reference, $data);

        // UIUX-005 — la proposition ne se fait jamais « dans le vide » : elle part toujours d'un
        // contexte consultable (Besoin/Projet/ZUMRA), la réponse doit y ramener, jamais vers une
        // fiche Partnership autonome (JSON, sans destination humaine).
        return redirect()->to($this->contextUrl($partnership))->with('status', 'Votre proposition de partenariat a été transmise.');
    }

    private function contextUrl(Partnership $partnership): string
    {
        return match ($partnership->context_type) {
            Partnership::CONTEXT_NEED => route('needs.show', ['need' => $partnership->context_reference]),
            Partnership::CONTEXT_PROJECT => route('projects.show', ['project' => $partnership->context_reference]),
            Partnership::CONTEXT_ZUMRA => route('zumra.groups.show', ['group' => $partnership->context_reference]),
            default => route('member.space'),
        };
    }

    public function show(Request $request, Partnership $partnership, PartnershipService $service): JsonResponse
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        abort_unless($service->canView($partnership, $identity->reference), 404);

        return response()->json($this->present($partnership));
    }

    public function activate(Request $request, Partnership $partnership, PartnershipService $service): RedirectResponse
    {
        $service->activate($partnership, $this->actor($request));

        return back()->with('status', 'Le partenariat est actif.');
    }

    public function pause(Request $request, Partnership $partnership, PartnershipService $service): RedirectResponse
    {
        $service->pause($partnership, $this->actor($request));

        return back()->with('status', 'Le partenariat est mis en pause.');
    }

    public function resume(Request $request, Partnership $partnership, PartnershipService $service): RedirectResponse
    {
        $service->resume($partnership, $this->actor($request));

        return back()->with('status', 'Le partenariat reprend.');
    }

    public function withdraw(Request $request, Partnership $partnership, PartnershipService $service): RedirectResponse
    {
        $service->withdraw($partnership, $this->actor($request));

        return back()->with('status', 'Vous avez retiré votre capacité.');
    }

    public function end(Request $request, Partnership $partnership, PartnershipService $service): RedirectResponse
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:1000']]);
        $service->end($partnership, $this->actor($request), $data['reason'] ?? null);

        return back()->with('status', 'Le partenariat est terminé.');
    }

    private function actor(Request $request): string
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');

        return $identity->reference;
    }

    private function present(Partnership $partnership): array
    {
        return [
            'reference' => $partnership->public_reference,
            'provider_type' => $partnership->provider_type,
            'provider_reference' => $partnership->provider_type === Partnership::PROVIDER_ORGANIZATION
                ? Organization::query()->find($partnership->provider_reference)?->public_reference
                : null,
            'context_type' => $partnership->context_type,
            'context_reference' => $partnership->context_reference,
            'capability_label' => $partnership->capability_label,
            'description' => $partnership->description,
            'status' => $partnership->status,
            'visibility' => $partnership->visibility,
        ];
    }
}
