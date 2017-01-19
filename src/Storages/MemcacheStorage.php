<?php namespace axxapy;

use axxapy\Context;
use axxapy\Debug\Log;
use axxapy\Debug\Timer;
use axxapy\Interfaces\Storage;
use Memcached;
use RuntimeException;

class MemcacheStorage implements Storage {
	/** @var Context */
	private $Context;

	/** @var Memcached */
	private $Memcached;

	/**
	 * @param Context $Context
	 *
	 * @throws RuntimeException
	 */
	public function __construct(Context $Context) {
		$Timer = (new Timer('memcache::connect'))->start();

		$this->Context = $Context;

		$servers = $Context->getConfig()->getSection('memcache');
		if (!$servers) {
			throw new RuntimeException('No memcache servers provided. Please check your config section "memcache"');
		}

		$this->Memcached = new Memcached;

		$serversToAdd = [];
		foreach ($servers as $s) {
			$timeout       = (int)$s['timeout'];
			$retryInterval = (int)$s['retry_interval'];
			$status        = (int)$s['status'];

			if (!$status) {
				continue;
			}

			$this->Memcached->setOption(Memcached::OPT_RETRY_TIMEOUT, $retryInterval);
			$this->Memcached->setOption(Memcached::OPT_CONNECT_TIMEOUT, $timeout);

			$serversToAdd[] = [
				$s['host'],
				$s['port'],
				(int)$s['weight']
			];
		}

		if (!$this->Memcached->addServers($serversToAdd)) {
			$Timer->stopWithFail();
			throw new RuntimeException('Cannot add the following servers to memcache connection pool: ' . print_r($serversToAdd, true));
		}

		$Timer->stopWithSuccess();
	}

	/**
	 * Returns data from cache by provided key.
	 *
	 * @param   string $key     cache key
	 * @param   mixed  $default the value returned when data cannot be found or obtained from cache
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	public function get($key, $default = null) {
		$key = $this->sanitize_key($key);

		$Timer = (new Timer('memcache::get'))
			->addData([
				'action' => __FUNCTION__,
				'key'    => $key,
			])
			->start();

		$result = false;
		try {
			$result = $this->Memcached->get($key);
			if ($result === false && $this->Memcached->getResultCode() !== Memcached::RES_SUCCESS) {
				$Timer->stopWithFail();
				$result = $default;
			} else {
				$Timer->stopWithSuccess();
			}
		} catch (\Exception $e) {
			$Timer->stopWithFail();
			Log::e(__CLASS__, $e->getMessage(), $e);
		}

		return $result;
	}

	/**
	 * Put data into cache.
	 *
	 * @param   string          $key cache key
	 * @param   string|int|bool $val data to store
	 * @param   int             $ttl data time to live
	 *
	 * @return bool success
	 */
	public function set($key, $val, $ttl = 604800) {
		$key = $this->sanitize_key($key);

		$Timer = (new Timer('memcache::set'))->start();

		$Timer->addData([
			'action' => __FUNCTION__,
			'key'    => $key,
			'value'  => var_export($val, true),
			'ttl'    => $ttl,
		]);

		$result = false;
		try {
			$Timer->stopWithResult($result = $this->Memcached->set($key, $val, $ttl));
		} catch (\Exception $e) {
			$Timer->stopWithFail();
			Log::e(__CLASS__, $e->getMessage(), $e);
		}

		return $result;
	}

	/**
	 * @todo: use http://php.net/manual/en/memcached.setmulti.php
	 *
	 * @param array $data
	 *
	 * @param       $ttl
	 *
	 * @return $this
	 */
	public function setMulti(array $data, $ttl = 604800) {
		foreach ($data as $key => $val) {
			$this->set($key, $val, $ttl);
		}
		return $this;
	}

	/**
	 * Deletes data from cache by the provided key.
	 *
	 * @param   string $key cache key
	 *
	 * @return  bool    success
	 */
	public function del($key) {
		$key = $this->sanitize_key($key);

		$Timer = (new Timer('memcache::del'))
			->addData([
				'action' => __FUNCTION__,
				'key'    => $key,
			])
			->start();

		try {
			$Timer->stopWithResult($this->Memcached->delete($key));
			return true;
		} catch (\Exception $e) {
			$Timer->stopWithFail();
			Log::e(__CLASS__, $e->getMessage(), $e);
		}

		return false;
	}

	public function inc($key, $step = 1) {
		$Timer = (new Timer('memcache::inc'))
			->addData([
				'action' => __FUNCTION__,
				'key'    => $key,
			])
			->start();

		try {
			$Timer->stopWithResult($this->Memcached->increment($key, $step));
			return true;
		} catch (\Exception $e) {
			$Timer->stopWithFail();
			Log::e(__CLASS__, $e->getMessage(), $e);
		}

		return false;
	}

	/**
	 * Decreases key value to {$step}
	 *
	 * @param string $key
	 * @param int    $step
	 *
	 * @return bool
	 */
	public function dec($key, $step = 1) {
		$Timer = (new Timer('memcache::dec'))
			->addData([
				'action' => 'INC',
				'key'    => $key,
			])
			->start();

		try {
			$Timer->stopWithResult($this->Memcached->decrement($key, $step));
			return true;
		} catch (\Exception $e) {
			$Timer->stopWithFail();
			Log::e(__CLASS__, $e->getMessage(), $e);
		}

		return false;
	}

	public function Add($key, $value, $ttl = 604800) {
		$Timer = (new Timer('memcache::add'))
			->addData([
				'action' => 'ADD',
				'key'    => $key,
			])
			->start();

		try {
			$Timer->stopWithResult($this->Memcached->add($key, $value, $ttl));
			return true;
		} catch (\Exception $e) {
			$Timer->stopWithFail();
			Log::e(__CLASS__, $e->getMessage(), $e);
		}

		return false;
	}

	/**
	 * Remove invalid characters from cache key.
	 *
	 * @param   string $key cache key
	 *
	 * @return  string
	 */
	private function sanitize_key($key) {
		return preg_replace("/[\s\t\n]+/", '', $key);
	}

	/**
	 * Not used for Memcache
	 *
	 * @return $this
	 */
	public function destroy() {
		return $this;
	}

	/**
	 * Not used for Memcache
	 *
	 * @return $this
	 */
	public function commit() {
		return $this;
	}
}
