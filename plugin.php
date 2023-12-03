<?php
/**
Plugin Name: WP Developer
Description: A plugin that help to find and fix errors on wordpress website.
Version: 1.0
Author: Dev Kabir
*/
require_once __DIR__ . '/vendor/autoload.php';

use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

$whoops = new Run();
$whoops->pushHandler( new PrettyPageHandler() );
$whoops->register();