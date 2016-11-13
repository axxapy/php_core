<?php namespace axxapy;

class Config {
	private $config = [
		/*
		'dirs' => array(
			'dir_name' => '/tmp/dir_name',
		),
		'services' => array(
			'main_db' => array(
				'type' => 'mysql',
				...
			),
		),
		'routes' => array(),
		*/
	];

	private $runtime_values = [];

	public function __construct(array $config = []) {
		$this->config = $config;
	}

	public function getValue($path) {
		$parts  = strpos($path, '/') ? explode('/', $path) : func_get_args();
		$parts  = array_reverse($parts);
		$config = $this->config;
		do {
			$key = array_pop($parts);
			if (empty($key)) continue;
			if (!isset($config[$key]) || ($parts && !is_array($config[$key]))) return false;
			$config = $config[$key];
		} while ($parts);
		return $config;
	}

	public function setRuntimeValue($key, $val) {
		$this->runtime_values[$key] = $val;
		return $this;
	}

	public function getRuntimeValue($key, $default = null) {
		return isset($this->runtime_values[$key]) ? $this->runtime_values[$key] : $default;
	}

	/**
	 * @param string $section_name
	 *
	 * @return bool
	 */
	public function getSection($section_name) {
		return isset($this->config[$section_name]) ? $this->config[$section_name] : false;
	}

	public function mergeConfig(array $config) {
		$merge        = function ($conf1, $conf2) use (&$merge) {
			foreach ($conf2 as $key => $val) {
				if (is_array($val) && isset($conf1[$key]) && is_array($conf1[$key])) {
					$conf1[$key] = $merge($conf1[$key], $val);
				} else {
					$conf1[$key] = $val;
				}
			}
			return $conf1;
		};
		$this->config = $merge($this->config, $config);
	}
}