<?php

namespace App\Support\Audit;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AuditRequestContext
{
    public static function capture(): array
    {
        if (! app()->bound('request')) {
            return [];
        }

        $request = request();

        if (! $request instanceof Request) {
            return [];
        }

        return [
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'route_name' => $request->route()?->getName(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'request_id' => self::requestId($request),
            ...self::impersonatorContext($request),
        ];
    }

    private static function requestId(Request $request): string
    {
        $requestId = $request->headers->get('X-Request-ID') ??
          $request->headers->get('X-Correlation-ID');

        if ($requestId) {
            return $requestId;
        }

        if (! $request->attributes->has('audit_request_id')) {
            $request->attributes->set('audit_request_id', (string) Str::orderedUuid());
        }

        return $request->attributes->get('audit_request_id');
    }

    private static function impersonatorContext(Request $request): array
    {
        if (! $request->hasSession() || ! session()->has('impersonator_id')) {
            return [];
        }

        return [
            'impersonator_type' => User::class,
            'impersonator_id' => session('impersonator_id'),
        ];
    }
}
