<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property string $role
 * @property Carbon|null $email_verified_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasUuids, Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_MEMBER = 'member';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // The schema deliberately has no remember_token column: sessions are
    // cookie-based and "remember me" is not part of this template.
    public function getRememberTokenName(): string
    {
        return '';
    }

    /** @return array{id: string, name: string, email: string, role: string, tenant_id: string|null} */
    public function toApiPayload(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            // Null unless the optional tenancy module added the column.
            'tenant_id' => $this->getAttribute('tenant_id'),
        ];
    }
}
