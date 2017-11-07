<?php

namespace App\Http\Middleware;

use App\Http\Request;
use Closure;
use Skoll\Utils\Jwt;
use Skoll\Repositories\Contracts\UserRepositoryContract;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class SkollUserMiddleware
{
	protected $userRepository;

	public function __construct(UserRepositoryContract $userRepository)
	{
		$this->userRepository = $userRepository;
	}

	/**
	 * @param Request $request
	 * @param Closure $next
	 * @return mixed
	 */
	public function handle(Request $request, Closure $next)
	{
		if ($this->attachSkollUser($request)) {
			return $next($request);
		}

		throw new UnauthorizedHttpException('', 'Invalid credentials.');
	}

	/**
	 * @param Request $request
	 * @return bool
	 */
	protected function attachSkollUser(Request $request)
	{
		$token = $request->getAuthToken();
		$deviceIdentifier = $request->getDeviceIdentifier();

		$jwt = Jwt::parse($token);

		if (!$jwt) {
			return false;
		}

		$data = $jwt->all();
		$userId = array_get($data, 'userId');
		$deviceId = array_get($data, 'deviceId');

		if (!$userId || !$deviceId) {
			return false;
		}

		$user = $this->userRepository->find($userId, $deviceId);

		if (!$user) {
			return false;
		}

		$device = $user->device();
		if (!$device || !$device->isActive() ||$device->deviceId() != $deviceIdentifier) {
			return false;
		}

		$request->withSkollUser($user);

		return true;
	}
}
