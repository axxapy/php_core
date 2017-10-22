<?php namespace axxapy\Controllers;

use axxapy\Renderers\StaticRenderer;
use InvalidArgumentException;

class StaticController extends WebController {
	protected function actionDefault(array $params = []) {
		if (empty($params['dir'])) {
			throw new InvalidArgumentException("required parameter 'dir' (path to base dir) is empty");
		}
		if (empty($params['file'])) {
			throw new InvalidArgumentException("required parameter 'file' is empty");
		}

		$file = $params['file'];
		if (preg_match('#(.*)\.min\.(js|css)$#', $file, $matches)) {
			$file = "{$matches[1]}.{$matches[2]}";
		}

		$Renderer = new StaticRenderer($params['dir'], $file);
		if (!empty($params['includes'])) {
			foreach ($params['includes'] as $name => $path) {
				$Renderer->addIncludeDir($name, $path);
			}
		}

		return $Renderer;
	}
}
