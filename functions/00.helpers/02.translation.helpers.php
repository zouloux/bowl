<?php

// ----------------------------------------------------------------------------- LOCALES

/**
 * Get current locale code, as "fr" or "en"
 */
function bowl_get_current_locale () {
	global $_bowlCurrentLocale;
	if ( !isset($_bowlCurrentLocale) ) {
		$_bowlCurrentLocale = (
		function_exists('wpm_get_language')
			? wpm_get_language()
			: ''
		);
	}
	return $_bowlCurrentLocale;
}

/**
 * Get current locale object with more info
 */
function bowl_get_current_locale_object () {
	global $_bowlCurrentIsoLocale;
	if ( !isset($_bowlCurrentIsoLocale) ) {
		$locale = bowl_get_current_locale();
		if ( function_exists('wpm') )
			$languages = wpm()->setup->get_languages();
		else
			$languages = [];
		$_bowlCurrentIsoLocale = $languages[ $locale ] ?? null;
	}
	return $_bowlCurrentIsoLocale;
}

/**
 * Get list of all locales.
 */
function bowl_get_locale_list () {
	if ( !function_exists('wpm') )
		return [];
	global $_bowlWPMLanguages;
	if ( !isset($_bowlWPMLanguages) )
		$_bowlWPMLanguages = wpm()->setup->get_languages();
	return $_bowlWPMLanguages;
}

/**
 * Get locale switcher menu data.
 */
function bowl_get_locale_switcher ( $pageData = null ) {
	$currentHref = bowl_get_current_href();
	$locales = bowl_get_locale_list();
	$currentLocale = bowl_get_current_locale();
	$localeSwitcher = [];
	foreach ( $locales as $key => $locale ) {
		if ( !$locale['enable'] ) continue;
		$href = $key == bowl_get_current_locale() ? bowl_get_current_href() : wpm_translate_url( $currentHref, $key );
		// Dirty check, if it's a translated news
		// We go back to home to avoid a 404
		if (
			( isset($pageData['type']) && $pageData['type'] == 'post' )
			|| ($pageData['type'] == 'news' && $pageData["newsMode"] == "category")
		) {
			$href = bowl_get_base();
		}
		$localeSwitcher[] = [
			'locale' => $key,
			'localeFull' => $locale,
			'href' => $href,
			'name' => $locale['name'],
			'current' => $currentLocale == $key
		];
	}
	return $localeSwitcher;
}

// ----------------------------------------------------------------------------- TRANSLATIONS

/**
 * Get translated field key.
 * For example : "description" in french locale will give "fr_description"
 */
function bowl_translate_field ( string $fieldName ) {
	return bowl_get_current_locale().'_'.$fieldName;
}

/**
 * Add a "translated field" next to translatable field titles
 */
function bowl_translate_label ( string $fieldTitle ) {
	$locales = bowl_get_locale_list();
	if ( count($locales) <= 1 )
		return $fieldTitle;
	return $fieldTitle.' <span class="bowl_translated">['.strtoupper(bowl_get_current_locale()).']</span>';
}

/**
 * Remove locale prefix from an URL.
 * Note : Will always remove base.
 * ex : https://domain.com/fr/my-post.html -> /my-post.html
 * ex : /fr/my-post.html -> /my-post.html
 * @param string $href URL to remove locale from
 * @return string
 */
function remove_locale_from_href ( string $href ) : string {
	$href = bowl_remove_base_from_href( $href );
	$localeStart = '/'.bowl_get_current_locale();
	if ( stripos($href, $localeStart) !== false )
		$href = substr($href, strlen($localeStart));
	return $href;
}

/**
 * Fix a translated wpm string containing [:fr] markers.
 * Will not fail plugin not enabled
 */
function bowl_fix_translated_string ( $string ) {
	return (
	function_exists('wpm_translate_string')
		? wpm_translate_string( $string )
		: $string
	);
}