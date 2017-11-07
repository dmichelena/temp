<?php

namespace App\Http;

class Fetch
{
	protected static $timeout = 20;
	protected static $_oldTimeout = 60;

	protected $method = null;
	protected $request = null;
	protected $response = null;
	protected $result = null;
	protected $errors = null;

	public static function setTimeout($timeout)
	{
		if ($timeout <= 0)
			throw new \Exception("Timeout must be positive", 0, null);
		static::$timeout = $timeout;
	}

	public function setRequest($request)
	{
		$this->request = $request;
		return $this;
	}

	public function getRequest()
	{
		return $this->request;
	}

	public function setResponse($response)
	{
		$this->response = $response;
		return $this;
	}

	public function getResponse()
	{
		return $this->response;
	}

	public function setResult($result)
	{
		$this->result = $result;
		return $this;
	}

	public function getResult()
	{
		return $this->result;
	}

	public function addError($error)
	{
		if ($this->errors === null)
			$this->errors = [];
		$this->errors[] = $error;
		return $this;
	}

	public function getErrors()
	{
		if ($this->errors === null)
			return [];
		return $this->errors;
	}

	public function hasErrors()
	{
		return ($this->errors != null) && count($this->errors) > 0;
	}

	protected static function __applyTimeout($timeout = null)
	{
		static::$_oldTimeout = ini_get('default_socket_timeout');
		ini_set('default_socket_timeout', $timeout === null ? static::$timeout : max(1, $timeout));
	}

	protected static function __resetTimeout()
	{
		ini_set('default_socket_timeout', static::$_oldTimeout);
	}

	/**
	 * Fetch a url and return the response object
	 *
	 * @param string $url
	 * @param number $timeout
	 * @return \App\Http\Fetch
	 */
	public static function url($url, $timeout = null)
	{
		$obj = new self();
		$obj->setRequest($url);
		$response = null;
		static::__applyTimeout($timeout);
		try {
			$response = file_get_contents($url);
		}
		catch (\Exception $ex)
		{
			$obj->addError($ex->getMessage());
		}
		static::__resetTimeout();
		$obj->setResponse($response);
		$obj->setResult($response);
		return $obj;
	}

	/**
	 * Same as url, but the result is parsed from json
	 *
	 * @param string $url
	 * @param boolean $assoc
	 * @param number $timeout
	 * @return \App\Http\Fetch
	 */
	public static function urlJson($url, $assoc = false, $timeout = null)
	{
		$obj = static::url($url, $timeout);
		if ($obj->hasErrors())
			return $obj;
		$obj->setResult(@json_decode($obj->getResult(), $assoc));
		return $obj;
	}

	/**
	 * Same as url, but the result is parsed from xml string
	 *
	 * @param string $url
	 * @param boolean $assoc
	 * @param number $timeout
	 * @return \App\Http\Fetch
	 */
	public static function urlXml($url, $assoc = false, $timeout = null)
	{
		$obj = static::url($url, $timeout);
		if ($obj->hasErrors())
			return $obj;
		$obj->setResult(@json_decode(@json_encode(@simplexml_load_string($obj->getResult())), $assoc));
		return $obj;
	}

	/**
	 * Fetch a url by post and return the response object
	 *
	 * @param string $url
	 * @param [] $post
	 * @param string $timeout
	 * @return \App\Http\Fetch
	 */
	public static function post($url, $post = [], $timeout = null)
	{
		$obj = new self();
		$obj->setRequest(['url' => $url, 'post' => $post]);
		$response = null;
		static::__applyTimeout($timeout);
		try {
			$response = file_get_contents($url, false, stream_context_create([
				'http' => [
					'method' => 'POST',
					'header' => 'Content-type: application/x-www-form-urlencoded',
					'content' => http_build_query($post),
				]
			]));
		}
		catch (\Exception $ex)
		{
			$obj->addError($ex->getMessage());
		}
		static::__resetTimeout();
		$obj->setResponse($response);
		$obj->setResult($response);
		return $obj;
	}


	/**
	 * Fetch a url by post with header and return the response object
	 *
	 * @param string $url
	 * @param [] $post
	 * @param string $timeout
	 * @return \App\Http\Fetch
	 */
	public static function postWithHeaders($url, $post = [], $header = [], $timeout = null, $isPostArray = true)
	{
		$obj = new self();
		$obj->setRequest(['url' => $url, 'post' => $post]);
		$response = null;

		$timeout = $timeout ?: 30;
		static::__applyTimeout($timeout);

		$post = $isPostArray?http_build_query($post):$post;

		$headers = [];
		$headers[] = $header;
		if(!$isPostArray)
		{
			$headers[] = 'Content-Type: text/plain';
		}

		try {
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			$response = curl_exec($curl);
			$code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
			curl_close($curl);
			//$obj->addError($post);return $obj;

			$bom = pack('H*','EFBBBF');
			$response = preg_replace("/^$bom/", '', $response);
		}
		catch (\Exception $ex)
		{
			$obj->addError($ex->getMessage());
		}
		static::__resetTimeout();
		$obj->setResponse($response);
		$obj->setResult($response);
		return $obj;
	}

	public static function postWithHeadersUserPwd($url, $post = [], $header = [], $timeout = null, $isPostArray = true)
	{
		$obj = new self();
		$obj->setRequest(['url' => $url, 'post' => $post]);
		$response = null;
		static::__applyTimeout($timeout);

		$post = http_build_query($post);


		try {
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_USERPWD, 'Payment_Web_Lentes:RvRjUbV96ZnnokxON07U8dJhsf5CE9gm');
			curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
			$response = curl_exec($curl);
			$code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
			curl_close($curl);

		}
		catch (\Exception $ex)
		{
			$obj->addError($ex->getMessage());
		}
		static::__resetTimeout();
		$obj->setResponse($response);
		$obj->setResult($response);
		return $obj;
	}

	/**
	 * Fetch a url by post with header and return the response object
	 *
	 * @param string $url
	 * @param [] $post
	 * @param string $timeout
	 * @return \App\Http\Fetch
	 */
	public static function getWithHeaders($url, $header = [], $method = 'GET', $timeout = null)
	{
		$obj = new self();
		$obj->setRequest(['url' => $url]);
		$response = null;
		static::__applyTimeout($timeout);
		try {
			$response = file_get_contents($url, false, stream_context_create([
				'http' => [
					'method' => $method,
					'header' => $header,
				],
				"ssl"=>[
					"allow_self_signed"	=>	true,
					"verify_peer"		=>	false,
					"verify_peer_name"	=> 	false,
				],
			]));

			$bom = pack('H*','EFBBBF');
			$response = preg_replace("/^$bom/", '', $response);
		}
		catch (\Exception $ex)
		{
			$obj->addError($ex->getMessage());
		}
		static::__resetTimeout();
		$obj->setResponse($response);
		$obj->setResult($response);
		return $obj;
	}

	/**
	 * Same as post, but the result is parsed from json
	 *
	 * @param string $url
	 * @param boolean $assoc
	 * @param number $timeout
	 * @return \App\Http\Fetch
	 */
	public static function postJson($url, $post = [], $assoc = false, $timeout = null)
	{
		$obj = static::post($url, $post, $timeout);
		if ($obj->hasErrors())
			return $obj;
		$obj->setResult(@json_decode($obj->getResult(), $assoc));
		return $obj;
	}

	/**
	 * Do a fake soap Call to mirror service.
	 *
	 * @param string|[] $wsdl Url or [url, 'setting1'=>'value1', ...]
	 * @param string $methodName
	 * @param array $params
	 * @param boolean $assoc
	 * @param number $timeout
	 * @param \SoapHeader[] $headers
	 * @return \App\Http\Fetch
	 */
	public static function fakeSoap($wsdl, $methodName, $params = [],
	                                $assoc = false, $timeout = null, $headers = null)
	{
		$obj = new self();
		static::__applyTimeout($timeout);
		try {
			$url = 'http://52.88.164.110/fetch.php';
			$post = [
				'wsdl' => $wsdl,
				'methodName' => $methodName,
				'params' => $params,
				'assoc' => $assoc,
				'timeout' => $timeout,
				'headers' => $headers,
			];

			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post));
			$response = curl_exec($curl);
			$code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
			curl_close($curl);

			$response = json_decode($response, true);

			$obj->setResponse($response['response']);
			$obj->setRequest($response['request']);
			$obj->setResult($response['result']);
			if( $response['hasErrors'] )
			{
				foreach($response['errors'] as $errors)
				{
					$obj->addError($errors);
				}
			}

		}catch(\Exception $e)
		{
			$obj->addError($e->getMessage());
		}

		return $obj;
	}

	/**
	 * Do a soap Call.
	 *
	 * @param string|[] $wsdl Url or [url, 'setting1'=>'value1', ...]
	 * @param string $methodName
	 * @param array $params
	 * @param boolean $assoc
	 * @param number $timeout
	 * @param \SoapHeader[] $headers
	 * @return \App\Http\Fetch
	 */
	public static function soap($wsdl, $methodName, $params = [],
	                            $assoc = false, $timeout = null, $headers = null)
	{
		// Wsdl should be an array with first the url and parameters next
		if (is_string($wsdl))
			$wsdl = [$wsdl];
		$url = array_shift($wsdl);
		$wsdl['exceptions'] = true;

		if ($params === null || $params === false)
			$params = [];

		// Prepare result
		$obj = new self();
		$obj->setRequest([
			'url' => $url,
			//'wsdlParams' => $wsdl,
			'methodName' => $methodName,
			'params' => $params,
			//'headers' => $headers,
		]);
		$response = null;
		static::__applyTimeout($timeout);
		try {
			$soapClient = @(new \SoapClient($url, $wsdl));
			if (!empty($headers))
				$soapClient->__setSoapHeaders($headers);
			$response = $soapClient->__soapCall($methodName, ['parameters' => $params]);
		}
		catch (\Exception $ex)
		{
			$obj->addError($ex->getMessage());
		}
		static::__resetTimeout();
		$obj->setResponse($response);

		// If errors on request then just return the object
		if ($obj->hasErrors())
		{
			return $obj;
		}

		// Try to parse the result
		try {
			$response = $obj->getResponse();
			$obj->setResult(@json_decode(@json_encode($response), $assoc));
		}
		catch (\Exception $ex) {
			$obj->addError($ex->getMessage());
		}
		return $obj;
	}

	public static function nusoap($url)
	{
		return json_encode(static::getUrl($url));
	}
}