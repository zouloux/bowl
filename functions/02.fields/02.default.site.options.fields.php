<?php

use Extended\ACF\Fields\Accordion;
use Extended\ACF\Fields\ButtonGroup;
use Extended\ACF\Fields\ColorPicker;
use Extended\ACF\Fields\Image;
use Extended\ACF\Fields\Repeater;
use Extended\ACF\Fields\Text;

// ----------------------------------------------------------------------------- DICTIONARIES

function bowl_create_dictionaries_fields_group ( string $title = "Dictionaries" ) {
	$group = new BowlGroupFields( $title );
	$group->rawFields()->multiLang();
	$group->fields([
		Repeater::make(' ', 'dictionaries')
			->buttonLabel("Add dictionary")
			->layout('row')
			->fields([
				bowl_create_title_field('Dictionary ID', 'id'),
				Accordion::make(bowl_translate_label('Translations'), 'accordion'),
				Repeater::make(' ', bowl_translate_field('data'))
					->buttonLabel("Add translation")
					->layout('table')
					->wrapper(['class' => 'clean'])
					->fields([
						Text::make('Key', 'key')->required(),
						Text::make('Value', 'value')->required(),
					])
			])
	]);
	return $group;
}

function bowl_create_dictionaries_filter ( string $key ) {
	return function ( $data ) use ( $key ) {
		if ( !isset($data[$key]) || !is_array($data[$key]) )
			return $data;
		//			throw new Exception("bowl_create_dictionaries_filter // Cannot find $key in data.");
		$dictionaries = $data[ $key ];
		$newArray = [];
		foreach ( $dictionaries as $dictionary ) {
			$translations = $dictionary['data'];
			$newDictionaryArray = [];
			if (is_array($translations))
				foreach ( $translations as $translation )
					$newDictionaryArray[ $translation['key'] ] = $translation['value'];
			$newArray[ $dictionary['id'] ] = $newDictionaryArray;
		}
		$data[ $key ] = $newArray;
		return $data;
	};
}

// ----------------------------------------------------------------------------- KEYS

function bowl_create_keys_fields_group ( string $title = "API and product keys" ) {
	$group = new BowlGroupFields( $title );
	$group->rawFields();
	$group->fields([
		Repeater::make(' ', 'keys')
			->instructions("List API and product keys here")
			->buttonLabel("Add key")
			->layout('table')
			->fields([
				Text::make('Key', 'key')->required(),
				Text::make('Value', 'value')->required(),
			])
	]);
	return $group;
}

function bowl_create_keys_filter ( string $key ) {
	return function ( $data ) use ( $key ) {
		if ( !isset($data[$key]) || !is_array($data[$key]) )
			return $data;
		//			throw new Exception("bowl_create_keys_filter // Cannot find $key in data.");
		$keys = $data[$key];
		$newArray = [];
		foreach ($keys as $associativeKeyValue)
			$newArray[ $associativeKeyValue['key'] ] = $associativeKeyValue['value'];
		$data[$key] = $newArray;
		return $data;
	};
}

// ----------------------------------------------------------------------------- THEME OPTIONS

function bowl_create_theme_options_fields_group ( string $title = "Theme options" ) {
	$group = new BowlGroupFields( $title );
	$group->multiLang();
	$group->fields([
		Text::make("Page title template", 'pageTitleTemplate')
			->placeholder("{{site}} - {{page}}")
			->instructions("<strong>{{site}}</strong> for site name<br><strong>{{page}}</strong> for page name."),
		Image::make("Icon 32", "icon32")
			->instructions("Favicon<br>32x32px, png<br>For desktop"),
		Image::make("Icon 1024", "icon1024")->instructions("1024x1024px, png")
			->instructions("Favicon<br>1024x1024px, png<br>For mobile"),
		Text::make(bowl_translate_label("Mobile App title"), bowl_translate_field('appTitle'))
			->instructions("Shortcut name on mobile when added to home page."),
		ColorPicker::make("Theme color", "appColor")
			->instructions("Browser theme color, for desktop and mobile."),
		ButtonGroup::make("iOS title bar color", 'iosTitleBar')
			->choices([
				'none' => "Not set",
				'default' => 'Default',
				'black' => 'Black',
				'translucent' => 'Translucent',
			])
	]);
	return $group;
}

function bowl_create_theme_filter ( $themeKey = "theme" ) {
	return function ( $data ) use ( $themeKey ) {
		bowl_filter_image_to_href( $data[$themeKey], "icon32" );
		bowl_filter_image_to_href( $data[$themeKey], "icon1024" );
		return $data;
	};
}

// ----------------------------------------------------------------------------- MENU FIELD

// FIXME : Add multi-level menu option, allow 2 levels deep

function bowl_create_menu_fields_group ( string $id, string $title = "menu") {
	$group = new BowlGroupFields( $title );
	$group->rawFields()->multiLang();
	$group->fields([
		Repeater::make(' ', $id)->fields( [
			bowl_create_page_link_field(),
			Text::make(' ', bowl_translate_field('title'))
				->instructions(bowl_translate_label('Title override (Optional)')) // TODO argument
		])
	]);
	return $group;
}

/**
 * Will get title for link to internal pages / post.
 * Will detect external links.
 * @param string $key
 * @return Closure
 */
function bowl_create_menu_filter ( string $key ) {
	return function ( $data ) use ( $key ) {
		if ( !isset($data[$key]) || !is_array($data[$key]) )
			return $data;
		foreach ( $data[$key] as &$itemValue ) {
			// Link will be null if page does not exist in current locale
			if ( !isset($itemValue['link']) ) continue;
			$link = $itemValue['link'];
			// Get post from link
			$post = BowlRequest::getWPPostByPath(
				$link === "/" ? get_home_url() : $link
			);
			// Save short link
			$itemValue["link"] = bowl_remove_base_from_href( $link );
			// No title override, get from post
			if ( empty($itemValue['title']) && !is_null($post) )
				$itemValue["title"] = $post->post_title;
			// Patch title
			$itemValue["title"] = bowl_fix_translated_string( $itemValue["title"] );
		}
		return $data;
	};
}

