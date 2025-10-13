<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'd4p_sanitize_key_expanded' ) ) {
	function d4p_sanitize_key_expanded( $key ) {
		$key = strtolower( $key );

		return preg_replace( '/[^a-z0-9._\-]/', '', $key );
	}
}

if ( ! function_exists( 'd4p_sanitize_extended' ) ) {
	function d4p_sanitize_extended( $text, $tags = null, $protocols = array(), $strip_shortcodes = false ) : string {
		$tags = is_null( $tags ) ? wp_kses_allowed_html( 'post' ) : $tags;
		$text = stripslashes( $text );

		if ( $strip_shortcodes ) {
			$text = strip_shortcodes( $text );
		}

		return wp_kses( trim( $text ), $tags, $protocols );
	}
}

if ( ! function_exists( 'd4p_sanitize_basic' ) ) {
	function d4p_sanitize_basic( $text, $strip_shortcodes = true ) : string {
		$text = stripslashes( $text );

		if ( $strip_shortcodes ) {
			$text = strip_shortcodes( $text );
		}

		return trim( wp_kses( $text, array() ) );
	}
}

if ( ! function_exists( 'd4p_sanitize_html' ) ) {
	function d4p_sanitize_html( $text, $tags = null, $protocols = array() ) : string {
		$tags = is_null( $tags ) ? wp_kses_allowed_html( 'post' ) : $tags;

		return wp_kses( trim( stripslashes( $text ) ), $tags, $protocols );
	}
}

if ( ! function_exists( 'd4p_sanitize_slug' ) ) {
	function d4p_sanitize_slug( $text ) : string {
		return trim( sanitize_title_with_dashes( stripslashes( $text ) ), "-_ \t\n\r\0\x0B" );
	}
}

if ( ! function_exists( 'd4p_sanitize_html_classes' ) ) {
	function d4p_sanitize_html_classes( $classes ) : string {
		$list = is_array( $classes ) ? $classes : explode( ' ', trim( stripslashes( $classes ) ) );
		$list = array_map( 'sanitize_html_class', $list );

		return trim( join( ' ', $list ) );
	}
}

if ( ! function_exists( 'd4p_sanitize_basic_array' ) ) {
	function d4p_sanitize_basic_array( $input, $strip_shortcodes = true ) : array {
		$output = array();

		foreach ( $input as $key => $value ) {
			$output[ $key ] = d4p_sanitize_basic( $value, $strip_shortcodes );
		}

		return $output;
	}
}
