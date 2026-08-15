<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Projects\ProjectAccompanimentConfiguration;
use App\Application\Projects\ProjectAccompanimentService;
use App\Models\PortalAdministrator;
use App\Models\PortalSetting;
use App\Models\Project;
use App\Models\ProjectAccompaniment;
use App\Models\ProjectAccompanimentAction;
use App\Models\ProjectEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

final class ProjectAccompanimentTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_activates_and_ends_accompaniment_without_transferring_control(): void
    {
        $project = $this->project();
        $service = app(ProjectAccompanimentService::class);
        $accompaniment = $service->activate($project, 'IDN-OWNER');
        self::assertSame(ProjectAccompaniment::STATUS_ACTIVE, $accompaniment->status);
        self::assertSame('IDN-OWNER', $project->refresh()->owner_reference);
        self::assertSame('PERSONAL_SUPPORTED', $project->property_regime);
        self::assertSame('IDEA', $project->maturity);
        $service->end($project, 'IDN-OWNER');
        self::assertSame(ProjectAccompaniment::STATUS_ENDED, $accompaniment->refresh()->status);
        self::assertSame(['ACCOMPANIMENT_ACTIVATED','ACCOMPANIMENT_ENDED'], ProjectEvent::query()->orderBy('occurred_at')->pluck('event')->all());
    }

    public function test_internal_and_partner_interventions_are_append_only_traceable_facts(): void
    {
        PortalAdministrator::query()->create(['core_identity_reference'=>'IDN-ADMIN']);
        $project=$this->project(); $service=app(ProjectAccompanimentService::class); $settings=(new ProjectAccompanimentConfiguration())->defaults();
        $service->activate($project,'IDN-OWNER');
        $internal=$service->recordAction($project,'IDN-ADMIN',['action_type'=>'ORIENTATION','delivery_source'=>ProjectAccompanimentAction::SOURCE_INTERNAL,'provider_label'=>'','summary'=>'Clarification des prochaines étapes de structuration du projet.','next_step'=>'Préparer les éléments du premier atelier.'],$settings);
        $partner=$service->recordAction($project,'IDN-ADMIN',['action_type'=>'TRAINING','delivery_source'=>ProjectAccompanimentAction::SOURCE_PARTNER,'provider_label'=>'Partenaire formation','summary'=>'Session pratique coordonnée avec un partenaire pour renforcer l’équipe.','next_step'=>null],$settings);
        self::assertSame('DG Afrique',$internal->provider_label); self::assertSame('Partenaire formation',$partner->provider_label);
        self::assertSame(2,ProjectAccompanimentAction::query()->count()); self::assertSame(2,ProjectEvent::query()->where('event','ACCOMPANIMENT_ACTION_RECORDED')->count());
    }

    public function test_outsider_cannot_activate_accompaniment(): void
    {
        $this->expectException(HttpException::class);
        app(ProjectAccompanimentService::class)->activate($this->project(),'IDN-OUTSIDER');
    }

    public function test_only_a_provisioned_portal_administrator_records_an_intervention(): void
    {
        $project=$this->project(); $service=app(ProjectAccompanimentService::class); $service->activate($project,'IDN-OWNER');
        $this->expectException(HttpException::class);
        $service->recordAction($project,'IDN-NOT-ADMIN',['action_type'=>'EXPERTISE','delivery_source'=>ProjectAccompanimentAction::SOURCE_INTERNAL,'provider_label'=>'DG Afrique','summary'=>'Une expertise factuelle qui ne doit pas être enregistrée par cet acteur.','next_step'=>null],(new ProjectAccompanimentConfiguration())->defaults());
    }

    public function test_financing_guidance_never_creates_project_finance_or_changes_ownership(): void
    {
        PortalAdministrator::query()->create(['core_identity_reference'=>'IDN-ADMIN']);
        $project=$this->project(); $service=app(ProjectAccompanimentService::class); $service->activate($project,'IDN-OWNER');
        $action=$service->recordAction($project,'IDN-ADMIN',['action_type'=>'FINANCING_MECHANISM','delivery_source'=>ProjectAccompanimentAction::SOURCE_INTERNAL,'provider_label'=>'DG Afrique','summary'=>'Orientation vers un dispositif disponible, sans promesse ni ouverture de financement.','next_step'=>'Vérifier les conditions du dispositif séparément.'],(new ProjectAccompanimentConfiguration())->defaults());
        self::assertSame('FINANCING_MECHANISM',$action->action_type); self::assertSame('IDN-OWNER',$project->refresh()->owner_reference); self::assertSame('PERSONAL_SUPPORTED',$project->property_regime);
        self::assertFalse(Schema::hasTable('dg_project_payments')); self::assertFalse(Schema::hasTable('dg_project_funding'));
    }

    public function test_admin_configures_available_interventions_without_deployment(): void
    {
        PortalAdministrator::query()->create(['core_identity_reference'=>'IDN-ADMIN']); $this->signIn('IDN-ADMIN');
        $this->put('/administration/accompagnement-projets',['page_title'=>'Accompagner sans prendre le contrôle','page_intro'=>'Des interventions progressives, traçables et adaptées aux dispositifs réellement disponibles.','action_type_codes'=>['ORIENTATION','LEGAL_REVIEW'],'action_type_labels'=>['Orientation','Relecture juridique']])->assertRedirect();
        $stored=PortalSetting::query()->findOrFail(ProjectAccompanimentConfiguration::KEY); self::assertSame('Relecture juridique',$stored->value['action_types']['LEGAL_REVIEW']); self::assertSame('IDN-ADMIN',$stored->updated_by_core_reference);
    }

    private function project(): Project
    {
        return Project::query()->create(['public_reference'=>'6f84f6c0-1b31-4cb0-9f3d-6efbc6b80d31','owner_type'=>Project::OWNER_PERSON,'owner_reference'=>'IDN-OWNER','initiator_core_reference'=>'IDN-OWNER','name'=>'Atelier numérique communautaire','summary'=>'Un projet concret destiné à construire des capacités utiles de façon progressive.','problem'=>'Des personnes motivées manquent de cadre pratique pour progresser ensemble.','proposed_solution'=>'Créer un atelier progressif avec des actions simples et des preuves concrètes.','beneficiaries'=>'Jeunes débutants et personnes en reconversion.','domain'=>'DIGITAL','participation_mode'=>'HYBRID','objectives'=>['Former une équipe'],'required_capabilities'=>['Formation numérique'],'required_resources'=>['Ordinateurs'],'risks'=>[],'property_regime'=>'PERSONAL_SUPPORTED','visibility'=>Project::VISIBILITY_PRIVATE,'status'=>Project::STATUS_IN_PROGRESS,'maturity'=>'IDEA']);
    }

    private function signIn(string $reference): void
    {
        Http::fake(['core.test/api/v1/sessions'=>Http::response(['jeton'=>'bearer-'.$reference,'entite'=>$reference,'assurance'=>'AS1','expire_le'=>'2026-08-16T23:59:00+00:00'],201),'core.test/api/v1/identites/*'=>Http::response(['reference'=>$reference,'type'=>'personne','libelle'=>'Administrateur DG Afrique','etat'=>'ACTIF','source'=>'CORE','regime'=>'INSCRIT_AU_REGISTRE']),'core.test/api/v1/sessions/current'=>Http::response(['entite'=>$reference,'assurance'=>'AS1','expire_le'=>'2026-08-16T23:59:00+00:00'])]);
        $this->post('/connexion',['identifier'=>$reference,'secret'=>'secret'])->assertRedirect('/espace');
    }
}
