<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Services\ImageUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(private ImageUploadService $uploader) {}

    public function edit(): View
    {
        $settings = SiteSetting::current();

        return view('admin.settings.edit', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'model_name' => 'required|string|max:255',
            'model_title' => 'required|string|max:255',
            'intro_text' => 'nullable|string|max:2000',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'hero_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'instagram_url' => 'nullable|url|max:255',
            'tiktok_url' => 'nullable|url|max:255',
            'facebook_url' => 'nullable|url|max:255',
            'youtube_url' => 'nullable|url|max:255',
            'twitter_url' => 'nullable|url|max:255',
            'linkedin_url' => 'nullable|url|max:255',
            'whatsapp_number' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
        ]);

        $settings = SiteSetting::current();
        $data = collect($validated)->except('hero_image')->toArray();

        if ($request->hasFile('hero_image')) {
            $this->uploader->delete($settings->hero_image);
            $data['hero_image'] = $this->uploader->store($request->file('hero_image'), 'hero');
        }

        $settings->update($data);
        SiteSetting::clearCache();

        return back()->with('success', 'Site settings updated successfully.');
    }
}
