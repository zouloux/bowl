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
				Accordion::make(bowl_translate_field_title('Translations'), 'accordion'),
				Repeater::make(' ', bowl_translate_field_name('data'))
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
	$group->fields([
		Text::make("Page title template", 'pageTitleTemplate')
			->placeholder("{{site}} - {{page}}")
			->instructions("<strong>{{site}}</strong> for site name<br><strong>{{page}}</strong> for page name."),
		Image::make("Favicon 32", "favicon32")->instructions("32x32px, png"),
		...bowl_create_conditional_group("Enable web-app capabilities", "webAppCapabilities", [
			'Off' => [],
			'On' => [
				Text::make("App title", 'appTitle'),
				Image::make("App icon 1024", "favicon1024")->instructions("1024x1024px, png"),
				ColorPicker::make("App icon background color"),
				ColorPicker::make("Theme color"),
				ButtonGroup::make("iOS title bar color", 'iosTitleBar')
					->choices([
						'default' => 'Default',
						'black' => 'Black',
						'translucent' => 'Translucent',
					]),
				ButtonGroup::make("Display type", 'displayType')
					->choices([
						'browser' => 'Browser',
						'fullscreen' => 'Fullscreen',
						'color' => 'Color',
					]),
				ButtonGroup::make("Allowed web-app orientation", 'allowedOrientation')
					->choices([
						'any' => 'Any',
						'auto' => 'Auto',
						'portrait' => 'Portrait',
						'landscape' => 'Landscape',
					]),
			]
		])
	]);
	return $group;
}

// ----------------------------------------------------------------------------- MENU FIELD

// FIXME : Add multi-level menu option, allow 2 levels deep

function bowl_create_menu_fields_group ( string $id, string $title = "menu") {
	$group = new BowlGroupFields( $title );
	$group->rawFields()->multiLang();
	$group->fields([
		Repeater::make(' ', $id)->fields( [
			bowl_create_page_link_field(),
			Text::make(' ', bowl_translate_field_name('title'))
				->instructions(bowl_translate_field_title('Title override (Optional)')) // TODO argument
		])
	]);
	return $group;
}

function bowl_create_menu_filter ( string $key ) {
	return function ( $data ) use ( $key ) {
		if ( !isset($data[$key]) || !is_array($data[$key]) )
			return $data;
		//			throw new Exception("bowl_create_menu_filter // Cannot find $key in data.");
		$menu = $data[$key];
		$newArray = [];
		// FIXME : Title not always working ? Href also ?
		foreach ( $menu as $item ) {
			// Link will be null if page does not exist in current locale
			if ( !$item['link'] ) continue;
			// Get overridden title
			$title = $item['title'];
			// No title, get from post
			if ( empty($title) ) {
				// Get post from link
				$post = BowlRequest::getWPPostByPath(
					remove_locale_from_href( $item['link'] )
				);
				// Override title
				if ( !is_null($post) )
					$title = $post->post_title;
			}
			$newArray[] = [
				'href' => $item['link'],
				'title' => $title,
			];
		}
		$data[$key] = $newArray;
		return $data;
	};
}

