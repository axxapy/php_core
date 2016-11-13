<?php namespace axxapy\Interfaces;

interface ReadStream extends Stream {
	public function Read($count = null);
	public function ReadLn();
}
