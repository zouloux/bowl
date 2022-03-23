<?php

BowlData::registerDataSet("globals", function () {
	$siteOptions = BowlRequest::getSingleton("site-options");
	$menus = BowlRequest::getSingleton("menus");
	$output = [];
	$output += $siteOptions;
	$output += $menus;
	return $output;
});