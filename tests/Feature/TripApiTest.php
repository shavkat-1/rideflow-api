<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Jobs\SendTripConfirmationJob;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Passport\Passport;
use Tests\TestCase;

class TripApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_trip_can_be_created(): void
    {
        Queue::fake();

        $passenger = User::factory()->create([
            'role' => UserRole::Passenger,
        ]);

        $response = $this->postJson('/api/trips', [
            'passenger_id' => $passenger->id,
            'pricing_type' => 'fixed',
            'estimated_price' => 150,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath(
                'message',
                'Поездка создана. Подтверждение будет отправлено позже.'
            )
            ->assertJsonPath('data.passenger_id', $passenger->id)
            ->assertJsonPath('data.pricing_type', 'fixed')
            ->assertJsonPath('data.estimated_price', 150);

        $this->assertDatabaseHas('trips', [
            'passenger_id' => $passenger->id,
            'pricing_type' => 'fixed',
            'estimated_price' => 150,
        ]);

        Queue::assertPushed(SendTripConfirmationJob::class);
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

    public function test_guest_cannot_list_trips(): void
    {
        Trip::factory()->count(3)->create();

        $response = $this->getJson('/api/trips');

        $response
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated.');
    }

    public function test_passenger_can_only_list_own_trips(): void
    {
        $passenger = User::factory()->create([
            'role' => UserRole::Passenger,
        ]);

        $otherPassenger = User::factory()->create([
            'role' => UserRole::Passenger,
        ]);

        Trip::factory()->count(2)->create([
            'passenger_id' => $passenger->id,
        ]);

        Trip::factory()->count(3)->create([
            'passenger_id' => $otherPassenger->id,
        ]);

        Passport::actingAs($passenger);

        $response = $this->getJson('/api/trips');

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data');

        foreach ($response->json('data') as $trip) {
            $this->assertSame(
                $passenger->id,
                $trip['passenger_id']
            );
        }
    }

    public function test_driver_can_only_list_assigned_trips(): void
    {
        $driver = User::factory()->create([
            'role' => UserRole::Driver,
        ]);

        $otherDriver = User::factory()->create([
            'role' => UserRole::Driver,
        ]);

        Trip::factory()->count(2)->create([
            'driver_id' => $driver->id,
            'status' => 'accepted',
        ]);

        Trip::factory()->count(3)->create([
            'driver_id' => $otherDriver->id,
            'status' => 'accepted',
        ]);

        Passport::actingAs($driver);

        $response = $this->getJson('/api/trips');

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data');

        foreach ($response->json('data') as $trip) {
            $this->assertSame(
                $driver->id,
                $trip['driver_id']
            );
        }
    }

    public function test_admin_can_list_all_trips(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);

        Trip::factory()->count(5)->create();

        Passport::actingAs($admin);

        $response = $this->getJson('/api/trips');

        $response
            ->assertOk()
            ->assertJsonCount(5, 'data');
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

    public function test_driver_can_accept_pending_trip(): void
    {
        $driver = User::factory()->create([
            'role' => UserRole::Driver,
        ]);

        $trip = Trip::factory()->create([
            'driver_id' => null,
            'status' => 'pending',
        ]);

        Passport::actingAs($driver);

        $response = $this->patchJson(
            "/api/trips/{$trip->id}/accept"
        );

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $trip->id)
            ->assertJsonPath('data.driver_id', $driver->id)
            ->assertJsonPath('data.status', 'accepted');

        $this->assertDatabaseHas('trips', [
            'id' => $trip->id,
            'driver_id' => $driver->id,
            'status' => 'accepted',
        ]);
    }

    public function test_passenger_cannot_accept_trip(): void
    {
        $passenger = User::factory()->create([
            'role' => UserRole::Passenger,
        ]);

        $trip = Trip::factory()->create([
            'driver_id' => null,
            'status' => 'pending',
        ]);

        Passport::actingAs($passenger);

        $response = $this->patchJson(
            "/api/trips/{$trip->id}/accept"
        );

        $response
            ->assertForbidden()
            ->assertJsonPath(
                'message',
                'Доступ разрешён только водителям'
            );

        $this->assertDatabaseHas('trips', [
            'id' => $trip->id,
            'driver_id' => null,
            'status' => 'pending',
        ]);
    }

    public function test_guest_cannot_accept_trip(): void
    {
        $trip = Trip::factory()->create([
            'driver_id' => null,
            'status' => 'pending',
        ]);

        $response = $this->patchJson(
            "/api/trips/{$trip->id}/accept"
        );

        $response
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated.');

        $this->assertDatabaseHas('trips', [
            'id' => $trip->id,
            'driver_id' => null,
            'status' => 'pending',
        ]);
    }

    public function test_driver_cannot_accept_missing_trip(): void
    {
        $driver = User::factory()->create([
            'role' => UserRole::Driver,
        ]);

        Passport::actingAs($driver);

        $response = $this->patchJson('/api/trips/999999/accept');

        $response
            ->assertNotFound()
            ->assertJsonPath('message', 'Поездка не найдена');
    }

    public function test_driver_cannot_accept_unavailable_trip(): void
    {
        $firstDriver = User::factory()->create([
            'role' => UserRole::Driver,
        ]);

        $secondDriver = User::factory()->create([
            'role' => UserRole::Driver,
        ]);

        $trip = Trip::factory()->create([
            'driver_id' => $firstDriver->id,
            'status' => 'accepted',
        ]);

        Passport::actingAs($secondDriver);

        $response = $this->patchJson(
            "/api/trips/{$trip->id}/accept"
        );

        $response
            ->assertConflict()
            ->assertJsonPath(
                'message',
                'Поездка уже недоступна для принятия'
            );

        $this->assertDatabaseHas('trips', [
            'id' => $trip->id,
            'driver_id' => $firstDriver->id,
            'status' => 'accepted',
        ]);
    }

    public function test_trip_owner_can_update_pending_trip(): void
    {
        $passenger = User::factory()->create([
            'role' => UserRole::Passenger,
        ]);

        $trip = Trip::factory()->create([
            'passenger_id' => $passenger->id,
            'driver_id' => null,
            'status' => 'pending',
            'estimated_price' => 150,
        ]);

        Passport::actingAs($passenger);

        $response = $this->patchJson(
            "/api/trips/{$trip->id}",
            [
                'pricing_type' => 'fixed',
                'estimated_price' => 180,
            ]
        );

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $trip->id)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.estimated_price', 180);

        $this->assertDatabaseHas('trips', [
            'id' => $trip->id,
            'passenger_id' => $passenger->id,
            'status' => 'pending',
            'estimated_price' => 180,
        ]);
    }

    public function test_other_passenger_cannot_update_trip(): void
    {
        $owner = User::factory()->create([
            'role' => UserRole::Passenger,
        ]);

        $otherPassenger = User::factory()->create([
            'role' => UserRole::Passenger,
        ]);

        $trip = Trip::factory()->create([
            'passenger_id' => $owner->id,
            'status' => 'pending',
            'estimated_price' => 150,
        ]);

        Passport::actingAs($otherPassenger);

        $response = $this->patchJson(
            "/api/trips/{$trip->id}",
            [
                'estimated_price' => 200,
            ]
        );

        $response->assertForbidden();

        $this->assertDatabaseHas('trips', [
            'id' => $trip->id,
            'passenger_id' => $owner->id,
            'estimated_price' => 150,
        ]);
    }

    public function test_trip_owner_cannot_update_accepted_trip(): void
    {
        $passenger = User::factory()->create([
            'role' => UserRole::Passenger,
        ]);

        $driver = User::factory()->create([
            'role' => UserRole::Driver,
        ]);

        $trip = Trip::factory()->create([
            'passenger_id' => $passenger->id,
            'driver_id' => $driver->id,
            'status' => 'accepted',
            'estimated_price' => 150,
        ]);

        Passport::actingAs($passenger);

        $response = $this->patchJson(
            "/api/trips/{$trip->id}",
            [
                'estimated_price' => 200,
            ]
        );

        $response->assertForbidden();

        $this->assertDatabaseHas('trips', [
            'id' => $trip->id,
            'status' => 'accepted',
            'estimated_price' => 150,
        ]);
    }

    public function test_unassigned_driver_cannot_update_trip(): void
    {
        $assignedDriver = User::factory()->create([
            'role' => UserRole::Driver,
        ]);

        $otherDriver = User::factory()->create([
            'role' => UserRole::Driver,
        ]);

        $trip = Trip::factory()->create([
            'driver_id' => $assignedDriver->id,
            'status' => 'accepted',
        ]);

        Passport::actingAs($otherDriver);

        $response = $this->patchJson(
            "/api/trips/{$trip->id}",
            [
                'status' => 'completed',
                'final_price' => 220,
            ]
        );

        $response->assertForbidden();

        $this->assertDatabaseHas('trips', [
            'id' => $trip->id,
            'driver_id' => $assignedDriver->id,
            'status' => 'accepted',
        ]);
    }

    public function test_trip_owner_can_delete_pending_trip(): void
    {
        $passenger = User::factory()->create([
            'role' => UserRole::Passenger,
        ]);

        $trip = Trip::factory()->create([
            'passenger_id' => $passenger->id,
            'status' => 'pending',
        ]);

        Passport::actingAs($passenger);

        $response = $this->deleteJson(
            "/api/trips/{$trip->id}"
        );

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Поездка удалена');

        $this->assertDatabaseMissing('trips', [
            'id' => $trip->id,
        ]);
    }

    public function test_other_passenger_cannot_delete_trip(): void
    {
        $owner = User::factory()->create([
            'role' => UserRole::Passenger,
        ]);

        $otherPassenger = User::factory()->create([
            'role' => UserRole::Passenger,
        ]);

        $trip = Trip::factory()->create([
            'passenger_id' => $owner->id,
            'status' => 'pending',
        ]);

        Passport::actingAs($otherPassenger);

        $response = $this->deleteJson(
            "/api/trips/{$trip->id}"
        );

        $response->assertForbidden();

        $this->assertDatabaseHas('trips', [
            'id' => $trip->id,
            'passenger_id' => $owner->id,
        ]);
    }

    public function test_trip_owner_cannot_delete_accepted_trip(): void
    {
        $passenger = User::factory()->create([
            'role' => UserRole::Passenger,
        ]);

        $trip = Trip::factory()->create([
            'passenger_id' => $passenger->id,
            'status' => 'accepted',
        ]);

        Passport::actingAs($passenger);

        $response = $this->deleteJson(
            "/api/trips/{$trip->id}"
        );

        $response->assertForbidden();

        $this->assertDatabaseHas('trips', [
            'id' => $trip->id,
            'status' => 'accepted',
        ]);
    }

    public function test_missing_trip_cannot_be_deleted(): void
    {
        $passenger = User::factory()->create([
            'role' => UserRole::Passenger,
        ]);

        Passport::actingAs($passenger);

        $response = $this->deleteJson('/api/trips/999999');

        $response->assertNotFound();
    }
}
