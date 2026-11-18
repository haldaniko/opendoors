<?php

namespace SearchWP_Related;

// exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class SearchWP_Related\Template
 *
 * Template loader class based on Pippin Williamson's guide
 * http://pippinsplugins.com/template-file-loaders-plugins/
 *
 * @since 1.0
 */
class Template {

	private $post;
	private $post_type;
	private $related;

	/**
	 * Template constructor.
	 */
	function __construct() {
		$this->related = new \SearchWP_Related();
	}

	/**
	 * Initialize
	 */
	function init() {
		if ( apply_filters( 'searchwp_related_auto_append', true ) ) {
			add_filter( 'the_content', array( $this, 'the_content' ) );
		}
	}

	/**
	 * Retrieve the template directory within this plugin
	 *
	 * @return string The template directory within this plugin
	 */
	function get_template_directory() {
		return trailingslashit( SEARCHWP_RELATED_PLUGIN_DIR ) . 'templates';
	}

	/**
	 * Callback to auto-append Related content to the_content
	 *
	 * @param $content
	 *
	 * @return mixed
	 */
	function the_content( $content ) {
		global $post;

		if ( ! is_singular() ) {
			return $content;
		}

		if ( doing_filter( 'get_the_excerpt' ) || doing_filter( 'wp_trim_excerpt' ) ) {
			return $content;
		}

		// Respect excluded post types
		$excluded_post_types = (array) apply_filters( 'searchwp_related_excluded_post_types', array( 'attachment' ) );

		if ( in_array( get_post_type(), $excluded_post_types ) ) {
			return $content;
		}

		$this->post = $post;

		// If post was force skipped: exit early
		$skipped = get_post_meta( $this->post->ID, $this->related->meta_key . '_skip', true );
		if ( ! empty( $skipped ) ) {
			return $content;
		}

		$this->post_type = get_post_type();

		// Check settings for auto-append post type
		$settings = $this->related->settings->get();
		$skip = ! in_array( $this->post_type, $settings['swp-auto-append-post-types'], true );

		// Allow code-based settings override for post type
		$auto_append_this = apply_filters( "searchwp_related_auto_append_{$this->post_type}", true );

		if ( $skip || empty( $auto_append_this ) ) {
			return $content;
		}

		$to_insert = $this->get_template( $this->post_type );

		$inserter = new Content_Inserter( $content, $to_insert );

		if ( isset( $settings['swp-auto-append-logic'], $settings['swp-append-after-paragraph-num'] ) && $settings['swp-auto-append-logic'] === 'after-paragraph' ) {
			$paragraph = absint( $settings['swp-append-after-paragraph-num'] );
			$content   = empty( $paragraph ) ? $inserter->prepend() : $inserter->after_paragraph( $paragraph );
		} else {
			$content = $inserter->append();
		}

		return $content;
	}

	/**
	 * Set up the proper template part array and locate it
	 *
	 * @sine 1.0
	 *
	 * @param null $post_type
	 * @param bool $load Whether to load the template part
	 *
	 * @return bool|string The location of the applicable template file
	 * @internal param string $slug The template slug (without file extension)
	 * @internal param null $name The template name (appended to $slug if provided)
	 */
	function get_template( $post_type = null, $load = true ) {

		// Templates must begin with 'related'
		$slug = 'related';

		// Templates can be customized per post type
		if ( empty( $post_type ) || ! post_type_exists( $post_type ) ) {
			$post_type = get_post_type();
		}

		$templates = array();
		$templates[] = $slug . '-' . $post_type . '.php';
		$templates[] = $slug . '.php';

		// allow filtration of template parts
		$templates = apply_filters( 'searchwp_related_get_template', $templates, $slug, $post_type );

		// return what was found
		return $this->locate_template( $templates, $load, false );
	}

	/**
	 * Get the template for a Gutenberg block.
	 *
	 * @since 1.5.0
	 *
	 * @return string The content of the template.
	 */
	function get_template_gutenberg() {

		return $this->get_template( null, true );
	}

	/**
	 * Check for the applicable template in the child theme, then parent theme, and in the plugin dir as a last resort
	 * and output it if it was located
	 *
	 * @since 1.0
	 *
	 * @param array $template_names The potential template names in order of precedence
	 * @param bool $load Whether to load the template file
	 * @param bool $require_once Whether to require the template file once
	 *
	 * @return bool|string The location of the applicable template file
	 */
	function locate_template( $template_names, $load = false, $require_once = true ) {
		global /** @noinspection PhpUnusedLocalVariableInspection */
		$wp_filesystem, $wp_query;

		// Default is not found
		$located = false;

		$template_dir = apply_filters( 'searchwp_related_template_dir', 'searchwp-related' );

		// try to find the template file
		foreach ( (array) $template_names as $template_name ) {
			if ( empty( $template_name ) ) {
				continue;
			}
			$template_name = ltrim( $template_name, '/' );

			// check the child theme first
			$maybe_child_theme = trailingslashit( get_stylesheet_directory() ) . trailingslashit( $template_dir ) . $template_name;
			if ( file_exists( $maybe_child_theme ) ) {
				$located = $maybe_child_theme;
				break;
			}

			if ( ! $located ) {
				// check parent theme
				$maybe_parent_theme = trailingslashit( get_template_directory() ) . trailingslashit( $template_dir ) . $template_name;
				if ( file_exists( $maybe_parent_theme ) ) {
					$located = $maybe_parent_theme;
					break;
				}
			}

			if ( ! $located ) {
				// check theme compat
				$maybe_theme_compat = trailingslashit( $this->get_template_directory() ) . $template_name;
				if ( file_exists( $maybe_theme_compat ) ) {
					$located = $maybe_theme_compat;
					break;
				}
			}
		}

		$located = apply_filters( 'searchwp_related_template', $located, $this );

		if ( ( true === $load ) && ! empty( $located ) && empty( $require_once ) ) {

			$template_data = $this->get_template_file_data( $located );
			$settings      = $this->related->settings->get();

			// Template 'engine' setting overrides the general 'swp-engine' setting. This is useful for setting up different templates for different post types.
			$template_data['engine'] = sanitize_key( $template_data['engine'] );
			$settings['swp-engine']  = ! empty( $template_data['engine'] ) && \SearchWP\Settings::get_engine_settings( $template_data['engine'] ) ? $template_data['engine'] : $settings['swp-engine'];

			// Template 'per_page' setting overrides the general 'swp-max-results' setting. This is useful for setting up different templates for different post types.
			$template_data['per_page']   = absint( $template_data['per_page'] );
			$settings['swp-max-results'] = ! empty( $template_data['per_page'] ) ? $template_data['per_page'] : $settings['swp-max-results'];

			/**
			 * $searchwp_related is used in the actual template so it must be defined here */
			/** @noinspection PhpUnusedLocalVariableInspection */
			$searchwp_related = $this->get_related_for_post_id(
				get_the_ID(),
				$settings['swp-engine'],
				$settings['swp-max-results']
			);

			$container_classes = self::get_container_classes( $settings );

			ob_start();
			/** @noinspection PhpIncludeInspection */
			include( $located );
			$markup = ob_get_contents();
			ob_end_clean();
			return $markup;
		} else {
			return $located;
		}
	}

	/**
	 * Retrieve configuration details from the template file.
	 *
	 * @since 1.5.0
	 *
	 * @param string $located Absolute path to the template file.
	 *
	 * @return array
	 */
	function get_template_file_data( $located = '' ) {

		include_once ABSPATH . 'wp-admin/includes/file.php';
		WP_Filesystem();

		return get_file_data( $located, array(
			'engine'   => 'SearchWP Engine',
			'per_page' => 'Maximum Results',
		) );
	}

	/**
	 * Get the related content
	 *
	 * @param $post_id
	 * @param string $engine
	 * @param int $per_page
	 *
	 * @return array
	 */
	function get_related_for_post_id( $post_id, $engine = 'default', $per_page = 3 ) {

		$engine = sanitize_key( $engine );
		$engine = \SearchWP\Settings::get_engine_settings( $engine ) ? $engine : 'default';

		$post_id = absint( $post_id );

		if ( empty( $this->post_type ) ) {
			$this->post_type = get_post_type( $post_id );
		}

		if ( empty( $this->post ) ) {
			$this->post = get_post( $post_id );
		}

		// Determine which terms to use
		$terms = get_post_meta( get_the_ID(), $this->related->meta_key, true );
		$terms = apply_filters( 'searchwp_related_keywords', $terms, get_the_ID() );

		// Define arguments
		$args = array(
			'engine'         => $engine,
			's'              => $terms,
			'posts_per_page' => absint( $per_page ),
		);

		$args['posts_per_page'] = absint( apply_filters( "searchwp_related_posts_per_page_{$post_id}", $args['posts_per_page'] ) );

		$settings = $this->related->settings->get();

		// Check for exclusions from settings.
		if ( isset( $settings['swp-post-exclude-logic'] ) && $settings['swp-post-exclude-logic'] === 'not_in' ) {
			$excluded = array_map( 'trim', $settings['swp-post-exclude-ids-not-in'] );
			$excluded = array_map( 'absint', $excluded );
			$args['post__not_in'] = $excluded;
		}

		// Check for limitations from settings.
		if ( isset( $settings['swp-post-exclude-logic'] ) && $settings['swp-post-exclude-logic'] === 'in' ) {
			$limitations = array_map( 'trim', $settings['swp-post-exclude-ids-in'] );
			$limitations = array_map( 'absint', $limitations );
			$args['post__in'] = $limitations;
		}

		// Allow late filtration of args for this template call.
		$args = apply_filters( 'searchwp_related_template_args', $args, $this->post );

		$related_ids = $this->related->get( $args );

		// If there isn't any Related content, make sure there's no Related content!
		if ( empty( $related_ids ) ) {
			$related_ids = array( 0 );
		}

		// Get objects for IDs
		$final_args = apply_filters( 'searchwp_related_template_cache_args', array(
			'post_type'      => 'any', // Will be limited by the post__in
			'post_status'    => 'any', // Will be limited by the post__in
			'post__in'       => $related_ids,
			'posts_per_page' => $args['posts_per_page'],
			'orderby'        => 'post__in',
		) );

		$related_posts = get_posts( $final_args );

		return $related_posts;
	}

	/**
	 * Get Related block container classes.
	 *
	 * @since 1.5.0
	 *
	 * @param array $settings Related settings.
	 *
	 * @return string
	 */
	private static function get_container_classes( $settings ) {

		$classes = [];

		if ( $settings['swp-layout-style'] === 'list' ) {
			$classes[] = 'searchwp-related-list';
		}

		if ( $settings['swp-layout-style'] === 'grid' ) {
			$classes[] = 'searchwp-related-grid';

			$max_results = ! empty( $settings['swp-max-results'] ) ? absint( $settings['swp-max-results'] ) : null;
			if ( ! empty( $max_results ) ) {
				$classes[] = 'searchwp-related-grid--cols-' . $max_results;
			}
		}

		$image_size = $settings['swp-image-size'];
		if ( $image_size === 'small' ) {
			$classes[] = 'searchwp-related--img-sm';
		}
		if ( $image_size === 'medium' ) {
			$classes[] = 'searchwp-related--img-m';
		}
		if ( $image_size === 'large' ) {
			$classes[] = 'searchwp-related--img-l';
		}

		return implode( ' ', $classes );
	}

	/**
	 * Get the result data to display in the template.
	 *
	 * @since 1.5.0
	 *
	 * @param \WP_Post|\WP_User|\WP_Term|mixed $result Result object.
	 *
	 * @return array
	 */
	public static function get_display_data( $result ) {

		$data = [];

		if ( $result instanceof \WP_Post ) {
			$data = [
				'id'         => absint( $result->ID ),
				'type'       => get_post_type( $result ),
				'title'      => get_the_title( $result ),
				'permalink'  => get_the_permalink( $result ),
				'image_html' => get_the_post_thumbnail( $result ),
				'content'    => get_the_excerpt( $result ),
			];
		}

		if ( $result instanceof \WP_User ) {
			$data = [
				'id'         => absint( $result->ID ),
				'type'       => 'user',
				'title'      => $result->data->display_name,
				'permalink'  => get_author_posts_url( $result->data->ID ),
				'image_html' => get_avatar( $result->data->ID ),
				'content'    => get_the_author_meta( 'description', $result->data->ID ),
			];
		}

		if ( $result instanceof \WP_Term ) {
			$data = [
				'id'         => absint( $result->term_id ),
				'type'       => 'taxonomy-term',
				'title'      => $result->name,
				'permalink'  => get_term_link( $result->term_id, $result->taxonomy ),
				'image_html' => '',
				'content'    => $result->description,
			];
		}

		$defaults = [
			'id'         => 0,
			'type'       => 'unknown',
			'title'      => '',
			'permalink'  => '',
			'image_html' => '',
			'content'    => '',
		];

		$data = apply_filters( 'searchwp_related_results_entry_data', empty( $data ) ? $defaults : $data, $result );

		// Make sure that default array structure is preserved.
		return is_array( $data ) ? array_merge( $defaults, $data ) : $defaults;
	}
}
