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
		$action = isset($arguments['__action_name__']) ? $arguments['__action_name__'] : 'actionDefault';
		if (!method_exists($this, $action)) {
			throw new \RuntimeException("Method {$arguments['__action_name__']} not found in " . get_class($this));
		}
		return $this->$action(isset($arguments['params']) ? $arguments['params'] : []);
	}

	protected function actionDefault(array $params = []) { }
}