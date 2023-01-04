<?php

use Extended\ACF\Fields\Group;
use Extended\ACF\Key;
use Extended\ACF\Location;

/**
 * TODO : Important
 * - Track list of all custom post types and templates + a getter
 * 		- Use it in BowlSitemap etc
 *
 * TODO : Less important
 * - check hidden and do it better (hide in admin vs hide in urls) 'show_in_rest'
 * - 'has_archive' ?
 * - sitemap filtering
 *
 * If possible :
 * - Remove editor for specific page ids / templates, not all pages
 *
 * TODO : Test createDefaultPageFields
 * TODO : Test createDefaultPostFields
 * TODO : Do not allow multiple instances of them
 *
 * TODO : DOC DOC DOC
 */
class BowlFields {

	// ------------------------------------------------------------------------- REGISTERING

	static protected array $__registeredFields = [];
	static function register ( callable|BowlFields $handler ) {
		self::$__registeredFields[] = $handler;
	}

	// ------------------------------------------------------------------------- INSTALLING

	protected static array $__postTypes = [];

	protected static array $__allFieldGroupOrders = [];

	protected static array $__installedFields = [];

	static function install () {
		$separatorPosition = 0;
		foreach ( self::$__registeredFields as $handler ) {
			$fields = is_callable($handler) ? $handler() : $handler;
			$separatorPosition = max($fields->_position, $separatorPosition);
			self::installFields( $fields );
			self::$__installedFields[] = $fields;
		}
		self::reorderMenu( $separatorPosition );
		self::afterFunctions();
	}

	protected static function installFields ( BowlFields $fields ) {
		$location = [];
		// Check if installed field is multi lang. First, only if site is multi lang
		$isMultiLang = false;
		if (count(bowl_get_locale_list()) >= 2) {
			// Then, check if installed fields are multi lang
			$isMultiLang = $fields->_multiLang;
			/** @var BowlGroupFields $group */
			foreach ( $fields->_groups as $group ) {
				// Then, check each group, if at least one is multi lang
				// the whole installed field becomes multi lang
				$isGroupMultiLang = $group->toArray()['multiLang'];
				if ( $isGroupMultiLang )
					$isMultiLang = true;
			}
		}
		/**
		 * SINGLETON
		 */
		if ( $fields->type == "singleton" ) {
			if ( !empty($fields->_label) ) {
				// Set location, id and order
				$location[] = Location::where( 'options_page', $fields->name );
				$fields->_id = 'toplevel_page_'.$fields->name;
				// Register options page with ACF
				acf_add_options_page(array_merge([
					'menu_slug' => $fields->name,
					'page_title' => $fields->_label[0],
					'icon_url' => $fields->_icon,
					'position' => $fields->_position,
				], $fields->_options));
				// Register this options page type as multi-lang
				if ( $isMultiLang )
					add_filter('wpm_admin_pages', function ( $config ) use ( $fields ) {
						$config[] = $fields->_id;
						return $config;
					});
			}
		}
		/**
		 * COLLECTION
		 */
		else if ( $fields->type == "collection" ) {
			// Set location, id and order
			$location[] = Location::where( 'post_type', $fields->name );
			$fields->_id = $fields->name;
			$orderHookName = $fields->name;
			// Do not re-declare post as a post type
			if ( $fields->name != 'post' && $fields->name != 'page' ) {
				// Register this post type
				self::$__postTypes[] = $fields->_id;
				// Compute ACF options
				$options = array_merge([
					'label' => $fields->_label[1] ?? $fields->_label[0],
					'public' => !$fields->_hidden,
					'show_ui' => !$fields->_hidden,
					'show_in_rest' => !$fields->_hidden,
					'has_archive' => false,
					'supports' => ['title'],
					'menu_position' => $fields->_position,
					'menu_icon' => $fields->_icon,
				], $fields->_options);
				// Register this post type at WP init
				add_action( 'init', function () use ($fields, $options) {
					register_post_type( $fields->name, $options );
				});
				// Register this custom post type as multi lang
				if ( $isMultiLang )
					add_filter( 'wpm_post_'.$fields->_id.'_config', function () { return []; });
				// Disable sitemap on this post type
				// TODO
				//				if ( isset($fields->sitemap) && $fields->sitemap === false ) {
				//					global $_bowlSitemapRemovedPostTypes;
				//					if (is_null($_bowlSitemapRemovedPostTypes))
				//						$_bowlSitemapRemovedPostTypes = [];
				//					$_bowlSitemapRemovedPostTypes[] = $fields->name;
				//				}
			}
			// All pages
			else if ( $fields->name == 'page' ) {
				// Do not execute on custom page IDs
				foreach ( $fields->_excludedPageIDs as $pageID )
					$location[0]->and( 'page', '!=', $pageID );
				// FIXME : Faire en sorte que le bowl_remove_editor_for_post prenne en compte le "not"
				// FIXME : Car là ça vire pour toutes les pages
				// Remove Wysiwyg editor
				!$fields->_editor && bowl_remove_field_for_post( 'page', 'editor' );
				!$fields->_excerpt && bowl_remove_field_for_post( 'page', 'excerpt');
			}
			// All posts
			else {
				// Remove Wysiwyg editor
				!$fields->_editor && bowl_remove_field_for_post( 'post', 'editor' );
				!$fields->_excerpt && bowl_remove_field_for_post( 'post', 'excerpt');
			}
		}
		/**
		 * PAGE
		 */
		else if ( $fields->type == "page" ) {
			$orderHookName = 'page';
			$fields->_id = $fields->name;
			// This page is associated with IDs
			if ( !empty($fields->_pageIDs) ) {
				// Restrict deletion
				$restrict_post_deletion = function ( $postID ) use ( $fields ) {
					if ( in_array($postID, $fields->_pageIDs) )
						bowl_show_admin_error_message( __("This page cannot be deleted.") );
				};
				add_action('wp_trash_post', $restrict_post_deletion, 10, 1);
				add_action('delete_post', $restrict_post_deletion, 10, 1);
				foreach ( $fields->_pageIDs as $pageID ) {
					// Register location
					$location[] = Location::where( 'page', $pageID );
					// Remove Wysiwyg editor
					!$fields->_editor && bowl_remove_field_for_post( 'page', 'editor', $pageID );
					!$fields->_excerpt && bowl_remove_field_for_post( 'page', 'excerpt', $pageID );
				}
			}
		}
		/**
		 * CUSTOM TEMPLATE
		 */
		if (
			!empty($fields->_template)
			&& (
				$fields->type == "collection"
				|| $fields->type == "page"
			)
		) {
			// We need to scope those field into template to avoid collisions of field names
			// between same post type with different templates (if both have flexible for ex)
			$fields->_id = $fields->_id."--".acf_slugify($fields->_template);
			//			dump("");
			//			dump($fields->_id);
			// Register new template for this post type
			$type = $fields->type == "page" ? "page" : $fields->name;
			add_filter( 'theme_'.$type.'_templates', function ($templates) use ($fields) {
				$templates[ $fields->_id ] = $fields->_template;
				return $templates;
			});
			// Register location
			//			$location[0]->and('post_template', $fields->_id);
			$location[0] = Location::where('post_template', $fields->_id);
		}
		/**
		 * REGISTER GROUPS
		 */
		// Set location
		$fields->location = $location;
		// Patch admin custom screen
		bowl_patch_admin_custom_screen( $fields );
		// Ordered IDs of field groups
		$fieldGroupsIDOrders = [];
		// Process all groups for this screen
		foreach ( $fields->_groups as $key => $groupObject ) {
			$group = $groupObject->toArray();
			// Set a key from screen and group name to avoid collisions across screens
			// Separator with ___ here is important because it will be used to strip
			// back to just "$key" @see BowlFilters::patchScreenNameFields
			$key = acf_slugify($fields->_id ?? $fields->name).'___'.$key;
			$rawFields = isset( $group['rawFields'] ) && $group['rawFields'];
			// Create FieldGroup
			$fieldGroup = array_merge([
				'title' => $group['title'],
				'key' => Key::generate(Key::sanitize($key), 'group'),
				// Set layout to non-null will show collapsible blocks
				'layout' => (isset($group['noBorders']) && $group['noBorders'] ? null : ''),
				// Convert locations to array
				'location' => array_map( fn ($location) => $location->get(), $fields->location ),
				'fields' => (
					// If rawFields is enabled, directly show fields without parent group
				$rawFields ? array_map( fn ($field) => $field->get(), $group['fields'] )
					// By default, show fields inside a nameless group
					: [
					// We use the unique key here to avoid collisions
					Group::make(' ', $key )
						->layout('row')
						->instructions( $group['instructions'] ?? '' )
						->fields( $group['fields'] )
						->get()
				]
				)
			], $group['options']);
			// Store key to order it later
			$fieldGroupsIDOrders[] = 'acf-'.$fieldGroup['key'];
			//			dump($fieldGroup);
			// Register this field group
			register_field_group( $fieldGroup );
		}
		// If we have info on field group orders
		if ( isset($orderHookName) ) {
			if ( !isset(self::$__allFieldGroupOrders[$orderHookName]) )
				self::$__allFieldGroupOrders[ $orderHookName ] = [];
			// Add them by custom post type
			// We do this because for the custom post type "page", we have only 1 hook
			// So we will just concat all field orders for every pages into the CPT "pages"
			// It works because WP admin will use only fields in current page
			self::$__allFieldGroupOrders[ $orderHookName ][] = $fieldGroupsIDOrders;
		}
	}

	// ------------------------------------------------------------------------- AFTER INSTALL

	/**
	 * Re-order custom items in menu.
	 * In order :
	 * - Singletons
	 * - -----
	 * - Posts
	 * - Pages
	 * - Collections
	 * - -----
	 * - ... Other options ...
	 */
	protected static function reorderMenu ( $separatorPosition ) {
		add_action( 'admin_init', function () use ($separatorPosition) {
			global $menu;
			// Sometime menu is not init at this time
			if (!$menu) return;
			// Remove dashboard item
			foreach ( $menu as $index => $section ) {
				if ($section[2] == "index.php" && $section[5] == "menu-dashboard")
					unset($menu[$index]);
			}
			$orderedMenu = [];
			// Get page section to move it after posts
			$pageSection = null;
			foreach ( $menu as $section ) {
				if ( $section[1] != "edit_pages" ) continue;
				$pageSection = $section;
			}
			// Browse and re-order menu
			$separatorIndex = 0;
			foreach ( $menu as $section ) {
				if ( $section[2] == "separator1" || $section[1] == "edit_pages" )
					continue;
				$isPost = $section[1] == "edit_posts" && $section[2] == "edit.php";
				if ( $isPost || $section[1] == "upload_files" && $section[2] == "upload.php" ) {
					$separatorIndex ++;
					$orderedMenu[] = ['','read',"separator$separatorIndex",'','wp-menu-separator'];
				}
				$orderedMenu[] = $section;
				if ( $isPost )
					$orderedMenu[] = $pageSection;
			}
			//			foreach ( $newMenu as $index => $section ) dump($section);
			// Override ordered global menu
			$menu = $orderedMenu;
		});
	}

	/**
	 * After function hook is listened to order field groups vertically.
	 */
	protected static function afterFunctions () {
		// We inject field group orders after all fields are declared
		$allFieldGroupOrders = self::$__allFieldGroupOrders;
		add_action('after_functions', function () use ( $allFieldGroupOrders ) {
			foreach ( $allFieldGroupOrders as $orderHookName => $fieldGroupOrders ) {
				// Concat all field groups orders for this custom post type
				$allFieldGroupOrdersForHook = [];
				foreach ( $fieldGroupOrders as $currentFieldGroupOrder )
					$allFieldGroupOrdersForHook = array_merge($allFieldGroupOrdersForHook, $currentFieldGroupOrder);
				// Hook meta box order for this custom post type
				$hookName = 'get_user_option_meta-box-order_'.$orderHookName;
				add_filter($hookName , function () use ($allFieldGroupOrdersForHook) {
					return [
						// Force order with Yoast on top
						'normal' => join(',', array_merge(
							[ 'wpseo_meta' ],
							$allFieldGroupOrdersForHook
						))
					];
				});
			}
		});
	}

	// ------------------------------------------------------------------------- GATHERING INSTALLED FIELDS

	static function getMatchingInstalledFieldsForPost ( WP_Post $post ) {
		$matchingFields = [];
		foreach ( self::$__installedFields as $installedField ) {
			// Browse locations to check if this post match
			/** @var Location $locations */
			foreach ( $installedField->location as $locations ) {
				// Only check first location (check "or" but do not check "and")
				$location = $locations->get()[0];
				//dump($location);
				$isMatching = false;
				// Page filtering
				// TODO : Exclude ids of Location->and( $excluded ) in BowlFields
				if ( $post->post_type == "page" ) {
					// Filter page of a specific ID
					if ( $location['param'] == "page" ) {
						if ( $location['value'] == $post->ID )
							$isMatching = true;
					}
					// Filter a page with a specific template
					if ( $location['param'] == 'post_template' ) {
						$templateName = get_page_template_slug( $post->ID );
						if ( !empty($templateName) && $location['value'] == $templateName )
							$isMatching = true;
					}
				}
				// Regular post filtering
				else if ( $post->post_type == "post" ) {
					// TODO : All posts matching
					if ( $location['param'] == 'post_template' ) {
						$templateName = get_page_template_slug( $post->ID );
						if ( !empty($templateName) && $location['value'] == $templateName )
							$isMatching = true;
					}
				}
				// Custom post type filtering
				else if ( $location['param'] == "post_type" ) {
					if ( $location['value'] == $post->post_type )
						$isMatching = true;
				}
				// Register this field as matching
				if ( $isMatching )
					$matchingFields[] = $installedField;
			}
		}
		return $matchingFields;
	}

	static function getInstalledSingletonsByName () {
		$fields = [];
		foreach ( self::$__installedFields as $installedField ) {
			// Browse locations to check if this post match
			/** @var Location $locations */
			foreach ( $installedField->location as $locations ) {
				$location = $locations->get();
				if ( !isset($location[0]) ) continue;
				if (
					$location[0]['param'] == "options_page"
					&& $location[0]['operator'] == "=="
				) {
					// FIXME : Why was that in an array ?
					//					if ( !isset($fields[$location[0]['value']]) )
					//						$fields[$location[0]['value']] = [];
					//					$fields[$location[0]['value']][] = $installedField;
					$fields[$location[0]['value']] = $installedField;
				}
			}
		}
		return $fields;
	}

	static function getAllInstalledFields ():array { return self::$__installedFields; }

	static function getFieldsFilterHandlers ( BowlFields $fields ):array {
		return $fields->_filterHandlers;
	}
	static function getFieldsGroups (BowlFields $fields):array {
		return $fields->_groups;
	}

	/**
	 * Get nice template name (not slugified one)
	 */
	static function getFieldsTemplateName (BowlFields $fields):string {
		return $fields->_template;
	}

	// ------------------------------------------------------------------------- FACTORY

	/**
	 * Create a Singleton fields configuration, which will appear on top of admin bar.
	 * Singleton are straight config page without collection of object.
	 * @param string $name
	 * @return BowlFields
	 * @throws Exception
	 */
	static function createSingletonFields ( string $name ) {
		return new BowlFields("singleton", $name);
	}

	/**
	 * Create a Collection fields configuration, which will appear bellow Singletons in admin bar.
	 * Collections are equivalent to custom post types.
	 * @param string $name
	 * @return BowlFields
	 * @throws Exception
	 */
	static function createCollectionFields ( string $name ) {
		return new BowlFields("collection", $name );
	}

	/**
	 * Create a new Page fields configuration. It will be usable from "Pages" in admin bar.
	 * Page fields can be limited to a specific page template name,
	 * or a specific set of page IDs. If limited to page IDs, it will prevent deletion.
	 * @param string $name
	 * @param string|array $templateOrPageIDs Template name to create as string,
	 * 										  or list of page IDs to associate with.
	 * @return BowlFields
	 * @throws Exception
	 */
	static function createPageFields ( string $name, string|array $templateOrPageIDs ) {
		$fields = new BowlFields("page", $name);
		if ( is_array($templateOrPageIDs) )
			$fields->_pageIDs = $templateOrPageIDs;
		else if ( is_string($templateOrPageIDs) )
			$fields->_template = $templateOrPageIDs;
		return $fields;
	}

	/**
	 * Create a default Page fields configuration.
	 * This will be applied to all Pages without template or not configured with
	 * a page ID (@see BowlFields::createPageFields).
	 * Default configuration can be created only once for obvious reasons.
	 * @param array $excludedPageIDs Exclude those page IDs from this default configuration.
	 * @return BowlFields
	 */
	static function createDefaultPageFields ( array $excludedPageIDs = [] ) {
		$fields = new BowlFields("collection", "page");
		$fields->_excludedPageIDs = $excludedPageIDs;
		return $fields;
	}

	/**
	 * Create a default Post fields configuration.
	 * This will be applied to all Posts.
	 * @return BowlFields
	 */
	static function createDefaultPostFields () {
		return new BowlFields("collection", "post");
	}


	static function createTemplatePostFields ( string $templateName ) {
		$fields = new BowlFields("collection", "post");
		$fields->_template = $templateName;
		return $fields;
	}

	// ------------------------------------------------------------------------- INIT

	protected string $_id;

	protected array $_excludedPageIDs = [];

	protected array $_pageIDs = [];

	protected string $_template = "";

	public array $location = [];

	public function __construct ( public string $type, public string $name ) {
		if ( !in_array($type, ["collection", "singleton", "page"]) )
			throw new \Exception("Invalid BowlFields type $type.");
		// Default menu position
		if ( $type == "singleton" )
			$this->_position = 1;
		else if ( $type == "collection" )
			$this->_position = 6;
		// Default name
		//		$this->_label = [$name];
	}

	// ------------------------------------------------------------------------- MENU

	protected array $_label = [];
	protected string $_icon = "";
	protected int $_position = 0;

	/**
	 * Add this item to the menu bar.
	 * @param array $label
	 * @param string|null $icon https://developer.wordpress.org/resource/dashicons/
	 * @param int|null $position
	 * @return $this
	 */
	public function menu ( array $label, string $icon = null, int $position = null ) {
		$this->_label = $label;
		if ( !is_null($icon) )
			$this->_icon = $icon;
		if ( !is_null($position) )
			$this->_position += $position;
		// FIXME : Not precise enough, should be hidden from admin menu only
		$this->_hidden = false;
		return $this;
	}

	// ------------------------------------------------------------------------- ACF OPTIONS

	protected array $_options = [];
	public function options ( array $options ) {
		$this->_options = $options;
		return $this;
	}

	// ------------------------------------------------------------------------- MULTILANG

	protected bool $_multiLang = false;
	public function multiLang ( bool $multiLang = true ) {
		$this->_multiLang = $multiLang;
		return $this;
	}

	// ------------------------------------------------------------------------- HIDDEN

	// FIXME : Hidden for admin menu / Hidden from rest API / Hidden from router
	protected bool $_hidden = true;
	public function hidden ( bool $hidden ) {
		$this->_hidden = $hidden;
		return $this;
	}

	// ------------------------------------------------------------------------- EDITOR

	protected bool $_editor = true;
	public function editor ( bool $editor ) {
		$this->_editor = $editor;
		return $this;
	}

	// ------------------------------------------------------------------------- EXCERPT

	protected bool $_excerpt = true;
	public function excerpt ( bool $excerpt ) {
		$this->_excerpt = $excerpt;
		return $this;
	}

	// ------------------------------------------------------------------------- GROUPS

	protected array $_groups = [];
	public function addGroup ( string $key, string $title = null ) {
		return $this->attachGroup( $key, new BowlGroupFields( $title ?? $key ) );
	}
	public function attachGroup ( string $key, BowlGroupFields $group ) {
		$this->_groups[ $key ] = $group;
		return $group;
	}

	// ------------------------------------------------------------------------- FILTERING DATA

	protected array $_filterHandlers = [];
	public function addFilter ( callable $filterHandler ) {
		$this->_filterHandlers[] = $filterHandler;
		return $this;
	}
}

// ----------------------------------------------------------------------------- BOWL GROUP FIELD

class BowlGroupFields
{
	protected array $_groupData = [
		'fields' => [],
		'options' => [],
		'multiLang' => false,
	];

	public function toArray () { return $this->_groupData; }

	public function __construct ( string $title ) {
		$this->_groupData['title'] = $title;
	}

	// ------------------------------------------------------------------------- CHAINED CONFIGURATORS

	public function rawFields ( bool $value = true ) {
		$this->_groupData['rawFields'] = $value;
		return $this;
	}
	public function noBorders ( bool $value = true ) {
		$this->_groupData['noBorders'] = $value;
		return $this;
	}
	public function fields ( array $fields ) {
		$this->_groupData['fields'] += $fields;
		return $this;
	}
	public function options ( array $options ) {
		$this->_groupData['options'] += $options;
		return $this;
	}
	public function multiLang ( bool $multiLang = true ) {
		$this->_groupData['multiLang'] = $multiLang;
		return $this;
	}
	public function instructions ( string $instructions ) {
		$this->_groupData['instructions'] = $instructions;
		return $this;
	}
}