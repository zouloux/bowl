<?php

class BowlTerm
{
	public int $id;
	public string $name;
	public string $slug;
	public string $href;
	public array $children = [];

	protected WP_Term $_source;
	public function getSource ():WP_Term { return $this->_source; }

	public function __construct ( WP_Term $source ) {
		$this->_source = $source;
		$this->id = $source->term_id;
		$this->name = $source->name;
		$this->slug = $source->slug;
		$this->href = bowl_remove_base_from_href( get_category_link( $source ) );
	}
}