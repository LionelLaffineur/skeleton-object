<?php

declare(strict_types=1);

/**
 * trait: Delete
 *
 * @author Christophe Gosiau <christophe.gosiau@tigron.be>
 * @author Gerry Demaret <gerry.demaret@tigron.be>
 * @author David Vandemaele <david@tigron.be>
 */

namespace Skeleton\Object;

trait Delete {

	/**
	 * Delete
	 *
	 * @access public
	 */
	public function delete() {
		if (method_exists($this, 'trait_child_delete') and is_callable([$this, 'trait_child_delete'])) {
			$this->trait_child_delete();
		}

		$table = self::trait_get_database_table();
		$db = self::trait_get_database();

		if (isset(self::$object_text_fields)) {
			if (!class_exists('\Skeleton\I18n\Object\Text')) {
				throw new \Exception('Skeleton package "skeleton-i18n" needs to be installed to use object text');
			}

			$object_texts = \Skeleton\I18n\Object\Text::get_by_object($this);
			foreach ($object_texts as $object_text) {
				$object_text->delete();
			}
		}

		if (self::trait_cache_enabled()) {
			self::cache_delete(get_called_class()::trait_get_cache_key($this));
		}

		$db->query('DELETE FROM ' . $db->quote_identifier($table) . ' WHERE ' . self::trait_get_table_field_id() . '=?', [$this->id]);
	}

	/**
	 * Archive
	 *
	 * @access public
	 * @param bool $validate
	 */
	public function archive($validate = true) {
		$this->archived = date('Y-m-d H:i:s');
		$this->save($validate);
	}

	/**
	 * Restore
	 *
	 * @access public
	 * @param bool $validate
	 */
	public function restore($validate = true) {
		$this->archived = null;
		$this->save($validate);
	}
}
