<?php
/**
 * @wordpress-plugin
 * Plugin Name:       wzy Router
 * Plugin URI:        http://wp-router.org/
 * Description:       A router for WordPress.
 * Version:           1.0.0
 * Author:            Jason Agnew
 * Author URI:        https://bigbitecreative.com/
 * License:           GPL2+
 */

namespace wzy;

/**
 * Autoload Classes
 */
require_once __DIR__ . '/inc/libraries/autoloader.php';

global $wzy_router;
$wzy_router = new \wzy\Src\Router;
