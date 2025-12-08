<?php
/**
 * trait: Cache
 *
 * @author Christophe Gosiau <christophe.gosiau@tigron.be>
 * @author Gerry Demaret <gerry.demaret@tigron.be>
 * @author David Vandemaele <david.vandemaele@tigron.be>
 */

namespace Skeleton\Object\Cache\Handler;

class Memcache extends \Skeleton\Object\Cache\Handler\Memcached {

	/**
	 * Get multi from objectcache
	 *
	 * @access public
	 * @param array $keys
	 * @return mixed
	 */
	public static function multi_get($keys) {
		$result = [];
		foreach ($keys as $key) {
			try {
				$result[] = self::get($key);
			} catch (\Exception $e) {
				continue;
			}
		}
		return $result;
	}

	/**
	 * Put
	 *
	 * @access public
	 * @param string $key
	 * @param mixed $value
	 */
	public static function set($key, $value) {
		$handler_object = self::connect();
		$config = \Skeleton\Object\Config::$cache_handler_config;
		$handler_object->set($key, $value, false, $config['expire']);
	}

	/**
	 * Get the current memcache object
	 *
	 * @access public
	 * @return Memcache $handler_object
	 */
	public static function connect() {
		if (self::$handler_object === null || self::$handler_object_pid !== getmypid()) {
			$config = \Skeleton\Object\Config::$cache_handler_config;

			self::$handler_object_pid = getmypid();

			self::$handler_object = new \Memcache();
			self::$handler_object->connect($config['hostname'], $config['port']);
		}

		return self::$handler_object;
	}

}
