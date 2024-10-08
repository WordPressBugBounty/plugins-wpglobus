<?php
/**
 * File: class-wpglobus-options.php
 *
 * @package     WPGlobus\Admin\Options
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load the Request class.
require_once dirname( dirname( __FILE__ ) ) . '/admin/class-wpglobus-language-edit-request.php';

// Load the WPGlobus_Customize_Themes class.
require_once dirname( dirname( __FILE__ ) ) . '/admin/customize/class-wpglobus-customize-themes.php';

/**
 * Class WPGlobus_Options.
 */
class WPGlobus_Options {

	/**
	 * Nonce.
	 *
	 * @var string
	 */
	const NONCE_ACTION = 'wpglobus-options-panel';

	/**
	 * Default tab.
	 *
	 * @var string
	 */
	const DEFAULT_TAB = 'welcome';

	/**
	 *  WPGlobus Options image URL.
	 *
	 * @since 2.5.8
	 *
	 * @var string
	 */
	protected $url_options_image = '';

	/**
	 * Various settings.
	 *
	 * @var array
	 */
	protected $args = array();

	/**
	 * Sections.
	 *
	 * @var array
	 */
	protected $sections = array();

	/**
	 * Object @see WPGlobus::Config().
	 *
	 * @var WPGlobus_Config
	 */
	protected $config;

	/**
	 * Var @see WPGlobus::OPTIONS_PAGE_SLUG.
	 *
	 * @var string
	 */
	protected $page_slug;

	/**
	 * The current tab.
	 *
	 * @var string
	 */
	protected $tab;

	/**
	 * The current admin page.
	 *
	 * @var string
	 */
	protected $current_page;

	/**
	 * Constructor
	 */
	public function __construct() {

		$this->url_options_image = WPGlobus::$PLUGIN_DIR_URL . 'includes/options/images/';

		/**
		 * For developers use only. Deletes settings! Irreversible!
		 *
		 * @since 2.12.1
		 */
		add_action( 'admin_init', array( $this, 'on__admin_init' ) );

		add_action( 'init', array( $this, 'on__init' ), PHP_INT_MAX );

		add_action( 'wp_loaded', array( $this, 'on__wp_loaded' ), PHP_INT_MAX );

		add_action( 'admin_menu', array( $this, 'on__admin_menu' ) );

		add_action( 'admin_print_scripts', array( $this, 'on__admin_scripts' ) );

		add_action( 'admin_print_styles', array( $this, 'on__admin_styles' ) );

		/**
		 * AJAX
		 *
		 * @since 2.2.14
		 */
		add_action( 'wp_ajax_' . WPGLOBUS_AJAX, array( $this, 'on__process_ajax' ) );
	}

	/**
	 * For developers use only. Deletes settings! Irreversible!
	 *
	 * @since 2.12.1
	 */
	public function on__admin_init() {

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}

		/**
		 * WPDB
		 *
		 * @global wpdb $wpdb
		 */
		global $wpdb;

		/**
		 * For developers use only. Deletes settings! Irreversible!
		 *
		 * @link wp-admin/admin.php?page=wpglobus_options&wpglobus-reset-all-options=1
		 */
		if ( 1 === (int) WPGlobus_WP::get_http_get_parameter( 'wpglobus-reset-all-options' ) && ! empty( $_POST['_wpnonce'] ) ) {

			if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), self::NONCE_ACTION ) ) {

				$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE 'wpglobus_option%';" );
				wp_safe_redirect( admin_url() );
				exit();

			} else {
				wp_safe_redirect(
					add_query_arg(
						array(
							'page' => 'wpglobus_options',
						),
						admin_url( 'admin.php' )
					)
				);
				exit();
			}
		}
	}

	/**
	 * Handler `init`.
	 */
	public function on__init() {

		$this->setup_vars();

		// Before handle_submit().
		$this->init_settings();

		// Handle the main options form submit.
		// If data posted, the options will be updated, and page reloaded (so no continue to the next line).
		$this->handle_submit();
	}

	/**
	 * Handler `wp_loaded`.
	 */
	public function on__wp_loaded() {

		// Create the sections and fields.
		// This is delayed so we have, for example, all CPTs registered for the 'post_types' section.

		/**
		 * Set.
		 *
		 * @since 2.5.10
		 */
		if ( $this->current_page !== $this->page_slug ) {
			return;
		}

		$this->set_sections();
	}

	/**
	 * Handler `admin_menu`.
	 */
	public function on__admin_menu() {
		add_menu_page(
			$this->args['page_title'],
			$this->args['menu_title'],
			'manage_options',
			$this->page_slug,
			array( $this, 'on__add_menu_page' ),
			'dashicons-admin-site'
		);
	}

	/**
	 * Callback for @see add_menu_page().
	 */
	public function on__add_menu_page() {
		$this->page_options();
	}

	/**
	 * Enqueue admin scripts.
	 *
	 * @return void
	 */
	public function on__admin_scripts() {

		if ( $this->current_page !== $this->page_slug ) {
			return;
		}

		wp_register_script(
			'wpglobus-options',
			WPGlobus::plugin_dir_url() . 'includes/js/wpglobus-options' . WPGlobus::SCRIPT_SUFFIX() . '.js',
			array( 'jquery', 'jquery-ui-sortable' ),
			WPGLOBUS_VERSION,
			true
		);
		wp_enqueue_script( 'wpglobus-options' );
		wp_localize_script(
			'wpglobus-options',
			'WPGlobusOptions',
			array(
				'version'    => WPGLOBUS_VERSION,
				'tab'        => $this->tab,
				'defaultTab' => self::DEFAULT_TAB,
				'sections'   => $this->sections,
				'newUrl'     => add_query_arg(
					array(
						'page' => $this->page_slug,
						'tab'  => '{*}',
					),
					admin_url( 'admin.php' )
				),
			)
		);

		/**
		 * Enable jQuery-UI touch support.
		 *
		 * @link  http://touchpunch.furf.com/
		 * @link  https://github.com/furf/jquery-ui-touch-punch/
		 * @since 1.9.10
		 */
		wp_enqueue_script(
			'wpglobus-options-touch',
			WPGlobus::plugin_dir_url() . 'lib/jquery.ui.touch-punch' . WPGlobus::SCRIPT_SUFFIX() . '.js',
			array( 'wpglobus-options' ),
			WPGLOBUS_VERSION,
			true
		);
	}

	/**
	 * Enqueue admin styles.
	 *
	 * @return void
	 */
	public function on__admin_styles() {

		if ( $this->current_page !== $this->page_slug ) {
			return;
		}

		wp_register_style(
			'wpglobus-options',
			WPGlobus::plugin_dir_url() . 'includes/css/wpglobus-options.css',
			array( 'wpglobus-admin' ),
			WPGLOBUS_VERSION
		);
		wp_enqueue_style( 'wpglobus-options' );
	}

	/**
	 * Tell where to find our custom fields.
	 *
	 * @since 1.2.2
	 *
	 * @param string $file  Path of the field class.
	 * @param array  $field Field parameters.
	 *
	 * @return string Path of the field class where we want to find it
	 */
	public function filter__add_custom_fields(
		/**
		 * Unused.
		 *
		 * @noinspection PhpUnusedParameterInspection
		 */
		$file, $field
	) {

		$file = WPGlobus::plugin_dir_path() . "includes/options/fields/{$field['type']}/field_{$field['type']}.php";

		if ( ! file_exists( $file ) ) {
			return false;
		}

		return $file;
	}

	/**
	 * For WPGlobus Plus.
	 *
	 * @see WPGlobusPlus_Menu::add_option
	 *
	 * @return array Field parameters.
	 */
	public static function field_switcher_menu_style() {
		return array(
			'id'       => 'switcher_menu_style',
			'type'     => 'wpglobus_dropdown',
			'title'    => esc_html__( 'Language Selector Menu Style', 'wpglobus' ),
			'subtitle' => '(' . esc_html__( 'WPGlobus Plus', 'wpglobus' ) . ')',
			'desc'     => esc_html__( 'Drop-down languages menu or Flat (in one line)', 'wpglobus' ),
			'anchor'   => 'language-selector-menu-style',
			'options'  => array(
				''         => esc_html__( 'Do not change', 'wpglobus' ),
				'dropdown' => esc_html__( 'Drop-down (vertical)', 'wpglobus' ),
				'flat'     => esc_html__( 'Flat (horizontal)', 'wpglobus' ),
			),
		);
	}

	/**
	 * Setup variables.
	 */
	protected function setup_vars() {
		$this->page_slug = WPGlobus::OPTIONS_PAGE_SLUG;

		$this->current_page = WPGlobus_WP::get_http_get_parameter( 'page' );

		$_tab = WPGlobus_WP::get_http_get_parameter( 'tab' );
		if ( empty( $_tab ) || ! is_string( $_tab ) ) {
			$_tab = self::DEFAULT_TAB;
		}
		$this->tab = sanitize_title_with_dashes( $_tab );
	}

	/**
	 * Initialize settings.
	 */
	protected function init_settings() {

		$this->config = WPGlobus::Config();

		foreach (
			array(
				'wpglobus_info',
				'wpglobus_sortable',
				'wpglobus_select',
				'wpglobus_dropdown',
				'wpglobus_multicheck',
				'wpglobus_ace_editor',
				'wpglobus_checkbox',
				'table',
			) as $field_type
		) {
			add_filter( "wpglobus_options_field_{$field_type}", array( $this, 'filter__add_custom_fields' ), 0, 2 );
		}

		// Set the default arguments.
		$this->set_arguments();
	}

	/**
	 * The Options Panel page - linked to the admin menu.
	 */
	protected function page_options() {

		/**
		 * For developers use only. Deletes settings! Irreversible!
		 *
		 * @since 2.12.1
		 *
		 * @link  wp-admin/admin.php?page=wpglobus_options&wpglobus-reset-all-options=1
		 */
		if ( ! empty( WPGlobus_WP::get_http_get_parameter( 'wpglobus-reset-all-options' ) ) ) { ?>
			<div class="wrap">
				<h1>WPGlobus <?php echo esc_html( WPGLOBUS_VERSION ); ?></h1>
				<form id="form-wpglobus-options" method="post">
					<h3>Deletes settings! Irreversible!</h3>
					<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary"
								value="Reset all options"></p>
					<?php wp_nonce_field( self::NONCE_ACTION ); ?>
				</form>
			</div>
			<?php
			return;
		}
		?>
		<div class="wrap">
			<h1>WPGlobus <?php echo esc_html( WPGLOBUS_VERSION ); ?></h1>
			<div id="wpglobus-options-old-browser-warning" class="notice notice-error">
				<p><strong>
						<?php esc_html_e( 'If you see this message then your browser may not display the WPGlobus Settings panel properly. Please try another browser.', 'wpglobus' ); ?>
					</strong></p>
			</div>
			<div class="wpglobus-options-container">
				<form id="form-wpglobus-options" method="post">
					<div id="wpglobus-options-intro-text"><?php echo wp_kses_post( $this->args['intro_text'] ); ?></div>
					<div class="wpglobus-options-wrap">
						<div class="wpglobus-options-sidebar wpglobus-options-wrap__item">
							<ul class="wpglobus-options-menu">
								<?php foreach ( $this->sections as $section_tab => $section ) : ?>
									<?php
									if ( empty( $section ) ) {
										continue;
									}
									?>
									<?php $section = $this->sanitize_section( $section ); ?>
									<?php
									// If section tab is not specified (old external sections?), create it from title.
									if ( empty( $section_tab ) ) {
										$section_tab = sanitize_title_with_dashes( $section['title'] );
									}
									?>
									<li id="wpglobus-tab-link-<?php echo esc_attr( $section_tab ); ?>"
											class="<?php echo esc_attr( $section['li_class'] ); ?>"
											data-tab="<?php echo esc_attr( $section_tab ); ?>">
										<a href="<?php echo esc_url( $section['tab_href'] ); ?>"
											<?php
											// Disable A-clicks unless it's a real (external) link.
											if ( empty( $section['externalLink'] ) || ! $section['externalLink'] ) {
												echo ' onclick="return false;" ';
											}
											?>
												data-tab="<?php echo esc_attr( $section_tab ); ?>">
											<i class="<?php echo esc_attr( $section['icon'] ); ?>"></i>
											<span class="group_title"><?php echo esc_html( $section['title'] ); ?></span>
										</a>
									</li>
								<?php endforeach; ?>
							</ul>
						</div><!-- sidebar -->
						<div class="wpglobus-options-main wpglobus-options-wrap__item">
							<div class="wpglobus-options-info">
								<?php foreach ( $this->sections as $section_tab => $section ) : ?>
									<?php
									if ( empty( $section ) ) {
										continue;
									}
									?>
									<div id="section-tab-<?php echo esc_attr( $section_tab ); ?>"
											class="wpglobus-options-tab"
											data-tab="<?php echo esc_attr( $section_tab ); ?>">
										<?php if ( empty( $section['caption'] ) ) { ?>
											<h2><?php echo esc_html( $section['title'] ); ?></h2>
										<?php } else { ?>
											<h2><?php echo esc_html( $section['caption'] ); ?></h2>
										<?php } ?>
										<?php
										if ( ! empty( $section['fields'] ) ) {
											foreach ( $section['fields'] as $field ) {
												$field = $this->sanitize_field( $field );
												if ( ! $field ) {
													// Invalid field.
													continue;
												}

												$field_type = $field['type'];
												/**
												 * Filter wpglobus_options_field_{$field_type}
												 *
												 * @since 1.9.10
												 */
												$file = apply_filters( "wpglobus_options_field_{$field_type}", '', $field );
												if ( $file && file_exists( $file ) ) :
													/**
													 * Intentionally "require" and not "require_once".
													 */
													require $file;
												endif;
											} // foreach.
										}
										?>
									</div><!-- .wpglobus-options-tab -->
								<?php endforeach; ?>
								<?php
								wp_nonce_field( self::NONCE_ACTION );
								?>
								<input type="hidden" name="wpglobus_options_current_tab"
										id="wpglobus_options_current_tab"
										value="<?php echo esc_attr( $this->tab ); ?>"/>
							</div><!-- .wpglobus-options-info -->
						</div><!-- wpglobus-options-main block -->
						<?php submit_button(); ?>
					</div>
				</form>
			</div>
			<div class="clear"></div>
		</div><!-- .wrap -->
		<?php
	}

	/**
	 * Handle the `Save Changes` form submit.
	 */
	protected function handle_submit() {

		// Check if there were any posted data before nonce verification.

		$option_name = $this->config->option;
		if ( empty( $_POST[ $option_name ] ) || ! is_array( $_POST[ $option_name ] ) ) { // phpcs:ignore WordPress.CSRF.NonceVerification
			// No data or invalid data submitted.
			return;
		}

		// WP anti-hacks.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized user' );
		}
		check_admin_referer( self::NONCE_ACTION );

		// Sanitize, and if OK then save the options and reload the page.
		$posted_data = $this->sanitize_posted_data( WPGlobus_WP::get_http_post_parameter( $option_name ) );
		if ( $posted_data ) {
			update_option( $option_name, $posted_data );

			// Need to get back to the current tab after reloading.
			$tab = self::DEFAULT_TAB;

			$_POST_wpglobus_options_current_tab = WPGlobus_WP::get_http_post_parameter( 'wpglobus_options_current_tab' );
			if ( $_POST_wpglobus_options_current_tab ) {
				$tab = $_POST_wpglobus_options_current_tab;
			}

			wp_safe_redirect( WPGlobus_Admin_Page::url_options_panel( $tab ) );
		}
	}

	/**
	 * Settings.
	 */
	protected function set_arguments() {

		$this->args = array(

			'opt_name'      => $this->config->option,
			'menu_title'    => 'WPGlobus',
			'page_title'    => 'WPGlobus',
			'page_slug'     => $this->page_slug,
			'footer_credit' => '&copy; Copyright 2014-' . gmdate( 'Y' ) .
							   ', <a href="' . WPGlobus_Utils::url_wpglobus_site() . '">TIV.NET INC. / WPGlobus</a>.',
		);

		// TODO.
		$this->args['intro_text'] = '&nbsp;';

		// TODO: SOCIAL ICONS.
		/**
		 * $ga_campaign = '?utm_source=wpglobus-options-socials&utm_medium=link&utm_campaign=options-panel';
		 *
		 * $this->args['share_icons'][] = array(
		 * 'url'   => WPGlobus_Utils::url_wpglobus_site() . 'quick-start/' . $ga_campaign,
		 * 'title' => esc_html__( 'Read the Quick Start Guide', 'wpglobus' ),
		 * 'icon'  => 'el el-question-sign',
		 * );
		 * $this->args['share_icons'][] = array(
		 * 'url'   => WPGlobus_Utils::url_wpglobus_site() . $ga_campaign,
		 * 'title' => esc_html__( 'Visit our website', 'wpglobus' ),
		 * 'icon'  => 'el el-globe',
		 * );
		 * $this->args['share_icons'][] = array(
		 * 'url'   => WPGlobus_Utils::url_wpglobus_site() . 'product/woocommerce-wpglobus/' . $ga_campaign,
		 * 'title' => esc_html__( 'Buy WooCommerce WPGlobus extension', 'wpglobus' ),
		 * 'icon'  => 'el el-icon-shopping-cart',
		 * );
		 * $this->args['share_icons'][] = array(
		 * 'url'   => 'https://github.com/WPGlobus',
		 * 'title' => esc_html__( 'Collaborate on GitHub', 'wpglobus' ),
		 * 'icon'  => 'el el-github'
		 * //'img'   => '', // You can use icon OR img. IMG needs to be a full URL.
		 * );
		 * $this->args['share_icons'][] = array(
		 * 'url'   => 'https://www.facebook.com/WPGlobus',
		 * 'title' => esc_html__( 'Like us on Facebook', 'wpglobus' ),
		 * 'icon'  => 'el el-facebook',
		 * );
		 * $this->args['share_icons'][] = array(
		 * 'url'   => 'https://twitter.com/WPGlobus',
		 * 'title' => esc_html__( 'Follow us on Twitter', 'wpglobus' ),
		 * 'icon'  => 'el el-twitter',
		 * );
		 * $this->args['share_icons'][] = array(
		 * 'url'   => 'https://www.linkedin.com/company/wpglobus',
		 * 'title' => esc_html__( 'Find us on LinkedIn', 'wpglobus' ),
		 * 'icon'  => 'el el-linkedin',
		 * );
		 * $this->args['share_icons'][] = array(
		 * 'url'   => 'https://plus.google.com/+Wpglobus',
		 * 'title' => esc_html__( 'Circle us on Google+', 'wpglobus' ),
		 * 'icon'  => 'el el-googleplus',
		 * );
		 */
	}

	/**
	 * Set sections.
	 *
	 * @since 2.5.12 Moved filter to end of the function.
	 */
	protected function set_sections() {

		$this->sections['welcome']          = $this->section_welcome();
		$this->sections['languages']        = $this->section_languages();
		$this->sections['language-table']   = $this->section_languages_table();
		$this->sections['post-types']       = $this->section_post_types();
		$this->sections['browser_redirect'] = $this->section_browser_redirect();
		$this->sections['customizer']       = $this->section_customizer();
		$this->sections['compatibility']    = $this->section_compatibility();
		$this->sections['block-editor']     = $this->section_block_editor();
		$this->sections['seo']              = $this->section_seo(); // @since 2.3.4
		$this->sections['rest-api']         = $this->section_rest_api(); // @since 2.5.8

		if ( defined( 'WPGLOBUS_PLUS_VERSION' ) ) {
			$this->sections['wpglobus-plus'] = $this->section_wpglobus_plus();
		}

		/**
		 * Links to Admin Central
		 */
		if ( class_exists( 'WPGlobus_Admin_Central', false ) ) {
			if ( class_exists( 'WPGlobusMobileMenu', false ) ) {
				$this->sections['mobile-menu'] = $this->section_mobile_menu();
			}
			if ( class_exists( 'WPGlobus_Language_Widgets', false ) ) {
				$this->sections['language-widgets'] = $this->section_language_widgets();
			}
			if ( class_exists( 'WPGlobus_Featured_Images', false ) ) {
				$this->sections['featured-images'] = $this->section_featured_images();
			}
		}

		// This section is added only if it's not empty.
		$section_recommendations = $this->section_recommendations();
		if ( count( $section_recommendations ) ) {
			$this->sections['recommendations'] = $section_recommendations;
		}

		// $this->sections['addons'] = $this->section_all_addons();

		if ( class_exists( 'WPGlobus_Admin_HelpDesk', false ) ) {
			$this->sections['helpdesk'] = $this->section_helpdesk();
		}

		$this->sections['custom-code'] = $this->section_custom_code();

		// @since 2.2.23 @todo to use in future versions.
		//$this->sections['debug-info'] = $this->section_debug_info();

		$this->sections['uninstall'] = $this->section_uninstall();

		/**
		 * Filter the array of sections. Here add-ons can add their menus.
		 *
		 * @since 2.5.12
		 *
		 * @param array $sections Array of sections.
		 */
		$this->sections = apply_filters( 'wpglobus_option_sections', $this->sections );
	}

	/**
	 * SECTION: Welcome.
	 */
	protected function section_welcome() {

		$fields_home = array();

		$tab_compatibility = __( 'Сompatibility', 'wpglobus' );

		/**
		 * The Welcome message.
		 */
		$fields_home[] =
			array(
				'id'    => 'welcome_intro',
				'type'  => 'wpglobus_info',
				'title' => __( 'Thank you for installing WPGlobus!', 'wpglobus' ),
				'desc'  => '&bull; ' .
						   '<a href="' . esc_url( WPGlobus_Admin_Page::url_about() ) . '">' .
						   esc_html__( 'Read About WPGlobus', 'wpglobus' ) .
						   '</a>' .
						   '<br/>' .
						   // Translators: placeholders for "strong" tags.
						   '&bull; ' . sprintf( esc_html__( 'Click the %1$s[Languages]%2$s tab at the left to setup the options.', 'wpglobus' ), '<strong>', '</strong>' ) .
						   '<br/>' .
						   // Translators: placeholders for "strong" tags.
						   '&bull; ' . sprintf( esc_html__( 'Use the %1$s[Languages Table]%2$s section to add a new language or to edit the language attributes: name, code, flag icon, etc.', 'wpglobus' ), '<strong>', '</strong>' ) .
						   '<br/>' .
						   '<br/>' .
						   '<h4>' . esc_html__( 'Important notes', 'wpglobus' ) . '</h4>' .
						   // Translators: placeholders for "strong" tags.
						   sprintf( esc_html__( '%1$sWPGlobus%2$s operates in two modes', 'wpglobus' ), '<strong>', '</strong>' ) . ':' .
						   '<br/>' .
						   // Translators: placeholders for "strong" tags; compatibility tab link.
						   '&nbsp;&nbsp;&nbsp;&bull; ' . sprintf( esc_html__( '%1$sBuilder mode%2$s: WPGlobus turns this mode on automatically when it discovers any of the plugins/add-ons listed on the %1$s[%3$s]%2$s tab.', 'wpglobus' ), '<strong>', '</strong>', $tab_compatibility ) .
						   '<br/>' .
						   // Translators: placeholders for "strong" tags; compatibility tab link.
						   '&nbsp;&nbsp;&nbsp;&bull; ' . sprintf( esc_html__( '%1$sStandard/Classic mode%2$s: is used when there are no plugins `Builder` or if you explicitly turned off builder support on the %1$s[%3$s]%2$s tab.', 'wpglobus' ), '<strong>', '</strong>', $tab_compatibility ) .
						   '<br/>' .
						   '<br/>' .
						   // Translators: placeholders for "strong" tags.
						   '&nbsp;&nbsp;&nbsp;&bull; ' . sprintf( esc_html__( 'The %1$sBuilder mode%2$s is turned OFF by default for all custom post types (CPT).', 'wpglobus' ), '<strong>', '</strong>' ) .
						   '<br/>' .
						   // Translators: placeholders for "strong" tags.
						   '&nbsp;&nbsp;&nbsp;&bull; ' . sprintf( esc_html__( 'To turn on the %1$sBuilder mode%2$s for specific post types, please visit the %1$s[%3$s]%2$s tab.', 'wpglobus' ), '<strong>', '</strong>', $tab_compatibility ) .
						   '<br/>' .
						   '<br/>' .
						   esc_html__( 'Should you have any questions or comments, please do not hesitate to contact us.', 'wpglobus' ) .
						   '<br/>' .
						   '<br/>' .
						   '<em>' .
						   esc_html__( 'Sincerely Yours,', 'wpglobus' ) .
						   '<br/>' .
						   esc_html__( 'The WPGlobus Team', 'wpglobus' ) .
						   '</em>',
				'class' => 'info',
			);

		return array(
			'wpglobus_id' => 'welcome',
			'title'       => __( 'Welcome!', 'wpglobus' ),
			'icon'        => 'dashicons dashicons-admin-site',
			'fields'      => $fields_home,
		);
	}

	/**
	 * Section: Uninstall.
	 *
	 * @return array
	 */
	protected function section_uninstall() {

		// translators: %?$s: HTML codes for hyperlink. Do not remove.
		$txt_link_to_cleanup_tool = sprintf( esc_html__( '%1$sClean-up Tool%2$s', 'wpglobus' ), '<a href="' . esc_url( WPGlobus_Admin_Page::url_clean_up_tool() ) . '">', '</a>' );

		$fields_home = array();

		$fields_home[] =
			array(
				'id'     => 'wpglobus_clean',
				'type'   => 'wpglobus_info',
				'title'  => __( 'Deactivating / Uninstalling', 'wpglobus' ),
				'desc'   => '<em>' .
							sprintf(// Translators: %?$s: HTML codes for hyperlink. Do not remove.
								esc_html__( 'We would hate to see you go. If something goes wrong, do not uninstall WPGlobus yet. Please %1$stalk to us%2$s and let us help!', 'wpglobus' ),
								'<a href="' . esc_url( WPGlobus_Admin_Page::url_helpdesk() ) . '">',
								'</a>'
							) .
							'</em>' .
							'<hr/>' .
							'<i class="dashicons dashicons-flag" style="color:red"></i> <strong>' .
							esc_html( __( 'Please note that if you deactivate WPGlobus, your site will show all the languages together, mixed up. You will need to remove all translations, keeping only one language.', 'wpglobus' ) ) .
							'</strong>' .
							'<hr>' .
							sprintf(// translators: %s: link to the Clean-up Tool.
								esc_html__( 'If there are just a few places, you should edit them manually. To automatically remove all translations at once, you can use the %s. WARNING: The clean-up operation is irreversible, so use it only if you need to completely uninstall WPGlobus.', 'wpglobus' ),
								$txt_link_to_cleanup_tool
							),
				'style'  => 'normal',
				'notice' => false,
				'class'  => 'normal',
			);

		return array(
			'wpglobus_id' => 'uninstall',
			'title'       => __( 'Uninstall', 'wpglobus' ),
			'icon'        => 'dashicons dashicons-no',
			'fields'      => $fields_home,
		);
	}

	/**
	 * Section: Help Desk.
	 *
	 * @return array
	 */
	protected function section_helpdesk() {
		return array(
			'wpglobus_id'  => 'helpdesk',
			'title'        => __( 'Help Desk', 'wpglobus' ),
			'tab_href'     => WPGlobus_Admin_Page::url_helpdesk(),
			'icon'         => WPGlobus_Admin_Page::nav_tab_icon( 'Helpdesk' ),
			'externalLink' => true,
		);
	}

	/**
	 * Link: All Addons.
	 *
	 * @return array
	 */
	protected function section_all_addons() {
		return array(
			'wpglobus_id'  => 'addons',
			'title'        => __( 'All add-ons', 'wpglobus' ),
			'tab_href'     => WPGlobus_Admin_Page::url_addons(),
			'icon'         => 'dashicons dashicons-admin-plugins',
			'externalLink' => true,
		);
	}

	/**
	 * Link: Mobile Menu.
	 *
	 * @return array
	 */
	protected function section_mobile_menu() {
		return array(
			'wpglobus_id'  => 'mobile_menu',
			'title'        => __( 'Mobile Menu', 'wpglobus' ),
			'tab_href'     => WPGlobus_Admin_Page::url_admin_central( 'tab-mobile-menu' ),
			'icon'         => 'dashicons dashicons-smartphone',
			'externalLink' => true,
		);
	}

	/**
	 * Link: Language Widgets.
	 *
	 * @return array
	 */
	protected function section_language_widgets() {
		return array(
			'wpglobus_id'  => 'language_widgets',
			'title'        => __( 'Language Widgets', 'wpglobus' ),
			'tab_href'     => WPGlobus_Admin_Page::url_admin_central( 'tab-language-widgets' ),
			'icon'         => 'dashicons dashicons-archive',
			'externalLink' => true,
		);
	}

	/**
	 * Link: Featured Images.
	 *
	 * @return array
	 */
	protected function section_featured_images() {
		return array(
			'wpglobus_id'  => 'featured_images',
			// DO NOT TRANSLATE.
			'title'        => __( 'Featured Images' ),
			'tab_href'     => WPGlobus_Admin_Page::url_admin_central( 'tab-featured-images' ),
			'icon'         => 'dashicons dashicons-images-alt',
			'externalLink' => true,
		);
	}

	/**
	 * Link: WPGlobus Plus.
	 *
	 * @return array
	 */
	protected function section_wpglobus_plus() {
		return array(
			'wpglobus_id'  => 'wpglobus_plus',
			'title'        => __( 'WPGlobus Plus', 'wpglobus' ),
			'tab_href'     => WPGlobus_Admin_Page::url_wpglobus_plus_panel(),
			'icon'         => 'dashicons dashicons-plus-alt',
			'externalLink' => true,
		);
	}

	/**
	 * Section: Recommendations.
	 *
	 * @return array
	 */
	protected function section_recommendations() {

		$tab_content = array();

		$_ = $this->recommend_wpg_plus();
		if ( count( $_ ) ) {
			$tab_content[] = $_;
		}
		$_ = $this->recommend_wpg_wc();
		if ( count( $_ ) ) {
			$tab_content[] = $_;
		}
		// $_ = $this->recommend_wpg_mc();
		// if ( count( $_ ) ) {
		// 	$tab_content[] = $_;
		// }
		$_ = $this->recommend_wpg_store();
		if ( count( $_ ) ) {
			$tab_content[] = $_;
		}

		if ( ! count( $tab_content ) ) {
			return array();
		}

		return array(
			'wpglobus_id' => 'recommendations',
			'title'       => __( 'We Recommend...', 'wpglobus' ),
			'icon'        => 'dashicons dashicons-megaphone',
			'fields'      => $tab_content,
		);
	}

	/**
	 * Recommend: WPGlobus Plus.
	 *
	 * @return array
	 */
	protected function recommend_wpg_plus() {

		if ( defined( 'WPGLOBUS_PLUS_VERSION' ) || $this->is_plugin_installed( 'wpglobus-plus' ) ) {
			return array();
		}

		$id           = 'recommend_wpg_plus';
		$product_slug = 'wpglobus-plus';
		$url          = $this->url_ga( WPGlobus_Utils::url_wpglobus_site() . "product/$product_slug/", $id );

		ob_start();

		?>
		<div class="wpglobus-recommend-container">
			<div class="wpglobus-recommend-logo grid__item">
				<img src="<?php echo esc_url( WPGlobus::plugin_dir_url() ); ?>includes/css/images/wpglobus-plus-logo-300x300.png"
						alt=""/>
			</div>
			<div class="grid__item">
				<h3><?php esc_html_e( 'WPGlobus Plus', 'wpglobus' ); ?></h3>
				<p><strong>
						<?php esc_html_e( 'Our premium add-on, WPGlobus Plus, will add several features to your website, such as:', 'wpglobus' ); ?>
					</strong></p>
				<p>
					<?php esc_html_e( '- Ability to write a post in one language and immediately publish it, not waiting for the translation to other languages;', 'wpglobus' ); ?>
				</p>
				<p>
					<?php esc_html_e( '- Set different URLs for each translation;', 'wpglobus' ); ?>
				</p>
				<p>
					<?php esc_html_e( '- In Yoast SEO, set the focus keyword and do the Page Analysis separately for each translation;', 'wpglobus' ); ?>
				</p>
				<p>
					<?php esc_html_e( '- and more...', 'wpglobus' ); ?>
				</p>
				<a class="button button-primary" href="<?php echo esc_url( $url ); ?>">
					<?php esc_html_e( 'Click here to download', 'wpglobus' ); ?>
				</a>
			</div>
		</div>
		<?php

		$content_body = ob_get_clean();

		return array(
			'id'   => $id . '_content',
			'type' => 'wpglobus_info',
			'desc' => $content_body,
		);
	}

	/**
	 * Recommend: WPGlobus for WooCommerce.
	 *
	 * @return array
	 */
	protected function recommend_wpg_wc() {

		if ( ! $this->is_plugin_installed( 'woocommerce' ) ) {
			return array();
		}

		if ( defined( 'WOOCOMMERCE_WPGLOBUS_VERSION' )
			 || $this->is_plugin_installed( 'woocommerce-wpglobus' )
		) {
			return array();
		}

		$id           = 'recommend_wpg_wc';
		$product_slug = 'woocommerce-wpglobus';
		$url          = $this->url_ga( WPGlobus_Utils::url_wpglobus_site() . "product/$product_slug/", $id );

		ob_start();

		?>
		<div class="wpglobus-recommend-container">
			<div class="wpglobus-recommend-logo grid__item">
				<img src="<?php echo esc_url( WPGlobus::plugin_dir_url() ); ?>includes/css/images/woocommerce-wpglobus-logo-300x300.png"
						alt=""/>
			</div>
			<div class="grid__item">
				<h3><?php esc_html_e( 'WPGlobus for WooCommerce', 'wpglobus' ); ?></h3>
				<p>

					<?php esc_html_e( 'Thanks for installing WPGlobus! Now you have a multilingual website and can translate your blog posts and pages to many languages.', 'wpglobus' ); ?>
				</p>
				<p><strong>
						<?php esc_html_e( 'The next step is to translate your WooCommerce-based store!', 'wpglobus' ); ?>
					</strong></p>
				<p>
					<?php esc_html_e( 'With the WPGlobus for WooCommerce premium add-on, you will be able to translate product titles and descriptions, categories, tags and attributes.', 'wpglobus' ); ?>
				</p>
				<a class="button button-primary" href="<?php echo esc_url( $url ); ?>">
					<?php esc_html_e( 'Click here to download', 'wpglobus' ); ?>
				</a>
			</div>
		</div>
		<?php

		$content_body = ob_get_clean();

		return array(
			'id'   => $id . '_content',
			'type' => 'wpglobus_info',
			'desc' => $content_body,
		);
	}

	/**
	 * Recommend: WPGlobus Multi-currency.
	 *
	 * @return array
	 * @noinspection PhpUnused
	 */
	protected function recommend_wpg_mc() {
		if ( ! $this->is_plugin_installed( 'woocommerce' ) ) {
			return array();
		}

		if ( defined( 'WPGLOBUS_MC_VERSION' )
			 || $this->is_plugin_installed( 'wpglobus-multi-currency' )
		) {
			return array();
		}

		$id           = 'recommend_wpg_mc';
		$product_slug = 'wpglobus-multi-currency';
		$url          = $this->url_ga( WPGlobus_Utils::url_wpglobus_site() . "product/$product_slug/", $id );

		ob_start();

		?>
		<div class="wpglobus-recommend-container">
			<div class="wpglobus-recommend-logo grid__item">
				<img src="<?php echo esc_url( WPGlobus::plugin_dir_url() ); ?>includes/css/images/wpglobus-multi-currency-logo.jpg"
						alt=""/>
			</div>
			<div class="grid__item">
				<h3><?php esc_html_e( 'Multi-currency', 'wpglobus' ); ?></h3>
				<p><strong>
						<?php
						esc_html_e( 'Your WooCommerce-powered store is set to show prices and accept payments in a single currency only.', 'wpglobus' );
						?>
					</strong></p>
				<p>
					<?php esc_html_e( 'With WPGlobus, you can add multiple currencies to your store and charge UK customers in Pounds, US customers in Dollars, Spanish clients in Euros, etc. Accepting multiple currencies will strengthen your competitive edge and positioning for global growth!', 'wpglobus' ); ?>

				</p>
				<p>
					<?php esc_html_e( 'The WPGlobus Multi-Currency premium add-on provides switching currencies and re-calculating prices on-the-fly.', 'wpglobus' ); ?>
				</p>
				<a class="button button-primary" href="<?php echo esc_url( $url ); ?>">
					<?php esc_html_e( 'Click here to download', 'wpglobus' ); ?>
				</a>
			</div>
		</div>
		<?php

		$content_body = ob_get_clean();

		return array(
			'id'   => $id . '_content',
			'type' => 'wpglobus_info',
			'desc' => $content_body,
		);
	}

	/**
	 * Recommend: WPGlobus Store.
	 *
	 * @return array
	 */
	protected function recommend_wpg_store() {

		$id  = 'recommend_wpg_store';
		$url = $this->url_ga( WPGlobus_Utils::url_wpglobus_site() . 'shop/', $id );

		ob_start();

		?>
		<div class="wpglobus-recommend-container">
			<div class="wpglobus-recommend-logo grid__item">
				<img src="<?php echo esc_url( WPGlobus::plugin_dir_url() ); ?>includes/css/images/wpglobus-logo.jpg"
						alt=""/>
			</div>
			<div class="grid__item">
				<h3><?php esc_html_e( 'WPGlobus Premium Add-ons', 'wpglobus' ); ?></h3>
				<p><strong>
						<?php esc_html_e( 'We have written several Premium add-ons for WPGlobus. With those add-ons, you will be able to:', 'wpglobus' ); ?>
					</strong></p>
				<blockquote>
					<ul>
						<li>
							- <?php echo wp_kses_post( __( '<strong>Translate URLs</strong> (/my-page/ translates to /fr/ma-page, /es/mi-pagina and so on);', 'wpglobus' ) ); ?>
						</li>
						<li>
							- <?php echo wp_kses_post( __( 'Postpone translation to some languages and <strong>publish only the translated texts</strong>;', 'wpglobus' ) ); ?>
						</li>
						<li>
							- <?php echo wp_kses_post( __( 'Maintain <strong>separate menus and widgets for each language</strong>;', 'wpglobus' ) ); ?>
						</li>
						<li>
							- <?php echo wp_kses_post( __( '<strong>Translate WooCommerce</strong> products and taxonomies;', 'wpglobus' ) ); ?>
						</li>
						<li>
							- <?php echo wp_kses_post( __( 'Enter separate focus keywords for each language in the <strong>Yoast SEO</strong>;', 'wpglobus' ) ); ?>
						</li>
					</ul>
				</blockquote>
				<p><?php esc_html_e( '...and more.', 'wpglobus' ); ?></p>
				<a class="button button-primary" href="<?php echo esc_url( $url ); ?>">
					<i class="dashicons dashicons-cart" style="vertical-align:middle"></i>
					<?php esc_html_e( 'Click here to visit the WPGlobus Store', 'wpglobus' ); ?>
				</a>
			</div>
		</div>
		<?php

		$content_body = ob_get_clean();

		return array(
			'id'   => $id . '_content',
			'type' => 'wpglobus_info',
			'desc' => $content_body,
		);
	}

	/**
	 * SECTION: Languages.
	 */
	protected function section_languages() {

		$wpglobus_option = get_option( $this->args['opt_name'] );

		/**
		 * Contains all enabled languages.
		 *
		 * @var array $enabled_languages
		 */
		$enabled_languages = array();

		/**
		 * Need for the sortable field setup.
		 *
		 * @var array $defaults_for_enabled_languages
		 */
		$defaults_for_enabled_languages = array();

		/**
		 * Additional languages.
		 *
		 * @var array $more_languages
		 */
		$more_languages = array( '' => __( 'Select a language', 'wpglobus' ) );

		foreach ( $this->config->enabled_languages as $code ) {
			$lang_in_en = '';
			if ( isset( $this->config->en_language_name[ $code ] ) && ! empty( $this->config->en_language_name[ $code ] ) ) {
				$lang_in_en = ' (' . $this->config->en_language_name[ $code ] . ')';
			}

			$enabled_languages[ $code ]              = $this->config->language_name[ $code ] . $lang_in_en;
			$defaults_for_enabled_languages[ $code ] = true;
		}

		/** Generate array $more_languages */
		foreach ( $this->config->flag as $code => $file ) {
			if ( ! array_key_exists( $code, $enabled_languages ) ) {
				$lang_in_en = '';
				if ( isset( $this->config->en_language_name[ $code ] ) && ! empty( $this->config->en_language_name[ $code ] ) ) {
					$lang_in_en = ' (' . $this->config->en_language_name[ $code ] . ')';
				}
				$more_languages[ $code ] = $this->config->language_name[ $code ] . $lang_in_en;
			}
		}

		$desc_languages_intro = implode(
			'',
			array(
				'<ul style="list-style: disc inside;">',
				'<li>' .
				// translators: %3$s placeholder for the icon (actual picture).
				sprintf( esc_html__( 'Place the %1$smain language%2$s of your site at the top of the list by dragging the %3$s icons.', 'wpglobus' ), '<strong>', '</strong>', '<i class="dashicons dashicons-move"></i>' ) . '</li>',
				'<li>' .
				// translators: placeholders for the "strong" HTML tags.
				sprintf( esc_html__( '%1$sUncheck%2$s the languages you do not plan to use.', 'wpglobus' ), '<strong>', '</strong>' ) . '</li>',
				'<li>' .
				// translators: placeholders for the "strong" HTML tags.
				sprintf( esc_html__( '%1$sAdd%2$s more languages using the section below.', 'wpglobus' ), '<strong>', '</strong>' ) . '</li>',
				'<li>' . esc_html__( 'When done, click the [Save Changes] button.', 'wpglobus' ) . '</li>',
				'</ul>',
			)
		);

		$txt_save_changes = esc_html__( 'Save Changes' );

		$desc_more_languages =
			esc_html__( 'Choose a language you would like to enable.', 'wpglobus' )
			. '<br />'
			// translators: %s - placeholder for the "Save Changes" button text.
			. sprintf( esc_html__( 'Press the %s button to confirm.', 'wpglobus' ), '<code>[' . $txt_save_changes . ']</code>' )
			. '<br /><br />'
			// translators: %1$s and %2$s - placeholders to insert HTML link around 'here'.
			. sprintf( esc_html__( 'or Add new Language %1$s here %2$s', 'wpglobus' ), '<a href="' . esc_url( WPGlobus_Language_Edit_Request::url_language_add() ) . '">', '</a>' );

		if ( empty( $wpglobus_option['enabled_languages'] ) ) {
			$_value_for_enabled_languages = $defaults_for_enabled_languages;
		} else {
			$_value_for_enabled_languages = $wpglobus_option['enabled_languages'];
		}

		$nav_menus = WPGlobus::get_nav_menus();

		/**
		 * Make 'Language Selector Menu' option.
		 */
		// translators: dropdown option meaning that none of the navigation menus should show the language selector.
		$menus['--none--'] = __( '-- none --', 'wpglobus' );
		$menus['all']      = __( 'All menus', 'wpglobus' );
		foreach ( $nav_menus as $menu ) {
			$menus[ $menu->slug ] = $menu->name;
		}

		return array(
			'wpglobus_id' => 'languages',
			'title'       => __( 'Languages', 'wpglobus' ),
			'icon'        => 'dashicons dashicons-translation',
			'fields'      => array(
				array(
					'id'    => 'languages_intro',
					'type'  => 'wpglobus_info',
					'title' => __( 'Instructions:', 'wpglobus' ),
					'html'  => $desc_languages_intro,
					'class' => 'normal',
				),
				array(
					'id'          => 'enabled_languages',
					'type'        => 'wpglobus_sortable',
					'title'       => __( 'Enabled Languages', 'wpglobus' ),
					'subtitle'    => esc_html__( 'These languages are currently enabled on your site.', 'wpglobus' ),
					'compiler'    => 'false',
					'options'     => $enabled_languages,
					'default'     => $defaults_for_enabled_languages,
					'mode'        => 'checkbox',
					'name'        => 'wpglobus_option[enabled_languages]',
					'name_suffix' => '',
					'value'       => $_value_for_enabled_languages,
					'class'       => 'wpglobus-enabled_languages',
				),
				array(
					'id'      => 'more_languages',
					'type'    => 'wpglobus_dropdown',
					'title'   => __( 'Add Languages', 'wpglobus' ),
					'desc'    => $desc_more_languages,
					'options' => $more_languages,
					'default' => '', // Do not remove.
				),
				array(
					'id'      => 'show_flag_name',
					'type'    => 'wpglobus_dropdown',
					'title'   => __( 'Language Selector Mode', 'wpglobus' ),
					'desc'    => __( 'Choose the way language name and country flag are shown in the drop-down menu', 'wpglobus' ),
					'options' => array(
						'code'      => __( 'Two-letter Code with flag (en, ru, it, etc.)', 'wpglobus' ),
						'full_name' => __( 'Full Name (English, Russian, Italian, etc.)', 'wpglobus' ),
						'name'      => __( 'Full Name with flag (English, Russian, Italian, etc.)', 'wpglobus' ),
						'empty'     => __( 'Flags only', 'wpglobus' ),
					),
					'default' => ( empty( $wpglobus_option['show_flag_name'] )
						? 'code'
						: $wpglobus_option['show_flag_name'] ),
					'name'    => 'wpglobus_option[show_flag_name]',
				),
				/**
				 * $WPGlobus_Config->nav_menu
				 */
				array(
					'id'      => 'use_nav_menu',
					'type'    => 'wpglobus_dropdown',
					'title'   => __( 'Language Selector Menu', 'wpglobus' ),
					'desc'    => __( 'Choose the navigation menu where the language selector will be shown', 'wpglobus' ),
					'options' => $menus,
					'default' => ( empty( $wpglobus_option['use_nav_menu'] )
						? '--none--'
						: $wpglobus_option['use_nav_menu'] ),
					'name'    => 'wpglobus_option[use_nav_menu]',
				),
				array(
					'id'       => 'selector_wp_list_pages',
					'type'     => 'wpglobus_checkbox',
					'title'    => esc_html__( '"All Pages" menus Language selector', 'wpglobus' ),
					'subtitle' => esc_html__( '(Found in some themes)', 'wpglobus' ),
					'desc'     => esc_html__( 'Adds language selector to the menus that automatically list all existing pages (using `wp_list_pages`)', 'wpglobus' ),
					'label'    => __( 'Enable', 'wpglobus' ),
				),
			),
		);
	}

	/**
	 * SECTION: Language table.
	 */
	protected function section_languages_table() {
		return array(
			'wpglobus_id' => 'language_table',
			'title'       => esc_html__( 'Languages table', 'wpglobus' ),
			'icon'        => 'dashicons dashicons-list-view',
			'fields'      => array(
				array(
					'id'       => 'description',
					'type'     => 'wpglobus_info',
					'title'    => esc_html__( 'Use this table to add, edit or delete languages.', 'wpglobus' ),
					'subtitle' => esc_html__( 'NOTE: you cannot remove the main language.', 'wpglobus' ),
					'style'    => 'info',
					'notice'   => false,
				),
				array(
					'id'   => 'languagesTable',
					'type' => 'table',
				),
			),
		);
	}

	/**
	 * SECTION: Post types.
	 *
	 * @return array
	 */
	protected function section_post_types() {

		/**
		 * Set.
		 *
		 * @since 2.2.11
		 */
		$post_types = $this->get_post_types();

		$options = array();

		foreach ( $post_types as $post_type ) {

			$label = $post_type->label . ' (' . $post_type->name . ')';

			$checked = ! $post_type->wpglobus['post_type_disabled'];

			$options[ $post_type->name ] = array(
				'label'   => $label,
				'checked' => $checked,
			);
		}

		$fields = array();

		$fields[] =
			array(
				'id'       => 'wpglobus_post_types_choose',
				'type'     => 'wpglobus_multicheck',
				'options'  => $options,
				'name'     => 'wpglobus_option[post_type]',
				'title'    => esc_html__( 'WPGlobus is enabled on these Post Types', 'wpglobus' ),
				'subtitle' => esc_html__( 'Uncheck to disable', 'wpglobus' ),
				'desc'     => esc_html__( 'Please note that there are post types, which status is managed by other plugins and cannot be changed here.', 'wpglobus' ),
			);

		return array(
			'wpglobus_id' => 'wpglobus_post_types',
			'title'       => esc_html__( 'Post Types', 'wpglobus' ),
			'icon'        => 'dashicons dashicons-admin-post',
			'fields'      => $fields,
		);
	}

	/**
	 * Section "Custom Code".
	 *
	 * @return array
	 */
	protected function section_custom_code() {

		$wpglobus_option = get_option( $this->args['opt_name'] );

		$intro_html = '<p class="wp-ui-notification notice-large">' .
					  __( 'You should put here only the code provided by WPGlobus Support. Do not write anything else in the sections below as it might break the functionality of your website!', 'wpglobus' )
					  . '</p>';

		$fields = array();

		$fields[] =
			array(
				'id'     => 'wpglobus_custom_code_intro',
				'type'   => 'wpglobus_info',
				'html'   => $intro_html,
				'style'  => 'normal',
				'notice' => false,
				'class'  => 'normal',
			);

		$fields[] =
			array(
				'id'       => 'wpglobus_custom_code_css',
				'type'     => 'wpglobus_ace_editor',
				'title'    => __( 'Custom CSS', 'wpglobus' ),
				'mode'     => 'css',
				'name'     => 'wpglobus_option[css_editor]',
				'value'    => empty( $wpglobus_option['css_editor'] ) ? '' : $wpglobus_option['css_editor'],
				'subtitle' => '',
				'desc'     => '',
			);

		$fields[] =
			array(
				'id'       => 'wpglobus_custom_code_js',
				'type'     => 'wpglobus_ace_editor',
				'title'    => __( 'Custom JS Code', 'wpglobus' ),
				'mode'     => 'javascript',
				'name'     => 'wpglobus_option[js_editor]',
				'value'    => empty( $wpglobus_option['js_editor'] ) ? '' : $wpglobus_option['js_editor'],
				'subtitle' => '',
				'desc'     => '',
			);

		return array(
			'wpglobus_id' => 'wpglobus_custom_code',
			'title'       => __( 'Custom Code', 'wpglobus' ),
			'icon'        => 'dashicons dashicons-edit',
			'fields'      => $fields,
		);
	}

	/**
	 * Section "Browser redirect".
	 *
	 * @return array
	 */
	protected function section_browser_redirect() {
		$fields = array();

		$fields[] =
			array(
				'id'    => 'browser_redirect_intro',
				'type'  => 'wpglobus_info',
				'title' => __( 'When a user comes to the site for the first time, try to find the best matching language version of the page.', 'wpglobus' ),
				'class' => 'normal',
			);

		$wpglobus_option = get_option( $this->args['opt_name'] );

		$options = array();

		/**
		 * Only one option is implemented at this time.
		 * When we add more options, need to update the @see WPGlobus_Options::sanitize_posted_data() method.
		 */
		$options['redirect_by_language'] = array(
			'label'   => __( 'Preferred language set in the browser', 'wpglobus' ),
			'checked' => ! empty( $wpglobus_option['browser_redirect']['redirect_by_language'] ),
		);

		$fields[] =
			array(
				'id'      => 'browser_redirect_choose',
				'type'    => 'wpglobus_multicheck',
				'options' => $options,
				'name'    => 'wpglobus_option[browser_redirect]',
				'title'   => __( 'Choose the language automatically, based on:', 'wpglobus' ),
			);

		return array(
			'wpglobus_id' => 'browser_redirect',
			'title'       => __( 'Redirect', 'wpglobus' ),
			'icon'        => 'dashicons dashicons-arrow-right-alt',
			'fields'      => $fields,
		);
	}

	/**
	 * Section "Customize".
	 *
	 * @since 1.9.12
	 * @return array
	 */
	protected function section_customizer() {

		$fields = array();

		$fields[] =
			array(
				'id'    => 'customizer_intro',
				'type'  => 'wpglobus_info',
				'html'  => include dirname( __FILE__ ) . '/templates/customize-intro.php',
				'class' => 'normal',
			);

		/**
		 * Set.
		 *
		 * @since 2.2.23
		 */
		$fields[] =
			array(
				'id'    => 'debug_info_theme',
				'type'  => 'wpglobus_info',
				'html'  => include dirname( __FILE__ ) . '/templates/debug-info-theme.php',
				'class' => 'normal',
			);

		return array(
			'wpglobus_id' => 'wpglobus_customizer',
			'title'       => __( 'Customize' ),
			'icon'        => 'dashicons dashicons-admin-appearance',
			'fields'      => $fields,
		);
	}

	/**
	 * Section "Сompatibility".
	 *
	 * @since 1.9.17
	 * @return array
	 */
	protected function section_compatibility() {

		$fields = array();

		$wpglobus_option = get_option( $this->args['opt_name'] );

		$options = array();

		/**
		 * When we add more options, need to update the @see WPGlobus_Options::sanitize_posted_data() method.
		 */
		$options['builder_disabled'] = array(
			'label'   => __( 'Enabled', 'wpglobus' ),
			'checked' => empty( $wpglobus_option['builder_disabled'] ) || ( isset( $options['builder_disabled'] ) && false === $options['builder_disabled'] ),
		);

		/**
		 * Field: "Builders support".
		 */
		$fields[] =
			array(
				'id'      => 'builder_disabled',
				'type'    => 'wpglobus_multicheck',
				'options' => $options,
				'name'    => 'wpglobus_option[builder_disabled]',
				'title'   => __( 'Builders support', 'wpglobus' ),
			);

		/**
		 * Field: "Builder mode is enabled on these Post Types".
		 *
		 * @since 2.2.11
		 */
		if ( empty( $wpglobus_option['builder_disabled'] ) || ( isset( $options['builder_disabled'] ) && false === $options['builder_disabled'] ) ) :

			$post_types = $this->get_post_types();

			$options = array();

			foreach ( $post_types as $post_type ) {

				$label = $post_type->label . ' (' . $post_type->name . ')';

				$checked = ! $post_type->wpglobus['post_type_disabled'];

				$disabled = '';

				$field_wrapper_style = '';

				if ( $checked || in_array( $post_type->name, array( 'post', 'page' ), true ) ) {

					if ( in_array( $post_type->name, array( 'post', 'page' ), true ) ) {
						$disabled = true;
					} else {
						$checked = false;
						if ( ! empty( $this->config->builder->post_types[ $post_type->name ] ) && 1 === (int) $this->config->builder->post_types[ $post_type->name ] ) {
							$checked = true;
						}
					}
				} else {
					$field_wrapper_style = 'display:none;';
				}

				$options[ $post_type->name ] = array(
					'label'               => $label,
					'checked'             => $checked,
					'disabled'            => $disabled,
					'field_wrapper_style' => $field_wrapper_style,
				);
			}

			$fields[] =
				array(
					'id'      => 'builder_post_types',
					'type'    => 'wpglobus_multicheck',
					'options' => $options,
					'name'    => 'wpglobus_option[builder_post_types]',
					'title'   => __( 'Builder mode is enabled on these Post Types', 'wpglobus' ),
				);

		endif;

		/**
		 * Other fields.
		 */
		$fields[] =
			array(
				'id'    => 'compatibility',
				'type'  => 'wpglobus_info',
				'html'  => include dirname( __FILE__ ) . '/templates/compatibility.php',
				'class' => 'normal',
			);

		$fields[] =
			array(
				'id'    => 'builder_beta_stage',
				'type'  => 'wpglobus_info',
				'html'  => include dirname( __FILE__ ) . '/templates/compatibility-beta.php',
				'class' => 'normal',
			);

		return array(
			'wpglobus_id' => 'wpglobus_compatibility',
			'title'       => __( 'Сompatibility', 'wpglobus' ),
			'icon'        => 'dashicons dashicons-clipboard',
			'fields'      => $fields,
		);
	}

	/**
	 * Section "Block Editor".
	 *
	 * @since 2.2.3
	 * @since 2.8.0 Added `use_widgets_block_editor` option.
	 * @return array
	 */
	protected function section_block_editor() {

		if ( version_compare( $GLOBALS['wp_version'], '5.7.99', '<' ) ) {
			/**
			 * Return
			 *
			 * @since 2.8.0
			 */
			return array();
		}

		$fields = array();

		$wpglobus_option = get_option( $this->args['opt_name'] );

		/**
		 * Get the theme support `widgets-block-editor` feature.
		 */
		$theme_support_widgets_block_editor = (bool) get_theme_support( 'widgets-block-editor' );

		if ( ! $theme_support_widgets_block_editor ) {

			$fields[] =
				array(
					'id'    => 'use_widgets_block_editor_info',
					'type'  => 'wpglobus_info',
					'title' => esc_html__( 'You cannot use block editor on the Widgets page because your theme disabled it.', 'wpglobus' ),
					#'html'  => '',
					'class' => 'normal',
				);

		} else {

			/**
			 * Obsolete
			 *
			 * @since 2.8.6
			 * $_desc = sprintf(
			 * esc_html__( '%1$sAttention%2$s', 'wpglobus' ),
			 * '<strong>',
			 * '</strong>'
			 * );
			 * $_desc .= ': ' . esc_html__( 'The current version of WPGlobus does not support multilingual widgets with block editor', 'wpglobus' );
			 * //*/

			$_checked = false;
			if ( ! empty( $wpglobus_option['use_widgets_block_editor'] ) && 1 === (int) $wpglobus_option['use_widgets_block_editor'] ) {
				$_checked = true;
			}
			$fields[] =
				array(
					'id'      => 'use_widgets_block_editor',
					'type'    => 'wpglobus_checkbox',
					'checked' => $_checked,
					'name'    => 'wpglobus_option[use_widgets_block_editor]',
					'title'   => esc_html__( 'Use block editor on the Widgets page', 'wpglobus' ),
					'label'   => esc_html__( 'Enabled', 'wpglobus' ),
					'desc'    => '', // $_desc
				);

			/**
			 * Todo may be need to use this option too.
			 * $_checked = false;
			 * if ( ! empty( $wpglobus_option['gutenberg_use_widgets_block_editor'] ) && 1 == $wpglobus_option['gutenberg_use_widgets_block_editor'] ) { // phpcs:ignore
			 * $_checked = true;
			 * }
			 * $fields[] =
			 * array(
			 * 'id'      => 'gutenberg_use_widgets_block_editor',
			 * 'type'    => 'wpglobus_checkbox',
			 * 'checked' => $_checked,
			 * 'name'    => 'wpglobus_option[gutenberg_use_widgets_block_editor]',
			 * 'title'   => esc_html__( 'Использовать редактор блоков на странице Виджеты в плагине Gutenberg', 'wpglobus' ),
			 * 'label'   => esc_html__( 'Enabled', 'wpglobus' ),
			 * );
			 * // */
		}

		return array(
			'wpglobus_id' => 'wpglobus_block_editor',
			'title'       => esc_html__( 'Block Editor', 'wpglobus' ),
			'caption'     => esc_html__( 'Block Editor Options', 'wpglobus' ),
			'icon'        => 'dashicons dashicons-layout',
			'fields'      => $fields,
			'li_class'    => '',
		);
	}

	/**
	 * Section "Multilingual Seo".
	 *
	 * @since 2.3.4
	 * @return array
	 */
	protected function section_seo() {

		/**
		 * We can get options from WPGlobus::Config().
		 */
		$wpglobus_option = get_option( $this->args['opt_name'] );

		$hreflang_type_default = 'zz-ZZ';
		// $hreflang_type_default_language = false;

		$hreflang_type                      = empty( $wpglobus_option['seo_hreflang_type'] ) ? $hreflang_type_default : $wpglobus_option['seo_hreflang_type'];
		$hreflang_type_for_default_language = empty( $wpglobus_option['seo_hreflang_default_language_type'] ) ? false : $wpglobus_option['seo_hreflang_default_language_type'];

		$home_url = home_url( '/' );

		$_info_desc = '<strong>';

		$_info_desc .= esc_html__( 'With the current settings, you will see the following lines in the section HEAD of your site pages', 'wpglobus' );
		$_info_desc .= '&nbsp;';
		$_info_desc .= esc_html__( '(example for two languages)', 'wpglobus' );
		$_info_desc .= '</strong>';
		$_info_desc .= ':<br />';

		$draft = '<link rel="alternate" hreflang="{{code}}" href="{{link}}" />';

		$i = 0;
		foreach ( WPGlobus::Config()->enabled_languages as $language ) {

			if ( $i > 1 ) {
				break;
			}

			switch ( $hreflang_type ) {
				case 'zz':
					$_hreflang_type = $language;
					break;
				case 'zz-zz':
					$_hreflang_type = str_replace( '_', '-', strtolower( WPGlobus::Config()->locale[ $language ] ) );
					break;
				default:
					// 'zz-ZZ'
					$_hreflang_type = str_replace( '_', '-', WPGlobus::Config()->locale[ $language ] );
					break;
			}

			if ( WPGlobus::Config()->default_language === $language ) {
				if ( $hreflang_type_for_default_language ) {
					$_hreflang_type = $hreflang_type_for_default_language;
				}
			}

			$_draft = str_replace(
				array( '{{code}}', '{{link}}' ),
				array( $_hreflang_type, WPGlobus_Utils::localize_url( $home_url, $language ) ),
				$draft
			);

			$_info_desc .= htmlspecialchars( $_draft, ENT_QUOTES, 'UTF-8' );
			$_info_desc .= '<br />';

			$i ++;
		}

		$fields[] =
			array(
				'id'    => 'wpglobus_hreflang',
				'type'  => 'wpglobus_info',
				'title' => esc_html__( 'Tell search engines about localized versions of your pages using the hreflang tag', 'wpglobus' ),
				'desc'  => $_info_desc,
				'class' => 'info', // or normal
			);

		$fields[] =
			array(
				'id'      => 'seo_hreflang_type',
				'type'    => 'wpglobus_dropdown',
				'title'   => esc_html__( 'Output the hreflang tag as', 'wpglobus' ),
				'desc'    => '',
				'options' => array(
					'zz-ZZ' => esc_html__( 'Language- and region-specific (en-US, ru-RU, etc.)', 'wpglobus' ),
					'zz-zz' => esc_html__( 'Language- and region-specific (en-us, ru-ru, etc.)', 'wpglobus' ),
					'zz'    => esc_html__( 'Language code only (en, ru, etc.)', 'wpglobus' ),
				),
				'default' => $hreflang_type,
				'name'    => 'wpglobus_option[seo_hreflang_type]',
			);

		$fields[] =
			array(
				'id'      => 'seo_hreflang_default_language_type',
				'type'    => 'wpglobus_checkbox',
				'checked' => (bool) $hreflang_type_for_default_language,
				'name'    => 'wpglobus_option[seo_hreflang_default_language_type]',
				'title'   => esc_html__( 'Use the code `x-default` for the main language', 'wpglobus' ),
				'label'   => esc_html__( 'Enabled', 'wpglobus' ),
			);

		return array(
			'wpglobus_id' => 'wpglobus_multilingual_seo',
			'title'       => esc_html__( 'Multilingual SEO', 'wpglobus' ),
			'caption'     => esc_html__( 'Multilingual SEO Options', 'wpglobus' ),
			'icon'        => 'dashicons dashicons-code-standards',
			'fields'      => $fields,
		);
	}

	/**
	 * Section "Rest API".
	 *
	 * @since 2.5.8
	 * @return array
	 */
	protected function section_rest_api() {

		if ( version_compare( $GLOBALS['wp_version'], '5.5', '<' ) ) {
			/**
			 * Return
			 *
			 * @since 2.5.9
			 */
			return array();
		}

		$fields = array();

		$_post_id               = 1;
		$_route_for_post        = rest_get_route_for_post( $_post_id );
		$_namespace             = 'wp/v2';
		$_default_language_name = WPGlobus::Config()->language_name[ WPGlobus::Config()->default_language ];
		$_extra_language_name   = WPGlobus::Config()->language_name[ WPGlobus::Config()->enabled_languages[1] ];

		if ( ! empty( $_route_for_post ) ) {
			$_rest_rout = rest_get_url_prefix() . $_route_for_post;
			$_url       = home_url( $_rest_rout );
		} else {
			$_rest_rout = rest_get_url_prefix();
			$_url       = home_url( $_rest_rout . '/' . $_namespace . '/posts/' );
		}

		$_info_desc = esc_html__( 'With WPGlobus, you can get translations for posts and pages using REST API.', 'wpglobus' );

		$_info_desc .= '<br /><br />';

		if ( ! empty( $_route_for_post ) ) {

			$_info_desc .= esc_html__( 'For demonstration, you can try the first post that WordPress creates at the initial installation.', 'wpglobus' );
			$_info_desc .= '<br />';
			$_info_desc .= sprintf(
			// Translators:
				esc_html__( 'Go to %1$s%2$s%3$s to see the content in language: %4$s.', 'wpglobus' ),
				'<a href="' . $_url . '" target="_blank">',
				$_url,
				'</a>',
				$_default_language_name
			);

			$_extra_url = WPGlobus_Utils::localize_url( $_url, WPGlobus::Config()->enabled_languages[1] );

			$_info_desc .= '<br />';
			$_info_desc .= sprintf(
			// Translators:
				esc_html__( 'Go to %1$s%2$s%3$s to see the content in language: %4$s.', 'wpglobus' ),
				'<a href="' . $_extra_url . '" target="_blank">',
				$_extra_url,
				'</a>',
				$_extra_language_name
			);
			$_info_desc .= '<br />';

		} else {

			$_info_desc .= sprintf(
			// Translators:
				esc_html__( 'Go to %1$s%2$s%3$s to see the content in language: %4$s.', 'wpglobus' ),
				'<a href="' . $_url . '" target="_blank">',
				$_url,
				'</a>',
				$_default_language_name
			);
			$_info_desc .= '<br />';

			$_extra_url = WPGlobus_Utils::localize_url( $_url, WPGlobus::Config()->enabled_languages[1] );

			$_info_desc .= sprintf(
			// Translators:
				esc_html__( 'Go to %1$s%2$s%3$s to see the content in language: %4$s.', 'wpglobus' ),
				'<a href="' . $_extra_url . '" target="_blank">',
				$_extra_url,
				'</a>',
				$_extra_language_name
			);
			$_info_desc .= '<br />';
		}

		$_info_desc .= '<br />';
		$_info_desc .= sprintf(
		// Translators:
			esc_html__( 'Please read the %1$sWordPress REST API documentation%2$s for more information.', 'wpglobus' ),
			'<a href="https://developer.wordpress.org/rest-api/" target="_blank">',
			'</a>'
		);
		$_info_desc .= '<br /><br />';

		$_info_desc .= sprintf(
		// Translators:
			esc_html__( 'In the REST API response, you can find the %1$stranslation%2$s field, which shows whether translations exist for the fields %1$stitle%2$s, %1$scontent%2$s and %1$sexcerpt%2$s or not, for each language. See the screenshot below:', 'wpglobus' ),
			'<code>',
			'</code>'
		);

		$_info_desc .= '<div class="img-container" style="vertical-align:middle;text-align:center;">';
		$_info_desc .= '<img src="' . $this->url_options_image . 'rest-api-response-field-translation.jpg" />';
		$_info_desc .= '</div>';

		$fields[] =
			array(
				'id'    => 'wpglobus_rest_api_info',
				'type'  => 'wpglobus_info',
				'title' => esc_html__( 'Description:', 'wpglobus' ),
				'desc'  => $_info_desc,
				'class' => 'info', // or normal
			);

		return array(
			'wpglobus_id' => 'wpglobus_rest_api',
			'title'       => esc_html__( 'REST API', 'wpglobus' ),
			'caption'     => esc_html__( 'REST API', 'wpglobus' ),
			'icon'        => 'dashicons dashicons-rest-api',
			'fields'      => $fields,
		);
	}

	/**
	 * Section "Info".
	 *
	 * @since        1.9.14
	 * @since        2.2.23 Move theme info to `Customize` section.
	 * @noinspection PhpUnused
	 */
	protected function section_debug_info() {
		// @todo to use in future versions.
	}

	/**
	 * Read file.
	 *
	 * @since 1.9.14
	 *
	 * @param string $file File path.
	 *
	 * @return array|bool
	 */
	protected function read_config_file( $file = '' ) {

		if ( empty( $file ) ) {
			return false;
		}

		static $buffers;
		if ( isset( $buffers[ $file ] ) ) {
			return $buffers[ $file ];
		}

		$buffer = array();

		if ( is_readable( $file ) ) {
			$handle = fopen( $file, 'r' );
			if ( $handle ) {
				$_buffer = fgets( $handle );
				while ( false !== $_buffer ) {
					$buffer[] = $_buffer;
					$_buffer  = fgets( $handle );
				}
				/**
				 * // if ( ! feof( $handle ) ) {.
				 *
				 * @todo add error handling.
				 * // }
				 */
				fclose( $handle );
			}
		}

		$buffers[ $file ] = $buffer;

		return $buffers[ $file ];
	}

	/**
	 * Filter options in file.
	 *
	 * @since        1.9.14
	 *
	 * @param string $file   File path.
	 * @param string $filter Filter.
	 *
	 * @return array|bool
	 * @noinspection PhpUnused
	 */
	protected function config_file_filter( $file = '', $filter = '' ) {

		if ( empty( $file ) ) {
			return false;
		}

		$_buffer = $this->read_config_file( $file );

		$buffer = array();
		if ( empty( $filter ) ) {
			return $_buffer;
		} else {
			/**
			 * Unused $_id
			 *
			 * @noinspection PhpUnusedLocalVariableInspection
			 */
			foreach ( $_buffer as $_id => $_value ) {
				if ( false !== strpos( $_value, $filter ) ) {
					$buffer[] = $_value;
				}
			}
		}

		return $buffer;
	}

	/**
	 * Sanitize $_POST before saving it to the options table.
	 *
	 * @param array $posted_data The submitted data.
	 *
	 * @return array The sanitized data.
	 */
	protected function sanitize_posted_data( $posted_data ) {

		// Standard WP anti-hack. Should return a clean array.
		$data = wp_unslash( $posted_data );
		if ( ! is_array( $data ) ) {
			// Something is wrong. This should never happen. Do not save.
			wp_die( 'WPGlobus: options data sanitization error' );
		}

		if ( empty( $data['enabled_languages'] ) || ! is_array( $data['enabled_languages'] ) ) {
			// Corrupted data / hack. This should never happen. Do not save this.
			wp_die( 'WPGlobus: options data without enabled_languages' );
		}

		// All enabled languages must be in the form [code] => true.
		// Remove the unchecked languages (empty values).
		$data['enabled_languages'] = array_filter( $data['enabled_languages'] );
		// Fill the rest with true.
		$data['enabled_languages'] = array_fill_keys( array_keys( $data['enabled_languages'] ), true );

		// "More languages" is appended to the "Enabled Languages".
		if ( ! empty( $data['more_languages'] ) && is_string( $data['more_languages'] ) ) {
			$data['enabled_languages'][ $data['more_languages'] ] = true;
		}
		unset( $data['more_languages'] );

		// Section `post_types` requires special processing to capture unchecked elements.
		if ( ! empty( $data['post_type'] ) && is_array( $data['post_type'] ) ) {
			// Extract "control" fields from the posted data.
			$control = $data['post_type']['control'];
			unset( $data['post_type']['control'] );

			// Sanitize control: fill with '0'.
			$control = array_fill_keys( array_keys( $control ), '0' );

			// Sanitize the posted checkboxes: fill with '1'.
			$data['post_type'] = array_fill_keys( array_keys( $data['post_type'] ), '1' );

			// We need to know only the disabled elements.
			// The control is the list of all post types, filled with zeroes, thus all disabled.
			// The "diff" removes from the control those that were posted as "enabled.
			// The result of "diff" is THE disabled post types.
			$data['post_type'] = array_diff_key( $control, $data['post_type'] );
		} else {
			// Invalid data posted (not an array)..fix.
			$data['post_type'] = array();
		}

		// Checkbox: if passed, make it `true`. No garbage.
		if ( ! empty( $data['selector_wp_list_pages'] ) ) {
			$data['selector_wp_list_pages'] = true;
		}

		// The $data['browser_redirect'] currently can only have one choice.
		if ( ! empty( $data['browser_redirect']['redirect_by_language'] ) ) {
			// If passed and checked then it's 1.
			$data['browser_redirect'] = array( 'redirect_by_language' => 1 );
		} else {
			// Otherwise it's 0.
			$data['browser_redirect'] = array( 'redirect_by_language' => 0 );
		}

		// The $data['builder_disabled'].
		if ( empty( $data['builder_disabled']['builder_disabled'] ) ) {
			// If passed and empty then set it as "disabled".
			$data['builder_disabled'] = 1;
		} elseif ( '1' === $data['builder_disabled']['builder_disabled'] ) {
			// Revert. Unset as "enabled".
			unset( $data['builder_disabled'] );
		} elseif ( '0' === $data['builder_disabled']['builder_disabled'] ) {
			// Revert. Set as "disabled".
			$data['builder_disabled'] = 1;
		}

		// @since 2.2.11 The $data['builder_post_types'].
		// Don't handle `post` and `page` post type. @see `section_compatibility` function.
		if ( ! empty( $data['builder_post_types']['control'] ) ) {
			unset( $data['builder_post_types']['control'] );
		}
		// We need synchronize with $data['post_type'].
		if ( ! empty( $data['post_type'] ) ) {
			foreach ( $data['post_type'] as $post_type => $init ) {
				if ( 0 === (int) $init && ! empty( $data['builder_post_types'][ $post_type ] ) ) {
					unset( $data['builder_post_types'][ $post_type ] );
				}
			}
		}

		/**
		 * SEO
		 *
		 * @see   section_seo()
		 * @since 2.3.4
		 */
		if ( ! empty( $data['seo_hreflang_default_language_type'] ) ) {
			if ( 1 === (int) $data['seo_hreflang_default_language_type'] ) {
				$data['seo_hreflang_default_language_type'] = 'x-default';
			} else {
				unset( $data['seo_hreflang_default_language_type'] );
			}
		}

		return $data;
	}

	/**
	 * Check the field parameters, fill in defaults if necessary.
	 *
	 * @param array $field The field.
	 *
	 * @return array|false The sanitized field or false if the field is invalid.
	 */
	protected function sanitize_field( $field ) {

		if (
			empty( $field['type'] )
			|| empty( $field['id'] )
		) {
			return false;
		}

		$field = $this->field_backward_compatibility( $field );

		$wpglobus_option = get_option( $this->args['opt_name'] );

		if ( ! isset( $field['name'] ) ) {
			$field['name'] = $this->args['opt_name'] . '[' . $field['id'] . ']';
		}

		// If these are not passed, get them from options.

		if ( ! isset( $field['default'] ) ) {
			$field['default'] = isset( $wpglobus_option[ $field['id'] ] ) ? $wpglobus_option[ $field['id'] ] : '';
		}
		if ( ! isset( $field['checked'] ) ) {
			$field['checked'] = isset( $wpglobus_option[ $field['id'] ] );
		}

		// Fill some missing fields with blanks.
		foreach (
			array(
				'title',
				'subtitle',
				'desc',
				'class',
				'name_suffix',
				'style',
				'value',
				'mode',
			) as $parameter
		) {
			if ( ! isset( $field[ $parameter ] ) ) {
				$field[ $parameter ] = '';
			}
		}

		return $field;
	}

	/**
	 * Backward compatibility for fields.
	 *
	 * @param array $field The field parameters.
	 *
	 * @return array Converted to the new format if necessary.
	 */
	protected function field_backward_compatibility( $field ) {

		if ( 'switcher_menu_style' === $field['id'] && 'wpglobus_select' === $field['type'] ) {
			$field = self::field_switcher_menu_style();
		}

		return $field;
	}

	/**
	 * Sanitize section parameters.
	 * - handle real links vs. tabs
	 * - fix icons
	 * - etc.
	 *
	 * @param array $section The array of section parameters.
	 *
	 * @return array
	 */
	protected function sanitize_section( $section ) {

		$section = $this->section_backward_compatibility( $section );

		/**
		 * Set.
		 *
		 * @since 2.5.4
		 */
		$_li_class = '';
		if ( ! empty( $section['li_class'] ) && is_string( $section['li_class'] ) ) {
			$_li_class = ' ' . $section['li_class'];
		}

		if ( empty( $section['tab_href'] ) ) {
			// No real link, just switch tab.
			$section['tab_href'] = '#';
			$section['li_class'] = 'wpglobus-tab-link' . $_li_class;
		} else {
			// Real link specified. Use it and do not set the "tab switching" CSS class.
			$section['li_class'] = 'wpglobus-tab-external' . $_li_class;
		}

		// Use the generic icon if not specified or deprecated (Elusive).
		if ( ! isset( $section['icon'] ) || 'el-icon' === substr( $section['icon'], 0, 7 ) ) {
			$section['icon'] = 'dashicons dashicons-admin-generic';
		}

		return $section;
	}

	/**
	 * Backward compatibility for sections.
	 *
	 * @param array $section The section parameters.
	 *
	 * @return array Converted to the new format if necessary.
	 */
	protected function section_backward_compatibility( $section ) {
		/**
		 * WPGlobus Translate Options.
		 *
		 * @link https://wordpress.org/plugins/wpglobus-translate-options/
		 * @see  wpglobus_add_options_section()
		 */
		if ( 'Translation options' === $section['title'] ) {
			$section = array(
				'wpglobus_id'  => 'translate_options_link',
				'title'        => __( 'Translate strings', 'wpglobus' ),
				'tab_href'     => add_query_arg( 'page', 'wpglobus-translate-options', admin_url( 'admin.php' ) ),
				'icon'         => 'dashicons dashicons-admin-generic',
				'externalLink' => true,
			);
		}

		return $section;
	}

	/**
	 * Add Google Analytics parameters to the URL.
	 *
	 * @param string $url      The URL.
	 * @param string $campaign Campaign ID.
	 * @param string $source   Optional.
	 * @param string $medium   Optional.
	 *
	 * @return string
	 */
	protected function url_ga( $url, $campaign, $source = 'wpglobus-options-panel', $medium = 'link' ) {
		return add_query_arg(
			array(
				'utm_campaign' => $campaign,
				'utm_source'   => $source,
				'utm_medium'   => $medium,
			),
			$url
		);
	}

	/**
	 * Check if a plugin is installed.
	 *
	 * @see is_plugin_active
	 *
	 * @param string $folder For example, 'woocommerce'.
	 *
	 * @return bool
	 */
	protected function is_plugin_installed( $folder ) {

		/**
		 * Avoid warning when `get_plugins` tries to open non-existing folder.
		 */
		$plugin_root = WP_PLUGIN_DIR;
		if ( ! is_dir( $plugin_root . '/' . $folder ) ) {
			return false;
		}

		if ( ! function_exists( 'get_plugins' ) ) {
			/**
			 * Include plugin.php.
			 */
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return (bool) get_plugins( '/' . $folder );
	}

	/**
	 * Get post types.
	 *
	 * @since 2.2.11
	 *
	 * @return array
	 */
	protected function get_post_types() {

		static $post_types = null;

		if ( ! is_null( $post_types ) ) {
			return $post_types;
		}

		/**
		 * Post types.
		 *
		 * @var WP_Post_Type[] $_post_types
		 */
		$_post_types = get_post_types( array(), 'objects' );

		/**
		 * Filter the array of disabled entities.
		 *
		 * @see   `wpglobus_disabled_entities` filter in includes\class-wpglobus.php
		 *
		 * @since 2.2.11
		 */
		$disabled_entities = apply_filters( 'wpglobus_disabled_entities', $this->config->disabled_entities );

		$hidden_types = WPGlobus_Post_Types::hidden_types();

		foreach ( $_post_types as $post_type ) {

			// todo "SECTION: Post types" in includes\admin\class-wpglobus-customize-options.php to adjust post type list.
			if ( in_array( $post_type->name, $hidden_types, true ) ) {

				unset( $_post_types[ $post_type->name ] );
			} else {

				$_post_types[ $post_type->name ]->wpglobus = array();
				if ( in_array( $post_type->name, $disabled_entities, true ) ) {
					$_post_types[ $post_type->name ]->wpglobus['post_type_disabled'] = true;
				} else {
					$_post_types[ $post_type->name ]->wpglobus['post_type_disabled'] = false;
				}
			}
		}

		$post_types = $_post_types;

		return $post_types;
	}

	/**
	 * Method on__process_ajax
	 *
	 * @since 2.2.14
	 */
	public function on__process_ajax() {

		if ( ! current_user_can( 'manage_options' ) ) {
			$response = array(
				'message' => 'You are not allowed to manage options for this site.',
				'result'  => 'error',
			);
			wp_send_json_error( $response );
		}

		$option_name = WPGlobus::Config()->option; // don't use $config here.
		$data        = WPGlobus_WP::get_http_post_parameter( 'data' );

		$response = array(
			'message'  => 'Incorrect option.',
			'postData' => $data,
			'result'   => 'error',
		);

		if ( 'saveOption' === $data['_action'] ) {
			$opts = get_option( $option_name );
			foreach ( $data['options'] as $option => $value ) {
				$opts[ $option ] = sanitize_text_field( $value );
			}
			if ( update_option( $option_name, $opts ) ) {
				$response['message'] = 'Options was updated.';
				$response['result']  = 'success';
			} else {
				$response['message'] = 'Option was not updated.';
			}
		}

		if ( 'success' === $response['result'] ) {
			wp_send_json_success( $response );

		}
		wp_send_json_error( $response );
	}
}
