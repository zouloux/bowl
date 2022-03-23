<?php

// ----------------------------------------------------------------------------- STRUCTURAL CONFIGS

// Disable emoji support.
// Will disable ability to add them items in backend, and will also disable
// library injection in front-end.
const BOWL_DISABLE_OEMBED = true;

// Disable page nesting
const BOWL_DISABLE_NESTED_PAGES = false;

// Disable news editing in admin. Page is still accessible but removed from menu.
const BOWL_DISABLE_NEWS = false;

// ----------------------------------------------------------------------------- IMAGE SIZES

// Remove accents and special chars from uploaded file names
const BOWL_SLUGIFY_UPLOAD_NAMES = true;

// Enable webp compression for all compatible images
const BOWL_WEBP_ENABLED = true;
const BOWL_WEBP_QUALITY = 80;

// Enable BlurHash preview algorithm for all compatible images
const BOWL_BLUR_HASH_ENABLED = true;
const BOWL_BLUR_HASH_RESOLUTION = [8, 8];

// Global JPG Quality
const BOWL_JPEG_QUALITY = 74;

// All post previews image
const BOWL_POST_THUMBNAIL_SIZE = [800, 600];

// Ordered list of all available image sizes.
// From small to large
// [?width, ?height, ?crop]
const BOWL_IMAGE_SIZES = [
	'thumbnail' => [150],
	'small' 	=> [512],
	'large' => [ 1024, 1024 ], // override after
	"1600x1200" => [ 1600, 1200 ],
	"2048x2048" => [ 2048, 2048 ], // override before
];

// ----------------------------------------------------------------------------- ADMIN BOWL CONFIG

// Disable plugins installation, WordPress update, theme installation
// NOTE : This is not a bowl prefixed constant
const DISALLOW_FILE_MODS = true;

// All meta box are in place and can't be moved by user
const BOWL_DISABLE_META_BOX_DRAGGABLE = true;

// Load theme/assets/admin.css and theme/assets/admin.js
const BOWL_ADMIN_LOAD_CUSTOM_ASSETS = false;

// ----------------------------------------------------------------------------- ADMIN NEWS BOWL CONFIG
// BOWL_DISABLE_NEWS needs to be set at false to be used

// Add meta image meta box on side
const BOWL_ADD_IMAGE_META_BOX = false;

// Disable excerpt edition in news
const BOWL_DISABLE_EXCERPT = false;

// Move author meta box on right side
const BOWL_AUTHOR_META_BOX_ON_SIDE = false;

// Disable tags or slug meta boxes
const BOWL_DISABLE_TAGS = false;
const BOWL_DISABLE_SLUG = false;

// ----------------------------------------------------------------------------- ADMIN EDITOR BOWL CONFIG

// Disable Gutenberg editor everywhere and enable classic editor
const BOWL_DISABLE_GUTENBERG = true;

// List allowed buttons in TinyMCE editor, everywhere
const BOWL_MCE_BUTTONS = [
	// First MCE line
	[
		"styleselect",
		//"formatselect",
		"bold",
		"italic",
		"bullist",
		"numlist",
		// "blockquote",
		"alignleft", "aligncenter", "alignright",
		"link",
		//"wp_more",
		"spellchecker",
		"fullscreen",
		// "wp_adv",
		// Second line buttons, placed on first line
		// "strikethrough", "hr", "forecolor",
		"pastetext",
		"removeformat",
		"charmap",
		"outdent",
		"indent",
		"undo",
		"redo",
		"wp_help",
		"emoticons"
	],
	// Second MCE line
	[]
];

// ----------------------------------------------------------------------------- TINY MCE CONFIG

const BOWL_MCE_STYLES = [
	[
		'title' => 'Title 1',
		'block' => 'h2',
		'classes' => 'Rich_title1',
		'wrapper' => false,
		'style' => [
			'font-size' => '2em',
			'text-align' => 'left',
			'font-weight' => 'bold',
		]
	], [
		'title' => 'Title 2',
		'block' => 'h3',
		'classes' => 'Rich_title2',
		'wrapper' => false,
		'style' => [
			'font-size' => '1.6em',
			'text-align' => 'left',
			'font-weight' => 'bold',
		]
	], [
		'title' => 'Paragraph Regular',
		'block' => 'p',
		'classes' => 'Rich_paragraphRegular',
		'wrapper' => false,
		'style' => [
			'font-size' => '1em',
		]
	], [
		'title' => 'Paragraph Medium',
		'block' => 'p',
		'classes' => 'Rich_paragraphMedium',
		'wrapper' => false,
		'style' => [
			'font-size' => '.8em',
		]
	], [
		'title' => 'Quote',
		'block' => 'p',
		'classes' => 'Rich_quote',
		'wrapper' => false,
		'style' => [
			'font-size' => '1.2em',
			'font-style' => 'italic'
		]
	],
];

// ----------------------------------------------------------------------------- THEME CUSTOMIZER

// Remove useless theme customizer options
const BOWL_REMOVE_THEME_CUSTOMIZE_SECTIONS = [
	'themes',
	'wporg_themes',
	'installed_themes',
	'add_menu',
	'custom_css',
	'wpseo_breadcrumbs_customizer_section',
	'menu_locations',
	'header_image',
	'colors',
];

// ----------------------------------------------------------------------------- FRONT
// Next configs are only if you use Wordpress theme and not Nano

// Enables ?json=1 on all pages
const BOWL_ENABLE_JSON_API = false;

// Enables ?ajax=1 on all pages
const BOWL_ENABLE_AJAX_API = false;

// Disable tags in sitemap
const BOWL_DISABLE_SITEMAP_TAGS = true;

// Disable users in sitemap
const BOWL_DISABLE_SITEMAP_USERS = true;
