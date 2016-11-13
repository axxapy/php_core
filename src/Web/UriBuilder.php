<?php namespace axxapy\Web;

/**
 * Build uris
 * Usage:
 *      // http://ya.ru/file/?var=value&var2=val2
 *      $url = (string)(new UriBuilder('http://ya.ru/file/?var=value'))->addGet(['var2' => 'val2']);
 *
 *      // //ya.ru/file/?var=value
 *      $url = (string)(new UriBuilder('file/?var=value'))->setDomain('ya.ru');
 *
 *      // https://ya.ru/file.php?var=value
 *      $url = (string)(new UriBuilder('file.php?var=value'))->setDomain('ya.ru')->setProtocol('https');
 *
 *      // /file.php?var=value
 *      $url = (string)(new UriBuilder('/file.php?var=value'));
 */
final class UriBuilder {
	private $domain   = '';
	private $url      = '';
	private $get      = [];
	private $protocol = '';

	public function __construct($base_uri = null) {
		if (!$base_uri) return;

		do {
			// http://domain.com/some/path.php?get=value
			if (preg_match('#^(?P<protocol>[a-z^:/]*):?//(?P<domain>[^/]+)(?P<url>\/[^\?]+)\?(?P<get>.*)$#i', $base_uri, $matches)) break;
			// http://domain.com/some/path.php
			if (preg_match('#^(?P<protocol>[a-z^:/]*):?//(?P<domain>[^/]+)(?P<url>\/[^\?]+)#i', $base_uri, $matches)) break;
			// http://domain.com/?get=value
			if (preg_match('#^(?P<protocol>[a-z^:/]*):?//(?P<domain>[^/]+)/?\?(?P<get>.*)$#i', $base_uri, $matches)) break;
			// http://domain.com/
			if (preg_match('#^(?P<protocol>[a-z^:/]*):?//(?P<domain>[^/]+)#i', $base_uri, $matches)) break;
			// /some/path.php?get=value
			if (preg_match('#^(?P<url>[^\?]+)/?\?(?P<get>.*)$#i', $base_uri, $matches)) break;
			// /some/path.php
			if (preg_match('#^(?P<url>[^\?]+)#i', $base_uri, $matches)) break;

			return;
		} while (false);

		if (isset($matches['domain'])) {
			$this->domain = strtolower($matches['domain']);
		}

		if (isset($matches['protocol'])) {
			$this->protocol = rtrim(strtolower($matches['protocol']), ':');
		}

		if (isset($matches['url'])) {
			$this->url = $matches['url'];
		}

		if (isset($matches['get'])) {
			parse_str($matches['get'], $this->get);
		}
	}

	public function addGet(array $get) {
		foreach ($get as $key => $val) {
			if(is_array($val)){
				foreach ($val as $sub_key => $sub_val) {
					$this->get["{$key}[{$sub_key}]"] = $sub_val;
				}
			} else {
				$this->get[$key] = $val;
			}
		}
		return $this;
	}

	/**
	 * @param string $protocol
	 *
	 * @return $this
	 */
	public function setProtocol($protocol) {
		$this->protocol = $protocol;
		return $this;
	}

	/**
	 * @param string $url
	 *
	 * @return $this
	 */
	public function setUrl($url) {
		$this->url = $url;
		return $this;
	}

	/**
	 * @param string $domain
	 *
	 * @return $this
	 */
	public function setDomain($domain) {
		$this->domain = $domain;
		return $this;
	}

	public function __toString() {
		if ($this->protocol && $this->domain) {
			$url = $this->protocol . '://' . $this->domain;
		} elseif ($this->domain) {
			$url = '//' . $this->domain;
		} else {
			$url = '';
		}

		if ($this->url) {
			$url .= preg_replace('#/{2,}#', '/', $url ? '/' . $this->url : $this->url);
		}

		if ($this->get) {
			$url .= '?' . http_build_query($this->get);
		}

		return $url;
	}

	/**
	 * @return string
	 */
	public function getUrl() {
		return $this->url;
	}

	/**
	 * @return array
	 */
	public function getGet() {
		return $this->get;
	}
}
