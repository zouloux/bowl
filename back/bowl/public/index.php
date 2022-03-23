<?php

declare(strict_types=1);

use Nano\core\Nano;
use Nano\renderers\twig\TwigRenderer;

require __DIR__ . '/../vendor/autoload.php';

// Init app
Nano::init( __DIR__);
// Load parent directory .env
Nano::loadEnvs();
// Render templates with native php tags
Nano::$renderer = new TwigRenderer();
// Load routes responders from app/responders/*.responder.php
Nano::loadResponders();
// Start router and execute matching route
Nano::start();