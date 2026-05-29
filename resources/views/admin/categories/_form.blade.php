@php $category = $category ?? null; @endphp

<div class="mb-3">
    <label class="form-label">Name *</label>
    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $category?->name) }}" required>
    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="mb-3">
    <label class="form-label">Slug</label>
    <input type="text" name="slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug', $category?->slug) }}" placeholder="Auto-generated if empty">
    @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="mb-3 form-check">
    <input type="hidden" name="status" value="0">
    <input type="checkbox" name="status" value="1" class="form-check-input" id="status" @checked(old('status', $category?->status ?? true))>
    <label class="form-check-label" for="status">Active</label>
</div>
