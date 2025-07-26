<?php

declare(strict_types=1);

/**
 * trait: Save
 *
 * @author Christophe Gosiau <christophe.gosiau@tigron.be>
 * @author Gerry Demaret <gerry.demaret@tigron.be>
 * @author David Vandemaele <david@tigron.be>
 */

namespace Skeleton\Object;

class Exception_Validation extends \Exception {

	private $errors = [];

	public function __construct($errors) {
		$this->errors = $errors;
		$this->message = 'Validation error! The following fields contain errors: ' . implode(', ', array_keys($this->errors));
	}

	public function get_errors() {
		return $this->errors;
	}

}

trait Save {

	/**
	 * Save the object
	 *
	 * @access public
	 */
	public function save($validate = true) {
		// If we have a validate() method, execute it
		if (method_exists($this, 'validate') && is_callable([$this, 'validate']) && $validate) {
			$errors = [];

			if ($this->validate($errors) === false) {
				throw new Exception_Validation($errors);
			}
		}

		$table = self::trait_get_database_table();
		$db = self::trait_get_database();

		/**
		 * Set the 'created' and 'updated' fields
		 */
		if (!isset($this->id) || $this->id === null) {
			if (!isset($this->details[ self::trait_get_table_field_created()])) {
				$this->details[ self::trait_get_table_field_created() ] = date('Y-m-d H:i:s');
			}
		} else {
			if (!$this->is_dirty( self::trait_get_table_field_updated() )) {
				$this->details[ self::trait_get_table_field_updated() ] = date('Y-m-d H:i:s');
			}
		}

		/**
		 * Generate the slug if trait is added
		 */
		if (
			method_exists($this, 'generate_slug')
			&& is_callable([$this, 'generate_slug'])
		) {
			$this->details['slug'] = $this->generate_slug();
		}

		/**
		 * Generate a UUID if trait is added
		 */
		if (
			method_exists($this, 'generate_uuid')
			&& is_callable([$this, 'generate_uuid'])
			&& (!isset($this->details['uuid']) || $this->details['uuid'] == '')
		) {
			$this->details['uuid'] = $this->generate_uuid();
		}

		/**
		 * Generate a unique number if the trait is added
		 */
		if (
			method_exists($this, 'generate_number')
			&& is_callable([$this, 'generate_number'])
		) {
			$this->generate_number();
		}

		if (!isset($this->id) || $this->id === null) {
			$db->insert($table, $this->details);
			$this->id = $db->get_insert_id($table, self::trait_get_table_field_id());

			if ($this->id === null) {
				throw new \Exception('Object was not properly saved, id is still null');
			}
		} else {
			$where = self::trait_get_table_field_id() . '=' . $db->quote($this->id);
			$db->update($table, $this->details, $where);
		}

		/**
		 * Store object_text fields
		 */
		if (class_exists('\Skeleton\I18n\Object\Text')) {
			foreach ($this->object_text_updated as $key => $value) {
				list($language, $label) = explode('_', str_replace('text_', '', $key), 2);
				$language_interface = \Skeleton\I18n\Config::$language_interface;
				$language = $language_interface::get_by_name_short($language);
				$object_text = \Skeleton\I18n\Object\Text::get_by_object_label_language($this, $label, $language);
				$object_text->content = $this->object_text_cache[$key];
				$object_text->save();

				if (get_called_class()::trait_cache_enabled()) {
					$key = \Skeleton\I18n\Object\Text::trait_get_cache_key($object_text);
					get_called_class()::cache_delete($key);
					get_called_class()::cache_set($key, $object_text);
				}
			}
		}

		/**
		 * If Child trait is added, we need to store child-specific data in
		 * a separate table
		 */
		if (method_exists($this, 'trait_child_save') && is_callable([$this, 'trait_child_save'])) {
			$this->trait_child_save();
		}

		if ($this->child_casted_object !== null) {
			if (is_callable([ $this->child_casted_object, 'trait_child_delete' ])) {
				$this->child_casted_object->trait_child_delete();
			}
			$this->child_casted_object = null;
		}

		/**
		 * Refresh the object
		 */
		$this->get_details();

		/**
		 * Cache invalidation
		 */
		if (get_called_class()::trait_cache_enabled()) {
			get_called_class()::cache_delete(get_called_class()::trait_get_cache_key($this));
		}

	}
}
