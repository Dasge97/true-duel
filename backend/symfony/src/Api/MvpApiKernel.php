<?php

declare(strict_types=1);

namespace App\Api;

use App\Controller\Api\AuthController;
use App\Controller\Api\ChampionController;
use App\Controller\Api\GameplayController;
use App\Controller\Api\MissionController;
use App\Controller\Api\ProfileController;
use App\Controller\Api\StoreController;
use App\Repository\ChampionRatingRepository;
use App\Repository\GameMatchRepository;
use App\Repository\MatchHistoryRepository;
use App\Repository\MatchOutcomeRuleRepository;
use App\Repository\MissionCatalogRepository;
use App\Repository\MatchSettlementRepository;
use App\Repository\MatchmakingTicketRepository;
use App\Repository\ChampionCatalogRepository;
use App\Repository\PlayerChampionRepository;
use App\Repository\PlayerInventoryRepository;
use App\Repository\PlayerMissionRepository;
use App\Repository\PlayerProfileRepository;
use App\Repository\StoreCatalogRepository;
use App\Repository\TurnRepository;
use App\Repository\UserRepository;
use App\Service\AuthService;
use App\Service\ChampionService;
use App\Service\GameplayService;
use App\Service\MissionService;
use App\Service\ProfileService;
use App\Service\StoreService;
use App\Service\TokenService;
use PDO;
use Throwable;

final class MvpApiKernel
{
    private ?PDO $pdo;
    private TokenService $tokenService;
    private ?AuthController $authController = null;
    private ?ChampionController $championController = null;
    private ?StoreController $storeController = null;
    private ?MissionController $missionController = null;
    private ?ProfileController $profileController = null;
    private ?GameplayController $gameplayController = null;

    public function __construct()
    {
        $this->pdo = $this->connectDb();
        $this->tokenService = new TokenService();

        if ($this->pdo !== null) {
            $userRepository = new UserRepository($this->pdo);
            $profileRepository = new PlayerProfileRepository($this->pdo);
            $championCatalogRepository = new ChampionCatalogRepository($this->pdo);
            $storeCatalogRepository = new StoreCatalogRepository($this->pdo);
            $missionCatalogRepository = new MissionCatalogRepository($this->pdo);
            $playerChampionRepository = new PlayerChampionRepository($this->pdo, $championCatalogRepository);
            $playerInventoryRepository = new PlayerInventoryRepository($this->pdo);
            $playerMissionRepository = new PlayerMissionRepository($this->pdo, $missionCatalogRepository);
            $historyRepository = new MatchHistoryRepository($this->pdo);
            $matchOutcomeRuleRepository = new MatchOutcomeRuleRepository($this->pdo);
            $matchRepository = new GameMatchRepository($this->pdo);
            $ticketRepository = new MatchmakingTicketRepository($this->pdo);
            $turnRepository = new TurnRepository($this->pdo);
            $championRatingRepository = new ChampionRatingRepository($this->pdo);
            $matchSettlementRepository = new MatchSettlementRepository($this->pdo);

            $authService = new AuthService(
                $this->pdo,
                $userRepository,
                $profileRepository,
                $playerChampionRepository,
                $this->tokenService,
            );
            $championService = new ChampionService($this->pdo, $playerChampionRepository, $profileRepository, $championCatalogRepository);
            $storeService = new StoreService($this->pdo, $profileRepository, $playerInventoryRepository, $storeCatalogRepository);
            $missionService = new MissionService($this->pdo, $profileRepository, $playerMissionRepository, $missionCatalogRepository);
            $profileService = new ProfileService($profileRepository, $historyRepository, $championRatingRepository);
            $gameplayService = new GameplayService(
                $this->pdo,
                $matchRepository,
                $ticketRepository,
                $turnRepository,
                $historyRepository,
                $profileRepository,
                $playerChampionRepository,
                $playerMissionRepository,
                $championCatalogRepository,
                $matchOutcomeRuleRepository,
                $championRatingRepository,
                $matchSettlementRepository,
            );

            $this->authController = new AuthController($authService);
            $this->championController = new ChampionController($championService);
            $this->storeController = new StoreController($storeService);
            $this->missionController = new MissionController($missionService);
            $this->profileController = new ProfileController($profileService);
            $this->gameplayController = new GameplayController($gameplayService);
        }
    }

    /** @param array<string, string> $headers */
    public function handle(string $method, string $path, array $headers = [], ?array $body = null): array
    {
        if ($this->pdo === null) {
            return $this->error(503, 'DB_UNAVAILABLE', 'Database unavailable.');
        }

        if ($method === 'POST' && $path === '/v1/auth/register') {
            return $this->authController?->register($body ?? []) ?? $this->error(503, 'DB_UNAVAILABLE', 'Database unavailable.');
        }

        if ($method === 'POST' && $path === '/v1/auth/login') {
            return $this->authController?->login($body ?? []) ?? $this->error(503, 'DB_UNAVAILABLE', 'Database unavailable.');
        }

        if ($method === 'GET' && $path === '/v1/ranking') {
            $auth = $this->requireAuth($headers);
            if (isset($auth['status'])) {
                return $auth;
            }
            return $this->profileController?->ranking() ?? $this->error(503, 'DB_UNAVAILABLE', 'Database unavailable.');
        }

        if ($method === 'GET' && $path === '/v1/users') {
            $auth = $this->requireAuth($headers);
            if (isset($auth['status'])) {
                return $auth;
            }
            return $this->profileController?->users() ?? $this->error(503, 'DB_UNAVAILABLE', 'Database unavailable.');
        }

        if ($method === 'GET' && $path === '/v1/profile') {
            $auth = $this->requireAuth($headers);
            if (isset($auth['status'])) {
                return $auth;
            }
            return $this->profileController?->profile((string) ($auth['playerId'] ?? '')) ?? $this->error(503, 'DB_UNAVAILABLE', 'Database unavailable.');
        }

        if ($method === 'GET' && $path === '/v1/champions/catalog') {
            $auth = $this->requireAuth($headers);
            if (isset($auth['status'])) {
                return $auth;
            }
            return $this->championController?->catalog((string) ($auth['playerId'] ?? '')) ?? $this->error(503, 'DB_UNAVAILABLE', 'Database unavailable.');
        }

        if ($method === 'GET' && $path === '/v1/champions/me') {
            $auth = $this->requireAuth($headers);
            if (isset($auth['status'])) {
                return $auth;
            }
            return $this->championController?->mine((string) ($auth['playerId'] ?? '')) ?? $this->error(503, 'DB_UNAVAILABLE', 'Database unavailable.');
        }

        if ($method === 'GET' && $path === '/v1/store/catalog') {
            $auth = $this->requireAuth($headers);
            if (isset($auth['status'])) {
                return $auth;
            }
            return $this->storeController?->catalog((string) ($auth['playerId'] ?? '')) ?? $this->error(503, 'DB_UNAVAILABLE', 'Database unavailable.');
        }

        if ($method === 'GET' && $path === '/v1/store/inventory') {
            $auth = $this->requireAuth($headers);
            if (isset($auth['status'])) {
                return $auth;
            }
            return $this->storeController?->inventory((string) ($auth['playerId'] ?? '')) ?? $this->error(503, 'DB_UNAVAILABLE', 'Database unavailable.');
        }

        if ($method === 'POST' && $path === '/v1/store/purchase') {
            $auth = $this->requireAuth($headers);
            if (isset($auth['status'])) {
                return $auth;
            }
            return $this->storeController?->purchase((string) ($auth['playerId'] ?? ''), $body ?? []) ?? $this->error(503, 'DB_UNAVAILABLE', 'Database unavailable.');
        }

        if ($method === 'POST' && $path === '/v1/store/equip') {
            $auth = $this->requireAuth($headers);
            if (isset($auth['status'])) {
                return $auth;
            }
            return $this->storeController?->equip((string) ($auth['playerId'] ?? ''), $body ?? []) ?? $this->error(503, 'DB_UNAVAILABLE', 'Database unavailable.');
        }

        if ($method === 'GET' && $path === '/v1/missions/daily') {
            $auth = $this->requireAuth($headers);
            if (isset($auth['status'])) {
                return $auth;
            }
            return $this->missionController?->daily((string) ($auth['playerId'] ?? '')) ?? $this->error(503, 'DB_UNAVAILABLE', 'Database unavailable.');
        }

        if ($method === 'POST' && $path === '/v1/missions/claim') {
            $auth = $this->requireAuth($headers);
            if (isset($auth['status'])) {
                return $auth;
            }
            return $this->missionController?->claim((string) ($auth['playerId'] ?? ''), $body ?? []) ?? $this->error(503, 'DB_UNAVAILABLE', 'Database unavailable.');
        }

        if ($method === 'POST' && $path === '/v1/champions/unlock') {
            $auth = $this->requireAuth($headers);
            if (isset($auth['status'])) {
                return $auth;
            }
            return $this->championController?->unlock((string) ($auth['playerId'] ?? ''), $body ?? []) ?? $this->error(503, 'DB_UNAVAILABLE', 'Database unavailable.');
        }

        if ($method === 'POST' && $path === '/v1/champions/select') {
            $auth = $this->requireAuth($headers);
            if (isset($auth['status'])) {
                return $auth;
            }
            return $this->championController?->select((string) ($auth['playerId'] ?? ''), $body ?? []) ?? $this->error(503, 'DB_UNAVAILABLE', 'Database unavailable.');
        }

        if ($method === 'GET' && preg_match('#^/v1/profile/([a-zA-Z0-9\-]+)$#', $path, $m) === 1) {
            $auth = $this->requireAuth($headers);
            if (isset($auth['status'])) {
                return $auth;
            }
            return $this->profileController?->profile($m[1]) ?? $this->error(503, 'DB_UNAVAILABLE', 'Database unavailable.');
        }

        if ($method === 'GET' && $path === '/v1/history') {
            $auth = $this->requireAuth($headers);
            if (isset($auth['status'])) {
                return $auth;
            }
            return $this->profileController?->history((string) ($auth['playerId'] ?? '')) ?? $this->error(503, 'DB_UNAVAILABLE', 'Database unavailable.');
        }

        if ($method === 'POST' && $path === '/v1/matchmaking/enqueue') {
            $auth = $this->requireAuth($headers);
            if (isset($auth['status'])) {
                return $auth;
            }
            return $this->gameplayController?->enqueue((string) ($auth['playerId'] ?? ''), $body ?? []) ?? $this->error(503, 'DB_UNAVAILABLE', 'Database unavailable.');
        }

        if ($method === 'GET' && preg_match('#^/v1/matchmaking/tickets/([a-zA-Z0-9\-]+)$#', $path, $m) === 1) {
            $auth = $this->requireAuth($headers);
            if (isset($auth['status'])) {
                return $auth;
            }
            return $this->gameplayController?->ticketStatus((string) ($auth['playerId'] ?? ''), $m[1]) ?? $this->error(503, 'DB_UNAVAILABLE', 'Database unavailable.');
        }

        if ($method === 'POST' && preg_match('#^/v1/matches/([a-zA-Z0-9\-]+)/turns$#', $path, $m) === 1) {
            $auth = $this->requireAuth($headers);
            if (isset($auth['status'])) {
                return $auth;
            }
            return $this->gameplayController?->resolveTurn((string) ($auth['playerId'] ?? ''), $m[1], $body ?? []) ?? $this->error(503, 'DB_UNAVAILABLE', 'Database unavailable.');
        }

        if ($method === 'GET' && preg_match('#^/v1/matches/([a-zA-Z0-9\-]+)$#', $path, $m) === 1) {
            $auth = $this->requireAuth($headers);
            if (isset($auth['status'])) {
                return $auth;
            }
            return $this->gameplayController?->match((string) ($auth['playerId'] ?? ''), $m[1]) ?? $this->error(503, 'DB_UNAVAILABLE', 'Database unavailable.');
        }

        if ($method === 'POST' && preg_match('#^/v1/matches/([a-zA-Z0-9\-]+)/complete$#', $path, $m) === 1) {
            $auth = $this->requireAuth($headers);
            if (isset($auth['status'])) {
                return $auth;
            }
            return $this->gameplayController?->completeMatch((string) ($auth['playerId'] ?? ''), $m[1]) ?? $this->error(503, 'DB_UNAVAILABLE', 'Database unavailable.');
        }

        return $this->error(404, 'NOT_FOUND', 'Endpoint not found.');
    }

    /** @return array<string, mixed>|null */
    private function requireAuth(array $headers): ?array
    {
        $authorization = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        if (!is_string($authorization) || !str_starts_with($authorization, 'Bearer ')) {
            return $this->error(401, 'UNAUTHORIZED', 'Bearer token missing.');
        }

        $payload = $this->tokenService->decode(substr($authorization, 7));
        if ($payload === null) {
            return $this->error(401, 'INVALID_TOKEN', 'Invalid or expired token.');
        }

        return ['playerId' => $payload['sub']];
    }

    private function error(int $status, string $code, string $message): array
    {
        return ['status' => $status, 'data' => ['error' => ['code' => $code, 'message' => $message]]];
    }

    private function connectDb(): ?PDO
    {
        $dsn = getenv('DATABASE_URL') ?: 'pgsql:host=db;port=5432;dbname=true_duel';
        $user = getenv('DATABASE_USER') ?: 'true_duel';
        $password = getenv('DATABASE_PASSWORD') ?: 'true_duel';

        try {
            return new PDO($dsn, $user, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (Throwable) {
            return null;
        }
    }
}
