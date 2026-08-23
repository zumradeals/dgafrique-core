<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Profile\CapabilityStatementSynchronizer;
use App\Domain\Identity\CoreIdentity;
use App\Models\CapabilityStatement;
use App\Models\PersonProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * UIUX-001 — porte d'entrée légère pour l'intention « Je peux apporter quelque chose » :
 * une phrase suffit à déclarer une première capacité. Réutilise exactement le domaine
 * CapabilityStatement existant et son synchroniseur (docs/product/EXPERIENCE-PRODUIT-CANONIQUE.md
 * §15) — aucun modèle métier parallèle, aucune nouvelle table. Le profil complet en 7 étapes
 * (MemberProfileController) reste la voie d'approfondissement, inchangée.
 */
final class QuickCapabilityController
{
    public function store(Request $request, CapabilityStatementSynchronizer $statementSynchronizer): RedirectResponse
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');

        $data = $request->validate([
            'capability' => ['required', 'string', 'min:3', 'max:200'],
        ]);
        $capability = trim($data['capability']);

        DB::transaction(function () use ($identity, $capability, $statementSynchronizer): void {
            $profile = PersonProfile::query()->firstOrNew(['core_identity_reference' => $identity->reference]);
            $skills = array_values(array_unique(array_merge($profile->existing_skills ?? [], [$capability])));
            $profile->existing_skills = $skills;
            $profile->starts_without_skill = false;
            $profile->save();

            $statementSynchronizer->sync(
                $identity->reference,
                [CapabilityStatement::KIND_POSSESSED => $skills],
                (bool) $profile->orientation_consent,
                (bool) $profile->discovery_consent,
            );
        });

        return redirect()->route('member.space')->with('status', 'Capacité déclarée : « '.$capability.' ».');
    }
}
