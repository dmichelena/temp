<?php

namespace App\Http\Base;

use App\Http\Request;

class ApiController extends Controller
{
	/**
	 * @var int
	 */
	protected $statusCode = 200;

	/**
	 * @var Request
	 */
	protected $request;

	/**
	 * ApiController constructor.
	 * @param Request $request
	 */
	public function __construct(Request $request)
	{
		$this->request = $request;
	}

}
