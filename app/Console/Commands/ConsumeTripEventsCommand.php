<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use RdKafka\Conf;
use RdKafka\KafkaConsumer;

#[Signature('kafka:consume-trips')]
#[Description('Consume trip events from Kafka')]
final class ConsumeTripEventsCommand extends Command
{
    public function handle(): int
    {
        $this->info('Kafka trip consumer started');

        $conf = new Conf;

        $conf->set(
            'bootstrap.servers',
            (string) config('services.kafka.brokers')
        );

        $conf->set(
            'group.id',
            'rideflow-notifications'
        );

        $conf->set(
            'auto.offset.reset',
            'earliest'
        );

        $consumer = new KafkaConsumer($conf);

        $consumer->subscribe([
            (string) config('services.kafka.topic'),
        ]);

        $this->info('Subscribed to trip-events');
        $this->info('Waiting for messages. Press Ctrl+C to stop.');

        while (true) {
            $message = $consumer->consume(10_000);

            switch ($message->err) {
                case RD_KAFKA_RESP_ERR_NO_ERROR:
                    $this->info('Trip event received:');
                    $this->line($message->payload);

                    break;

                case RD_KAFKA_RESP_ERR__PARTITION_EOF:
                    break;

                case RD_KAFKA_RESP_ERR__TIMED_OUT:
                    break;

                default:
                    $this->error(
                        $message->errstr()
                    );

                    return self::FAILURE;
            }
        }
    }
}
