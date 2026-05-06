<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\CatalogoPersonajesRepositorio;
use App\Repository\EquipoJugadorRepositorio;
use App\Repository\JugadorPersonajeRepositorio;
use App\Repository\PlayerProfileRepository;
use App\Repository\UserRepository;
use PDO;
use PDOException;

final class AuthService
{
    public function __construct(
        private PDO $pdo,
        private UserRepository $userRepository,
        private PlayerProfileRepository $profileRepository,
        private CatalogoPersonajesRepositorio $catalogoPersonajesRepositorio,
        private JugadorPersonajeRepositorio $jugadorPersonajeRepositorio,
        private EquipoJugadorRepositorio $equipoJugadorRepositorio,
        private TokenService $tokenService,
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

        if (strlen($password) < 6) {
            return ['status' => 422, 'data' => ['error' => ['code' => 'WEAK_PASSWORD', 'message' => 'Password must be at least 6 characters.']]];
        }

        $playerId = $this->uuidV4();
        $hash = password_hash($password, PASSWORD_BCRYPT);

        try {
            $this->pdo->beginTransaction();
            $this->userRepository->create($playerId, $username, $email, $hash);
            $this->profileRepository->create($playerId, $displayName, 'Bronce I', 1000, 'eu-west');
            $this->jugadorPersonajeRepositorio->inicializarParaJugador($playerId);
            $this->inicializarEquipoBase($playerId);
            $this->pdo->commit();
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            $sqlState = (string) ($e->errorInfo[0] ?? '');
            if ($sqlState === '23505') {
                return ['status' => 409, 'data' => ['error' => ['code' => 'USER_ALREADY_EXISTS', 'message' => 'Username or email already exists.']]];
            }

            return ['status' => 500, 'data' => ['error' => ['code' => 'REGISTER_FAILED', 'message' => 'Could not register user.']]];
        }

        $token = $this->tokenService->create($playerId, $displayName);

        return [
            'status' => 201,
            'data' => [
                'token' => $token,
                'user' => ['playerId' => $playerId, 'name' => $displayName, 'username' => $username],
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

        $profile = $this->profileRepository->findByPlayerId($user->id());
        $displayName = $profile?->displayName() ?? $user->username();
        $token = $this->tokenService->create($user->id(), $displayName);

        return [
            'status' => 200,
            'data' => [
                'token' => $token,
                'user' => ['playerId' => $user->id(), 'name' => $displayName, 'username' => $user->username()],
            ],
        ];
    }

    private function uuidV4(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
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
