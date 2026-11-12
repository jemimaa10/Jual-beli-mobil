<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Form "Proses Penjualan" mobil. Aturan uangnya sendiri dicek lagi di CarSaleService. */
class SellCarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_phone' => ['nullable', 'string', 'max:40'],
            'payment_method' => ['required', Rule::in(['cash', 'transfer', 'credit'])],
            'discount' => ['nullable', 'integer', 'min:0', 'max:100000000000'],
            'paid_amount' => ['nullable', 'integer', 'min:0', 'max:100000000000'],

            'leasing' => ['nullable', Rule::in(config('auto18.leasing_partners'))],
            'dp_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'tenor_months' => ['nullable', Rule::in(config('auto18.tenors'))],
            'annual_rate' => ['nullable', 'numeric', 'min:0', 'max:30'],

            'use_trade_in' => ['boolean'],
            'trade_in_request_id' => ['nullable', 'integer', 'exists:sell_requests,id'],
            'trade_in_car_name' => ['nullable', 'required_if:use_trade_in,1', 'string', 'max:120'],
            'trade_in_value' => ['nullable', 'required_if:use_trade_in,1', 'integer', 'min:1', 'max:100000000000'],

            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_name.required' => 'Nama pembeli wajib diisi.',
            'discount.integer' => 'Diskon harus berupa angka bulat (Rp).',
            'discount.min' => 'Diskon tidak valid.',
            'paid_amount.integer' => 'Uang diterima harus berupa angka bulat (Rp).',
            'trade_in_value.integer' => 'Nilai tukar tambah harus berupa angka bulat (Rp).',
            'trade_in_value.min' => 'Nilai tukar tambah belum diisi.',
            'trade_in_value.required_if' => 'Nilai tukar tambah belum diisi.',
            'trade_in_car_name.required_if' => 'Mobil tukar tambah belum dipilih.',
        ];
    }

    /** Bentuk array yang dimengerti CarSaleService. */
    public function saleData(): array
    {
        $v = $this->validated();

        return [
            'customer_name' => $v['customer_name'],
            'customer_phone' => $v['customer_phone'] ?? null,
            'payment_method' => $v['payment_method'],
            'discount' => $v['discount'] ?? 0,
            'paid_amount' => $v['paid_amount'] ?? null,
            'credit' => [
                'leasing' => $v['leasing'] ?? null,
                'dp_percent' => $v['dp_percent'] ?? null,
                'tenor_months' => $v['tenor_months'] ?? null,
                'annual_rate' => $v['annual_rate'] ?? null,
            ],
            'trade_in' => $this->boolean('use_trade_in') ? [
                'request_id' => $v['trade_in_request_id'] ?? null,
                'car_name' => $v['trade_in_car_name'] ?? '',
                'value' => $v['trade_in_value'] ?? 0,
            ] : null,
            'notes' => $v['notes'] ?? null,
        ];
    }
}
