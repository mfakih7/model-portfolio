@extends('layouts.admin')

@section('page_title', 'Message from ' . $message->name)

@section('content')
<div class="admin-card">
    <p><strong>From:</strong> {{ $message->name }} &lt;{{ $message->email }}&gt;</p>
    <p><strong>Received:</strong> {{ $message->created_at->format('F j, Y \a\t g:i A') }}</p>
    <hr>
    <div class="mb-4" style="white-space: pre-wrap;">{{ $message->message }}</div>
    <div class="d-flex gap-2">
        <form action="{{ route('admin.messages.toggle-read', $message) }}" method="POST">@csrf @method('PATCH')
            <button class="btn btn-outline-secondary">Mark as {{ $message->is_read ? 'Unread' : 'Read' }}</button>
        </form>
        <form action="{{ route('admin.messages.destroy', $message) }}" method="POST" onsubmit="return confirm('Delete message?')">@csrf @method('DELETE')
            <button class="btn btn-outline-danger">Delete</button>
        </form>
        <a href="{{ route('admin.messages.index') }}" class="btn btn-link">Back</a>
    </div>
</div>
@endsection
