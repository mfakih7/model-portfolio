<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AboutContent;
use App\Services\ImageUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AboutController extends Controller
{
    public function __construct(private ImageUploadService $uploader) {}

    public function edit(): View
    {
        $about = AboutContent::current();

        return view('admin.about.edit', compact('about'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'short_bio' => 'nullable|string|max:2000',
            'full_story' => 'nullable|string',
            'main_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'additional_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $about = AboutContent::current();
        $data = collect($validated)->except(['main_image', 'additional_image'])->toArray();

        if ($request->hasFile('main_image')) {
            $this->uploader->delete($about->main_image);
            $data['main_image'] = $this->uploader->store($request->file('main_image'), 'about');
        }

        if ($request->hasFile('additional_image')) {
            $this->uploader->delete($about->additional_image);
            $data['additional_image'] = $this->uploader->store($request->file('additional_image'), 'about');
        }

        $about->update($data);

        return back()->with('success', 'About content updated successfully.');
    }
}
