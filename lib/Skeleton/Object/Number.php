<?php

declare(strict_types=1);

/**
 * trait: generate_number
 *
 * @author Christophe Gosiau <christophe.gosiau@tigron.be>
 * @author Gerry Demaret <gerry.demaret@tigron.be>
 */

namespace Skeleton\Object;

trait Number {
	/**
	 * Generate a uuid
	 *
	 * @access protected
	 */
	protected function generate_number(): void {
		$number_field = null;
		$number_dividers = [];

		/**
		 * We need to know the fields that divide the numbers groups of unique numbers
		 */
		if (property_exists(__CLASS__, 'class_configuration') && isset(self::$class_configuration['number_dividers'])) {
			$number_dividers = self::$class_configuration['number_dividers'];
		}

		/**
		 * If there are no dividers, we cannot proceed
		 */
		if (!is_array($number_dividers) or count($number_dividers) === 0) {
			return;
		}

		/**
		 * Which field do we need to store the number
		 */
		if (property_exists(__CLASS__, 'class_configuration') && isset(self::$class_configuration['number_field'])) {
			$number_field = self::$class_configuration['number_field'];
		}

		/**
		 * If there is no number field, we cannot proceed
		 */
		if (empty($number_field)) {
			return;
		}

		/**
		 * If the number-field is already set, don't create a number
		 */
		if (!empty($this->details[$number_field])) {
			return;
		}

		$table = self::trait_get_database_table();
		$db = self::trait_get_database();

		/**
		 * Find the highest number for the given dividers
		 */
		$conditions = [];
		foreach ($number_dividers as $number_divider) {
			if (empty($this->$number_divider)) {
				throw new \Exception(
					'Cannot create number for ' . __CLASS__ . '. Number divider ' . $number_divider . ' is empty'
				);
			}
			$conditions[$number_divider] = $this->$number_divider;
		}

		$condition = ' WHERE 1';
		foreach ($conditions as $field => $value) {
			$condition .= ' AND ' . $db->quote_identifier($field) . ' = ' . $db->quote($value);
		}

		$max_number = $db->get_one(
			'SELECT MAX(' . $number_field . ') FROM ' . $db->quote_identifier($table) . $condition, [ ]
		);
		$max_number++;
		$this->details[$number_field] = $max_number;
	}
}
