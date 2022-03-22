<?php

function bowl_inject_twig_helpers ( \Twig\Environment $twig ) {
	// TODO : Dictionary helper, how to get dictionary data ?
	$twig->addFunction( new \Twig\TwigFunction('dictionary', function () {
	}) );
	// TODO : BowlImage helper, get correct size
}