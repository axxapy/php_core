<?php namespace axxapy;

use axxapy\Core\ErrorHandler;
use axxapy\Debug\Log;
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
		try {
			return (string)$this->fetch();
		} catch (\Throwable $e) {
			Log::e(__CLASS__, $e->getMessage(), $e);
			if (Log::isLoggable(__CLASS__, Log::DEBUG)) {
				return (new ErrorHandler(true))->setOutputFormat(ErrorHandler::OUTPUT_FORMAT_HTML)->formatException($e);
			}
		}
		return null;
	}

	/**
	 * Returns view content.
	 * @return Renderable
	 */
	abstract public function fetch();
}
