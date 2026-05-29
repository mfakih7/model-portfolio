@extends('layouts.admin')

@section('title', 'Account Settings')

@section('page_title', 'Account Settings')

@section('content')
<div class="admin-card" style="max-width: 720px;">
    <form action="{{ route('admin.account.update') }}" method="POST" autocomplete="off">
        @csrf @method('PUT')

        <h5 class="mb-3">Profile</h5>
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">Name *</label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Email *</label>
                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required>
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>

        <hr class="my-4">

        <h5 class="mb-1">Change Password</h5>
        <p class="text-muted small mb-3">Leave the password fields blank to keep your current password.</p>

        <div class="mb-3">
            <label class="form-label">Current Password</label>
            <input type="password" name="current_password" class="form-control @error('current_password') is-invalid @enderror" autocomplete="current-password">
            @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="row mb-4">
            <div class="col-md-6">
                <label class="form-label">New Password</label>
                <input type="password" name="new_password" class="form-control @error('new_password') is-invalid @enderror" autocomplete="new-password">
                @error('new_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <small class="text-muted">Minimum 8 characters.</small>
            </div>
            <div class="col-md-6">
                <label class="form-label">Confirm New Password</label>
                <input type="password" name="new_password_confirmation" class="form-control" autocomplete="new-password">
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Update Account</button>
    </form>
</div>
@endsection
