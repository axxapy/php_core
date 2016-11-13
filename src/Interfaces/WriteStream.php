<?php namespace axxapy\Interfaces;

interface WriteStream extends Stream {
	public function write($str);
}
