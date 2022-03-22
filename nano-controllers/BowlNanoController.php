<?php

use Nano\core\Nano;

class BowlNanoController
{
	// ------------------------------------------------------------------------- INIT

	public function __construct () {}

	// ------------------------------------------------------------------------- START WORDPRESS

	protected bool $_isWordpressLoaded = false;
	function isWordpressLoaded () { return $this->_isWordpressLoaded; }

	/**
	 * Will load Wordpress libs / plugins / theme, but not run any query.
	 */
	function loadWordpress () {
		require_once Nano::path('wordpress', 'wp-load.php');
		$this->_isWordpressLoaded = true;

		// Inject bowl twig helpers
		if ( Nano::$renderer instanceof \Nano\renderers\twig\TwigRenderer ) {
			/** @var \Nano\renderers\twig\TwigRenderer $renderer */
			$renderer = Nano::$renderer;
			bowl_inject_twig_helpers( $renderer->getTwig() );
		}
	}

	/**
	 * Will run and exec main Wordpress query.
	 */
	function startWordpress () {
		if ( !$this->_isWordpressLoaded )
			$this->loadWordpress();
		define('WP_USE_THEMES', true);
		wp();
		require_once ABSPATH . WPINC . '/template-loader.php';
	}

	// ------------------------------------------------------------------------- BOWL POSTS

	function getCurrentBowlPost () {
		$path = Nano::getRequestPath( false );
		return BowlRequest::getBowlPostByPath( $path );
	}

	// ------------------------------------------------------------------------- LOCALE

	function redirectToBrowserLocale () {
		$userLanguage = wpm()->setup->get_user_language();
		Nano::redirect( Nano::getURL('wordpressPage', ['lang' => $userLanguage]) );
	}

	// ------------------------------------------------------------------------- WEBSITE RESPONDERS

	function printRobots () {
		$allow = !get_option( 'blog_public' );
		Nano::action("Website", "printRobots", [
			$allow ? ['*'] : [],
			$allow ? [] : ['*'],
		]);
	}

	function printSitemap ( callable $filterPages = null ) {
		// TODO : Check if post exists in other languages
		// TODO : Add pages with other locales
		// TODO : Split in sub-sitemaps for performances, 1 by post-type
		// TODO : 		Need to change API and declare routes in here
		$allPost = BowlRequest::getAllBowlPosts();
		$sitemapPages = [];
		/** @var BowlPost $post */
		foreach ( $allPost as $post ) {
			$sitemapPages[] = [
				'href' => $post->href,
				'lastModified' => $post->timestamp,
			];
		}
		if ( $filterPages )
			$sitemapPages = $filterPages( $sitemapPages );
		Nano::action("Website", "printSitemap", [$sitemapPages]);
	}
}
