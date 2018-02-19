<?php namespace axxapy\Debug;

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

	public static $level_names = [
		self::DEBUG   => 'D',
		self::ERROR   => 'E',
		self::INFO    => 'I',
		self::VERBOSE => 'V',
		self::WARNING => 'W',
		self::WTF     => 'WTF',
	];

	public static $level_names_full = [
		self::DEBUG   => 'DEBUG',
		self::ERROR   => 'ERROR',
		self::INFO    => 'INFO',
		self::VERBOSE => 'VERBOSE',
		self::WARNING => 'WARNING',
		self::WTF     => 'WTF',
	];

	private static $log_level;
	private static $log_level_tag = [];

	/** @var [\axxapy\Debug\LogWriterInterface => int] */
	private static $Writers = [];

	/** @var \axxapy\Debug\DefaultLogWriter */
	private static $WriterDefault;

	private static function getLogLevel($tag = null, $default = null) {
		if (!empty($tag) && isset(self::$log_level_tag[$tag])) {
			return self::$log_level_tag[$tag];
		}
		if ($default !== null) return $default;
		if (self::$log_level !== null) return self::$log_level;
		return self::ERROR | self::WARNING | self::WTF | self::INFO;
	}

	private static function _log($level, $tag, $msg, Throwable $ex = null) {
		foreach (self::$Writers as $item) {
			$Writer = $item[0]; $writer_level = $item[1];
			if (!self::isLoggable($tag, $level, $writer_level ? $writer_level : null)) continue;
			if ($Writer->write($level, $tag, $msg, $ex) === true) return; //stop processing other log writers
		}

		if (!self::isLoggable($tag, $level)) return;
		self::getDefaultLogWriter()->write($level, $tag, $msg, $ex);
	}

	public static function addLogWriter(LogWriterInterface $Writer, int $level = 0) {
		self::$Writers[] = [$Writer, $level];
	}

	public static function getDefaultLogWriter() : DefaultLogWriter {
		if (!self::$WriterDefault) {
			self::$WriterDefault = new DefaultLogWriter();
		}
		return self::$WriterDefault;
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
	 * @param int    $default_log_level
	 *
	 * @return bool
	 */
	public static function isLoggable($tag, $log_level, $default_log_level = null) {
		/*if (!isset(self::$level_names[$log_level])) {
			self::e(__CLASS__, 'Unknown log level: ' . var_export($log_level, true));
			return false;
		}*/
		return (bool)(self::getLogLevel($tag, $default_log_level) & $log_level);
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
