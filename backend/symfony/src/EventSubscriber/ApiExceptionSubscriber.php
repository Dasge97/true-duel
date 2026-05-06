<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Api\ApiResponseFormatter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

final class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly ApiResponseFormatter $formatter)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();
        $path = $request->getPathInfo();

        if (!str_starts_with($path, '/v1') && $path !== '/health') {
            return;
        }

        $throwable = $event->getThrowable();
        $status = 500;
        $payload = [
            'error' => [
                'code' => 'INTERNAL_ERROR',
                'message' => 'Unexpected server error.',
            ],
        ];

        if ($throwable instanceof HttpExceptionInterface) {
            $status = $throwable->getStatusCode();
            if ($status === 404) {
                $payload = [
                    'error' => [
                        'code' => 'NOT_FOUND',
                        'message' => 'Endpoint not found.',
                    ],
                ];
            } else {
                $payload = [
                    'error' => [
                        'code' => 'HTTP_ERROR',
                        'message' => $throwable->getMessage() !== '' ? $throwable->getMessage() : 'HTTP error.',
                    ],
                ];
            }
        }

        $normalized = $this->formatter->normalize([
            'status' => $status,
            'data' => $payload,
        ], $this->formatter->newTraceId());

        $responsePayload = $normalized['data'] ?? $payload;
        $response = new JsonResponse($responsePayload, (int) ($normalized['status'] ?? $status));
        if (isset($responsePayload['traceId']) && is_string($responsePayload['traceId'])) {
            $response->headers->set('X-Trace-Id', $responsePayload['traceId']);
        }

        $event->setResponse($response);
    }
}
