<?php

namespace App\Auth\Repositories;

interface IAuthRepository
{
    public function register(array $data): array;

    public function login(array $credentials): array;

    public function logout(int $userId): void;

    public function updateProfile(int $userId, array $data): array;

    public function changePassword(int $userId): array;
}
