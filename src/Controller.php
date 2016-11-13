<?php namespace axxapy;

use axxapy\Interfaces\Runnable;

class Controller implements Runnable {
	private $Context;

	public function __construct(Context $Context) {
		$this->Context = $Context;
	}

	protected function getContext() {
		return $this->Context;
	}

	public function run(array $arguments = []) {
		$action = isset($arguments['action']) ? $arguments['action'] : 'actionDefault';
		if (!method_exists($this, $action)) return false;
		return $this->actionDefault(isset($arguments['params']) ? $arguments['params'] : []);
	}

	protected function actionDefault(array $params = []) {}
}