<?php


use WordPlate\Acf\Fields\Text;

BowlFields::register(function () {
	$fields = BowlFields::createCollectionFields("test-collection");
	$fields->menu(["Un test", "Tests"], null );
	$fields->addGroup("test", "Test")->fields([
		Text::make("Title", "title"),
		WordPlate\Acf\Fields\File::make("File", "file")
			->returnFormat('object')
	]);
	$fields->attachGroup( "meta", bowl_create_meta_fields_group() );
	$fields->addFilter(function ( $data ) {
		$data['collection'] = true;
		return $data;
	});
	return $fields;
});