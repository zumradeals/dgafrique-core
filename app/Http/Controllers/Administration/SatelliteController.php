<?php

declare(strict_types=1);

namespace App\Http\Controllers\Administration;

use App\Domain\Identity\CoreIdentity;
use App\Models\Satellite;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class SatelliteController
{
    public function index(): View
    {
        return view('administration.satellites', [
            'satellites' => Satellite::query()->orderBy('display_name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var CoreIdentity $identity */
        $identity = $request->attributes->get('dg_identity');
        $data = $this->validated($request);

        Satellite::query()->create($data + ['created_by_core_reference' => $identity->reference, 'is_active' => true]);

        return back()->with('status', 'Satellite déclaré dans le registre.');
    }

    public function update(Request $request, Satellite $satellite): RedirectResponse
    {
        $data = $this->validated($request, $satellite->id);
        $satellite->update($data);

        return back()->with('status', 'Satellite mis à jour.');
    }

    public function toggle(Satellite $satellite): RedirectResponse
    {
        $satellite->update(['is_active' => ! $satellite->is_active]);

        return back()->with('status', $satellite->is_active ? 'Satellite réactivé.' : 'Satellite désactivé.');
    }

    private function validated(Request $request, ?string $ignoreId = null): array
    {
        return $request->validate([
            'product_reference' => ['required', 'string', 'max:64', Rule::unique('dg_satellites', 'product_reference')->ignore($ignoreId)],
            'display_name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'callback_url' => ['nullable', 'url', 'starts_with:https://', 'max:255'],
        ]);
    }
}
