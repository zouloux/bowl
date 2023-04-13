<?php

// Do nothing on front app
if (!is_admin()) return null;

// Hide ACF admin
// FIXME : Remove access to this page for every user !
// FIXME : /wordpress/wp-admin/post-new.php?post_type=acf-field-group
//apply_filters('acf/settings/show_admin', false);
//add_filter('acf/settings/show_admin', '__return_false');
//apply_filters( "acf/settings/local", false );
//apply_filters( "acf/settings/json", false );
//apply_filters( "acf/settings/show_updates", false );
//apply_filters( "acf/settings/rest_api_enabled", false );
//add_action( 'acf/input/admin_head', function () {
//	echo "<style>.acf-postbox > .hndle .acf-hndle-cog { display: none !important; }</style>";
//});

// ----------------------------------------------------------------------------- STYLE

// Inject patched admin style
add_action('admin_head', function () {
	if (!defined('BOWL_PLUGIN_DIR')) return;
	$stylePath = BOWL_PLUGIN_DIR.'assets/admin-style.css';
	echo '<link rel="stylesheet" href="'.$stylePath.'" />';
});
add_action('admin_footer', function () {
	if (!defined('BOWL_PLUGIN_DIR')) return;
	$scriptPath = BOWL_PLUGIN_DIR.'assets/admin-script.js';
	echo '<script src="'.$scriptPath.'"></script>';
});

// Inject admin custom assets
if ( defined('BOWL_ADMIN_LOAD_CUSTOM_ASSETS') && BOWL_ADMIN_LOAD_CUSTOM_ASSETS ) {
	add_action('admin_head', function () {
		$stylePath = get_template_directory_uri().'/assets/admin.css';
		echo '<link rel="stylesheet" href="'.$stylePath.'" />';
	});
	add_action('admin_footer', function () {
		$scriptPath = get_template_directory_uri().'/assets/admin.js';
		echo '<script src="'.$scriptPath.'"></script>';
	});
}

// ----------------------------------------------------------------------------- NESTED PAGES

// Remove page attribute panel to disable nested pages (parenting)
if ( defined('BOWL_DISABLE_NESTED_PAGES') && BOWL_DISABLE_NESTED_PAGES ) {
	//	add_action( 'init', function () {
	//		remove_post_type_support('page','page-attributes');
	//	});
	add_action( 'admin_init', function () {
		$style = "#pageparentdiv .inside .parent-id-label-wrapper, #parent_id { display: none; }";
		bowl_inject_custom_admin_resource_for_screen(null, $style, "");
	});
}

// ----------------------------------------------------------------------------- DISABLE BLOG

// Disable blog feature
if ( defined('BOWL_DISABLE_NEWS') && BOWL_DISABLE_NEWS ) {

	// FIXME : Does not work any more ? it will remove post and page
	//	add_action('admin_menu', function () {
	//		remove_menu_page('edit.php');
	//		global $menu;
	//		dd($menu);
	//	});
	// FIXME : So remove only with CSS ...
	add_action( 'admin_init', function () {
		$style = "#menu-posts { display: none; }";
		bowl_inject_custom_admin_resource_for_screen(null, $style, "");
	});
	add_action('wp_before_admin_bar_render', function () {
		global $wp_admin_bar;
		$wp_admin_bar->remove_menu('new-post');
	});
	add_action('wp_dashboard_setup', function () {
		global $wp_meta_boxes;
		unset($wp_meta_boxes[ 'dashboard' ][ 'side' ][ 'core' ][ 'dashboard_quick_press' ]);
		unset($wp_meta_boxes[ 'dashboard' ][ 'normal' ][ 'core' ][ 'dashboard_recent_comments' ]);
	});
}

// ----------------------------------------------------------------------------- DISABLE GUTENBERG

// Disable Gutenberg everywhere
if ( defined('BOWL_DISABLE_GUTENBERG') && BOWL_DISABLE_GUTENBERG ) {
	add_filter('use_block_editor_for_post', function () { return false; }, 10, 0 );
}

// ----------------------------------------------------------------------------- META BOXES

// Disable meta box draggable. Custom admin-script will add back open / close feature
if ( defined('BOWL_DISABLE_META_BOX_DRAGGABLE') && BOWL_DISABLE_META_BOX_DRAGGABLE ) {
	add_action( 'admin_init', function () {
		// Check if we are on an edit / create page in admin
		global $pagenow;
		if ( !in_array($pagenow, ['post-new.php', 'post.php', 'admin.php']) ) return;
		// Remove original drag and drop and open / close for all meta boxes
		$style = ".postbox .handle-order-higher, .postbox .handle-order-lower { display: none }\n";
		//$style .= ".postbox .postbox-header .handlediv { display: none; }\n";
		$style .= ".postbox .postbox-header .hndle { pointer-events: none; }\n";
		// IMPORTANT NOTE : Do not use this, it will prevent usage of all "edit" buttons on admin !
		// wp_deregister_script('postbox');
		$script = "window._customMetaboxBehavior = true;";
		// IMPORTANT NOTE : Do not remove this class, ACF will crashes when changing template in admin
		// Remove hndle class will disable draggable on meta boxes
		//$script = "jQuery(document).ready(function (\$) {\$('.postbox .postbox-header .hndle').removeClass('hndle');});";
		bowl_inject_custom_admin_resource_for_screen(null, $style, $script);
	});
}

// Add main image meta box on articles
if ( defined('BOWL_ADD_IMAGE_META_BOX') && BOWL_ADD_IMAGE_META_BOX ) {
	add_action( 'current_screen', function () {
		$screen = get_current_screen();
		if (isset($screen->id) && $screen->id === 'post')
			add_theme_support( 'post-thumbnails' );
	});
}

// Clean meta box on sidebar
add_action('add_meta_boxes', function () {
	global $wp_meta_boxes;
	//dump($wp_meta_boxes);exit;
	foreach ( $wp_meta_boxes as $key => $value ) {
		// Move excerpt on size
		if ( defined('BOWL_EXCERPT_META_BOX_ON_SIDE') && BOWL_EXCERPT_META_BOX_ON_SIDE ) {
			if ( isset($wp_meta_boxes[ $key ]['normal']['core']['postexcerpt']) ) {
				$wp_meta_boxes[ $key ]['side']['core']['postexcerpt'] = $wp_meta_boxes[ $key ]['normal']['core']['postexcerpt'];
				unset($wp_meta_boxes[ $key ]['normal']['core']['postexcerpt']);
			}
		}
		// Move author meta box on side
		if ( defined('BOWL_AUTHOR_META_BOX_ON_SIDE') && BOWL_AUTHOR_META_BOX_ON_SIDE ) {
			if ( isset($wp_meta_boxes[ $key ]['normal']['core']['authordiv']) ) {
				$wp_meta_boxes[ $key ]['side']['core']['authordiv'] = $wp_meta_boxes[ $key ]['normal']['core']['authordiv'];
				unset($wp_meta_boxes[ $key ]['normal']['core']['authordiv']);
			}
		}
		// Remove tags box
		if ( defined('BOWL_DISABLE_TAGS') && BOWL_DISABLE_TAGS )
			unset($wp_meta_boxes[ $key ]['side']['core']['tagsdiv-post_tag']);
		// Remove slug box
		if ( defined('BOWL_DISABLE_SLUG') && BOWL_DISABLE_SLUG )
			unset($wp_meta_boxes[ $key ]['normal']['core']['slugdiv']);
	}
}, 0);

// ----------------------------------------------------------------------------- TINY MCE EDITOR

add_filter( 'mce_buttons', function ($buttons) {
	return (
	( defined('BOWL_MCE_BUTTONS') && isset(BOWL_MCE_BUTTONS[0]) )
		? BOWL_MCE_BUTTONS[0] : $buttons
	);
});
add_filter( 'mce_buttons_2', function ($buttons) {
	return (
	( defined('BOWL_MCE_BUTTONS') && isset(BOWL_MCE_BUTTONS[1]) )
		? BOWL_MCE_BUTTONS[1] : $buttons
	);
});

// Generate style formats from config
add_filter('tiny_mce_before_init', function ( $init ) {
	// Declare new styles formats
	$mceStylesFormats = defined('BOWL_MCE_STYLES') ? BOWL_MCE_STYLES : [];
	$init['style_formats'] = wp_json_encode( $mceStylesFormats );
	// Init style rendering of those formats in TinyMCE
	if (!isset($init['content_style']))
		$init['content_style'] = '';
	// Browser formats
	foreach ( $mceStylesFormats as $format ) {
		// Generate style for TinyMce
		$computedStyle = '';
		foreach ( $format['style'] as $key => $value )
			$computedStyle .= $key.': '.$value.'; ';
		$init['content_style'] .= " .".$format['classes']." {".$computedStyle."} ";
	}
	return $init;
});

add_action( 'customize_register', function ( $wp_customize ) {
	if ( defined('BOWL_REMOVE_THEME_CUSTOMIZE_SECTIONS') && is_array(BOWL_REMOVE_THEME_CUSTOMIZE_SECTIONS) )
		foreach ( BOWL_REMOVE_THEME_CUSTOMIZE_SECTIONS as $sectionName )
			$wp_customize->remove_section( $sectionName );
}, 30);


function bowl_clear_nano_cache () {
	if ( class_exists("\Nano\core\Nano") )
		\Nano\core\Nano::cacheClear();
}

// Clear Nano APCU cache on post and options saving
add_action( 'save_post', "bowl_clear_nano_cache", 0);
add_action( 'updated_option', "bowl_clear_nano_cache", 0);

// Add a class to offset screen-meta if we have multilang plugin
add_filter("admin_body_class", function ($classes) {
	if ( function_exists('wpm_get_language') )
		$classes .= " has-wpm-plugin";
	return $classes;
});

// ----------------------------------------------------------------------------- TOP BUTTONS

if ( defined('BOWL_ADMIN_ANALYTICS_BUTTON') ) {
	add_action('admin_bar_menu', function ($adminBar) {
		$href = call_user_func(BOWL_ADMIN_ANALYTICS_BUTTON);
		if ( $href === false )
			return;
		$adminBar->add_node([
			'id' => 'open-analytics',
			'title' => 'Open Analytics',
			'href' => $href,
			'meta' => [ 'class' => 'open-analytics-top-button' ]
		]);
	}, 40);
}

if ( defined('BOWL_ADMIN_CLEAR_CACHE_BUTTON') ) {
	$_bowlClearCacheParam = "clear-bowl-cache";
	add_action('admin_bar_menu', function ( $adminBar ) use ( $_bowlClearCacheParam ) {
		$adminBar->add_node([
			'id' => 'clear-cache',
			'title' => 'Clear cache',
			'meta' => [
				'class' => 'clear-cache-top-button',
				'onclick' => implode("", [
					'if (!confirm("Are you sure ?")) return false;',
					'event.preventDefault();',
					'fetch(location.pathname + "?'.$_bowlClearCacheParam.'=1")',
					'.then( async r => alert(await r.text()));',
				])
			]
		]);
	}, 50);

	if ( isset($_GET[$_bowlClearCacheParam]) && $_GET[$_bowlClearCacheParam] === "1" ) {
		if ( is_admin() ) {
			if ( BOWL_ADMIN_CLEAR_CACHE_BUTTON === true )
				bowl_clear_nano_cache();
			else
				call_user_func( BOWL_ADMIN_CLEAR_CACHE_BUTTON );
			echo "Done";
			exit;
		}
	}
}
