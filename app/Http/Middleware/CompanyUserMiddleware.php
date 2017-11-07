<?php

namespace App\Http\Middleware;

use App\Http\Request;
use Closure;
use Skoll\Utils\Jwt;
use Skoll\Repositories\Contracts\CompanyUserRepositoryContract;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class CompanyUserMiddleware
{
	protected $companyUserRepository;

	public function __construct(CompanyUserRepositoryContract $companyUserRepository)
	{
		$this->companyUserRepository = $companyUserRepository;
	}

	/**
	 * @param Request $request
	 * @param Closure $next
	 * @return mixed
	 */
	public function handle(Request $request, Closure $next)
	{
		if ($this->attachCompanyUser($request)) {
			return $next($request);
		}

		throw new UnauthorizedHttpException('', 'Invalid credentials.');
	}

	/**
	 * @param Request $request
	 * @return bool
	 */
	protected function attachCompanyUser(Request $request)
	{
		$token = $request->getAuthToken();
		$deviceIdentifier = $request->getDeviceIdentifier();

		$jwt = Jwt::parse($token);

		if (!$jwt) {
			return false;
		}

		$data = $jwt->all();
		$userId = array_get($data, 'companyUserId');
		$deviceId = array_get($data, 'deviceId');

		if (!$userId || !$deviceId) {
			return false;
		}

		$user = $this->companyUserRepository->find($userId, $deviceId);

		if (!$user) {
			return false;
		}

		$device = $user->device();
		if (!$device || !$device->isActive() || $device->deviceId() != $deviceIdentifier) {
			return false;
		}

		$request->withCompanyUser($user);

		return true;
	}
}
