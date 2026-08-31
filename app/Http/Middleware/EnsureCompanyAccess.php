<?php

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanyAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $company = $request->route('company');
        $companyId = $company instanceof Company ? $company->id : $company;

        abort_unless($user && ($user->isSuperAdmin() || ! $companyId || (int) $user->company_id === (int) $companyId), 403);

        return $next($request);
    }
}
