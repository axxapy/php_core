<?php namespace axxapy\Interfaces;

interface Storage {
	/**
	 * @param string     $key
	 * @param mixed|null $default
	 *
	 * @return mixed
	 */
	public function Get($key, $default = null);

	/**
	 * @param string $key
	 *
	 * @return $this
	 */
	public function Del($key);

	/**
	 * @param string $key
	 * @param mixed  $val
	 *
	 * @return $this
	 */
	public function Set($key, $val);

	/**
	 * @param array $data
	 *
	 * @return $this
	 */
	public function SetMulti(array $data);

	/**
	 * @return $this
	 */
	public function Destroy();

	/**
	 * @return $this
	 */
	public function Commit();
}