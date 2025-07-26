<?php

declare(strict_types=1);

/**
 * trait: Get
 *
 * @author Christophe Gosiau <christophe.gosiau@tigron.be>
 * @author Gerry Demaret <gerry.demaret@tigron.be>
 * @author David Vandemaele <david@tigron.be>
 */

namespace Skeleton\Object;

trait Get {
	/**
	 * Get object info
	 *
	 * @access public
	 * @return array $details
	 */
	public function get_info() {
		$info = [];
		if (isset($this->child_details)) {
			$info = array_merge($info, $this->child_details);
		}
		$info = array_merge($info, $this->details);
		return $info;
	}

	/**
	 * Get classname
	 *
	 * @access public
	 * @return string $classname
	 */
	public function get_classname() {
		return (new \ReflectionClass($this))->getShortName();
	}


	/**
	 * Get by ids
	 *
	 * @access public
	 * @param array $ids
	 * @return array $obecjts
	 */
	public static function get_by_ids($ids) {
		// Preserve the order of the ids
		$result = array_fill_keys($ids, null);

		if (get_called_class()::trait_cache_enabled()) {
			$prefix = get_called_class()::trait_get_cache_prefix();
			$cache_keys = [];
			foreach ($ids as $id) {
				$cache_keys[$id] = $prefix . '_' . $id;
			}
			$cached_objects = get_called_class()::cache_multi_get($cache_keys);

			// we use it to avoid repeatedly iterating if some cache is missing
			$cached_objects_map = [];

			foreach ($cached_objects as $cached_object) {
				$cached_objects_map[$cached_object->id] = $cached_object;
				unset($cache_keys[$cached_object->id]);
			}

			if (count($cache_keys) === 0) {
				// we should reset indexes in case if someone tries to get first element via 0 index
				return array_values($cached_objects);
			}

			foreach ($ids as $id) {
				if (isset($cached_objects_map[$id]) === false) {
					continue;
				}
				$result[$id] = $cached_objects_map[$id];
			}
		}

		// Fill in the result with values that could not be obtained from cache
		foreach ($result as $id => $value) {
			if ($value !== null) {
				continue;
			}
			$result[$id] = self::get_by_id($id);
		}

		// we should reset indexes in case if someone tries to get first element via 0 index
		return array_values($result);
	}

	/**
	 * Get by id
	 *
	 * @access public
	 * @param int $id
	 * @return object
	 */
	public static function get_by_id($id) {
		if ($id === null) {
			throw new \Exception('Can not fetch ' . get_called_class() . ' with id null');
		}

		if (get_called_class()::trait_cache_enabled()) {
			$prefix = get_called_class()::trait_get_cache_prefix();
			try {
				return get_called_class()::cache_get($prefix . '_' . $id);
			} catch (\Exception $e) {
			}
		}

		$classname = get_called_class();

		/**
		 * If in class_configuration a child_classname field is specified,
		 * use this
		 */
		if (property_exists(self::class, 'class_configuration') && isset(self::$class_configuration['child_classname_field'])) {
			$classname_field = self::$class_configuration['child_classname_field'];
			$table = self::trait_get_database_table();
			$db = self::trait_get_database();
			$classname = $db->get_one('SELECT ' . $classname_field . ' FROM ' . $db->quote_identifier($table) . ' WHERE id=?', [ $id ]);
			if ($classname === null) {
				throw new \Exception('Can not fetch ' . get_called_class() . ' with id ' . $id);
			}
		}

		$object = new $classname($id);
		if (get_called_class()::trait_cache_enabled()) {
			get_called_class()::cache_set(get_called_class()::trait_get_cache_key($object), $object);
		}

		return $object;
	}

	/**
	 * Get all
	 *
	 * @access public
	 * @return array objects
	 */
	public static function get_all($sort = null, $direction = null) {
		$table = self::trait_get_database_table();
		$db = self::trait_get_database();

		$where = '';
		$field_archived = self::trait_get_table_field_archived();
		foreach (self::get_object_fields() as $field) {
			if ($field['Field'] != $field_archived) {
				continue;
			}

			$where = ' AND ' . $field_archived . ' IS NULL';
		}

		$reflection = new \ReflectionClass(get_called_class());
		$traits = $reflection->getTraits();
		if (isset($traits['Skeleton\Object\Child']) === true) {
			$where .= ' AND classname = ' . $db->quote(get_called_class());
		}

		$query = 'SELECT ' . self::trait_get_table_field_id() . ' FROM ' . $db->quote_identifier($table) . ' WHERE 1=1' . $where;
		if ($sort !== null) {
			if ($direction === null) {
				$direction = 'ASC';
			}

			$query .= ' ORDER BY ' . $sort . ' ' . $direction;
		}

		$ids = $db->get_column($query, []);
		return self::get_by_ids($ids);
	}
}
