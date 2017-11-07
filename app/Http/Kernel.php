<?php

namespace App\Http;

use App\Http\Middleware\BasicAuth;
use App\Http\Middleware\CompanyUserMiddleware;
use App\Http\Middleware\CreateDeviceMiddleware;
use App\Http\Middleware\SkollUserMiddleware;
use App\Http\Middleware\VerifyPermissions;
use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
	/**
	 * The application's global HTTP middleware stack.
	 *
	 * These middleware are run during every request to your application.
	 *
	 * @var array
	 */
	protected $middleware = [
//        \Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode::class,
	];

	/**
	 * The application's route middleware groups.
	 *
	 * @var array
	 */
	protected $middlewareGroups = [

	];

	/**
	 * The application's route middleware.
	 *
	 * These middleware may be assigned to groups or used individually.
	 *
	 * @var array
	 */
	protected $routeMiddleware = [
	];
}
