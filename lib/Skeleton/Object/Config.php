<?php

declare(strict_types=1);

/**
 * Config class
 * Configuration for Skeleton\Object
 *
 * @author David Vandemaele <david@tigron.be>
 */

namespace Skeleton\Object;

class Config {

	/**
	 * Caching backend handler
	 *
	 * @access public
	 * @var string $classname
	 */
	public static $cache_handler = false;

	/**
	 * Caching backend config
	 *
	 * @access public
	 * @var string $classname
	 */
	public static $cache_handler_config = [];

}
