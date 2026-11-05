<?php
namespace App\Support;

use App\Models\AuditLog;

class Audit
{
    public static function record(string $action, string $type, ?int $id, string $description, array $metadata = []): void
    {
        $user = request()->user();
        AuditLog::create([
            'action' => $action, 'entity_type' => $type, 'entity_id' => $id,
            'description' => $description,
            'actor' => $user ? $user->name.' (#'.$user->id.')' : 'Console',
            'metadata' => $metadata,
        ]);
    }
}
