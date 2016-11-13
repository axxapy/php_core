<?php namespace axxapy;

use axxapy\Debug\Log;
use axxapy\Interfaces\Runnable;
use axxapy\Streams\StdoutStream;
use axxapy\Web\UriBuilder;
use InvalidArgumentException;
use RuntimeException;

class Router {
	const METHOD_ANY    = 'any:';
	const METHOD_POST   = 'post:';
	const METHOD_GET    = 'get:';
	const METHOD_DELETE = 'delete:';
	const METHOD_PUT    = 'put:';
	const METHOD_HEAD   = 'head:';

	const METHOD_CLI = 'cli:';

	private $Context;

	private $original_routes = [];
	private $routes          = [];
	private $routes_regex    = [];

	private $current_route_name;

	public function __construct(Context $Context) {
		$this->Context = $Context;
		$this->compileRoutes($Context->getConfig()->getSection('routes'));
	}

	/**
	 * @param string $route_name Name of route. [1] element of arrain in router (__base__.php)
	 * @param array  $params     params to route. For instance, if route is '/one/{key}/', params should be ['key' =>
	 *                           'val']
	 * @param bool   $full       Full url. With domain and protocol
	 *
	 * @return \axxapy\Web\UriBuilder|null
	 */
	public function getUrl($route_name, array $params = [], $full = false) {
		if (isset($params['https'])) {
			$protocol = 'https://';
			unset($params['https']);
		} elseif (isset($_SERVER['SERVER_PROTOCOL'])) {
			$protocol = empty($_SERVER['HTTPS']) ? 'http://' : 'https://';
		} else {
			$protocol = '//';
		}
		foreach ($this->original_routes as $key => $val) {
			if (!empty($val[1]) && $val[1] == $route_name) {

				if (isset($val['https'])) {
					$protocol = 'https://';
				}

				$url = str_replace(
					array_map(function ($val) { return '{' . $val . '}'; }, array_keys($params)),
					array_values($params),
					substr($key, strpos($key, ':') + 1) //remove "method:" from start of the route
				);
				$url = $full ? ($protocol . $this->Context->getProject()->getUrl() . $url) : $url;
				return new UriBuilder($url);
			}
		}
		return null;
	}

	public function route($not_found = null) {
		$argv = isset($_SERVER['argv']) ? $_SERVER['argv'] : [];
		$argc = isset($_SERVER['argc']) ? $_SERVER['argc'] : 0;

		$matched = [];

		if (PHP_SAPI == 'cli') {
			if ($argc < 2) throw new InvalidArgumentException("No arguments provided");
			$method = 'cli';
			$path   = $argv[1];
		} else {
			$method = strtolower($_SERVER['REQUEST_METHOD']);
			if (!empty ($_SERVER['PATH_INFO'])) {
				$path = $_SERVER['PATH_INFO'];
				$path = preg_replace('#^([^?]+)?(.*)$#', '\1', $path);
				if (substr($path, 0, 2) == '//') $path = substr($path, 1);
			} elseif (!empty ($_SERVER['REQUEST_URI'])) {
				$path = (strpos($_SERVER['REQUEST_URI'], '?') > 0) ? strstr($_SERVER['REQUEST_URI'], '?', true) : $_SERVER['REQUEST_URI'];
			} else {
				$path = '/';
			}
		}

		if (strlen($path) > 1) {
			$path = rtrim($path, '/');
		}

		Log::v(__CLASS__, "path: {$path} | {$method}:{$path} | any:{$path}");

		if (isset($this->routes["{$method}:{$path}"]) || isset($this->routes["any:{$path}"])) {
			$handler = isset($this->routes["{$method}:{$path}"]) ? $this->routes["{$method}:{$path}"] : $this->routes["any:{$path}"];
		} else {
			$path_with_method     = "{$method}:{$path}";
			$path_with_any_method = "any:{$path}";
			foreach ($this->routes_regex as $pattern => $found) {
				if (preg_match($pattern, $path_with_method, $matches) || preg_match($pattern, $path_with_any_method, $matches)) {
					$matched = [];
					foreach ($matches as $key => $value) {
						if (is_string($key)) {
							$matched[$key] = $value;
						}
					}
					$handler = $found;
					break;
				}
			}
		}

		if (!empty($handler) && !empty($handler[1])) {
			$this->current_route_name = $handler[1];
		}

		$result = false;
		if (!empty($handler)) {
			$result = $this->callFunction($handler, $matched/*, $params*/);
		}

		if ($result === false) {
			$result = $this->callFunction($not_found);
		}

		if ($result instanceof Interfaces\Renderable) {
			$result->render(new StdoutStream());
			return true;
		}

		if (is_string($result)) {
			(new StdoutStream())->write($result);
			return true;
		}

		return $result;
	}

	public function getCurrentRouteName() {
		return $this->current_route_name;
	}

	/**
	 * Parse routes, convert pathes like "/something/{user_id}/settings" to regexes
	 *
	 * @param $routes
	 *
	 * @return array
	 */
	private function compileRoutes($routes) {
		$this->original_routes = $routes;
		$compiled_routes       = [];

		$num = 0;
		foreach ($routes as $url_path => $handler) {
			if (strlen($url_path) > strpos($url_path, ':') + 2) {//do not trim / for case like "get:/"
				$url_path = trim($url_path, '/');
			}
			$var_pos = strpos($url_path, '{');
			if ($var_pos === false) { //simple route
				if (strlen($url_path) > strpos($url_path, ':') + 2 && substr($url_path, -1) == '/') {
					$url_path = substr($url_path, 0, -1);
				}
				$this->routes[$url_path] = $handler;
				continue;
			}
			$before                    = substr($url_path, 0, $var_pos);
			$before                    = substr($before, strpos($before, ':') + 1);
			$before_len                = count(explode('/', $before));
			$next_symbol               = substr($url_path, strpos($url_path, '}', $var_pos) + 1, 1);
			$close_pattern             = $next_symbol ? ">[^\\{$next_symbol}]*?)" : '>.*?)';
			$url_path                  = preg_replace(
				'#^([^:]+):#',
				strpos($url_path, self::METHOD_ANY) === 0 ? '([a-zA-Z]+):' : '(\1):',
				$url_path
			);
			$pattern                   = '#^' . str_replace(['{', '}'], ['(?<', $close_pattern], $url_path) . '$#s';
			$compiled_routes[$pattern] = [$before_len, ++$num, $handler];
		}

		//sort them by length of path to avoid regex conflicts
		uasort($compiled_routes, function ($a, $b) {
			if ($a[0] == $b[0]) return $a[1] < $b[1] ? -1 : 1;
			return $a[0] < $b[0] ? 1 : -1;
		});

		foreach ($compiled_routes as $key => &$val) {
			$val = $val[2];
		}

		$this->routes_regex = $compiled_routes;
	}

	public function callFunction($handler, array $params = []/*, array $params = []*/) {
		if (is_array($handler)) {
			$h = $handler[0];
			unset($handler[0], $handler[1]);
			$params += $handler;//add all params from route description
			$handler = $h;
		}

		if (is_string($handler)) {
			list($class, $action_name) = strpos($handler, ':') ? explode(':', $handler, 2) : [$handler, null];
			$Controller = new $class($this->Context);
			$this->setControllerStatData(get_class($Controller) . ':' . $action_name);

			if ($Controller instanceof Runnable) {
				return $Controller->run($params);
			}

			throw new RuntimeException('Controller should be instance of \AF\Controller_Base');
		}

		if (is_callable($handler)) {
			return call_user_func_array($handler, [$this->Context, $params/*, $params*/]);
		}

		return false;
	}

	private function setControllerStatData($script_or_class_name) {
		if (function_exists('pinba_script_name_set')) {
			pinba_script_name_set($script_or_class_name . ' [' . __CLASS__ . ']');
		}
		$_SERVER['__CONTROLLER'] = $script_or_class_name;
	}

	/**
	 * @return array
	 */
	public function getRoutes() {
		return $this->routes;
	}
}
