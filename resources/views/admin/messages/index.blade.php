@extends('layouts.admin')

@section('page_title', 'Contact Messages')

@section('content')
@if($messages->count())
<div class="admin-card admin-card-flush d-md-none">
    <div class="admin-mobile-list">
        @foreach($messages as $message)
        <article class="admin-list-card {{ !$message->is_read ? 'admin-list-card-unread' : '' }}">
            <div class="admin-list-card-body">
                <h6 class="admin-list-card-title">{{ $message->name }}</h6>
                <a href="mailto:{{ $message->email }}" class="admin-list-card-sub text-break">{{ $message->email }}</a>
                <div class="admin-list-card-meta">
                    <span class="admin-pill admin-pill-muted">{{ $message->created_at->format('M d, Y') }}</span>
                    <span class="admin-pill {{ $message->is_read ? 'admin-pill-muted' : 'admin-pill-info' }}">
                        {{ $message->is_read ? 'Read' : 'Unread' }}
                    </span>
                </div>
            </div>
            <div class="admin-list-card-actions">
                <a href="{{ route('admin.messages.show', $message) }}" class="btn btn-admin-sm btn-admin-edit w-100">View</a>
            </div>
        </article>
        @endforeach
    </div>
    <div class="admin-pagination mt-3">
        {{ $messages->links() }}
    </div>
</div>

<div class="admin-card d-none d-md-block">
    <div class="table-responsive admin-table-desktop">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr><th>Name</th><th>Email</th><th>Date</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
                @foreach($messages as $message)
                <tr class="{{ !$message->is_read ? 'table-warning' : '' }}">
                    <td>{{ $message->name }}</td>
                    <td>{{ $message->email }}</td>
                    <td class="text-nowrap">{{ $message->created_at->format('M d, Y H:i') }}</td>
                    <td><span class="badge {{ $message->is_read ? 'bg-secondary' : 'bg-primary' }}">{{ $message->is_read ? 'Read' : 'Unread' }}</span></td>
                    <td class="text-end">
                        <a href="{{ route('admin.messages.show', $message) }}" class="btn btn-sm btn-outline-primary">View</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-3 admin-pagination">
        {{ $messages->links() }}
    </div>
</div>
@else
<div class="admin-card">
    <p class="text-muted mb-0">No messages yet.</p>
</div>
@endif
@endsection
