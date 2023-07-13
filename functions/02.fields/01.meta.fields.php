<?php

use Extended\ACF\Fields\Text;
use Extended\ACF\Fields\Textarea;

// ----------------------------------------------------------------------------- META FIELDS

function bowl_create_meta_fields_group ( string $title = 'SEO and share', $showTitleOverride = true ) {
	$group = new BowlGroupFields( $title );
	$group->multiLang();
	$fields = [
		Textarea::make(bowl_translate_label("Meta description"), bowl_translate_field('description'))
			->rows(3)
			->instructions("For SEO only. Optional."),
		$showTitleOverride ? Text::make(bowl_translate_label("Title override"), bowl_translate_field('title'))
			->instructions("Override head title tag. Useful to separate post title from title tag. Title template will be ignored.") : null,
		Text::make(bowl_translate_label("Share title"), bowl_translate_field('shareTitle'))
			->instructions("For Facebook and Twitter share.<br>Will use page title by default."),
		Textarea::make(bowl_translate_label("Share description"), bowl_translate_field('shareDescription'))
			->rows(3)
			->instructions("For Facebook and Twitter share.<br>Will use meta description by default."),
		bowl_create_image_field("Share image", 'shareImage')
			->instructions("For Facebook and Twitter"),
	];
	$fields = array_filter( $fields, fn ($item) => $item !== null );
	$group->fields( $fields );
	return $group;
}

function bowl_create_meta_filter ( $metaKey = "meta" ) {
	return function ( $data ) use ( $metaKey ) {
		bowl_filter_image_to_href( $data[$metaKey], "shareImage" );
		return $data;
	};
}
