<?php

function bowl_inject_twig_helpers ( \Twig\Environment $twig ) {
	// TODO : Dictionary helper, how to get dictionary data ?
	$twig->addFunction(
		new \Twig\TwigFunction('dictionary', function () {
		})
	);

	function browseCompatibleFormats ( $formats, $filter = null ) {
		$supportsWebP = str_contains($_SERVER[ 'HTTP_ACCEPT' ], 'image/webp');
		$r = [];
		foreach ( $formats as $format ) {
			if ( !$supportsWebP && $format['format'] === "webp" ) continue;
			if ( $supportsWebP && $format['format'] !== "webp" ) continue;
			$r[] = is_null($filter) ? $format : $filter( $format );
		}
		return $r;
	}
	$twig->addFilter(
		new \Twig\TwigFilter('imageSrcSet', function ( BowlImage|array $image ) {
			$image = is_array($image) ? $image : $image->toArray();
			$sizes = browseCompatibleFormats($image['formats'], function ($format) {
				return bowl_remove_base_from_href( $format['href'] )." ".$format['width']."w";
			});
			return implode(",", $sizes);
		})
	);
	$twig->addFilter(
		new \Twig\TwigFilter('imageSrc', function ( BowlImage|array $image, string|int $size = null ) {
			$image = is_array($image) ? $image : $image->toArray();
			$formats = browseCompatibleFormats($image['formats']);
			$nearestFormat = null;
			foreach ( $formats as $format ) {
				if (
					$nearestFormat === null ||
					abs( $format['width'] - $size ) < abs( $nearestFormat['width'] - $size )
				)
					$nearestFormat = $format;
			}
			return $nearestFormat['href'];
		})
	);
}