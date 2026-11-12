<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1', 'max:200'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:100000'],
            'discount_type' => ['nullable', Rule::in(['nominal', 'percent'])],
            'discount_value' => ['nullable', 'numeric', 'min:0', 'max:100000000000'],
            'payment_method' => ['required', Rule::in(['cash', 'qris', 'transfer'])],
            'paid_amount' => ['nullable', 'integer', 'min:0', 'max:100000000000'],
            'customer_name' => ['nullable', 'string', 'max:120'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Keranjang masih kosong.',
            'items.min' => 'Keranjang masih kosong.',
            'items.*.qty.min' => 'Jumlah barang minimal 1.',
            'items.*.product_id.exists' => 'Ada barang di keranjang yang tidak ditemukan. Muat ulang halaman kasir.',
            'discount_value.numeric' => 'Nilai diskon harus berupa angka.',
            'discount_value.min' => 'Nilai diskon tidak boleh negatif.',
            'paid_amount.integer' => 'Uang diterima harus berupa angka bulat (Rp).',
            'paid_amount.min' => 'Uang diterima tidak valid.',
            'payment_method.in' => 'Metode pembayaran tidak dikenal.',
        ];
    }
}
