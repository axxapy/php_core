<?php namespace axxapy\Renderers;

use axxapy\Debug\Log;
use axxapy\Interfaces\Renderable;
use axxapy\Interfaces\WriteStream;

class StaticRenderer implements Renderable {
	const TYPE_JS  = 1;
	const TYPE_CSS = 2;

	const CONTENT_TYPE_CSS        = 'text/css';
	const CONTENT_TYPE_JAVASCRIPT = 'text/javascript';

	/** @var string */
	private $base_dir;

	/** @var string */
	private $target_file;

	private $replaces = [];

	private $includes = [];

	public function __construct(string $base_dir, string $target_file) {
		$this->base_dir = $base_dir;
		$this->target_file = $target_file;
	}

	public function addReplaces(array $replaces): self {
		$this->replaces += $replaces;
		return $this;
	}

	public function addIncludeDir(string $name, string $path): self {
		$this->includes[$name] = $path;
		return $this;
	}

	public function render(WriteStream $Stream) {
		switch ($this->getFileType()) {
			case self::TYPE_JS:
				$body     = $this->fetchFile($this->base_dir, $this->target_file);
				$Stream->write($this->cleanJs($body));
				break;

			case self::TYPE_CSS:
				$body     = $this->fetchFile($this->base_dir, $this->target_file);
				$Stream->write($this->cleanCss($body));
				break;

			default:
				Log::e(__CLASS__, 'wrong file: ' . $this->target_file);
		}
	}

	private function getFileType() {
		if (!$this->target_file) return null;
		$ext = pathinfo($this->target_file, PATHINFO_EXTENSION);
		switch ($ext) {
			case 'js':
				return self::TYPE_JS;

			case 'css':
				return self::TYPE_CSS;
		}
		return null;
	}

	private function fetchFile($base_dir, $file_path) {
		//load js file
		$body = file_get_contents($base_dir . '/' . $file_path);
		if (!$body) return false;
		$body = $this->cleanJs($body);
		$body = $this->processReplaces($body);
		$body = $this->processCallbacks($body);
		$body = $this->processIncludes($base_dir, $body);
		return $body;
	}

	private function processCallbacks($body) {
		$p = '#{{call\((?P<callback>[^\)]+)\)}}#';
		while (preg_match($p, $body, $matches)) {
			if (empty($matches['callback'])) continue;
			$result = call_user_func($matches['callback']);
			$body = str_replace($matches[0], $result, $body);
		}

		return $body;
	}

	private function processReplaces($body) {
		return str_replace(array_keys($this->replaces), array_values($this->replaces), $body);
	}

	private function processIncludes($base_dir, $body) {
		$p = '#//@include\s+file\s*=[\s\'"]*(?P<file>[@:a-zA-Z0-9\.\-_\/]+)[\s\'"]*\n?#i';
		while (preg_match($p, $body, $matches)) {
			if (empty($matches['file'])) continue;
			$file = $matches['file'];
			if (preg_match('#^@(?P<alias>[^:]+):(?P<file>[^\n]+)#', $file, $matches1)) {//example: @include file=@alias:file.js
				if (isset($this->includes[$matches1['alias']])) {
					$base_dir = $this->includes[$matches1['alias']];
					$file = $matches1['file'];
				}
			}
			$file = $this->fetchFile($base_dir, $file);
			$body = str_replace($matches[0], $file . PHP_EOL, $body);
		}
		return $body;
	}

	private function cleanCss($body) {
		$patterns = [
			"#\r#"       => '', //\r possible can be removed by \n filter (next one)
			'#\s?\n\s?#' => '',
			'#\s?{\s?#'  => '{',
			'#\s?}\s?#'  => '}',
		];
		foreach ($patterns as $pattern => $replace) {
			$body = preg_replace($pattern, $replace, $body);
		}
		return $body;
	}

	private function cleanJs($body) {
		$patterns = [
			"#\r#"               => '',
			//'#\s?{\s?#' => '{',
			//'#\s?}\s?#' => '}',
		];
		//remove comments and lines with ';;;' on non-devel environment
		if (!Log::isLoggable(__CLASS__, Log::DEBUG)) {
			$patterns += [
				'#^(\s{0,}//[^\n@]*)\n#' => "\n",
				'#(\s{0,};;;[^\n]*)\n#' => "\n",
			];
		}
		foreach ($patterns as $pattern => $replace) {
			$body = preg_replace($pattern, $replace, $body);
		}
		return $body;
	}
}
