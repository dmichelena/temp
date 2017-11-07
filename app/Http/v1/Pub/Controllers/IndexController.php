<?php

namespace App\Http\v1\Pub\Controllers;

use App\Http\Base\ApiController;
use Google\Cloud\PubSub\PubSubClient;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Kreait\Firebase\Database;
use Skoll\Eloquent\Models\Core\OrderLog;
use Skoll\Jobs\UpdateOrderOnFireBase;
use Skoll\Repositories\Eloquent\OrderRepository;

class IndexController extends ApiController
{
	/**
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function index()
	{
		echo '<pre>';
		print_r(123);
		die();
	}

	/**
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function test()
	{
	}
}
