<?php

namespace App\Tenancy\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Tenancy\Models\Note;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Example tenant-scoped CRUD. Every route runs behind `auth.tenant`, so the
 * tenant context is already open: queries are scoped by both the Eloquent
 * global scope and RLS without this controller doing anything.
 *
 * Routes use `{id}` rather than implicit model binding: SubstituteBindings
 * can run before the tenant context exists, which would make every binding
 * miss.
 */
class NoteController extends Controller
{
    public function index(): JsonResponse
    {
        return $this->ok(['notes' => Note::query()->orderBy('created_at')->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'body' => ['nullable', 'string'],
        ]);

        // tenant_id is filled from the active TenantContext by BelongsToTenant;
        // a body-supplied tenant_id is never read.
        $note = Note::query()->create($data);

        return $this->ok($note->toArray(), 201);
    }

    public function destroy(string $id): JsonResponse
    {
        Note::query()->findOrFail($id)->delete();

        return $this->ok(['status' => 'deleted']);
    }
}
