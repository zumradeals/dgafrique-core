<?php

declare(strict_types=1);

namespace App\Http\Controllers\Administration;

use App\Application\Zumra\ZumraProgramConfiguration;
use App\Domain\Identity\CoreIdentity;
use App\Models\PortalSetting;
use App\Models\ZumraCharter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class ZumraProgramConfigurationController
{
    public function edit(ZumraProgramConfiguration $configuration): View
    {
        return view('administration.zumra-program', [
            'configuration' => $configuration->get(),
            'activeCharter' => ZumraCharter::query()->where('status', ZumraCharter::STATUS_PUBLISHED)->latest('published_at')->first(),
            'charters' => ZumraCharter::query()->latest('published_at')->limit(20)->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'introduction' => ['required', 'string', 'max:600'],
            'commitment_notice' => ['required', 'string', 'max:600'],
            'pending_payment_title' => ['required', 'string', 'max:200'],
            'pending_payment_help' => ['required', 'string', 'max:600'],
            'accept_label' => ['required', 'string', 'max:240'],
            'submit_label' => ['required', 'string', 'max:100'],
            'payment_unavailable_notice' => ['required', 'string', 'max:500'],
        ]);

        PortalSetting::query()->updateOrCreate(
            ['key' => ZumraProgramConfiguration::KEY],
            ['value' => $data, 'updated_by_core_reference' => $identity->reference],
        );

        return back()->with('status', 'Présentation du Programme ZUMRA enregistrée.');
    }

    public function publishCharter(Request $request): RedirectResponse
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $data = $request->validate([
            'version' => ['required', 'string', 'max:40', 'regex:/^[A-Za-z0-9._-]+$/', 'unique:dg_zumra_charters,version'],
            'charter_title' => ['required', 'string', 'max:200'],
            'charter_body' => ['required', 'string', 'min:100', 'max:30000'],
        ]);
        $body = trim($data['charter_body']);
        $hash = hash('sha256', $data['charter_title']."\n".$body);

        DB::transaction(static function () use ($data, $body, $hash, $identity): void {
            ZumraCharter::query()->where('status', ZumraCharter::STATUS_PUBLISHED)
                ->update(['status' => ZumraCharter::STATUS_RETIRED]);
            ZumraCharter::query()->create([
                'version' => $data['version'], 'title' => $data['charter_title'], 'body' => $body,
                'content_hash' => $hash, 'status' => ZumraCharter::STATUS_PUBLISHED,
                'published_at' => now(), 'published_by_core_reference' => $identity->reference,
            ]);
        });

        return back()->with('status', 'Nouvelle version de la charte publiée. Les versions antérieures restent conservées.');
    }
}
