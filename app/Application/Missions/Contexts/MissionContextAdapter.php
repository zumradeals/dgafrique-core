<?php

declare(strict_types=1);

namespace App\Application\Missions\Contexts;

/**
 * Contrat d'un adaptateur de contexte MISSIONS. L'autorité vient toujours du parent,
 * jamais de Missions : chaque adaptateur réutilise les frontières existantes du service
 * métier réel (ProjectService, NeedService, ZumraGroupService) — jamais une deuxième
 * logique d'autorité parallèle.
 */
interface MissionContextAdapter
{
    public function type(): string;

    public function resolve(string $reference): object;

    public function canView(object $context, string $actor): bool;

    public function canPropose(object $context, string $actor): bool;

    public function canOfficialize(object $context, string $actor): bool;

    public function canManageAssignments(object $context, string $actor): bool;

    public function canValidate(object $context, string $actor): bool;

    public function canCancel(object $context, string $actor): bool;

    public function canReopen(object $context, string $actor): bool;

    public function isOperational(object $context): bool;

    public function maxVisibility(object $context, string $actor): string;

    public function label(object $context): string;

    public function reference(object $context): string;

    /**
     * Références de contextes (de ce type) où l'acteur détient l'autorité d'officialiser/
     * décider — bornée par l'empreinte propre de l'acteur (ses ZUMRA dirigées, ses Projets/
     * Besoins décidables), jamais par un balayage global des Missions récentes. Permet à
     * MissionService de construire une requête « à décider » scalable sans dépendre d'une
     * fenêtre de récence qui pourrait masquer les décisions d'une autorité peu active.
     *
     * @return list<string>
     */
    public function authorityContextReferences(string $actor): array;
}
