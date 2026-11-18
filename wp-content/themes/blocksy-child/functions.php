<?php
if (!defined('WP_DEBUG')) {
	die('Direct access forbidden.');
}

if (!defined('WP_DEBUG')) {
	die('Direct access forbidden.');
}

define('THEME_DIR', __DIR__);

/* Class requirements */

// Load Composer dependencies.
require_once __DIR__ . '/vendor/autoload.php';

// Initialize Timber.
Timber\Timber::init();

if (!function_exists('PrintArray')) {
	function PrintArray($array) {

		if (WP_DEBUG) {
			echo "<pre>";
			print_r($array);
			echo "</pre>";
		}
	}
}

if (!function_exists('addFileVersion')) {
	function addFileVersion($file_path) {

		$version_time = @filemtime($file_path);

		if (!$version_time) {
			//Fallback if mtime could not be found:
			$version_time = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
		}

		return $version_time;
	}
}

if (file_exists(dirname(__FILE__) . '/includes/TpvTheme.php')) {
	include_once dirname(__FILE__) . '/includes/TpvTheme.php';
}

define('THEME_URL', get_bloginfo('url') . "/wp-content/themes/blocksy-child/assets/js");

/**
 * Suppress plugin update notifications.
 *
 * Add plugin basenames to the array below to suppress their update checks.
 * To find a plugin's basename, look for the constant definition like:
 *   define('WP_REDIS_BASENAME', plugin_basename(WP_REDIS_FILE));
 * in the plugin's main file — the value is 'redis-cache/redis-cache.php'.
 */
add_filter('pre_set_site_transient_update_plugins', function ($transient) {
	$suppressed = [
		'redis-cache/redis-cache.php',
		// Add more plugin basenames here, e.g.:
		// 'my-plugin/my-plugin.php',
	];

	foreach ($suppressed as $plugin) {
		if (isset($transient->response[$plugin])) {
			unset($transient->response[$plugin]);
		}
	}

	return $transient;
});

// The imported Blocksy font CSS points to production uploads, whose responses do
// not allow fonts to be used by the local origin. Google Fonts serves these same
// font paths with cross-origin access enabled.
function opendoors_local_font_css($css) {
	if (wp_get_environment_type() !== 'development' || !is_string($css)) {
		return $css;
	}

	return str_replace(
		[
			'https://opendoors.center/wp-content/uploads/blocksy/local-google-fonts/',
			'http://opendoors.center/wp-content/uploads/blocksy/local-google-fonts/',
		],
		'https://fonts.gstatic.com/',
		$css
	);
}

add_filter('option_blocksy_ext_local_google_fonts_settings', function ($settings) {
	if (is_array($settings) && isset($settings['cached_css'])) {
		$settings['cached_css'] = opendoors_local_font_css($settings['cached_css']);
	}

	return $settings;
});

// Blocksy caches the generated CSS separately from its font settings.
add_filter('transient_blocksy_dynamic_styles_descriptor', function ($descriptor) {
	if (is_array($descriptor) && isset($descriptor['styles']) && is_array($descriptor['styles'])) {
		foreach ($descriptor['styles'] as $device => $css) {
			$descriptor['styles'][$device] = opendoors_local_font_css($css);
		}
	}

	return $descriptor;
});

add_filter('blocksy:dynamic-css:has_files_cache', function ($has_files) {
	return wp_get_environment_type() === 'development' ? false : $has_files;
}, 99);
