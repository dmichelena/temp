<?php

namespace App\Http\Middleware;

use App\Http\Request;
use Closure;
use Skoll\Models\ApiUser;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class VerifyPermissions
{
	/**
	 * @param Request $request
	 * @param Closure $next
	 * @return mixed
	 */
	public function handle(Request $request, Closure $next)
	{
		if (!$this->canContinue()) {
			throw new UnauthorizedHttpException('', 'You do not have permissions to access.');
		}

		return $next($request);
	}

	/**
	 * @return bool
	 */
	protected function canContinue()
	{
		$user = \Auth::user();
		if (!$user) {
			return false;
		}

		/* @var $user ApiUser */
		$url = request()->path();

		return $user->canAccessTo($url);
	}
}