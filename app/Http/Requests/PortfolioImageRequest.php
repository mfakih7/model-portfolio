<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PortfolioImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $imageRule = $this->isMethod('post')
            ? 'required|image|mimes:jpg,jpeg,png,webp|max:5120'
            : 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120';

        return [
            'portfolio_category_id' => 'required|exists:portfolio_categories,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'image' => $imageRule,
            'is_featured' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ];
    }
}
