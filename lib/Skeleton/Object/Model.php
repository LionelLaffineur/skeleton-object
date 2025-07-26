<?php

declare(strict_types=1);

/**
 * trait: Model
 *
 * @author Christophe Gosiau <christophe.gosiau@tigron.be>
 * @author Gerry Demaret <gerry.demaret@tigron.be>
 * @author David Vandemaele <david@tigron.be>
 */

namespace Skeleton\Object;

use Skeleton\Database\Database;

trait Model {
	/**
	 * @var int $id identifier
	 * @access public
	 */
	public $id;

	/**
	 * Details
	 *
	 * @var array<mixed> $details
	 * @access private
	 */
	protected array $details = [];

	/**
	 * Dirty fields
	 * Unsaved fields
	 *
	 * @var array<mixed> $dirty_fields
	 * @access private
	 */
	private array $dirty_fields = [];

	/**
	 * Object text cache
	 *
	 * @access private
	 * @var array<mixed> $object_text_cache object texts in cache
	 */
	private array $object_text_cache = [];

	/**
	 * Object text update
	 *
	 * @access private
	 * @var array<mixed> $object_text_updated object texts updated
	 */
	private array $object_text_updated = [];

	/**
	 * child_casted_object
	 *
	 * Used to cleanup a casted child object
	 *
	 * @access private
	 * @var object $child_casted_object
	 */
	private $child_casted_object = null;

	/**
	 * Constructor
	 *
	 * @access public
	 * @param int $id object id
	 */
	public function __construct($id = null) {
		if (property_exists(__CLASS__, 'class_configuration') &&
			isset(self::$class_configuration['child_classname_field'])) {
			$classname_field = self::$class_configuration['child_classname_field'];
			$this->details[$classname_field] = get_class($this);
		}

		if ($id !== null) {
			$this->id = $id;
			$this->get_details();
		}
	}

	/**
	 * Set a detail
	 *
	 * @access public
	 * @param string $key key
	 * @param mixed $value object
	 */
	public function __set(string $key, mixed $value): void {
		// Check if the key we want to set exists in the disallow_set variable
		if (property_exists(__CLASS__, 'class_configuration') && isset(self::$class_configuration['disallow_set'])) {
			if (is_array(self::$class_configuration['disallow_set'])) {
				if (in_array($key, self::$class_configuration['disallow_set'])) {
					throw new \Exception('Can not set ' . $key . ' directly');
				}
			} else {
				throw new \Exception('Improper use of disallow_set');
			}
		}

		if (is_object($value) && property_exists($value, 'id')) {
			$key .= '_id';
			$this->$key = $value->id;
			return;
		}

		if (isset(self::$object_text_fields) === true && strpos($key, 'text_') === 0) {
			$this->trait_set_object_text($key, $value);
			return;
		}

		if (substr($key, -3) === '_id' && empty($value) === true) {
			$value = null;
		}

		// If a new value is set, let's tag it as dirty
		if (isset($this->dirty_fields[$key]) === false && empty($this->id) === false) {
			if (array_key_exists($key, $this->details)) {
				if (is_numeric($value) === true && $this->details[$key] != $value) {
					$this->dirty_fields[$key] = $this->details[$key];
				}

				if (is_numeric($value) === false && $this->details[$key] !== $value) {
					$this->dirty_fields[$key] = $this->details[$key];
				}
			}

			if (isset($this->child_details) === true && array_key_exists($key, $this->child_details) === true) {
				if (is_numeric($value) === true && $this->child_details[$key] != $value) {
					$this->dirty_fields[$key] = $this->child_details[$key];
				}

				if (is_numeric($value) === false && $this->child_details[$key] !== $value) {
					$this->dirty_fields[$key] = $this->child_details[$key];
				}
			}
		}

		$this->details[$key] = $value;
		if (isset($this->child_details)) {
			$this->child_details[$key] = $value;
		}
	}

	/**
	 * Get a detail
	 *
	 * @access public
	 * @param string $key key
	 * @return mixed $value object
	 */
	public function __get(string $key): mixed {
		if (isset($this->details[strtolower($key) . '_id']) && class_exists($key)) {
			return $key::get_by_id($this->details[strtolower($key) . '_id']);
		}

		if (is_array($this->details) && array_key_exists($key, $this->details)) {
			return $this->details[$key];
		}

		if (isset($this->child_details) && array_key_exists($key, $this->child_details)) {
			return $this->child_details[$key];
		}

		if (isset($this->child_details) && isset($this->child_details[strtolower($key) . '_id']) &&
			class_exists($key)) {
			return $key::get_by_id($this->child_details[strtolower($key) . '_id']);
		}

		if (isset(self::$object_text_fields)) {
			if (strpos($key, 'text_') === 0) {
				return $this->trait_get_object_text($key);
			}
		}

		throw new \Exception('Unknown key requested: ' . $key);
	}

	/**
	 * Isset
	 *
	 * @access public
	 * @param string $key key
	 * @return bool is set ?
	 */
	public function __isset(string $key): bool {
		if (isset($this->details[strtolower($key) . '_id']) && class_exists($key)) {
			return true;
		}

		if (is_array($this->details) && isset($this->details[$key])) {
			return true;
		}

		if (isset(self::$object_text_fields)) {
			if (strpos($key, 'text_') === 0) {
				[$language, $label] = explode('_', str_replace('text_', '', $key), 2);

				if (!in_array($label, self::$object_text_fields)) {
					return false;
				}
				return true;
			}
		}

		if (isset($this->child_details) && isset($this->child_details[strtolower($key) . '_id']) &&
			class_exists($key)) {
			return true;
		}

		if (isset($this->child_details) && isset($this->child_details[$key])) {
			return true;
		}

		return false;
	}

	/**
	 * Cast
	 *
	 * @access public
	 * @param string $classname classname
	 * @return mixed object
	 */
	public function cast(string $classname): mixed {
		if (!isset(self::$class_configuration['child_classname_field'])) {
			throw new \Exception('Only Child classes can be casted to another child class');
		}

		if (!class_exists($classname)) {
			throw new \Exception('Classname "' . $classname . '" doesn\'t exist');
		}

		if (get_class($this) === $classname) {
			return $this;
		}

		$object = new $classname();
		$object->id = $this->id;
		$object->details = $this->details;

		if (isset($object->child_details) && isset($this->child_details)) {
			$object->child_details = $this->child_details;
			unset($object->child_details['id']);
		}

		$object->child_casted_object = $this;
		$classname_field = self::$class_configuration['child_classname_field'];
		$object->$classname_field = $classname;

		return $object;
	}

	/**
	 * Is Dirty
	 *
	 * @access public
	 * @param string $key key
	 * @return bool is dirty ?
	 */
	public function is_dirty(?string $key = null): bool {
		$dirty_fields = $this->get_dirty_fields();
		if (count($dirty_fields) === 0) {
			return false;
		}

		if (!is_null($key) && !array_key_exists($key, $dirty_fields)) {
			return false;
		}

		return true;
	}

	/**
	 * Get dirty fields
	 *
	 * @access public
	 * @return array<mixed> $dirty_fields
	 */
	public function get_dirty_fields(): array {
		return array_merge($this->dirty_fields, $this->object_text_updated);
	}

	/**
	 * Reset dirty fields
	 *
	 * @access public
	 */
	public function reset_dirty_fields(): void {
		$this->dirty_fields = [];
		$this->object_text_updated = [];
		$this->object_text_cache = [];
	}

	/**
	 * Load array
	 *
	 * @access public
	 * @param array<mixed> $details objects
	 */
	public function load_array(array $details): void {
		foreach ($details as $key => $value) {
			$this->$key = $value;
		}
	}

	/**
	 * Get object fields
	 *
	 * @access public
	 * @return array<mixed> $fields
	 */
	public static function get_object_fields(): array {
		$db = self::trait_get_database();
		$table = self::trait_get_database_table();
		return $db->get_table_definition($table);
	}

	/**
	 * trait_get_database_table: finds out which table we need to use
	 *
	 * @access private
	 * @return string table name
	 */
	public static function trait_get_database_table(): string {
		if (property_exists(self::class, 'class_configuration') &&
			isset(self::$class_configuration['database_table'])) {
			return self::$class_configuration['database_table'];
		}
		return strtolower((new \ReflectionClass(self::class))->getShortName());
	}

	/**
	 * Get cache prefix
	 *
	 * @access public
	 * @param mixed $object
	 * @return string key
	 */
	public static function trait_get_cache_prefix(): string {
		if (get_parent_class(get_called_class()) !== false &&
			method_exists(get_parent_class(get_called_class()), 'cache_get')) {
			return get_parent_class(get_called_class());
		}

		if (method_exists(get_called_class(), 'cache_get')) {
			return get_called_class();
		}

		throw new \Exception('Cache not available');
	}

	/**
	 * Get cache key
	 *
	 * @access public
	 * @param mixed $object object
	 * @return string key
	 */
	public static function trait_get_cache_key(mixed $object): string {
		return get_called_class()::trait_get_cache_prefix() . '_' . $object->id;
	}

	/**
	 * Get the details of this object
	 *
	 * @access private
	 */
	protected function get_details(): void {
		$table = self::trait_get_database_table();

		if (!isset($this->id) || $this->id === null) {
			throw new \Exception('Could not fetch ' . $table . ' data: id not set');
		}

		$db = self::trait_get_database();
		$details = $db->get_row(
			'SELECT * FROM ' . $db->quote_identifier($table) . ' WHERE ' .
			self::trait_get_table_field_id() . ' = ?', [ $this->id ]
		);

		if ($details === null) {
			throw new \Exception('Could not fetch ' . $table . ' data: none found with id ' . $this->id);
		}

		$this->id = $details[self::trait_get_table_field_id()];
		$this->details = $details;
		$this->reset_dirty_fields();

		if (method_exists($this, 'trait_get_child_details') && is_callable([$this, 'trait_get_child_details'])) {
			$this->trait_get_child_details();
		}
	}

	/**
	 * trait_get_database_config_name: finds out which database name we need to get
	 *
	 * @access private
	 * @return Database database object
	 */
	protected static function trait_get_database() {
		if (property_exists(self::class, 'class_configuration') &&
			isset(self::$class_configuration['database_config_name'])) {
			return Database::get(self::$class_configuration['database_config_name']);
		}
		return Database::get();
	}

	/**
	 * Trait_get_link_tables
	 *
	 * @access private
	 * @return array<string> linked tables
	 */
	protected static function trait_get_link_tables(): array {
		$db = self::trait_get_database();
		$table = self::trait_get_database_table();
		$fields = $db->get_columns($table);
		$tables = $db->get_tables();

		$joins = [];
		foreach ($fields as $field) {
			if (substr($field, -3) != '_id') {
				continue;
			}

			$link_table = substr($field, 0, -3);

			if (in_array($link_table, $tables)) {
				$joins[] = $link_table;
			}
		}
		return $joins;
	}

	/**
	 * trait_get_table_field_id: get the field that is used as ID
	 *
	 * @access private
	 * @return string identifier
	 */
	protected static function trait_get_table_field_id(): string {
		if (property_exists(self::class, 'class_configuration') &&
			isset(self::$class_configuration['table_field_id'])) {
			return self::$class_configuration['table_field_id'];
		}
		return 'id';
	}

	/**
	 * set an object text
	 *
	 * @access private
	 * @param string $key key
	 * @param mixed $value object
	 */
	private function trait_set_object_text(string $key, mixed $value): void {
		if (!class_exists('\Skeleton\I18n\Object\Text')) {
			throw new \Exception('Skeleton package "skeleton-i18n" needs to be installed to use object text');
		}
		[$language, $label] = explode('_', str_replace('text_', '', $key), 2);

		if (!in_array($label, self::$object_text_fields)) {
			throw new \Exception('Incorrect text field:' . $label);
		}

		if (!isset($this->object_text_cache[$key])) {
			$this->trait_get_object_text($key);
		}

		if ($this->object_text_cache[$key] != $value) {
			$this->object_text_updated[$key] = $this->$key;
			$this->object_text_cache[$key] = $value;
		}
	}

	/**
	 * get an object text
	 *
	 * @access private
	 * @param string $key key
	 * @param string $value object
	 * @return mixed object
	 */
	private function trait_get_object_text(string $key): mixed {
		if (!class_exists('\Skeleton\I18n\Object\Text')) {
			throw new \Exception('Skeleton package "skeleton-i18n" needs to be installed to use object text');
		}

		[$language, $label] = explode('_', str_replace('text_', '', $key), 2);

		if (!in_array($label, self::$object_text_fields)) {
			throw new \Exception('Incorrect text field:' . $label);
		}

		if ($this->id === null && !isset($this->object_text_cache[$key])) {
			$this->object_text_cache[$key] = '';
		}

		if (!isset($this->object_text_cache[$key])) {
			$language_interface = \Skeleton\I18n\Config::$language_interface;
			$language = $language_interface::get_by_name_short($language);
			// Check if the object text label can be found, otherwise return empty string
			$object_text_label = \Skeleton\I18n\Object\Text::get_by_object_label_language($this, $label, $language);
			if ($object_text_label === null) {
				$this->object_text_cache[$key] = '';
			} else {
				$this->object_text_cache[$key] = $object_text_label->content;
			}
		}

		return $this->object_text_cache[$key];
	}

	/**
	 * trait_get_table_field_created: get the field that is used for 'created'
	 *
	 * @access private
	 * @return string date/time created
	 */
	private static function trait_get_table_field_created(): string {
		if (property_exists(self::class, 'class_configuration') &&
			isset(self::$class_configuration['table_field_created'])) {
			return self::$class_configuration['table_field_created'];
		}
		return 'created';
	}

	/**
	 * trait_get_table_field_updated: get the field that is used for 'updated'
	 *
	 * @access private
	 * @return string date/time updated
	 */
	private static function trait_get_table_field_updated(): string {
		if (property_exists(self::class, 'class_configuration') &&
			isset(self::$class_configuration['table_field_updated'])) {
			return self::$class_configuration['table_field_updated'];
		}
		return 'updated';
	}

	/**
	 * trait_get_table_field_archived: get the field that is used for 'archived'
	 *
	 * @access private
	 * @return string date/time archived
	 */
	private static function trait_get_table_field_archived(): string {
		if (property_exists(self::class, 'class_configuration') &&
			isset(self::$class_configuration['table_field_archived'])) {
			return self::$class_configuration['table_field_archived'];
		}
		return 'archived';
	}

	/**
	 * Is Cache enabled
	 *
	 * @access private
	 * @return bool is cache enabled
	 */
	private static function trait_cache_enabled(): bool {
		if (Config::$cache_handler === false) {
			return false;
		}

		if (method_exists(get_called_class(), 'cache_get')) {
			return true;
		}

		if (get_parent_class(get_called_class()) === false) {
			// The class has no parent
			return false;
		}

		if (method_exists(get_parent_class(get_called_class()), 'cache_get')) {
			return true;
		}

		return false;
	}
}
