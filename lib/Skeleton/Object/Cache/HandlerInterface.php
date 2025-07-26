<?php

declare(strict_types=1);

/**
 *
 */

namespace Skeleton\Object\Cache;

interface HandlerInterface {

	public static function set($key, $value);
	public static function get($key);
	public static function multi_get($keys);
	public static function delete($key);

}
