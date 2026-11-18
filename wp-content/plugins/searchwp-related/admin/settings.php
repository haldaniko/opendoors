<?php

use SearchWP\Settings;
use SearchWP\Utils;

/**
 * Class SearchWP_Related
 *
 * This class powers the settings screen UI within the SearchWP settings screen
 * allowing users to determine which post types have Related content auto-appended
 */
class SearchWP_Related_Settings {

	public $public                = true;
	public $slug                  = 'related';
	public $name                  = 'Related';
	public $min_searchwp_version  = '4.2.6';

	private $settings = [];

	/**
	 * SearchWP_Related_Settings constructor.
	 */
	function __construct() {}

	/**
	 * Initializer
	 */
	function init() {

		add_filter( 'searchwp\extensions', [ $this, 'register' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'assets' ] );

		add_action( 'wp_ajax_searchwp_related_save_settings',  [ $this, 'save_settings_ajax' ] );
	}

	/**
	 * Validate settings array against expected values
	 *
	 * @param $dirty_settings
	 *
	 * @return array
	 */
	function validate( $dirty_settings ) {

		return [
			'swp-layout-theme'               => isset( $dirty_settings['swp-layout-theme'] ) ? sanitize_key( $dirty_settings['swp-layout-theme'] ) : 'epsilon',
			'swp-layout-style'               => isset( $dirty_settings['swp-layout-style'] ) ? sanitize_key( $dirty_settings['swp-layout-style'] ) : 'grid',
			'swp-max-results'                => isset( $dirty_settings['swp-max-results'] ) ? absint( $dirty_settings['swp-max-results'] ) : 3,
			'swp-block-title'                => isset( $dirty_settings['swp-block-title'] ) ? sanitize_text_field( $dirty_settings['swp-block-title'] ) : esc_html__( 'Related Content', 'searchwp-related' ),
			'swp-image-size'                 => isset( $dirty_settings['swp-image-size'] ) ? sanitize_key( $dirty_settings['swp-image-size'] ) : 'large',
			'swp-description-enabled'        => ! empty( $dirty_settings['swp-description-enabled'] ),
			'swp-engine'                     => isset( $dirty_settings['swp-engine'] ) && Settings::get_engine_settings( sanitize_key( $dirty_settings['swp-engine'] ) ) ? sanitize_key( $dirty_settings['swp-engine'] ) : 'default',
			'swp-auto-append-post-types'     => isset( $dirty_settings['swp-auto-append-post-types'] ) && is_array( $dirty_settings['swp-auto-append-post-types'] ) ? array_filter( $dirty_settings['swp-auto-append-post-types'], 'post_type_exists' ) : [],
			'swp-auto-append-logic'          => isset( $dirty_settings['swp-auto-append-logic'] ) ? sanitize_key( $dirty_settings['swp-auto-append-logic'] ) : 'bottom',
			'swp-append-after-paragraph-num' => isset( $dirty_settings['swp-append-after-paragraph-num'] ) ? absint( $dirty_settings['swp-append-after-paragraph-num'] ) : 2,
			'swp-post-exclude-logic'         => isset( $dirty_settings['swp-post-exclude-logic'] ) ? sanitize_key( $dirty_settings['swp-post-exclude-logic'] ) : 'not_in',
			'swp-post-exclude-ids-in'        => isset( $dirty_settings['swp-post-exclude-ids-in'] ) && is_array( $dirty_settings['swp-post-exclude-ids-in'] ) ? array_unique( array_filter( array_map( 'absint', $dirty_settings['swp-post-exclude-ids-in'] ) ) ) : [],
			'swp-post-exclude-ids-not-in'    => isset( $dirty_settings['swp-post-exclude-ids-not-in'] ) && is_array( $dirty_settings['swp-post-exclude-ids-not-in'] ) ? array_unique( array_filter( array_map( 'absint', $dirty_settings['swp-post-exclude-ids-not-in'] ) ) ) : [],
		];
	}

	/**
	 * Save settings.
     *
     * @since 1.5.0
	 */
	public function save_settings_ajax() {

		Utils::check_ajax_permissions();

		if ( ! isset( $_POST['settings'] ) ) {
			wp_send_json_error();
		}

		$this->set( json_decode( wp_unslash( $_POST['settings'] ), true ) );

		wp_send_json_success();
	}

	/**
     * Getter for settings.
     *
     * @since 1.5.0
     *
	 * @return array
	 */
	public function get() {

	    if ( empty( $this->settings ) ) {
		    $this->settings = $this->validate( $this->get_option() );
        }

		return $this->settings;
    }

	/**
	 * Setter for settings.
	 *
	 * @since 1.5.0
     *
     * @param array $settings Settings to set.
     *
     * @return array
	 */
	public function set( $settings ) {

		$this->settings = $this->validate( $settings );
		$this->set_option( $this->settings );

        return $this->settings;
	}

	/**
	 * Get settings option.
	 *
	 * @since 1.5.0
     *
     * @return mixed Option value.
	 */
	public function get_option() {

		return get_option( 'searchwp_related' );
	}

	/**
	 * Set settings option.
	 *
	 * @since 1.5.0
     *
     * @param mixed $value Option value.
     *
     * @return bool
	 */
	private function set_option( $value ) {

		return update_option( 'searchwp_related', $value );
	}

	/**
	 * Output the view for the settings screen.
	 */
	function view() {

		$settings = $this->get();

		?>
        <div class="swp-content-container">
            <div class="swp-page-header">
                <div class="swp-flex--row sm:swp-flex--col sm:swp-flex--gap30 swp-justify-between swp-flex--align-c sm:swp-flex--align-start">
                    <div class="swp-flex--row swp-flex--gap15 swp-flex--align-c">
                        <h1 class="swp-h1 swp-page-header--h1">
                            <?php esc_html_e( 'Settings', 'searchwp-related' ); ?>
                        </h1>
                    </div>
                    <div class="swp-flex--row swp-flex--gap15 swp-flex--grow0">

                        <button type="button" class="swp-button swp-button--flex-content" data-swp-modal="#swp-search-form-embed-modal">
                            <svg width="13" height="8" viewBox="0 0 13 8" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M4 7.84615L0.25 3.99999L4 0.153839L4.89062 1.0673L2.01562 4.01602L4.875 6.94871L4 7.84615ZM9 7.84615L8.10938 6.93269L10.9844 3.98397L8.125 1.05128L9 0.153839L12.75 3.99999L9 7.84615Z" fill="#0E2121" fill-opacity="0.8"/>
                            </svg>

		                    <?php esc_html_e( 'Embed', 'searchwp-related' ); ?>
                        </button>

                        <button type="button" id="swp-results-page-save" class="swp-button swp-button--green">
                            <?php esc_html_e( 'Save', 'searchwp-related' ); ?>
                        </button>

                    </div>
                </div>
            </div>

            <div class="swp-collapse swp-opened">
                <div class="swp-collapse--header">

                    <h2 class="swp-h2">
                        <?php esc_html_e( 'Layout Template', 'searchwp-related' ); ?>
                    </h2>

                    <button class="swp-expand--button">
                        <svg class="swp-arrow" width="17" height="11" viewBox="0 0 17 11" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M14.2915 0.814362L8.09717 6.95819L1.90283 0.814362L0 2.7058L8.09717 10.7545L16.1943 2.7058L14.2915 0.814362Z" fill="#0E2121" fill-opacity="0.8"/>
                        </svg>
                    </button>

                </div>

                <div class="swp-collapse--content">
                    <div class="swp-row">
                        <?php esc_html_e( 'Use Layout Themes to quickly set the style of the Related block. Fine-tune it even further by tweaking the layout and customizing the appearance of individual elements. Limit the maximum number of results and list the post IDs that should never appear as related items.', 'searchwp-related' ); ?>
                    </div>
                    <div class="swp-row">
                        <div class="swp-flex--row sm:swp-flex--col sm:swp-flex--gap30">
                            <div class="swp-col swp-col--title-width--sm">
                                <h3 class="swp-h3">
                                    <?php esc_html_e( 'Layout Theme', 'searchwp-related' ); ?>
                                </h3>
                            </div>

                            <div class="swp-col">
                                <div class="swp-flex--row swp-flex--gap20 swp-sf--layout-themes">

                                    <div class="swp-flex--grow1 swp-input--radio-img">
                                        <input type="radio" id="swp-alpha-theme" name="swp-layout-theme" value="alpha"<?php checked( $settings['swp-layout-theme'], 'alpha' ); ?> />

                                        <label for="swp-alpha-theme">
                                            <img src="<?php echo esc_url( SEARCHWP_RELATED_PLUGIN_URL . 'assets/images/admin/alpha.svg' ); ?>" alt="" />
					                        <?php esc_html_e( 'Minimal', 'searchwp-related' ); ?>
                                        </label>
                                    </div>

                                    <div class="swp-flex--grow1 swp-input--radio-img">
                                        <input type="radio" id="swp-beta-theme" name="swp-layout-theme" value="beta"<?php checked( $settings['swp-layout-theme'], 'beta' ); ?> />

                                        <label for="swp-beta-theme">
                                            <img src="<?php echo esc_url( SEARCHWP_RELATED_PLUGIN_URL . 'assets/images/admin/beta.svg' ); ?>" alt="" />
					                        <?php esc_html_e( 'Columns', 'searchwp-related' ); ?>
                                        </label>
                                    </div>

                                    <div class="swp-flex--grow1 swp-input--radio-img">
                                        <input type="radio" id="swp-gamma-theme" name="swp-layout-theme" value="gamma"<?php checked( $settings['swp-layout-theme'], 'gamma' ); ?> />

                                        <label for="swp-gamma-theme">
                                            <img src="<?php echo esc_url( SEARCHWP_RELATED_PLUGIN_URL . 'assets/images/admin/gamma.svg' ); ?>" alt="" />
					                        <?php esc_html_e( 'Medium', 'searchwp-related' ); ?>
                                        </label>
                                    </div>

                                    <div class="swp-flex--grow1 swp-input--radio-img">
                                        <input type="radio" id="swp-epsilon-theme" name="swp-layout-theme" value="epsilon"<?php checked( $settings['swp-layout-theme'], 'epsilon' ); ?> />

                                        <label for="swp-epsilon-theme">
                                            <img src="<?php echo esc_url( SEARCHWP_RELATED_PLUGIN_URL . 'assets/images/admin/epsilon.svg' ); ?>" alt="" />
					                        <?php esc_html_e( 'Thumbnails', 'searchwp-related' ); ?>
                                        </label>
                                    </div>

                                    <div class="swp-flex--grow1 swp-input--radio-img">
                                        <input type="radio" id="swp-zeta-theme" name="swp-layout-theme" value="zeta"<?php checked( $settings['swp-layout-theme'], 'zeta' ); ?> />

                                        <label for="swp-zeta-theme">
                                            <img src="<?php echo esc_url( SEARCHWP_RELATED_PLUGIN_URL . 'assets/images/admin/zeta.svg' ); ?>" alt="" />
					                        <?php esc_html_e( 'Rich', 'searchwp-related' ); ?>
                                        </label>
                                    </div>

                                    <div class="swp-flex--grow1 swp-input--radio-img">
                                        <input type="radio" id="swp-custom-theme"  name="swp-layout-theme" value="custom"<?php checked( $settings['swp-layout-theme'], 'custom' ); ?> />

                                        <label for="swp-custom-theme">
                                            <img src="<?php echo esc_url( SEARCHWP_RELATED_PLUGIN_URL . 'assets/images/admin/custom.svg' ); ?>" alt="" />
					                        <?php esc_html_e( 'Custom', 'searchwp-related' ); ?>
                                        </label>
                                    </div>

                                </div>

                                <h4 class="swp-h4 swp-margin-t30">
			                        <?php esc_html_e( 'Theme Preview', 'searchwp-related' ); ?>
                                </h4>

                                <div class="swp-rp-theme-preview">

                                    <div class="<?php echo esc_attr( self::get_preview_container_classes( $settings ) ); ?>">

                                        <div class="swp-result-item">
                                            <div class="swp-result-item--img-container">
                                                <div class="swp-result-item--img">
                                                    <img src="<?php echo esc_url( SEARCHWP_RELATED_PLUGIN_URL . 'assets/images/admin/cracker001.jpg' ); ?>" alt="">
                                                </div>
                                            </div>

                                            <div class="swp-result-item--info-container">
                                                <h2 class="swp-result-item--h2">
                                                    <a class="swp-a" role="link" aria-disabled="true">
								                        <?php esc_html_e( 'New crackers recipe available!', 'searchwp-related' ); ?>
                                                    </a>
                                                </h2>
                                                <p class="swp-result-item--desc">
		                                            <?php esc_html_e( 'Crispy, crunchy multigrain crackers, loaded with seeds. Add delicious crunch to your day!' ); ?>
                                                </p>
                                            </div>
                                        </div>

                                        <div class="swp-result-item">
                                            <div class="swp-result-item--img-container">
                                                <div class="swp-result-item--img">
                                                    <img src="<?php echo esc_url( SEARCHWP_RELATED_PLUGIN_URL . 'assets/images/admin/cracker002.jpg' ); ?>" alt="">
                                                </div>
                                            </div>

                                            <div class="swp-result-item--info-container">
                                                <h2 class="swp-result-item--h2">
                                                    <a class="swp-a" role="link" aria-disabled="true">
					                                    <?php esc_html_e( 'Fresh avocado crackers recipe!', 'searchwp-related' ); ?>
                                                    </a>
                                                </h2>
                                                <p class="swp-result-item--desc">
		                                            <?php esc_html_e( 'Classic multigrain crackers, featuring a fresh avocado twist. Elevate your snacking experience with this delicious combination!' ); ?>
                                                </p>
                                            </div>
                                        </div>

                                        <div class="swp-result-item">
                                            <div class="swp-result-item--img-container">
                                                <div class="swp-result-item--img">
                                                    <img src="<?php echo esc_url( SEARCHWP_RELATED_PLUGIN_URL . 'assets/images/admin/cracker003.jpg' ); ?>" alt="">
                                                </div>
                                            </div>

                                            <div class="swp-result-item--info-container">
                                                <h2 class="swp-result-item--h2">
                                                    <a class="swp-a" role="link" aria-disabled="true">
					                                    <?php esc_html_e( 'Multigrain crackers with banana topping!', 'searchwp-related' ); ?>
                                                    </a>
                                                </h2>
                                                <p class="swp-result-item--desc">
				                                    <?php esc_html_e( 'Perfect blend of crunchy multigrain crackers, enriched with seeds and topped with sweet bananas.' ); ?>
                                                </p>
                                            </div>
                                        </div>

                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="swp-row">
                        <div class="swp-flex--row sm:swp-flex--col sm:swp-flex--gap30">

                            <div class="swp-col swp-col--title-width--sm">

                                <h3 class="swp-h3">
				                    <?php esc_html_e( 'Layout Style', 'searchwp-related' ); ?>
                                </h3>

                            </div>


                            <div class="swp-col">

                                <div class="swp-flex--col swp-flex--gap30">

                                    <div class="swp-flex--row swp-flex--gap12">

                                        <div class="swp-input--radio-img">

                                            <input type="radio" name="swp-layout-style" id="swp-layout-style-grid" value="grid"<?php checked( isset( $settings['swp-layout-style'] ) && $settings['swp-layout-style'] === 'grid' ); ?> />

                                            <label for="swp-layout-style-grid">
                                                <img src="<?php echo esc_url( SEARCHWP_RELATED_PLUGIN_URL . 'assets/images/admin/columns-layout.svg' ); ?>" alt="" />

							                    <?php esc_html_e( 'Columns', 'searchwp-related' ); ?>

                                            </label>

                                        </div>

                                        <div class="swp-input--radio-img">

                                            <input type="radio" name="swp-layout-style" id="swp-layout-style-list" value="list"<?php checked( isset( $settings['swp-layout-style'] ) && $settings['swp-layout-style'] === 'list' ); ?> />

                                            <label for="swp-layout-style-list">
                                                <img src="<?php echo esc_url( SEARCHWP_RELATED_PLUGIN_URL . 'assets/images/admin/list-layout.svg' ); ?>" alt="" />

							                    <?php esc_html_e( 'List', 'searchwp-related' ); ?>

                                            </label>

                                        </div>

                                    </div>

                                    <div class="swp-flex--col swp-flex--gap25">

                                        <p class="swp-desc">
			                                <?php esc_html_e( 'Choose the maximum number of results', 'searchwp-related' ); ?>
                                        </p>

                                        <div class="swp-inputbox-horizontal">

                                            <div class="swp-w-1/6">
                                                <input type="number" min="0" class="swp-input swp-w-full" name="swp-max-results" value="<?php echo ! empty( $settings['swp-max-results'] ) ? esc_attr( $settings['swp-max-results'] ) : 3; ?>">
                                            </div>

                                            <label for="" class="swp-label">
				                                <?php esc_html_e( 'Results', 'searchwp-related' ); ?>
                                            </label>

                                        </div>

                                    </div>

                                </div>

                            </div>

                        </div>
                    </div>

                    <div class="swp-row">
                        <div class="swp-flex--col swp-flex--gap30">

                            <div class="swp-flex--row sm:swp-flex--col sm:swp-flex--gap30">

                                <div class="swp-col swp-col--title-width--sm">
                                    <h3 class="swp-h3">
				                        <?php esc_html_e( 'Block Title', 'searchwp-related' ); ?>
                                    </h3>
                                </div>

                                <div class="swp-col">
                                    <input class="swp-input swp-w-1/4" type="text" name="swp-block-title" value="<?php echo ! empty( $settings['swp-block-title'] ) ? esc_attr( $settings['swp-block-title'] ) : ''; ?>">
                                </div>

                            </div>

                            <div class="swp-flex--row sm:swp-flex--col sm:swp-flex--gap30">

                                <div class="swp-col swp-col--title-width--sm">

                                    <h3 class="swp-h3">
                                        <?php esc_html_e( 'Images', 'searchwp-related' ); ?>
                                    </h3>

                                </div>

                                <div class="swp-col">
                                    <div class="swp-w-1/4 sm:swp-w-full">
                                        <select class="swp-choicesjs-single" name="swp-image-size">
                                            <option value=""><?php esc_html_e( 'None', 'searchwp-related' ); ?></option>
                                            <option value="small"<?php selected( $settings['swp-image-size'], 'small' ); ?>><?php esc_html_e( 'Small', 'searchwp-related' ); ?></option>
                                            <option value="medium"<?php selected( $settings['swp-image-size'], 'medium' ); ?>><?php esc_html_e( 'Medium', 'searchwp-related' ); ?></option>
                                            <option value="large"<?php selected( $settings['swp-image-size'], 'large' ); ?>><?php esc_html_e( 'Large', 'searchwp-related' ); ?></option>
                                        </select>
                                    </div>

                                </div>

                            </div>

                            <div class="swp-flex--row sm:swp-flex--col sm:swp-flex--gap30">

                                <div class="swp-col swp-col--title-width--sm">

                                    <h3 class="swp-h3">
				                        <?php esc_html_e( 'Descriptions', 'searchwp-related' ); ?>
                                    </h3>

                                </div>


                                <div class="swp-col">

                                    <label class="swp-toggle">

                                        <input class="swp-toggle-checkbox" type="checkbox" name="swp-description-enabled"<?php checked( $settings['swp-description-enabled'] ); ?>>

                                        <div class="swp-toggle-switch"></div>

                                    </label>

                                </div>

                            </div>

                        </div>

                    </div>

                    <div class="swp-row">

                        <div class="swp-flex--row sm:swp-flex--col sm:swp-flex--gap30">

                            <div class="swp-col swp-col--title-width--sm">

                                <h3 class="swp-h3">
					                <?php esc_html_e( 'Engine', 'searchwp-related' ); ?>
                                </h3>

                            </div>

                            <div class="swp-col">

                                <div class="swp-w-1/4 sm:swp-w-full">
					                <?php $selected_engine = isset( $settings['swp-engine'] ) ? $settings['swp-engine'] : 'default'; ?>
                                    <select class="swp-choicesjs-single" name="swp-engine">
						                <?php foreach ( Settings::get_engines() as $_engine ) : ?>
							                <?php $engine_name = $_engine->get_name(); ?>
                                            <option value="<?php echo esc_attr( $engine_name ); ?>"<?php selected( $selected_engine, $engine_name ); ?>><?php echo esc_html( $_engine->get_label() ); ?></option>
						                <?php endforeach; ?>
                                    </select>
                                </div>

                            </div>

                        </div>

                    </div>

                    <div class="swp-row">
                        <div class="swp-flex--row sm:swp-flex--col sm:swp-flex--gap30">

                            <div class="swp-col swp-col--title-width--sm">

                                <h3 class="swp-h3">
				                    <?php esc_html_e( 'Auto-append', 'searchwp-related' ); ?>
                                </h3>

                            </div>

                            <div class="swp-col">

                                <div class="swp-flex--col swp-flex--gap25">

                                    <div class="swp-flex--col swp-flex--gap17">

                                        <label class="swp-label" for="swp-qs-place">
                                            <?php esc_html_e( 'Select what post types get Related block auto-appended to the content.', 'searchwp-related' ); ?>
                                        </label>

                                        <div class="swp-w-1/2 sm:swp-w-full">
                                            <?php $selected_post_types = isset( $settings['swp-auto-append-post-types'] ) ? $settings['swp-auto-append-post-types'] : []; ?>
                                            <select class="swp-choicesjs-multiple" name="swp-auto-append-post-types" multiple>
                                                <?php foreach ( Utils::get_post_types() as $post_type ) : ?>
                                                    <?php $post_type_obj = get_post_type_object( $post_type ); ?>
                                                    <?php if ( $post_type_obj ) : ?>
                                                        <option value="<?php echo esc_attr( $post_type ); ?>"<?php selected( in_array( $post_type, $selected_post_types, true ) ); ?>><?php echo esc_html( $post_type_obj->label ); ?></option>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                    </div>

                                    <div class="swp-flex--col swp-flex--gap17">

                                        <label class="swp-label" for="swp-qs-place">
		                                    <?php esc_html_e( 'Choose where in the post body the related content will be placed.', 'searchwp-related' ); ?>
                                        </label>

                                        <div class="swp-flex--row swp-flex--gap17">
                                            <div class="swp-input--radio-embed">
                                                <input type="radio" name="swp-auto-append-logic" id="swp-auto-append-bottom" value="bottom"<?php checked( $settings['swp-auto-append-logic'], 'bottom' ); ?> />
                                                <label for="swp-auto-append-bottom">
                                                    <?php esc_html_e( 'Bottom', 'searchwp-related' ); ?>
                                                </label>
                                            </div>

                                            <div class="swp-input--radio-embed">
                                                <input type="radio" name="swp-auto-append-logic" id="swp-auto-append-after-paragraph" value="after-paragraph"<?php checked( $settings['swp-auto-append-logic'], 'after-paragraph' ); ?> />
                                                <label for="swp-auto-append-after-paragraph">
                                                    <?php esc_html_e( 'After Specific Paragraph', 'searchwp-related' ); ?>
                                                </label>
                                            </div>
                                        </div>

                                        <div id="swp-append-after-paragraph-num-block" class="swp-inputbox-horizontal"<?php echo ( empty( $settings['swp-auto-append-logic'] ) || $settings['swp-auto-append-logic'] === 'bottom' ) ? ' style="display: none;"' : ''; ?>>
                                            <label for="" class="swp-label">
		                                        <?php esc_html_e( 'After', 'searchwp-related' ); ?>
                                            </label>
                                            <div class="swp-w-1/6">
                                                <input type="number" min="0" class="swp-input swp-w-full" name="swp-append-after-paragraph-num" value="<?php echo ! empty( $settings['swp-append-after-paragraph-num'] ) ? esc_attr( $settings['swp-append-after-paragraph-num'] ) : 0; ?>">
                                            </div>
                                            <label for="" class="swp-label">
			                                    <?php esc_html_e( 'Paragraph', 'searchwp-related' ); ?>
                                            </label>
                                        </div>

                                    </div>

                                </div>

                            </div>

                        </div>
                    </div>

                    <div class="swp-row">

                        <div class="swp-flex--row sm:swp-flex--col sm:swp-flex--gap30">
                            <div class="swp-col swp-col--title-width--sm">
                                <h3 class="swp-h3">
                                    <?php esc_html_e( 'Exclude Entries', 'searchwp-related' ); ?>
                                </h3>
                            </div>

                            <div class="swp-col">
                                <div class="swp-flex--col swp-flex--gap30">
                                    <div class="swp-flex--col swp-flex--gap25">
                                        <p class="swp-desc">
                                            <?php esc_html_e( 'Choose the exclude logic and enter the IDs to narrow down the results.', 'searchwp-related' ); ?>
                                        </p>

                                        <div class="swp-w-1/3 sm:swp-w-full">
                                            <select class="swp-choicesjs-single" name="swp-post-exclude-logic">
                                                <option value="not_in"<?php selected( ! isset( $settings['swp-post-exclude-logic'] ) || $settings['swp-post-exclude-logic'] === 'not_in' ); ?>><?php esc_html_e( 'Exclude selected IDs:', 'searchwp-related' ); ?></option>

                                                <option value="in"<?php selected( isset( $settings['swp-post-exclude-logic'] ) && $settings['swp-post-exclude-logic'] === 'in' ); ?>><?php esc_html_e( 'Search selected IDs only:', 'searchwp-related' ); ?></option>
                                            </select>
                                        </div>

                                        <div id="swp-post-exclude-ids-not-in-block" class="swp-flex--col swp-flex--gap17 swp-w-1/3 sm:swp-w-full"<?php echo ( ! isset( $settings['swp-post-exclude-logic'] ) || $settings['swp-post-exclude-logic'] === 'not_in' ) ? '' : ' style="display: none;"'; ?>>
                                            <p class="swp-desc">
                                                <?php esc_html_e( 'Exclude these IDs:', 'searchwp-related' ); ?>
                                            </p>

                                            <?php $selected_exclude_ids_not_in = isset( $settings['swp-post-exclude-ids-not-in'] ) && is_array( $settings['swp-post-exclude-ids-not-in'] ) ? $settings['swp-post-exclude-ids-not-in'] : []; ?>
                                            <select class="swp-choicesjs-hybrid" name="swp-post-exclude-ids-not-in" multiple>
                                                <?php foreach ( $selected_exclude_ids_not_in as $exclude_id_not_in ) : ?>
                                                    <option value="<?php echo absint( $exclude_id_not_in ); ?>" selected><?php echo absint( $exclude_id_not_in ); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div id="swp-post-exclude-ids-in-block" class="swp-flex--col swp-flex--gap17 swp-w-1/3 sm:swp-w-full"<?php echo ( isset( $settings['swp-post-exclude-logic'] ) && $settings['swp-post-exclude-logic'] === 'in' ) ? '' : ' style="display: none;"'; ?>>
                                            <p class="swp-desc">
                                                <?php esc_html_e( 'Exclude everything except these IDs:', 'searchwp-related' ); ?>
                                            </p>

                                            <?php $selected_exclude_ids_in = isset( $settings['swp-post-exclude-ids-in'] ) && is_array( $settings['swp-post-exclude-ids-in'] ) ? $settings['swp-post-exclude-ids-in'] : []; ?>
                                            <select class="swp-choicesjs-hybrid" name="swp-post-exclude-ids-in" multiple>
                                                <?php foreach ( $selected_exclude_ids_in as $exclude_id_in ) : ?>
                                                    <option value="<?php echo absint( $exclude_id_in ); ?>" selected><?php echo absint( $exclude_id_in ); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                </div>
            </div>

            <div id="swp-search-form-embed-modal" class="swp-modal swp-modal--centered swp-modal-l" style="display: none;">

                <div class="swp-modal--header swp-bg--gray">

                    <div class="swp-flex--row swp-justify-between swp-flex--align-c">

                        <h1 class="swp-h1 swp-font-size16">
					        <?php esc_html_e( 'Embed Manually', 'searchwp-related' ); ?>
                        </h1>

                        <button class="swp-modal--close">
                            <svg width="16" height="15" viewBox="0 0 16 15" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path opacity="0.5" d="M16 1.49633L14.3886 0L8 5.93224L1.61143 0L0 1.49633L6.38857 7.42857L0 13.3608L1.61143 14.8571L8 8.9249L14.3886 14.8571L16 13.3608L9.61143 7.42857L16 1.49633Z" fill="#646970"></path>
                            </svg>
                        </button>

                    </div>

                </div>

                <div class="swp-modal--content swp-margin-b25">

                    <p class="swp-p swp-margin-b25">
                        <b class="swp-b"><?php esc_html_e( 'Need even more granular control over your Related block placement?', 'searchwp-related' ); ?></b></br>
				        <?php esc_html_e( 'Using the methods below you can embed the block in a specific place of the particular page or virtually any place on your website.', 'searchwp-related' ); ?>
                    </p>

                    <hr class="swp-hr swp-margin-b25">

                    <div class="swp-flex--row swp-margin-b25">

                        <div class="swp-col swp-col--title-width">

                            <h3 class="swp-h3">
						        <?php esc_html_e( 'Embed Related Block', 'searchwp-related' ); ?>
                            </h3>

                        </div>

                        <div class="swp-col">

                            <div class="swp-flex--col swp-flex--gap17">

                                <div class="swp-flex--row swp-flex--gap9 sm:swp-flex--col sm:swp-flex--gap30">

                                    <div class="swp-input--radio-embed">

                                        <input type="radio" name="swp-sfe-embed" id="swp-sfe-gutenberg" value="swp-sfe-gutenberg-desc" checked/>

                                        <label for="swp-sfe-gutenberg">

                                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <mask id="mask0_6_3691" style="mask-type:alpha" maskUnits="userSpaceOnUse" x="0" y="0" width="20" height="20">
                                                    <rect width="20" height="20" fill="#D9D9D9"/>
                                                </mask>
                                                <g mask="url(#mask0_6_3691)">
                                                    <path d="M9.16667 14.1667H10.8333V10.8333H14.1667V9.16667H10.8333V5.83333H9.16667V9.16667H5.83333V10.8333H9.16667V14.1667ZM4.16667 17.5C3.70833 17.5 3.31583 17.3369 2.98917 17.0108C2.66306 16.6842 2.5 16.2917 2.5 15.8333V4.16667C2.5 3.70833 2.66306 3.31583 2.98917 2.98917C3.31583 2.66306 3.70833 2.5 4.16667 2.5H15.8333C16.2917 2.5 16.6842 2.66306 17.0108 2.98917C17.3369 3.31583 17.5 3.70833 17.5 4.16667V15.8333C17.5 16.2917 17.3369 16.6842 17.0108 17.0108C16.6842 17.3369 16.2917 17.5 15.8333 17.5H4.16667ZM4.16667 15.8333H15.8333V4.16667H4.16667V15.8333Z" fill="#0E2121" fill-opacity="0.9"/>
                                                </g>
                                            </svg>

									        <?php esc_html_e( 'Gutenberg Block', 'searchwp-related' ); ?>

                                        </label>

                                    </div>

                                    <div class="swp-input--radio-embed">

                                        <input type="radio" name="swp-sfe-embed" id="swp-sfe-shortcode" value="swp-sfe-shortcode-desc" />

                                        <label for="swp-sfe-shortcode">

                                            <svg width="24" height="20" viewBox="0 0 24 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M0 0H4.47458V1.32625H2.40664V10.6674H4.47458V12H0V0Z" fill="#0E2121" fill-opacity="0.9"/>
                                                <path d="M13.885 0H16.2713L10.5019 12H8.13574L13.885 0Z" fill="#0E2121" fill-opacity="0.9"/>
                                                <path d="M24 0H19.5254V1.32625H21.5934V10.6674H19.5254V12H24V0Z" fill="#0E2121" fill-opacity="0.9"/>
                                            </svg>

									        <?php esc_html_e( 'Shortcode', 'searchwp-related' ); ?>

                                        </label>

                                    </div>

                                    <?php if ( get_theme_support( 'widgets' ) ) : ?>
                                        <div class="swp-input--radio-embed">

                                            <input type="radio" name="swp-sfe-embed" id="swp-sfe-widget" value="swp-sfe-widget-desc"/>

                                            <label for="swp-sfe-widget">

                                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <mask id="mask0_20_20135" style="mask-type:alpha" maskUnits="userSpaceOnUse" x="0" y="0" width="20" height="20">
                                                        <rect width="20" height="20" fill="#D9D9D9"/>
                                                    </mask>
                                                    <g mask="url(#mask0_20_20135)">
                                                        <path d="M13.875 10.8333L9.16667 6.12496L13.875 1.41663L18.5833 6.12496L13.875 10.8333ZM2.5 9.16663V2.49996H9.16667V9.16663H2.5ZM10.8333 17.5V10.8333H17.5V17.5H10.8333ZM2.5 17.5V10.8333H9.16667V17.5H2.5ZM4.16667 7.49996H7.5V4.16663H4.16667V7.49996ZM13.8958 8.49996L16.25 6.14579L13.8958 3.79163L11.5417 6.14579L13.8958 8.49996ZM12.5 15.8333H15.8333V12.5H12.5V15.8333ZM4.16667 15.8333H7.5V12.5H4.16667V15.8333Z" fill="#0E2121" fill-opacity="0.9"/>
                                                    </g>
                                                </svg>

                                                <?php esc_html_e( 'Widget', 'searchwp-related' ); ?>

                                            </label>

                                        </div>
                                    <?php endif; ?>

                                    <div class="swp-input--radio-embed">

                                        <input type="radio" name="swp-sfe-embed" id="swp-sfe-phpcode" value="swp-sfe-phpcode-desc" />

                                        <label for="swp-sfe-phpcode">

                                            <svg width="33" height="20" viewBox="0 0 33 7" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M6.7998 5.83546H8.26923V7H6.7998V5.83546ZM5 2.93418C5.03945 2.16857 5.36654 1.62592 5.98126 1.30625C6.36917 1.10208 6.84583 1 7.41124 1C8.15418 1 8.77055 1.14506 9.26035 1.43519C9.75345 1.72532 10 2.15514 10 2.72465C10 3.07387 9.89316 3.36803 9.67949 3.60712C9.55457 3.75218 9.3146 3.93754 8.95957 4.1632L8.60947 4.38482C8.4188 4.50571 8.29224 4.64674 8.22978 4.80792C8.19034 4.91001 8.16897 5.0685 8.16568 5.28341H6.83432C6.85404 4.82942 6.90664 4.51645 6.99211 4.34453C7.07758 4.16991 7.29783 3.96978 7.65286 3.74412L8.01282 3.51444C8.13116 3.44191 8.2265 3.36266 8.29882 3.2767C8.43031 3.12895 8.49606 2.96642 8.49606 2.78912C8.49606 2.58496 8.42209 2.3996 8.27416 2.23304C8.12952 2.0638 7.86325 1.97918 7.47535 1.97918C7.09402 1.97918 6.82281 2.08261 6.66174 2.28946C6.50394 2.49631 6.42505 2.71122 6.42505 2.93418H5Z" fill="#0E2121" fill-opacity="0.9"/>
                                                <path d="M14.8993 2.59091C14.8993 2.33085 14.8277 2.14541 14.6844 2.0346C14.5435 1.92379 14.3451 1.86839 14.0891 1.86839H13.078V3.34057H14.0891C14.3451 3.34057 14.5435 3.28064 14.6844 3.16079C14.8277 3.04093 14.8993 2.85097 14.8993 2.59091ZM15.9738 2.58412C15.9738 3.17436 15.8188 3.59159 15.5088 3.83582C15.1988 4.08005 14.7561 4.20217 14.1807 4.20217H13.078V6H12V1H14.2617C14.7831 1 15.1988 1.1289 15.5088 1.3867C15.8188 1.6445 15.9738 2.04365 15.9738 2.58412Z" fill="#0E2121" fill-opacity="0.9"/>
                                                <path d="M16.7806 6V1H17.8551V2.90638H19.8842V1H20.9623V6H19.8842V3.76798H17.8551V6H16.7806Z" fill="#0E2121" fill-opacity="0.9"/>
                                                <path d="M24.9255 2.59091C24.9255 2.33085 24.8539 2.14541 24.7106 2.0346C24.5697 1.92379 24.3712 1.86839 24.1152 1.86839H23.1042V3.34057H24.1152C24.3712 3.34057 24.5697 3.28064 24.7106 3.16079C24.8539 3.04093 24.9255 2.85097 24.9255 2.59091ZM26 2.58412C26 3.17436 25.845 3.59159 25.535 3.83582C25.225 4.08005 24.7823 4.20217 24.2068 4.20217H23.1042V6H22.0262V1H24.2879C24.8093 1 25.225 1.1289 25.535 1.3867C25.845 1.6445 26 2.04365 26 2.58412Z" fill="#0E2121" fill-opacity="0.9"/>
                                                <path d="M4.04858 0L5 0.8225L1.90958 3.5L5 6.1775L4.04858 7L0 3.5L4.04858 0Z" fill="#0E2121" fill-opacity="0.9"/>
                                                <path d="M29.7611 0L29 0.8225L31.4723 3.5L29 6.1775L29.7611 7L33 3.5L29.7611 0Z" fill="#0E2121" fill-opacity="0.9"/>
                                            </svg>

									        <?php esc_html_e( 'PHP Code', 'searchwp-related' ); ?>

                                        </label>

                                    </div>

                                </div>

                                <div id="swp-sfe-gutenberg-desc" class="swp-sfe-embed--desc">

                                    <p class="swp-desc swp-leading--160">
								        <?php esc_html_e( 'To add this block, edit a page or post and search for “Related” block.', 'searchwp-related' ); ?>
                                    </p>

                                </div>

                                <div id="swp-sfe-shortcode-desc" class="swp-sfe-embed--desc" style="display: none;">

                                    <div class="swp-flex--row swp-flex--gap9 sm:swp-flex--col sm:swp-flex--gap30">

                                        <input type="text" id="swp-sfe-shortcode-desc-input" class="swp-input swp-input--embed" value="[searchwp_related]" disabled>

                                        <button class="swp-button swp-button--flex-content" type="button" data-swp-copy-from="#swp-sfe-shortcode-desc-input">

                                            <svg width="13" height="15" viewBox="0 0 13 15" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M9.3125 0.625H1.8125C1.125 0.625 0.5625 1.1875 0.5625 1.875V10.625H1.8125V1.875H9.3125V0.625ZM11.1875 3.125H4.3125C3.625 3.125 3.0625 3.6875 3.0625 4.375V13.125C3.0625 13.8125 3.625 14.375 4.3125 14.375H11.1875C11.875 14.375 12.4375 13.8125 12.4375 13.125V4.375C12.4375 3.6875 11.875 3.125 11.1875 3.125ZM11.1875 13.125H4.3125V4.375H11.1875V13.125Z" fill="#0E2121" fill-opacity="0.8"/>
                                            </svg>

									        <?php esc_html_e( 'Copy', 'searchwp-related' ); ?>

                                        </button>

                                    </div>

                                </div>

	                            <?php if ( get_theme_support( 'widgets' ) ) : ?>
                                    <div id="swp-sfe-widget-desc" class="swp-sfe-embed--desc" style="display: none;">

                                        <p class="swp-desc swp-leading--160">
                                            <?php
                                                echo sprintf(
                                                    wp_kses( /* translators: %1$s - Widgets page URL. */
                                                        __( 'To add this widget, visit the <a href="%1$s" target="_blank">Widgets Page</a> and look for “Related” widget.', 'searchwp-related' ),
                                                        [
                                                            'a' => [
                                                                'href'   => true,
                                                                'target' => true,
                                                            ],
                                                        ]
                                                    ),
                                                    esc_url( admin_url( 'widgets.php' ) )
                                                );
                                            ?>
                                        </p>

                                    </div>
                                <?php endif; ?>

                                <div id="swp-sfe-phpcode-desc" class="swp-sfe-embed--desc" style="display: none;">

                                    <p class="swp-desc swp-leading--160 swp-margin-b15">
								        <?php esc_html_e( 'Use the following PHP snippet in your code to display the block.', 'searchwp-related' ); ?>
                                    </p>

                                    <div class="swp-flex--row swp-flex--gap9 sm:swp-flex--col sm:swp-flex--gap30">

                                        <input type="text" id="swp-sfe-phpcode-desc-input" class="swp-input swp-input--embed" value="&lt;?php echo ( new \SearchWP_Related\Template() )->get_template(); ?&gt;" disabled>

                                        <button class="swp-button swp-button--flex-content" type="button" data-swp-copy-from="#swp-sfe-phpcode-desc-input">

                                            <svg width="13" height="15" viewBox="0 0 13 15" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M9.3125 0.625H1.8125C1.125 0.625 0.5625 1.1875 0.5625 1.875V10.625H1.8125V1.875H9.3125V0.625ZM11.1875 3.125H4.3125C3.625 3.125 3.0625 3.6875 3.0625 4.375V13.125C3.0625 13.8125 3.625 14.375 4.3125 14.375H11.1875C11.875 14.375 12.4375 13.8125 12.4375 13.125V4.375C12.4375 3.6875 11.875 3.125 11.1875 3.125ZM11.1875 13.125H4.3125V4.375H11.1875V13.125Z" fill="#0E2121" fill-opacity="0.8"/>
                                            </svg>

									        <?php esc_html_e( 'Copy', 'searchwp-related' ); ?>

                                        </button>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

            <div class="swp-modal--bg"></div>

        </div>
		<?php
	}

	/**
	 * Callback for SearchWP Extension register
	 *
	 * @param $extensions
	 *
	 * @return mixed
	 */
	function register( $extensions ) {

		// When instantiating, SearchWP core forces a prefix of 'SearchWP' and it needs
		// to match the name of this class right here, so we need to get creative :boo:
		$extensions['_Related_Settings'] = SEARCHWP_RELATED_PLUGIN_FILE;

		return $extensions;
	}

	/**
	 * Output assets needed for settings screen
	 */
	function assets( $hook ) {

		if ( $hook !== 'searchwp_page_searchwp-settings' ) {
			return;
		}

		if ( empty( $_GET['extension'] ) ) {
			return;
		}

		if ( 'related' !== $_GET['extension'] ) {
			return;
		}

		wp_enqueue_script(
			SEARCHWP_PREFIX . 'choicesjs',
			SEARCHWP_PLUGIN_URL . 'assets/vendor/choicesjs/js/choices-10.2.0.min.js',
			[],
			'10.2.0',
			true
		);

		wp_enqueue_style(
			SEARCHWP_PREFIX . 'choicesjs',
			SEARCHWP_PLUGIN_URL . 'assets/vendor/choicesjs/css/choices-10.2.0.min.css',
			[],
			'10.2.0'
		);

        $slug = 'searchwp';

		wp_enqueue_script(
			'searchwp-related-settings',
			SEARCHWP_RELATED_PLUGIN_URL . 'assets/js/admin/settings.js',
			[
				$slug . 'choices',
				$slug . 'collapse',
				$slug . 'copy-input-text',
				$slug . 'modal',
			],
			SEARCHWP_RELATED_VERSION,
			true
		);

		Utils::localize_script( 'searchwp-related-settings' );

		wp_enqueue_style(
            'searchwp-related-settings',
			SEARCHWP_RELATED_PLUGIN_URL . 'assets/css/admin/settings.css',
			[
				$slug . 'choicesjs',
				$slug . 'collapse-layout',
				$slug . 'input',
				$slug . 'modal',
				$slug . 'toggle-switch',
				$slug . 'radio-img',
				$slug . 'style',
			],
			SEARCHWP_RELATED_VERSION
        );
	}

	/**
	 * Get block preview container classes.
	 *
	 * @since 1.5.0
	 *
	 * @param array $settings Related settings.
	 *
	 * @return string
	 */
	private static function get_preview_container_classes( $settings ) {

		$classes = [];

		if ( $settings['swp-layout-style'] === 'list' ) {
			$classes[] = 'swp-flex';
		}

		if ( $settings['swp-layout-style'] === 'grid' ) {
			$classes[] = 'swp-grid';
			$classes[] = 'swp-grid--cols-3';
		}

		$image_size = $settings['swp-image-size'];
		if ( empty( $image_size ) || $image_size === 'none' ) {
			$classes[] = 'swp-result-item--img--off';
		}
		if ( $image_size === 'small' ) {
			$classes[] = 'swp-rp--img-sm';
		}
		if ( $image_size === 'medium' ) {
			$classes[] = 'swp-rp--img-m';
		}
		if ( $image_size === 'large' ) {
			$classes[] = 'swp-rp--img-l';
		}

        if ( empty( $settings['swp-description-enabled'] ) ) {
	        $classes[] = 'swp-result-item--desc--off';
        }

		return implode( ' ', $classes );
	}
}
