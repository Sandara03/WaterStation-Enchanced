<?php
namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $r)
    {
        $d = $r->validate(['search' => 'nullable|string|max:255']);
        return AuditLog::when($d['search'] ?? null, fn ($q, $s) => $q->where(fn ($q) => $q->where('action', 'like', '%'.$s.'%')->orWhere('actor', 'like', '%'.$s.'%')->orWhere('description', 'like', '%'.$s.'%')))->latest('id')->paginate(25);
    }
}
