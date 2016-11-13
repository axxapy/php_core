<?php namespace axxapy\Streams;

use axxapy\Interfaces\WriteStream;

class StdoutStream implements WriteStream  {
	public function close() {}

	public function write($str) {
		echo $str;
	}
}