<?php namespace axxapy;

use AF\MemcacheStorage;
use axaxpy\Storages\SessionStorage;

class Context {
	private $Config;
	private $Router;
	private $Session;
	private $CacheAdapter;

	public function __construct(array $config = []) {
		$this->Config = new Config($config);
	}

	public function getConfig() {
		return $this->Config;
	}

	public function getRouter() {
		if (!$this->Router) {
			$this->Router = new Router($this);
		}
		return $this->Router;
	}

	public function getSession() {
		if (!$this->Session) {
			$this->Session = new SessionStorage();
		}
		return $this->Session;
	}

	public function getCacheAdapter() {
		if (!$this->CacheAdapter) {//@todo: make configurable through config
			$this->CacheAdapter = new MemcacheStorage($this);
		}
		return $this->CacheAdapter;
	}
}
