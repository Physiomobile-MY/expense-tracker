<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->isDirector(), 403);

        $openai = SystemSetting::firstOrCreate(['key' => 'openai'], [
            'value' => [
                'enabled' => (bool) config('services.openai.receipt_extraction_enabled'),
                'model' => config('services.openai.receipt_model'),
                'daily_scan_limit' => (int) config('services.openai.daily_scan_limit'),
            ],
        ]);

        return view('admin.settings.index', compact('openai'));
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isDirector(), 403);

        $validated = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'model' => ['required', 'string', 'max:100'],
            'daily_scan_limit' => ['required', 'integer', 'min:0', 'max:10000'],
        ]);

        SystemSetting::updateOrCreate(['key' => 'openai'], [
            'value' => [
                'enabled' => $request->boolean('enabled'),
                'model' => $validated['model'],
                'daily_scan_limit' => (int) $validated['daily_scan_limit'],
            ],
        ]);

        return back()->with('status', 'Settings saved. Update `.env` to apply API credentials or model values at runtime.');
    }
}
