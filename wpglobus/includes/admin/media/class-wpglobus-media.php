<?php

/**
 * Class WPGlobus_Media.
 *
 * @since 1.7.3
 * @since 2.2.22
 */
if ( ! class_exists( 'WPGlobus_Media' ) ) :

	class WPGlobus_Media {

		/**
		 * Instance.
		 */
		protected static $instance;

		/**
		 * Post types to work on media page.
		 */
		protected $enabled_post_types = array();

		/**
		 * Get instance.
		 */
		public static function get_instance( $http_post_action = false, $http_post_actions = array() ) {
			if ( null === self::$instance ) {
				self::$instance = new self( $http_post_action, $http_post_actions );
			}

			return self::$instance;
		}

		/**
		 * Constructor.
		 */
		public function __construct( $http_post_action, $http_post_actions ) {

			/**
			 * On admin_print_scripts
			 *
			 * @scope admin
			 * @since 1.7.3
			 */
			add_action( 'admin_print_scripts', array(
				$this,
				'post_php__admin_scripts',
			), 5 );

			if ( in_array( $http_post_action, $http_post_actions, true ) ) {

				if ( 'send-attachment-to-editor' === $http_post_action ) {

					/**
					 * See filter 'media_send_to_editor' in wp-admin\includes\media.php
					 *
					 * @scope admin
					 * @since 1.7.3
					 */
					add_filter( 'media_send_to_editor', array(
						$this,
						'filter__media_send_to_editor',
					), 5, 3 );

				} elseif ( 'query-attachments' === $http_post_action ) {

					/**
					 * See filter 'wp_prepare_attachment_for_js' in wp-includes\media.php
					 *
					 * @scope admin
					 * @since 2.2.22
					 */
					add_filter( 'wp_prepare_attachment_for_js', array(
						$this,
						'filter__prepare_attachment_for_js',
					), 5, 3 );

				}

				return;
			}

			$this->enabled_post_types[] = 'attachment';

			/**
			 * On edit_form_after_editor
			 *
			 * @scope admin
			 * @since 1.7.3
			 */
			add_action( 'edit_form_after_editor', array(
				$this,
				'language_tabs',
			) );

			/**
			 * On admin_print_scripts
			 *
			 * @scope admin
			 * @since 1.7.3
			 */
			add_action( 'admin_print_scripts', array(
				$this,
				'media__admin_scripts',
			) );

			/**
			 * On admin_print_styles
			 *
			 * @scope admin
			 * @since 1.7.3
			 */
			add_action( 'admin_print_styles', array(
				$this,
				'action__admin_styles',
			) );
		}

		/**
		 * Filters the HTML markup for a media item sent to the editor.
		 *
		 * @scope        admin
		 * @since        1.7.3
		 *
		 * @param string $html       HTML markup.
		 * @param int    $id         Unused.
		 * @param array  $attachment Array of attachment metadata.
		 *
		 * @return bool
		 * @noinspection PhpUnusedParameterInspection
		 */
		public function filter__media_send_to_editor( $html, $id, $attachment ) {

			$fields = array(
				'post_content',
				'post_excerpt',
				'image_alt',
			);

			$current_language = WPGlobus::Config()->default_language;

			$post_language_tab = WPGlobus_WP::get_http_post_parameter( 'wpglobusLanguageTab' );
			if ( $post_language_tab ) {
				/**
				 * See wpglobus-media.js
				 */
				$current_language = $post_language_tab;

				if ( ! in_array( $current_language, WPGlobus::Config()->enabled_languages, true ) ) {
					return $html;
				}
			}

			foreach ( $fields as $field ) {
				if ( ! empty( $attachment[ $field ] ) && WPGlobus_Core::has_translations( $attachment[ $field ] ) ) {
					$html = str_replace( $attachment[ $field ], WPGlobus_Core::text_filter( $attachment[ $field ], $current_language ), $html );
				}
			}

			return $html;
		}

		/**
		 * Check for enabled post types.
		 *
		 * @scope  admin
		 * @since  1.7.3
		 *
		 * @return bool
		 */
		public function is_enabled() {

			global $post;

			if ( empty( $post ) ) {
				return false;
			}

			if ( in_array( $post->post_type, $this->enabled_post_types, true ) ) {
				return true;
			}

			return false;
		}

		/**
		 * Enqueue admin scripts on post.php page.
		 *
		 * @scope  admin
		 * @since  1.7.3
		 *
		 * @return void
		 */
		public function post_php__admin_scripts() {

			global $post;

			if ( empty( $post ) ) {
				return;
			}

			if ( 'attachment' === $post->post_type ) {
				/**
				 * Don't load on edit media page.
				 */
				return;
			}

			wp_register_script(
				'wpglobus-media-post-php',
				WPGlobus::$PLUGIN_DIR_URL . 'includes/js/wpglobus-media-post-php' . WPGlobus::SCRIPT_SUFFIX() . '.js',
				array(),
				WPGLOBUS_VERSION,
				true
			);
			wp_enqueue_script( 'wpglobus-media-post-php' );
			wp_localize_script(
				'wpglobus-media-post-php',
				'WPGlobusMediaInPost',
				array(
					'version'   => WPGLOBUS_VERSION,
					'builderID' => WPGlobus::Config()->builder->get_id(),
				)
			);
		}

		/**
		 * Enqueue admin scripts.
		 *
		 * @scope  admin
		 * @since  1.7.3
		 *
		 * @return void
		 */
		public function media__admin_scripts() {

			if ( ! $this->is_enabled() ) {
				return;
			}

			/**
			 * WordPress 4.7+ needs a new version of our admin JS.
			 *
			 * @since 1.7.0
			 */
			$version = '';
			if ( version_compare( $GLOBALS['wp_version'], '4.6.999', '>' ) ) {
				$version = '-47';
			}

			wp_register_script(
				'wpglobus-admin',
				WPGlobus::$PLUGIN_DIR_URL . "includes/js/wpglobus-admin$version" . WPGlobus::SCRIPT_SUFFIX() . '.js',
				array( 'jquery', 'jquery-ui-dialog', 'jquery-ui-tabs' ),
				WPGLOBUS_VERSION,
				true
			);
			wp_enqueue_script( 'wpglobus-admin' );
			wp_localize_script(
				'wpglobus-admin',
				'WPGlobusAdmin',
				array(
					'version' => WPGLOBUS_VERSION,
					'i18n'    => array(),
					'data'    => array(
						'default_language' => WPGlobus::Config()->default_language,
					),
				)
			);

			wp_localize_script(
				'wpglobus-admin',
				'WPGlobusCoreData',
				array(
					'multisite'         => 'false',
					'default_language'  => WPGlobus::Config()->default_language,
					'enabled_languages' => WPGlobus::Config()->enabled_languages,
					'locale_tag_start'  => WPGlobus::LOCALE_TAG_START,
					'locale_tag_end'    => WPGlobus::LOCALE_TAG_END,
				)
			);

			wp_register_script(
				'wpglobus-media',
				WPGlobus::$PLUGIN_DIR_URL . 'includes/js/wpglobus-media' . WPGlobus::SCRIPT_SUFFIX() . '.js',
				array( 'jquery', 'wpglobus-admin' ),
				WPGLOBUS_VERSION,
				true
			);
			wp_enqueue_script( 'wpglobus-media' );
			wp_localize_script(
				'wpglobus-media',
				'WPGlobusMedia',
				array(
					'version'          => WPGLOBUS_VERSION,
					'language'         => WPGlobus::Config()->default_language,
					'defaultLanguage'  => WPGlobus::Config()->default_language,
					'enabledLanguages' => WPGlobus::Config()->enabled_languages,
					'attachment'       => array(
						'caption'     => 'attachment_caption',
						'alt'         => 'attachment_alt',
						'description' => 'attachment_content',
						'title'       => 'title',
					),
				)
			);
		}

		/**
		 * Enqueue admin styles.
		 *
		 * @scope  admin
		 * @since  1.7.3
		 *
		 * @return void
		 */
		public function action__admin_styles() {

			if ( ! $this->is_enabled() ) {
				return;
			}

			wp_register_style(
				'wpglobus-admin-tabs',
				WPGlobus::$PLUGIN_DIR_URL . 'includes/css/wpglobus-admin-tabs.css',
				array(),
				WPGLOBUS_VERSION
			);
			wp_enqueue_style( 'wpglobus-admin-tabs' );
		}

		/**
		 * Add language tabs on media page.
		 *
		 * @scope  admin
		 * @since  1.7.3
		 *
		 * @return void
		 */
		public function language_tabs() {

			if ( ! $this->is_enabled() ) {
				return;
			}

			?>
			<div id="wpglobus-media-body-tabs" style="margin-top:20px;" class="wpglobus-post-body-tabs">
				<ul class="wpglobus-post-body-tabs-list">
					<?php
					$order = 0;
					foreach ( WPGlobus::Config()->open_languages as $language ) {
						$tab_suffix = WPGlobus::Config()->default_language === $language ? 'default' : $language;
						?>
						<li id="link-tab-<?php echo esc_attr( $tab_suffix ); ?>"
								data-language="<?php echo esc_attr( $language ); ?>"
								data-order="<?php echo esc_attr( $order ); ?>"
								class="wpglobus-post-tab">
							<a href="#tab-<?php echo esc_attr( $tab_suffix ); ?>"><?php echo esc_html( WPGlobus::Config()->en_language_name[ $language ] ); ?></a>
						</li>
						<?php
						++$order;
					}
					?>
				</ul>
				<?php
				foreach ( WPGlobus::Config()->open_languages as $language ) {
					$tab_suffix = WPGlobus::Config()->default_language === $language ? 'default' : $language;
					?>
					<div id="tab-<?php echo esc_attr( $tab_suffix ); ?>" style="display:none"></div>
					<?php
				}
				?>
			</div>
			<?php
		}

		/**
		 * Filters the attachment data prepared for JavaScript.
		 *
		 * @since 2.2.22
		 *
		 * @param array       $response   Array of prepared attachment data.
		 * @param WP_Post     $attachment Attachment object.
		 * @param array|false $meta       Array of attachment meta data, or false if there is none.
		 * @noinspection PhpUnusedParameterInspection
		 */
		public function filter__prepare_attachment_for_js( $response, $attachment, $meta ) {

			if ( ! WPGlobus_WP::get_http_post_parameter( 'wpglobusPrepareAttachments' ) ) {
				return $response;
			}

			$fields = array(
				'alt',
				'description',
				'caption',
				// @todo may be `uploadedToTitle` field
			);

			$current_language = WPGlobus::Config()->default_language;

			$post_language_tab = WPGlobus_WP::get_http_post_parameter( 'wpglobusLanguageTab' );
			if ( $post_language_tab ) {
				/**
				 * See wpglobus-media.js
				 */
				$current_language = $post_language_tab;

				if ( ! in_array( $current_language, WPGlobus::Config()->enabled_languages, true ) ) {
					return $response;
				}
			}

			foreach ( $fields as $field ) {
				if ( ! empty( $response[ $field ] ) && WPGlobus_Core::has_translations( $response[ $field ] ) ) {
					$response[ $field ] = WPGlobus_Core::text_filter( $response[ $field ], $current_language );
				}
			}

			return $response;
		}
	}

endif;
