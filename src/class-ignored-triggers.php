<?php
/**
 * Manages ignored error trigger points using WordPress transients
 *
 * @package DevKabir\WPDebugger
 */

namespace DevKabir\WPDebugger;

/**
 * Class Ignored_Triggers
 * Handles storage and checking of ignored error trigger points
 */
class Ignored_Triggers {

	/**
	 * Transient key for storing ignored triggers
	 */
	private const TRANSIENT_KEY = 'wp_debugger_ignored_triggers';

	/**
	 * Duration to ignore triggers (1 day in seconds)
	 */
	private const IGNORE_DURATION = DAY_IN_SECONDS;

	/**
	 * Add a trigger point to the ignored list
	 *
	 * @param string $file The file path where the error occurred.
	 * @param int    $line The line number where the error occurred.
	 *
	 * @return bool True if successfully added, false otherwise.
	 */
	public static function add_trigger( string $file, int $line ): bool {
		$ignored = self::get_all();
		$key     = self::generate_key( $file, $line );

		// Add trigger with expiration timestamp
		$ignored[ $key ] = array(
			'file'       => $file,
			'line'       => $line,
			'expires_at' => time() + self::IGNORE_DURATION,
		);

		return set_transient( self::TRANSIENT_KEY, $ignored, self::IGNORE_DURATION );
	}

	/**
	 * Check if a trigger point should be ignored
	 *
	 * @param string $file The file path to check.
	 * @param int    $line The line number to check.
	 *
	 * @return bool True if should be ignored, false otherwise.
	 */
	public static function should_ignore( string $file, int $line ): bool {
		$ignored = self::get_all();
		$key     = self::generate_key( $file, $line );

		if ( ! isset( $ignored[ $key ] ) ) {
			return false;
		}

		$trigger = $ignored[ $key ];

		// Check if the trigger has expired
		if ( isset( $trigger['expires_at'] ) && $trigger['expires_at'] < time() ) {
			// Remove expired trigger
			unset( $ignored[ $key ] );
			set_transient( self::TRANSIENT_KEY, $ignored, self::IGNORE_DURATION );
			return false;
		}

		return true;
	}

	/**
	 * Get all ignored triggers
	 *
	 * @return array Array of ignored triggers.
	 */
	private static function get_all(): array {
		$ignored = get_transient( self::TRANSIENT_KEY );
		return is_array( $ignored ) ? $ignored : array();
	}

	/**
	 * Generate a unique key for a trigger point
	 *
	 * @param string $file The file path.
	 * @param int    $line The line number.
	 *
	 * @return string Unique key for the trigger point.
	 */
	private static function generate_key( string $file, int $line ): string {
		return md5( $file . ':' . $line );
	}

	/**
	 * Clear all ignored triggers
	 *
	 * @return bool True if successfully cleared, false otherwise.
	 */
	public static function clear_all(): bool {
		return delete_transient( self::TRANSIENT_KEY );
	}

	/**
	 * Remove a specific trigger from the ignored list
	 *
	 * @param string $file The file path.
	 * @param int    $line The line number.
	 *
	 * @return bool True if successfully removed, false otherwise.
	 */
	public static function remove_trigger( string $file, int $line ): bool {
		$ignored = self::get_all();
		$key     = self::generate_key( $file, $line );

		if ( ! isset( $ignored[ $key ] ) ) {
			return false;
		}

		unset( $ignored[ $key ] );

		if ( empty( $ignored ) ) {
			return delete_transient( self::TRANSIENT_KEY );
		}

		return set_transient( self::TRANSIENT_KEY, $ignored, self::IGNORE_DURATION );
	}
}
