<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Trip;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

final class SendTripConfirmationJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    public int $timeout = 30;

    public int $uniqueFor = 300;

    public function __construct(
        public readonly int $tripId
    ) {}

    public function uniqueId(): string
    {
        return (string) $this->tripId;
    }

    public function tags(): array
    {
        return [
            'trip:'.$this->tripId,
            'trip-confirmation',
        ];
    }

    public function handle(): void
    {
        $trip = Trip::query()->find($this->tripId);

        if ($trip === null) {
            Log::info('Trip confirmation skipped: trip not found', [
                'trip_id' => $this->tripId,
            ]);

            return;
        }

        if ($trip->confirmation_sent_at !== null) {
            Log::info('Trip confirmation skipped: already sent', [
                'trip_id' => $trip->id,
            ]);

            return;
        }

        Log::info('Trip confirmation sent', [
            'trip_id' => $trip->id,
            'passenger_id' => $trip->passenger_id,
        ]);

        $trip->update([
            'confirmation_sent_at' => now(),
        ]);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Trip confirmation job failed', [
            'trip_id' => $this->tripId,
            'message' => $exception->getMessage(),
        ]);
    }
}
