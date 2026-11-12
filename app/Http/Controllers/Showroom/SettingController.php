<?php

namespace App\Http\Controllers\Showroom;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\DemoData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function edit(): View
    {
        return view('showroom.settings', ['settings' => Setting::current()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'tagline' => ['nullable', 'string', 'max:150'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'call_center' => ['nullable', 'string', 'max:40'],
            'whatsapp' => ['nullable', 'regex:/^\d{8,16}$/'],
            'email' => ['nullable', 'email', 'max:120'],
            'instagram' => ['nullable', 'string', 'max:60'],
            'tiktok' => ['nullable', 'string', 'max:60'],
            'youtube' => ['nullable', 'string', 'max:80'],
            'support_partner' => ['nullable', 'string', 'max:80'],
            'cashier_name' => ['required', 'string', 'max:80'],
            'receipt_footer' => ['nullable', 'string', 'max:500'],
            'tax_rate' => ['required', 'numeric', 'between:0,100'],
            'credit_rate' => ['required', 'numeric', 'between:0,30'],
            'min_dp_percent' => ['required', 'numeric', 'between:0,90'],
        ], [
            'name.required' => 'Nama showroom wajib diisi.',
            'cashier_name.required' => 'Nama kasir wajib diisi.',
            'whatsapp.regex' => 'WhatsApp harus berupa angka format 628xxx, tanpa spasi atau tanda.',
            'tax_rate.between' => 'Tarif PPN harus antara 0 dan 100.',
            'credit_rate.between' => 'Bunga flat harus antara 0 dan 30.',
            'min_dp_percent.between' => 'DP minimal harus antara 0 dan 90.',
        ]);

        // Username media sosial disimpan tanpa "@"
        foreach (['instagram', 'tiktok'] as $key) {
            $data[$key] = ltrim(trim((string) ($data[$key] ?? '')), '@') ?: null;
        }
        $data['tax_enabled'] = $request->boolean('tax_enabled');

        Setting::current()->update($data);

        return back()->with('success', 'Pengaturan showroom disimpan.');
    }

    public function reset(): RedirectResponse
    {
        DemoData::reset();

        return redirect()->route('showroom.dashboard')->with('success', 'Semua data dikembalikan ke data demo awal.');
    }
}
