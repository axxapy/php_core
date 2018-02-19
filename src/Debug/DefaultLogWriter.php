<?php namespace axxapy\Debug;

use axxapy\Interfaces\WriteStream;
use axxapy\Streams\ErrorLogStream;
use axxapy\Streams\FileWriteStream;

class DefaultLogWriter implements LogWriterInterface {
	/** @var \axxapy\Interfaces\Stream */
	private $Stream_standard;

	/** @var \axxapy\Interfaces\Stream */
	private $Stream_error;

	private static $stdout = [
		Log::INFO,
		Log::VERBOSE,
	];

	private static $stderr = [
		Log::DEBUG,
		Log::ERROR,
		Log::WARNING,
		Log::WTF,
	];

	public function write(int $level, string $tag, string $msg, \Throwable $ex = null) : bool {
		$str = sprintf("[%s] [%s] [%s] %s", date('Y-m-d_H:i:s'), Log::$level_names[$level], $tag, $msg);
		if ($ex) {
			$str .= (empty($msg) ? '' : "\n") . (string)$ex;
		}

		if (in_array($level, self::$stdout)) {
			$this->getStreamStandard()->write($str . PHP_EOL);
		}

		if (in_array($level, self::$stderr)) {
			$this->getStreamError()->write($str . PHP_EOL);
		}

		return false;
	}

	private function getStreamStandard() {
		if ($this->Stream_standard) {
			return $this->Stream_standard;
		}
		if (PHP_SAPI == 'cli') {
			$this->Stream_standard = new FileWriteStream(STDOUT);
		} else {
			$this->Stream_standard = new ErrorLogStream();
		}
		return $this->Stream_standard;
	}

	private function getStreamError() {
		if ($this->Stream_error) {
			return $this->Stream_error;
		}
		if (PHP_SAPI == 'cli') {
			$this->Stream_error = new FileWriteStream(STDERR);
		} else {
			$this->Stream_error = new ErrorLogStream();
		}
		return $this->Stream_error;
	}

	/**
	 * @param WriteStream $Stream
	 * @return $this
	 */
	public function setStreamStandard(WriteStream $Stream) {
		$this->Stream_standard = $Stream;
		return $this;
	}

	/**
	 * @param WriteStream $Stream
	 * @return $this
	 */
	public function setStreamError(WriteStream $Stream) {
		$this->$Stream_error = $Stream;
		return $this;
	}
}
