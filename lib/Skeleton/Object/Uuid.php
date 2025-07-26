<?php

declare(strict_types=1);

/**
 * trait: generate_uuid
 *
 * @author Christophe Gosiau <christophe.gosiau@tigron.be>
 * @author Gerry Demaret <gerry.demaret@tigron.be>
 * @author David Vandemaele <david.vandemaele@tigron.be>
 */

namespace Skeleton\Object;

trait Uuid {

	/**
	 * Generate a uuid
	 *
	 * @access protected
	 */
	protected function generate_uuid($version = 4) {
		do {
			$hash = bin2hex(random_bytes(16));

			// Based on Andrew Moore's comment:
			// https://www.php.net/manual/en/function.uniqid.php#94959
			$uuid = sprintf('%08s-%04s-%04x-%04x-%12s',
				// 32 bits for "time_low"
				substr($hash, 0, 8),
				// 16 bits for "time_mid"
				substr($hash, 8, 4),
				// 16 bits for "time_hi_and_version",
				// four most significant bits holds version number (4)
				(hexdec(substr($hash, 12, 4)) & 0x0fff) | 4 << 12,
				// 16 bits, 8 bits for "clk_seq_hi_res",
				// 8 bits for "clk_seq_low",
				// two most significant bits holds zero and one for variant DCE1.1
				(hexdec(substr($hash, 16, 4)) & 0x3fff) | 0x8000,
				// 48 bits for "node"
				substr($hash, 20, 12)
			);

			$unique = false;

			try {
				self::get_by_uuid($uuid);
			} catch (\Exception $e) {
				$unique = true;
			}
		} while ($unique === false);

		return $uuid;
	}

	/**
	 * Get by UUID
	 *
	 * @access public
	 * @param string $uuid
	 * @return Object $object
	 */
	public static function get_by_uuid($uuid) {
		$table = self::trait_get_database_table();
		$db = self::trait_get_database();

		$id = $db->get_one('SELECT ' . self::trait_get_table_field_id() . ' FROM ' . $db->quote_identifier($table) . ' WHERE uuid=?', [$uuid]);

		if ($id === null) {
			throw new \Exception('Object not found');
		}

		return self::get_by_id($id);
	}
}
