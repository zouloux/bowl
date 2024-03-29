<?php

// TODO : Fetch categories if type is post


class BowlPost
{
	// -------------------------------------------------------------------------

	/**
	 * Template name is post_type,
	 * But for pages it will be named from the custom field name.
	 */
	static function getTemplateNameFromWPPost ( WP_Post $post ):string {
		$template = $post->post_type;
		// If post type is page, we need to check template name from installed filters
		if ( $post->post_type === "page" || $post->post_type === "post" ) {
			// Browse matching fields to get the best template name
			$matchingFields = BowlFields::getMatchingInstalledFieldsForPost( $post );
			/** @var BowlFields $field */
			foreach ( $matchingFields as $field ) {
				// Check this field is a post template
				$fieldTemplateName = BowlFields::getFieldsTemplateName($field);
				if ( !empty($fieldTemplateName) ) {
					$templateID = get_page_template_slug( $post->ID );
					$exploded = explode("--", $templateID, 2);
					$template = ( count($exploded) == 2 ? $exploded[1] : $templateID );
					$template = $post->post_type.'-'.$template;
					break;
				}
				// Otherwise, get field name as template name
				else if ( !empty($field->name) ) {
					$template = $field->name;
					break;
				}
			}
		}
		return $template;
	}

	// -------------------------------------------------------------------------

	public int $id;
	public string $title;
	public string $href;
	public string $type;
	public string $content;
	public string $excerpt;
	public BowlImage|null $thumbnail = null;
	public bool $isPublished;
	public string $template;
	public int $parentPostID;
	public DateTime $date;
	public DateTime $modified;
	public array $tags = [];
	public array $categories = [];
	public BowlAuthor|null $author = null;

	protected WP_Post $_source;
	public function getSource ():WP_Post { return $this->_source; }

	public function __construct ( WP_Post $post, public array $fields = [], $fetchTerms = false, $fetchAuthor = false ) {
		// Save original WP post
		$this->_source = $post;
		// Get post properties
		$this->id = $post->ID;
		$this->title = bowl_fix_translated_string($post->post_title ?? "");
		$this->href = get_permalink( $post );
		$this->type = $post->post_type;
		$this->isPublished = $post->post_status == "publish";
		$this->parentPostID = $post->post_parent;
		$this->date = new \DateTime( $post->post_date );
		$this->modified = new \DateTime( $post->post_modified );
		// Clean content and excerpt
		$this->content = BowlFilters::filterRichContent($post->post_content);
		$this->excerpt = BowlFilters::filterRichContent($post->post_excerpt);
		// Get thumbnail
		$thumbnailID = get_post_thumbnail_id( $this->id );
		if ( $thumbnailID != 0 ) {
			$src = wp_get_attachment_image_src( $thumbnailID, 0 );
			$image = wp_get_attachment_metadata( $thumbnailID );
			if ( is_array($src) && is_array($image) && !empty($image["file"]) ) {
				$this->thumbnail = new BowlImage([
					"ID" => $thumbnailID,
					"type" => "image",
					"filename" => $image["file"],
					"filesize" => 0, // FIXME
					"url" => $src[0],
					"width" => $image["width"],
					"height" => $image["height"],
					"sizes" => $image["sizes"],
				]);
			}
		}
		// Get template name
		$this->template = self::getTemplateNameFromWPPost( $post );
		// Fetch terms
		if ( $fetchTerms )
			$this->fetchTerms();
		// Fetch author
		if ( $fetchAuthor )
			$this->fetchAuthor();
	}

	public function fetchTerms () {
		$categoryIDS = wp_get_post_categories( $this->id );
		if ( !empty($categoryIDS) )
			foreach ( $categoryIDS as $categoryID )
				$this->categories[] = BowlRequest::getCategoryById( $categoryID );
		$tags = wp_get_post_terms( $this->id, 'post_tag', [ "fields" => "all" ] );
		$this->tags = BowlRequest::filterTags( $tags );
	}

	public function fetchAuthor () {
		$authorID = intval( $this->_source->post_author );
		$this->author = new BowlAuthor( $authorID );
	}

	// ------------------------------------------------------------------------- TO ARRAY

	/**
	 * Serialize a bowl element into an array recursively.
	 * Will skip _source to avoid having deep inclusion of WP_Post
	 */
	public static function recursiveToArray ( $element ) {
		if ( is_object( $element ) )
			$vars = get_object_vars( $element );
		else if ( is_array($element) )
			$vars = $element;
		else
			throw new Exception("BowlPost::recursiveToArray // Invalid element.");
		unset( $vars["_source"] );
		foreach ( $vars as $key => $value )
			if ( is_object($value) || is_array($value) )
				$vars[ $key ] = self::recursiveToArray( $value );
		return $vars;
	}

	public function toArray () {
		return self::recursiveToArray($this);
	}
}