<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Identity\CoreIdentity;
use App\Models\PortalAdministrator;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RequirePortalAdministrator
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var CoreIdentity|null $identity */
        $identity = $request->attributes->get('dg_identity');
        abort_unless(
            $identity && PortalAdministrator::query()->whereKey($identity->reference)->exists(),
            403,
        );

        return $next($request);
    }
}
