<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContactFormRequest;
use App\Models\ContactMessage;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;

class ContactController extends Controller
{
    public function index()
    {
        $settings = SiteSetting::current();

        return view('public.contact', compact('settings'));
    }

    public function store(ContactFormRequest $request): RedirectResponse
    {
        ContactMessage::create($request->validated());

        return back()->with('success', 'Thank you! Your message has been sent successfully.');
    }
}
