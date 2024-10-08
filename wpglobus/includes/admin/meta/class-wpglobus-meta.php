<?php
/**
 * File: class-wpglobus-meta.php
 *
 * @since   1.9.17
 * @since   1.9.25 Added build_multilingual_string function.
 * @since   2.8.9 Added support for term meta.
 *
 * @package WPGlobus\Admin\Meta
 * Author  Alex Gor(alexgff)
 */

if ( ! class_exists( 'WPGlobus_Meta' ) ) :

	/**
	 * Class WPGlobus_Meta.
	 */
	class WPGlobus_Meta {

		/**
		 * Instance of this class.
		 *
		 * @var WPGlobus_Meta
		 */
		protected static $instance;

		/**
		 * Meta fields.
		 *
		 * @var string[]
		 */
		public static $meta_fields = array();

		/**
		 * Builder.
		 *
		 * @var WPGlobus_Config_Builder
		 */
		protected static $builder = null;

		/**
		 * Constructor.
		 *
		 * @param string[]                $meta_fields
		 * @param WPGlobus_Config_Builder $builder
		 */
		protected function __construct( $meta_fields, $builder ) {

			self::$meta_fields = $meta_fields;
			self::$builder     = $builder;

			/**
			 * Added checking REST API request.
			 *
			 * @since 2.8.9
			 */
			if ( is_admin() || WPGlobus_WP::is_rest_api_request() ) {

				add_filter( 'get_post_metadata', array( __CLASS__, 'filter__post_metadata' ), 5, 4 );

				/**
				 * In wp-includes\meta.php
				 *
				 * @see update_metadata()
				 */
				add_filter( 'update_post_metadata', array( __CLASS__, 'filter__update_post_metadata' ), 5, 5 );

				add_filter( 'delete_post_metadata', array( __CLASS__, 'filter__delete_metadata' ), 5, 5 );

				/**
				 * Filter get_term_metadata
				 *
				 * @since 2.8.9
				 */
				add_filter( 'get_term_metadata', array( __CLASS__, 'filter__term_metadata' ), 5, 4 );

				/**
				 * Filter update_term_metadata
				 *
				 * @since 2.8.9
				 */
				add_filter( 'update_term_metadata', array( __CLASS__, 'filter__update_term_metadata' ), 5, 5 );

				/**
				 * Todo  Test deleting term meta with Yoast and Rank Math(REST query).
				 *
				 * @since 2.8.9
				 * // add_filter( 'delete_term_metadata', array( __CLASS__, 'filter__delete_term_metadata' ), 5, 5 );
				 */
			}
		}

		/**
		 * Get instance of this class.
		 *
		 * @param string[]                $meta_fields
		 * @param WPGlobus_Config_Builder $builder
		 *
		 * @return WPGlobus_Meta
		 */
		public static function get_instance( $meta_fields, $builder ) {
			if ( ! ( self::$instance instanceof WPGlobus_Meta ) ) {
				self::$instance = new self( $meta_fields, $builder );
			}

			return self::$instance;
		}

		/**
		 * Update meta data.
		 *
		 * Internal use.
		 *
		 * @since 2.8.9
		 */
		protected static function update_metadata( $check, $object_id, $meta_key, $meta_value, $prev_value, $meta_type = '' ) {

			if ( empty( $meta_type ) ) {
				return $check;
			}

			/**
			 * DuplicatedCode
			 *
			 * @noinspection DuplicatedCode
			 */
			$table = _get_meta_table( $meta_type );
			if ( ! $table ) {
				return false;
			}

			$column    = $meta_type . '_id';
			$id_column = 'meta_id';

			$raw_meta_key = $meta_key;

			if ( empty( $prev_value ) ) {
				$old_value = get_metadata_raw( $meta_type, $object_id, $meta_key );
				if ( is_countable( $old_value ) && count( $old_value ) === 1 ) { // phpcs:ignore PHPCompatibility
					if ( $old_value[0] === $meta_value ) {
						return false;
					}
				}
			}

			$_meta_value = $meta_value;

			$meta_value = maybe_serialize( $meta_value );

			/**
			 * Global
			 *
			 * @global wpdb $wpdb
			 */
			global $wpdb;

			/**
			 * Don't auto-modify this SQL query.
			 */
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$prev_meta = $wpdb->get_row( $wpdb->prepare( "SELECT $id_column, meta_value FROM $table WHERE meta_key = %s AND $column = %d", $meta_key, $object_id ) );

			if ( is_null( $prev_meta ) ) {

				$_passed_value = $_meta_value;

				if ( ! empty( $_passed_value ) && WPGlobus::Config()->default_language !== self::$builder->get_language() ) {
					/**
					 * If we get multilingual $meta_value, let save it as is.
					 */
					if ( ! WPGlobus_Core::has_translations( $_passed_value ) ) {
						$_passed_value = self::build_multilingual_string( array( self::$builder->get_language() => $_passed_value ) );
					}
				}

				return add_metadata( $meta_type, $object_id, $raw_meta_key, $_passed_value );
			}

			/**
			 * WPGlobus Core.
			 * We get $meta_value in language that can be retrieved with self::$builder->get_language().
			 */

			/**
			 * If we get multilingual $meta_value, let save it as is.
			 */
			if ( ! WPGlobus_Core::has_translations( $meta_value ) ) {

				$_new_ml_array = array();

				if ( WPGlobus_Core::has_translations( $prev_meta->meta_value ) ) {

					foreach ( WPGlobus::Config()->enabled_languages as $language ) :

						if ( $language === self::$builder->get_language() ) {

							if ( ! empty( $meta_value ) ) {
								$_new_ml_array[ $language ] = $meta_value;
							}
						} else {

							$_value = WPGlobus_Core::text_filter( $prev_meta->meta_value, $language, WPGlobus::RETURN_EMPTY );

							if ( '' !== $_value ) {
								$_new_ml_array[ $language ] = $_value;
							}
						}

					endforeach;

					$_new_value = self::build_multilingual_string( $_new_ml_array );

				} else {

					if ( WPGlobus::Config()->default_language === self::$builder->get_language() ) {
						$_new_ml_array[ WPGlobus::Config()->default_language ] = $meta_value;
					} else {
						$_new_ml_array[ WPGlobus::Config()->default_language ] = $prev_meta->meta_value;
						if ( ! empty( $meta_value ) ) {
							$_new_ml_array[ self::$builder->get_language() ] = $meta_value;
						}
					}

					$_new_value = self::build_multilingual_string( $_new_ml_array );

				}

				if ( ! empty( $_new_value ) ) {
					$meta_value = $_new_value;
				}
			}

			/**
			 * DuplicatedCode
			 *
			 * @noinspection DuplicatedCode
			 */
			$data           = compact( 'meta_value' );
			$field_meta_key = 'meta_key'; // To please PHPCS
			$where          = array(
				$column         => $object_id,
				$field_meta_key => $meta_key,
			);

			$result = $wpdb->update( $table, $data, $where );

			if ( ! $result ) {
				return false;
			}

			wp_cache_delete( $object_id, $meta_type . '_meta' );

			return true;
		}

		/**
		 * Filter term meta data.
		 *
		 * @since        2.8.9
		 * @noinspection PhpUnusedParameterInspection
		 */
		public static function filter__term_metadata( $check, $object_id, $meta_key, $single ) {

			if ( empty( self::$meta_fields ) ) {
				return $check;
			}

			if ( ! self::meta_key_exists( $meta_key ) ) {
				return $check;
			}

			if ( empty( self::$builder->get_language() ) ) {
				/**
				 * Prevent update term meta when $builder is not set.
				 */
				return $check;
			}

			$meta_type = 'term';

			$meta_cache = wp_cache_get( $object_id, $meta_type . '_meta' );

			if ( ! $meta_cache ) {
				$meta_cache = update_meta_cache( $meta_type, array( $object_id ) );
				$meta_cache = $meta_cache[ $object_id ];
			}

			if ( ! empty( $meta_cache[ $meta_key ][0] ) && WPGlobus_Core::has_translations( $meta_cache[ $meta_key ][0] ) ) {

				$meta_cache[ $meta_key ][0] = WPGlobus_Core::text_filter(
					$meta_cache[ $meta_key ][0],
					self::$builder->get_language(),
					WPGlobus::RETURN_EMPTY
				);

				wp_cache_replace(
					$object_id,
					$meta_cache,
					$meta_type . '_meta'
				);
			}

			return $check;
		}

		/**
		 * Update term meta data.
		 *
		 * @since 2.8.9
		 */
		public static function filter__update_term_metadata( $check, $object_id, $meta_key, $meta_value, $prev_value ) {

			if ( empty( self::$meta_fields ) ) {
				return $check;
			}

			if ( ! self::meta_key_exists( $meta_key ) ) {
				return $check;
			}

			if ( empty( self::$builder->get_language() ) ) {
				/**
				 * Prevent update term meta when $builder is not set.
				 */
				return $check;
			}

			return self::update_metadata( $check, $object_id, $meta_key, $meta_value, $prev_value, 'term' );
		}

		/**
		 * Unused.
		 * public static function get_post_meta( $post_id = false ) {
		 * $meta_cache = wp_cache_get($post_id, 'post_meta');
		 * }
		 */

		/**
		 * Update post meta data.
		 *
		 * See wp-includes\meta.php "update_{$meta_type}_metadata".
		 *
		 * @param null|bool $check      Whether to allow updating metadata for the given type.
		 * @param int       $object_id  Object ID.
		 * @param string    $meta_key   Meta key.
		 * @param mixed     $meta_value Meta value. Must be serializable if non-scalar.
		 * @param mixed     $prev_value Optional. If specified, only update existing
		 *                              metadata entries with the specified value.
		 *                              Otherwise, update all entries.
		 *
		 * @return bool
		 */
		public static function filter__update_post_metadata( $check, $object_id, $meta_key, $meta_value, $prev_value ) {

			if ( empty( self::$meta_fields ) ) {
				return $check;
			}

			if ( ! self::meta_key_exists( $meta_key ) ) {
				return $check;
			}

			if ( empty( self::$builder->get_language() ) ) {
				/**
				 * Prevent update post meta when $builder is not set.
				 * For example, Elementor saves meta `_elementor_data` for Template (post type `elementor_library`).
				 * And we have meta `_elementor_data` in `$meta_fields` array See wpglobus\configs\elementor.json
				 *
				 * @since 2.2.33
				 */
				return $check;
			}

			$meta_type = 'post';

			/**
			 * Global
			 *
			 * @global wpdb $wpdb
			 */
			global $wpdb;

			/**
			 * DuplicatedCode
			 *
			 * @noinspection DuplicatedCode
			 */
			$table = _get_meta_table( $meta_type );
			if ( ! $table ) {
				return false;
			}

			$column    = $meta_type . '_id';
			$id_column = 'meta_id';

			$raw_meta_key = $meta_key;

			/**
			 * Compare existing value to new value if no prev value given and the key exists only once.
			 *
			 * @since 2.5.16 Fixed PHP Warning: count(): Parameter must be an array or an object that implements Countable.
			 */
			if ( empty( $prev_value ) ) {
				$old_value = get_metadata_raw( $meta_type, $object_id, $meta_key );
				if ( is_countable( $old_value ) && count( $old_value ) === 1 ) { // phpcs:ignore PHPCompatibility
					if ( $old_value[0] === $meta_value ) {
						return false;
					}
				}
			}

			$_meta_value = $meta_value;
			/**
			 * Use maybe_serialize
			 *
			 * @since 2.1.7
			 */
			$meta_value = maybe_serialize( $meta_value );

			/**
			 * Don't auto-modify this SQL query.
			 */
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$meta_ids = $wpdb->get_col( $wpdb->prepare( "SELECT $id_column FROM $table WHERE meta_key = %s AND $column = %d", $meta_key, $object_id ) );

			/**
			 * Incorrect query.
			 * $meta_ids = $wpdb->get_col( $wpdb->prepare(
			 * 'SELECT %s FROM %s WHERE meta_key = %s AND %s = %d',
			 * $id_column,
			 * $table,
			 * $meta_key,
			 * $column,
			 * $object_id
			 * ) );
			 */

			if ( empty( $meta_ids ) ) {

				$_passed_value = $_meta_value;

				if ( ! empty( $_passed_value ) && WPGlobus::Config()->default_language !== self::$builder->get_language() ) {
					/**
					 * If we get multilingual $meta_value, save it as is.
					 *
					 * @since 1.9.25 do nothing.
					 */
					if ( ! WPGlobus_Core::has_translations( $_passed_value ) ) {
						$_passed_value = self::build_multilingual_string( array( self::$builder->get_language() => $_passed_value ) );
					}
				}

				return add_metadata( $meta_type, $object_id, $raw_meta_key, $_passed_value );
			}

			/**
			 * WPGlobus Core.
			 * We get $meta_value in language that can be retrieved with self::$builder->get_language().
			 */
			$meta_cache = wp_cache_get( $object_id, $meta_type . '_meta' );

			/**
			 * May be we need to update meta cache.
			 *
			 * @see 'filter__post_metadata' function.
			 *
			 * //            if ( !$meta_cache ) {
			 * //                $meta_cache = update_meta_cache( $meta_type, array( $object_id ) );
			 * //                $meta_cache = $meta_cache[$object_id];
			 * //            }
			 */

			if ( isset( $meta_cache[ $meta_key ] ) ) {

				/**
				 * If we get multilingual $meta_value, save it as is.
				 *
				 * @since 1.9.25 do nothing.
				 */
				if ( ! WPGlobus_Core::has_translations( $meta_value ) ) {

					/**
					 * Unused _key
					 *
					 * @noinspection PhpUnusedLocalVariableInspection
					 */
					foreach ( $meta_cache[ $meta_key ] as $_key => $_ml_value ) {

						$_new_ml_array = array();

						if ( WPGlobus_Core::has_translations( $_ml_value ) ) {

							foreach ( WPGlobus::Config()->enabled_languages as $language ) :

								if ( $language === self::$builder->get_language() ) {

									if ( ! empty( $meta_value ) ) {
										$_new_ml_array[ $language ] = $meta_value;
									}
								} else {

									$_value = WPGlobus_Core::text_filter( $_ml_value, $language, WPGlobus::RETURN_EMPTY );

									if ( '' !== $_value ) {
										$_new_ml_array[ $language ] = $_value;
									}
								}

							endforeach;

							$_new_value = self::build_multilingual_string( $_new_ml_array );

						} else {

							if ( WPGlobus::Config()->default_language === self::$builder->get_language() ) {
								$_new_ml_array[ WPGlobus::Config()->default_language ] = $meta_value;
							} else {
								$_new_ml_array[ WPGlobus::Config()->default_language ] = $_ml_value;
								if ( ! empty( $meta_value ) ) {
									$_new_ml_array[ self::$builder->get_language() ] = $meta_value;
								}
							}

							$_new_value = self::build_multilingual_string( $_new_ml_array );

						}

						if ( ! empty( $_new_value ) ) {
							/**
							 * Need?
							 * $meta_value = maybe_unserialize( $_new_value );
							 */
							$meta_value = $_new_value;
						}
					}
				}
			}

			/**
			 * DuplicatedCode
			 *
			 * @noinspection DuplicatedCode
			 */
			$data           = compact( 'meta_value' );
			$field_meta_key = 'meta_key'; // To please PHPCS
			$where          = array(
				$column         => $object_id,
				$field_meta_key => $meta_key,
			);

			$result = $wpdb->update( $table, $data, $where );

			if ( ! $result ) {
				return false;
			}

			wp_cache_delete( $object_id, $meta_type . '_meta' );

			return true;
		}

		/**
		 * Delete metadata for the specified object.
		 *
		 * In our case we should prevent deleting multilingual string when $meta_value is empty.
		 *
		 * See wp-includes\meta.php "delete_{$meta_type}_metadata".
		 *
		 * @param null|bool $check      Whether to allow metadata deletion of the given type.
		 * @param int       $object_id  Object ID.
		 * @param string    $meta_key   Meta key.
		 * @param mixed     $meta_value Meta value. Must be serializable if non-scalar.
		 * @param bool      $delete_all Whether to delete the matching metadata entries
		 *                              for all objects, ignoring the specified $object_id.
		 *                              Default false.
		 *
		 * @return bool|null
		 * @noinspection PhpUnusedParameterInspection
		 */
		public static function filter__delete_metadata(
			$check, $object_id, $meta_key, $meta_value,
			$delete_all = false
		) {

			if ( empty( self::$meta_fields ) ) {
				return $check;
			}

			if ( ! self::meta_key_exists( $meta_key ) ) {
				return $check;
			}

			$result = true;

			if ( empty( $meta_value ) && WPGlobus::Config()->default_language === self::$builder->get_language() ) {

				/**
				 * Todo check for extra languages.
				 */

				/**
				 * Global
				 *
				 * @global wpdb $wpdb
				 */
				global $wpdb;

				$_meta_value =
					$wpdb->get_col(
						$wpdb->prepare(
							"SELECT meta_value FROM $wpdb->postmeta WHERE post_id = %d AND meta_key = %s",
							$object_id,
							$meta_key
						)
					);

				if ( ! isset( $_meta_value ) || empty( $_meta_value[0] ) ) {
					$result = null;
				} else {
					if ( WPGlobus_Core::has_translations( $_meta_value[0] ) ) {
						$result = true;
					} else {
						$result = null;
					}
				}
			}

			return $result;
		}

		/**
		 * Get post meta.
		 *
		 * @see get_metadata()
		 *
		 * @param null|array|string $check     The value get_metadata() should return - a single metadata value,
		 *                                     or an array of values.
		 * @param int               $object_id Object ID.
		 * @param string            $meta_key  Meta key.
		 * @param bool              $single    Whether to return only the first value of the specified $meta_key.
		 *
		 * @return array|null|string
		 */
		public static function filter__post_metadata( $check, $object_id, $meta_key, $single ) {

			if ( empty( self::$meta_fields ) ) {
				return $check;
			}

			if ( empty( $meta_key ) ) {
				$_cache_meta_key = '__WPGLOBUS_META_KEY_EMPTY__';
			} else {

				if ( ! self::meta_key_exists( $meta_key ) ) {
					return $check;
				}
				$_cache_meta_key = $meta_key;
			}

			$return_value = 'as_array';
			if ( $single ) {
				$return_value = 'as_single';
			}

			/**
			 * Disable/enable to filter meta field.
			 *
			 * @since 1.9.25
			 *
			 * @param string $meta_key Meta key.
			 *
			 * @return string|bool String if to filter meta field or false if not.
			 */
			if ( ! empty( $meta_key ) && is_string( $meta_key ) ) {
				/**
				 * Filter 'wpglobus/meta/key'
				 *
				 * @since 1.9.25
				 */
				$meta_key = apply_filters( 'wpglobus/meta/key', $meta_key );
				if ( false === $meta_key ) {
					return $check;
				}
			}

			/**
			 * May be called many times on one page. Let's cache.
			 * //if ( isset( $_cache[ $_cache_meta_key ][ $object_id ][ $return_value ] ) ) {
			 * // @since 2.4
			 * //return $_cache[ $_cache_meta_key ][ $object_id ][ $return_value ];
			 * //}
			 */
			static $_cache;

			$meta_type = 'post';

			$meta_cache = wp_cache_get( $object_id, $meta_type . '_meta' );

			if ( ! $meta_cache ) {
				$meta_cache = update_meta_cache( $meta_type, array( $object_id ) );
				$meta_cache = $meta_cache[ $object_id ];
			}

			if ( ! $meta_key ) {

				/**
				 * Todo add doc.
				 */
				if ( ! empty( $meta_cache ) ) {

					foreach ( $meta_cache as $_meta_key => $_values ) {
						if ( self::meta_key_exists( $_meta_key ) ) {
							foreach ( $_values as $__key => $__value ) {
								$meta_cache[ $_meta_key ][ $__key ] = WPGlobus_Core::text_filter( $__value, self::$builder->get_language(), WPGlobus::RETURN_EMPTY );
							}
						}
					}
				}

				$_cache[ $_cache_meta_key ][ $object_id ][ $return_value ] = $meta_cache;

				return $meta_cache;

			} else {

				if ( isset( $meta_cache[ $meta_key ] ) ) {

					if ( $single ) {

						$_value = WPGlobus_Core::text_filter( $meta_cache[ $meta_key ][0], self::$builder->get_language(), WPGlobus::RETURN_EMPTY );

						if ( ! empty( $_value ) ) {
							$_value = maybe_unserialize( $_value );
						}

						if ( is_array( $_value ) ) {
							// Note!
							$_value = array( $_value );
						} else {
							$_cache[ $_cache_meta_key ][ $object_id ][ $return_value ] = $_value;
						}

						return $_value;

					} else {

						foreach ( $meta_cache[ $meta_key ] as $_key => $_value ) {

							if ( WPGlobus_Core::has_translations( $_value ) ) {
								$_value = WPGlobus_Core::text_filter( $_value, self::$builder->get_language(), WPGlobus::RETURN_EMPTY );
							} elseif ( self::$builder->get_language() !== WPGlobus::Config()->default_language ) {
								$_value = '';
							}

							if ( ! empty( $_value ) ) {
								$meta_cache[ $meta_key ][ $_key ] = maybe_unserialize( $_value );
							} else {
								$meta_cache[ $meta_key ][ $_key ] = '';
							}
						}

						$_cache[ $_cache_meta_key ][ $object_id ][ $return_value ] = $meta_cache[ $meta_key ];

						return $meta_cache[ $meta_key ];

					}
				}

				if ( $single ) {
					return '';
				} else {
					return array();
				}
			}

			/**
			 * We should not be here. Keeping as a precaution for potential code changes.
			 *
			 * @noinspection PhpUnreachableStatementInspection
			 */
			return $check;
		}

		/**
		 * Check for meta key existence.
		 *
		 * @param string $meta_key
		 *
		 * @return bool
		 */
		protected static function meta_key_exists( $meta_key ) {
			if ( in_array( $meta_key, self::$meta_fields, true ) ) {
				return true;
			}

			return false;
		}

		/**
		 * Build multilingual string.
		 *
		 * @since 1.9.25
		 *
		 * @param string[] $ml_array
		 *
		 * @return string
		 * @todo  First idea is: This function needs for Page Builder by SiteOrigin because it should serialize array before creating multilingual string.
		 */
		protected static function build_multilingual_string( $ml_array ) {

			/**
			 * Todo W.I.P with Page Builder by SiteOrigin.
			 * foreach( $ml_array as $language=>$value ) {
			 * if ( is_array($value) ) {
			 * $ml_array[$language] = maybe_serialize($value);
			 * }
			 * }
			 */

			return WPGlobus_Utils::build_multilingual_string( $ml_array );
		}
	}

endif;
