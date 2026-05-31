<?php

namespace App\Http\Requests;

use App\Services\PortfolioImageService;
use Illuminate\Foundation\Http\FormRequest;

class PortfolioImageRequest extends FormRequest
{
    /** Max upload size in kilobytes (25 MB). */
    public const MAX_IMAGE_KB = 25600;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $required = $this->isMethod('post') ? 'required' : 'nullable';

        return [
            'portfolio_category_id' => 'required|exists:portfolio_categories,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'image' => [$required, 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.self::MAX_IMAGE_KB],
            'is_featured' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! $this->hasFile('image')) {
                return;
            }

            $file = $this->file('image');
            $info = @getimagesize($file->getRealPath());

            if ($info === false) {
                $validator->errors()->add('image', 'The uploaded file is not a valid image.');

                return;
            }

            [$width, $height] = $info;

            if ($width > PortfolioImageService::MAX_DIMENSION || $height > PortfolioImageService::MAX_DIMENSION) {
                $validator->errors()->add(
                    'image',
                    'Image dimensions are too large. Please upload an image up to 6000x6000 pixels.',
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'image.required' => 'Please choose an image to upload.',
            'image.image' => 'The uploaded file must be a valid image.',
            'image.mimes' => 'Unsupported image format. Please upload a JPG, JPEG, PNG, or WEBP file.',
            'image.max' => 'The image file is too large. Maximum allowed size is 25 MB.',
        ];
    }
}
