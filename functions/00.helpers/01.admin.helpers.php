<?php

// Do nothing on front app
if (!is_admin()) return null;

// ----------------------------------------------------------------------------- MESSAGES

/**
 * Show a fatal error message on admin.
 * @param $message
 */
function bowl_show_admin_error_message ( $message ) {
	$html = <<<HTML
        <div style="display: flex; align-items: center; justify-content: center; height: 100%;">
            <p style="flex-grow:1; background: #eee;padding: 1% 3%;max-width: 500px;border-radius: 10px;text-align: center;font-family: Arial;">$message</p>
        </div>
HTML;
	die( $html );
}

// ----------------------------------------------------------------------------- RESOURCES

// Inject custom CSS / JS for a screen ID (page / options / etc ...)
function bowl_inject_custom_admin_resource_for_screen ( $screenID, $style = null, $script = null) {
	add_action('admin_head', function () use ($screenID, $style, $script) {
		$screen = get_current_screen();
		if ( !is_null($screenID) && $screen->id != $screenID ) return;
		if ( !is_null($style) )   echo '<style>'.$style.'</style>';
		if ( !is_null($script) )  echo '<script type="text/javascript">'.$script.'</script>';
	});
}

// ----------------------------------------------------------------------------- SCREEN PATCH

// Patch admin title for a specific screen
function bowl_patch_admin_custom_screen ( BowlFields $fields ) {
	$titleClass = "h1.wp-heading-inline";
	// If we have labels, this screen is a custom post type
	if ( isset($fields->labels) ) {
		// Set titles for add or update actions
		$titles = [
			"Ajouter ".$fields->labels[0],
			"Modifier ".$fields->labels[0]
		];
		// Inject script which will inject correct title and show it
		$script = <<<JS
            jQuery(function ($) {
                $('.post-new-php $titleClass').text("$titles[0]").css({ opacity: 1 });
                $('.post-php $titleClass').text("$titles[1]").css({ opacity: 1 });
            });
JS;
		bowl_inject_custom_admin_resource_for_screen( $fields['id'], null, $script );
	}
	// Show title
	else
		bowl_inject_custom_admin_resource_for_screen( null, "${titleClass} { opacity: 1; } ");
}

// ----------------------------------------------------------------------------- EDITOR & EXCERPT

function bowl_remove_field_for_post ( $postType, $field = 'editor', $postID = null ) {
	add_filter( 'admin_head', function () use ($postType, $postID, $field) {
		global $post;
		if ( is_null($post) ) return;
		if ( !is_null($postID) && $post->ID != $postID ) return;
		if ( $postType != $post->post_type ) return;
		remove_post_type_support( $postType, $field );
	});
}