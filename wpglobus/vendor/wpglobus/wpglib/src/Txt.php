<?php
/**
 * Copyright (c) 2026. TIV.NET INC. All Rights Reserved.
 *
 * @noinspection PhpUnused
 */

namespace WPGLIB;

/**
 * Class WPGLIB\Txt
 *
 * @since 1.0.0
 */
class Txt {
	protected static $txt = array();

	public static function t( string $key ): string {
		if ( empty( self::$txt ) ) {
			self::load();
		}

		return self::$txt[ $key ] ?? $key;
	}

	public static function t_html( string $key ): string {
		return \esc_html( self::t( $key ) );
	}

	public static function t_html_e( string $key ): void {
		echo self::t_html( $key );
	}

	protected static function load(): void {
		self::$txt = array(
			'Customize'                           => \__( 'Customize' ),
			'Draft saved at %s.'                  => \__( 'Draft saved at %s.' ),
			'Email'                               => \__( 'Email' ),
			'Error while saving.'                 => \__( 'Error while saving.' ),
			'Featured Images'                     => \__( 'Featured Images' ),
			'g:i:s a'                             => \__( 'g:i:s a' ),
			'Invalid taxonomy.'                   => \__( 'Invalid taxonomy.' ),
			'Last edited by %1$s on %2$s at %3$s' => \__( 'Last edited by %1$s on %2$s at %3$s' ),
			'Last edited on %1$s at %2$s'         => \__( 'Last edited on %1$s at %2$s' ),
			'Menus'                               => \__( 'Menus' ),
			'Name'                                => \__( 'Name' ),
			'Save Changes'                        => \__( 'Save Changes' ),
			'Settings'                            => \__( 'Settings' ),
			'Submit'                              => \__( 'Submit' ),
			'Title'                               => \__( 'Title' ),
			'Word count: %s'                      => \__( 'Word count: %s' ),
			'You are customizing %s'              => \__( 'You are customizing %s' ),
		);
	}

	public static function test(): void {

		// Load admin translations also.
		$locale = get_locale();
		load_textdomain( 'default', WP_LANG_DIR . "/admin-$locale.mo", $locale );

		self::load();
		foreach ( self::$txt as $k => $v ) {
			echo esc_html( $k ) . ' -- ' . self::t_html( $v ) . '<br>';
		}
	}
}
