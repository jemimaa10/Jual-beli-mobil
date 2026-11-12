<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\SellRequest;
use App\Models\TestDrive;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MakesShowroomData;
use Tests\TestCase;

class DashboardReportTest extends TestCase
{
    use MakesShowroomData, RefreshDatabase;

    private int $seq = 0;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-12 15:00:00');
        $this->actingAs($this->staff());
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function trx(array $o = []): Transaction
    {
        $this->seq++;
        $o = array_merge(['total' => 100_000, 'total_profit' => 20_000, 'sale_type' => 'retail', 'payment_method' => 'cash', 'status' => 'completed', 'date' => now(), 'customer_name' => 'Pelanggan Umum', 'buy' => 80_000], $o);
        $buy = $o['buy'];
        unset($o['buy']);

        $trx = Transaction::create(array_merge([
            'code' => 'TRX-T-' . $this->seq, 'cashier' => 'Admin', 'subtotal' => $o['total'], 'amount_due' => $o['total'], 'paid_amount' => $o['total'],
        ], $o));
        $trx->items()->create(['sku' => 'S-' . $this->seq, 'name' => 'Item ' . $this->seq, 'unit' => 'pcs', 'price' => $o['total'], 'buy_price' => $buy, 'qty' => 1, 'subtotal' => $o['total']]);

        return $trx;
    }

    public function test_dashboard_today_figures_ignore_void_and_other_days(): void
    {
        $this->trx(['total' => 300_000, 'total_profit' => 60_000]);
        $this->trx(['total' => 200_000_000, 'total_profit' => 20_000_000, 'sale_type' => 'car', 'payment_method' => 'transfer']);
        $this->trx(['total' => 999_000, 'total_profit' => 99_000, 'status' => 'void']);
        $this->trx(['total' => 500_000, 'total_profit' => 50_000, 'date' => now()->subDay()->setTime(10, 0)]);

        $view = $this->get(route('showroom.dashboard'))->assertOk();

        $this->assertSame(200_300_000, $view->viewData('today')['revenue']);
        $this->assertSame(20_060_000, $view->viewData('today')['profit']);
        $this->assertSame(2, $view->viewData('today')['count']);
    }

    public function test_dashboard_showroom_counts(): void
    {
        $this->makeCar();
        $this->makeCar(['status' => 'booked']);
        $this->makeCar(['status' => 'sold', 'sold_at' => now()->subDays(2)]);
        $this->makeCar(['status' => 'sold', 'sold_at' => now()->subMonths(2)]);
        $this->makeRequest(['status' => 'new']);
        $this->makeRequest(['status' => 'inspected']);
        TestDrive::create(['code' => 'TD-A', 'car_name' => 'X', 'customer_name' => 'A', 'phone' => '0812', 'date' => today(), 'time' => '10:00', 'status' => 'confirmed']);
        TestDrive::create(['code' => 'TD-B', 'car_name' => 'Y', 'customer_name' => 'B', 'phone' => '0812', 'date' => today()->addDays(2), 'time' => '10:00', 'status' => 'pending']);
        TestDrive::create(['code' => 'TD-C', 'car_name' => 'Z', 'customer_name' => 'C', 'phone' => '0812', 'date' => today()->subDays(3), 'time' => '10:00', 'status' => 'done']);
        Product::create(['sku' => 'L-1', 'name' => 'Aki Tipis', 'unit' => 'pcs', 'buy_price' => 1, 'sell_price' => 2, 'stock' => 1, 'min_stock' => 3]);

        $view = $this->get(route('showroom.dashboard'))->assertOk()->assertSee('Aki Tipis');
        $s = $view->viewData('showroom');

        $this->assertSame(1, $s['available']);
        $this->assertSame(1, $s['booked']);
        $this->assertSame(1, $s['soldThisMonth']);
        $this->assertSame(1, $s['newRequests']);
        $this->assertSame(1, $s['testDrivesToday']);
        $this->assertSame(2, $s['testDrivesUpcoming']);
    }

    public function test_seven_day_series_splits_car_and_retail(): void
    {
        $yesterday = now()->subDay()->setTime(11, 0);
        $this->trx(['total' => 400_000, 'date' => $yesterday]);
        $this->trx(['total' => 100_000, 'date' => $yesterday]);
        $this->trx(['total' => 150_000_000, 'sale_type' => 'car', 'date' => $yesterday]);
        $this->trx(['total' => 777_000, 'date' => $yesterday, 'status' => 'void']);
        $this->trx(['total' => 123_000, 'date' => now()->subDays(8)]);

        $series = $this->get(route('showroom.dashboard'))->viewData('series');

        $this->assertCount(7, $series);
        $this->assertSame(now()->toDateString(), $series[6]['date']);
        $this->assertSame(now()->subDays(6)->toDateString(), $series[0]['date']);
        $this->assertSame(500_000, $series[5]['retail']);
        $this->assertSame(150_000_000, $series[5]['car']);
        $this->assertSame(0, $series[0]['retail'] + $series[0]['car']);
    }

    public function test_report_lists_void_rows_but_excludes_them_from_totals(): void
    {
        $this->trx(['total' => 300_000, 'total_profit' => 60_000, 'buy' => 240_000]);
        $this->trx(['total' => 700_000, 'total_profit' => 100_000, 'buy' => 600_000]);
        $void = $this->trx(['total' => 900_000, 'total_profit' => 90_000, 'status' => 'void']);

        $view = $this->get(route('showroom.reports', ['period' => 'all']))->assertOk()->assertSee($void->code)->assertSee('Dibatalkan');
        $totals = $view->viewData('totals');

        $this->assertCount(3, $view->viewData('transactions'));
        $this->assertSame(1_000_000, $totals['revenue']);
        $this->assertSame(160_000, $totals['profit']);
        $this->assertSame(840_000, $totals['cost']);
        $this->assertSame(500_000, $totals['average']);
        $this->assertSame(2, $totals['count']);
    }

    public function test_report_filters(): void
    {
        $a = $this->trx(['customer_name' => 'Ibu Rahayu']);
        $b = $this->trx(['sale_type' => 'car', 'payment_method' => 'credit', 'total' => 200_000_000, 'date' => now()->subDays(3)]);
        $c = $this->trx(['payment_method' => 'qris', 'date' => now()->subDays(40)]);
        $codes = fn (array $q) => $this->get(route('showroom.reports', $q))->assertOk()->viewData('transactions')->pluck('code')->sort()->values()->all();

        $this->assertSame([$a->code], $codes(['period' => 'today']));
        $this->assertSame(collect([$a->code, $b->code])->sort()->values()->all(), $codes(['period' => 'month']));
        $this->assertCount(3, $codes(['period' => 'all']));
        $this->assertSame([$b->code], $codes(['period' => 'all', 'sale_type' => 'car']));
        $this->assertSame([$c->code], $codes(['period' => 'all', 'payment_method' => 'qris']));
        $this->assertSame([$a->code], $codes(['period' => 'all', 'q' => 'rahayu']));
        $this->assertSame([$b->code], $codes(['from' => now()->subDays(5)->toDateString(), 'to' => now()->subDays(2)->toDateString()]));
    }

    public function test_report_survives_bad_filter_values(): void
    {
        $this->trx();

        $this->get(route('showroom.reports', ['from' => '2026-13-99', 'to' => 'kemarin']))->assertOk();
        $this->get(route('showroom.reports', ['from' => '2026-11-12', 'to' => '2026-11-01']))->assertOk();
        $this->get(route('showroom.reports', ['period' => 'abad', 'sale_type' => ['x'], 'payment_method' => 'emas', 'q' => ['a']]))->assertOk();
    }

    public function test_csv_export_matches_the_filter_and_escapes_text(): void
    {
        $this->trx(['customer_name' => 'Budi, "Si Bos" Santoso', 'total' => 300_000]);
        $this->trx(['sale_type' => 'car', 'total' => 200_000_000]);

        $response = $this->get(route('showroom.reports.export', ['period' => 'all', 'sale_type' => 'retail']));
        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));

        $csv = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $rows = array_map('str_getcsv', array_filter(explode("\n", trim(substr($csv, 3)))));

        $this->assertCount(2, $rows, 'header + 1 transaksi retail');
        $this->assertSame('Kode', $rows[0][1]);
        $this->assertSame('Budi, "Si Bos" Santoso', $rows[1][array_search('Pelanggan', $rows[0])]);
        $this->assertSame('300000', $rows[1][array_search('Total', $rows[0])]);
    }

    public function test_csv_cells_that_look_like_formulas_are_neutralised(): void
    {
        $this->trx(['customer_name' => '=HYPERLINK("http://jahat","klik")']);

        $csv = $this->get(route('showroom.reports.export', ['period' => 'all']))->streamedContent();
        $rows = array_map('str_getcsv', array_filter(explode("\n", trim(substr($csv, 3)))));

        $this->assertStringStartsWith("'=", $rows[1][array_search('Pelanggan', $rows[0])]);
    }

    public function test_dashboard_and_reports_require_login(): void
    {
        auth()->logout();

        $this->get(route('showroom.dashboard'))->assertRedirect('/login');
        $this->get(route('showroom.reports'))->assertRedirect('/login');
        $this->get(route('showroom.reports.export'))->assertRedirect('/login');
    }

    public function test_pages_render_with_seeded_demo_data(): void
    {
        $this->seed();

        $this->get(route('showroom.dashboard'))->assertOk()->assertSee('Dashboard Showroom Auto18')->assertSee('Tren Omset 7 Hari');
        $this->get(route('showroom.reports', ['period' => 'all']))->assertOk()->assertSee('Laporan Penjualan')->assertSee('TRX-A18-1004');
        $this->assertSame(1, SellRequest::where('status', 'new')->count());
    }
}
