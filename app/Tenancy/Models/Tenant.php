<?php

namespace App\Tenancy\Models;

use Database\Factories\Tenancy\TenantFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * The tenancy boundary itself, not a tenant-scoped table: no RLS and no
 * TenantScope.
 *
 * @property string $id
 * @property string $name
 * @property string $slug
 * @property Carbon|null $deleted_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
    ];

    protected static function newFactory(): TenantFactory
    {
        return TenantFactory::new();
    }
}
