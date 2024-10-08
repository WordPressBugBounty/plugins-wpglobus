<?php
/**
 * WPGlobus/Admin/Debug.
 *
 * @package WPGlobus\Admin
 */

if ( ! class_exists( 'WPGlobus_Admin_Debug' ) ) :

	/**
	 * Class WPGlobus_Admin_Debug.
	 *
	 * @since 1.8.1
	 */
	class WPGlobus_Admin_Debug {

		/**
		 * Instance.
		 *
		 * @var WPGlobus_Admin_Debug
		 */
		protected static $instance;

		/**
		 * Debug mode.
		 *
		 * @since 2.2.35
		 */
		protected static $mode = 'godmode';

		/**
		 * Key.
		 *
		 * @since 2.2.35
		 */
		protected static $key = '';

		/**
		 * Get instance.
		 */
		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Constructor.
		 */
		public function __construct() {

			$get_debug = WPGlobus_WP::get_http_get_parameter( 'wpglobus-debug' );
			if ( 'meta' === $get_debug ) {
				self::$mode = 'meta';
			} elseif ( 'wpglobus_options' === $get_debug || 'wpglobus-options' === $get_debug ) {
				self::$mode = 'wpglobus_options';
			} elseif ( 'yoast' === $get_debug ) {
				self::$mode = 'yoast';
			}

			if ( 'godmode' !== self::$mode ) {
				$get_key = WPGlobus_WP::get_http_get_parameter( 'key' );
				if ( $get_key ) {
					self::$key = $get_key;
				}
			}

			/**
			 * Action.
			 *
			 * @scope admin
			 * @since 1.8.1
			 */
			add_action( 'admin_print_scripts', array( $this, 'on__admin_scripts' ), 99 );

			/**
			 * Action.
			 *
			 * @scope admin
			 * @since 1.8.1
			 */
			add_action( 'admin_print_styles', array( $this, 'on__admin_styles' ), 99 );

			/**
			 * Action.
			 *
			 * @scope admin
			 * @since 1.8.1
			 */
			add_action( 'admin_footer', array( $this, 'on__admin_footer' ), 9999 );
		}

		/**
		 * Method is_enabled_section.
		 *
		 * @since 2.2.35
		 *
		 * @param string $section
		 *
		 * @return bool
		 */
		public function is_enabled_section( $section ) {
			if ( 'godmode' === self::$mode ) {
				return true;
			}
			if ( $section === self::$mode ) {
				return true;
			}

			return false;
		}

		/**
		 * Enqueue admin styles.
		 *
		 * @scope  admin
		 * @since  1.8.1
		 */
		public function on__admin_styles() {

			wp_register_style(
				'wpglobus-admin-debug',
				WPGlobus::plugin_dir_url() . 'includes/css/wpglobus-admin-debug.css',
				array(),
				WPGLOBUS_VERSION
			);
			wp_enqueue_style( 'wpglobus-admin-debug' );
		}

		/**
		 * Enqueue admin scripts.
		 *
		 * @scope  admin
		 * @since  1.8.1
		 */
		public function on__admin_scripts() {

			wp_register_script(
				'wpglobus-admin-debug',
				WPGlobus::plugin_dir_url() . 'includes/js/wpglobus-admin-debug' . WPGlobus::SCRIPT_SUFFIX() . '.js',
				array( 'jquery' ),
				WPGLOBUS_VERSION,
				true
			);
			wp_enqueue_script( 'wpglobus-admin-debug' );
			wp_localize_script(
				'wpglobus-admin-debug',
				'WPGlobusAdminDebug',
				array(
					'version' => WPGLOBUS_VERSION,
					'data'    => '',
				)
			);
		}

		/**
		 * Output table.
		 *
		 * @scope  admin
		 * @since  1.8.1
		 */
		public function on__admin_footer() {

			static $fn_dump = 'print_r';

			global $wpdb, $post, $pagenow;

			$meta_query_caption = '';

			if ( 'post.php' === $pagenow ) {
				if ( is_object( $post ) ) {
					// post.php page.
					if ( empty( $post->ID ) || 0 === (int) $post->ID ) {
						return;
					}

					$_id                = $post->ID;
					$meta_query_caption = "SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id = $_id";
					/**
					 * Get metadata.
					 *
					 * @var array $metas
					 */
					$metas = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id = %d", $post->ID ), ARRAY_A );
				}
			} elseif ( 'term.php' === $pagenow ) {

				$get_tag_ID = WPGlobus_WP::get_http_get_parameter( 'tag_ID' );
				if ( ! $get_tag_ID ) {
					return;
				}
				$_id = $get_tag_ID;

				$meta_query_caption = "SELECT meta_key, meta_value FROM $wpdb->termmeta WHERE term_id = $_id";

				/**
				 * Get metadata.
				 *
				 * @var array $metas
				 */
				$metas = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM $wpdb->termmeta WHERE term_id = %d", $_id ), ARRAY_A );
			} else {
				return;
			}
			?>
			<div id="wpglobus-admin-debug-box" class="" style="display:none;">
				<h4>WPGlobus debug box</h4>
				<?php
				if ( $this->is_enabled_section( 'yoast' ) ) :
					/**
					 * Output yoast options.
					 */
					if ( empty( self::$key ) ) {
						$query_caption = "SELECT * FROM $wpdb->options WHERE option_name LIKE '%wpseo%' OR option_name LIKE '%yoast%'";

						$results = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->options WHERE option_name LIKE %s OR option_name LIKE %s", '%wpseo%', '%yoast%' ) );
					} else {
						$_key          = self::$key;
						$query_caption = "SELECT * FROM $wpdb->options WHERE option_name = $_key";

						$results = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->options WHERE option_name = %s", self::$key ) );
					}
					?>
					<table class="table1">
						<caption><strong><?php echo '"' . esc_html( $query_caption ) . '"'; ?></strong></caption>
						<thead>
						<tr>
							<th><strong>№</strong></th>
							<th><strong>option name</strong></th>
							<th><strong>value</strong></th>
						</tr>
						</thead>
						<tbody>
						<?php
						$order = 1;

						/**
						 * Unused $key
						 *
						 * @noinspection PhpUnusedLocalVariableInspection
						 */
						foreach ( $results as $key => $value ) {
							$code = false;
							/**
							 * UNUSED
							 * //if ( is_array( $meta ) ) {
							 * //$results[$key]['meta_key'] = htmlspecialchars( $meta['meta_value'] );
							 * //}
							 */
							0 && wp_verify_nonce( '' );
							$args = $_GET;
							if ( empty( self::$key ) ) {
								$args['wpglobus-debug'] = 'yoast';
								$args                   = array_merge( $args, array( 'key' => $value->option_name ) );
								$_url                   = admin_url( add_query_arg( $args, $pagenow ) );
							} else {
								$_url = admin_url( add_query_arg( $args, $pagenow ) );
							}
							?>
							<tr>
								<td><?php echo esc_html( $order ); ?></td>
								<td>
									<a href="<?php echo esc_url( $_url ); ?>"><?php echo esc_html( $value->option_name ); ?></a>
								</td>
								<?php if ( $code ) { ?>
									<td>
										<pre><?php echo esc_html( $fn_dump( $value->option_value, true ) ); ?></pre>
									</td>
								<?php } else { ?>
									<td><?php echo esc_html( $fn_dump( $value->option_value, true ) ); ?></td>
								<?php } ?>
							</tr>
							<?php ++$order; ?>
						<?php } ?>
						</tbody>
					</table>
				<?php
				endif;

				if ( $this->is_enabled_section( 'meta' ) ) :
					/**
					 * Output metadata.
					 */
					?>
					<table class="table2">
						<caption><strong><?php echo '"' . esc_html( $meta_query_caption ) . '"'; ?></strong></caption>
						<thead>
						<tr>
							<th><strong>№</strong></th>
							<th><strong>meta</strong></th>
							<th><strong>value</strong></th>
						</tr>
						</thead>
						<tbody>
						<?php if ( empty( $metas ) ) { ?>
							<tr>
								<td></td>
								<td>No data</td>
								<td>No data</td>
							</tr>
							<?php
						} else {
							$order = 1;
							foreach ( $metas as $key => $meta ) {
								$code = false;
								if ( is_array( $meta ) ) {
									$_meta_key                   = 'meta_key';
									$metas[ $key ][ $_meta_key ] = htmlspecialchars( $meta['meta_value'] );
								}
								?>
								<tr>
									<td><?php echo esc_html( $order ); ?></td>
									<td><?php echo esc_html( $fn_dump( $meta['meta_key'], true ) ); ?></td>
									<?php if ( $code ) { ?>
										<td>
											<pre><?php echo esc_html( $fn_dump( $meta['meta_value'], true ) ); ?></pre>
										</td>
									<?php } else { ?>
										<td><?php echo esc_html( $fn_dump( $meta['meta_value'], true ) ); ?></td>
									<?php } ?>
								</tr>
								<?php
								++$order;
							}
						}
						?>
						</tbody>
					</table>
				<?php
				endif;

				if ( $this->is_enabled_section( 'wpglobus_options' ) ) :
					/**
					 * Output WPGlobus options.
					 */
					$results = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM $wpdb->options WHERE option_name LIKE %s', '%wpglobus%' ) );
					?>
					<table class="table3">
						<caption>
							<strong><?php echo '"SELECT * FROM $wpdb->options WHERE option_name LIKE \'%wpglobus%\'"'; ?></strong>
						</caption>
						<caption><?php echo 'Option count: ' . count( $results ); ?></caption>
						<thead>
						<tr>
							<th><strong>Option ID</strong></th>
							<th><strong>Option Name</strong></th>
							<th><strong>Option Value</strong></th>
						</tr>
						</thead>
						<tbody>
						<?php

						$order = 1;

						/**
						 * Unused $option_key
						 *
						 * @noinspection PhpUnusedLocalVariableInspection
						 */
						foreach ( $results as $option_key => $option ) {
							$code = false;
							if ( is_array( $option->option_value ) ) {
								foreach ( $option->option_value as $key => $value ) {
									$option->option_value[ $key ] = htmlspecialchars( $value );
								}
							} elseif ( is_string( $option->option_value ) ) {
								$option->option_value = htmlspecialchars( $option->option_value );
							}
							?>
							<tr>
								<td><?php echo esc_html( $option->option_id ); ?></td>
								<td><?php echo esc_html( $fn_dump( $option->option_name, true ) ); ?></td>
								<?php if ( $code ) { ?>
									<td>
										<pre><?php echo esc_html( $fn_dump( $option->option_value, true ) ); ?></pre>
									</td>
								<?php } else { ?>
									<td><?php echo esc_html( $fn_dump( $option->option_value, true ) ); ?></td>
								<?php } ?>
							</tr>
							<?php
							/**
							 * Result of $order ++ is unused.
							 *
							 * @noinspection PhpUnusedLocalVariableInspection
							 */
							++$order;
							?>
						<?php } ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>
			<?php
		}
	}

endif;
