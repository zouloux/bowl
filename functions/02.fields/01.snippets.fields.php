<?php

use Extended\ACF\Fields\Group;
use Extended\ACF\Fields\Image;
use Extended\ACF\Fields\Layout;
use Extended\ACF\ConditionalLogic;
use Extended\ACF\Fields\ButtonGroup;
use Extended\ACF\Fields\PageLink;
use Extended\ACF\Fields\Text;
use Extended\ACF\Fields\WysiwygEditor;

// ----------------------------------------------------------------------------- TITLE FIELD

function bowl_create_title_field ( $label = "Title", $key = 'title' ) {
	return Text::make( $label, $key );
}

// ----------------------------------------------------------------------------- WYSIWYG FIELD

function bowl_create_wysiwyg_field ( $label = "Content", $allowMedia = false, $class = 'clean', $key = "content" ) {
	return WysiwygEditor::make( $label, $key )
		->tabs('visual')
		->mediaUpload( $allowMedia )
		->wrapper(['class' => $class]);
}

// ----------------------------------------------------------------------------- ENABLED CONDITIONAL
// Create a boolean with its condition

function bowl_create_enabled_fields ( $title = "Enabled", $offLabel = "Off", $onLabel = "On", $default = 1, $key = 'enabled') {
	return ButtonGroup::make( $title, $key )
		->defaultValue( $default )
		->choices([
			0 => $offLabel,
			1 => $onLabel
		]);
}

function bowl_create_enabled_conditional_fields ( $label = "Enabled", $default = 1, $key = 'enabled', $offLabel = "Off", $onLabel = "On" ) {
	return [
		bowl_create_enabled_fields( $label, $offLabel, $onLabel, $default, $key ),
		ConditionalLogic::where( $key, "==", 1 )
	];
}

// ----------------------------------------------------------------------------- CONDITIONAL GROUP

/**
 * IMPORTANT : Use expand when using into fields
 * ->fields([
 * 		...bowl_create_conditional_group()
 * ])
 */
function bowl_create_conditional_group ( $label, $key, $choiceFields ) {
	// Key for button group
	$enabledKey = $key.'Group_selected';
	// Convert choices to "my-choice" => "My Choice"
	$choices = [];
	foreach ( $choiceFields as $choice => $fields )
		$choices[ acf_slugify($choice) ] = $choice;
	// Generate button group
	$output = [
		ButtonGroup::make( $label, $enabledKey )
			->wrapper(['class' => 'noLabel'])
			->choices( $choices )
	];
	// Browse choices
	foreach ( $choiceFields as $choice => $fields ) {
		$slugified = acf_slugify($choice);
		// Do not create empty groups
		if (empty($fields)) continue;
		// Create group and connect it to correct choice
		$output[] = Group::make(' ', 'selectorGroup_'.$slugified)
			->layout("row")
			->wrapper(['class' => 'conditionalGroup'])
			->fields( $fields )
			->conditionalLogic([
				ConditionalLogic::where( $enabledKey, "==", $slugified )
			]);
	}
	return $output;
}

// ----------------------------------------------------------------------------- COLUMNS GROUP
// Create a clean group field

function bowl_create_columns_group_field ( $fields = [], $name = "columns", $layout = 'row') {
	return Group::make(' ', $name)
		->layout( $layout )
		->wrapper(['class' => 'columns-group clean'])
		->fields( $fields );
}

// ----------------------------------------------------------------------------- PAGE LINK FIELD

function bowl_create_page_link_field ( $title = "Link to page", $key = 'link', $postTypes = ['page'], $allowArchives = false ) {
	return PageLink::make( $title, $key )
		->allowArchives( $allowArchives )
		->allowNull()->required()
		->postTypes( $postTypes );
}

// ----------------------------------------------------------------------------- IMAGE FIELD

function bowl_create_image_field ( $label = "Image", $key = 'image', $class = 'smallImage' ) {
	return Image::make($label, $key)
		->wrapper(['class' => $class]);
}

// ----------------------------------------------------------------------------- FLEXIBLE LAYOUT

function bowl_create_flexible_layout ( $title, $id, $layout, $fields ) {
	return Layout::make( $title, $id )
		->layout( $layout )
		->fields( $fields );
}

// ----------------------------------------------------------------------------- LAYOUT FLEXIBLE SEPARATOR
// Create a separator layout for flexibles

$_bowlLayoutSeparatorIndex = 0;
function bowl_create_separator_layout () {
	global $_bowlLayoutSeparatorIndex;
	return Layout::make('', '--'.(++$_bowlLayoutSeparatorIndex));
}
