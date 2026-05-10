<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class ContentLengthSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::RESPONSE => ['onResponse', -10]];
    }

    public function onResponse(ResponseEvent $event): void
    {
        $response = $event->getResponse();
        $content  = $response->getContent();

        if ($content === false || $content === '') {
            return;
        }

        if (!$response->headers->has('Content-Length')) {
            $response->headers->set('Content-Length', (string) strlen($content));
        }

        $response->headers->remove('Transfer-Encoding');
    }
}
