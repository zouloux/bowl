<?php

function bowl_inject_twig_helpers ( \Twig\Environment $twig ) {
	// TODO : Dictionary helper, how to get dictionary data ?
	$twig->addFunction( new \Twig\TwigFunction('dictionary', function () {
	}) );
	// TODO : BowlImage helper, get correct size
	$twig->addFilter(
		new \Twig\TwigFilter('image', function ( BowlImage $image, $formatName = null ) {
//			dump($image);
			foreach ( $image->formats as $format ) {
				if ( $format->name == $formatName ) {
					dump( $format );
					return $format;
				}
			}
			return null;
		})
	);
}