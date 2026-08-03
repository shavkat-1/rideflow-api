<?php

namespace App\Jobs;

use App\Models\Trip;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendTripConfirmationJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public int $tripId
    ) {}

    public function handle(): void
    {
        $trip = Trip::find($this->tripId);

        /*
         * Поездка удалена — это не временная ошибка.
         * Retry здесь не поможет.
         */
        if ($trip === null) {
            Log::info('Trip confirmation skipped: trip not found', [
                'trip_id' => $this->tripId,
            ]);

            return;
        }

        /*
         * Job могла выполниться повторно.
         * Если подтверждение уже отправляли — ничего не делаем.
         */
        if ($trip->confirmation_sent_at !== null) {
            Log::info('Trip confirmation skipped: already sent', [
                'trip_id' => $trip->id,
            ]);

            return;
        }

        /*
         * Пока вместо настоящего Email используем лог.
         */
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
