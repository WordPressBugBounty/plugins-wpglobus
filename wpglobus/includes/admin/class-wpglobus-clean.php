<?php
/**
 * WPGlobus_Clean
 *
 * @since     1.4.3
 * @package   WPGlobus/Admin
 */

if ( ! class_exists( 'WPGlobus_Clean' ) ) :

	/**
	 * Class WPGlobus_Clean
	 */
	class WPGlobus_Clean {

		const LOG_BASENAME = 'wpglobus-clean';

		protected static $tables = array();

		protected static $log_file = '';

		/**
		 * Controller
		 */
		public static function controller() {

			self::set_log_file();

			self::get_table();

			self::screen();

			add_action(
				'admin_footer',
				array(
					'WPGlobus_Clean',
					'action__admin_print_scripts',
				),
				99
			);
		}

		/**
		 * Initialize the class variable `log_file`.
		 * Note: 'wp-content' can be set to a different path, so we are using the standard WP method.
		 *
		 * @todo Check if the folder exists and file is writeable.
		 */
		protected static function set_log_file() {
			$upload_dir        = wp_upload_dir();
			$wpglobus_logs_dir = $upload_dir['basedir'] . '/wpglobus-logs';

			wp_mkdir_p( $wpglobus_logs_dir );

			self::$log_file = $wpglobus_logs_dir . '/' . self::LOG_BASENAME . '.log';

			// Protect the folder from reading via URL

			/**
			 * WP_Filesystem
			 *
			 * @global WP_Filesystem_Direct $wp_filesystem
			 */
			global $wp_filesystem;
			if ( ! $wp_filesystem ) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
				if ( ! WP_Filesystem() ) {
					return;
				}
			}

			if ( ! $wp_filesystem->exists( $wpglobus_logs_dir . '/.htaccess' ) ) {
				$wp_filesystem->put_contents( $wpglobus_logs_dir . '/.htaccess', 'deny from all' );
			}
			if ( ! $wp_filesystem->exists( $wpglobus_logs_dir . '/index.php' ) ) {
				$wp_filesystem->put_contents( $wpglobus_logs_dir . '/index.php', '' );
			}
		}

		/**
		 * Get table
		 *
		 * @param string $table Table name to set data.
		 *
		 * @return void
		 */
		public static function get_table( $table = '' ) {

			$get_all = false;
			if ( '' === $table ) {
				$get_all = true;
			}

			if ( 'posts' === $table || $get_all ) {

				// Table posts
				$posts                 = new stdClass();
				$posts->include_fields = array(
					'post_content',
					'post_title',
					'post_excerpt',
					'post_content_filtered',
				);
				$posts->id_field       = 'ID';
				$posts->post_status    = array(
					'publish',
					'draft',
				);

				self::$tables['posts'] = $posts;

			}

			if ( 'postmeta' === $table || $get_all ) {

				// Table postmeta
				$postmeta                 = new stdClass();
				$postmeta->include_fields = array(
					'meta_value',
				);
				$postmeta->id_field       = 'meta_id';

				self::$tables['postmeta'] = $postmeta;

			}

			if ( 'options' === $table || $get_all ) {

				// Table options
				$options                 = new stdClass();
				$options->include_fields = array(
					'option_value',
				);
				$options->id_field       = 'option_id';

				self::$tables['options'] = $options;

			}

			if ( 'terms' === $table || $get_all ) {

				// Table terms
				$terms                 = new stdClass();
				$terms->include_fields = array(
					'name',
				);
				$terms->id_field       = 'term_id';

				self::$tables['terms'] = $terms;

			}

			if ( 'term_taxonomy' === $table || $get_all ) {

				// Table term_taxonomy
				$term_taxonomy                 = new stdClass();
				$term_taxonomy->include_fields = array(
					'description',
				);
				$term_taxonomy->id_field       = 'term_taxonomy_id';

				self::$tables['term_taxonomy'] = $term_taxonomy;

			}

			if ( 'usermeta' === $table || $get_all ) {

				// Table usermeta
				$usermeta                 = new stdClass();
				$usermeta->include_fields = array(
					'meta_value',
				);

				$usermeta->id_field = 'umeta_id';

				self::$tables['usermeta'] = $usermeta;

			}

			if ( class_exists( 'WooCommerce' ) ) :

				/**
				 * WooCommerce tables
				 */
				if ( 'woocommerce_attribute_taxonomies' === $table || $get_all ) {

					// Table woocommerce_attribute_taxonomies
					$woocommerce_attribute_taxonomies                 = new stdClass();
					$woocommerce_attribute_taxonomies->include_fields = array(
						'attribute_label',
					);
					$woocommerce_attribute_taxonomies->id_field       = 'attribute_id';

					self::$tables['woocommerce_attribute_taxonomies'] = $woocommerce_attribute_taxonomies;
				}

				if ( 'woocommerce_order_items' === $table || $get_all ) {
					// Table woocommerce_order_items
					$woocommerce_order_items                 = new stdClass();
					$woocommerce_order_items->include_fields = array(
						'order_item_name',
					);
					$woocommerce_order_items->id_field       = 'order_item_id';

					self::$tables['woocommerce_order_items'] = $woocommerce_order_items;
				}

			endif;
		}

		/**
		 * Get table list
		 */
		public static function get_table_list() {

			$spinner = WPGlobus::$PLUGIN_DIR_URL . 'includes/css/images/spinner.gif';

			$list = '<ul>';
			foreach ( self::$tables as $table => $data ) {

				$list .= '<li id="' . $table . '">';
				$list .= '<span class="wpglobus-spinner" style="float:left;margin-right:10px;"><img src="' . $spinner . '"  alt=""/></span>';
				$list .= '<span class="wpglobus-result" style="float:left;width:20px;height:20px;"></span>';
				$list .= '<span class=""><input type="checkbox" id="cb-' . $table . '" checked disabled /></span>';
				$list .= $table;
				$list .= '</li>';

			}
			$list .= '<li id="wpglobus_options">';
			$list .= '<span class="wpglobus-spinner" style="float:left;margin-right:10px;"><img src="' . $spinner . '"  alt=""/></span>';
			$list .= '<span class="wpglobus-result" style="float:left;width:20px;height:20px;"></span>';
			$list .= '<span class=""><input type="checkbox" id="cb-wpglobus_options" name="cb-wpglobus_options" /></span>';
			$list .= esc_html( __( 'Remove the WPGlobus settings (not recommended)', 'wpglobus' ) );
			$list .= '</li>';
			$list .= '</ul>';

			return $list;
		}

		/**
		 * Ajax action handler
		 *
		 * @param string[] $order
		 */
		public static function process_ajax( $order ) {

			self::set_log_file();

			$_log = false;

			if ( 'true' === $order['log'] ) {
				$_log = true;
			}

			if ( 'die' === $order['action'] ) {
				wp_send_json_success( $order );
			}

			if ( 'wpglobus-reset' === $order['action'] ) {

				global $wpdb;

				/**
				 * Make table with prefix
				 */
				$table = $wpdb->prefix . 'options';

				$ids = $wpdb->get_results( "SELECT `option_id`, `option_name` FROM `{$wpdb->prefix}options` WHERE `option_name` REGEXP 'wpglobus'", ARRAY_A );

				$records = array();
				$fields  = array();

				foreach ( $ids as $data ) {
					foreach ( $data as $field_id => $record_id ) {
						if ( 'option_id' === $field_id ) {
							$records[] = $record_id;
						} elseif ( 'option_name' === $field_id ) {
							$fields[] = $record_id;
						}
					}
				}

				$result = true;
				if ( ! empty( $records ) ) {

					if ( $_log ) {
						self::log( $table, '', $fields );
					}

					$set   = implode( ',', $records );
					$query = "DELETE FROM $table WHERE `option_id` IN ($set)";

					/**
					 * Todo: resolve "NotPrepared" by doing DELETE in the loop above
					 */
					// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					$result = $wpdb->query( $query );
				}

				if ( false === $result ) {
					wp_send_json_error( $order );
				}

				wp_send_json_success( $order );

			}

			if ( 'clean' !== $order['action'] ) {
				wp_send_json_error( $order );
			}

			self::get_table( $order['table'] );

			/**
			 * Set of examples of sql queries
			 *
			 * @see http://dev.mysql.com/doc/refman/5.7/en/pattern-matching.html
			 * @see http://dev.mysql.com/doc/refman/5.7/en/regexp.html
			 */

			/**
			 * SELECT * FROM `wp_posts` WHERE 1=1 AND `post_status` REGEXP 'publish|draft' AND (
			 * `post_content` REGEXP '{:[a-z]{2}|[[.[.]]:[a-z]{2}|<!--:[a-z]{2}' OR
			 * `post_title` REGEXP '{:[a-z]{2}|[[.[.]]:[a-z]{2}|<!--:[a-z]{2}' OR
			 * `post_excerpt` REGEXP '{:[a-z]{2}|[[.[.]]:[a-z]{2}|<!--:[a-z]{2}' OR
			 * `post_content_filtered` REGEXP '{:[a-z]{2}|[[.[.]]:[a-z]{2}|<!--:[a-z]{2}' )
			 */

			/**
			 * SELECT *  FROM `wp_postmeta` WHERE `meta_value` REGEXP '{:[a-z]{2}|[[.[.]]:[a-z]{2}|<!--:[a-z]{2}'
			 */

			/**
			 * SELECT *  FROM `wp_options` WHERE `option_value` REGEXP '{:[a-z]{2}|[[.[.]]:[a-z]{2}|<!--:[a-z]{2}'
			 */

			/**
			 * SELECT *  FROM `wp_terms` WHERE `name` REGEXP '{:[a-z]{2}|[[.[.]]:[a-z]{2}|<!--:[a-z]{2}'
			 */

			/**
			 * SELECT *  FROM `wp_term_taxonomy` WHERE `description` REGEXP '{:[a-z]{2}|[[.[.]]:[a-z]{2}|<!--:[a-z]{2}'
			 */

			/**
			 * SELECT *  FROM `wp_usermeta` WHERE `meta_value` REGEXP '{:[a-z]{2}|[[.[.]]:[a-z]{2}|<!--:[a-z]{2}'
			 */

			/**
			 * SELECT * FROM `wp_woocommerce_attribute_taxonomies` WHERE `attribute_label` REGEXP '{:[a-z]{2}|[[.[.]]:[a-z]{2}|<!--:[a-z]{2}'
			 */

			/**
			 * SELECT * FROM `wp_woocommerce_order_items` WHERE `order_item_name` REGEXP '{:[a-z]{2}|[[.[.]]:[a-z]{2}|<!--:[a-z]{2}'
			 */

			global $wpdb;

			/**
			 * Make table with prefix
			 */
			$table = $wpdb->prefix . $order['table'];

			/**
			 * Standard regexp
			 *
			 * @since 2.10.7 Changed regexp because it did not work with mysqli 8/PHP8.
			 */
			$wpg_regexp = '(\\\\{|\\\\[|<!--):[a-z][a-z]';
			if ( defined( 'WPGLOBUS_OLD_CLEANER' ) ) {
				$wpg_regexp = '{:[a-z]{2}|[[.[.]]:[a-z]{2}|<!--:[a-z]{2}';
			}

			/**
			 * Post status
			 */
			$post_status = '';
			if ( ! empty( self::$tables[ $order['table'] ]->post_status ) ) {
				/**
				 * Example: AND `post_status` REGEXP 'publish|draft'
				 */
				$post_status = "AND post_status REGEXP '" . implode( '|', self::$tables[ $order['table'] ]->post_status ) . "' ";
			}

			/**
			 * Set condition
			 */
			$condition = false;

			if ( count( self::$tables[ $order['table'] ]->include_fields ) === 0 ) {
				/**
				 * Do nothing
				 *
				 * @noinspection PhpUnusedLocalVariableInspection
				 */
				$_noop = true;
			} elseif ( count( self::$tables[ $order['table'] ]->include_fields ) === 1 ) {
				// One field
				$field     = self::$tables[ $order['table'] ]->include_fields[0];
				$condition = "AND $field REGEXP '$wpg_regexp'";

			} else {
				// Multiple fields
				$temp = array();
				foreach ( self::$tables[ $order['table'] ]->include_fields as $field ) {
					$temp[] = "$field REGEXP '$wpg_regexp'";
				}

				$condition = 'AND ( ' . implode( ' OR ', $temp ) . ' )';

			}

			/**
			 * Set record ID
			 */
			$id = false;
			if ( ! empty( self::$tables[ $order['table'] ]->id_field ) ) {
				$id = self::$tables[ $order['table'] ]->id_field;
			}

			if ( ! $id ) {
				wp_send_json_error( $order );
			}

			if ( ! $condition ) {
				wp_send_json_error( $order );
			}

			$query = "SELECT $id FROM $table WHERE 1=1 $post_status $condition";

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$ids = $wpdb->get_results( $query, ARRAY_A );

			$result = true;

			foreach ( $ids as $data ) {

				foreach ( $data as $field_id => $record_id ) {

					$ifields = implode( ', ', self::$tables[ $order['table'] ]->include_fields );

					$query1 = "SELECT $ifields FROM $table WHERE $field_id = '$record_id'";

					// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					$record = $wpdb->get_results( $query1 );

					$update_fields = array();

					foreach ( self::$tables[ $order['table'] ]->include_fields as $include_field ) {

						$value = $record[0]->$include_field;

						$value = trim( $value );
						if ( empty( $value ) ) {
							continue;
						}

						$serialized = false;
						if ( is_serialized( $value ) ) {
							$serialized = true;
							$value      = maybe_unserialize( $value );
						}

						$converted = self::convert( $value );

						if ( $_log ) {
							self::log( $table, $record_id, $value, $converted );
						}

						if ( $serialized ) {
							$converted = maybe_serialize( $converted );
						}

						$update_fields[] = $include_field . "='" . $converted . "'";

					}    // endforeach;

					if ( ! empty( $update_fields ) ) {
						/**
						 * UPDATE record
						 */
						$updates   = implode( ', ', $update_fields );
						$upd_query = "UPDATE `$table` SET $updates WHERE `$field_id` = $record_id";

						// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
						$res = $wpdb->query( $upd_query );

						if ( false === $res ) {
							$result = false;
						}
					}
				} // endforeach
			} // endforeach

			if ( false === $result ) {
				wp_send_json_error( $order );
			}

			wp_send_json_success( $order );
		}

		/**
		 * Logger
		 *
		 * @param string $table
		 * @param string $id
		 * @param string $initial
		 * @param string $converted
		 */
		protected static function log( $table = '', $id = '', $initial = '', $converted = '' ) {
			$fn_log     = 'error_log';
			$fn_print_r = 'print_r';
			$fn_log( gmdate( DATE_ATOM ) . "\n", 3, self::$log_file );
			$fn_log( 'TABLE: ' . $table . "\n", 3, self::$log_file );
			$fn_log( 'ID: ' . $id . "\n", 3, self::$log_file );
			$fn_log( "BEFORE: \n" . $fn_print_r( $initial, true ) . "\n", 3, self::$log_file );
			if ( empty( $converted ) ) {
				$fn_log( "AFTER: \n(empty)\n", 3, self::$log_file );
			} else {
				$fn_log( "AFTER: \n" . $fn_print_r( $converted, true ) . "\n", 3, self::$log_file );
			}
			$fn_log( '=================' . "\n\n", 3, self::$log_file );
		}

		/**
		 * Enqueue js
		 *
		 * @return void
		 */
		public static function action__admin_print_scripts() {

			$src = WPGlobus::$PLUGIN_DIR_URL . 'includes/js/wpglobus-clean' . WPGlobus::SCRIPT_SUFFIX() . '.js';

			$result_icons['success'] = WPGlobus::$PLUGIN_DIR_URL . 'includes/css/images/success.png';
			$result_icons['error']   = WPGlobus::$PLUGIN_DIR_URL . 'includes/css/images/error.png';

			wp_enqueue_script(
				'wpglobus-clean',
				$src,
				array( 'jquery' ),
				WPGLOBUS_VERSION,
				true
			);

			wp_localize_script(
				'wpglobus-clean',
				'WPGlobusClean',
				array(
					'version' => WPGLOBUS_VERSION,
					'icons'   => $result_icons,
					'data'    => array_merge( self::$tables, array( 'wpglobus_options' => new stdClass() ) ),
				)
			);
		}

		/**
		 * Output the clean screen.
		 *
		 * @return void
		 */
		public static function screen() {

			/**
			 * WP anti-hacks.
			 *
			 * @since 2.12.1
			 */
			if ( ! current_user_can( 'manage_options' ) ) {
				?>
				<div class="wrap about-wrap wpglobus-about-wrap clean-wrap wpglobus-clean">
					<h1 class="wpglobus"><span class="wpglobus-wp">WP</span>Globus
						<span class="wpglobus-version"><?php echo esc_html( WPGLOBUS_VERSION ); ?></span>
					</h1>
					<h4>Unauthorized user</h4>
				</div>
				<?php
				return;
			}

			/**
			 * For Google Analytics
			 */
			$ga_campaign = '?utm_source=wpglobus-admin-clean&utm_medium=link&utm_campaign=clean-up-tool';

			$url_wpglobus_site             = WPGlobus_Utils::url_wpglobus_site();
			$url_wpglobus_site_contact     = $url_wpglobus_site . 'pg/contact-us/' . $ga_campaign;
			$url_wpglobus_site_quick_start = $url_wpglobus_site . 'quick-start/' . $ga_campaign;
			/**
			 * //            $url_wpglobus_site_home        = $url_wpglobus_site . $ga_campaign;
			 * //            $url_wpglobus_site_faq         = $url_wpglobus_site . 'faq/' . $ga_campaign;
			 * //            $url_wpglobus_site_pro_support = $url_wpglobus_site . 'professional-support/' . $ga_campaign;
			 */
			$url_wpglobus_logo = WPGlobus::$PLUGIN_DIR_URL . 'includes/css/images/wpglobus-logo-180x180.png';

			// @formatter:off
			?>
			<style>
			.wp-badge.wpglobus-badge {
				background: #ffffff url(<?php echo esc_url( $url_wpglobus_logo ); ?>) no-repeat;
				background-size: contain;
			}
			</style>
			<?php
			// @formatter:on
			?>
			<div class="wrap about-wrap wpglobus-about-wrap clean-wrap wpglobus-clean">
				<h1 class="wpglobus"><span class="wpglobus-wp">WP</span>Globus
					<span class="wpglobus-version"><?php echo esc_html( WPGLOBUS_VERSION ); ?></span>
				</h1>

				<div class="wpglobus-motto"><?php esc_html_e( 'Multilingual WordPress', 'wpglobus' ); ?></div>

				<div class="about-text">
					<?php esc_html_e( 'WPGlobus is a family of WordPress plugins assisting you in making multilingual WordPress blogs and sites.', 'wpglobus' ); ?>
				</div>

				<div class="wp-badge wpglobus-badge"></div>

				<h2 class="nav-tab-wrapper">
					<a href="#" class="nav-tab nav-tab-active">
						<?php esc_html_e( 'Clean-up Tool', 'wpglobus' ); ?>
					</a>
					<?php
					/**
					 * Removed
					 *
					 * @since 3.0.0
					 */
					?>
					<?php if ( 0 ) : ?>
						<a href="<?php echo esc_url( $url_wpglobus_site_quick_start ); ?>"
								target="_blank"
								class="nav-tab">
							<?php esc_html_e( 'Guide', 'wpglobus' ); ?>
						</a>
						<a href="<?php echo esc_url( WPGlobus_Admin_Page::url_settings() ); ?>" class="nav-tab">
							<?php esc_html_e( 'Settings' ); ?>
						</a>
						<a href="<?php echo esc_url( WPGlobus_Admin_Page::url_addons() ); ?>" class="nav-tab">
							<?php esc_html_e( 'Add-ons', 'wpglobus' ); ?>
						</a>
						<a href="<?php echo esc_url( $url_wpglobus_site_contact ); ?>" class="nav-tab">
							<?php esc_html_e( 'Support', 'wpglobus' ); ?>
						</a>
					<?php endif; ?>
				</h2>

				<div style="color: white; background-color: red; padding: .5em; margin-top: 1em;">
					<?php esc_html_e( 'WARNING: this operation is non-reversible. It is strongly recommended that you backup your database before proceeding.', 'wpglobus' ); ?>
				</div>

				<div style="padding: .5em">
					<p><strong>
							1. <?php esc_html_e( 'This tool should be used only if you plan to completely uninstall WPGlobus. By running it, you will remove ALL translations you have entered to your post, pages, etc., keeping only the MAIN language texts. Please make sure that all entries have some content in the main language. Otherwise, you might end up with empty titles, no content, no excerpts, blank comments and so on.', 'wpglobus' ); ?>
						</strong></p>
					<p><strong>
							2. <?php esc_html_e( 'Make sure that your active theme does not have any code related to WPGlobus. Such code could be added by you or by a 3rd party developer. If that code runs without first verifying that WPGlobus is active, WordPress may die with a fatal error.', 'wpglobus' ); ?>
						</strong></p>
				</div>

				<div style="color:red; background-color: white; padding: .5em">
					<?php
					$_message = esc_html(
						sprintf( // translators: %1$s - language name, %2$s - language code. Do not remove.
							__( 'The main language is currently set to %1$s (%2$s). ALL TEXTS THAT ARE NOT IN %1$s WILL BE DELETED! To change the main language, please go to {{settings}}.', 'wpglobus' ),
							WPGlobus::Config()->en_language_name[ WPGlobus::Config()->default_language ],
							WPGlobus::Config()->default_language
						)
					);

					$_settings_link = '<a href="' . esc_url( WPGlobus_Admin_Page::url_settings( 'languages' ) ) . '">' . esc_html__( 'Settings' ) . '</a>';
					echo wp_kses_post( str_replace( '{{settings}}', $_settings_link, $_message ) );
					?>
				</div>

				<hr/>
				<h3 id="about-to-clean">
					<?php esc_html_e( 'You are about to clean the content of the following database tables:', 'wpglobus' ); ?>
				</h3>

				<?php echo wp_kses_post( self::get_table_list() ); ?>

				<hr/>

				<h3>
					<?php esc_html_e( 'The operations log', 'wpglobus' ); ?>
				</h3>
				<div>
					<?php esc_html_e( 'We are going to write a detailed log of all the database changes performed. It should help in the case you need to restore something important. The log will be written to the file:', 'wpglobus' ); ?>
				</div>
				<br/>
				<code>
					<?php echo esc_html( self::$log_file ); ?>
				</code>
				<br/>
				<br/>
				<label>
					<input type="checkbox" name="wpglobus-clean-log" id="wpglobus-clean-log" checked="checked"/>
					<?php esc_html_e( 'Uncheck if you do not want to write the operations log (we recommend to keep it checked)', 'wpglobus' ); ?>

				</label>
				<hr/>
				<h3>
					<?php esc_html_e( 'You have been warned...', 'wpglobus' ); ?>
				</h3>
				<?php esc_html_e( 'Please confirm by checking the box below:', 'wpglobus' ); ?>
				<div style="color:red; background-color: white; padding: .5em; margin: 1em 0;">
					<?php esc_html_e( 'I have read and understood everything written on this page. I am aware that by using this tool I may loose some content of my website. I have made a database backup and know how to restore it if necessary. I am fully responsible for the results.', 'wpglobus' ); ?>
				</div>

				<label><input type="checkbox" name="wpglobus-clean-activate"
							id="wpglobus-clean-activate"/><?php esc_html_e( 'YES, I CONFIRM', 'wpglobus' ); ?>
				</label>
				<div class="return-to-dashboard">
					<a id="wpglobus-clean-button" class="button button-primary hidden" href="#about-to-clean">
						<?php esc_html_e( 'Process with the Clean-up', 'wpglobus' ); ?>
					</a>
				</div>

			</div>

			<?php
		}

		/**
		 * Convert string to text with default language
		 *
		 * @param mixed $data
		 *
		 * @return mixed $data
		 */
		public static function convert( $data ) {

			if ( empty( $data ) ) {
				return '';
			}

			if ( is_null( $data ) ) {
				return $data;
			}

			if ( is_bool( $data ) ) {
				return $data;
			}

			if ( is_float( $data ) ) {
				return $data;
			}

			if ( is_int( $data ) ) {
				return $data;
			}

			if ( is_string( $data ) ) {
				$data = WPGlobus_Core::text_filter( $data, WPGlobus::Config()->default_language, WPGlobus::RETURN_EMPTY );

				/**
				 * Set double apostrophe for mySQL UPDATE
				 */
				return str_replace( "'", "''", $data );
			}

			foreach ( $data as $key => $value ) {
				if ( is_array( $data ) ) {
					$data[ $key ] = self::convert( $value );
				} elseif ( is_object( $data ) ) {
					$data->$key = self::convert( $value );
				}
			}

			return $data;
		}
	}

endif;
