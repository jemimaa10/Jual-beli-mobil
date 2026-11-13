<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveCarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'brand' => ['required', Rule::in(config('auto18.brands'))],
            'model' => ['required', 'string', 'max:60'],
            'variant' => ['nullable', 'string', 'max:80'],
            'year' => ['required', 'integer', 'between:1980,' . (now()->year + 1)],
            'body_type' => ['required', Rule::in(config('auto18.body_types'))],
            'transmission' => ['required', Rule::in(config('auto18.transmissions'))],
            'fuel' => ['required', Rule::in(config('auto18.fuels'))],
            'engine_cc' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'seats' => ['nullable', 'integer', 'min:1', 'max:20'],
            'mileage' => ['required', 'integer', 'min:0', 'max:2000000'],
            'color' => ['required', 'string', 'max:40'],
            'color_hex' => ['nullable', 'regex:/^#[0-9a-fA-F]{3,6}$/'],
            'plate' => ['nullable', 'string', 'max:20'],
            'location' => ['nullable', 'string', 'max:80'],
            'price' => ['required', 'integer', 'min:1', 'max:100000000000'],
            'buy_price' => ['required', 'integer', 'min:0', 'max:100000000000'],
            'status' => ['required', Rule::in(['available', 'booked'])],
            'owner_count' => ['nullable', 'integer', 'min:1', 'max:20'],
            'tax_valid_until' => ['nullable', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'service_record' => ['boolean'],
            'is_featured' => ['boolean'],
            'image_key' => ['nullable', Rule::in(array_column(config('auto18.photo_credits'), 'key'))],
            'image_url' => ['nullable', 'url', 'max:500'],
            'features' => ['nullable', 'string', 'max:1000'],
            'description' => ['nullable', 'string', 'max:2000'],
            'inspection' => ['nullable', 'array'],
            'inspection.*' => ['nullable', 'numeric'],
        ];
    }

    public function messages(): array
    {
        return [
            'model.required' => 'Model wajib diisi.',
            'year.between' => 'Tahun tidak valid.',
            'price.min' => 'Harga jual wajib diisi.',
            'price.required' => 'Harga jual wajib diisi.',
            'price.integer' => 'Harga jual harus berupa angka bulat (Rp).',
            'buy_price.integer' => 'Modal harus berupa angka bulat (Rp).',
            'buy_price.min' => 'Modal tidak valid.',
            'status.in' => 'Status hanya boleh Tersedia atau Dipesan. Gunakan "Jual" untuk menandai terjual.',
            'tax_valid_until.regex' => 'Format pajak harus TTTT-BB, misalnya 2027-05.',
            'image_url.url' => 'URL foto tidak valid.',
        ];
    }
}
