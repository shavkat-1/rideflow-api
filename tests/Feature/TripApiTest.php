<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\SendTripConfirmationJob;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TripApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_trip_can_be_created(): void
    {
        Queue::fake();

        $passenger = User::factory()->create();

        $response = $this->postJson('/api/trips', [
            'passenger_id' => $passenger->id,
            'pricing_type' => 'fixed',
            'estimated_price' => 150,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Поездка создана. Подтверждение будет отправлено позже.')
            ->assertJsonPath('data.passenger_id', $passenger->id)
            ->assertJsonPath('data.pricing_type', 'fixed')
            ->assertJsonPath('data.estimated_price', 150);

        $this->assertDatabaseHas('trips', [
            'passenger_id' => $passenger->id,
            'pricing_type' => 'fixed',
            'estimated_price' => 150,
        ]);

        Queue::assertPushed(
            SendTripConfirmationJob::class
        );
    }

    public function test_trips_can_be_listed(): void
    {
        Trip::factory()->count(3)->create();

        $response = $this->getJson('/api/trips');

        $response
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_trip_can_be_shown(): void
    {
        $trip = Trip::factory()->create();

        $response = $this->getJson("/api/trips/{$trip->id}");

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $trip->id)
            ->assertJsonPath('data.status', $trip->status)
            ->assertJsonPath('data.pricing_type', $trip->pricing_type);
    }

    public function test_missing_trip_returns_not_found(): void
    {
        $response = $this->getJson('/api/trips/999999');

        $response
            ->assertNotFound()
            ->assertJsonPath('message', 'Поездка не найдена');
    }

    public function test_trip_can_be_updated(): void
    {
        $trip = Trip::factory()->create([
            'status' => 'pending',
            'final_price' => null,
        ]);

        $response = $this->patchJson("/api/trips/{$trip->id}", [
            'status' => 'completed',
            'final_price' => 220,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $trip->id)
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.final_price', 220);

        $this->assertDatabaseHas('trips', [
            'id' => $trip->id,
            'status' => 'completed',
            'final_price' => 220,
        ]);
    }

    public function test_trip_can_be_deleted(): void
    {
        $trip = Trip::factory()->create();

        $response = $this->deleteJson("/api/trips/{$trip->id}");

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Поездка удалена');

        $this->assertDatabaseMissing('trips', [
            'id' => $trip->id,
        ]);
    }

    public function test_missing_trip_cannot_be_deleted(): void
    {
        $response = $this->deleteJson('/api/trips/999999');

        $response
            ->assertNotFound()
            ->assertJsonPath('message', 'Поездка не найдена');
    }

    public function test_trip_creation_requires_valid_data(): void
    {
        $response = $this->postJson('/api/trips', [
            'passenger_id' => 999999,
            'pricing_type' => 'unknown',
            'estimated_price' => -100,
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'passenger_id',
                'pricing_type',
                'estimated_price',
            ]);

        $this->assertDatabaseCount('trips', 0);
    }
}
