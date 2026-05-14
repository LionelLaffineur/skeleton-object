<?php
/**
 * trait: Slug
 *
 * @author Christophe Gosiau <christophe.gosiau@tigron.be>
 * @author Gerry Demaret <gerry.demaret@tigron.be>
 * @author David Vandemaele <david.vandemaele@tigron.be>
 */

namespace Skeleton\Object;

trait Slug {

	/**
	 * Get base for slug
	 *
	 * @access private
	 * @return string $slug_base
	 */
	private function trait_slug_get_base(): string {
		$sluggable_field = 'name';

		if (property_exists(__CLASS__, 'class_configuration') AND isset(self::$class_configuration['sluggable'])) {
			$sluggable_field = self::$class_configuration['sluggable'];
		}

		if (isset($this->details[$sluggable_field])) {
			return $this->details[$sluggable_field];
		}

		if (isset(self::$object_text_fields) AND in_array($sluggable_field, self::$object_text_fields)) {
			if (!class_exists('\Skeleton\I18n\Object\Text')) {
				throw new \Exception('Skeleton package "skeleton-i18n" needs to be installed to use object text');
			}

			$language_interface = \Skeleton\I18n\Config::$language_interface;
			$base_language = $language_interface::get_base();
			$sluggable_field = 'text_' . $base_language->name_short . '_' . $sluggable_field;
			if (isset($this->$sluggable_field)) {
				return $this->$sluggable_field;
			}
		}

		throw new \Exception('No base found to generate slug');
	}

	/**
	 * Does the slug needs slug
	 * Indicate if the slug should be regenerated
	 *
	 * @access private
	 * @return bool $regenerate
	 */
	private function trait_slug_needs_regeneration(): bool {
		/**
		 * We believe slugs should not be regenerated. They serve a specific
		 * purpose: provide a permanent url. Changing them could lead to dead
		 * links.
		 * However, it could be that you do want to regenerate the slug (ex
		 * based on user input or dirty fields).
		 */
		return false;
	}

	/**
	 * Make slug unique
	 *
	 * @access private
	 * @param string $slug
	 * @return string $unique_slug
	 */
	private function trait_slug_unique($slug): string {
		while (true) {
			try {
				$object = self::get_by_slug($slug);

				if ($this->id === null || $this->id !== $object->id) {
					$slug = $slug . bin2hex(random_bytes(1));
				}

				if ($this->id !== null && $this->id === $object->id) {
					throw new \Exception('Nothing has changed');
				}
			} catch (\Exception $e) {
				// If the slug was not found, we're good to go
				break;
			}
		}

		return $slug;
	}

	/**
	 * Generate a slug
	 *
	 * @access private
	 * @return string $slug
	 */
	private function generate_slug() {
		$slug_base = $this->trait_slug_get_base();

		if (empty($slug_base)) {
			throw new \Exception('Slug base cannot be empty');
		}

		if (isset($this->id) AND $this->trait_slug_needs_regeneration() === false and !empty($this->details['slug'])) {
			return $this->details['slug'];
		}

		// "Any-Latin": transliterate to latin while preserving what we can
		// "NFD; [:Nonspacing Mark:] Remove; NFC": move accents into separate characters, remove the accents
		// "Lower()": lowercase the end result
		$slug = transliterator_transliterate('Any-Latin; NFD; [:Nonspacing Mark:] Remove; NFC; Lower()', $slug_base);

		// "[:Punctuation:] Remove": replace any character in the unicode punctuation category with dashes
		$slug = preg_replace('/\p{P}/', '-', $slug);

		// Replace leftover non-alphanumerics with dashes
		$slug = preg_replace('/[^A-Za-z0-9 ]/', '-', $slug);

		// Replace spaces and consecutive dashes with single dashes
		$slug = preg_replace('/[-\s]+/', '-', $slug);

		// Remove any leading or trailing dashes
		$slug = trim($slug, '-');

		/**
		 * If slug regenerate was enabled, we could end up generating the
		 * same slug as we already had
		 */
		if (isset($this->details['slug']) and $this->details['slug'] == $slug) {
			return $this->details['slug'];
		}

		/**
		 * Make the slug unique
		 */
		return $this->trait_slug_unique($slug);
	}

	/**
	 * get by slug
	 *
	 * @access public
	 * @param string $name
	 * @return Object $object
	 */
	public static function get_by_slug($slug) {
		$table = self::trait_get_database_table();
		$db = self::trait_get_database();

		$id = $db->get_one('SELECT ' . self::trait_get_table_field_id() . ' FROM ' . $db->quote_identifier($table) . ' WHERE slug=?', [$slug]);
		if ($id === null) {
			throw new \Exception('Object not found');
		}

		return self::get_by_id($id);
	}
}
