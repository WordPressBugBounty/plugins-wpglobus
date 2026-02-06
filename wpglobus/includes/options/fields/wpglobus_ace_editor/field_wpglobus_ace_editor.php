<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WPGlobusOptions_wpglobus_ace_editor' ) ) :

	/**
	 * Class WPGlobusOptions_wpglobus_ace_editor
	 */
	class WPGlobusOptions_wpglobus_ace_editor {


		/**
		 * WPGlobusOptions_wpglobus_ace_editor constructor.
		 *
		 * @param array $field Field attributes.
		 */
		public function __construct( $field ) {

			$this->render( $field );
		}

		/**
		 * Render the field.
		 *
		 * @param array $field Field attributes.
		 */
		public function render( $field ) {
			?>

			<div class="wpglobus-options-field wpglobus-options-field-wpglobus_ace_editor">

				<div class="grid__item">
					<?php if ( ! empty( $field['title'] ) ) { ?>
						<p class="title">
							<?php echo esc_html( $field['title'] ); ?>
						</p>
					<?php } ?>
					<?php if ( ! empty( $field['subtitle'] ) ) { ?>
						<p class="subtitle"><?php echo esc_html( $field['subtitle'] ); ?></p>
					<?php } ?>
				</div>
				<div class="grid__item">
					<div id="wpglobus-options-<?php echo esc_attr( $field['id'] ); ?>">
						<?php echo esc_html( $field['value'] ); ?>
					</div>
					<input type="hidden" id="wpglobus-options-<?php echo esc_attr( $field['id'] ); ?>_control"
							name="<?php echo esc_attr( $field['name'] ); ?>" value=""/>
					<?php if ( ! empty( $field['desc'] ) ) : ?>
						<p class="description"><?php echo esc_html( $field['desc'] ); ?></p>
					<?php endif; ?>
				</div>
			</div>
			<?php

			/**
			 * https://ace.c9.io/
			 * https://cdn.jsdelivr.net/npm/ace-builds@1.43.6/css/ace.min.css
			 *
			 * https://raw.githubusercontent.com/ajaxorg/ace-builds/refs/heads/master/src-min-noconflict/ace.js
			 * https://raw.githubusercontent.com/ajaxorg/ace-builds/refs/heads/master/src-min-noconflict/mode-javascript.js
			 * https://raw.githubusercontent.com/ajaxorg/ace-builds/refs/heads/master/src-min-noconflict/worker-javascript.js
			 * https://raw.githubusercontent.com/ajaxorg/ace-builds/refs/heads/master/src-min-noconflict/mode-css.js
			 * https://raw.githubusercontent.com/ajaxorg/ace-builds/refs/heads/master/src-min-noconflict/worker-css.js
			 *
			 * https://github.com/beautifier/js-beautify
			 *
			 * https://cdnjs.cloudflare.com/ajax/libs/js-beautify/1.15.4/beautify.min.js
			 * https://cdnjs.cloudflare.com/ajax/libs/js-beautify/1.15.4/beautify-css.min.js
			 */

			$_url_lib = WPGlobus::plugin_dir_url() . 'lib';

			$_to_enqueue = array(
					'ace-editor' => array(
							'src'     => $_url_lib . '/ace/ace.js',
							'version' => '1.43.6',
					),
					'beautify'   => array(
							'src'     => $_url_lib . '/js-beautify/beautify.min.js',
							'version' => '1.15.4',
					),
					'beautify-css'   => array(
							'src'     => $_url_lib . '/js-beautify/beautify-css.min.js',
							'version' => '1.15.4',
					),
			);

			foreach ( $_to_enqueue as $handle => $data ) {
				if ( ! wp_script_is( $handle ) ) {
					wp_enqueue_script(
							$handle,
							$data['src'],
							array(),
							$data['version'],
							true
					);
				}
			}

			// @formatter:off
			?>
			<script>
				jQuery(function ($) {

					const mode = "<?php echo esc_attr( $field['mode'] ); ?>";
					const divEditor = "wpglobus-options-<?php echo esc_attr( $field['id'] ); ?>";
					const editor = ace.edit(divEditor,
						{
							mode: `ace/mode/${mode}`,
							minLines: 20,
							maxLines: 20,
							tabSize: 2,
							showPrintMargin: false
						});

					const beautify = 'css' === mode ? css_beautify : js_beautify;

					editor.session.setValue(beautify(editor.getValue(),
						{
						"indent_size": "2",
						"indent_char": " ",
						"max_preserve_newlines": "2",
						"preserve_newlines": true,
						"keep_array_indentation": false,
						"break_chained_methods": false,
						"indent_scripts": "normal",
						"brace_style": "collapse",
						"space_before_conditional": true,
						"unescape_strings": false,
						"jslint_happy": false,
						"end_with_newline": false,
						"wrap_line_length": "0",
						"indent_inner_html": false,
						"comma_first": false,
						"e4x": false,
						"indent_empty_lines": false
					}));

					$("#form-wpglobus-options").on("submit", function () {
						const elEditorControl = "wpglobus-options-<?php echo esc_attr( $field['id'] ); ?>_control";
						document.getElementById(elEditorControl)
							.value = editor.getValue().replace(/[\s]+/g, " ");
					});

				})
			</script>
			<?php
			// @formatter:on
		}
	}

endif;

/**
 * Go
 *
 * @see WPGlobus_Options::page_options
 * @global array $field
 */
new WPGlobusOptions_wpglobus_ace_editor( $field );
