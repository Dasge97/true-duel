<?php

declare(strict_types=1);

namespace App\Controller\Http;

use App\Http\ApiControllerSupport;
use App\Service\GameplayService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class GameplayHttpController
{
    public function __construct(
        private readonly GameplayService $gameplayService,
        private readonly ApiControllerSupport $api,
    ) {
    }

    public function enqueue(Request $request): JsonResponse
    {
        if (($error = $this->api->authError($request)) !== null) {
            return $error;
        }

        return $this->api->respond(
            $this->gameplayService->enqueue(
                $this->api->playerId($request),
                $this->api->decodeBody($request)
            )
        );
    }

    public function activeTicket(Request $request): JsonResponse
    {
        if (($error = $this->api->authError($request)) !== null) {
            return $error;
        }

        return $this->api->respond(
            $this->gameplayService->activeTicket($this->api->playerId($request))
        );
    }

    public function ticketStatus(Request $request, string $ticketId): JsonResponse
    {
        if (($error = $this->api->authError($request)) !== null) {
            return $error;
        }

        return $this->api->respond(
            $this->gameplayService->ticketStatus($this->api->playerId($request), $ticketId)
        );
    }

    public function cancelTicket(Request $request, string $ticketId): JsonResponse
    {
        if (($error = $this->api->authError($request)) !== null) {
            return $error;
        }

        return $this->api->respond(
            $this->gameplayService->cancelTicket($this->api->playerId($request), $ticketId)
        );
    }

    public function resolveTurn(Request $request, string $matchId): JsonResponse
    {
        if (($error = $this->api->authError($request)) !== null) {
            return $error;
        }

        $body = $this->api->decodeBodyWithIdempotency($request);

        return $this->api->respond(
            $this->gameplayService->resolveTurn(
                $this->api->playerId($request),
                $matchId,
                $body
            )
        );
    }

    public function match(Request $request, string $matchId): JsonResponse
    {
        if (($error = $this->api->authError($request)) !== null) {
            return $error;
        }

        return $this->api->respond(
            $this->gameplayService->match($this->api->playerId($request), $matchId)
        );
    }

    public function complete(Request $request, string $matchId): JsonResponse
    {
        if (($error = $this->api->authError($request)) !== null) {
            return $error;
        }

        return $this->api->respond(
            $this->gameplayService->completeMatch($this->api->playerId($request), $matchId)
        );
    }

    public function abandon(Request $request, string $matchId): JsonResponse
    {
        if (($error = $this->api->authError($request)) !== null) {
            return $error;
        }

        return $this->api->respond(
            $this->gameplayService->abandonMatch($this->api->playerId($request), $matchId)
        );
    }
}
