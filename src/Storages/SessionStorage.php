<?php namespace axxapy\Storages;

use axxapy\Interfaces\Storage;
use axxapy\Debug\Log;

class SessionStorage implements Storage {
	const TAG = 'SESSION';

	private $id;
	private $lifetime;

	public function __construct($session_id = null, $lifetime = 0) {
		if ($session_id) {
			$this->id = $session_id;
		}
		$this->lifetime = $lifetime;
	}

	public function isStarted() {
		return session_status() === PHP_SESSION_ACTIVE;
	}

	public function Close() {
		if (!$this->isStarted()) {
			Log::w(self::TAG, 'Close: session not started');
			return;
		}
		#$this->Commit();
		#session_abort();
		session_write_close();
	}

	public function destroy() {
		if (!$this->isStarted()) {
			$this->start();
			Log::v(self::TAG, 'Destroy: had to start session to destroy');
		}
		session_destroy();
	}

	public function getId() {
		return $this->id ? $this->id : ($this->isStarted() ? session_id() : null);
	}

	public function setId($id) {
		$this->id = $id;
		if ($this->isStarted() && $this->id != session_id()) {
			$this->Close();
		}
	}

	public function set($key, $val, $ttl = 604800) {
		$this->start();
		$_SESSION[$key] = $val;
		return $this;
	}

	public function get($key, $default = null) {
		$this->start();
		return isset($_SESSION[$key]) ? $_SESSION[$key] : $default;
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
		$value = (int)$this->get($key, 0);
		$this->set($key, $value + $step);
		return true;
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
		$value = (int)$this->get($key, 0);
		$this->set($key, $value - $step);
		return true;
	}

	public function del($key) {
		$this->start();
		unset($_SESSION[$key]);
		return $this;
	}

	/**
	 * @param array $data
	 *
	 * @param       $ttl
	 *
	 * @return $this
	 */
	public function setMulti(array $data, $ttl = 604800) {
		foreach ($data as $key => $val) {
			$this->set($key, $val, $ttl);
		}
		return $this;
	}

	public function isKeyExist($key) {
		$this->start();
		return array_key_exists($key, $_SESSION);
	}

	/**
	 * @return $this
	 */
	public function commit() {
		if (!$this->isStarted()) return $this;
		session_commit();
		return $this;
	}

	private function start() {
		if ($this->isStarted()) {
			if (!$this->id || $this->id == session_id()) {
				return;
			}
			$this->Close();
		}
		if ($this->id) {
			session_id($this->id);
		}
		session_set_cookie_params($this->lifetime);
		session_start();
	}
}
