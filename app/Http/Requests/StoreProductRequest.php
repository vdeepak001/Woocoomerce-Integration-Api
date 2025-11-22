<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'sku' => 'required|string',
            'price' => 'required|numeric',
            'description' => 'nullable|string',
            'short_description' => 'nullable|string',
            'quantity' => 'nullable|integer',
            'weight' => 'nullable|string',
            'woocommerce_category_id' => 'nullable|array',
            'woocommerce_category_id.*' => 'integer',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Product name is required',
            'sku.required' => 'Product SKU is required',
            'price.required' => 'Product price is required',
            'price.numeric' => 'Product price must be a number',
            'quantity.integer' => 'Quantity must be an integer',
            'woocommerce_category_id.array' => 'Categories must be an array',
            'woocommerce_category_id.*.integer' => 'Each category ID must be an integer',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // You can add any data transformation here if needed
    }
}
