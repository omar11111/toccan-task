<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class EnsureIdempotencyKeyIsPresent
{
    /**
     * مسؤولية الـ Middleware: HTTP concern بس - هل الـ header موجود؟
     * الـ lookup الفعلي (هل ده استخدم قبل كده) مسؤولية PaymentService.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->hasHeader('Idempotency-Key') || trim((string) $request->header('Idempotency-Key')) === '') {
            throw ValidationException::withMessages([
                'idempotency_key' => 'The Idempotency-Key header is required.',
            ]);
        }

        return $next($request);
    }
}
