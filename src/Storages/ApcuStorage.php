<?php namespace axxapy\Storages;

use axxapy\Interfaces\Storage;
use RuntimeException;

final class ApcuStorage implements Storage {
	public function __construct() {
		if (!extension_loaded('apcu')) {
			throw new RuntimeException('apcu extension not loaded');
		}
		$enable_key = PHP_SAPI == 'cli' ? 'apc.enable_cli' : 'apc.enabled';
		if (!(bool)ini_get($enable_key)) {
			throw new RuntimeException('apcu is disabled for ' . PHP_SAPI);
		}
	}

	/**
	 * @param string     $key
	 * @param mixed|null $default
	 *
	 * @return mixed
	 */
	public function get($key, $default = null) {
		$success = false;
		if (function_exists('apc_fetch')) {
			$value = apc_fetch($key, $success);
		} elseif (function_exists('apcu_fetch')) {
			$value = apcu_fetch($key, $success);
		} else {
			trigger_error('no apc-compatible extension found', E_USER_WARNING);
		}
		return $success ? $value : $default;
	}

	/**
	 * @param string $key
	 *
	 * @return $this
	 */
	public function del($key) {
		if (function_exists('apc_delete')) {
			apc_delete($key);
		} elseif (function_exists('apcu_delete')) {
			apcu_delete($key);
		} else {
			trigger_error('no apc-compatible extension found', E_USER_WARNING);
		}
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
		if (function_exists('apc_store')) {
			apc_store($key, $val, $ttl);
		} elseif (function_exists('apcu_store')) {
			apcu_store($key, $val, $ttl);
		} else {
			trigger_error('no apc-compatible extension found', E_USER_WARNING);
		}
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
		if (function_exists('apc_inc')) {
			$result = apc_inc($key, $step);
		} elseif (function_exists('apcu_inc')) {
			$result = apcu_inc($key, $step);
		} else {
			trigger_error('no apc-compatible extension found', E_USER_WARNING);
			$result = false;
		}
		return $result !== false;
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
		if (function_exists('apc_inc')) {
			$result = apc_dec($key, $step);
		} elseif (function_exists('apcu_inc')) {
			$result = apcu_dec($key, $step);
		} else {
			trigger_error('no apc-compatible extension found', E_USER_WARNING);
			$result = false;
		}
		return $result !== false;
	}

	/**
	 * @param array $data
	 *
	 * @param       $ttl
	 *
	 * @return $this
	 */
	public function setMulti(array $data, $ttl = 604800) {
		foreach ($data as $key => $value) {
			$this->set($key, $value, $ttl);
		}
		return $this;
	}

	/**
	 * @return $this
	 */
	public function Destroy() {
		if (function_exists('apc_clear_cache')) {
			apc_clear_cache('user');
		} elseif (function_exists('apcu_clear_cache')) {
			apcu_clear_cache('user');
		} else {
			trigger_error('no apc-compatible extension found', E_USER_WARNING);
		}
		return $this;
	}

	/**
	 * @return $this
	 */
	public function Commit() {
		return $this;
	}
}
