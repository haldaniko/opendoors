<?php

namespace SearchWP_Related;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Migrator class.
 *
 * @since 1.5.0
 */
class Migrator {

	/**
	 * Migration routine.
	 *
	 * @since 1.5.0
	 */
	public static function maybe_migrate() {

		$last_version = get_option( 'searchwp_related_version' );

		if ( ! empty( $last_version ) && version_compare( $last_version, SEARCHWP_RELATED_VERSION, '>=' ) ) {
			return;
		}

		if ( version_compare( $last_version, '1.5.0', '<' ) && version_compare( SEARCHWP_RELATED_VERSION, '1.5.0', '>=' ) ) {
			self::migrate_to_v1_5();
		}

		update_option( 'searchwp_related_version', SEARCHWP_RELATED_VERSION );

		if ( ! empty( $last_version ) ) {
			update_option( 'searchwp_related_version_upgraded_from', $last_version );
		}
	}

	/**
	 * Migrate settings to v1.5.0+.
	 *
	 * @since 1.5.0
	 */
	public static function migrate_to_v1_5() {

		$plugin_settings = new \SearchWP_Related_Settings();

		$settings = $plugin_settings->get_option();

		if ( empty( $settings ) ) {
			return;
		}

		if ( ! isset( $settings['auto_append'] ) && ! isset( $settings['post__not_in'] ) && ! isset( $settings['post__in'] ) ) {
			return;
		}

		if ( ! empty( $settings['auto_append'] ) && is_array( $settings['auto_append'] ) ) {
			$settings['swp-auto-append-post-types'] = array_keys( $settings['auto_append'] );
		}

		if ( ! empty( $settings['post__not_in'] ) && is_array( $settings['post__not_in'] ) ) {
			$settings['swp-post-exclude-ids-not-in'] = [];
			foreach ( $settings['post__not_in'] as $post__not_in ) {
				$post__not_in = explode( ',' ,\SearchWP\Utils::get_integer_csv_string_from( $post__not_in ) );
				$settings['swp-post-exclude-ids-not-in'] = array_merge( $settings['swp-post-exclude-ids-not-in'], $post__not_in );
			}
			$settings['swp-post-exclude-ids-not-in'] = array_unique( array_filter( array_map( 'absint', $settings['swp-post-exclude-ids-not-in'] ) ) );
		}

		if ( ! empty( $settings['post__in'] ) && is_array( $settings['post__in'] ) ) {
			$settings['swp-post-exclude-ids-in'] = [];
			foreach ( $settings['post__in'] as $post__in ) {
				$post__in = explode( ',' ,\SearchWP\Utils::get_integer_csv_string_from( $post__in ) );
				$settings['swp-post-exclude-ids-in'] = array_merge( $settings['swp-post-exclude-ids-in'], $post__in );
			}
			$settings['swp-post-exclude-ids-in'] = array_unique( array_filter( array_map( 'absint', $settings['swp-post-exclude-ids-in'] ) ) );
			if ( ! empty( $settings['swp-post-exclude-ids-in'] ) ) {
				$settings['swp-post-exclude-logic'] = 'in';
			}
		}

		$plugin_settings->set( $settings );
	}
}
