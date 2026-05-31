@extends('layouts.admin')

@section('page_title', 'Contact Messages')

@section('content')
<div class="admin-card">
  @if($messages->count())
  {{-- Desktop table --}}
  <div class="table-responsive admin-table-desktop d-none d-lg-block">
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

  {{-- Mobile cards --}}
  <div class="admin-mobile-list d-lg-none">
    @foreach($messages as $message)
    <article class="admin-mobile-card {{ !$message->is_read ? 'admin-mobile-card-unread' : '' }}">
      <div class="admin-mobile-card-info mb-2">
        <h6 class="admin-mobile-card-title mb-1">{{ $message->name }}</h6>
        <a href="mailto:{{ $message->email }}" class="small text-break">{{ $message->email }}</a>
      </div>
      <div class="admin-mobile-card-meta">
        <span class="admin-mobile-meta-item">
          <span class="text-muted">Date</span>
          <strong>{{ $message->created_at->format('M d, Y') }}</strong>
        </span>
        <span class="admin-mobile-meta-item">
          <span class="text-muted">Status</span>
          <span class="badge {{ $message->is_read ? 'bg-secondary' : 'bg-primary' }}">{{ $message->is_read ? 'Read' : 'Unread' }}</span>
        </span>
      </div>
      <div class="admin-mobile-card-actions">
        <a href="{{ route('admin.messages.show', $message) }}" class="btn btn-outline-primary">View Message</a>
      </div>
    </article>
    @endforeach
  </div>

  <div class="mt-3 admin-pagination">
    {{ $messages->links() }}
  </div>
  @else
  <p class="text-muted mb-0">No messages yet.</p>
  @endif
</div>
@endsection
