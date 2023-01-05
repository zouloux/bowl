<?php

use PHPMailer\PHPMailer\PHPMailer;

// ----------------------------------------------------------------------------- REMOVE OEMBED
// = emoji support in back and front-end

// https://kinsta.com/fr/base-de-connaissances/desactiver-embeds-wordpress/#disable-embeds-code
if ( defined('BOWL_DISABLE_OEMBED') && BOWL_DISABLE_OEMBED ) {
	add_filter('init', function () {
		remove_action( 'rest_api_init', 'wp_oembed_register_route' );
		add_filter( 'embed_oembed_discover', '__return_false' );
		remove_filter( 'oembed_dataparse', 'wp_filter_oembed_result', 10 );
		remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
		remove_action( 'wp_head', 'wp_oembed_add_host_js' );
		add_filter( 'tiny_mce_plugins', function ($plugins) {
			return array_diff($plugins, ['wpembed']);
		});
		add_filter( 'rewrite_rules_array', function ($rules) {
			foreach($rules as $rule => $rewrite)
				if( str_contains($rewrite, 'embed=true') )
					unset($rules[$rule]);
			return $rules;
		});
		remove_filter( 'pre_oembed_result', 'wp_filter_pre_oembed_result', 10 );
	}, '999');
}

// ----------------------------------------------------------------------------- PATCH ZLIB OBFLUSH

remove_action( 'shutdown', 'wp_ob_end_flush_all', 1 );
add_action( 'shutdown', function() {
	while ( @ob_end_flush() );
});

// ----------------------------------------------------------------------------- MAIL CONFIG

// Register SMTP email with HTML support.
add_action('phpmailer_init', function (PHPMailer $mail) {
	$mail->isSMTP();
	$mail->SMTPAutoTLS = false;
	$mail->SMTPAuth = env('MAIL_USERNAME') && env('MAIL_PASSWORD');
	$mail->SMTPSecure = env('MAIL_ENCRYPTION', 'tls');
	$mail->Host = env('MAIL_HOST');
	$mail->Port = env('MAIL_PORT', 587);
	$mail->Username = env('MAIL_USERNAME');
	$mail->Password = env('MAIL_PASSWORD');
	return $mail;
});

add_filter('wp_mail_content_type', fn () => 'text/html');
add_filter('wp_mail_from_name', fn () => env('MAIL_FROM_NAME', env('MAIL_USERNAME')));
add_filter('wp_mail_from', fn () => env('MAIL_FROM_ADDRESS', env('MAIL_USERNAME')));
