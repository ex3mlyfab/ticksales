<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApplicationSetting;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class BrandingController extends Controller
{
    public function edit(): Response
    {
        $setting = ApplicationSetting::query()->firstOrCreate();

        return Inertia::render('admin/branding', [
            'branding' => [
                'app_name' => $setting->app_name,
                'currency' => $setting->currency,
                'logo_url' => $setting->logo_path
                    ? Storage::disk('public')->url($setting->logo_path)
                    : null,
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'app_name' => ['required', 'string', 'max:100'],
            'currency' => ['required', 'string', 'size:3', 'alpha', 'uppercase'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $setting = ApplicationSetting::query()->firstOrCreate();
        $currency = strtoupper($validated['currency']);

        if ($request->hasFile('logo')) {
            if ($setting->logo_path) {
                Storage::disk('public')->delete($setting->logo_path);
            }

            $setting->logo_path = $request->file('logo')->store('branding', 'public');
        }

        $setting->app_name = $validated['app_name'];
        $setting->currency = $currency;
        $setting->save();

        Tenant::query()->where(function ($query) use ($currency): void {
            $query->whereNull('currency')->orWhere('currency', '!=', $currency);
        })->update(['currency' => $currency]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Branding updated.')]);

        return redirect()->route('admin.branding.edit');
    }
}
