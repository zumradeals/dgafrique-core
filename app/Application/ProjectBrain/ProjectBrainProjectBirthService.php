<?php

declare(strict_types=1);

namespace App\Application\ProjectBrain;

use App\Application\Projects\ProjectConfiguration;
use App\Application\Projects\ProjectService;
use App\Models\Project;
use App\Models\ProjectBrainIntent;
use Illuminate\Support\Str;

final class ProjectBrainProjectBirthService
{
    public function __construct(private readonly ProjectService $projects, private readonly ProjectConfiguration $configuration) {}

    public function candidate(ProjectBrainIntent $intent): array
    {
        $state = is_array($intent->context['project_state'] ?? null) ? $intent->context['project_state'] : [];
        $list = static fn(string $key): string => implode("\n", array_values(array_filter(array_map('strval', is_array($state[$key] ?? null) ? $state[$key] : []))));
        $beneficiaries = is_array($state['beneficiaries'] ?? null) ? implode(', ', $state['beneficiaries']) : (string)($state['beneficiaries'] ?? '');
        $mode = mb_strtolower((string)($state['mode'] ?? ''));
        $participation = str_contains($mode,'hybrid')||str_contains($mode,'hybride') ? 'HYBRID' : (str_contains($mode,'digital')||str_contains($mode,'ligne')||str_contains($mode,'numérique') ? 'DIGITAL' : 'PHYSICAL');
        $domain = $this->domain((string)($state['activity'] ?? ''), array_keys($this->configuration->get()['domains'] ?? []));
        return [
            'owner_type'=>Project::OWNER_PERSON,'group_reference'=>null,'source_need_reference'=>null,
            'name'=>trim((string)($state['name'] ?? '')),
            'summary'=>trim((string)($state['summary'] ?? '')),
            'problem'=>trim((string)($state['problem'] ?? '')),
            'proposed_solution'=>trim((string)($state['proposed_solution'] ?? '')),
            'beneficiaries'=>trim($beneficiaries),'domain'=>$domain,'participation_mode'=>$participation,
            'location'=>trim((string)($state['location'] ?? '')) ?: null,
            'objectives'=>$list('objectives') ?: trim((string)($state['goal'] ?? '')),
            'required_capabilities'=>$list('required_capabilities'),'required_resources'=>$list('required_resources'),
            'risks'=>$list('risks'),'milestones'=>$list('milestones'),
            'property_regime'=>'PERSONAL_SUPPORTED','visibility'=>Project::VISIBILITY_PRIVATE,
        ];
    }

    public function ready(ProjectBrainIntent $intent): bool
    {
        $state = $intent->context['project_state'] ?? [];
        if (!is_array($state) || ($state['ready_for_confirmation'] ?? false) !== true) return false;
        $c=$this->candidate($intent);
        return mb_strlen($c['name'])>=5 && mb_strlen($c['summary'])>=40 && mb_strlen($c['problem'])>=40
            && mb_strlen($c['proposed_solution'])>=40 && mb_strlen($c['beneficiaries'])>=20
            && mb_strlen($c['objectives'])>=5 && mb_strlen($c['milestones'])>=5;
    }

    public function confirm(ProjectBrainIntent $intent, string $actor): Project
    {
        abort_unless($intent->actor_core_reference === $actor, 404);
        abort_if($intent->status === 'CREATED', 409, 'Ce projet a déjà été créé.');
        abort_unless($this->ready($intent), 422, 'Le Cerveau doit encore clarifier quelques éléments avant la création.');
        $project=$this->projects->create($actor,$this->candidate($intent),$this->configuration->get());
        $context=$intent->context ?? []; $context['canonical_project_reference']=$project->public_reference;
        $intent->update(['status'=>'CREATED','context'=>$context]);
        return $project;
    }

    private function domain(string $activity, array $allowed): string
    {
        foreach ($allowed as $key) if (str_contains(mb_strtolower($activity), mb_strtolower((string)$key))) return (string)$key;
        return (string)($allowed[0] ?? 'OTHER');
    }
}
