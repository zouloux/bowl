<?php

use Extended\ACF\Fields\Group;
use Extended\ACF\Fields\Image;
use Extended\ACF\Fields\Layout;
use Extended\ACF\ConditionalLogic;
use Extended\ACF\Fields\ButtonGroup;
use Extended\ACF\Fields\PageLink;
use Extended\ACF\Fields\Text;
use Extended\ACF\Fields\WysiwygEditor;

// ----------------------------------------------------------------------------- MIME TYPE

function bowl_get_mime ( $type, $others = [] ) {
	if ( $type === "image" )
		return ["jpg", "png", "jpeg", ...$others];
	if ( $type === "svg" )
		return ["svg"];
	else if ( $type === "video" )
		return ["mp4", ...$others];
	else if ( $type === "document" )
		return ["pdf", ...$others];
	else
		throw new \Exception("bowl_get_mime // Invalid type $type");
}

// ----------------------------------------------------------------------------- TITLE FIELD

function bowl_create_title_field ( $label = "Title", $key = 'title' ) {
	return Text::make( $label, $key );
}

// ----------------------------------------------------------------------------- IMAGES

function bowl_create_regular_image_field ( $key = "image", $label = "Image" ) {
	return Image::make($label, $key)
		->wrapper(["class" => "smallImage"]);
}

function bowl_filter_image_to_href ( &$node, $imageKey ) {
	$node[$imageKey] = (
		( isset($node[$imageKey]) && $node[$imageKey] instanceof BowlImage )
		? bowl_remove_base_from_href( $node[$imageKey]->href )
		: null
	);
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

function bowl_create_locale_enable_field ( $disabled = "Disabled", $enabled = "Enabled" ) {
	$locales = bowl_get_locale_list();
	$choices = [
		$disabled,
		...array_keys($locales),
		$enabled,
	];
	return ButtonGroup::make("", "enabled")
		->choices( $choices )->defaultValue( count($choices) - 1 )
		->returnFormat('array')
		->wrapper(["class" => "bowlEnabledField"]);
}


// ----------------------------------------------------------------------------- ENABLED FLEXIBLE
// An enabled field which is on top of the flexible block

function bowl_create_enable_field ( $choices = [ "Disabled", "Enabled" ], $key = "enabled" ) {
	return ButtonGroup::make("", $key)
		->choices( $choices )->defaultValue( 1 )
		->wrapper(["class" => "bowlEnabledField"]);
}


// ----------------------------------------------------------------------------- CONDITIONAL GROUP

/**
 * IMPORTANT : Use expand when using into fields
 * ->fields([
 * 		...bowl_create_conditional_group()
 * ])
 * NOTE : Parsed and filtered by BowlFilter::recursivePatchFields
 */
function bowl_create_conditional_group ( $label, $key, $choiceFields ) {
	// Key for button group
	$groupKey = "\$_".$key.'_group';
	$enabledKey = $groupKey.'_selected';
	// Convert choices to "my-choice" => "My Choice"
	$choices = [];
	// Allow keys to be like "disabled/Désactivé" to convert to ["disabled" => "Désactivé"]
	foreach ( $choiceFields as $choice => $fields ) {
		$split = explode("/", $choice, 2);
		if ( count($split) === 2 )
			$choices[ acf_slugify($split[0]) ] = $split[1];
		else
			$choices[ acf_slugify($choice) ] = $choice;
	}
	// Generate button group
	$output = [
		ButtonGroup::make( $label, $enabledKey )
			->wrapper(['class' => 'noLabel'])
			->choices( $choices )
	];
	// Browse choices and map to correct field
	$c = array_keys( $choices );
	$v = array_values( $choiceFields );
	foreach ( $c as $index => $choiceSlug ) {
		// Target fields from choice index
		$fields = $v[ $index ];
		// Do not create empty groups
		if ( empty($fields) ) continue;
		// Create group and connect it to correct choice
		$output[] = Group::make(' ', $groupKey.'_'.$choiceSlug)
			->layout("row")
			->wrapper(['class' => 'conditionalGroup'])
			->fields( $fields )
			->conditionalLogic([
				ConditionalLogic::where( $enabledKey, "==", $choiceSlug )
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

// ----------------------------------------------------------------------------- INSTRUCTIONS

function bowl_create_instruction_group_fields ( $fields, $richContent, $fontSize = "1.3em" ) {
	global $bowl_create_instruction_group_fields_counter;
	if ( !isset($bowl_create_instruction_group_fields_counter) )
		$bowl_create_instruction_group_fields_counter = 0;
	$bowl_create_instruction_group_fields_counter ++;
	$fields->addGroup("instructions_".$bowl_create_instruction_group_fields_counter, " ")
		->seamless()
		->instructions("<p style='font-size: $fontSize'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$richContent</p>");
}

