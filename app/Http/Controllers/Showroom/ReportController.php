<?php

namespace App\Http\Controllers\Showroom;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public const PERIODS = ['today' => 'Hari Ini', 'week' => '7 Hari Terakhir', 'month' => 'Bulan Ini', 'all' => 'Semua Waktu'];

    public const METHODS = ['cash' => 'Tunai', 'qris' => 'QRIS', 'transfer' => 'Transfer', 'credit' => 'Kredit'];

    public function index(Request $request): View
    {
        $filters = $this->filters($request);
        $transactions = $this->query($filters)->with('items')->get();

        // Total hanya dari transaksi yang tidak dibatalkan
        $done = $transactions->where('status', 'completed');
        $revenue = (int) $done->sum('total');

        return view('showroom.reports', [
            'transactions' => $transactions,
            'filters' => $filters,
            'periods' => self::PERIODS,
            'methods' => self::METHODS,
            'totals' => [
                'revenue' => $revenue,
                'profit' => (int) $done->sum('total_profit'),
                'cost' => (int) $done->sum(fn (Transaction $t) => $t->items->sum(fn ($i) => $i->buy_price * $i->qty)),
                'count' => $done->count(),
                'average' => $done->count() ? (int) round($revenue / $done->count()) : 0,
                'car' => (int) $done->where('sale_type', 'car')->sum('total'),
                'retail' => (int) $done->where('sale_type', 'retail')->sum('total'),
            ],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $this->filters($request);
        $query = $this->query($filters)->with('items');
        $name = 'laporan-auto18-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM: Excel membaca UTF-8 dengan benar
            fputcsv($out, ['Tanggal', 'Kode', 'Jenis', 'Pelanggan', 'Rincian', 'Metode', 'Subtotal', 'Diskon', 'Pajak', 'Total', 'Laba', 'Status']);

            $query->chunk(200, function ($chunk) use ($out) {
                foreach ($chunk as $t) {
                    fputcsv($out, [
                        $t->date->format('Y-m-d H:i'),
                        self::safe($t->code),
                        $t->sale_type === 'car' ? 'Mobil' : 'Aksesoris',
                        self::safe($t->customer_name),
                        self::safe($t->items->map(fn ($i) => "{$i->qty}x {$i->name}")->implode('; ')),
                        self::METHODS[$t->payment_method] ?? $t->payment_method,
                        (int) $t->subtotal, (int) $t->discount, (int) $t->tax, (int) $t->total, (int) $t->total_profit,
                        $t->status === 'void' ? 'Dibatalkan' : 'Selesai',
                    ]);
                }
            });

            fclose($out);
        }, $name, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Teks yang diawali = + - @ bisa dijalankan Excel sebagai rumus; beri tanda kutip tunggal di depannya. */
    private static function safe(?string $text): string
    {
        $text = (string) $text;

        return preg_match('/^[=+\-@\t\r]/', $text) ? "'" . $text : $text;
    }

    private function filters(Request $request): array
    {
        $text = fn (string $key) => is_string($v = $request->query($key)) ? trim($v) : '';
        $date = function (string $key) use ($text): ?Carbon {
            $v = $text($key);
            if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) {
                return null;
            }
            try {
                $d = Carbon::createFromFormat('Y-m-d', $v);

                return $d && $d->format('Y-m-d') === $v ? $d->startOfDay() : null;
            } catch (\Throwable) {
                return null;
            }
        };

        $from = $date('from');
        $to = $date('to');
        if ($from && $to && $from->gt($to)) {
            [$from, $to] = [$to, $from]; // rentang terbalik: tukar saja
        }

        return [
            'q' => $text('q'),
            'period' => array_key_exists($text('period'), self::PERIODS) ? $text('period') : 'month',
            'from' => $from?->toDateString(),
            'to' => $to?->toDateString(),
            'sale_type' => in_array($text('sale_type'), ['car', 'retail'], true) ? $text('sale_type') : '',
            'payment_method' => array_key_exists($text('payment_method'), self::METHODS) ? $text('payment_method') : '',
        ];
    }

    private function query(array $f): Builder
    {
        $query = Transaction::query()->orderByDesc('date')->orderByDesc('id');

        if ($f['from'] || $f['to']) {
            if ($f['from']) {
                $query->whereDate('date', '>=', $f['from']);
            }
            if ($f['to']) {
                $query->whereDate('date', '<=', $f['to']);
            }
        } else {
            match ($f['period']) {
                'today' => $query->whereDate('date', today()),
                'week' => $query->where('date', '>=', today()->subDays(6)),
                'month' => $query->where('date', '>=', today()->startOfMonth()),
                default => null,
            };
        }

        if ($f['sale_type']) {
            $query->where('sale_type', $f['sale_type']);
        }
        if ($f['payment_method']) {
            $query->where('payment_method', $f['payment_method']);
        }
        foreach (preg_split('/\s+/', str_replace(['%', '_'], '', $f['q']), -1, PREG_SPLIT_NO_EMPTY) as $word) {
            $query->where(fn (Builder $q) => $q
                ->where('code', 'like', "%{$word}%")
                ->orWhere('customer_name', 'like', "%{$word}%")
                ->orWhereHas('items', fn (Builder $i) => $i->where('name', 'like', "%{$word}%")));
        }

        return $query;
    }
}
