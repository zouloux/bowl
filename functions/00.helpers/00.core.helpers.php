<?php

function bowl_get_base ():string {
	return rtrim(env('WP_URL') ?? "", '/') . '/';
}

// FIXME : Does not work well with local added two times depending on WPM config
function bowl_get_current_href ():string {
	global $wp;
	return home_url( $wp->request ?? $_SERVER['REQUEST_URI'] );
}

function bowl_remove_scheme_from_href ( $href ) {
	return (
		stripos($href, '://') === false ? $href
		: substr($href, stripos($href, '://') + 3, strlen($href))
	);
}

function bowl_remove_base_from_href ( $baseHref ) {
	$href = bowl_remove_scheme_from_href( $baseHref );
	$base = bowl_remove_scheme_from_href( bowl_get_base() );
	if ( stripos($href, $base) !== false )
		return substr($href, stripos($href, '/', strlen($base)));
	else
		return $baseHref;
}

function bowl_keep_host_from_href ( $href ) {
	if ( stripos($href, '://') === false )
		return $href;
	$split = explode("/", $href, 4);
	if ( count($split) >= 4 )
		array_pop($split);
	return implode("/", $split);
}