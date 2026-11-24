<?php
/**
 * Plugin Name: OpenDoors Content Migrations
 * Description: Applies small, idempotent content updates to existing databases.
 */

if (!defined('ABSPATH')) {
	exit;
}

add_action('init', static function (): void {
	global $wpdb;

	$migration = 'opendoors_content_migration_20261124_projects_button';

	if (get_option($migration)) {
		return;
	}

	$old_fragment = 'href="https://opendoors.center/what-we-do/"><span class="has-text-color stk-button__inner-text">What we do</span>';
	$new_fragment = 'href="https://opendoors.center/projects/"><span class="has-text-color stk-button__inner-text">What we do</span>';
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
