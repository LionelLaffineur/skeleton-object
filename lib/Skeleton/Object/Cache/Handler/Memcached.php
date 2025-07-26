<?php

declare(strict_types=1);

/**
 * trait: Cache
 *
 * @author Christophe Gosiau <christophe.gosiau@tigron.be>
 * @author Gerry Demaret <gerry.demaret@tigron.be>
 * @author David Vandemaele <david.vandemaele@tigron.be>
 */

namespace Skeleton\Object\Cache\Handler;

class Memcached implements \Skeleton\Object\Cache\HandlerInterface {

	protected static $handler_object = null;

	/**
	 * Get from objectcache
	 *
	 * @access public
	 * @param string $key
	 * @return mixed
	 */
	public static function get($key) {
		$handler_object = static::connect();
		$var = $handler_object->get($key);
		if ($var === false) {
			throw new \Exception('Object not in cache');
		}
		return $var;
	}

	/**
	 * Get multi from objectcache
	 *
	 * @access public
	 * @param array $keys
	 * @return mixed
	 */
	public static function multi_get($keys) {
		$memcached = self::connect();
		$result = [];
		// set limit of keys per request for optimization
		foreach (array_chunk($keys, 1000) as $chunk) {
			$result = array_merge($result, $memcached->getMulti($chunk));
		}
		return $result;
	}

	/**
	 * Set
	 *
	 * @access public
	 * @param string $key
	 * @param mixed $value
	 */
	public static function set($key, $value) {
		$handler_object = static::connect();
		$config = \Skeleton\Object\Config::$cache_handler_config;
		$handler_object->set($key, $value, $config['expire']);
	}

	/**
	 * Delete
	 *
	 * @access public
	 * @param string $key
	 */
	public static function delete($key) {
		$handler_object = static::connect();
		$handler_object->delete($key);
	}

	/**
	 * Flush
	 *
	 * @access public
	 */
	public static function flush() {
		$handler_object = static::connect();
		$handler_object->flush();
	}

	/**
	 * Get the current memcached object
	 *
	 * @access public
	 * @return Memcache $handler_object
	 */
	public static function connect() {
		if (self::$handler_object === null) {
			$config = \Skeleton\Object\Config::$cache_handler_config;
			self::$handler_object = new \Memcached();
			self::$handler_object->setOption(\Memcached::OPT_SERIALIZER, \Memcached::SERIALIZER_IGBINARY);
			self::$handler_object->setOption(\Memcached::OPT_BINARY_PROTOCOL, true);
			self::$handler_object->setOption(\Memcached::OPT_NO_BLOCK, true);
			self::$handler_object->setOption(\Memcached::OPT_TCP_NODELAY, true);
			self::$handler_object->addServers([
				[$config['hostname'], $config['port']]
			]);
		}

		return self::$handler_object;
	}

}
