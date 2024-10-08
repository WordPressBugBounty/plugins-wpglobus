<?php
/**
 * Filters and actions
 * Only methods here. The add_filter calls are in the Controller
 *
 * @package WPGlobus
 */

/**
 * Class WPGlobus_Filters
 */
class WPGlobus_Filters {

	/**
	 * Meta keys where data can be multilingual
	 *
	 * @var string[]
	 */
	protected static $multilingual_meta_keys = array();

	/**
	 * This is the basic filter used to extract the text portion in the current language from a string.
	 * Applied to the main WP texts, such as post title, content and excerpt.
	 *
	 * @param string $text
	 *
	 * @return string
	 */
	public static function filter__text( $text ) {

		return WPGlobus_Core::text_filter(
			$text,
			WPGlobus::Config()->language,
			null,
			WPGlobus::Config()->default_language
		);
	}

	/**
	 * This is the alternate filter used to extract the text portion in the current language from a string.
	 *
	 * @since 2.4.16
	 *
	 * @param string $text
	 *
	 * @return string
	 */
	public static function filter__extract_text( $text ) {

		return WPGlobus_Core::extract_text(
			$text,
			WPGlobus::Config()->language
		);
	}

	/**
	 * This filter is needed to display correctly the posts with the '--- MORE ---' separator
	 * in archives.
	 * Without it, the post content is truncated at the beginning of <!--more-->, thus keeping
	 * only the first language.
	 * *
	 * 'the_posts' filter is used by @see get_posts(), which is commonly used in all sorts of places,
	 * including, for instance, @see wp_get_associated_nav_menu_items while deleting a post.
	 * So, to minimize potential side effects, we limit the scope to main queries, or
	 * when the 'wpglobus_force_filter__the_posts' is set
	 * (@see WPGlobus_QA::_test_get_posts for example).
	 *
	 * @since 1.0.14
	 *
	 * @param WP_Query $query
	 *
	 * @param array    $posts
	 *
	 * @return array
	 */
	public static function filter__the_posts( $posts, $query ) {

		if ( $query->is_main_query() || $query->get( 'wpglobus_force_filter__the_posts' ) ) {
			foreach ( $posts as $post ) {

				/**
				 * Don't filter post of disabled post type.
				 *
				 * @since 2.5.15
				 */
				if ( in_array( $post->post_type, WPGlobus::Config()->disabled_entities, true ) ) {
					continue;
				}

				WPGlobus_Core::translate_wp_post(
					$post,
					WPGlobus::Config()->language,
					WPGlobus::RETURN_IN_DEFAULT_LANGUAGE
				);
			}
		}

		return $posts;
	}

	/**
	 * This is similar to the @see filter__text filter,
	 * but it returns text in the DEFAULT language.
	 *
	 * @since        1.0.8
	 *
	 * @param string $text
	 *
	 * @return string
	 * @noinspection PhpUnused
	 */
	public static function filter__text_default_language( $text ) {

		return WPGlobus_Core::text_filter(
			$text,
			WPGlobus::Config()->default_language,
			null,
			WPGlobus::Config()->default_language
		);
	}


	/**
	 * Filter @see get_terms
	 *
	 * @scope admin
	 * @scope front
	 *
	 * @param string[]|object[] $terms
	 *
	 * @return array
	 */
	public static function filter__get_terms( array $terms ) {

		/**
		 * Todo Example of a "stopper" filter
		 *       if ( apply_filters( 'wpglobus_do_filter__get_terms', true ) ) {}
		 *       Because it might affect the performance, this is a to-do for now.
		 */

		foreach ( $terms as &$_term ) {
			WPGlobus_Core::translate_term( $_term, WPGlobus::Config()->language );
		}
		unset( $_term );

		reset( $terms );

		return $terms;
	}

	/**
	 * Filter @see get_the_terms
	 *
	 * @scope admin
	 *
	 * @param stdClass[]|WP_Error $terms List of attached terms, or WP_Error on failure.
	 *
	 * @return array
	 */
	public static function filter__get_the_terms( $terms ) {

		/**
		 * Theoretically, we should not have this filter because @see get_the_terms
		 * calls @see wp_get_object_terms, which is already filtered.
		 * However, there is a case when the terms are retrieved from @see get_object_term_cache,
		 * and when we do a Quick Edit / inline-save, we ourselves write raw terms to the cache.
		 * As of now, we know only one such case, so we activate this filter only in admin,
		 * and only on the 'single_row' call
		 *
		 * @todo     Keep watching this
		 * @internal 15.01.31
		 */

		if ( ! is_wp_error( $terms ) && WPGlobus_WP::is_function_in_backtrace( 'single_row' ) ) {

			// Casting $terms to (array) causes syntax error in PHP 5.3 and older.
			/* @noinspection ForeachSourceInspection */
			foreach ( $terms as &$_term ) {
				WPGlobus_Core::translate_term( $_term, WPGlobus::Config()->language );
			}
			unset( $_term );

			reset( $terms );
		}

		return $terms;
	}

	/**
	 * Filter @see wp_get_object_terms()
	 *
	 * @scope admin
	 * @scope front
	 *
	 * @param string[]|stdClass[] $terms An array of terms for the given object or objects.
	 *
	 * @return array
	 */
	public static function filter__wp_get_object_terms( array $terms ) {

		/**
		 * Do not need to check for is_wp_error($terms),
		 * because the WP_Error is returned by wp_get_object_terms() before applying filter.
		 *
		 * @internal
		 */

		if ( ! count( $terms ) ) {
			return $terms;
		}

		/**
		 * Don't filter term names when saving or publishing posts
		 *
		 * @todo Check this before add_filter and not here
		 * @todo Describe exactly how to check this visually, and is possible - write the acceptance test
		 */
		if (
			is_admin() &&
			WPGlobus_WP::is_pagenow( 'post.php' ) &&
			(
				WPGlobus_WP::get_http_post_parameter( 'save' ) ||
				WPGlobus_WP::get_http_post_parameter( 'publish' )
			)
		) {
			return $terms;
		}

		$_GET_action = WPGlobus_WP::get_http_get_parameter( 'action' );

		/**
		 * Don't filter term names for trash and un-trash single post
		 * We check post.php page instead of edit.php because redirect
		 */
		if ( is_admin() && WPGlobus_WP::is_pagenow( 'post.php' ) &&
			 ( 'trash' === $_GET_action || 'untrash' === $_GET_action )
		) {
			return $terms;
		}

		/**
		 * Don't filter term names bulk trash and untrash posts
		 */
		if ( is_admin() && WPGlobus_WP::is_pagenow( 'edit.php' ) &&
			 ( 'trash' === $_GET_action || 'untrash' === $_GET_action )
		) {
			return $terms;
		}

		/**
		 * Don't filter term names for bulk edit post from edit.php page
		 */
		if ( is_admin() && WPGlobus_WP::is_function_in_backtrace( 'bulk_edit_posts' ) ) {
			return $terms;
		}

		/**
		 * Don't filter term names for inline-save ajax action from edit.php page
		 *
		 * @see wp_ajax_inline_save
		 * ...except when the same AJAX refreshes the table row @see WP_Posts_List_Table::single_row
		 * -
		 * @qa  At the "All posts" admin page, do Quick Edit on any post. After update, categories and tags
		 *     must not show multilingual strings with delimiters.
		 * @qa  At Quick Edit, enter an existing tag. After save, check if there is no additional tag
		 *     on the "Tags" page. If a new tag is created then the "is tag exists" check was checking
		 *     only a single language representation of the tag, while there is a multilingual tag in the DB.
		 */
		if ( WPGlobus_WP::is_http_post_action( 'inline-save' ) &&
			 WPGlobus_WP::is_pagenow( 'admin-ajax.php' )
		) {
			if ( ! WPGlobus_WP::is_function_in_backtrace( 'single_row' ) ) {
				return $terms;
			}
		}

		0 && wp_verify_nonce( '' );

		/**
		 * Don't filter term names for heartbeat autosave
		 */
		if ( WPGlobus_WP::is_http_post_action( 'heartbeat' ) &&
			 WPGlobus_WP::is_pagenow( 'admin-ajax.php' ) &&
			 ! empty( $_POST['data']['wp_autosave'] )
		) {
			return $terms;
		}

		/**
		 * Don't filter term name at time generate checklist categories in metabox
		 */
		if (
			empty( $_POST ) &&
			is_admin() &&
			WPGlobus_WP::is_pagenow( 'post.php' ) &&
			WPGlobus_WP::is_function_in_backtrace( 'wp_terms_checklist' )
		) {
			return $terms;
		}

		foreach ( $terms as &$_term ) {
			WPGlobus_Core::translate_term( $_term, WPGlobus::Config()->language );
		}
		unset( $_term );

		reset( $terms );

		return $terms;
	}

	/**
	 * This filter is needed to build correct permalink (slug, post_name)
	 * using only the main part of the post title (in the default language).
	 * -
	 * Because 'sanitize_title' is a commonly used function, we have to apply our filter
	 * only on very specific calls. Therefore, there are (ugly) debug_backtrace checks.
	 * -
	 * Case 1
	 * When a draft post is created,
	 * the post title is converted to the slug in the @see get_sample_permalink function,
	 * using the 'sanitize_title' filter.
	 * -
	 * Case 2
	 * When the draft is published, @see wp_insert_post calls
	 *
	 * @see               sanitize_title to set the slug
	 * -
	 * @see               WPGLobus_QA::_test_post_name
	 * -
	 * @see               WPSEO_Metabox::localize_script
	 *
	 * @param string $title
	 *
	 * @return string
	 * @todo              Check what's going on in localize_script of WPSEO?
	 * @todo              What if there is no EN language? Only ru and kz but - we cannot use 'en' for permalink
	 * @todo              check guid
	 */
	public static function filter__sanitize_title( $title ) {

		if (
			WPGlobus_WP::is_filter_called_by( 'get_sample_permalink' ) ||
			WPGlobus_WP::is_filter_called_by( 'wp_insert_post' ) ||
			WPGlobus_WP::is_filter_called_by( 'wp_update_term' )
		) {
			/**
			 * Internal_note: the DEFAULT language, not the current one
			 */
			$title = WPGlobus_Core::text_filter(
				$title, WPGlobus::Config()->default_language
			);
		}

		return $title;
	}

	/**
	 * Filter @see get_term()
	 *
	 * @param string|object $term
	 *
	 * @return string|object
	 */
	public static function filter__get_term( $term ) {

		if ( ! WPGlobus_WP::is_http_post_action( 'inline-save-tax' ) ) {
			/**
			 * Don't filter ajax action 'inline-save-tax' from edit-tags.php page.
			 * See quick_edit() in includes/js/wpglobus.admin.js
			 * for and example of working with taxonomy name and description
			 * wp_current_filter contains
			 * 0=wp_ajax_inline-save-tax
			 * 1=get_term
			 *
			 * @see wp_ajax_inline_save_tax()
			 */
			WPGlobus_Core::translate_term( $term, WPGlobus::Config()->language );
		}

		return $term;
	}

	/**
	 * Filter @see get_terms_to_edit()
	 *
	 * @since 1.6.4
	 *
	 * @param string
	 *
	 * @return string
	 */
	public static function filter__terms_to_edit( $terms_to_edit ) {

		if ( ! WPGlobus_Core::has_translations( $terms_to_edit ) ) {
			return $terms_to_edit;
		}

		$terms = explode( ',', $terms_to_edit );

		foreach ( $terms as $k => $term ) {
			$terms[ $k ] = WPGlobus_Core::text_filter( $term, WPGlobus::Config()->language );
		}

		return implode( ',', $terms );
	}

	/**
	 * Filter @see wp_insert_term().
	 *
	 * @since        1.6.6
	 *
	 * @param string $term     The term to add or update.
	 * @param string $taxonomy Taxonomy slug.
	 *
	 * @return string
	 * @noinspection PhpUnusedParameterInspection
	 */
	public static function filter__pre_insert_term( $term, $taxonomy ) {

		$multilingual_term = esc_sql( $term );
		if ( WPGlobus::Config()->language !== WPGlobus::Config()->default_language ) {
			$multilingual_term = WPGlobus_Utils::build_multilingual_string( array( WPGlobus::Config()->language => $term ) );
		}

		global $wpdb;

		$data = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->terms AS terms WHERE terms.name LIKE %s", "%{$multilingual_term}%" ) );

		if ( count( $data ) > 0 ) {
			/**
			 * Return empty to prevent creating duplicate term.
			 *
			 * @see wp_insert_term() in wp-includes\taxonomy.php
			 */
			return '';
		}

		return $term;
	}

	/**
	 * Localize home_url
	 * Should be processed on:
	 * - front
	 * - AJAX, except for several specific actions
	 *
	 * @param string $url
	 *
	 * @return string
	 */
	public static function filter__home_url( $url ) {

		/**
		 * Internal note
		 * Example of URL in admin:
		 * When admin interface is not in default language, we still should not see
		 * any permalinks with language prefixes.
		 * For that, we could check if we are at the 'post.php' screen:
		 * if ( 'post.php' == $pagenow ) ....
		 * However, we do not need it, because we disallowed almost any processing in admin.
		 */

		/**
		 * 1. Do not work in admin
		 */
		$need_to_process = ( ! is_admin() );

		if ( WPGlobus_WP::is_pagenow( 'admin-ajax.php' ) ) {
			/**
			 * 2. But work in AJAX, which is also admin
			 */
			$need_to_process = true;

			/**
			 * 3. However, don't convert url for these AJAX actions:
			 */
			if ( WPGlobus_WP::is_http_post_action(
				array(
					'heartbeat',
					'sample-permalink',
					'add-menu-item',
				)
			)
			) {
				$need_to_process = false;
			}
		}

		if ( $need_to_process ) {
			$url = WPGlobus_Utils::localize_url( $url );
		}

		return $url;
	}

	/**
	 * Filter @see get_pages
	 *
	 * @qa See a list of available pages in the "Parent Page" metabox when editing a page.
	 *
	 * @param WP_Post[] $pages
	 *
	 * @return WP_Post[]
	 */
	public static function filter__get_pages( $pages ) {

		foreach ( $pages as &$_page ) {
			WPGlobus_Core::translate_wp_post( $_page, WPGlobus::Config()->language );
		}
		unset( $_page );

		reset( $pages );

		return $pages;
	}

	/**
	 * Filter for @see get_locale
	 *
	 * @see     setlocale
	 *
	 * @param string $locale Locale.
	 *
	 * @return string
	 * @todo    Do we need to do setlocale(LC_???, $locale)? (*** NOT HERE )
	 * @link    http://php.net/manual/en/function.setlocale.php
	 * @example echo setlocale(LC_ALL, 'Russian'); => Russian_Russia.1251
	 */
	public static function filter__get_locale( $locale ) {

		/**
		 * Admin.
		 * In admin area, we show everything in the language of admin interface.
		 * The admin interface is set in the Settings -> General and saved as 'WPLANG' option.
		 * For that, we only need to set the WPGlobus language, not changing the locale.
		 * Note: no caching in admin!
		 */
		if (
			// We need to exclude is_admin when it's a front-originated AJAX.
			is_admin()
			&& ( ! WPGlobus_WP::is_doing_ajax() || WPGlobus_WP::is_admin_doing_ajax() )
			/**
			 * Filter wpglobus_use_admin_wplang
			 *
			 * @since 1.9.15
			 */
			&& apply_filters( 'wpglobus_use_admin_wplang', true )
		) {
			/**
			 * Set locale in admin area using WPLANG option.
			 */
			$locale_from_wplang = get_option( 'WPLANG' );
			if ( ! $locale_from_wplang ) {
				$locale_from_wplang = 'en_US';
			}
			WPGlobus::Config()->set_language( $locale_from_wplang );

			// Return w/o caching - important!
			return $locale_from_wplang;
		}

		/**
		 * Set locale if the language is enabled in WPGlobus.
		 *
		 * @since 2.10.3 - Cache it.
		 * @since 2.10.7 - Removing caching as it does not work under certain conditions.
		 */
		$language = WPGlobus::Config()->language;
		if ( WPGlobus_Utils::is_enabled( $language ) ) {
			$locale = WPGlobus::Config()->locale[ $language ];
		}

		return $locale;
	}

	/**
	 * Filter @see wp_setup_nav_menu_item in wp-includes\nav-menu.php for more info
	 *
	 * @since        1.0.0
	 *
	 * @param WP_Post $menu_item
	 *
	 * @return WP_Post
	 * @noinspection PhpUnused
	 */
	public static function filter__nav_menu_item( $menu_item ) {
		/**
		 * This filter is used at nav-menus.php page for .field-move elements
		 */
		if ( is_object( $menu_item ) && 'WP_Post' === get_class( $menu_item ) ) {

			if ( ! empty( $menu_item->title ) ) {
				$menu_item->title = WPGlobus_Core::text_filter( $menu_item->title, WPGlobus::Config()->language );
			}
			if ( ! empty( $menu_item->description ) ) {
				$menu_item->description = WPGlobus_Core::text_filter( $menu_item->description, WPGlobus::Config()->language );
			}
		}

		return $menu_item;
	}

	/**
	 * Filter @see nav_menu_description
	 *
	 * @since 1.0.0
	 *
	 * @param string $description
	 *
	 * @return string
	 */
	public static function filter__nav_menu_description( $description ) {
		/**
		 * This filter for translate menu item description
		 */
		if ( ! empty( $description ) ) {
			$description = WPGlobus_Core::text_filter( $description, WPGlobus::Config()->language );
		}

		return $description;
	}

	/**
	 * Filter @see heartbeat_received
	 *
	 * @since        1.0.1
	 *
	 * @param array  $response
	 * @param array  $data
	 * @param string $screen_id
	 *
	 * @return array
	 * @noinspection PhpUnusedParameterInspection
	 */
	public static function filter__heartbeat_received( $response, $data, $screen_id ) {

		if ( false !== strpos( WPGlobus_WP::http_referer(), 'wpglobus=off' ) ) {
			/**
			 * Check $_SERVER['HTTP_REFERER'] for wpglobus toggle is off because wpglobus-admin.js doesn't loaded in this mode
			 */
			return $response;
		}

		if ( ! empty( $data['wp_autosave'] ) ) {

			if ( empty( $data['wp_autosave']['post_id'] ) || 0 === (int) $data['wp_autosave']['post_id'] ) {
				/**
				 * Note: wp_autosave may come from edit.php page
				 */
				return $response;
			}

			if ( empty( $data['wpglobus_heartbeat'] ) ) {
				/**
				 * Check for wpglobus key
				 */
				return $response;
			}

			$title_wrap     = false;
			$content_wrap   = false;
			$post_title_ext = '';
			$content_ext    = '';

			foreach ( WPGlobus::Config()->enabled_languages as $language ) {
				if ( WPGlobus::Config()->default_language === $language ) {

					$post_title_ext .= WPGlobus::add_locale_marks( $data['wp_autosave']['post_title'], $language );
					$content_ext    .= WPGlobus::add_locale_marks( $data['wp_autosave']['content'], $language );

				} else {

					if ( ! empty( $data['wp_autosave'][ 'post_title_' . $language ] ) ) {
						$title_wrap = true;

						$post_title_ext .= WPGlobus::add_locale_marks( $data['wp_autosave'][ 'post_title_' . $language ], $language );
					}

					if ( ! empty( $data['wp_autosave'][ 'content_' . $language ] ) ) {
						$content_wrap = true;

						$content_ext .= WPGlobus::add_locale_marks( $data['wp_autosave'][ 'content_' . $language ], $language );
					}
				}
			}

			if ( $title_wrap ) {
				$data['wp_autosave']['post_title'] = $post_title_ext;
			}

			if ( $content_wrap ) {
				$data['wp_autosave']['content'] = $content_ext;
			}

			/**
			 * Filter before autosave
			 *
			 * @since 1.0.2
			 *
			 * @param array $data ['wp_autosave'] Array of post data.
			 */
			$data['wp_autosave'] = apply_filters( 'wpglobus_autosave_post_data', $data['wp_autosave'] );

			$saved = wp_autosave( $data['wp_autosave'] );

			if ( is_wp_error( $saved ) ) {
				$response['wp_autosave'] = array(
					'success' => false,
					'message' => $saved->get_error_message(),
				);
			} elseif ( empty( $saved ) ) {
				$response['wp_autosave'] = array(
					'success' => false,
					'message' => __( 'Error while saving.' ),
				);
			} else {
				$draft_saved_date_format = __( 'g:i:s a' );
				$response['wp_autosave'] = array(
					'success' => true,
					'message' => sprintf(
					// Translators:
						__( 'Draft saved at %s.' ), date_i18n( $draft_saved_date_format )
					),
				);
			}
		}

		return $response;
	}

	/**
	 * Filter @see wp_nav_menu_objects
	 *
	 * @since 1.0.2
	 *
	 * @param WP_Post[] $sorted_menu_items The menu items, sorted by each menu item's menu order.
	 *
	 * @return WP_Post[]
	 */
	public static function filter__nav_menu_objects( $sorted_menu_items ) {

		if ( is_array( $sorted_menu_items ) ) {
			foreach ( $sorted_menu_items as &$post ) {
				if ( ! empty( $post->attr_title ) ) {
					$post->attr_title = WPGlobus_Core::text_filter( $post->attr_title, WPGlobus::Config()->language );
				}
			}
		}

		return $sorted_menu_items;
	}

	/**
	 * Translate widget strings (besides the title handled by the `widget_title` filter)
	 *
	 * @see   WP_Widget::display_callback
	 * @scope front
	 *
	 * @since 1.0.6
	 * @since 2.4.10 Prevent handling of incorrect widget instance's settings.
	 * @since 2.8.5 Added checking for translations in $widget_setting.
	 *                Using `extract_text` instead of `text_filter`.
	 *
	 * @param string[] $instance
	 *
	 * @return string[]
	 */
	public static function filter__widget_display_callback( $instance ) {

		/**
		 * Prevent handling of incorrect widget instance's settings.
		 *
		 * @since 2.4.10
		 */
		if ( empty( $instance ) || is_bool( $instance ) ) {
			return $instance;
		}

		foreach ( $instance as &$widget_setting ) {

			/**
			 * What?
			 *
			 * @noinspection ReferenceMismatchInspection
			 */
			if ( ! empty( $widget_setting ) && is_string( $widget_setting ) && WPGlobus_Core::has_translations( $widget_setting ) ) {
				$widget_setting =
					WPGlobus_Core::extract_text( $widget_setting, WPGlobus::Config()->language );
			}
		}

		return $instance;
	}

	/**
	 * Filter @see comment_moderation_text,
	 *
	 * @see   comment_moderation_subject
	 * @since 1.0.6
	 *
	 * @param string $text
	 * @param int    $comment_id
	 *
	 * @return string
	 */
	public static function filter__comment_moderation( $text, $comment_id ) {

		$comment = get_comment( $comment_id );
		$post    = get_post( $comment->comment_post_ID );
		$title   = WPGlobus_Core::text_filter( $post->post_title, WPGlobus::Config()->language );

		return str_replace( $post->post_title, $title, $text );
	}

	/**
	 * Filter @see wp_trim_words
	 *
	 * @qa           At the /wp-admin/index.php page is a Quick Draft metabox
	 *      which shows 3 last post drafts. This filter lets post content in default language.
	 * @since        1.0.14
	 *
	 * @param string $text          The trimmed text.
	 * @param int    $num_words     The number of words to trim the text to.
	 * @param string $more          An optional string to append to the end of the trimmed text, e.g. &hellip;.
	 * @param string $original_text The text before it was trimmed.
	 *
	 * @return string
	 * @noinspection PhpUnusedParameterInspection
	 */
	public static function filter__wp_trim_words( $text, $num_words, $more, $original_text ) {

		/**
		 * Method argument is ignored.
		 *
		 * @noinspection SuspiciousAssignmentsInspection
		 */
		$text = WPGlobus_Core::text_filter( $original_text, WPGlobus::Config()->language );

		if ( null === $more ) {
			$more = __( '&hellip;' );
		}

		$text = wp_strip_all_tags( $text );
		if ( 'characters' === _x( 'words', 'word count: words or characters?' ) && preg_match( '/^utf-?8$/i', get_option( 'blog_charset' ) ) ) {
			$text = trim( preg_replace( "/[\n\r\t ]+/", ' ', $text ), ' ' );
			preg_match_all( '/./u', $text, $words_array );
			$words_array = array_slice( $words_array[0], 0, $num_words + 1 );
			$sep         = '';
		} else {
			$words_array = preg_split( "/[\n\r\t ]+/", $text, $num_words + 1, PREG_SPLIT_NO_EMPTY );
			$sep         = ' ';
		}
		if ( count( $words_array ) > $num_words ) {
			array_pop( $words_array );
			$text = implode( $sep, $words_array );
			$text = $text . $more;
		} else {
			$text = implode( $sep, $words_array );
		}

		return $text;
	}

	/**
	 * Register the WPGlobus widgets
	 *
	 * @wp-hook widgets_init
	 * @since   1.0.7
	 */
	public static function register_widgets() {
		register_widget( 'WPGlobusWidget' );
	}

	/**
	 * Do something on admin_init hook.
	 *
	 * @todo Note: runs on admin-ajax and admin-post, too
	 */
	public static function action__admin_init() {
		/**
		 * Display blog name correctly on the WooThemes Helper page
		 * wp-admin/index.php?page=woothemes-helper
		 */
		if ( WPGlobus_WP::is_plugin_page( 'woothemes-helper' ) ) {
			add_filter( 'option_blogname', array( 'WPGlobus_Filters', 'filter__text' ), 0 );
		}
	}

	/**
	 * Specify meta keys where the meta data can be multilingual.
	 *
	 * @since   1.2.1
	 * @example
	 *          <code>
	 *          add_filter( 'wpglobus_multilingual_meta_keys',
	 *          function ( $multilingual_meta_keys ) {
	 *          $multilingual_meta_keys['slides'] = true;
	 *          return $multilingual_meta_keys;
	 *          }
	 *          );
	 *          </code>
	 */
	public static function set_multilingual_meta_keys() {

		/**
		 * Add Alternative Text meta value for media.
		 * We need to use only one meta because Title, Caption and Description was stored in wp_posts table.
		 *
		 * @since 1.9.11
		 * @todo  may be to use another class to store keys for $multilingual_meta_keys in future version.
		 */
		self::$multilingual_meta_keys['_wp_attachment_image_alt'] = true;

		/**
		 * Filter wpglobus_multilingual_meta_keys
		 *
		 * @since 1.9.11
		 */
		self::$multilingual_meta_keys = apply_filters(
			'wpglobus_multilingual_meta_keys', self::$multilingual_meta_keys
		);
	}

	/**
	 * Translate meta data
	 *
	 * @see WPGlobus_Filters::set_multilingual_meta_keys
	 *
	 * @param string|array $value     Null is passed. We set the value.
	 * @param int          $object_id Post ID
	 * @param string       $meta_key  Passed by the filter. We need only one key.
	 * @param string|array $single    Meta value, or an array of values.
	 *
	 * @return string|array
	 */
	public static function filter__postmeta( $value, $object_id, $meta_key, $single ) {

		/**
		 * Todo Currently, only single values are supported
		 */
		if ( ! $single ) {
			return $value;
		}

		/**
		 * Will process only if the `meta_key` is one of the explicitly set.
		 */
		if ( ! isset( self::$multilingual_meta_keys[ $meta_key ] ) ) {
			return $value;
		}

		/**
		 * May be called many times on one page. Let's cache.
		 */
		static $_cache;
		if ( isset( $_cache[ $meta_key ][ $object_id ] ) ) {
			return $_cache[ $meta_key ][ $object_id ];
		}

		global $wpdb;
		$meta_value = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = %s AND post_id = %d LIMIT 1;",
				$meta_key, $object_id
			)
		);

		if ( $meta_value ) {

			if ( is_serialized( $meta_value ) ) {
				/**
				 * Todo Refactor this. Write a `filter__array` method.
				 */
				$_meta_array = maybe_unserialize( $meta_value );
				foreach ( $_meta_array as &$_value ) {
					if ( is_array( $_value ) ) {
						foreach ( $_value as &$_deep_value ) {
							/**
							 * Todo Assuming that the array had max. two levels, which is wrong.
							 */
							$_deep_value = self::filter__text( $_deep_value );
						}
						unset( $_deep_value );
					} else {
						$_value = self::filter__text( $_value );
					}
				}
				unset( $_value );
				$value = $_meta_array;

				/**
				 * If single is requested, the following code is executed by
				 *
				 * @see get_metadata
				 * <code>
				 * if ( $single && is_array( $check ) )
				 *      return $check[0];
				 * </code>
				 * Therefore, we need to return the entire `$value` as the first element of
				 * an array.
				 */
				if ( $single ) {
					$value = array( $value );
				}
			} else {
				$value = self::filter__text( $meta_value );
			}
		}

		/**
		 * Save to cache, even if we did not do anything
		 */
		$_cache[ $meta_key ][ $object_id ] = $value;

		return $value;
	}

	/**
	 * Localize feed url
	 *
	 * @since 1.5.3
	 *
	 * @scope both (RSS are shown in admin dashboard "News" widgets).
	 *
	 * @param SimplePie $obj
	 */
	public static function fetch_feed_options( $obj ) {

		$need_to_localize = true;
		/**
		 * Filter to disable localize feed url.
		 *
		 * @since 1.5.3
		 *
		 * @param bool      $need_to_localize True is value by default.
		 * @param SimplePie $obj              The feed object.
		 *
		 * @return bool
		 */
		$need_to_localize = apply_filters( 'wpglobus_localize_feed_url', $need_to_localize, $obj );

		if ( ! empty( $obj->feed_url ) && $need_to_localize ) {
			$obj->feed_url = WPGlobus_Utils::localize_url( $obj->feed_url );
		}
	}

	/**
	 * Filter CSS rules for frontend.
	 *
	 * @since 1.6.6
	 *
	 * @scope front
	 *
	 * @param string $css
	 * @param string $css_editor
	 *
	 * @return string
	 */
	public static function filter__front_styles( $css, $css_editor ) {
		if ( ! empty( $css_editor ) ) {
			$css .= wp_strip_all_tags( $css_editor );
		}

		return $css;
	}

	/**
	 * De-localize URL to the default language so that @see url_to_postid() can
	 * determine the post ID.
	 *
	 * @since 1.8.4
	 *
	 * @param string $url The URL to derive the post ID from.
	 *
	 * @return string
	 */
	public static function filter__url_to_postid( $url ) {
		return WPGlobus_Utils::localize_url( $url, WPGlobus::Config()->default_language );
	}

	/**
	 * The post ID has been changed already by the @see filter__url_to_postid,
	 * so we do not need to modify it here.
	 * However, oembed does not know which language to use to fill in its $data
	 * from the post.
	 * Therefore, we use a workaround: extract the language from the URL and
	 * store it in a special variable, to use later in
	 *
	 * @see   filter__oembed_response_data.
	 *
	 * @since 1.8.4
	 *
	 * @param int    $post_id The post ID.
	 * @param string $url     The requested URL.
	 *
	 * @return int The post ID, unchanged.
	 */
	public static function filter__oembed_request_post_id( $post_id, $url ) {
		$language = WPGlobus_Utils::extract_language_from_url( $url );
		if ( WPGlobus::Config()->default_language !== $language ) {
			WPGlobus::Config()->setLanguageForOembed( $language );
		}

		return $post_id;
	}

	/**
	 * Filter the oembed data returned by the /wp-json/oembed/... calls.
	 *
	 * @since 1.8.4
	 *
	 * @param array $data The response data.
	 *
	 * @return array
	 */
	public static function filter__oembed_response_data( $data ) {
		// If $language_for_oembed is empty, text_filter will use the default language.
		$language_for_oembed = WPGlobus::Config()->getAndResetLanguageForOembed();
		foreach ( array( 'author_name', 'title' ) as $field ) {
			if ( ! empty( $data[ $field ] ) ) {
				$data[ $field ] = WPGlobus_Core::text_filter( $data[ $field ], $language_for_oembed );
			}
		}

		return $data;
	}

	/**
	 * Filters the wp_mail() arguments.
	 *
	 * See   wp-includes\pluggable.php
	 *
	 * @since 1.9.5
	 *
	 * @param array $atts A compacted array of wp_mail() arguments.
	 *
	 * @return array
	 */
	public static function filter__wp_mail( $atts ) {

		/**
		 * May be called many times. Let's cache.
		 */
		/*
		static $_cache;
		if ( isset( $_cache ) ) {
			return $_cache;
		} // */

		/**
		 * Array of enabled attributes to translate.
		 * Full array is 'to', 'subject', 'message', 'headers', 'attachments';
		 */
		$keys = array( 'subject', 'message', 'headers' );

		foreach ( $keys as $key ) :

			if ( empty( $atts[ $key ] ) ) {
				continue;
			}

			if ( 'message' === $key ) {
				$atts[ $key ] = str_replace( "\n", '[[wpg-newline]]', $atts[ $key ] );
			}

			$atts[ $key ] = WPGlobus_Core::extract_text( $atts[ $key ], WPGlobus::Config()->default_language );

			if ( 'message' === $key ) {
				$atts[ $key ] = str_replace( '[[wpg-newline]]', "\n", $atts[ $key ] );
			}

		endforeach;

		/**
		 * Save to cache.
		 * // $_cache = $atts;
		 */

		return $atts;
	}

	/**
	 * Filters oEmbed HTML.
	 *
	 * @since        1.9.8
	 * @noinspection PhpUnusedParameterInspection
	 *
	 * @param string $url     The attempted embed URL.
	 * @param array  $attr    An array of shortcode attributes.
	 * @param int    $post_ID Post ID.
	 *
	 * @param mixed  $cache   The cached HTML result, stored in post meta.
	 *
	 * @return string
	 */
	public static function filter__embed_oembed_html( $cache, $url, $attr, $post_ID ) {

		if ( ! is_string( $cache ) ) {
			/**
			 * We are working with string.
			 *
			 * @since 1.9.8
			 */
			return $cache;
		}

		$language = WPGlobus_Utils::extract_language_from_url( $url );

		if ( empty( $language ) ) {
			/**
			 * URL has no language code. So this is default language.
			 */
			return $cache;
		}

		return str_replace( WPGlobus_Utils::localize_url( $url, WPGlobus::Config()->default_language ), $url, $cache );
	}

	/**
	 * Filters a 'wpseo_taxonomy_meta' option before its value is updated.
	 *
	 * @since        2.0
	 *
	 * @param mixed  $new_value The new, unserialized option value.
	 * @param mixed  $old_value The old option value.
	 * @param string $option    Option name.
	 *
	 * @return mixed
	 * @noinspection PhpUnusedParameterInspection
	 */
	public static function filter__pre_update_wpseo_taxonomy_meta( $new_value, $old_value, $option ) {

		global $pagenow;

		if ( 'edit-tags.php' === $pagenow && 'editedtag' === WPGlobus_WP::get_http_post_parameter( 'action' ) ) {
			/**
			 * Update button was clicked on term.php page.
			 */
			$current_language = WPGlobus::Config()->builder->get_language();
			$taxonomy         = WPGlobus::Config()->builder->get( 'taxonomy' );
			$tag_ID           = (int) WPGlobus_WP::get_http_post_parameter( 'tag_ID' );

			$_enabled_keys = array( 'wpseo_title', 'wpseo_desc', 'wpseo_focuskw' );

			/**
			 * Get option.
			 */
			global $wpdb;
			$result = $wpdb->get_col( "SELECT option_value FROM $wpdb->options WHERE option_name = 'wpseo_taxonomy_meta'" );

			if ( ! empty( $result[0] ) ) {
				$option_values = maybe_unserialize( $result[0] );
			} else {
				$option_values = array();
			}

			foreach ( $_enabled_keys as $field ) {

				$new = array();

				foreach ( WPGlobus::Config()->enabled_languages as $lang ) :

					if ( $lang === $current_language ) {

						if ( ! empty( $new_value[ $taxonomy ][ $tag_ID ][ $field ] ) ) {
							$new[ $lang ] = $new_value[ $taxonomy ][ $tag_ID ][ $field ];
							// } else {
							//$text = '';
						}
					} else {

						if ( ! empty( $option_values[ $taxonomy ][ $tag_ID ][ $field ] ) ) {

							$_text = WPGlobus_Core::text_filter( $option_values[ $taxonomy ][ $tag_ID ][ $field ], $lang, WPGlobus::RETURN_EMPTY );
							if ( ! empty( $_text ) ) {
								$new[ $lang ] = $_text;
							}
						}
					}

				endforeach;

				$new_value[ $taxonomy ][ $tag_ID ][ $field ] = WPGlobus_Utils::build_multilingual_string( $new );

			} // endforeach
		}

		return $new_value;
	}

	/**
	 * Filters whether or not to use the block editor to manage widgets.
	 *
	 * @since 2.8.0
	 *
	 * @param bool $use_block_editor Whether or not to use the block editor to manage widgets.
	 */
	public static function filter__use_widgets_block_editor( $use_block_editor ) {

		if ( ! $use_block_editor ) {

			$theme_support_widgets_block_editor = get_theme_support( 'widgets-block-editor' );

			if ( ! $theme_support_widgets_block_editor ) {
				return $theme_support_widgets_block_editor;
			}
		}

		if ( ! empty( WPGlobus::Config()->use_widgets_block_editor ) && (bool) WPGlobus::Config()->use_widgets_block_editor ) {
			return $use_block_editor;
		}

		return false;
	}
}
