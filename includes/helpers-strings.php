<?php

function branded_auto_updates_for_mainwp_strip_html_and_contents( $html ) {
	$string = $html;
	$string = preg_replace('/<[^>]*>[^<]*<[^>]*>/', '', $string);
	$string = preg_replace('/^ - /', '', $string);

	return $string;
}