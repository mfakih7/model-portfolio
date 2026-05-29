@extends('layouts.admin')

@section('page_title', 'Contact Messages')

@section('content')
<div class="admin-card">
  @if($messages->count())
  <div class="table-responsive">
    <table class="table table-hover align-middle">
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
  {{ $messages->links() }}
  @else
  <p class="text-muted mb-0">No messages yet.</p>
  @endif
</div>
@endsection
