<?php

namespace App\Http\Controllers\Showroom;

use App\Http\Controllers\Controller;
use App\Models\TestDrive;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TestDriveController extends Controller
{
    /** pending -> confirmed; pending/confirmed -> done | cancelled. */
    private const ALLOWED = [
        'confirmed' => ['pending'],
        'done' => ['pending', 'confirmed'],
        'cancelled' => ['pending', 'confirmed'],
    ];

    public function update(Request $request, TestDrive $testDrive): RedirectResponse
    {
        $status = $request->validate(['status' => ['required', Rule::in(array_keys(self::ALLOWED))]])['status'];

        if (! in_array($testDrive->status, self::ALLOWED[$status], true)) {
            return back()->with('error', "Booking {$testDrive->code} tidak bisa diubah ke status itu.");
        }

        $testDrive->update(['status' => $status]);

        return back()->with('success', "Booking {$testDrive->code} diperbarui.");
    }

    public function destroy(TestDrive $testDrive): RedirectResponse
    {
        $code = $testDrive->code;
        $testDrive->delete();

        return back()->with('success', "Booking {$code} dihapus.");
    }
}
