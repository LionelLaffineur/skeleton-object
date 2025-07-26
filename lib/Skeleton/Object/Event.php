<?php

declare(strict_types=1);

/**
 * trait: Event
 *
 * @author David Vandemaele <david@tigron.be>
 */

namespace Skeleton\Object;

trait Event {

	/**
	 * Add listener
	 *
	 * @access public
	 * @param  string $event name
	 * @param  array $listener
	 */
	public function add_listener($event) {
		if (strpos('::', $event) === false) {
			$classname = get_called_class();
			$method = $event;
		} else {
			list($classname, $method) = explode('::', $event);
		}
		$event_name = strtolower(str_replace('\\', '_', $classname . '_' . $method));

		$dispatcher = \Skeleton\Event\Dispatcher::get();
		$dispatcher->add_listener($event_name, [ '\\Event\\' . $classname, $method ]);
	}

	/**
	 * Dispatch
	 *
	 * @access public
	 * @param  string $event name
	 */
	public function dispatch() {
		$params = func_get_args();
		$event = array_shift($params);
		if (strpos('::', $event) === false) {
			$classname = get_called_class();
			$method = $event;
		} else {
			list($classname, $method) = explode('::', $event);
		}
		$event_name = strtolower(str_replace('\\', '_', $classname . '_' . $method));

		$dispatcher = \Skeleton\Event\Dispatcher::get();
		if ($dispatcher->has_listener($event_name) === false) {
			$this->add_listener($event);
		}

		$dispatcher->dispatch($event_name, $params);
	}

}
