<?php

declare(strict_types=1);
namespace App\Http\Controllers;
use App\Application\Missions\MissionVisibilityService; use App\Application\Needs\NeedConfiguration; use App\Application\Needs\NeedService; use App\Application\ProjectBrain\ProjectBrainNeedDraftService; use App\Application\Projects\ProjectService; use App\Domain\Identity\CoreIdentity; use App\Models\Mission; use App\Models\Need; use App\Models\PersonProfile; use App\Models\PortalAdministrator; use App\Models\Project; use App\Models\ProjectBrainConversation; use App\Models\ProjectBrainDraft; use App\Models\ProjectBrainMessage; use App\Models\ProjectTeamMember; use Illuminate\Http\RedirectResponse; use Illuminate\Http\Request; use Illuminate\View\View;
final class ProjectBrainController {
 // UX-HARMONY-CLEANUP-001 — l'ancienne vue « vue-ensemble » (projects.overview-v2) mélangeait des données Core réelles à des projections métier fictives codées en dur (montants de financement, missions, preuves). Tout son contenu réel est déjà présent sur projects.show, qui l'absorbe entièrement : cette route redirige désormais vers la Fiche Projet réelle plutôt que d'exposer des chiffres inventés.
 public function overview(Request $request,Project $project,ProjectService $projects):RedirectResponse { /** @var CoreIdentity $identity */ $identity=$request->attributes->get('dg_identity'); abort_unless($projects->canView($project,$identity->reference),404); return redirect()->route('projects.show',$project); }
 public function show(Request $request,Project $project,ProjectService $projects,NeedService $needs,NeedConfiguration $needConfiguration,ProjectBrainNeedDraftService $brain,MissionVisibilityService $missionVisibility):View {
  /** @var CoreIdentity $identity */
  $identity=$request->attributes->get('dg_identity');
  abort_unless($projects->canView($project,$identity->reference),404);
  // Colonne gauche : mes projets réels, même filtrage que ProjectController::index() — jamais une liste fabriquée.
  $visibleProjects=Project::query()->where('status','!=',Project::STATUS_ARCHIVED)->latest('updated_at')->limit(30)->get()->filter(fn(Project $p):bool=>$projects->canView($p,$identity->reference))->values();
  // Une ProjectBrainConversation est scopée (project, actor) : indicateur réel « a déjà une conversation », jamais des fils fabriqués.
  $conversationProjectIds=ProjectBrainConversation::query()->where('actor_core_reference',$identity->reference)->whereIn('project_id',$visibleProjects->pluck('id'))->pluck('project_id')->all();
  $archivedProjectsCount=Project::query()->where('status',Project::STATUS_ARCHIVED)->limit(300)->get()->filter(fn(Project $p):bool=>$projects->canView($p,$identity->reference))->count();
  $projectNeeds=Need::query()->where('owner_type',Need::OWNER_PROJECT)->where('owner_reference',$project->id)->where('status','!=',Need::STATUS_ARCHIVED)->latest()->limit(20)->get()->filter(fn(Need $n):bool=>$needs->canView($n,$identity->reference))->values();
  $teamMembers=ProjectTeamMember::query()->where('project_id',$project->id)->where('status',ProjectTeamMember::STATUS_ACTIVE)->get();
  $teamProfiles=PersonProfile::query()->whereIn('core_identity_reference',$teamMembers->pluck('core_identity_reference'))->get()->keyBy('core_identity_reference');
  // CAP-missions : missions réelles rattachées au projet (context_type=PROJECT), jamais un moteur reconstruit ici.
  $missions=Mission::query()->where('context_type','PROJECT')->where('context_reference',$project->public_reference)->whereNotIn('status',Mission::TERMINAL_STATUSES)->latest()->limit(6)->get()->filter(fn(Mission $m):bool=>$missionVisibility->canViewMission($m,$identity->reference))->values();
  $nextMilestone=$project->milestones()->where('status','!=','COMPLETED')->orderBy('position')->first();
  $conversation=$brain->conversation($project,$identity->reference);
  $messages=ProjectBrainMessage::query()->where('conversation_id',$conversation->id)->oldest()->limit(60)->get();
  $drafts=ProjectBrainDraft::query()->where('conversation_id',$conversation->id)->where('status',ProjectBrainDraft::STATUS_PENDING)->latest()->get()->keyBy('id');
  $isAdministrator=PortalAdministrator::query()->whereKey($identity->reference)->exists();
  return view('projects.brain',['identity'=>$identity,'project'=>$project,'visibleProjects'=>$visibleProjects,'conversationProjectIds'=>$conversationProjectIds,'archivedProjectsCount'=>$archivedProjectsCount,'projectNeeds'=>$projectNeeds,'teamMembers'=>$teamMembers,'teamProfiles'=>$teamProfiles,'missions'=>$missions,'nextMilestone'=>$nextMilestone,'progressSeed'=>$project->progressionSeed(),'conversation'=>$conversation,'messages'=>$messages,'drafts'=>$drafts,'isAdministrator'=>$isAdministrator,'needConfiguration'=>$needConfiguration->get()]);
 }
 public function prepareNeed(Request $request,Project $project,ProjectService $projects,ProjectBrainNeedDraftService $brain):RedirectResponse { /** @var CoreIdentity $identity */ $identity=$request->attributes->get('dg_identity'); abort_unless($projects->canView($project,$identity->reference),404); $data=$request->validate(['message'=>['required','string','min:2','max:3000']]); $draft=$brain->prepare($project,$identity->reference,$data['message']); return redirect()->route('projects.brain.show',$project)->with('status',$draft?'Le Cerveau propose une action. Vérifiez-la : rien n’est créé avant votre confirmation.':'Le Cerveau a pris en compte votre message.'); }
 public function confirmNeed(Request $request,Project $project,ProjectBrainDraft $draft,ProjectService $projects,ProjectBrainNeedDraftService $brain):RedirectResponse { /** @var CoreIdentity $identity */ $identity=$request->attributes->get('dg_identity'); abort_unless($projects->canView($project,$identity->reference),404); $need=$brain->confirm($project,$draft,$identity->reference); return redirect()->route('projects.brain.show',$project)->with('status','Besoin créé dans le Core : '.$need->title); }
 public function cancelDraft(Request $request,Project $project,ProjectBrainDraft $draft,ProjectService $projects,ProjectBrainNeedDraftService $brain):RedirectResponse { /** @var CoreIdentity $identity */ $identity=$request->attributes->get('dg_identity'); abort_unless($projects->canView($project,$identity->reference),404); $brain->cancel($project,$draft,$identity->reference); return redirect()->route('projects.brain.show',$project)->with('status','Proposition abandonnée. Aucune mutation métier.'); }
}
