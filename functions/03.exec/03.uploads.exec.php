<?php

// ----------------------------------------------------------------------------- UPLOADS

// Slugify all file names to avoid spaces, uppercase, special chars in file names
if ( defined("BOWL_SLUGIFY_UPLOAD_NAMES") && BOWL_SLUGIFY_UPLOAD_NAMES ) {
	add_filter('wp_handle_upload_prefilter', function ( $file ) {
		$fileParts = explode('.', $file['name']);
		foreach ( $fileParts as $key => $part )
			$fileParts[$key] = sanitize_title( $part );
		$file['name'] = implode('.', $fileParts);
		return $file;
	});
}

// ----------------------------------------------------------------------------- IMAGE CONFIG

// Override jpeg quality
add_filter('jpeg_quality', function() { return BOWL_JPEG_QUALITY; });

// Register those sizes
global $_wp_additional_image_sizes;
foreach ( BOWL_IMAGE_SIZES as $key => $value ) {
	if ( isset($_wp_additional_image_sizes[$key]) ) continue;
	add_image_size( $key, $value[0] ?? 0, $value[1] ?? 0, $value[2] ?? false );
}

// Register thumbail sizes
if ( defined("BOWL_POST_THUMBNAIL_SIZE") && is_array(BOWL_POST_THUMBNAIL_SIZE) )
	set_post_thumbnail_size(BOWL_POST_THUMBNAIL_SIZE[0], BOWL_POST_THUMBNAIL_SIZE[1]);

// ----------------------------------------------------------------------------- PROCESS IMAGE UPLOADS

function bowl_filter_attachment_metadata ( $file, $attachmentID, $context ) {
	// Silently fail if gd extension is not available
	if ( !function_exists('imagecreatetruecolor') )
		return $file;
	// Continue only on images
	if ( !is_array($file['sizes']) || !is_array($file['image_meta']) )
		return $file;
	// Compute uploaded file path and upload dir path
	$uploadDir   = wp_upload_dir();
	$uploadDirPath = rtrim($uploadDir['basedir'],'/').'/';
	$uploadDirWithDate = $uploadDirPath.pathinfo($file["file"])["dirname"]."/";
	$imagePath = $uploadDirPath.$file['file'];
	// Get image quality from settings
	$imageQuality = intval(
		defined('BOWL_WEBP_QUALITY') ? BOWL_WEBP_QUALITY : apply_filters('jpeg_quality', null)
	) ?? 80;
	// Get BlurHash resolution
	$blurHashResolution = defined("BOWL_BLUR_HASH_RESOLUTION") ? BOWL_BLUR_HASH_RESOLUTION : [4, 4];
	// This file is not an image
	try {
		// Get image file extension and sizes
		$extension = strtolower( pathinfo($imagePath, PATHINFO_EXTENSION) );
		$uploadedWidth = $file['width'];
		$uploadedHeight = $file['height'];
		// Open uploaded image with gd if mime type is OK
		if ( $extension === 'png' )
			$gdImage = imagecreatefrompng( $imagePath );
		else if ( $extension === 'jpg' || $extension === 'jpeg' )
			$gdImage = imagecreatefromjpeg( $imagePath );
		else if ( $extension === 'gif' )
			$gdImage = imagecreatefromgif( $imagePath );
		// Not an image we can convert to web or create blurhash from
		else return $file;
		// Compress to WebP
		if ( function_exists('imagewebp') && defined("BOWL_WEBP_ENABLED") && BOWL_WEBP_ENABLED ) {
			$webpImages = [];
			// Browse all image sizes
			foreach ( $file['sizes'] as $key => $value ) {
				// Double check it's an image process on images
				if ( !isset($value['mime-type']) ) continue;
				// Resize
				// FIXME : Check the crop parameter ? What about 0 or -1 heights ?
				$w = $value['width'];
				$h = $value['height'];
				// Create a copy to resize image
				$imageCopy = imagecreatetruecolor($w, $h);
				imagecopyresampled($imageCopy, $gdImage, 0, 0, 0, 0, $w, $h, $uploadedWidth, $uploadedHeight);
				// Convert to webp
				$fileName = pathinfo($value['file'], PATHINFO_FILENAME);
				$fullPath = $uploadDirWithDate.$fileName.'.webp';
				imagewebp( $imageCopy, $fullPath, $imageQuality );
				// Destroy copied image
				imagedestroy($imageCopy);
				// Add to meta
				$webpImages[$key] = [
					"mime_type" => "image/webp",
					"file" => "$fileName.webp",
					"width" => $w,
					"height" => $h,
				];
			}
			update_post_meta($attachmentID, "webp_sizes", json_encode($webpImages));
		}
		// Create BlurHash version
		if ( class_exists("kornrunner\Blurhash\Blurhash") && defined("BOWL_BLUR_HASH_ENABLED") && BOWL_BLUR_HASH_ENABLED ) {
			// Get resolution down a bit so blur hash is less memory intensive
			$maxWidth = $blurHashResolution[0] * 10; // TODO : Config
			if( $uploadedWidth > $maxWidth ) {
				$gdImage = imagescale($gdImage, $maxWidth);
				$uploadedWidth = imagesx($gdImage);
				$uploadedHeight = imagesy($gdImage);
			}
			// Get all colors of image
			$pixels = [];
			for ( $y = 0; $y < $uploadedHeight; ++$y ) {
				$row = [];
				for ( $x = 0; $x < $uploadedWidth; ++$x ) {
					$i = imagecolorat($gdImage, $x, $y);
					$c = imagecolorsforindex($gdImage, $i);
					$row[] = [$c['red'], $c['green'], $c['blue']];
				}
				$pixels[] = $row;
			}
			// Compute blur hash and store it to meta
			$blurHash = array_merge(
				$blurHashResolution,
				[ kornrunner\Blurhash\Blurhash::encode($pixels, $blurHashResolution[0], $blurHashResolution[1]) ]
			);
			update_post_meta( $attachmentID, 'blur_hash', json_encode($blurHash) );
		}
	}
	// Silently fail into php logs
	catch ( Exception $e ) {
		$encodedError = json_encode($e);
		error_log("Unable to encode ${imagePath} to webp or generate blurhash. ${$encodedError}");
	}
	// Destroy pending source gd image
	if ( isset($gdImage) && function_exists('imagedestroy' ))
		imagedestroy( $gdImage );

	return $file;
}
add_filter('wp_generate_attachment_metadata', 'bowl_filter_attachment_metadata', 10, 3);