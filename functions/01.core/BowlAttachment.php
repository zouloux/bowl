<?php

// TODO : BowlVideo -> OUI
// TODO : BowlAudio -> OUI
// TODO : BowlDocument (text / html / txt / doc / csv ...) -> Osef pour l'instant
// TODO : BowlZip -> Osef pour l'instant

// -----------------------------------------------------------------------------

class BowlAttachment
{
	public string $type;
	public int $id;
	public string $title;
	public string $fileName;
	public int $fileSize;
	public string $alt;
	public string $description;
	public string $caption;
	public string $href;

	protected array $_source = [];
	public function getSource ():array { return $this->_source; }

	public function __construct ( array $source ) {
		$this->_source = $source;

		$this->type = $source['type'];
		$this->id = $source['ID'];

		$this->fileName = $source['filename'];
		$this->fileSize = $source['filesize'];
		$this->href = $source['url'];

		$this->title = bowl_fix_translated_string($source['title'] ?? "");
		$this->alt = bowl_fix_translated_string($source['alt'] ?? "");// FIXME : In image only or media ?
		$this->description = bowl_fix_translated_string($source['description'] ?? "");
		$this->caption = bowl_fix_translated_string($source['caption'] ?? "");
	}
}

class BowlGraphicAttachment extends BowlAttachment
{
	public int $width;
	public int $height;
	public float $ratio;

	public function __construct ( array $source ) {
		// Relay to BowlAttachment
		parent::__construct($source);
		// Width / height / ratio
		$this->width = $source["width"];
		$this->height = $source["height"];
		$this->ratio = ($this->width /  $this->height) ?? 1;
	}
}

class BowlImage extends BowlGraphicAttachment
{
	protected static function mimeTypeToFormat ( string $mimeType ) {
		$mimeToFormat = [
			"image/jpeg" => "jpg",
			"image/jpg" => "jpg",
			"image/gif" => "gif",
			"image/png" => "png",
			"image/webp" => "webp",
		];
		return $mimeToFormat[ $mimeType ] ?? "unknown";
	}

	/** @var BowlImageFormat[] $formats */
	public array $formats = [];

	public array $blurhash = [];

	public function __construct ( array $source ) {
		// Relay to BowlGraphicAttachment
		parent::__construct($source);
		// Width / height / ratio
		$this->width = $source["width"];
		$this->height = $source["height"];
		$this->ratio = ($this->width /  $this->height) ?? 1;
		// Get blur hash
		$blurHash = get_post_meta($this->id, "blur_hash", true);
		if ( is_string($blurHash) ) {
			try {
				$blurHash = json_decode($blurHash, true);
			}
			catch ( Exception $e ) {}
			if ( is_array($blurHash) )
				$this->blurhash = $blurHash;
		}
		// Native formats (same as original mime types but resized)
		foreach ( $source['sizes'] as $key => $size ) {
			if ( isset($source['sizes'][$key.'-width']) && isset($source['sizes'][$key.'-height']) ) {
				$this->formats[] = new BowlImageFormat([
					'name' => $key,
					'href' => $size,
					'width' => $source['sizes'][$key.'-width'],
					'height' => $source['sizes'][$key.'-height'],
					'format' => self::mimeTypeToFormat( $source['mime_type'] ),
				]);
			}
		}
		// Converted to other formats
		$webpSizes = get_post_meta($this->id, "webp_sizes", true);
		if ( is_string($webpSizes) ) {
			try {
				$webpSizes = json_decode($webpSizes, true);
			}
			catch ( Exception $e ) {}
			if ( is_array($webpSizes) ) {
				$folderPath = dirname($this->href);
				foreach ( $webpSizes as $key => $size ) {
					$this->formats[] = new BowlImageFormat([
						'name' => $key,
						'href' => $folderPath.'/'.$size['file'],
						'width' => $size['width'],
						'height' => $size['height'],
						'format' => self::mimeTypeToFormat( $size['mime_type'] ),
					]);
				}
			}
		}
	}
}

class BowlImageFormat
{
	public int $width;
	public int $height;
	public string $href;
	public string $name;
	/**
	 * @var string "jpg" / "png" / "gif" / "webp"
	 */
	public string $format;

	public function __construct ( array $source ) {
		foreach ( $source as $key => $value )
			$this->$key = $value;
	}
}


class BowlVideo extends BowlGraphicAttachment {}