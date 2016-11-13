<?php namespace axxapy;

use axxapy\Interfaces\Renderable;
use axxapy\Interfaces\WriteStream;

abstract class View implements Renderable {
	protected $Context;

	public function __construct(Context $Context) {
		$this->Context = $Context;
	}

	protected function getContext() {
		return $this->Context;
	}

	public function render(WriteStream $Stream) {
		$Stream->write($this->fetch());
	}

	public function __toString() {
		return (string)$this->fetch();
	}

	/**
	 * Returns view content.
	 * @return Renderable
	 */
	abstract public function fetch();
}
