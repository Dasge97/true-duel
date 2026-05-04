<?php

declare(strict_types=1);

namespace App\Telemetry\Application;

final class EmitDomainEventHandler
{
    public function __construct(
        private OutboxRepository $outbox,
        private TelemetryPublisher $publisher,
    ) {
    }

    /**
     * @param array<string, mixed> $event
     */
    public function __invoke(array $event): void
    {
        $eventType = (string) ($event['eventType'] ?? '');
        $aggregateId = (string) ($event['aggregateId'] ?? '');

        if ($eventType === '' || $aggregateId === '') {
            throw new \InvalidArgumentException('Invalid telemetry event payload.');
        }

        $outboxId = $this->outbox->store($eventType, $aggregateId, $event);
        try {
            $this->publisher->publish($eventType, $event);
            $this->outbox->markPublished($outboxId);
        } catch (\Throwable $e) {
            $this->outbox->markFailed($outboxId, $e->getMessage());
            throw $e;
        }
    }
}

interface OutboxRepository
{
    /** @param array<string, mixed> $payload */
    public function store(string $eventType, string $aggregateId, array $payload): string;

    public function markPublished(string $outboxId): void;

    public function markFailed(string $outboxId, string $reason): void;
}

interface TelemetryPublisher
{
    /** @param array<string, mixed> $payload */
    public function publish(string $eventType, array $payload): void;
}
