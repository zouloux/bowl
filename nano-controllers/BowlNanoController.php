<?php

use Nano\core\Nano;
use Nano\debug\NanoDebug;

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
		if ( $this->_isWordpressLoaded ) return;
		require_once Nano::path('wordpress', 'wp-load.php');
		$this->_isWordpressLoaded = true;
		// Enable WP query profiling
		if ( Nano::getEnv("NANO_DEBUG") && Nano::getEnv("NANO_PROFILE", false) ) {
			define('SAVEQUERIES', true);
			NanoDebug::addCustomTab("WP Queries", function () {
				global $wpdb;
				if (!$wpdb->queries) return "";
				$total = count($wpdb->queries);
				$buffer = "<h2>Total queries: $total</h2>";
				foreach ( $wpdb->queries as $query ) {
					$initiator = implode(" < ", array_reverse(explode(",", $query[2])));
					$queryContent = NanoDebug::dumpToString($query[0]);
					$buffer .= "<h3 class='DebugBar_dumpTitle'>$initiator</h3>";
					$buffer .= "<div class=''>$queryContent</div>";
				}
				return $buffer;
			});
		}

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
		$this->loadWordpress();
		define('WP_USE_THEMES', true);
		wp();
		require_once ABSPATH . WPINC . '/template-loader.php';
	}

	// ------------------------------------------------------------------------- BOWL POSTS

	function getCurrentBowlPost () {
		$this->loadWordpress();
		$path = Nano::getRequestPath( false );
		$path = strtolower( $path );
		return BowlRequest::getBowlPostByPath( $path );
	}

	// ------------------------------------------------------------------------- LOCALE

	// Get locale info from wpm
	// Load WordPress once.
	function getCachedLocaleData () {
		// FIXME : Clear cache when updating languages in WPM ? Or just any post to refresh it ?
		return Nano::cacheDefine("__bowl__localesData", function () {
			$this->loadWordpress();
			return [
				'languages' => wpm()->setup->get_languages(),
				'default' => wpm()->setup->get_default_language()
			];
		});
	}

	function getUserLocale () {
		$localesData = $this->getCachedLocaleData();
		$allLocales = array_keys( $localesData['languages'] );
		// Get user locale
		$browserLocale = strtolower( substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2) );
		// FIXME : Parse all user locales and order them
		$locale = $localesData["default"];
		if ( in_array($browserLocale, $allLocales) )
			$locale = $browserLocale;
		// TODO : Check cookie for user selected locale
		return $locale;
	}

	// ------------------------------------------------------------------------- WEBSITE RESPONDERS

	function printRobots () {
		$allow = !!get_option( 'blog_public' );
		Nano::action("Website", "printRobots", [
			$allow ? ['*'] : [],
			$allow ? [] : ['*'],
		]);
	}

	function printSitemap ( $postTypes = ["page", "posts"], callable $filterPages = null ) {
		// TODO : Check if post exists in other languages
		// TODO : Add pages with other locales
		// TODO : Split in sub-sitemaps for performances, 1 by post-type
		// TODO : 		Need to change API and declare routes in here
		$allPost = BowlRequest::getAllBowlPosts( $postTypes );
		$sitemapEntries = [];
		/** @var BowlPost $post */
		foreach ( $allPost as $post ) {
			$sitemapEntries[] = [
				'href' => $post->href,
				'lastModified' => $post->date->getTimestamp(),
				'post' => $post,
			];
		}
		if ( $filterPages )
			foreach ( $sitemapEntries as $key => $entry )
				$sitemapEntries[$key] = $filterPages( $entry );
		$sitemapEntries = array_filter($sitemapEntries, fn ($p) => $p !== false );
		Nano::action("Website", "printSitemap", [$sitemapEntries]);
	}
}
