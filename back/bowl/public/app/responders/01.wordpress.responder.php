<?php

use Nano\core\Nano;
use Nano\debug\NanoDebug;
use Pecee\SimpleRouter\SimpleRouter;

// ----------------------------------------------------------------------------- ALL ROUTES

// Load wordpress on all routes
Nano::onBeforeRoute( function () {
	Nano::addControllerDirectory('mu-plugins/bowl/nano-controllers');
	Nano::action("Bowl", "loadWordpress");
});

// ----------------------------------------------------------------------------- REDIRECT LOCALE

// On home, redirect to correct locale code from browser
SimpleRouter::get('/', function () {
	Nano::action("Bowl", "redirectToBrowserLocale");
});

// ----------------------------------------------------------------------------- WORDPRESS PAGE

// Get a Wordpress page / post / custom post type
SimpleRouter::get('/{lang}/{a?}/{b?}/{c?}', function ( $lang ) {
	// Get current bowl post
	/** @var BowlPost $bowlPost */
	$bowlPost = Nano::action("Bowl", "getCurrentBowlPost");
	// Get current bowl post for this matching URL
	if ( is_null($bowlPost) ) return null;
	// Get global data and profile them
	$profiling = NanoDebug::profile("Get globals data");
	$globals = BowlData::getData("globals");
	$profiling();
	// Render this post
	return Nano::$renderer->render($bowlPost->template, [
		'post' => $bowlPost,
		'globals' => $globals,
	]);
})->setName('wordpressPage');

// ----------------------------------------------------------------------------- WP-JSON GATEWAY

// Open some wp-json endpoints
SimpleRouter::get('/wp-json/wp/v2/{request}', function ( $request ) {
	// Only open those endpoints
	if ( in_array($request, ['media']) )
		Nano::action("Bowl", "startWordpress");
});

// -----------------------------------------------------------------------------

// TODO : /{lang}/ !!!
// TODO : /{lang}/{category/categorie}/{categoryName}
// TODO : /{lang}/{search/recherche}/{searchTerm}

// ----------------------------------------------------------------------------- NOT FOUND

Nano::onNotFound(function () {
	return Nano::$renderer->render("not-found", [
		'globals' => BowlData::getData("globals")
	]);
});
