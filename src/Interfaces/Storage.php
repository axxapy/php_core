<?php namespace axxapy\Interfaces;

interface Storage {
	/**
	 * @param string     $key
	 * @param mixed|null $default
	 *
	 * @return mixed
	 */
	public function get($key, $default = null);

	/**
	 * @param string $key
	 *
	 * @return $this
	 */
	public function del($key);

	/**
	 * @param string $key
	 * @param mixed  $val
	 *
	 * @return $this
	 */
	public function set($key, $val);

	/**
	 * @param array $data
	 *
	 * @return $this
	 */
	public function setMulti(array $data);

	/**
	 * @return $this
	 */
	public function destroy();

	/**
	 * @return $this
	 */
	public function commit();
}