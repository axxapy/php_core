<?php namespace axxapy\Storages;

use axxapy\Interfaces\Storage;

class SecondLevelStorage implements Storage {
	/** @var Storage */
	private $Storage;

	private $base_key;

	public function __construct(Storage $Storage, $base_key) {
		$this->Storage  = $Storage;
		$this->base_key = $base_key;
	}

	/**
	 * @param string     $key
	 * @param mixed|null $default
	 *
	 * @return mixed
	 */
	public function get($key, $default = null) {
		$data = $this->Storage->get($this->base_key, []);
		return is_array($data) && array_key_exists($key, $data) ? $data[$key] : $default;
	}

	/**
	 * @param string $key
	 *
	 * @return $this
	 */
	public function del($key) {
		$data = $this->Storage->get($this->base_key, []);
		unset($data[$key]);
		$this->Storage->set($this->base_key, $data);
		return $this;
	}

	/**
	 * @param string $key
	 * @param mixed  $val
	 *
	 * @param        $ttl
	 *
	 * @return $this
	 */
	public function set($key, $val, $ttl = 604800) {
		$data       = $this->Storage->get($this->base_key, []);
		$data[$key] = $val;
		$this->Storage->set($this->base_key, $data);
		return $this;
	}

	/**
	 * Increases key value to {$step}
	 *
	 * @param string $key
	 * @param int    $step
	 *
	 * @return bool
	 */
	public function inc($key, $step = 1) {
		return $this->set($key, (int)$this->get($key) + $step);
	}

	/**
	 * Decreases key value to {$step}
	 *
	 * @param string $key
	 * @param int    $step
	 *
	 * @return bool
	 */
	public function dec($key, $step = 1) {
		return $this->set($key, (int)$this->get($key) - $step);
	}

	/**
	 * @param array $data
	 *
	 * @param int   $ttl
	 *
	 * @return $this
	 */
	public function setMulti(array $data, $ttl = 604800) {
		foreach ($data as $key => $value) {
			$this->set($key, $value, $ttl);
		}
	}

	/**
	 * @return $this
	 */
	public function Destroy() {
		$this->Storage->del($this->base_key);
		return $this;
	}

	/**
	 * @return $this
	 */
	public function Commit() {
		$this->Storage->Commit();
		return $this;
	}
}
