<?php

namespace App\Support;

class AdminGate
{
    private const SESSION_KEY = 'admin_authenticated';

    public static function isConfigured(): bool
    {
        $username = config('admin.username');
        $password = config('admin.password');

        return is_string($username) && $username !== ''
            && is_string($password) && $password !== '';
    }

    public static function isAuthenticated(): bool
    {
        return session(self::SESSION_KEY) === true;
    }

    public static function attempt(string $username, string $password): bool
    {
        if (! self::isConfigured()) {
            return false;
        }

        $validUsername = hash_equals((string) config('admin.username'), $username);
        $validPassword = hash_equals((string) config('admin.password'), $password);

        if (! $validUsername || ! $validPassword) {
            return false;
        }

        session([self::SESSION_KEY => true]);

        return true;
    }

    public static function logout(): void
    {
        session()->forget(self::SESSION_KEY);
    }
}
