@php $image = $image ?? null; @endphp

<div class="mb-3">
    <label class="form-label">Image {{ $image ? '(leave empty to keep current)' : '*' }}</label>
    <input type="file" name="image" id="portfolioImageInput" class="form-control admin-file-input @error('image') is-invalid @enderror" {{ $image ? '' : 'required' }} accept=".jpg,.jpeg,.png,.webp">
    @error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror
    <small class="text-muted d-block mt-1">JPG, JPEG, PNG or WEBP — max 25 MB, up to 6000×6000 pixels. Photos are automatically optimized for the web.</small>
    <div id="imageUploadPreview" class="image-upload-preview d-none mt-3" aria-live="polite">
        <p class="small text-muted mb-2">Preview</p>
        <img id="imageUploadPreviewImg" src="" alt="Selected image preview">
    </div>
</div>

<div class="mb-3">
    <label class="form-label">Title *</label>
    <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $image?->title) }}" required>
    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="mb-3">
    <label class="form-label">Category *</label>
    <select name="portfolio_category_id" class="form-select @error('portfolio_category_id') is-invalid @enderror" required>
        <option value="">Select category</option>
        @foreach($categories as $cat)
            <option value="{{ $cat->id }}" @selected(old('portfolio_category_id', $image?->portfolio_category_id) == $cat->id)>{{ $cat->name }}</option>
        @endforeach
    </select>
    @error('portfolio_category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="mb-3">
    <label class="form-label">Description</label>
    <textarea name="description" class="form-control" rows="3">{{ old('description', $image?->description) }}</textarea>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Sort Order</label>
        <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $image?->sort_order ?? 0) }}" min="0">
    </div>
    <div class="col-md-6 mb-3 d-flex align-items-end">
        <div class="form-check admin-form-check">
            <input type="checkbox" name="is_featured" value="1" class="form-check-input" id="is_featured" @checked(old('is_featured', $image?->is_featured))>
            <label class="form-check-label" for="is_featured">Featured on homepage</label>
        </div>
    </div>
</div>
