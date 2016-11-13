<?php namespace axxapy\Interfaces;

interface ReadStream extends Stream {
	public function read($count = null);
	public function readLn();
}
