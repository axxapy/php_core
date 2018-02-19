<?php namespace axxapy\Debug;

interface LogWriterInterface {
	/**
	 * @param int             $level
	 * @param string          $tag
	 * @param string          $msg
	 * @param \Throwable|null $ex
	 *
	 * @return bool return true to prevent other log writers from processing this log
	 */
	public function write(int $level, string $tag, string $msg, \Throwable $ex = null): bool;
}
