<?php

namespace App\Core;

use App\Models\Role;
use App\Models\User;

class Auth
{
    public static function user(): ?array
    {
        if (empty($_SESSION['user_id'])) {
            return null;
        }

        return User::findById((int) $_SESSION['user_id']);
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function isAdmin(): bool
    {
        $user = self::user();
        if (! $user) {
            return false;
        }

        return Role::userHasRole((int) $user['id'], 'administrador');
    }

    public static function roleName(): ?string
    {
        $user = self::user();
        if (! $user) {
            return null;
        }

        return Role::getUserRoleName((int) $user['id']);
    }

    public static function attempt(string $email, string $password): bool
    {
        $user = User::findByEmail($email);
        if (! $user || $user['estado'] !== 'activo') {
            return false;
        }

        if (! password_verify($password, $user['password_hash'])) {
            return false;
        }

        $_SESSION['user_id'] = $user['id'];

        return true;
    }

    public static function logout(): void
    {
        unset($_SESSION['user_id']);
    }
}
