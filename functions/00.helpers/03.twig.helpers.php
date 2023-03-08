<?php

use Twig\Environment;
use Twig\TwigFilter;
use Twig\TwigFunction;

function bowl_inject_twig_helpers ( Environment $twig ) {
	// TODO : Dictionary helper, how to get dictionary data ?
	$twig->addFunction(
		new TwigFunction('dictionary', function () {
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
		new TwigFilter('imageSrcSet', function ( BowlImage|array $image ) {
			$image = is_array($image) ? $image : $image->toArray();
			$sizes = browseCompatibleFormats($image['formats'], function ($format) {
				return bowl_remove_base_from_href( $format['href'] )." ".$format['width']."w";
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
			return $nearestFormat['href'];
		})
	);
	
	$twig->getTwig()->addFilter(
		new \Twig\TwigFilter("blurhash64", function ($blurhashArray, $punch = 1.1) {
			//			var_dump($blurhashArray);
			$width = $blurhashArray[0];
			$height = $blurhashArray[1];
			$pixels = \kornrunner\Blurhash\Blurhash::decode($blurhashArray[2], $width, $height, $punch);
			$image  = imagecreatetruecolor($width, $height);
			for ($y = 0; $y < $height; ++$y) {
				for ($x = 0; $x < $width; ++$x) {
					[$r, $g, $b] = $pixels[$y][$x];
					imagesetpixel($image, $x, $y, imagecolorallocate($image, $r, $g, $b));
				}
			}
			ob_start();
			imagepng($image);
			$contents = ob_get_contents();
			ob_end_clean();
			return "data:image/jpeg;base64," . base64_encode($contents);
		})
	);
}