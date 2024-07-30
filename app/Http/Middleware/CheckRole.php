<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        // kiem tra xem nguoi dung co dang nhap hop le hay khong
        if ($request->user() && in_array($request->user()->role, $roles)) {
            return $next($request);
        }
        // neu khong thuoc role truyen vao -> loi 403
        abort(403, 'Unauthorized action.');
    }
}
