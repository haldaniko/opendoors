<?php

class TpvTheme {

	protected static $_instance;

	public function __construct() {

		/*-----------------------------------------------------------------------------------*/
		/*  Script Control  */
		/*-----------------------------------------------------------------------------------*/

		add_action('wp_enqueue_scripts', array($this, '_load_scripts'), 10);
		add_action('wp_enqueue_scripts', array($this, '_load_styles'), 5);
		//		add_action( 'wp_enqueue_scripts', array( $this, '_unload_styles' ), 9999 );
		//		add_action( 'wp_enqueue_scripts', array( $this, '_unload_scripts' ), 999 );

		add_action('wp_head', array($this, 'include_templates'));

		/*-----------------------------------------------------------------------------------*/
		/*  Theme Settings  */
		/*-----------------------------------------------------------------------------------*/

		add_action('wp_dashboard_setup', array($this, 'remove_dashboard_widgets'));
		add_action('init', array($this, 'register_post_type_projects'));
		add_action('init', array($this, 'register_project_taxonomy'));
		add_action('wp_head', array($this, 'enable_site_favicon'), 5);
		add_filter('register_block_type_args', array($this, "register_anchor_field"), 10, 2);
		add_action('enqueue_block_editor_assets', array($this, "register_anchor_field_script"));

		// Check if acf plugin is activated
		if (function_exists('get_field')) {
			add_shortcode('media_project', array($this, 'create_project_media_shortcode'));
			add_shortcode('partner_logos', array($this, 'create_partner_logos_shortcode'));
			add_shortcode('team_members', array($this, 'create_team_members_shortcode'));
		}
	}

	public static function init() {
		static::$_instance = new static();
	}

	public static function get_instance() {
		if (is_null(static::$_instance)) {
			throw new \RuntimeException('You must initialize this instance before gaining access to it');
		}

		return static::$_instance;
	}

	public function _load_scripts() {
		wp_enqueue_script('bootstrap-script', get_stylesheet_directory_uri() . '/assets/css/bootstrap-5.2.3/js/bootstrap.min.js', array('jquery'), '5.2.3', true);
		wp_enqueue_script('site', get_stylesheet_directory_uri() . '/dist/site-dist.js', array('jquery'), $this->addFileVersion(get_stylesheet_directory() . '/dist/site-dist.js'), true);
	}

	public function _load_styles() {
		wp_enqueue_style('blocksy-child', get_stylesheet_directory_uri() . '/assets/css/styles.css', '', $this->addFileVersion(get_stylesheet_directory() . '/assets/css/styles.css'), 'all');
	}


	public function _unload_styles() {
	}

	public function _unload_scripts() {
	}

	public function load_widgets() {
	}


	public function remove_dashboard_widgets() {

		remove_meta_box('dashboard_site_health', 'dashboard', 'normal');   // Wordpress Health Status
		remove_meta_box('dashboard_quick_press', 'dashboard', 'side');  // Quick Press
		remove_meta_box('dashboard_primary', 'dashboard', 'side');   // WordPress blog

	}

	private function addFileVersion($file_path) {
		$version_time = @filemtime($file_path);

		if (!$version_time) {
			//Fallback if mtime could not be found:
			$version_time = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
		}

		return $version_time;
	}

	public function include_templates() {
		if (file_exists(dirname(__FILE__) . '/../views/templates/modal.php')) {
			include_once dirname(__FILE__) . '/../views/templates/modal.php';
		}
	}

	// Register custom post type - projects
	public function register_post_type_projects() {
		$labels = array(
			'name'               => 'Projects',
			'singular_name'      => 'Project',
			'menu_name'          => 'Projects',
			'name_admin_bar'     => 'Project',
			'add_new'            => 'Add New',
			'add_new_item'       => 'Add New Project',
			'new_item'           => 'New Project',
			'edit_item'          => 'Edit Project',
			'view_item'          => 'View Project',
			'all_items'          => 'All Projects',
			'search_items'       => 'Search Projects',
			'not_found'          => 'No projects found',
			'not_found_in_trash' => 'No projects found in Trash',
		);

		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'has_archive'        => true,
			'show_in_rest'       => true, // Включает поддержку Gutenberg
			'supports'           => array('title', 'editor', 'thumbnail', 'excerpt'),
			'menu_position'      => 5,
			'menu_icon'          => 'dashicons-portfolio', // Иконка в админке
			'rewrite'            => array('slug' => 'projects'),
		);

		register_post_type('projects', $args);
	}

	//
	public function register_project_taxonomy() {
		$labels = array(
			'name'              => 'Project Categories',
			'singular_name'     => 'Project Category',
			'search_items'      => 'Search Project Categories',
			'all_items'         => 'All Project Categories',
			'parent_item'       => 'Parent Category',
			'parent_item_colon' => 'Parent Category:',
			'edit_item'         => 'Edit Category',
			'update_item'       => 'Update Category',
			'add_new_item'      => 'Add New Category',
			'new_item_name'     => 'New Category Name',
			'menu_name'         => 'Categories',
		);

		$args = array(
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'show_in_rest'      => true, // Gutenberg и REST API
			'rewrite'           => array('slug' => 'project-category'),
		);

		register_taxonomy('project_category', array('projects'), $args);
	}

	// Shortcode to display media acf - [media_project]
	public function create_project_media_shortcode() {

		ob_start();

		$media = get_field('project_media') ?: [];
		$carousel_id = uniqid('carousel_');
		$gallery = [];

		// Extract embed content fields
		$embed_iframe = $media['embed_content']['embed_presentation_iframe'] ?? '';
		$embed_link = $media['embed_content']['embed_content_link'] ?? '';
		$embed_instructions = $media["review_instructions"] ?? '';
		$pdf_file = $media["file"] ?? "";

		// Add embed-content as first slide
		if (!empty($embed_iframe)) {
			$gallery[] = [
				'type' => 'embed',
				'content' => $embed_iframe,
			];
		}

		// Add gallery images
		if (!empty($media['gallery'])) {
			foreach ($media['gallery'] as $img) {
				$gallery[] = [
					'type' => 'image',
					'url' => $img['url'] ?? '',
					'thumbnail' => $img['sizes']['thumbnail'] ?? ($img['url'] ?? ''),
					'alt' => $img['alt'] ?? '',
					'caption' => $img['caption'] ?? '',
					'description' => $img['description'] ?? '',
				];
			}
		}

		// Add embed video
		$video_url = $media['embed_content']['embed_video'] ?? "";
		if (!empty($video_url)) {
			$gallery[] = [
				'type'    => 'video',
				'url'  => $video_url,
			];
		}

		// Build context for Twig
		$context = Timber::context();
		$context['gallery'] = $gallery;
		$context['carousel_id'] = $carousel_id;

		// Fire a custom hook before the media section
		do_action('tpv/media_project/before', $context);

		// Render gallery or fallback image
		if (count($gallery) > 0) {
			Timber::render('/single-project/partials/gallery.twig', $context);
		} else {
			$context['project'] = Timber::get_post();
			Timber::render('/single-project/partials/featured-image.twig', $context);
		}

		// Pass media to twig
		$media['embed_content']['embed_content_link'] = $embed_link;
		$media['embed_content']['review_instructions'] = $embed_instructions;
		$context['media'] = $media;

		// Only render embed buttons section if file or link exists
		if (!empty($pdf_file) || !empty($embed_link) || !empty($embed_instructions)) {
			Timber::render('/single-project/partials/embed-content-btns.twig', $context);
		}

		// Fire a custom hook after the media section
		do_action('tpv/media_project/after', $context);

		return ob_get_clean();
	}

	// Shortcode to display partner logos acf - [partner_logos]
	public function create_partner_logos_shortcode() {

		ob_start();

		$group = get_field('partner_logos_group') ?: [];
		$partner_logos = (array) (get_field('partner_logos_group')['partner_logos'] ?? []);
		$last_partner_logo = null;

		// Split the last logo into a separate variable (to align it strictly on the right in the template)
		if (count($partner_logos) > 1) {
			$last_partner_logo = array_pop($partner_logos);
		}

		$context = Timber::context();
		$context['partner_logos'] = $partner_logos;
		$context['last_partner_logo'] = $last_partner_logo;
		$context['disclaimer_text'] = trim((string) ($group['disclaimer_text'] ?? ''));

		// Fire a custom hook before the partner logos section
		do_action('tpv/partner_logos/before', $context);

		Timber::render('/single-project/partials/partner-logos.twig', $context);

		// Fire a custom hook after the partner logos section
		do_action('tpv/partner_logos/after', $context);

		return ob_get_clean();
	}

	// Ensure site icon tags are printed even if theme is minimal.
	public function enable_site_favicon() {
		if (function_exists('get_site_icon_url') && has_site_icon()) {
			echo '<link rel="icon" href="' . esc_url(get_site_icon_url(32)) . '" sizes="32x32" />' . "\n";
			echo '<link rel="icon" href="' . esc_url(get_site_icon_url(192)) . '" sizes="192x192" />' . "\n";
			echo '<link rel="apple-touch-icon" href="' . esc_url(get_site_icon_url(180)) . '" />' . "\n";
			echo '<meta name="msapplication-TileImage" content="' . esc_url(get_site_icon_url(270)) . '" />' . "\n";
		}
	}

	public function register_anchor_field($args, $name) {
		$targets = ['blocksy/query'];
		if (in_array($name, $targets, true)) {
			$args['supports'] = array_merge($args['supports'] ?? [], ['anchor' => true]);
		}
		return $args;
	}

	public function register_anchor_field_script() {
		wp_add_inline_script('wp-blocks', "
			wp.hooks.addFilter(
				'blocks.registerBlockType',
				'site/enable-anchor-blocksy',
				(settings, name) => {
					if (name === 'blocksy/query') {
						settings.supports = Object.assign({}, settings.supports, { anchor: true });
					}
					return settings;
				}
			);
		");
	}

	// Shortcode to display members acf - [team_members]
	public function create_team_members_shortcode() {
		if (!class_exists('\Timber\Timber')) {
			return '';
		}

		ob_start();

		$team_members = get_field('team_members');

		if (empty($team_members) || !is_array($team_members)) {
			return '';
		}

		// Fire a custom hook before the team members
		do_action('tpv/team_members/before');

		foreach ($team_members as &$member) {
			if (isset($member['member']) && is_array($member['member'])) {
				$member = $member['member'];
			}
		}
		unset($member);

		$context = Timber::context();
		$context['team_members'] = $team_members;

		Timber::render('/single-project/partials/team-members.twig', $context);

		// Fire a custom hook after the team members
		do_action('tpv/team_members/after');

		return ob_get_clean();
	}
}

TpvTheme::init();
