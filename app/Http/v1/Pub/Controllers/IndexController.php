<?php

namespace App\Http\v1\Pub\Controllers;

use App\Http\Base\ApiController;
use App\Http\Fetch;
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
		$data = $this->data();
		$sent = false;
		if ($data['send']) {
			$response = $this->sendToMulticines($data['data']);
			$this->sendEmail($response);
			$sent = true;
		}

		echo $sent ? 'Se envió el mail' : 'No es necesario mandar el mail';
		echo '<script>';
		echo 'location.reload();';
		echo '</script>';
		die();
	}

	private function sendEmail($response)
	{
		\Mail::send('asd', ['data' => $response], function ($m) {
			$m->from('hello@app.com', 'MulticinesEc');
			$m->to('hello@app.com', 'MulticinesEc')->subject('Response:');
		});
	}

	/**
	 * @param $data
	 * @return array|null
	 */
	private function sendToMulticines($data)
	{
		$result = Fetch::soap($data['endpoint'], $data['method'], $data['params'], true);
		if ($result->hasErrors()) {
			return ['ERRORES' => $result->getErrors()];
		}

		return $result->getResult();
	}

	private function data()
	{
		$url = 'https://raw.githubusercontent.com/dmichelena/dots/master/mc.json';
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'Content-Type: application/json'
		]);

		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);

		$response = curl_exec($ch);
		curl_close($ch);

		$response = json_decode($response, true);

		return $response;
	}
}
