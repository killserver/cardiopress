<?php
if(!defined("IS_CORE")) {
	echo "403 ERROR";
	die();
}

class Route {

	private static $router = array();
	private static $_params = array();

	public static function Add($name, $regex, $call = "", array $def = array()) {
		self::$router[$name] = array("regex" => $regex, "call" => $call, "default" => $def);
		return true;
	}

	public static function Remove($name = "", $regex = "") {
		foreach(self::$router as $k => $v) {
			if($k==$name || $v==self::$router[$k]['regex']) {
				unset(self::$router[$k]);
			}
		}
		return true;
	}

	public static function Param($name = "", $default = false) {
		if($name==="") {
			return self::$_params;
		} else if(isset(self::$_params[$name])) {
			return self::$_params[$name];
		} else {
			return $default;
		}
	}

	public static function Find($name, $params = array()) {
		global $pageNow;
		if(isset(self::$router[$name])) {
			$uri = self::$router[$name]['regex'];
			if(strpos($uri, '<') === false && strpos($uri, '(') === false) {
				return $uri;
			}
			while(preg_match('#\([^()]++\)#', $uri, $match)) {
				$search = $match[0];
				$replace = substr($match[0], 1, -1);
				while(preg_match('#<([a-zA-Z0-9_]++)>#', $replace, $match)) {
					list($key, $param) = $match;
					if(isset($params[$param])) {
						$replace = str_replace($key, $params[$param], $replace);
					} else {
						$replace = '';
						break;
					}
				}
				$uri = str_replace($search, $replace, $uri);
			}

			while(preg_match('#<([a-zA-Z0-9_]++)>#', $uri, $match)) {
				list($key, $param) = $match;
				if(!isset($params[$param])) {
					if(isset($this->_defaults[$param])) {
						$params[$param] = $this->_defaults[$param];
					} else {
						return false;
					}
				}
				$uri = str_replace($key, $params[$param], $uri);
			}
			$uri = preg_replace('#//+#', '/', rtrim($uri, '/'));
			return $uri;
		} else {
			return false;
		}
	}

	public static function Get($link) {
		$ret = false;
		$call = "";
		foreach(self::$router as $k => $v) {
			$comp = self::Compile($v['regex']);
			if($comp!==false) {
				$ret = self::Matches($comp, $link);
				if($ret!==false) {
					self::$_params = array_merge($v['default'], $ret);
					$call = $v['call'];
					break;
				}
			}
		}
		return array($ret, $call);
	}

	private static function Compile($uri, $regex = array()) {
		if(!is_string($uri)) {
			return;
		}
		if(!is_array($regex)) {
			return;
		}
		$expression = preg_replace('#[.\\+*?[^\\]${}=!|]#', '\\\\$0', $uri);
		if(strpos($expression, '(') !== false) {
			$expression = str_replace(array('(', ')'), array('(?:', ')?'), $expression);
		}
		$expression = str_replace(array('<', '>'), array('(?P<', '>[^/.,;?\n]++)'), $expression);
		if($regex) {
			$search = $replace = array();
			foreach($regex as $key => $value) {
				$search[]  = "<".$key.">[^/.,;?\n]++";
				$replace[] = "<".$key.">".$value;
			}
			$expression = str_replace($search, $replace, $expression);
		}
		return '#^'.$expression.'$#uD';
	}

	private static function Matches($route, $uri, $def = "") {
		if(!preg_match($route, $uri, $matches)) {
			return false;
		}
		$params = array();
		foreach($matches as $key => $value) {
			if(is_int($key)) {
				continue;
			}
			$params[$key] = $value;
		}
		return $params;
	}

}