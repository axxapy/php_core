<?php namespace axxapy\Streams;

use axxapy\Interfaces\WriteStream;

class ErrorLogStream implements WriteStream {
	public function write($str) {
		error_log($str);
	}

	public function close() {}
}
