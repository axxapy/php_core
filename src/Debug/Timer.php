<?php namespace axxapy\Debug;

class Timer {
	private $timer_id;
	private $method_name;
	private $group_name;
	private $user_data = array();

	public function __construct($method, $group = 'profiler::method') {
		$this->method_name = $method;
		$this->group_name = $group;
	}

	public function start() {
		if (!function_exists('pinba_timer_start')) return $this;
		if ($this->timer_id) return $this;
		$this->addData(array('pinba::memory' => memory_get_usage()));
		$this->timer_id = pinba_timer_start(
			array('group' => $this->group_name, 'method' => $this->method_name),
			$this->user_data
		);
		return $this;
	}

	public function stopWithResult($result) {
		return $this->stop(['pinba::success' => (bool)$result]);
	}

	public function stopWithSuccess() {
		return $this->stopWithResult(true);
	}

	public function stopWithFail() {
		return $this->stopWithResult(false);
	}

	public function stop(array $additional_data = []) {
		if (!function_exists('pinba_timer_stop')) return $this;
		if (!$this->timer_id) {
			Log::e(__CLASS__, 'Start timer before stopping it');
			return $this;
		}
		$this->addData($additional_data);
		pinba_timer_stop($this->timer_id);
		return $this;
	}

	public function addData($data) {
		$data = (array)$data;
		$this->user_data = array_merge($this->user_data, $data);
		if (!$this->timer_id) return $this;
		if (!function_exists('pinba_timer_data_merge')) return $this;
		pinba_timer_data_merge($this->timer_id, $data);
		return $this;
	}

	public function getData() {
		return $this->user_data;
	}

	public function getInfo() {
		if (!$this->timer_id) return 0;
		if (!function_exists('pinba_timer_get_info')) return 0;
		return pinba_timer_get_info($this->timer_id);
	}
}
