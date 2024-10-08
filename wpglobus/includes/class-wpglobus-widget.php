<?php
/**
 * Widget
 *
 * @since   1.0.7
 * @package WPGlobus
 */

/**
 * Class WPGlobusWidget
 */
class WPGlobusWidget extends WP_Widget {

	/**
	 * Array types of switcher
	 *
	 * @since  1.0.7
	 * @var array
	 */
	private $types = array();

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct(
			'wpglobus',
			esc_html__( 'WPGlobus widget', 'wpglobus' ),
			array(
				'description' => esc_html__( 'Add language switcher', 'wpglobus' ),
			)
		);
		$this->types['flags']               = esc_html__( 'Flags', 'wpglobus' );
		$this->types['list']                = esc_html__( 'List', 'wpglobus' );
		$this->types['list_with_flags']     = esc_html__( 'List with flags', 'wpglobus' );
		$this->types['select']              = esc_html__( 'Select', 'wpglobus' );
		$this->types['select_with_code']    = esc_html__( 'Select with language code', 'wpglobus' );
		$this->types['dropdown']            = esc_html__( 'Dropdown', 'wpglobus' );
		$this->types['dropdown_with_flags'] = esc_html__( 'Dropdown with flags', 'wpglobus' );
	}

	/**
	 * Echo the widget content.
	 *
	 * @param array $args     Display arguments including before_title, after_title, before_widget, and after_widget.
	 * @param array $instance The settings for the particular instance of the widget
	 */
	public function widget( $args, $instance ) {

		$type = ! empty( $instance['type'] ) ? $instance['type'] : 'flags';

		/**
		 * Update
		 *
		 * @since 2.8.4
		 */
		$title = ! empty( $instance['title'] ) ? $instance['title'] : 'Language switcher';

		$inside = '';

		$enabled_languages = WPGlobus::Config()->enabled_languages;

		switch ( $type ) :
			case 'list':
				$code = '<div class="list">{{inside}}</div>';
				break;
			case 'list_with_flags':
				$code = '<div class="list flags">{{inside}}</div>';
				break;
			case 'select':
			case 'select_with_code':
				$code =
					'<div class="select-styled"><select onchange="document.location.href = this.value;">{{inside}}</select></div>';
				break;
			case 'dropdown':
			case 'dropdown_with_flags':
				/**
				 * Todo remove after testing.
				 *
				 * @since 1.6.9
				 */
				//$sorted[] = WPGlobus::Config()->language;
				//foreach ( $enabled_languages as $language ) {
				//if ( $language != WPGlobus::Config()->language ) {
				//$sorted[] = $language;
				//}
				//}
				//$enabled_languages = $sorted;

				$code = '<div class="dropdown-styled"> <ul>
					  <li>
						{{language}}
						<ul>
							{{inside}}
						</ul>
					  </li>
					</ul></div>';
				break;
			default:
				//	This is case 'flags'. Having it as default makes $code always set.
				$code = '<div class="flags-styled">{{inside}}</div>';
				break;
		endswitch;

		$extra_languages = array_diff( $enabled_languages, (array) WPGlobus::Config()->language );

		/**
		 * Filter extra languages.
		 *
		 * Returning array.
		 *
		 * @since 1.0.13
		 * @since 1.6.9
		 *
		 * @param array $extra_languages An array with extra languages to show off in menu.
		 * @param string    WPGlobus::Config()->language The current language.
		 */
		$extra_languages = apply_filters( 'wpglobus_extra_languages', $extra_languages, WPGlobus::Config()->language );

		/**
		 * Filter extra languages.
		 *
		 * Returning array.
		 *
		 * @since 1.9.17
		 *
		 * @param array $extra_languages An array with extra languages to show off in menu.
		 * @param string    WPGlobus::Config()->language The current language.
		 */
		$extra_languages = apply_filters( 'wpglobus/widget/extra_languages', $extra_languages, WPGlobus::Config()->language );

		$enabled_languages = array_merge( (array) WPGlobus::Config()->language, $extra_languages );

		/**
		 * Filter enabled languages.
		 *
		 * Returning array.
		 *
		 * @since 1.9.17
		 *
		 * @param array $enabled_languages An array with enabled languages to show off in menu.
		 * @param string    WPGlobus::Config()->language The current language.
		 */
		$enabled_languages = apply_filters( 'wpglobus/widget/enabled_languages', $enabled_languages, WPGlobus::Config()->language );

		/**
		 * CSS class for link in a and option tags. Used for adding hash.
		 *
		 * @since 1.2.0
		 */
		$link_classes['selector_link'] = 'wpglobus-selector-link';

		/**
		 * CSS class for flag box
		 *
		 * @since 1.4.0
		 */
		$flag_classes = array();

		/**
		 * This filter is documented in wp-includes/widgets/class-wp-widget-pages.php
		 *
		 * @since 2.8.4
		 */
		$title = apply_filters( 'widget_title', $title, $instance, $this->id_base );

		echo wp_kses_post( $args['before_widget'] );
		if ( ! empty( $title ) ) {
			echo wp_kses_post( $args['before_title'] . $title . $args['after_title'] );
		}
		foreach ( $enabled_languages as $language ) :

			$selected = '';

			/**
			 * Init current language class
			 */
			$link_classes['current_language'] = '';

			/**
			 * Init current language class for flag box
			 */
			$flag_classes['current_language'] = '';

			if ( WPGlobus::Config()->language === $language ) {
				$selected = ' selected';
				switch ( $type ) :
					case 'flags':
						$flag_classes['current_language'] = 'wpglobus-current-language';
						break;
					case 'list':
					case 'list_with_flags':
					case 'dropdown':
					case 'dropdown_with_flags':
						$link_classes['current_language'] = 'wpglobus-current-language';
						break;
				endswitch;
			}

			$url = WPGlobus_Utils::localize_current_url( $language );

			$flag = WPGlobus::Config()->flags_url . WPGlobus::Config()->flag[ $language ];

			switch ( $type ) :
				case 'flags':
					$inside .= '<span class="flag ' . implode( ' ', $flag_classes ) . '">';
					$inside .= '<a href="' . $url . '" class="' . implode( ' ', $link_classes ) . '"><img alt="" src="' . $flag . '"/></a>';
					$inside .= '</span>';
					break;
				case 'list':
				case 'list_with_flags':
					$inside .= '<a href="' . $url . '" class="' . implode( ' ', $link_classes ) . '">' .
							   '<img src="' . $flag . '" alt=""/>' .
							   ' ' .
							   '<span class="name">' .
							   WPGlobus::Config()->language_name[ $language ] .
							   '</span>' .
							   ' ' .
							   '<span class="code">' . strtoupper( $language ) . '</span>' .
							   '</a>';
					break;
				case 'select':
					$inside .= '<option class="' . implode( ' ', $link_classes ) . '" ' . $selected . ' value="' . $url . '">' . WPGlobus::Config()->language_name[ $language ] . '</option>';
					break;
				case 'select_with_code':
					$inside .= '<option class="' . implode( ' ', $link_classes ) . '" ' . $selected . ' value="' . $url . '">' . WPGlobus::Config()->language_name[ $language ] . '&nbsp;(' . strtoupper( $language ) . ')</option>';
					break;
				case 'dropdown':
					if ( '' !== $selected ) {
						$code =
							str_replace( '{{language}}', '<a class="' . implode( ' ', $link_classes ) . '" href="' . $url . '">' . WPGlobus::Config()->language_name[ $language ] . '&nbsp;(' . strtoupper( $language ) . ')</a>', $code );
					} else {
						$inside .= '<li><a class="' . implode( ' ', $link_classes ) . '" href="' . $url . '">' . WPGlobus::Config()->language_name[ $language ] . '&nbsp;(' . strtoupper( $language ) . ')</a></li>';
					}
					break;
				case 'dropdown_with_flags':
					if ( '' !== $selected ) {
						$code =
							str_replace( '{{language}}', '<a class="' . implode( ' ', $link_classes ) . '" href="' . $url . '"><img alt="" src="' . $flag . '"/>&nbsp;&nbsp;' . WPGlobus::Config()->language_name[ $language ] . '</a>', $code );
					} else {
						$inside .= '<li><a class="' . implode( ' ', $link_classes ) . '" href="' . $url . '"><img alt="" src="' . $flag . '"/>&nbsp;&nbsp;' . WPGlobus::Config()->language_name[ $language ] . '</a></li>';
					}
					break;
			endswitch;

		endforeach;

		echo wp_kses_post( str_replace( '{{inside}}', $inside, $code ) );

		echo wp_kses_post( $args['after_widget'] );
	}

	/**
	 * Echo the settings update form.
	 *
	 * @param array $instance Current settings
	 *
	 * @return string
	 */
	public function form( $instance ) {

		if ( isset( $instance['type'] ) ) {
			$selected_type = $instance['type'];
		} else {
			$selected_type = 'flags';
		}
		if ( empty( $instance['title'] ) ) {
			$instance['title'] = '';
		}
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_name( 'type' ) ); ?>"><?php echo esc_html__( 'Title' ); ?></label>
			<!--suppress HtmlFormInputWithoutLabel -->
			<input type="text" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
					name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
					value="<?php echo esc_html( $instance['title'] ); ?>"/>
		</p>
		<p><?php esc_html_e( 'Selector type', 'wpglobus' ); ?></p>
		<p>
			<?php foreach ( $this->types as $type => $caption ) : ?>
				<!--suppress HtmlFormInputWithoutLabel -->
				<input type="radio"
						id="<?php echo esc_attr( $this->get_field_id( 'type' ) . '-' . $type ); ?>"
						name="<?php echo esc_attr( $this->get_field_name( 'type' ) ); ?>"
					<?php checked( $type, $selected_type ); ?>
						value="<?php echo esc_attr( $type ); ?>"/>
				<?php
				echo esc_html( $caption ) . '<br />';
			endforeach;
			?>
		</p>
		<?php

		return '';
	}

	/**
	 * Update a particular instance.
	 * This function should check that $new_instance is set correctly.
	 * The newly calculated value of $instance should be returned.
	 * If "false" is returned, the instance won't be saved/updated.
	 *
	 * @param array $new_instance New settings for this instance as input by the user via form()
	 * @param array $old_instance Old settings for this instance
	 *
	 * @return array Settings to save or bool false to cancel saving
	 */
	public function update( $new_instance, $old_instance ) {
		$instance          = array();
		$instance['type']  = ( ! empty( $new_instance['type'] ) ) ? $new_instance['type'] : '';
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? $new_instance['title'] : '';

		return $instance;
	}
}
