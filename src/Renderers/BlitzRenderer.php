<?php namespace axxapy\Renderers;

use axxapy\Context;
use axxapy\Debug\Log;
use axxapy\Interfaces\Renderable;
use axxapy\Interfaces\WriteStream;

if (!extension_loaded('blitz')) {
	throw new \RuntimeException("blitz.so not loaded");
}

final class BlitzCallback extends \Blitz {
	private $callbacks_map = [];
	private $subviews_map  = [];
	private $Context;

	public function __construct(Context $Context, $tpl_file, $tpl_dir) {
		ini_set('blitz.path', $tpl_dir);
		parent::__construct($tpl_file);
		$this->Context = $Context;
	}

	/**
	 * Setts map for template callbacks
	 *
	 * @param $map
	 *
	 * @return $this
	 */
	public function setCallbacksMap(array $map) {
		$this->callbacks_map = $map;
		return $this;
	}

	/**
	 * setts map for subviews whick cann be called by 'renderView()' method from template
	 *
	 * @param array $map
	 *
	 * @return $this
	 */
	public function setSubviewsMap(array $map) {
		$this->subviews_map = $map;
		return $this;
	}

	/**
	 * Executes callback called from template
	 *
	 * @param string $name
	 * @param array  $args
	 *
	 * @return mixed|null
	 */
	public function __call($name, $args = []) {
		if (!isset($this->callbacks_map[$name])) {
			Log::e(__CLASS__, "unknown function callback name: '{$name}'");
			return null;
		}
		$callback = $this->callbacks_map[$name];
		return call_user_func_array($callback, $args);
	}

	/**
	 * Internal template function.
	 * Calls module from Module folder.
	 *
	 * @param string $name
	 *
	 * @return mixed|null
	 */
	public function callModule($name) {
		$args = array_slice(func_get_args(), 1);
		if (strpos($name, ':') === false) return null;
		list($class, $func) = explode(':', $name, 2);
		$class = strtoupper(substr($class, 0, 1)) . strtolower(substr($class, 1));
		$class = __NAMESPACE__ . '\\Modules\\' . $class . 'RenderModule';
		return call_user_func_array([$class, $func], $args);
	}

	public function renderView($name, $args = []) {
		if (!array_key_exists($name, $this->subviews_map)) {
			Log::e(__CLASS__, "unknown view name: '{$name}'");
			return null;
		}
		$view = $this->subviews_map[$name];
		if (!is_object($view)) {
			if (empty($view)) return null;
			try {
				$view = new $view($this->Context);
			} catch (\Exception $ex) {
				Log::e(__CLASS__, 'cannot create view object: ' . var_export($view, true));
			}
		}
		if (!$view instanceof Renderable) {
			Log::e(__CLASS__, "wrong view class: ({$name}) " . get_class($view));
			return null;
		}
		return (string)$view->fetch();
	}

	/**
	 * Internal template function.
	 * Generates URL by route name and args.
	 *
	 * @param string $name
	 * @param array  $args
	 *
	 * @return string|null
	 */
	public function url($name, $args = []) {
		return (string)$this->Context->getRouter()->getUrl($name, $args);
	}

	/**
	 * Internal template function.
	 * Formats data in JSON format.
	 *
	 * @param $data
	 *
	 * @return string
	 */
	public function json($data) {
		return json_encode($data);
	}

	/**
	 * Internal template function.
	 * Applies urlencode to data.
	 *
	 * @param $data
	 *
	 * @return string
	 */
	public function urlencode($data) {
		return urlencode($data);
	}
}

final class BlitzRenderer extends Renderer {
	private $callbacks_map = [];
	private $subviews_map  = [];
	private $tpl_file      = null;
	private $tpl_body      = null;
	private $tpl_dir       = null;
	private $data_global   = [];

	public function __construct(Context $Context) {
		parent::__construct($Context);

		$public_dirs = $Context->getConfig()->getValue('dirs/public');
		if ($public_dirs) {
			$public_dirs = array_flip($public_dirs);
			array_walk($public_dirs, function (&$name, $path) {
				$name = 'DIR_' . $name;
			});
			$this->setGlobalData(array_flip($public_dirs));
		}

		$tpl_data = $public_dirs = array_flip($Context->getConfig()->getValue('tpl_data'));
		if (is_array($tpl_data)) {
			$this->addGlobalData(array_flip($tpl_data));
		}
	}

	/**
	 * Adds callback which can be called directly from template
	 *
	 * @param string                $name
	 * @param array|string|callable $callback
	 *
	 * @return $this
	 */
	public function addCallback($name, $callback) {
		$name                       = strtolower($name);
		$this->callbacks_map[$name] = $callback;
		return $this;
	}

	public function addSubview($name, $class) {
		$this->subviews_map[$name] = $class;
		return $this;
	}

	/**
	 * Sets tpl filename
	 *
	 * @param string $file
	 *
	 * @return $this
	 */
	public function setTplFile($file) {
		$this->tpl_file = $file;
		return $this;
	}

	/**
	 * Sets tpl body. Not the file but the body!
	 * This allows render templates from, for instance, database.
	 *
	 * @param string $body
	 *
	 * @return $this
	 */
	public function setTplBody($body) {
		$this->tpl_body = $body;
		return $this;
	}

	/**
	 * Setts tpl dir. By default, dir from config file is used.
	 *
	 * @param string $tpl_dir
	 *
	 * @return $this
	 */
	public function setTplDir($tpl_dir) {
		$this->tpl_dir = $tpl_dir;
		return $this;
	}

	/**
	 * Fetches the template
	 *
	 * @return string
	 */
	public function fetch() {
		if (strpos($this->tpl_file, '/') !== 0) {
			$tpl = ($this->tpl_dir ? $this->tpl_dir : $this->getContext()->getConfig()->getValue('dirs/tpl')) . $this->tpl_file;
		}
		$blitz = new BlitzCallback($this->getContext(), $tpl, $this->tpl_dir);
		$blitz->setCallbacksMap($this->callbacks_map);
		$blitz->setSubviewsMap($this->subviews_map);
		if (is_null($this->tpl_file)) $blitz->load($this->tpl_body);
		$blitz->setGlobal($this->prefetchData($this->data_global));
		$blitz->set($this->prefetchData($this->data));
		return $blitz->parse();
	}

	/**
	 * Adds global data.
	 * This data will be accessible from any blitz block.
	 *
	 * @param string|array $key
	 * @param mixed        $val
	 *
	 * @return $this
	 */
	public function addGlobalData($key, $val = null) {
		if (is_array($key)) {
			$this->data_global = array_merge_recursive($this->data_global, $key);
		} else {
			$this->data_global[$key] = $val;
		}
		return $this;
	}

	/**
	 * Sets global data array.
	 * This data will be accessible from any blitz block.
	 *
	 * @param array $data
	 *
	 * @return $this
	 */
	public function setGlobalData(array $data) {
		$this->data_global = $data;
		return $this;
	}

	public function render(WriteStream $Stream) {
		$Stream->write($this->fetch());
	}
}
