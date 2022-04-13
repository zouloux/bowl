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
	public DateTime $postDate;
	public DateTime $postModifiedDate;
	public array $categories = [];
	public BowlAuthor|null $author = null;

	protected WP_Post $_source;
	public function getSource ():WP_Post { return $this->_source; }

	public function __construct ( WP_Post $post, public array $fields = [], $fetchCategories = false, $fetchAuthor = false ) {
		// Save original WP post
		$this->_source = $post;
		// Get post properties
		$this->id = $post->ID;
		$this->title = $post->post_title;
		$this->href = get_permalink( $post );
		$this->type = $post->post_type;
		$this->isPublished = $post->post_status == "publish";
		$this->parentPostID = $post->post_parent;
		$this->postDate = new \DateTime( $post->post_date );
		$this->postModifiedDate = new \DateTime( $post->post_date );
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
		// Fetch categories
		if ( $fetchCategories )
			$this->fetchCategories();
		// Fetch author
		if ( $fetchAuthor )
			$this->fetchAuthor();
	}

	public function fetchCategories () {
		$categoryIDS = wp_get_post_categories( $this->id );
		if ( !empty($categoryIDS) )
			foreach ( $categoryIDS as $categoryID )
				$this->categories[] = BowlRequest::getCategoryById( $categoryID );
	}

	public function fetchAuthor () {
		$authorID = intval( $this->_source->post_author );
		$this->author = new BowlAuthor( $authorID );
	}
}