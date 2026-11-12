<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\TestDrive;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TestDriveBookingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'customer_name' => 'Budi Santoso',
            'phone' => '0812-1818-9900',
            'date' => now()->addDay()->toDateString(),
            'time' => '10:00',
            'location' => 'showroom',
            'address' => '',
            'notes' => 'Mau coba tanjakan.',
        ], $overrides);
    }

    public function test_valid_booking_is_saved_as_pending(): void
    {
        $car = Car::where('code', 'A18-0001')->firstOrFail();

        $this->from(route('cars.show', $car))
            ->post(route('cars.test-drive', $car), $this->payload())
            ->assertRedirect(route('cars.show', $car))
            ->assertSessionHas('success');

        $drive = TestDrive::where('code', 'TD-0004')->firstOrFail();
        $this->assertSame('pending', $drive->status);
        $this->assertSame($car->id, $drive->car_id);
        $this->assertSame('Mitsubishi Xpander Ultimate 1.5 AT 2021', $drive->car_name);
        $this->assertSame('Budi Santoso', $drive->customer_name);
    }

    public function test_home_visit_requires_an_address(): void
    {
        $car = Car::where('code', 'A18-0001')->firstOrFail();

        $this->post(route('cars.test-drive', $car), $this->payload(['location' => 'rumah', 'address' => '']))
            ->assertSessionHasErrors('address');

        $this->post(route('cars.test-drive', $car), $this->payload(['location' => 'rumah', 'address' => 'Jl. Melati 5, Depok']))
            ->assertSessionHasNoErrors();
        $this->assertSame('rumah', TestDrive::latest('id')->first()->location);
    }

    public function test_invalid_phone_is_rejected(): void
    {
        $car = Car::where('code', 'A18-0001')->firstOrFail();

        $this->post(route('cars.test-drive', $car), $this->payload(['phone' => '12345']))
            ->assertSessionHasErrors('phone');

        $this->assertSame(3, TestDrive::count());
    }

    public function test_past_date_and_missing_name_are_rejected(): void
    {
        $car = Car::where('code', 'A18-0001')->firstOrFail();

        $this->post(route('cars.test-drive', $car), $this->payload(['date' => now()->subDay()->toDateString()]))
            ->assertSessionHasErrors('date');
        $this->post(route('cars.test-drive', $car), $this->payload(['customer_name' => '']))
            ->assertSessionHasErrors('customer_name');

        $this->assertSame(3, TestDrive::count());
    }

    public function test_sold_car_cannot_be_booked(): void
    {
        $sold = Car::where('status', 'sold')->firstOrFail();

        $this->from(route('cars.show', $sold))
            ->post(route('cars.test-drive', $sold), $this->payload())
            ->assertSessionHas('error');

        $this->assertSame(3, TestDrive::count());
    }
}
