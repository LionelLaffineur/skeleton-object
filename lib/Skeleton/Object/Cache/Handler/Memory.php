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

class Memory implements \Skeleton\Object\Cache\HandlerInterface {
	private static ?Memory $memory = null;

	private array $details = [];

	/**
	 * __get
	 *
	 * @access public
	 * @param string $key key
	 * @return mixed $value
	 */
	public function __get(string $key): mixed {
		if (!isset($this->details[$key])) {
			throw new \Exception('Unknown key ' . $key);
		}
		return $this->details[$key];
	}

	/**
	 * __set
	 *
	 * @access public
	 * @param string $key key
	 * @param mixed $value value
	 */
	public function __set(string $key, mixed $value): void {
		$this->details[$key] = $value;
	}

	/**
	 * __isset
	 *
	 * @access public
	 * @param string $key key
	 * @return bool isset
	 */
	public function __isset(string $key): bool {
		if (isset($this->details[$key])) {
			return true;
		}
		return false;
	}

	/**
	 * Flush
	 *
	 * @access public
	 */
	public function clear(): void {
		$this->details = [];
	}

	/**
	 * Get from objectcache
	 *
	 * @access public
	 * @param string $key key
	 * @return mixed object
	 */
	public static function get(string $key): mixed {
		$memory = self::fetch();
		if (isset($memory->$key)) {
			return $memory->$key;
		}
		throw new \Exception('Object not in cache');
	}

	/**
	 * Get multi from objectcache
	 *
	 * @access public
	 * @param array<string> $keys keys
	 * @return array<mixed>
	 */
	public static function multi_get(array $keys): array {
		$result = [];
		foreach ($keys as $key) {
			try {
				$result[] = self::get($key);
			} catch (\Exception $e) {
			}
		}
		return $result;
	}

	/**
	 * Put
	 *
	 * @access public
	 * @param string $key key
	 * @param mixed $value object
	 */
	public static function set(string $key, mixed $value): void {
		$memory = self::fetch();
		$memory->$key = $value;
	}

	/**
	 * Delete
	 *
	 * @access public
	 * @param string $key key
	 */
	public static function delete(string $key): void {
		$memory = self::fetch();
		unset($memory->$key);
	}

	/**
	 * Flush
	 *
	 * @access public
	 */
	public static function flush(): void {
		$memory = self::fetch();
		$memory->clear();
	}

	/**
	 * Get the current memcache object
	 *
	 * @access public
	 * @return Memcache $memcache
	 */
	public static function fetch(): Memcache {
		if (self::$memory === null) {
			self::$memory = new self();
		}

		return self::$memory;
	}
}
