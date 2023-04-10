<?php

/**
 * Plugin Name:       Bowl
 * Plugin URI:        https://github.com/zouloux/bowl
 * GitHub Plugin URI: https://github.com/zouloux/bowl
 * Description:       Advanced CMS fields workflow on top of Wordplate for Wordpress.
 * Author:            ZoulouX
 * Author URI: 		  https://zouloux.com
 * License:           MIT
 * License URI:       https://opensource.org/licenses/MIT
 * Text Domain:       bowl
 * Domain Path:       /cms
 * Version:           1.4.4
 * Copyright:         © 2022 Alexis Bouhet
 */

// No overload when blog is not installed yet
if ( !is_blog_installed() ) return;

// Register bowl plugin dir for other components
define('BOWL_PLUGIN_DIR', plugin_dir_url(__FILE__));

// ----------------------------------------------------------------------------- AUTO LOAD FUNCTIONS

/**
 * Autoload php files in a directory.
 * Is recursive.
 * Will load files and directories in ascendant alphanumeric order.
 * Name your files like so :
 * - 00.my.first.file.php
 * - 01.loaded.after.php
 * - 02.you.got.it.php
 * Can also start at 01.
 * Will skip files and directories with name starting with an underscore. ex :
 * - _skipped.php
 */
function auto_load_functions ( $directory ) {
	$files = scandir( $directory );
	foreach ( $files as $file ) {
		if ( $file == '.' || $file == '..' ) continue;
		if ( stripos($file, '_') === 0 ) continue;
		$path = $directory.'/'.$file;
		if ( is_dir($path) )
			auto_load_functions( $path );
		else
			require_once( $directory.'/'.$file );
	}
}

// ----------------------------------------------------------------------------- BOOTSTRAP BOWL

// Load Bowl helper and classes
auto_load_functions(__DIR__ . '/functions/00.helpers');
auto_load_functions(__DIR__ . '/functions/01.core');
auto_load_functions(__DIR__ . '/functions/02.fields');

// Load and start bowl
function bowl_start ( string $configPath = null, string $functionsPath = null ) {
	// Load config if asked by theme
	if ( !is_null($configPath) )
		require_once( $configPath );
	// Recursively load theme functions
	if ( !is_null($functionsPath) )
		auto_load_functions( $functionsPath );
	// Execute bowl runners
	auto_load_functions( __DIR__.'/functions/03.exec' );
	// Install registered fields from theme
	BowlFields::install();
	// Call a hook after all functions are loaded / executed
	// Mandatory for custom meta box order
	apply_filters('after_functions', null);
}
