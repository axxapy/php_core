<?php namespace axxapy\Renderers\Modules;

class HtmlRendererModule {
	public static function options($values, $selected = null) {
		$ret = "";
		foreach ($values as $key => $val) {
			if (!is_null($selected) && $selected == $key) {
				$ret .= "<option value='{$key}' selected>{$val}</option>";
			} else {
				$ret .= "<option value='{$key}'>{$val}</option>";
			}
		}
		return $ret;
	}
}
