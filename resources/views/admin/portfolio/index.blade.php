@extends('layouts.admin')

@section('page_title', 'Portfolio Images')

@section('page_actions')
<a href="{{ route('admin.portfolio.create') }}" class="btn btn-primary btn-sm admin-btn-action"><i class="bi bi-plus-lg"></i> Add Image</a>
@endsection

@section('content')
<div class="admin-card">
    @if($images->count())
    {{-- Reorder form holds only its CSRF + submit button. Order inputs live in
         #orderInputs and are updated by Sortable on desktop table or mobile cards. --}}
    <form action="{{ route('admin.portfolio.reorder') }}" method="POST" id="reorderForm">@csrf</form>

    <div id="orderInputs" class="visually-hidden" aria-hidden="true">
        @foreach($images as $image)
        <input type="hidden" name="order[]" value="{{ $image->id }}" form="reorderForm">
        @endforeach
    </div>

    <p class="text-muted small mb-3">Drag to reorder, then click Save Order.</p>

    {{-- Desktop table --}}
    <div class="table-responsive admin-table-desktop d-none d-lg-block">
        <table class="table table-hover align-middle mb-0" id="sortableTable">
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
                    <td>{{ $image->sort_order }}</td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('admin.portfolio.edit', $image) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                        <button type="submit" form="delete-image-{{ $image->id }}" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this image?')">Delete</button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Mobile cards --}}
    <div class="admin-mobile-list d-lg-none" id="sortableCards">
        @foreach($images as $image)
        <article class="admin-mobile-card portfolio-mobile-card" data-id="{{ $image->id }}">
            <div class="admin-mobile-card-top">
                <button type="button" class="admin-card-handle handle" aria-label="Drag to reorder">
                    <i class="bi bi-grip-vertical"></i>
                </button>
                <img src="{{ $image->thumb_url }}" class="portfolio-mobile-thumb" alt="{{ $image->title }}" loading="lazy">
                <div class="admin-mobile-card-info">
                    <h6 class="admin-mobile-card-title mb-1">{{ $image->title }}</h6>
                    <span class="badge bg-secondary">{{ $image->category->name }}</span>
                </div>
            </div>
            <div class="admin-mobile-card-meta">
                <span class="admin-mobile-meta-item">
                    <span class="text-muted">Order</span>
                    <strong>{{ $image->sort_order }}</strong>
                </span>
                <span class="admin-mobile-meta-item">
                    <span class="text-muted">Featured</span>
                    @if($image->is_featured)
                        <i class="bi bi-star-fill text-warning"></i>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </span>
            </div>
            <div class="admin-mobile-card-actions">
                <a href="{{ route('admin.portfolio.edit', $image) }}" class="btn btn-outline-primary">Edit</a>
                <button type="submit" form="delete-image-{{ $image->id }}" class="btn btn-outline-danger" onclick="return confirm('Delete this image?')">Delete</button>
            </div>
        </article>
        @endforeach
    </div>

    <button type="submit" form="reorderForm" class="btn btn-secondary btn-sm mt-3 admin-btn-save-order">Save Order</button>

    @foreach($images as $image)
    <form id="delete-image-{{ $image->id }}" action="{{ route('admin.portfolio.destroy', $image) }}" method="POST" class="d-none">
        @csrf @method('DELETE')
    </form>
    @endforeach
    @else
    <p class="text-muted mb-0">No portfolio images yet. <a href="{{ route('admin.portfolio.create') }}">Upload your first image</a>.</p>
    @endif
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
(function () {
    const orderInputs = document.getElementById('orderInputs');
    const tbody = document.querySelector('#sortableTable tbody');
    const cards = document.getElementById('sortableCards');

    const syncOrder = (container, itemSelector) => {
        if (!orderInputs || !container) return;
        const inputs = orderInputs.querySelectorAll('input[name="order[]"]');
        [...container.querySelectorAll(itemSelector)].forEach((el, i) => {
            if (inputs[i]) {
                inputs[i].value = el.dataset.id;
            }
        });
    };

    if (tbody && typeof Sortable !== 'undefined') {
        new Sortable(tbody, {
            handle: '.handle',
            animation: 150,
            onEnd: () => syncOrder(tbody, 'tr'),
        });
    }

    if (cards && typeof Sortable !== 'undefined') {
        new Sortable(cards, {
            handle: '.handle',
            animation: 150,
            onEnd: () => syncOrder(cards, '.portfolio-mobile-card'),
        });
    }
})();
</script>
@endpush
