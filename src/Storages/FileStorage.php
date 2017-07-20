<?php namespace axxapy\Storages;

use axxapy\Interfaces\Storage;

class FileStorage implements Storage {
	private $filename;
	private $auto_commit;
	private $data = [];

	private $changed = false;

	public function __construct($filename, $auto_commit = false) {
		$this->auto_commit = (bool)$auto_commit;
		$this->filename    = (string)$filename;
		$this->Reload();
	}

	public function Reload() {
		$data              = file_get_contents($this->filename);
		$this->data        = json_decode($data, true);
		if (!$this->data) $this->data = [];
	}

	/**
	 * @param string     $key
	 * @param mixed|null $default
	 *
	 * @return mixed
	 */
	public function get($key, $default = null) {
		return array_key_exists($key, $this->data) ? $this->data[$key] : $default;
	}

	/**
	 * @param string $key
	 *
	 * @return $this
	 */
	public function del($key) {
		if (!array_key_exists($key, $this->data)) return $this;
		unset($this->data[$key]);
		$this->changed = true;
		if ($this->auto_commit) $this->Commit();
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
		if (array_key_exists($key, $this->data) && $this->data[$key] == $val) return $this;
		$this->data[$key] = $val;
		$this->changed = true;
		if ($this->auto_commit) $this->Commit();
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
		$this->set($key, (int)$this->get($key, 0) + $step);
		$this->changed = true;
		if ($this->auto_commit) $this->Commit();
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
		$this->set($key, (int)$this->get($key, 0) - $step);
		$this->changed = true;
		if ($this->auto_commit) $this->Commit();
		return true;
	}

	/**
	 * @param array $data
	 *
	 * @param int   $ttl
	 *
	 * @return $this
	 */
	public function setMulti(array $data, $ttl = 604800) {
		foreach ($data as $key => $val) {
			$this->set($key, $val, $ttl);
		}
	}

	/**
	 * @return $this
	 */
	public function Destroy() {
		$this->data = [];
		$this->changed = false;
		unlink($this->filename);
		return $this;
	}

	/**
	 * @return $this
	 */
	public function Commit() {
		if (!$this->changed) return $this;
		file_put_contents($this->filename, json_encode($this->data));
		$this->changed = false;
		return $this;
	}
}
