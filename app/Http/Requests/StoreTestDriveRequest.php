<?php

namespace App\Http\Requests;

use App\Rules\IndonesianPhone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTestDriveRequest extends FormRequest
{
    public const TIME_SLOTS = ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00'];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:40', new IndonesianPhone],
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'time' => ['required', Rule::in(self::TIME_SLOTS)],
            'location' => ['required', Rule::in(['showroom', 'rumah'])],
            'address' => ['nullable', 'required_if:location,rumah', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return [
            'customer_name' => 'nama',
            'phone' => 'nomor HP',
            'date' => 'tanggal',
            'time' => 'jam',
            'location' => 'lokasi',
            'address' => 'alamat',
            'notes' => 'catatan',
        ];
    }

    public function messages(): array
    {
        return [
            'customer_name.required' => 'Nama wajib diisi.',
            'date.required' => 'Tanggal wajib dipilih.',
            'date.after_or_equal' => 'Tanggal sudah lewat.',
            'date.date_format' => 'Tanggal tidak valid.',
            'address.required_if' => 'Alamat wajib diisi.',
            'phone.required' => 'Nomor HP wajib diisi.',
        ];
    }
}
