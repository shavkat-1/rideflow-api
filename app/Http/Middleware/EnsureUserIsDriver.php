<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsDriver
{
    public function handle(
        Request $request,
        Closure $next
    ): Response|JsonResponse {
        $user = $request->user();

        if ($user === null) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if ($user->role !== UserRole::Driver) {
            return response()->json([
                'message' => 'Доступ разрешён только водителям',
            ], 403);
        }

        return $next($request);
    }
}
