<?php

class BowlFilters
{
	// ------------------------------------------------------------------------- PATCHES

	/**
	 * Patch all "${screenName}___${fieldName}" to "$fieldName"
	 */
	protected static function patchScreenNameFields ( &$data ) {
		foreach ( $data as $key => $value ) {
			$split = explode("___", $key, 2);
			if ( count($split) != 2 ) continue;
			$data[ $split[1] ] = $value;
			unset( $data[$key] );
		}
		return $data;
	}

	/**
	 * Will patch fields recursively :
	 * - Translated keys (fr_keyName to keyName)
	 * - Clear nodes with enabled=false
	 * - Convert media to BowlAttachment objects
	 * - Convert WP_Post to BowlPost, and will fetch fields recursively
	 * - Key of flexibles "acf_fc_layout" to "type"
	 * @param array $data
	 * @param array|bool $autoFetchPostsWithTemplate
	 * @return array
	 */
	static function recursivePatchFields ( array &$data, array|bool $autoFetchPostsWithTemplate = [] ):array {
		$locale = bowl_get_current_locale();
		// Filter translated keys
		// Out of main loop because altering $data (otherwise node can be duplicated)
		foreach ( $data as $key => &$node ) {
			if ( stripos($key, $locale.'_') === 0 ) {
				$oldKey = $key;
				// Convert it without locale prefix
				$newKey = substr($key, strlen($locale) + 1);
				// If a field with this key already exists, keep the _
				if ( isset($data[$newKey]) )
					$newKey = substr($key, strlen($locale));
				// Replace value
				$data[$newKey] = $node;
				unset( $data[$oldKey] );
			}
		}
		// Browse node properties
		foreach ( $data as $key => &$node ) {
			// Remove all data for a node when field enabled=false
			if ( is_array($node) && isset($node['enabled']) ) {
				if ( !$node['enabled'] ) {
					unset( $data[ $key ] );
					continue;
				}
				else {
					unset( $node['enabled'] );
				}
				//				$data[ $key ] = [ 'enabled' => false ];
				//				continue;
			}
			// Convert flexible layouts to "type"
			if ( $key === 'acf_fc_layout' ) {
				$data += ['type' => $data[$key]];
				unset( $data[$key] );
			}
			// Filter conditional groups generated with ...bowl_create_conditional_group()
			// Convert field groups like _webAppCapabilities_group_selected = 'ok'
			// To something clean : webAppCapabilities => ["selected" => true, ...]
			if ( is_array($node) ) {
				// Get all keys of this node
				$nk = array_keys($node);
				$extractedKeyName = null;
				$extractedValue = null;
				// Browse keys
				foreach ( $nk as $k ) {
					// Check if a key starts with $_
					// The first is always the selector ( ButtonGroup )
					// This is why the $extractedKeyName var is reset out of this scope
					if ( !str_starts_with($k, "\$_") ) continue;
					if ( is_null($extractedKeyName) ) {
						// Get key and value for this group
						$parts = explode("_", $k, 3);
						if ( count($parts) != 3 ) continue;
						$extractedKeyName = $parts[1];
						$extractedValue = $node[ $k ];
					}
					else {
						// Create new node with correct key and selected value
						if ( !isset($node[ $extractedKeyName ]) )
							$node[ $extractedKeyName ] = [
								"selected" => $extractedValue
							];
						// Compute searched selected key
						$searchedSelectedKey = "\$_".$extractedKeyName.'_group_'.$extractedValue;
						// We are on the selected group, inject data into the new node
						if ( $k === $searchedSelectedKey )
							$node[ $extractedKeyName ] += $node[ $k ];
					}
					// Unset all ( the selected one and not selected groups )
					unset( $node[$k] );
				}
			}
			// Filter WP_Post to BowlPost and auto fetch fields and sub posts
			if ( $node instanceof WP_Post ) {
				// If we need to fetch fields for this post
				$fetchFields = (
				is_bool($autoFetchPostsWithTemplate) ? $autoFetchPostsWithTemplate
					: in_array(BowlPost::getTemplateNameFromWPPost( $node ), $autoFetchPostsWithTemplate)
				);
				// Recursively convert to BowlPost
				$data[ $key ] = BowlFilters::filterPost( $node, $fetchFields, $autoFetchPostsWithTemplate );
				continue;
			}
			// Filter media
			if (
				is_array($node)
				&& isset($node['type'])
				&& isset($node['subtype'])
				&& isset($node['mime_type'])
			) {
				$data[$key] = self::filterAttachment( $node );
				continue;
			}
			// Recursive patch filter
			if ( is_array($node) )
				$data[ $key ] = BowlFilters::recursivePatchFields( $node, $autoFetchPostsWithTemplate );
		}
		return $data;
	}

	// ------------------------------------------------------------------------- BOWL POST FILTER

	protected static array $__bowlPostFilters = [];
	static function registerBowlPostsFieldsFilter ( callable $handler, $afterFieldFiltering = true ) {
		self::$__bowlPostFilters[] = [$afterFieldFiltering, $handler];
	}

	/**
	 * Filter a WP_Post and convert it to a BowlPost.
	 * Will fetch / parse / clean all associated fields.
	 * @param WP_Post|null $post
	 * @param bool $fetchFields
	 * @param array|bool $autoFetchPostsWithTemplate
	 * @return BowlPost|null
	 */
	static function filterPost ( WP_Post|null $post, bool $fetchFields = true, array|bool $autoFetchPostsWithTemplate = []):?BowlPost {
		if ( is_null($post) )
			return null;
		// Do not fetch fields
		if ( !$fetchFields )
			return new BowlPost( $post );
		// Get raw fields associated to this post
		$fields = get_fields( $post->ID );
		if ( $fields === false ) $fields = [];
		// Patch screen names to remove uniqueness part
		self::patchScreenNameFields( $fields );
		// Filter with global before filter
		foreach ( self::$__bowlPostFilters as $filter )
			if ( !$filter[0] )
				$fields = $filter[1]( $fields, $post );
		// Recursive patch fields after pre-filter be before fields filter
		$fields = self::recursivePatchFields( $fields, $autoFetchPostsWithTemplate );
		// Get matching installed fields and browse them
		$matchingFields = BowlFields::getMatchingInstalledFieldsForPost( $post );
		/** @var BowlFields $field */
		foreach ( $matchingFields as $field ) {
			// Get field filter and filter raw fields through them
			$handlers = BowlFields::getFieldsFilterHandlers( $field );
			foreach ( $handlers as $handler)
				$fields = $handler( $fields, $post );
		}
		// Filter with global after filter
		foreach ( self::$__bowlPostFilters as $filter )
			if ( $filter[0] )
				$fields = $filter[1]( $fields, $post );
		// Create a new bowl post from original WP_Post and parsed fields
		return new BowlPost( $post, $fields, true, true );
	}

	// ------------------------------------------------------------------------- FILTER SINGLETON

	/**
	 * Filter a Singleton Field and its data.
	 * Should not be used directly.
	 * Data will be recursively patched and filtered by BowlFields filters.
	 * @see BowlRequest::getSingleton()
	 * @param BowlFields $singletonFields BowlFields to filter (holds filter handlers)
	 * @param array $singletonData Singleton data, gathered with get_field.
	 * @param array|bool $autoFetchPostsWithTemplate
	 * @return array
	 */
	static function filterSingletonData ( BowlFields $singletonFields, array $singletonData, array|bool $autoFetchPostsWithTemplate = [] ) {
		// Recursive patch fields after pre-filter be before fields filter
		$singletonData = self::recursivePatchFields( $singletonData, $autoFetchPostsWithTemplate );
		// Get filters and apply them
		$filters = BowlFields::getFieldsFilterHandlers( $singletonFields );
		foreach ( $filters as $filter )
			$singletonData = $filter( $singletonData );
		return $singletonData;
	}

	// ------------------------------------------------------------------------- OTHER FILTERS

	/**
	 * Filter rich content and
	 * @param string $content
	 * @return string
	 */
	static function filterRichContent ( string $content ):string {
		// Remove HTML comments
		$content = preg_replace("/<!--(.*)-->/Uis", "", $content);
		// Remove multiple line jumps
		return preg_replace("/[\r\n]+/", "\n", $content);
	}

	/**
	 * Convert WP Attachment to a BowlAttachment
	 * @param array $node
	 * @return BowlAttachment
	 */
	static function filterAttachment ( array $node ):BowlAttachment {
		if ( $node["type"] === "image" )
			return new BowlImage( $node );
		else if ( $node["type"] === "video")
			return new BowlVideo( $node );
		else
			return new BowlAttachment( $node );
	}
}