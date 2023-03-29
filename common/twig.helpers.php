<?php

use Twig\Environment;
use Twig\TwigFilter;
use Twig\TwigFunction;

function bowl_inject_twig_helpers ( Environment $twig ) {
	// TODO : Dictionary helper, how to get dictionary data ?
	//	$twig->addFunction(
	//		new TwigFunction('dictionary', function () {
	//		})
	//	);
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
		new TwigFilter('imageSrcSet', function ( BowlImage|array $image ) {
			$image = is_array($image) ? $image : $image->toArray();
			$sizes = browseCompatibleFormats($image['formats'], function ($format) {
				return parse_url( $format['href'], PHP_URL_PATH )." ".$format['width']."w";
			});
			return implode(",", $sizes);
		})
	);
	$twig->addFilter(
		new TwigFilter('imageSrc', function ( BowlImage|array $image, string|int $size = null ) {
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
			return parse_url($nearestFormat['href'], PHP_URL_PATH);
		})
	);
}
