<?php

namespace App\Http\Middleware;

use Jenssegers\Agent\Agent;
use Skoll\Models\Device;
use App\Http\Request;
use Closure;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class CreateDeviceMiddleware
{
	/**
	 * @param Request $request
	 * @param Closure $next
	 * @return mixed
	 */
	public function handle(Request $request, Closure $next)
	{
		$device = $this->createDevice($request);

		if (!$device) {
			throw new UnauthorizedHttpException('', 'Invalid credentials.');
		};

		$request->withNewDevice($device);

		return $next($request);
	}

	/**
	 * @param Request $request
	 * @return Device|null
	 */
	protected function createDevice(Request $request)
	{
		$deviceIdentifier = $request->getDeviceIdentifier();

		if (empty($deviceIdentifier)) {
			return null;
		}

		$agent = app('agent');
		/* @var Agent $agent */

		$device = new Device([
			'deviceId'      => $deviceIdentifier,
			'userAgentJson' => [
				'userAgent'       => $agent->getUserAgent(),
				'browser'         => $agent->browser(),
				'browserVersion'  => $agent->version($agent->browser()),
				'device'          => $agent->device(),
				'deviceVersion'   => $agent->version($agent->device()),
				'platform'        => $agent->platform(),
				'platformVersion' => $agent->version($agent->platform()),
				'robot'           => $agent->robot(),
				'isDesktop'       => $agent->isDesktop(),
				'isMobile'        => $agent->isMobile(),
				'isPhone'         => $agent->isPhone(),
				'isTablet'        => $agent->isTablet(),
				'isRobot'         => $agent->isRobot(),
			],
		]);

		return $device;
	}
}
