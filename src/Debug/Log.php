<?php namespace axxapy\Debug;

use axxapy\Interfaces\WriteStream;
use axxapy\Streams\ErrorLogStream;
use axxapy\Streams\FileWriteStream;
use Throwable;

/**
 * API for sending log output.
 * Generally, use the Log.v() Log.d() Log.i() Log.w() and Log.e() methods.
 *
 * The order in terms of verbosity, from least to most is ERROR, WARN, INFO, DEBUG, VERBOSE.
 * Verbose should never be compiled into an application except during development.
 * By default debug logs are compiled in but stripped at runtime. Error, warning and info logs are always kept.
 *
 * Tip: A good convention is to declare a TAG constant in your class:
 *     const TAG = 'MyClassTag';
 * and use that in subsequent calls to the log methods:
 *     Log.d(self::TAG, 'Message');
 */
class Log {
	const DEBUG   = 1;
	const ERROR   = 2;
	const INFO    = 4;
	const VERBOSE = 8;
	const WARNING = 16;
	const WTF     = 32;

	const LEVEL_ALL = 63; // self::DEBUG | self::ERROR | self::INFO | self::VERBOSE | self::WARNING | self::WTF

	private static $level_names = [
		self::DEBUG   => 'D',
		self::ERROR   => 'E',
		self::INFO    => 'I',
		self::VERBOSE => 'V',
		self::WARNING => 'W',
		self::WTF     => 'WTF',
	];

	private static $stdout = [
		self::INFO,
		self::VERBOSE,
	];

	private static $stderr = [
		self::DEBUG,
		self::ERROR,
		self::WARNING,
		self::WTF,
	];

	private static $log_level;
	private static $log_level_tag = [];

	/** @var \axxapy\Interfaces\Stream */
	private static $Stream_standard;

	/** @var \axxapy\Interfaces\Stream */
	private static $Stream_error;

	private static function getLogLevel($tag = null) {
		if (!empty($tag) && isset(self::$log_level_tag[$tag])) {
			return self::$log_level_tag[$tag];
		}
		if (self::$log_level !== null) {
			return self::$log_level;
		}
		return self::ERROR | self::WARNING | self::WTF | self::INFO;
	}

	private static function _log($level, $tag, $msg, Throwable $ex = null) {
		if (!self::isLoggable($tag, $level)) return;

		$str = sprintf("[%s] [%s] [%s] %s", date('Y-m-d_H:i:s'), self::$level_names[$level], $tag, $msg);
		if ($ex) {
			$str .= (empty($msg) ? '' : "\n") . (string)$ex;
		}

		if (in_array($level, self::$stdout)) {
			self::getStreamStandard()->write($str . PHP_EOL);
		}

		if (in_array($level, self::$stderr)) {
			self::getStreamError()->write($str . PHP_EOL);
		}
	}

	private static function getStreamStandard() {
		if (self::$Stream_standard) {
			return self::$Stream_standard;
		}
		if (PHP_SAPI == 'cli') {
			self::$Stream_standard = new FileWriteStream(STDOUT);
		} else {
			self::$Stream_standard = new ErrorLogStream();
		}
		return self::$Stream_standard;
	}

	private static function getStreamError() {
		if (self::$Stream_error) {
			return self::$Stream_error;
		}
		if (PHP_SAPI == 'cli') {
			self::$Stream_error = new FileWriteStream(STDERR);
		} else {
			self::$Stream_error = new ErrorLogStream();
		}
		return self::$Stream_error;
	}

	/**
	 * Sets log level. Global, if {$tag} is empty or for specific tag, if not.
	 *
	 * @param int         $log_level
	 * @param string|null $tag
	 */
	public static function setLogLevel($log_level, $tag = null) {
		if ($tag !== null) {
			self::$log_level_tag[$tag] = $log_level;
		} else {
			self::$log_level = $log_level;
		}
	}

	/**
	 * Checks to see whether or not a log for the specified tag is loggable at the specified level.
	 *
	 * @param string $tag
	 * @param int    $log_level
	 *
	 * @return bool
	 */
	public static function isLoggable($tag, $log_level) {
		if (!isset(self::$level_names[$log_level])) {
			self::e(__CLASS__, 'Unknown log level: ' . var_export($log_level, true));
			return false;
		}
		return (bool)(self::getLogLevel($tag) & $log_level);
	}

	/**
	 * @param WriteStream $Stream
	 */
	public static function setStreamStandard(WriteStream $Stream) {
		self::$Stream_standard = $Stream;
	}

	/**
	 * @param WriteStream $Stream
	 */
	public static function setStreamError(WriteStream $Stream) {
		self::$Stream_error = $Stream;
	}

	/**
	 * Send a DEBUG log message and log the exception, if present.
	 *
	 * @param string     $tag
	 * @param string     $msg
	 * @param \Throwable $ex
	 */
	public static function d($tag, $msg, Throwable $ex = null) {
		self::_log(self::DEBUG, $tag, $msg, $ex);
	}

	/**
	 * Send a ERROR log message and log the exception, if present.
	 *
	 * @param string     $tag
	 * @param string     $msg
	 * @param \Throwable $ex
	 */
	public static function e($tag, $msg, Throwable $ex = null) {
		self::_log(self::ERROR, $tag, $msg, $ex);
	}

	/**
	 * Send a INFO log message and log the exception, if present.
	 *
	 * @param string     $tag
	 * @param string     $msg
	 * @param \Throwable $ex
	 */
	public static function i($tag, $msg, Throwable $ex = null) {
		self::_log(self::INFO, $tag, $msg, $ex);
	}

	/**
	 * Send a VERBOSE log message and log the exception, if present.
	 *
	 * @param string     $tag
	 * @param string     $msg
	 * @param \Throwable $ex
	 */
	public static function v($tag, $msg, Throwable $ex = null) {
		self::_log(self::VERBOSE, $tag, $msg, $ex);
	}

	/**
	 * Send a WARNING log message and log the exception, if present.
	 *
	 * @param string     $tag
	 * @param string     $msg
	 * @param \Throwable $ex
	 */
	public static function w($tag, $msg, Throwable $ex = null) {
		self::_log(self::WARNING, $tag, $msg, $ex);
	}

	/**
	 * What a Terrible Failure: Report a condition that should never happen.
	 *
	 * @param string     $tag
	 * @param string     $msg
	 * @param \Throwable $ex
	 */
	public static function wtf($tag, $msg, Throwable $ex = null) {
		self::_log(self::WTF, $tag, $msg, $ex);
	}
}
