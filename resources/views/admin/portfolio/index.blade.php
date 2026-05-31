@extends('layouts.admin')

@section('page_title', 'Portfolio Images')

@section('page_actions')
<a href="{{ route('admin.portfolio.create') }}" class="btn btn-admin-primary admin-btn-action">
    <i class="bi bi-plus-lg"></i> Add Image
</a>
@endsection

@section('content')
@if($images->count())
<form action="{{ route('admin.portfolio.reorder') }}" method="POST" id="reorderForm">@csrf</form>

<div id="orderInputs" class="visually-hidden" aria-hidden="true">
    @foreach($images as $image)
    <input type="hidden" name="order[]" value="{{ $image->id }}" form="reorderForm">
    @endforeach
</div>

@foreach($images as $image)
<form id="delete-image-{{ $image->id }}" action="{{ route('admin.portfolio.destroy', $image) }}" method="POST" class="d-none">
    @csrf @method('DELETE')
</form>
@endforeach

{{-- Desktop table (768px+) --}}
<div class="admin-card d-none d-md-block">
    <p class="text-muted small mb-3">Drag rows to reorder, then click Save Order.</p>
    <div class="table-responsive admin-table-desktop">
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
    <button type="submit" form="reorderForm" class="btn btn-secondary btn-sm mt-3">Save Order</button>
</div>

{{-- Mobile cards (<768px) --}}
<div class="d-md-none admin-mobile-section">
    <p class="admin-list-hint">Drag to reorder, then save.</p>
    <div class="admin-mobile-list" id="sortableCards">
        @foreach($images as $image)
        <article class="portfolio-mobile-card" data-id="{{ $image->id }}">
            <div class="portfolio-card-inner">
                <button type="button" class="portfolio-card-handle handle" aria-label="Drag to reorder">
                    <i class="bi bi-grip-vertical"></i>
                </button>

                <div class="portfolio-card-thumb">
                    <div class="portfolio-card-thumb-placeholder" aria-hidden="true">
                        <i class="bi bi-image"></i>
                    </div>
                    @if($image->thumb_url)
                    <img src="{{ $image->thumb_url }}"
                         class="portfolio-card-thumb-img"
                         alt="{{ $image->title }}"
                         loading="lazy"
                         decoding="async"
                         width="80"
                         height="80"
                         onerror="this.remove();">
                    @endif
                </div>

                <div class="portfolio-card-body">
                    <h6 class="portfolio-card-title">{{ $image->title }}</h6>
                    <div class="portfolio-card-badges">
                        <span class="admin-pill admin-pill-category">{{ $image->category->name }}</span>
                        @if($image->is_featured)
                        <span class="admin-pill admin-pill-featured"><i class="bi bi-star-fill"></i> Featured</span>
                        @endif
                    </div>
                    <span class="portfolio-card-order">Order {{ $image->sort_order }}</span>
                </div>
            </div>

            <div class="portfolio-card-actions">
                <a href="{{ route('admin.portfolio.edit', $image) }}" class="btn btn-admin-sm btn-admin-edit">Edit</a>
                <button type="submit" form="delete-image-{{ $image->id }}" class="btn btn-admin-sm btn-admin-delete" onclick="return confirm('Delete this image?')">Delete</button>
            </div>
        </article>
        @endforeach
    </div>

    <div class="admin-sticky-bar">
        <button type="submit" form="reorderForm" class="btn btn-admin-save w-100">
            <i class="bi bi-check2-circle me-1"></i> Save Order
        </button>
    </div>
</div>
@else
<div class="admin-card">
    <p class="text-muted mb-0">No portfolio images yet. <a href="{{ route('admin.portfolio.create') }}">Upload your first image</a>.</p>
</div>
@endif
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
            ghostClass: 'portfolio-card-ghost',
            chosenClass: 'portfolio-card-chosen',
            delay: 150,
            delayOnTouchOnly: true,
            onEnd: () => syncOrder(cards, '.portfolio-mobile-card'),
        });
    }
})();
</script>
@endpush
