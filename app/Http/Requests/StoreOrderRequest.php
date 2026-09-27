<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_email' => ['required_without:customer_name', 'nullable', 'email'],
            'customer_name'  => ['required_without:customer_email', 'nullable', 'string', 'max:255'],
            'products'       => ['required', 'array', 'min:1'],
            'products.*.product_id' => ['required', 'exists:products,id'],
            'products.*.quantity'   => ['required', 'integer', 'min:1'],
        ];
    }
}
