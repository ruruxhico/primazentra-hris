<?php

/**
 * The goal of this file is to allow developers a location
 * where they can overwrite core procedural functions and
 * replace them with their own. This file is loaded during
 * the bootstrap process and is called during the framework's
 * execution.
 *
 * This can be looked at as a `master helper` file that is
 * loaded early on, and may also contain additional functions
 * that you'd like to use throughout your entire application
 *
 * @see: https://codeigniter.com/user_guide/extending/common.html
 */

if (! function_exists('view_text')) {
	/**
	 * Normalize mixed values from Firestore/view data into a plain string.
	 * This keeps IDE diagnostics from inferring array unions at view escape points.
	 */
	function view_text(mixed $value, string $default = ''): string
	{
		if ($value === null) {
			return $default;
		}

		if (is_string($value) || is_int($value) || is_float($value) || is_bool($value)) {
			return (string) $value;
		}

		if (is_object($value) && method_exists($value, '__toString')) {
			return (string) $value;
		}

		return $default;
	}
}
