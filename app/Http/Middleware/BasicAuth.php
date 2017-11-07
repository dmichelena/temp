<?php

namespace App\Http\Middleware;

use App\Http\Request;
use Closure;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class BasicAuth
{
	/**
	 * @param Request $request
	 * @param Closure $next
	 * @return mixed
	 */
	public function handle(Request $request, Closure $next)
	{
		if (\Auth::onceBasic()) {
			throw new UnauthorizedHttpException('', 'Invalid credentials.');
		}

		return $next($request);
	}
}