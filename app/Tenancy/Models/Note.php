<?php

namespace App\Tenancy\Models;

use App\Tenancy\Concerns\BelongsToTenant;
use Database\Factories\Tenancy\NoteFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A deliberately trivial tenant-scoped model that demonstrates the pattern:
 * a `tenant_id` column, the BelongsToTenant trait, an RLS policy, and a
 * manifest entry in config/tenancy.php. Copy it, or delete it once you have
 * a real tenant-scoped model.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $title
 * @property string|null $body
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Note extends Model
{
    /** @use HasFactory<NoteFactory> */
    use BelongsToTenant, HasFactory, HasUuids;

    protected $fillable = [
        'tenant_id',
        'title',
        'body',
    ];

    protected static function newFactory(): NoteFactory
    {
        return NoteFactory::new();
    }
}
