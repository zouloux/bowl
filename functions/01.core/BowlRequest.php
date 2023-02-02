<?php

class BowlRequest
{
	// ------------------------------------------------------------------------- GET WP POST BY PATH

	/**
	 * Get a Wordpress Post from its path.
	 * @param string $path Path can be relative from base or absolute with scheme and host.
	 * @return WP_Post|null
	 */
	static function getWPPostByPath ( string $path ):?WP_Post {
		// FIXME : Filter out attachments (uploads) EX : /fr/test-collection/test/rail/
		$postID = url_to_postid( $path );
		if ( $postID === 0 ) {
			// Get host
			$homeURL = get_home_url();
			$homeURL = bowl_keep_host_from_href( $homeURL );
			// Prepend with request
			$path = $homeURL.$path;
			// Try to get
			$postID = url_to_postid( $path );
		}
		return $postID === 0 ? null : get_post( $postID );
	}

	// ------------------------------------------------------------------------- GET PAGE DATA

	static function getCurrentBowlPost ( bool $fetchFields = true, array|bool $autoFetchPostsWithTemplate = true ):?BowlPost {
		// FIXME : Filter out attachments (uploads) EX : /fr/test-collection/test/rail/
		// Try from global and fallback to get_post
		global $post;
		$post ??= get_post();
		// Not found try with manual post by path
		if ( is_null($post) )
			return null;
		// Post found
		return BowlFilters::filterPost( $post, $fetchFields, $autoFetchPostsWithTemplate );
	}

	static function getBowlPostByPath ( string $path, bool $fetchFields = true, array|bool $autoFetchPostsWithTemplate = [] ):?BowlPost {
		// FIXME : Filter out attachments (uploads) EX : /fr/test-collection/test/rail/
		$post = self::getWPPostByPath( $path );
		return BowlFilters::filterPost( $post, $fetchFields, $autoFetchPostsWithTemplate );
	}

	static function getBowlPostByID ( string|int $postID, bool $fetchFields = true, array|bool $autoFetchPostsWithTemplate = [] ):?BowlPost {
		// FIXME : Filter out attachments (uploads) EX : /fr/test-collection/test/rail/
		$post = get_post( $postID );
		return BowlFilters::filterPost( $post, $fetchFields, $autoFetchPostsWithTemplate );
	}

	static function getAllBowlPosts ( array $postTypes = ["page", "posts"], bool $fetchFields = false, array|bool $autoFetchPostsWithTemplate = [] ) {
		// TODO : Retrieve all custom post types that are not hidden
		// $postTypes == true -> all post types
		// Get a list of all published pages from WordPress.
		$posts = [];
		foreach ( $postTypes as $postType )
			$posts = array_merge($posts, get_posts([ "numberposts" => -1, "post_type" => $postType ]) );
		$bowlPosts = [];
		foreach ( $posts as $page )
			$bowlPosts[] = BowlFilters::filterPost( $page, $fetchFields, $autoFetchPostsWithTemplate );
		return $bowlPosts;
	}

	// ------------------------------------------------------------------------- GET CPT OBJECTS

	static function getSingleton ( string $singletonName, array|bool $autoFetchPostsWithTemplate = []  ) {
		$singletons = BowlFields::getInstalledSingletonsByName();
		if ( !isset($singletons[$singletonName]) )
			return null;
		/** @var BowlFields $singleton */
		$singleton = $singletons[ $singletonName ];
		$data = [];
		$groups = BowlFields::getFieldsGroups( $singleton );
		/** @var BowlGroupFields $group */
		foreach ( $groups as $groupKey => $group ) {
			// FIXME : This is not super clean because we can have keys colliding
			// FIXME : Raw fields vs group fields are not stored the same way with ACF ...
			$groupData = get_field( 'toplevel-page-'.$singletonName.'___'.$groupKey, 'option' );
			// Try with prefix, and without
			if ( is_null($groupData) )
				$groupData = get_field( $singletonName.'___'.$groupKey, 'option' );
			if ( is_null($groupData) )
				$groupData = get_field( $groupKey, 'option' );
			if ( is_null($groupData) )
				continue;
			$data[ $groupKey ] = $groupData;
		}
		// Filter all data
		return BowlFilters::filterSingletonData( $singleton, $data, $autoFetchPostsWithTemplate );
	}

	static function getCollection ( string $name, array $options = [] ) {
		// TODO : Which API ?
		// TODO : Search ?
		// TODO : Pagination ?
		// TODO : Get all posts ?
	}

	// ------------------------------------------------------------------------- POSTS

	/**
	 * Get bowl posts with get_posts options.
	 * @param array $options @see https://developer.wordpress.org/reference/functions/get_posts/
	 * @param bool $fetchFields
	 * @param array|bool $autoFetchPostsWithTemplate
	 * @return array
	 */
	static function getBowlPosts ( array $options, bool $fetchFields = false, array|bool $autoFetchPostsWithTemplate = [] ) {
		$posts = get_posts( $options );
		$output = [];
		foreach ( $posts as $post )
			$output[] = BowlFilters::filterPost( $post, $fetchFields, $autoFetchPostsWithTemplate );
		return $output;
	}

	// ------------------------------------------------------------------------- ATTACHMENTS

	static function getAttachmentByPath ( string $path ):?BowlAttachment {
		return null;
	}

	static function getAttachmentByID ( int $id ):?BowlAttachment {
		return null;
	}

	// ------------------------------------------------------------------------- SEARCH

	// TODO : WP Search query

	static function searchPost ( string $query, array $options ) {

	}

	// ------------------------------------------------------------------------- SUB PAGES

	static function getSubPagesOfPage ( string $pageID, bool $fetchFields = true, array|bool $autoFetchPostsWithTemplate = [], int $depth = 1, string $order = 'menu_order' ) {
		$pages = get_pages([
			'child_of' => $pageID,
			'depth' => $depth,
			'sort_column' => $order,
		]);
		$bowlPosts = [];
		foreach ( $pages as $page ) {
			$bowlPost = BowlFilters::filterPost( $page, $fetchFields, $autoFetchPostsWithTemplate );
			if ( is_null($bowlPost) ) continue;
			$bowlPosts[] = $bowlPost;
		}
		return $bowlPosts;
	}

	// ------------------------------------------------------------------------- CATEGORIES

	// Cache categories request because WP seems to not cache them
	protected static array $__cachedCategories = [];

	/**
	 * Get all categories and cache them.
	 * @param bool $forceRefresh Will force cache to be cleared.
	 * @return array
	 */
	static function getCategories ( bool $forceRefresh = false ) {
		if ( $forceRefresh )
			self::$__cachedCategories = [];
		if ( empty(self::$__cachedCategories) ) {
			$categories = get_categories([
				'hide_empty' => false
			]);
			// First, filter all categories and store them into the cache
			foreach ( $categories as $term )
				self::$__cachedCategories[] = new BowlTerm( $term );
			// Now we have all categories filtered and cached, we can query them
			foreach ( self::$__cachedCategories as $category ) {
				$children = get_term_children($category->id, 'category');
				foreach ( $children as $childID ) {
					$cat = self::getCategoryById( $childID );
					if (is_null($cat)) continue;
					$category->children[] = $cat;
				}
			}
		}
		return self::$__cachedCategories;
	}

	static function getCategoryHierarchy () {
		$hierarchyOutput = [];
		$categories = self::getCategories();
		foreach ( $categories as $category ) {
			if ( $category->parentID == 0 )
				$hierarchyOutput[] = $category;
		}
		return $hierarchyOutput;
	}

	/**
	 * Get a category by its ID.
	 * Can be in a loop, categories are cached.
	 */
	static function getCategoryById ( int $id ) {
		$categories = self::getCategories();
		foreach ( $categories as $category ) {
			if ( $category->id == $id )
				return $category;
		}
		return null;
	}

	/**
	 * Get a category by its slug
	 */
	static function getCategoryBySlug ( string $slug ) {
		$categories = self::getCategories();
		foreach ( $categories as $category ) {
			if ( $category->slug == $slug )
				return $category;
		}
		return null;
	}

	// ------------------------------------------------------------------------- TAGS

	static function filterTag ( WP_Term $tag ) {
		return new BowlTerm( $tag );
	}

	static function filterTags ( array $tags ) {
		$filteredTags = [];
		foreach ( $tags as $tag )
			$filteredTags[] = self::filterTag( $tag );
		return $filteredTags;
	}
}
