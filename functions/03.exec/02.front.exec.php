<?php

// Do not continue on backend
if (is_admin()) return null;

// ----------------------------------------------------------------------------- WP HEAD FILTERING

// Remove all WP Junk
remove_action('wp_head', 'wlwmanifest_link');
remove_action('wp_head', 'rest_output_link_wp_head', 10);
remove_action('wp_head', 'rsd_link');
add_filter('the_generator', function () { return ''; });
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
add_action( 'wp_enqueue_scripts', function () {
	wp_dequeue_style( 'wp-block-library' );
	wp_dequeue_style( 'wp-block-library-theme' );
	wp_dequeue_style( 'wc-block-style' );
}, 100 );
remove_action( 'wp_head', 'wp_oembed_add_discovery_links');
add_action('wp_print_styles', function () {
	global $wp_styles;
	$wp_styles->queue = [];
}, 100);
add_filter( 'wpseo_debug_markers', '__return_false' );
remove_action( 'wp_head', 'wp_resource_hints', 2 );
remove_action('wp_head', 'wp_shortlink_wp_head', 10);
add_filter( 'rank_math/frontend/remove_credit_notice', '__return_true' );
add_theme_support( 'admin-bar', array( 'callback' => '__return_false' ) );

// ----------------------------------------------------------------------------- ROBOTS

add_filter( 'robots_txt', function ($output, $public) {
	$isPublic = $public && !env('BOWL_NO_ROBOTS');
	$base = bowl_get_base();
	return implode("\n",
		array_merge([
			"User-agent: *",
		], $isPublic ? [
			"Allow: *",
			"Sitemap: ${base}wp-sitemap.xml"
		] : [
			"Disallow: *",
		])
	);
}, 10, 2);

// ----------------------------------------------------------------------------- SITEMAP

// Disable tags in sitemap
if ( defined('BOWL_DISABLE_SITEMAP_TAGS') && BOWL_DISABLE_SITEMAP_TAGS ) {
	add_filter( 'wpseo_sitemap_exclude_taxonomy', function ($value, $taxonomy) {
		if ( $taxonomy == 'post_tag' ) return true;
	}, 10, 2 );
}

// Disable some post types in sitemap
// TODO : Refacto this to use new Filters ( if hidden, remove from here )
add_filter( 'wp_sitemaps_post_types', function ( $postTypes ) {
	global $_bowlSitemapRemovedPostTypes;
	foreach ( $postTypes as $key => $value ) {
		if ( !is_array($_bowlSitemapRemovedPostTypes) ) continue;
		if ( !in_array($key, $_bowlSitemapRemovedPostTypes) ) continue;
		unset( $postTypes[$key] );
	}
	return $postTypes;
});

// Remove authors from sitemap
if ( defined('BOWL_DISABLE_SITEMAP_USERS') && BOWL_DISABLE_SITEMAP_USERS ) {
	add_filter('wp_sitemaps_add_provider', function ($provider, $name) {
		return $name === 'users' ? false : $provider;
	}, 10, 2);
}