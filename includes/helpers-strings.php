<?php

function baufm_strip_html_and_contents( $html ) {
	$string = $html;
	$string = preg_replace( '/<[^>]*>[^<]*<[^>]*>/', '', $string );
	$string = preg_replace( '/^ - /', '', $string );

	return $string;
}

function burgerfied_get_var( $var ) {
	if ( is_string( $var ) ) {
		return '';
	}

	return isset( $_GET[ $var ] ) ? wp_unslash( $_GET[ $var ] ) : '';
}
