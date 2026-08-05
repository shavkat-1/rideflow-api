<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\TripEventPublisherInterface;
use App\Events\TripCreatedEventData;
use JsonException;
use RdKafka\Conf;
use RdKafka\Producer;
use RuntimeException;

final class KafkaTripEventPublisher implements TripEventPublisherInterface
{
    public function publishTripCreated(
        TripCreatedEventData $event
    ): void {
        $conf = new Conf;

        $conf->set(
            'metadata.broker.list',
            (string) config('services.kafka.brokers')
        );

        $producer = new Producer($conf);

        $topic = $producer->newTopic(
            (string) config('services.kafka.topic')
        );

        try {
            $payload = json_encode(
                $event->toArray(),
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException $exception) {
            throw new RuntimeException(
                'Не удалось сериализовать Kafka-событие',
                previous: $exception
            );
        }

        $topic->produce(
            RD_KAFKA_PARTITION_UA,
            0,
            $payload,
            (string) $event->tripId
        );

        $result = $producer->flush(10_000);

        if ($result !== RD_KAFKA_RESP_ERR_NO_ERROR) {
            throw new RuntimeException(
                'Не удалось отправить событие в Kafka'
            );
        }
    }
}
