<?php


BowlFields::register(function () {
	$fields = BowlFields::createSingletonFields( "site-options" );
	$fields->menu( ["Site options"], "dashicons-admin-generic" );

	$fields->attachGroup( "dictionaries", bowl_create_dictionaries_fields_group() );
	$fields->addFilter( bowl_create_dictionaries_filter("dictionaries") );

	$fields->attachGroup( "meta", bowl_create_meta_fields_group("Default meta") );

	$fields->attachGroup( "keys", bowl_create_keys_fields_group() );
	$fields->addFilter( bowl_create_keys_filter("keys") );

	$fields->attachGroup( "theme", bowl_create_theme_options_fields_group());

	return $fields;
});