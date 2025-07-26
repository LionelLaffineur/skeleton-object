<?php

declare(strict_types=1);

/**
 * trait: Cache
 *
 * @author Christophe Gosiau <christophe.gosiau@tigron.be>
 * @author Gerry Demaret <gerry.demaret@tigron.be>
 * @author David Vandemaele <david.vandemaele@tigron.be>
 */

namespace Skeleton\Object;

trait Cache {

	/**
	 * Get
	 *
	 * @access public
	 * @param string $classname
	 * @param int $id
	 */
	public static function cache_get($key) {
		$handler = '\Skeleton\Object\Cache\Handler\\' . Config::$cache_handler;
		return $handler::get($key);
	}

	/**
	 * multi Get
	 *
	 * @access public
	 * @param string $classname
	 * @param array $keys
	 */
	public static function cache_multi_get($keys) {
		$handler = '\Skeleton\Object\Cache\Handler\\' . Config::$cache_handler;
		return $handler::multi_get($keys);
	}

	/**
	 * Add
	 *
	 * @access	public
	 * @param Object $object
	 */
	public static function cache_set($key, $object) {
		$handler = '\Skeleton\Object\Cache\Handler\\' . Config::$cache_handler;
		$handler::set($key, $object);
	}

	/**
	 * Delete
	 *
	 * @access public
	 * @param Object $object
	 */
	public static function cache_delete($key) {
		$handler = '\Skeleton\Object\Cache\Handler\\' . Config::$cache_handler;
		$handler::delete($key);
	}

	/**
	 * Flush
	 *
	 * @access public
	 */
	public static function cache_flush() {
		$handler = '\Skeleton\Object\Cache\Handler\\' . Config::$cache_handler;
		$handler::flush();
	}

}
