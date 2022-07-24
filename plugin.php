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

$wzy_router->get( array(
	'as'   => 'getRoute',
	'uri'  => '/simple',
	'uses' => static function()	{
		return 'A get request';
	}
) );

$wzy_router->post( array(
	'as'   => 'postRoute',
	'uri'  => '/simple',
	'uses' => static function() use ( $wzy_router ) {
		return 'A post request';
	}
) );
