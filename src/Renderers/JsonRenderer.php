<?php namespace axxapy\Renderers;

use axxapy\Interfaces\WriteStream;

/**
 * Renders Json, sends application/json header
 */
class Renderer_Json extends Renderer {
	private $pretty_print_enabled = false;
	private $check_errors         = false;
	private $force_utf8           = false;

	public function fetch() {
		$data = $this->prefetchData($this->data);
		if ($this->isForceUtf8()) {
			$data = $this->utf8ize($data);
		}
		if ($this->pretty_print_enabled) {
			$json = json_encode($data, JSON_PRETTY_PRINT);
		} else {
			$json = json_encode($data);
		}
		if ($this->isCheckErrors() && json_last_error() != JSON_ERROR_NONE) {
			trigger_error(json_last_error_msg());
		}
		return $json;
	}

	public function render(WriteStream $Stream) {
		header("Content-type: application/json");
		header("Date: " . date("c"));
		header("Cache-Control: no-cache, no-store, must-revalidate");
		header("Pragma: no-cache");
		header("Expires: 0");
		$Stream->write($this->fetch());
	}

	/**
	 * @param boolean $pretty_print_enabled
	 *
	 * @return $this
	 */
	public function setPrettyPrintEnabled($pretty_print_enabled) {
		$this->pretty_print_enabled = (boolean)$pretty_print_enabled;
		return $this;
	}

	/**
	 * @return boolean
	 */
	public function isCheckErrors() {
		return $this->check_errors;
	}

	/**
	 * @param boolean $check_errors
	 *
	 * @return $this
	 */
	public function setCheckErrors($check_errors) {
		$this->check_errors = $check_errors;
		return $this;
	}

	/**
	 * @return boolean
	 */
	public function isForceUtf8() {
		return $this->force_utf8;
	}

	/**
	 * @param boolean $force_utf8
	 *
	 * @return $this
	 */
	public function setForceUtf8($force_utf8) {
		$this->force_utf8 = $force_utf8;
		return $this;
	}

	/**
	 * @param array|string $mixed
	 *
	 * @return array
	 */
	private function utf8ize($mixed) {
		if (is_array($mixed)) {
			foreach ($mixed as $key => $value) {
				unset($mixed[$key]);
				$new_key         = utf8_encode($key);
				$mixed[$new_key] = self::utf8ize($value);
			}
		} else if (is_string($mixed)) {
			return utf8_encode($mixed);
		}
		return $mixed;
	}
}