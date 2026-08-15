<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Support\Toast;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class KelasController extends BaseAppController implements HasMiddleware
{

    public static function middleware(): array
    {
        return [
            function (Request $request, Closure $next) {
                return $next($request);
            },
        ];
    }

    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        return inertia("guru/Kelas/Detail");
    }
}
