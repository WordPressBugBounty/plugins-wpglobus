<?php
/**
 * File: debug-info-theme
 *
 * @package WPGlobus/Options
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$debug_info_theme = '';

$theme = wp_get_theme();

$theme_caption = 'Active theme';
$params        = array(
	'Name',
	'ThemeURI',
	'Description',
	'Author',
	'AuthorURI',
	'Version',
	'Template',
	#'Status',
	#'Tags',
	'TextDomain',
	#'DomainPath'
);

/**
 * Initialize here so it's not undefined below.
 *
 * @since 3.0.0
 */
$parent_theme_caption = '';

$parent_template = $theme->get( 'Template' );
if ( ! empty( $parent_template ) ) {
	$parent_theme         = wp_get_theme( get_template() );
	$parent_theme_caption = 'Parent theme';

	$theme_caption .= ' (child theme)';
}

/**
 * Check compatibility.
 *
 * @since 1.9.14 with WPML.
 */
$compatibility = array(
	'wpml' => array(
		'caption'   => 'Config file',
		'file_name' => 'wpml-config.xml',
		'file'      => '',
		'compat'    => false,
	),
);

/**
 * Class
 *
 * @since 2.2.32.
 */
$theme_info_spec_class = 'hidden';
if ( WPGlobus_WP::get_http_get_parameter( 'config-file' ) ) {
	$theme_info_spec_class = '';
}

/**
 * Theme compatibility.
 */
$_compats = $compatibility;

$theme_compats = array();

foreach ( $compatibility as $_id => $_value ) {

	$_file = get_stylesheet_directory() . '/' . $_value['file_name'];

	if ( file_exists( $_file ) ) {
		$_compats[ $_id ]['compat'] = true;
		$_compats[ $_id ]['file']   = $_file;
		$_compats[ $_id ]['link']   = add_query_arg( array(
			'file'  => $_value['file_name'],
			'theme' => $theme->get_stylesheet(),
		), admin_url( 'theme-editor.php' ) );
	}
}

$theme_compats = $_compats;

/**
 * Parent theme compatibility.
 */
if ( ! empty( $parent_template ) ) {

	$_compats = $compatibility;

	$parent_compats = array();

	foreach ( $compatibility as $_id => $_value ) {

		$_file = get_template_directory() . '/' . $_value['file_name'];

		if ( file_exists( $_file ) ) {
			$_compats[ $_id ]['compat'] = true;
			$_compats[ $_id ]['file']   = $_file;
			$_compats[ $_id ]['link']   = add_query_arg( array(
				'file'  => $_value['file_name'],
				'theme' => $parent_template,
			), admin_url( 'theme-editor.php' ) );
		}
	}

	$parent_compats = $_compats;
}

ob_start();
?>
	<a name="current-theme"></a>
	<table class="active_theme" cellspacing="10">
		<caption><h2><?php echo esc_html( $theme_caption ); ?></h2></caption>
		<thead>
		<tr>
			<th>Parameter</th>
			<th style="text-align: left;">Value</th>
		</tr>
		</thead>
		<tbody>
		<tr>
			<td><b>Theme option</b></td>
			<td><?php echo 'theme_mods_' . esc_html( get_stylesheet() ); ?></td>
		</tr>
		<?php foreach ( $params as $param ) : ?>
			<tr>
				<td><b><?php echo esc_html( $param ); ?></b></td>
				<td><?php echo esc_html( $theme->get( $param ) ); ?></td>
			</tr>
		<?php
		endforeach;
		if ( ! empty( $theme_compats ) ) :

			foreach ( $theme_compats as $_id => $_compat ) :
				?>
				<tr class="wpglobus-theme-info-spec <?php echo esc_attr( $theme_info_spec_class ); ?>">
					<td><b><?php echo esc_html( $_compat['caption'] ); ?></b></td>
					<td>
						<?php
						if ( $_compat['compat'] ) {
							echo '<span class="">' . esc_html( $_compat['file_name'] ) . '</span>';
							echo '&nbsp;&nbsp;&nbsp;<span class=""><a href="' . esc_url( $_compat['link'] ) . '" target="_blank">Open ' . esc_html( $_compat['file_name'] ) . '</a></span>';
						} else {
							echo 'none';
						}
						?>
					</td>
				</tr>
			<?php
			endforeach;

			if ( isset( $_compat['compat'] ) ) {
				$_filter = 'action="translate"';
				$buffer  = $this->config_file_filter( $_compat['file'], $_filter );
				if ( $buffer ) {
					foreach ( $buffer as $_id => $_value ) :
						?>
						<tr class="wpglobus-theme-info-spec hidden">
							<td><b><?php echo esc_html( $_filter ); ?></b></td>
							<td><?php echo esc_html( $_value ); ?></td>
						</tr>
					<?php
					endforeach;
				}
			}
		endif;
		?>
		</tbody>
	</table>
<?php
if ( ! empty( $parent_theme ) ) {
	/**
	 * Show parent theme.
	 */
	?>
	<table class="parent_theme" cellspacing="10">
		<caption><h2><?php echo esc_html( $parent_theme_caption ); ?></h2></caption>
		<thead>
		<tr>
			<th>Parameter</th>
			<th style="text-align: left;">Value</th>
		</tr>
		</thead>
		<tbody>
		<tr>
			<td><b>Theme option</b></td>
			<td><?php echo esc_html( 'theme_mods_' . get_template() ); ?></td>
		</tr>
		<?php foreach ( $params as $param ) : ?>
			<tr>
				<td><b><?php echo esc_html( $param ); ?></b></td>
				<td><?php echo esc_html( $parent_theme->get( $param ) ); ?></td>
			</tr>
		<?php
		endforeach;
		if ( ! empty( $parent_compats ) ) :

			foreach ( $parent_compats as $_id => $_compat ) :
				?>
				<tr class="wpglobus-theme-info-spec <?php echo esc_attr( $theme_info_spec_class ); ?>">
					<td><b><?php echo esc_html( $_compat['caption'] ); ?></b></td>
					<td>
						<?php
						if ( $_compat['compat'] ) {
							echo '<span class="">' . esc_html( $_compat['file_name'] ) . '</span>';
							echo '&nbsp;&nbsp;&nbsp;<span class=""><a href="' . esc_url( $_compat['link'] ) . '" target="_blank">Open ' . esc_html( $_compat['file_name'] ) . '</a></span>';
						} else {
							echo 'none';
						}
						?>
					</td>
				</tr>
			<?php
			endforeach;

			if ( isset( $_compat['compat'] ) ) {
				$_filter = 'action="translate"';
				$buffer  = $this->config_file_filter( $_compat['file'], $_filter );
				if ( $buffer ) {
					foreach ( $buffer as $_id => $_value ) :
						?>
						<tr class="wpglobus-theme-info-spec hidden">
							<td><b><?php echo esc_html($_filter); ?></b></td>
							<td><?php echo esc_html( $_value); ?></td>
						</tr>
					<?php
					endforeach;
				}
			}
		endif;
		?>
		</tbody>
	</table>
	<?php
}
$debug_info_theme = ob_get_clean();

return $debug_info_theme;
