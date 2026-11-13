<?php

namespace App\Http\Requests;

use App\Rules\IndonesianPhone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSellRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $year = now()->year;

        return [
            'type' => ['required', Rule::in(['sell', 'trade-in'])],

            // Langkah 1: data mobil
            'brand' => ['required', Rule::in(config('auto18.brands'))],
            'model' => ['required', 'string', 'max:60'],
            'variant' => ['nullable', 'string', 'max:80'],
            'year' => ['required', 'integer', 'between:' . ($year - 30) . ',' . $year],
            'body_type' => ['required', Rule::in(config('auto18.body_types'))],
            'transmission' => ['required', Rule::in(config('auto18.transmissions'))],
            'fuel' => ['required', Rule::in(config('auto18.fuels'))],
            'mileage' => ['required', 'integer', 'min:0', 'max:1000000'],
            'color' => ['required', Rule::in(array_column(config('auto18.colors'), 'name'))],
            'location' => ['required', Rule::in(config('auto18.locations'))],
            'condition' => ['required', Rule::in(config('auto18.conditions'))],

            // Langkah 2: jadwal inspeksi
            'inspection_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'inspection_time' => ['required', Rule::in(StoreTestDriveRequest::TIME_SLOTS)],
            'asking_price' => ['nullable', 'integer', 'min:0', 'max:100000000000'],
            'trade_in_car_id' => [
                'nullable',
                'required_if:type,trade-in',
                'integer',
                Rule::exists('cars', 'id')->where('status', 'available'),
            ],

            // Langkah 3: kontak
            'customer_name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:40', new IndonesianPhone],
            'email' => ['nullable', 'email', 'max:120'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'model.required' => 'Model mobil wajib diisi.',
            'mileage.required' => 'Kilometer wajib diisi.',
            'mileage.max' => 'Kilometer tidak wajar.',
            'inspection_date.required' => 'Tanggal inspeksi wajib dipilih.',
            'inspection_date.after_or_equal' => 'Tanggal sudah lewat.',
            'trade_in_car_id.required_if' => 'Pilih mobil yang ingin ditukar.',
            'trade_in_car_id.exists' => 'Mobil yang dipilih tidak tersedia.',
            'customer_name.required' => 'Nama wajib diisi.',
            'phone.required' => 'Nomor HP wajib diisi.',
            'email.email' => 'Email tidak valid.',
            'asking_price.integer' => 'Harga harus berupa angka bulat (Rp).',
            'year.between' => 'Tahun mobil tidak valid.',
        ];
    }
}
