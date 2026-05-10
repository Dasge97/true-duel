<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\CatalogoPersonajesRepositorio;
use App\Repository\EquipoJugadorRepositorio;
use App\Repository\JugadorPersonajeRepositorio;
use App\Repository\PlayerProfileRepository;
use App\Repository\UserRepository;
use App\Support\UuidGenerator;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

final class AuthService
{
    public function __construct(
        private Connection $connection,
        private UserRepository $userRepository,
        private PlayerProfileRepository $profileRepository,
        private CatalogoPersonajesRepositorio $catalogoPersonajesRepositorio,
        private JugadorPersonajeRepositorio $jugadorPersonajeRepositorio,
        private EquipoJugadorRepositorio $equipoJugadorRepositorio,
        private TokenService $tokenService,
        private UuidGenerator $uuidGenerator,
    ) {
    }

    /** @param array<string, mixed> $payload */
    public function register(array $payload): array
    {
        $username = strtolower(trim((string) ($payload['username'] ?? '')));
        $email = strtolower(trim((string) ($payload['email'] ?? '')));
        $password = (string) ($payload['password'] ?? '');
        $displayName = trim((string) ($payload['displayName'] ?? ''));
        if ($displayName === '') {
            $displayName = $username;
        }

        if ($username === '' || $email === '' || $password === '' || $displayName === '') {
            return ['status' => 422, 'data' => ['error' => ['code' => 'INVALID_REGISTER', 'message' => 'username, email, password and displayName are required.']]];
        }

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return ['status' => 422, 'data' => ['error' => ['code' => 'INVALID_EMAIL', 'message' => 'Invalid email format.']]];
        }

        if (strlen($password) < 6) {
            return ['status' => 422, 'data' => ['error' => ['code' => 'WEAK_PASSWORD', 'message' => 'Password must be at least 6 characters.']]];
        }

        $playerId = $this->uuidGenerator->v4();
        $hash = password_hash($password, PASSWORD_BCRYPT);

        try {
            $this->connection->beginTransaction();
            $this->userRepository->create($playerId, $username, $email, $hash);
            $this->profileRepository->create($playerId, $displayName, 'Hierro', 1000, 'eu-west');
            $this->jugadorPersonajeRepositorio->inicializarParaJugador($playerId);
            $this->inicializarEquipoBase($playerId);
            $this->connection->commit();
        } catch (UniqueConstraintViolationException) {
            if ($this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }

            return ['status' => 409, 'data' => ['error' => ['code' => 'USER_ALREADY_EXISTS', 'message' => 'Username or email already exists.']]];
        } catch (\Throwable) {
            if ($this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }

            return ['status' => 500, 'data' => ['error' => ['code' => 'REGISTER_FAILED', 'message' => 'Could not register user.']]];
        }

        $token        = $this->tokenService->create($playerId, $displayName);
        $refreshToken = $this->tokenService->createRefresh($playerId);

        return [
            'status' => 201,
            'data' => [
                'token'        => $token,
                'refreshToken' => $refreshToken,
                'user'         => ['playerId' => $playerId, 'name' => $displayName, 'username' => $username],
            ],
        ];
    }

    /** @param array<string, mixed> $payload */
    public function login(array $payload): array
    {
        $username = strtolower(trim((string) ($payload['username'] ?? '')));
        $password = (string) ($payload['password'] ?? '');

        if ($username === '' || $password === '') {
            return ['status' => 422, 'data' => ['error' => ['code' => 'INVALID_LOGIN', 'message' => 'username and password are required.']]];
        }

        $user = $this->userRepository->findByUsernameOrEmail($username);
        if ($user === null || !password_verify($password, $user->passwordHash())) {
            return ['status' => 401, 'data' => ['error' => ['code' => 'INVALID_CREDENTIALS', 'message' => 'Invalid credentials.']]];
        }

        $profile      = $this->profileRepository->findByPlayerId($user->id());
        $displayName  = $profile?->displayName() ?? $user->username();
        $token        = $this->tokenService->create($user->id(), $displayName);
        $refreshToken = $this->tokenService->createRefresh($user->id());

        return [
            'status' => 200,
            'data' => [
                'token'        => $token,
                'refreshToken' => $refreshToken,
                'user'         => ['playerId' => $user->id(), 'name' => $displayName, 'username' => $user->username()],
            ],
        ];
    }

    /** @param array<string, mixed> $payload */
    public function refresh(array $payload): array
    {
        $refreshToken = trim((string) ($payload['refreshToken'] ?? ''));
        if ($refreshToken === '') {
            return ['status' => 422, 'data' => ['error' => ['code' => 'MISSING_REFRESH_TOKEN', 'message' => 'refreshToken is required.']]];
        }

        $decoded = $this->tokenService->decodeRefresh($refreshToken);
        if ($decoded === null) {
            return ['status' => 401, 'data' => ['error' => ['code' => 'INVALID_REFRESH_TOKEN', 'message' => 'Invalid or expired refresh token.']]];
        }

        $playerId    = $decoded['sub'];
        $profile     = $this->profileRepository->findByPlayerId($playerId);
        $user        = $this->userRepository->findById($playerId);
        $displayName = $profile?->displayName() ?? $user?->username() ?? '';

        if ($displayName === '') {
            return ['status' => 401, 'data' => ['error' => ['code' => 'PLAYER_NOT_FOUND', 'message' => 'Player not found.']]];
        }

        return [
            'status' => 200,
            'data' => [
                'token'        => $this->tokenService->create($playerId, $displayName),
                'refreshToken' => $this->tokenService->createRefresh($playerId),
            ],
        ];
    }

    private function inicializarEquipoBase(string $jugadorId): void
    {
        $equipo = [];
        foreach ($this->catalogoPersonajesRepositorio->todos() as $personaje) {
            if (!(bool) ($personaje['desbloqueadoInicial'] ?? false)) {
                continue;
            }
            $equipo[] = (string) ($personaje['id'] ?? '');
            if (count($equipo) === 3) {
                break;
            }
        }

        if (count($equipo) === 3) {
            $this->equipoJugadorRepositorio->guardar($jugadorId, $equipo);
        }
    }
}
