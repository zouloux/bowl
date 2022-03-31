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
			$matchingFields = BowlFields::getMatchingInstalledFieldsForPost( $post );
			/** @var BowlFields $field */
			foreach ( $matchingFields as $field ) {
				$templateID = get_page_template_slug( $post->ID );
				$exploded = explode("--", $templateID, 2);
				$template = ( count($exploded) == 2 ? $exploded[1] : $templateID );
			}
			$template = $post->post_type.'-'.$template;
		}
		return $template;
	}

	// -------------------------------------------------------------------------

	public int $id;
	public int $timestamp;
	public string $title;
	public string $href;
	public string $type;
	public string $content;
	public string $excerpt;
	public bool $isPublished;
	public string $template;
	public int $authorID;
	public int $parentPostID;

	protected WP_Post $_source;
	public function getSource ():WP_Post { return $this->_source; }

	public function __construct ( WP_Post $post, public array $fields = [] ) {
		// Save original WP post
		$this->_source = $post;
		// Get post properties
		$this->id = $post->ID;
		$this->timestamp = (new \DateTime($post->post_date))->getTimestamp();
		$this->title = $post->post_title;
		$this->href = get_permalink( $post );
		$this->type = $post->post_type;
		$this->isPublished = $post->post_status == "publish";
		$this->parentPostID = $post->post_parent;
		$this->authorID = intval( $post->post_author );
		// Clean content and excerpt
		$this->content = BowlFilters::filterRichContent($post->post_content);
		$this->excerpt = BowlFilters::filterRichContent($post->post_excerpt);
		// Get template name
		$this->template = self::getTemplateNameFromWPPost( $post );
	}
}