<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DomainMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $host = strtolower($request->getHost());
        $path = strtolower(ltrim((string) $request->path(), '/'));
        $domains = config('hris.domains', []);
        $hostToPortal = [];

        foreach ($domains as $portal => $domain) {
            $domain = strtolower(trim((string) $domain));
            if ($domain !== '') {
                $hostToPortal[$domain] = $portal;
            }
        }

        $portal = $hostToPortal[$host] ?? null;

        if ($portal === null && in_array($host, ['localhost', '127.0.0.1', '[::1]', '::1'], true)) {
            if (str_starts_with($path, 'mpr')) {
                $portal = 'mpr';
            } elseif (str_starts_with($path, 'assets')) {
                $portal = 'assets';
            } elseif (str_starts_with($path, 'certifications') || str_starts_with($path, 'cert')) {
                $portal = 'certificates';
            } elseif (str_starts_with($path, 'hr')) {
                $portal = 'hris';
            } elseif (str_starts_with($path, 'outsource')) {
                $portal = 'outsource';
            } elseif (str_starts_with($path, 'recruitment')) {
                $portal = 'recruitment';
            }
        }

        if ($portal) {
            $request->attributes->set('portal', $portal);
            $request->attributes->set('portal_access', config("hris.portal_access.{$portal}.access", 'public'));
            $request->attributes->set('auth_source', config("hris.portal_access.{$portal}.auth_source", 'none'));
        } else {
            $request->attributes->set('portal', 'public');
            $request->attributes->set('portal_access', 'public');
            $request->attributes->set('auth_source', 'none');
        }

        return $next($request);
    }
}
