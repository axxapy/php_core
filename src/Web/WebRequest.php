<?php namespace axxapy\Web;

use axxapy\Debug\Log;

class WebRequest {
	const INPUT_RAW = 7;

	private $raw_data = [];
	private $raw_post_body;

	public function __construct(array $raw_data = []) {
		$this->raw_data = $raw_data;
	}

	private function checkSource($src) {
		$valid_sourses = [INPUT_GET, INPUT_POST, INPUT_COOKIE, INPUT_ENV, INPUT_REQUEST, INPUT_SESSION, INPUT_SERVER, self::INPUT_RAW];
		if (!in_array($src, $valid_sourses)) {
			Log::e(__CLASS__, 'unknown source: ' . var_export($src, true));
			return false;
		}
		return true;
	}

	private function filter($source, $name, $filter, $default) {
		if (!$this->checkSource($source)) return $default;
		if ($source == self::INPUT_RAW) {
			if (!isset($this->raw_data[$name])) return $default;
			return filter_var($this->raw_data[$name], $filter);
		}
		$res = filter_input($source, $name, $filter);
		return $res == false ? $default : $res;
	}

	public function setRawData(array $raw) {
		//store raw request for kibana
		$_REQUEST['__RAW__'] = $raw;

		$this->raw_data = $raw;
		return $this;
	}

	public function getRawData() {
		return $this->raw_data;
	}

	public function isPost() {
		return !empty($_SERVER['REQUEST_METHOD']) && strtoupper($_SERVER['REQUEST_METHOD']) == 'POST';
	}

	/**
	 * @param int|array $src     INPUT_GET, INPUT_POST, INPUT_* constant from default php set
	 * @param string    $name    name of variable
	 * @param mixed     $default default value
	 *
	 * @return string|null
	 */
	public function getString($src, $name, $default = null) {
		if (!is_array($src)) {
			$src = [$src];
		}
		foreach ($src as $source) {
			$res = $this->filter($source, $name, FILTER_SANITIZE_STRING, $default);
			if ($res !== $default) {
				return (string)$res;
			}
		}
		return $default;
	}

	public function getFilteredString($src, $name, $allowed_chars, $default = null, $length = null) {
		$result = $this->getString($src, $name, $default);
		if ($result) {
			$result = preg_replace("/[^$allowed_chars]/imus", '', $result);
			if ($length) {
				$result = mb_substr($result, 0, $length);
			}
		}
		return $result;
	}

	/**
	 * @param int|array $src     INPUT_GET, INPUT_POST, INPUT_* constant from default php set
	 * @param string    $name    name of variable
	 * @param mixed     $default default value
	 *
	 * @return bool|null
	 */
	public function getBool($src, $name, $default = null) {
		if (!is_array($src)) {
			$src = [$src];
		}
		foreach ($src as $source) {
			$res = $this->filter($source, $name, FILTER_SANITIZE_STRING, $default);
			if ($res !== $default) {
				return (bool)$res;
			}
		}
		return $default;
	}

	/**
	 * @param int|array $src
	 * @param string    $name
	 * @param mixed     $default
	 *
	 * @return int|null
	 */
	public function getInt($src, $name, $default = null) {
		if (!is_array($src)) {
			$src = [$src];
		}
		foreach ($src as $source) {
			$res = $this->filter($source, $name, FILTER_VALIDATE_INT, $default);
			if ($res !== $default) {
				return (int)$res;
			}
		}
		return (int)$default;
	}

	public function getFloat($src, $name, $default = null) {
		if (!is_array($src)) {
			$src = [$src];
		}
		foreach ($src as $source) {
			$res = $this->filter($source, $name, FILTER_VALIDATE_FLOAT, $default);
			if ($res !== $default) {
				return (float)$res;
			}
		}
		return $default;
	}

	/**
	 * Usage example:
	 * If we have $_POST array like this:
	 * $_POST = [
	 *   'data' => [
	 *     1   => 10,
	 *     2   => 20,
	 *     'a' => 'A',
	 *   ]
	 * ]
	 * we can get 'data' subarray using this call:
	 * $Request->getArray(
	 *   INPUT_POST, [
	 *     'data' => [
	 *       'name'   => 'data',
	 *       'filter' => FILTER_SANITIZE_STRING,
	 *       'flags'  => FILTER_REQUIRE_ARRAY,
	 *     ]
	 *   ]
	 * );
	 *
	 * @param       $src
	 * @param       $name
	 * @param array $default
	 *
	 * @return array|mixed
	 */
	public function getArray($src, $name, $default = []) {
		if (!$this->checkSource($src)) return $default;
		if ($src == self::INPUT_RAW) {
			return isset($this->raw_data[$name]) ? $this->raw_data[$name] : $default;
		}
		$res = filter_input_array($src, $name, FILTER_FORCE_ARRAY);
		return $res !== false ? $res : $default;
	}

	public function isExists($src, $name) {
		if (!is_array($src)) {
			$src = [$src];
		}
		foreach ($src as $source) {
			$res = $source == self::INPUT_RAW ? isset($this->raw_data[$name]) : filter_has_var($source, $name);
			if ($res) {
				return true;
			}
		}
		return false;
	}

	public function setRawPostBody($raw_post_body) {
		$this->raw_post_body = $raw_post_body;
		return $this;
	}

	public function getRawPostBody() {
		if (is_null($this->raw_post_body)) {
			$this->raw_post_body = file_get_contents('php://input');
		}
		return $this->raw_post_body;
	}
}
