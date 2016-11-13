<?php namespace axxapy\Renderers;

use axxapy\Context;
use axxapy\Debug\Log;
use axxapy\Interfaces\Renderable;

abstract class Renderer implements Renderable {
	protected $data = [];

	private $Context;

	public function __construct(Context $Context) {
		$this->Context = $Context;
	}

	/**
	 * Set one variable to data array
	 *
	 * @param string $key
	 * @param        $value
	 *
	 * @return $this
	 */
	public function set($key, $value) {
		$this->data[$key] = $value;
		return $this;
	}

	/**
	 * Set data array for template parsing
	 *
	 * @param array $data
	 *
	 * @return $this
	 */
	public function setData(array $data) {
		$this->data = $data;
		return $this;
	}

	public function appendData(array $data) {
		$this->data = array_merge_recursive($this->data, $data);
		return $this;
	}

	public function getContext() {
		return $this->Context;
	}

	/**
	 * Prefetches data. Walk through array and execute fetch if it is instance of a View
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	protected function prefetchData(array $data) {
		array_walk_recursive($data, function (&$value, $key) {
			if ($value instanceof self) {//@todo: should be instance of interface
				$value = $value->fetch();
			}
		});
		return $data;
	}

	public function __toString() {
		try {
			return $this->fetch();
		} catch (\Throwable $t) {
			Log::e(__CLASS__, $t->getMessage(), $t);
		}
		return null;
	}

	/**
	 * Fetches the template
	 *
	 * @return string
	 */
	abstract public function fetch();
}