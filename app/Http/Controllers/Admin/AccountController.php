<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function edit(Request $request): View
    {
        return view('admin.account.edit', ['user' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            // The current password is only required when the admin is setting a new one,
            // and the `current_password` rule verifies it via Hash::check under the hood.
            'current_password' => ['nullable', 'required_with:new_password', 'current_password'],
            'new_password' => ['nullable', 'confirmed', Password::min(8)],
        ], [
            'current_password.current_password' => 'The current password is incorrect.',
            'current_password.required_with' => 'Please enter your current password to set a new one.',
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];

        if (! empty($validated['new_password'])) {
            // The User model casts `password` as "hashed", so assigning the plain
            // value hashes it automatically (equivalent to Hash::make).
            $user->password = $validated['new_password'];
        }

        $user->save();

        return redirect()
            ->route('admin.account.edit')
            ->with('success', 'Account settings updated successfully.');
    }
}
