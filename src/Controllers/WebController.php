<?php namespace axxapy\Controllers;

use axxapy\Controller;
use axxapy\Web\WebRequest;

class WebController extends Controller {
	/** @var WebRequest */
	private $Request;

	/**
	 * @return \axxapy\Web\WebRequest
	 */
	public function getRequest(): \axxapy\Web\WebRequest {
		if (!$this->Request) {
			$this->Request = new WebRequest();
		}
		return $this->Request;
	}
}
