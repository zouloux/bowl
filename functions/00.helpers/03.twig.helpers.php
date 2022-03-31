<?php

function bowl_inject_twig_helpers ( \Twig\Environment $twig ) {
	// TODO : Dictionary helper, how to get dictionary data ?
	$twig->addFunction(
		new \Twig\TwigFunction('dictionary', function () {
		})
	);
	// TODO : BowlImage helper, get correct size
	$twig->addFilter(
		// TODO : WebP
		new \Twig\TwigFilter('image', function ( BowlImage $image, string|int $size = null ) {
			$matchingFormats = [];
			/** @var BowlImageFormat $nearestFormat */
//			$nearestFormat = null;
			foreach ( $image->formats as $format ) {
				// Keep image format by its size name
				if ( is_string($size) && $format->name == $size )
					$matchingFormats[] = $format;
				// Keep image format by its width, in px.
				// Looking for the nearest format bigger than request size.
//				else if ( is_int($size) && $format->width >= $size ) {
//					// Already has a nearest
//					if ( !is_null($nearestFormat) && $size > $nearestFormat->width ) {
//						continue;
//					}
//					$nearest = $size;
//				}
			}

			// No format found find biggest
			if ( empty($matchingFormats) ) {
				$biggestFormatsByType = [];
				foreach ( $image->formats as $format ) {
					if ( !isset($biggestFormatsByType[$format->format]) )
						$biggestFormatsByType[$format->format] = $format;
					if ( $format->width > $biggestFormatsByType[$format->format]->width )
						$biggestFormatsByType[$format->format] = $format;
				}
			}

//			$srcset = wp_get_attachment_image_srcset($image->id);
//			dump($srcset);

			// Return WebP if client is compatible, otherwise return default format
			if ( str_contains($_SERVER[ 'HTTP_ACCEPT' ], 'image/webp') )
				foreach ( $matchingFormats as $format )
					if ( $format->format == "webp" )
						return $format;
			// WebP not supported or not generated
			if ( isset($matchingFormats[0]) )
				return $matchingFormats[0];

			// Still nothing found, return original uploaded image to avoid "href" not found
			return new BowlImageFormat([
				'width' => $image->width,
				'height' => $image->height,
				'href' => $image->href,
				'format' => '', // TODO : Format
				'name' => 'original',
			]);
		})
	);
}