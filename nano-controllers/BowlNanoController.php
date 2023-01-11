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
		if ($this->_isWordpressLoaded) return;
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
	 * Load WP_Post and Bowl classes.
	 * Useful to have cachable Bowl objects classes loaded without loading WordPress entirely.
	 */
	function loadCachableClasses () {
		// Load WP_Post
		require_once __DIR__.'/../../../wordpress/wp-includes/class-wp-post.php';
		// Load all Bowl classes
		$directory = __DIR__.'/../../../mu-plugins/bowl/functions/01.core/';
		$files = scandir($directory);
		foreach ( $files as $file ) {
			if ( $file === "." || $file === ".." ) continue;
			require_once $directory.$file;
		}
	}

	/**
	 * Cache automatically BowlPosts or BowlData requests.
	 * Will not load WordPress when retrieving cache for better perfs.
	 * @param string $key Cache key
	 * @param callable $getHandler Handler called when cache key is not found to retrieve cached object.
	 * @return mixed
	 * @throws Exception
	 */
	function cache ( string $key, callable $getHandler ) {
		$key = "_wordpress_".$key;
		$profiling = NanoDebug::profile("Cache [$key]");
		$result = Nano::cacheDefine( $key, function () use ($getHandler) {
			Nano::action("Bowl", "loadWordpress");
			return $getHandler();
		}, fn () => $this->loadCachableClasses());
		$profiling();
		return $result;
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
		$path = strtolower( $path );
		return BowlRequest::getBowlPostByPath( $path );
	}

	// ------------------------------------------------------------------------- LOCALE

	function getUserLocale () {
		// Get locale info from wpm
		// Load Wordpress once.
		// FIXME : Clear cache when updating languages in WPM ? Or just any post to refresh it ?
		$localesData = $this->cache("__localesData", function () {
			$this->loadWordpress();
			return [
				'languages' => wpm()->setup->get_languages(),
				'default' => wpm()->setup->get_default_language()
			];
		});
		$allLocales = array_keys( $localesData['languages'] );
		// Get user locale
		$browserLocale = strtolower( substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2) );
		// FIXME : Parse all user locales and order them
		$locale = $localesData["default"];
		if ( in_array($browserLocale, $allLocales) )
			$locale = $browserLocale;
		// TODO : Check cookie for user selected locale
		return $locale;
		// Old system :
		//		$userLanguage = wpm()->setup->get_user_language();
		//		Nano::redirect( Nano::getURL($route, ['lang' => $userLanguage]) );
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
