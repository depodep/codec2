<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'user_name',
        'role',
        'action',
        'module',
        'status',
        'description',
        'ip_address',
        'device',
        'browser',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function record(
        string $action,
        string $module,
        string $status = 'Success',
        ?string $description = null,
        ?User $user = null
    ): self {
        $user = $user ?? auth()->user();
        $request = request();

        $roleName = match((int) ($user?->role_id ?? 0)) {
            1 => 'Admin',
            2 => 'Faculty',
            3 => 'Student',
            4 => 'Management',
            default => $user ? 'User' : 'System'
        };

        return self::create([
            'user_id' => $user?->id,
            'user_name' => $user ? trim($user->first_name . ' ' . $user->last_name) : 'System / Guest',
            'role' => $roleName,
            'action' => $action,
            'module' => $module,
            'status' => $status,
            'description' => $description,
            'ip_address' => $request?->ip() ?? '127.0.0.1',
            'device' => $request?->header('Sec-Ch-Ua-Platform') ?? 'Windows',
            'browser' => $request?->userAgent() ?? 'Web Browser',
        ]);
    }
}