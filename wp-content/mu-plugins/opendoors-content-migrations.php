<?php
/**
 * Plugin Name: OpenDoors Content Migrations
 * Description: Applies small, idempotent content updates to existing databases.
 */

if (!defined('ABSPATH')) {
	exit;
}

const OPENDOORS_OLD_WHAT_WE_DO_URL = 'https://opendoors.center/what-we-do/';
const OPENDOORS_PROJECTS_URL = 'https://opendoors.center/projects/';

/**
 * Keep the homepage CTA correct even if an older post revision or cached
 * database value is restored later.
 */
add_filter('render_block', static function (string $block_content): string {
	if (false === strpos($block_content, '>What we do</span>')) {
		return $block_content;
	}

	return str_replace(
		OPENDOORS_OLD_WHAT_WE_DO_URL,
		OPENDOORS_PROJECTS_URL,
		$block_content
	);
});

/**
 * Preserve existing bookmarks and stale cached copies of the old CTA.
 */
add_action('template_redirect', static function (): void {
	if ('/what-we-do/' !== wp_parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH)) {
		return;
	}

	wp_safe_redirect(home_url('/projects/'), 301, 'OpenDoors');
	exit;
}, 0);

add_action('init', static function (): void {
	global $wpdb;

	$migration = 'opendoors_content_migration_20261124_projects_button';

	if (get_option($migration)) {
		return;
	}

	$old_fragment = 'href="' . OPENDOORS_OLD_WHAT_WE_DO_URL . '"><span class="has-text-color stk-button__inner-text">What we do</span>';
	$new_fragment = 'href="' . OPENDOORS_PROJECTS_URL . '"><span class="has-text-color stk-button__inner-text">What we do</span>';
	$matching_ids = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts} WHERE post_content LIKE %s",
			'%' . $wpdb->esc_like($old_fragment) . '%'
		)
	);

	if ($matching_ids) {
		$updated = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->posts}
				SET post_content = REPLACE(post_content, %s, %s)
				WHERE post_content LIKE %s",
				$old_fragment,
				$new_fragment,
				'%' . $wpdb->esc_like($old_fragment) . '%'
			)
		);

		if (false === $updated) {
			error_log('OpenDoors content migration failed: ' . $wpdb->last_error);
			return;
		}

		foreach ($matching_ids as $post_id) {
			clean_post_cache((int) $post_id);
		}
	}

	update_option($migration, gmdate('c'), false);
});
