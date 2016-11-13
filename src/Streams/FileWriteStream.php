<?php namespace axxapy\Streams;

use axxapy\Interfaces\WriteStream;
use InvalidArgumentException;

class FileWriteStream implements WriteStream {
	private $fp;

	public function __construct($fp) {
		if (!is_resource($fp)) {
			throw new InvalidArgumentException('$fp must be pointer to opened file');
		}
		$this->fp = $fp;
	}

	public function close() {
		fclose($this->fp);
	}

	public function write($str) {
		fwrite($this->fp, $str);
	}
}
