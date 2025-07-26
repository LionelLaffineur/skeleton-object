<?php

declare(strict_types=1);

/**
 * trait: Child
 *
 * @author Christophe Gosiau <christophe.gosiau@tigron.be>
 * @author Gerry Demaret <gerry.demaret@tigron.be>
 * @author David Vandemaele <david@tigron.be>
 */

namespace Skeleton\Object;

use Skeleton\Database\Database;

trait Child {
	/**
	 * Child details
	 *
	 * @var array $child_details
	 * @access protected
	 */
	protected $child_details = [];

	/**
	 * Get the child details of this object
	 *
	 * @access private
	 */
	protected function trait_get_child_details() {
		$table = self::trait_get_child_database_table();

		if ($table === null) {
			return;
		}

		if (!isset($this->id) OR $this->id === null) {
			throw new \Exception('Could not fetch ' . $table . ' data: id not set');
		}

		$db = self::trait_get_database();
		$child_details = $db->get_row('SELECT * FROM ' . $db->quote_identifier($table) . ' WHERE ' . self::trait_get_parent_table_field_id() . '=?', [$this->id]);

		if ($child_details === null) {
			throw new \Exception('Could not fetch ' . $table . ' data: none found with id ' . $this->id);
		}

		$this->child_details = $child_details;
		$this->reset_dirty_fields();
	}

	/**
	 * Save
	 *
	 * @access public
	 */
	protected function trait_child_delete() {
		$db = self::trait_get_database();
		$table = self::trait_get_child_database_table();

		if ($table === null) {
			return;
		}

		$this->child_details[self::trait_get_parent_table_field_id()] = $this->id;
		$db->query('DELETE FROM ' . $db->quote_identifier($table) . ' WHERE ' . self::trait_get_parent_table_field_id() . '=?', [$this->id]);
	}

	/**
	 * Save
	 *
	 * @access public
	 */
	protected function trait_child_save() {
		$db = self::trait_get_database();
		$table = self::trait_get_child_database_table();

		if ($table === null) {
			return;
		}

		$count = $db->get_one('SELECT count(*) FROM ' . $db->quote_identifier($table) . ' WHERE ' . self::trait_get_parent_table_field_id() . '=?', [$this->id]);

		$this->child_details[self::trait_get_parent_table_field_id()] = $this->id;
		if ($count == 0) {
			$db->insert($table, $this->child_details);
		} else {
			$where = self::trait_get_parent_table_field_id() . '=' . $db->quote($this->id);
			$db->update($table, $this->child_details, $where);
		}
	}

	/**
	 * trait_get_database_table: finds out which table we need to use
	 *
	 * @access private
	 * @return string $table
	 */
	public static function trait_get_child_database_table() {
		if (property_exists(self::class, 'class_configuration') and is_array(self::$class_configuration)) {
			if (array_key_exists('database_table', self::$class_configuration) and self::$class_configuration['database_table'] === null) {
				return null;
			}
			if (isset(self::$class_configuration['database_table'])) {
				return self::$class_configuration['database_table'];
			}
		}

		return strtolower((new \ReflectionClass(self::class))->getShortName());
	}

	/**
	 * trait_get_table_field_id: get the field that is used as ID
	 *
	 * @access private
	 * @return string $id
	 */
	public static function trait_get_parent_table_field_id() {
		if (property_exists(self::class, 'class_configuration') and isset(self::$class_configuration['parent_field_id'])) {
			return self::$class_configuration['parent_field_id'];
		}
		return strtolower((new \ReflectionClass(self::class))->getParentClass()->getShortName() . '_id');
	}
}
