<?php

use Extended\ACF\Fields\Image;
use Extended\ACF\Fields\Text;
use Extended\ACF\Fields\Textarea;

// ----------------------------------------------------------------------------- META FIELDS

function bowl_create_meta_fields_group ( string $title = 'SEO and share' ) {
	$group = new BowlGroupFields( $title );
	$group->multiLang();
	$group->fields([
		Textarea::make(bowl_translate_label("Meta description"), bowl_translate_field('description'))
			->rows(3)
			->instructions("For SEO only. Optional."),
		Text::make(bowl_translate_label("Share title"), bowl_translate_field('shareTitle'))
			->instructions("For Facebook and Twitter share.<br>Will use page title by default."),
		Textarea::make(bowl_translate_label("Share description"), bowl_translate_field('shareDescription'))
			->rows(3)
			->instructions("For Facebook and Twitter share.<br>Will use meta description by default."),
		Image::make("Share image", 'shareImage')
			->instructions("For Facebook and Twitter"),
	]);
	return $group;
}

function bowl_create_meta_filter ( $metaKey = "meta" ) {
	return function ( $data ) use ( $metaKey ) {
		bowl_filter_image_to_href( $data[$metaKey], "shareImage" );
		return $data;
	};
}
