@extends('layouts.admin')

@section('page_title', 'Portfolio Images')

@section('page_actions')
<a href="{{ route('admin.portfolio.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Add Image</a>
@endsection

@section('content')
<div class="admin-card">
    @if($images->count())
    <form action="{{ route('admin.portfolio.reorder') }}" method="POST" id="reorderForm">
        @csrf
        <p class="text-muted small">Drag rows to reorder, then click Save Order.</p>
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="sortableTable">
                <thead>
                    <tr>
                        <th width="40"></th>
                        <th>Image</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Featured</th>
                        <th>Order</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($images as $image)
                    <tr data-id="{{ $image->id }}">
                        <td><i class="bi bi-grip-vertical text-muted handle" style="cursor: grab"></i></td>
                        <td><img src="{{ $image->thumb_url }}" class="thumb-preview" alt="{{ $image->title }}" loading="lazy"></td>
                        <td>{{ $image->title }}</td>
                        <td><span class="badge bg-secondary">{{ $image->category->name }}</span></td>
                        <td>@if($image->is_featured)<i class="bi bi-star-fill text-warning"></i>@endif</td>
                        <td><input type="hidden" name="order[]" value="{{ $image->id }}">{{ $image->sort_order }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.portfolio.edit', $image) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            <form action="{{ route('admin.portfolio.destroy', $image) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this image?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <button type="submit" class="btn btn-secondary btn-sm mt-2">Save Order</button>
    </form>
    @else
    <p class="text-muted mb-0">No portfolio images yet. <a href="{{ route('admin.portfolio.create') }}">Upload your first image</a>.</p>
    @endif
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
const tbody = document.querySelector('#sortableTable tbody');
if (tbody) {
    new Sortable(tbody, {
        handle: '.handle',
        animation: 150,
        onEnd: () => {
            const inputs = tbody.querySelectorAll('input[name="order[]"]');
            [...tbody.querySelectorAll('tr')].forEach((row, i) => {
                inputs[i].value = row.dataset.id;
            });
        }
    });
}
</script>
@endpush
