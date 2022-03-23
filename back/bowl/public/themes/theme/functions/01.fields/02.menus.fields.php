<?php


BowlFields::register(function () {
	$fields = BowlFields::createSingletonFields( "menus" );
	$fields->menu( ["Menus"], "dashicons-menu-alt2" );

	$fields->attachGroup( "menuHeader", bowl_create_menu_fields_group("menuHeader", "Header menu") );
	$fields->addFilter( bowl_create_menu_filter("menuHeader") );

	$fields->attachGroup( "menuFooter", bowl_create_menu_fields_group("menuFooter", "Footer menu") );
	$fields->addFilter( bowl_create_menu_filter("menuFooter") );

	return $fields;
});