<?php

namespace App\Http\Controllers\Showroom;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\View\View;

class ReceiptController extends Controller
{
    public function show(Transaction $transaction): View
    {
        return view('showroom.receipt', [
            'trx' => $transaction->load('items'),
            // Nota yang baru dibuat "dicetak" dengan animasi; nota lama (dari Laporan) langsung tampil
            'fresh' => $transaction->created_at && $transaction->created_at->gt(now()->subSeconds(30)),
        ]);
    }
}
