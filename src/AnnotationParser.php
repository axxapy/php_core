<?php namespace axxapy;

use RuntimeException;

class AnnotationParser {
	private $annotations = [];
	private $comments    = [];

	public static function parseMethod($class, $method): self {
		return new self((new \ReflectionClass($class))->getMethod($method)->getDocComment());
	}

	public static function parseClass($class): self {
		return new self((new \ReflectionClass($class))->getDocComment());
	}

	public static function parseFunction(string $function): self {
		return new self((new \ReflectionFunction($function))->getDocComment());
	}

	public static function parseReflector(\Reflector $Reflector) {
		if (!method_exists($Reflector, 'getDocComment')) {
			throw new RuntimeException('$Reflector does not have getDocComment method');
		}
		return new self($Reflector->getDocComment());
	}

	private function __construct($doc) {
		if (preg_match_all('#\n\s*\*\s*@(?P<name>[^\s]*)\s{0,}(?P<value>[^\n]*)#s', $doc, $matches)) {
			foreach ($matches['name'] as $num => $name) {
				$value = isset($matches['value'][$num]) ? $matches['value'][$num] : null;
				if (!isset($this->annotations[$name])) {
					$this->annotations[$name] = $value;
				} else {
					$this->annotations[$name] .= "\n" . $value;
				}
			}
		}

		if (preg_match_all('#\n\s*\*\s*[^@\n]+#s', $doc, $matches)) {
			foreach ($matches[0] as $str) {
				$lines = explode("\n", $str);
				foreach ($lines as $line) {
					$line = str_replace(['/*', '*/'], '', $line);
					$line = trim(preg_replace('#\s*\*\s*#', '', $line));
					if (!$line) continue;
					$this->comments[] = $line;
				}
			}
		}
	}

	public function getAnnotation($name, $default = ''): string {
		return array_key_exists($name, $this->annotations) ? $this->annotations[$name] : $default;
	}

	public function getComments(): array {
		return $this->comments;
	}
}
