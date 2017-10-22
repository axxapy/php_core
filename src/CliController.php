<?php namespace axxapy;

use InvalidArgumentException;
use Throwable;

class CliController extends Controller {
	protected function actionDefault(array $params = []) {
		$argc = $_SERVER['argc'];
		$argv = $_SERVER['argv'];

		$class = get_class($this);
		if ($argv[1] == $class || $argv[1] == '\\' . $class) { //command was launched as 'run.php \Class\Name'
			$usage_start = implode(' ', array_slice($argv, 0, 2));
			$argv = array_slice($argv, 2);
		} else { // command was launched as standalone script.
			$usage_start = array_slice($argv, 0, 1);
			$argv = array_slice($argv, 1);
		}

		if (!count($argv)) {
			return $this->show_usage();
		}

		$cmd  = $argv[0];
		$argv = array_slice($argv, 1);

		$commands = $this->get_commands();
		if (!isset($commands[$cmd])) {
			$this->stderr("Command '{$cmd}' not found.\n");
			return $this->show_usage();
		}

		$command = $commands[$cmd];

		if (!empty($command['usage']) && !count($argv)) {
			$this->stderr("Command usage:\n  {$usage_start} {$command['usage']}\n");
			return false;
		}

		try {
			$method = $command['method'];
			$result = $this->$method($argv);
			if ($result === false) exit(1);
			return $result;
		} catch (InvalidArgumentException $ex) {
			$msg =
			$this->stderr($ex->getMessage() ? $ex->getMessage() : "Wrong arguments");
			$this->stderr("\n\n");
			$this->stderr("Command usage:\n  {$usage_start} {$command['usage']}\n");
			return false;
		} catch (Throwable $ex) {
			$this->stderr($ex->getMessage() . PHP_EOL);
			exit(1);
		}
	}

	private function show_usage() {
		$commands = $this->get_commands();
		if (empty($commands)) {
			$this->stderr("This script is not designed to launch from console\n");
			return true;
		}
		$desc = AnnotationParser::parseClass($this)->getAnnotation('cliDescription');
		$this->stderr(($desc ? $desc . "\n" : '') . "\nBasic commands are:");
		$max_len = max(array_map('strlen', array_keys($commands)));
		foreach ($commands as $cmd => $desc) {
			if (!empty($desc['desc'])) {
				$this->stderr(sprintf("\n  %-{$max_len}s  - %s", $cmd, $desc['desc']));
			} else {
				$this->stderr(sprintf("\n  %-{$max_len}s", $cmd));
			}
		}
		$this->stderr("\n\nSee '" . implode(' ', $_SERVER['argv']) . " <command>' to get information on command usage\n");
		return true;
	}

	private function get_commands() {
		$methods = [];
		foreach ((new \ReflectionClass($this))->getMethods() as $Method) {
			$Parser = AnnotationParser::parseReflector($Method);
			$cmd    = $Parser->getAnnotation('cliCommand');
			if (empty($cmd)) continue;
			$methods[$cmd] = [
				'desc'   => $Parser->getAnnotation('cliDescription'),
				'usage'  => $Parser->getAnnotation('cliUsage'),
				'method' => $Method->getName(),
				'Parser' => $Parser,
			];
		}
		return $methods;
	}

	protected function stdout($msg) {
		return fwrite(STDOUT, $msg);
	}

	protected function stderr($msg) {
		return fwrite(STDERR, $msg);
	}
}
