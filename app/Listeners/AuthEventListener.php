<?php

namespace App\Listeners;

use App\Models\AuditLog;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Failed;

class AuthEventListener
{
    public function handleLogin(Login $event): void
    {
        $user = $event->user;
        AuditLog::record(
            action: 'Login',
            module: 'Authentication',
            status: 'Success',
            description: 'User logged into the SIATRACK system successfully.',
            user: $user
        );
    }

    public function handleLogout(Logout $event): void
    {
        $user = $event->user;
        if (!$user) return;

        AuditLog::record(
            action: 'Logout',
            module: 'Authentication',
            status: 'Success',
            description: 'User logged out of the system.',
            user: $user
        );
    }

    public function handleFailed(Failed $event): void
    {
        $credentials = $event->credentials;
        $username = $credentials['email'] ?? $credentials['username'] ?? 'Unknown User';

        AuditLog::record(
            action: 'Failed Login',
            module: 'Authentication',
            status: 'Failed',
            description: "Failed login attempt for identifier: '{$username}'.",
            user: null
        );
    }

    public function subscribe($events): array
    {
        return [
            Login::class => 'handleLogin',
            Logout::class => 'handleLogout',
            Failed::class => 'handleFailed',
        ];
    }
}