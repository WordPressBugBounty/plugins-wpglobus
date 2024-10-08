<?php
/**
 * File: class-wpglobus-gutenberg.php
 *
 * @since   2.10.8 Added default switcher items.
 *
 * @package WPGlobus\Builders\Gutenberg
 * Author  Alex Gor(alexgff)
 */

/**
 * Class WPGlobus_Gutenberg.
 */
class WPGlobus_Gutenberg extends WPGlobus_Builder {

	/**
	 * Constructor.
	 */
	public function __construct() {

		parent::__construct( 'gutenberg' );

		if ( is_admin() ) {

			/**
			 * Filter the post for Gutenberg editor.
			 *
			 * See wp-includes\class-wp-query.php
			 */
			add_action( 'the_post', array( $this, 'translate_post' ), 5 );

			/**
			 * Add 'wpglobus-language' hidden field.
			 */
			add_action( 'add_meta_boxes', array( $this, 'on__add_meta_box' ) );

			add_action( 'admin_enqueue_scripts', array( $this, 'on__enqueue_scripts' ), 1000 );

			/**
			 * Action admin_print_styles.
			 *
			 * @since 1.9.29
			 */
			add_action( 'admin_print_styles', array( $this, 'on__enqueue_styles' ) );

			/**
			 * Action enqueue_block_assets.
			 *
			 * See   wp-includes\script-loader.php
			 *
			 * @since 2.2.3
			 */
			add_action( 'enqueue_block_assets', array( $this, 'on__enqueue_block_assets' ) );

			/**
			 * Filter wpglobus_seo_meta_box_title.
			 *
			 * See wpglobus-seo\includes\class-wpglobus-seo.php
			 */
			add_filter( 'wpglobus_seo_meta_box_title', array( $this, 'filter__seo_meta_box_title' ) );
		}
	}

	/**
	 * Enqueue block assets.
	 *
	 * @since 2.2.3
	 */
	public function on__enqueue_block_assets() {

		if ( ! empty( WPGlobus::Config()->block_editor_old_fashioned_language_switcher ) && '1' === WPGlobus::Config()->block_editor_old_fashioned_language_switcher ) {
			return;
		}

		/**
		 * Set $style_file
		 *
		 * @since 2.2.3  wpglobus-block-editor.css
		 * @since 2.2.14 wpglobus-switcher.css
		 */
		$style_file = WPGlobus::plugin_dir_url() . 'includes/builders/gutenberg/assets/css/dist/wpglobus-switcher.css';

		/**
		 * Enqueue frontend and editor block styles.
		 */
		wp_enqueue_style(
			'wpglobus-switcher-css',
			$style_file,
			'',
			WPGLOBUS_VERSION
		);
	}

	/**
	 * Translate post.
	 *
	 * @param WP_Post $post The Post object.
	 */
	public function translate_post( $post ) {
		if ( $post instanceof WP_Post ) {
			WPGlobus_Core::translate_wp_post( $post, $this->language, WPGlobus::RETURN_EMPTY );
		}
	}

	/**
	 * Generate box with language switcher.
	 *
	 * @param string $page The page.
	 *
	 * @return string
	 */
	private function get_switcher_box( $page ) {

		global $post;

		$query_string = explode( '&', WPGlobus_WP::query_string() );

		foreach ( $query_string as $key => $_q ) {
			if ( false !== strpos( $_q, 'language=' ) ) {
				unset( $query_string[ $key ] );
			}
		}
		$query = implode( '&', $query_string );
		$url   = admin_url(
			add_query_arg(
				array(
					'language' => '{{language}}',
				),
				'post.php?' . $query
			)
		);

		$_box_style = 'position:absolute;top:15px;left:10px;z-index:100;';
		if ( file_exists( WPGlobus::Config()->flag_path['big'] . WPGlobus::Config()->flag[ $this->language ] ) ) {
			$_flag_img   = WPGlobus::Config()->flag_urls['big'] . WPGlobus::Config()->flag[ $this->language ];
			$_height     = '25px';
			$_width      = '25px';
			$_flag_style = 'border:1px solid #bfbfbf;border-radius:25px';
		} else {
			$_flag_img   = WPGlobus::Config()->flags_url . WPGlobus::Config()->flag[ $this->language ];
			$_height     = '';
			$_width      = '';
			$_flag_style = 'margin-top:5px';

			$_box_style .= 'margin-top:3px;';
		}

		$out = '';

		if ( 'post-new.php' === $page ) {

			ob_start();
			?>
			<div style="<?php echo esc_attr( $_box_style ); ?>"
					class="wpglobus-gutenberg-selector-box">
				<!--suppress CssInvalidPropertyValue -->
				<div class="wpglobus-selector-grid" style="">
					<a style="text-decoration:none;cursor:text;" onclick="return false;"
							href="#" class="wpglobus-gutenberg-selector wpglobus-gutenberg-selector-column-1"
							data-language="<?php echo esc_attr( $this->language ); ?>">
						<img alt=""
								height="<?php echo esc_attr( $_height ); ?>"
								width="<?php echo esc_attr( $_width ); ?>"
								style="<?php echo esc_attr( $_flag_style ); ?>"
								src="<?php echo esc_url( $_flag_img ); ?>"/>
					</a>
					<a style="text-decoration:none;cursor:text;" onclick="return false;"
							href="#" class="wpglobus-gutenberg-selector wpglobus-gutenberg-selector-column-2"
							data-language="<?php echo esc_attr( $this->language ); ?>">
						&nbsp;<span
								class="wpglobus-gutenberg-selector-text"><?php echo esc_html( WPGlobus::Config()->en_language_name[ $this->language ] ); ?></span>
					</a>
				</div>
				<ul class="wpglobus-gutenberg-selector-dropdown"
						style="display:none;position:fixed;margin:5px;list-style-type:none;">
					<li class="item" style="border:1px solid #ddd;background-color:#eee;padding:4px;">
						<?php esc_html_e( 'Before switching the language, please save draft or publish.', 'wpglobus' ); ?>
					</li>
				</ul>
			</div>
			<?php
			$out = ob_get_clean();

		} elseif ( 'post.php' === $page ) {

			ob_start();
			?>
			<div style="<?php echo esc_attr( $_box_style ); ?>"
					class="wpglobus-gutenberg-selector-box">
				<!--suppress CssInvalidPropertyValue -->
				<div class="wpglobus-selector-grid" style="">
					<a style="text-decoration: none;"
							href="<?php echo esc_url( str_replace( '{{language}}', $this->language, $url ) ); ?>"
							class="wpglobus-gutenberg-selector wpglobus-gutenberg-selector-column-1"
							data-language="<?php echo esc_attr( $this->language ); ?>">
						<img alt=""
								height="<?php echo esc_attr( $_height ); ?>"
								width="<?php echo esc_attr( $_width ); ?>"
								style="<?php echo esc_attr( $_flag_style ); ?>"
								src="<?php echo esc_url( $_flag_img ); ?>"/>
					</a>
					<a style="text-decoration: none;"
							href="<?php echo esc_url( str_replace( '{{language}}', $this->language, $url ) ); ?>"
							class="wpglobus-gutenberg-selector wpglobus-gutenberg-selector-column-2"
							data-language="<?php echo esc_attr( $this->language ); ?>">
						&nbsp;<span class="wpglobus-gutenberg-selector-text">
							<?php
							/**
							 * Filter the current language name.
							 *
							 * @since 2.1.0
							 *
							 * @param string  $en_language_name English language name.
							 * @param string  $language         Current language.
							 * @param WP_Post $post             The current post.
							 *
							 * @return string
							 */
							$en_language_name = apply_filters(
								'wpglobus_gutenberg_selector_text',
								WPGlobus::Config()->en_language_name[ $this->language ],
								$this->language,
								$post
							);
							echo esc_html( $en_language_name );
							?>
						</span>
					</a>
				</div>
				<ul class="wpglobus-gutenberg-selector-dropdown"
						style="display:none;position:fixed;border-left:1px solid #ddd;border-right:1px solid #ddd;background-color:#eee;margin:5px 0 0;padding:0 5px 5px 0;list-style-type:none;">
					<?php foreach ( WPGlobus::Config()->enabled_languages as $lang ) : ?>
						<?php
						if ( $lang === $this->language ) {
							continue;
						}
						?>
						<li class="item"
								style="text-align:left;cursor:pointer;border-bottom:1px solid #ddd;margin:0;height:25px;padding:5px 0 5px 5px;"
								data-language="<?php echo esc_attr( $lang ); ?>">
							<a href="<?php echo esc_url( str_replace( '{{language}}', $lang, $url ) ); ?>">
								<img src="<?php echo esc_url( WPGlobus::Config()->flags_url . WPGlobus::Config()->flag[ $lang ] ); ?>"
										alt=""/>&nbsp;<?php echo esc_html( WPGlobus::Config()->en_language_name[ $lang ] ); ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
			<?php
			$out = ob_get_clean();

		}

		return $out;
	}

	/**
	 * Callback for 'wpglobus_seo_meta_box_title'.
	 *
	 * @param string $meta_box_title Metabox title.
	 *
	 * @return string
	 */
	public function filter__seo_meta_box_title( $meta_box_title ) {
		return $meta_box_title . ' ' .
			   // Translators: Metabox title FOR language.
			   _x( 'for', 'filter__seo_meta_box_title', 'wpglobus' )
			   . ' ' . WPGlobus::Config()->en_language_name[ $this->get_current_language() ];
	}

	/**
	 * Enqueue styles.
	 *
	 * @since 1.9.29
	 * @return void
	 */
	public function on__enqueue_styles() {
		/**
		 * Pagenow global.
		 *
		 * @var string $pagenow
		 */
		global $pagenow;

		if ( ! in_array( $pagenow, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		if ( WPGlobus_WP::is_parameter_in_http_get( 'classic-editor' ) ) {
			return;
		}

		wp_register_style(
			'wpglobus-gutenberg',
			WPGlobus::plugin_dir_url() . 'includes/builders/gutenberg/assets/css/wpglobus-gutenberg.css',
			array(),
			WPGLOBUS_VERSION,
			'all'
		);
		wp_enqueue_style( 'wpglobus-gutenberg' );
	}

	/**
	 * Enqueue scripts.
	 *
	 * @return void
	 */
	public function on__enqueue_scripts() {

		/**
		 * Globals.
		 *
		 * @var string  $pagenow
		 * @var string  $wp_version
		 * @var WP_Post $post
		 */
		global $pagenow, $wp_version, $post;

		if ( ! in_array( $pagenow, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		if ( WPGlobus_WP::is_parameter_in_http_get( 'classic-editor' ) ) {
			return;
		}

		/**
		 * Old-style switcher.
		 *
		 * @since 2.2.3
		 */
		$tabs = '';
		if ( ! empty( WPGlobus::Config()->block_editor_old_fashioned_language_switcher ) && 1 === (int) WPGlobus::Config()->block_editor_old_fashioned_language_switcher ) {
			$tabs = $this->get_switcher_box( $pagenow );
		}

		$i18n              = array();
		$i18n['reload']    = esc_html__( 'Page is being reloaded. Please wait...', 'wpglobus' );
		$i18n['save_post'] = esc_html__( 'Before switching the language, please save draft or publish, then reload page.', 'wpglobus' );

		/**
		 * Extra data.
		 *
		 * @since 2.4.11
		 */
		$__data = array();

		/**
		 * We have Gutenberg in core since WP 5.0.
		 *
		 * @since 2.0
		 */
		$version_gutenberg = '';
		if ( version_compare( $wp_version, '4.9.99', '>' ) ) {
			$version_gutenberg = $wp_version;
		} elseif ( defined( 'GUTENBERG_VERSION' ) ) {
			$version_gutenberg = GUTENBERG_VERSION;
		}

		/**
		 * Check for Yoast SEO.
		 */
		$yoast_seo = false;
		if ( defined( 'WPSEO_VERSION' ) ) {
			$yoast_seo = true;
		}

		/**
		 * Check for Elementor.
		 *
		 * @since 2.4.11
		 */
		$elementor = false;
		if ( defined( 'ELEMENTOR_VERSION' ) ) {
			$elementor                         = true;
			$__data['elementorVersion']        = ELEMENTOR_VERSION;
			$__data['elementorCssPrintMethod'] = get_option( 'elementor_css_print_method', 'external' );
			$_url                              = add_query_arg(
				array(
					'page' => 'elementor#tab-advanced',
				),
				admin_url( 'admin.php' )
			);
			$i18n['elementorWarning']          = esc_html__( 'WPGlobus provides multilingual support for Elementor only when the option `CSS Print Method` is set to `External File`.', 'wpglobus' );
			$i18n['elementorActionLabel']      = esc_html__( 'Open Elementor Settings page', 'wpglobus' );
			$i18n['elementorActionLink']       = $_url;
		}

		/**
		 * Block editor tab URL.
		 *
		 * @since 2.2.3
		 */
		$block_editor_tab_url = admin_url(
			add_query_arg(
				array(
					'page' => WPGlobus::OPTIONS_PAGE_SLUG,
					'tab'  => 'block-editor',
				),
				'admin.php'
			)
		);

		/**
		 * Options page URL.
		 *
		 * @since 2.5.10
		 */
		$options_page_url = admin_url(
			add_query_arg(
				array(
					'page' => WPGlobus::OPTIONS_PAGE_SLUG,
				),
				'admin.php'
			)
		);

		/**
		 * Build the flags URL.
		 *
		 * @since        2.2.3
		 * @noinspection DuplicatedCode
		 */
		$flags_url = array();
		foreach ( WPGlobus::Config()->enabled_languages as $language ) {
			if ( file_exists( WPGlobus::Config()->flag_path['big'] . WPGlobus::Config()->flag[ $language ] ) ) {
				$flags_url[ $language ] = WPGlobus::Config()->flag_urls['big'] . WPGlobus::Config()->flag[ $language ];
			} else {
				$flags_url[ $language ] = WPGlobus::Config()->flags_url . WPGlobus::Config()->flag[ $language ];
			}
		}

		/**
		 * Pass the $post to JS.
		 *
		 * @since 2.2.4
		 */
		$__post = array(
			'ID'       => '',
			'type'     => '',
			'disabled' => false,
		);
		if ( $post instanceof WP_Post ) {
			$__post['ID']   = $post->ID;
			$__post['type'] = $post->post_type;
			if ( in_array( $post->post_type, WPGlobus::Config()->disabled_entities, true ) ) {
				$__post['disabled'] = true;
			}
		}

		/**
		 * See   includes\class-wpglobus-config.php for config options.
		 *
		 * @since 2.2.14
		 */
		$key_option                       = array();
		$key_option['switcherButtonType'] = 'block_editor_switcher_plugin_button_type';

		$options = array();
		/**
		 * Unused $key
		 *
		 * @noinspection PhpUnusedLocalVariableInspection
		 */
		foreach ( $key_option as $key => $option ) {
			$options[ $option ] = '';
			if ( ! empty( WPGlobus::Config()->$option ) ) {
				$options[ $option ] = WPGlobus::Config()->$option;
			}
		}

		/**
		 * Added default switcher items.
		 *
		 * @since 2.10.8
		 */
		$switcher_items = array(
			'top' => array(
				'containerClassName' => 'flex-row',
				'elements'           => array(
					array(
						'tagName' => 'img',
						'props'   => array(
							'className' => 'wpglobus-switcher-panel__flag',
							'style'     => array(),
							'src'       => '{{flagUrl}}',
						),
					),
					array(
						'tagName'  => 'Button',
						'props'    => array(
							'className' => 'button-switch',
							'style'     => array(),
							'href'      => '{{href}}',
							'isSmall'   => true,
							'isPrimary' => true,
						),
						'children' => '{{LanguageName}}',
					),
				),
			),
		);

		$data = array(
			'version'              => WPGLOBUS_VERSION,
			'versionGutenberg'     => $version_gutenberg,
			'wpglobusAjax'         => WPGLOBUS_AJAX,
			'context'              => WPGlobus::Config()->builder->get( 'context' ),
			'tabs'                 => $tabs,
			'language'             => $this->language,
			'pagenow'              => $pagenow,
			'postEditPage'         => 'post.php',
			'postNewPage'          => 'post-new.php',
			'defaultLanguage'      => WPGlobus::Config()->default_language,
			'i18n'                 => $i18n,
			'yoastSeo'             => $yoast_seo,
			'elementor'            => $elementor,
			'flags_url'            => $flags_url,
			'store_link'           => WPGlobus::URL_WPGLOBUS_SHOP,
			'__post'               => $__post,
			'block_editor_tab_url' => $block_editor_tab_url,
			'options_page_url'     => $options_page_url,
			'disabled_entities'    => WPGlobus::Config()->disabled_entities,
			'options'              => $options,
			'enabledOptionsTab'    => true,
			'keyOption'            => $key_option,
			'switcherItems'        => $switcher_items,
			'data'                 => $__data,
		);

		/**
		 * Filter for data to send to JS.
		 * Returning array.
		 *
		 * @since 2.2.14
		 *
		 * @param array $data An array with data.
		 */
		$data = apply_filters( 'wpglobus_block_editor_localize_data', $data );

		wp_register_script(
			'wpglobus-gutenberg',
			WPGlobus::plugin_dir_url() . 'includes/builders/gutenberg/assets/js/wpglobus-gutenberg' . WPGlobus::SCRIPT_SUFFIX() . '.js',
			array( 'jquery' ),
			WPGLOBUS_VERSION,
			true
		);
		wp_enqueue_script( 'wpglobus-gutenberg' );
		wp_localize_script(
			'wpglobus-gutenberg',
			'WPGlobusGutenberg',
			$data
		);
	}

	/**
	 * Callback for 'add_meta_boxes'.
	 */
	public function on__add_meta_box() {

		global $post;

		if ( in_array( $post->post_type, WPGlobus::Config()->disabled_entities, true ) ) {
			return;
		}

		add_meta_box(
			'wpglobus',
			__( 'WPGlobus', 'wpglobus' ),
			array( $this, 'callback__meta_box' ),
			null,
			'side',
			'core'
		);
	}

	/**
	 * Callback for 'add_meta_box' function.
	 */
	public function callback__meta_box() {
		$this->e_language_field();
		/**
		 * Action wpglobus_gutenberg_metabox
		 *
		 * @since 1.9.17
		 */
		do_action( 'wpglobus_gutenberg_metabox' );
	}
}
