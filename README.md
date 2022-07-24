# wzy-Router

You can access the router at any point using the $wzy_router global

``` php
global $wzy_router
```

Defining a simple route is simple, routes consist of a Name, URI and a Closure callback. The URI will be appended to the site url, for example: `http://example.com/simple`

#### Basic GET Route

``` php
$wzy_router->get( 
	array(
		'as'   => 'simpleRoute',
		'uri'  => '/simple',
		'uses' => function() {
			return 'Hello World';
		}
	) 
);
```

#### Basic POST Route

``` php
$wzy_router->post( 
	array(
		'as'   => 'simpleRoute',
		'uri'  => '/simple',
		'uses' => function() {
			return 'Hello World';
		}
	) 
);
```

#### Support for PUT, DELETE and PATCH

```
$wzy_router->put();
$wzy_router->delete();
$wzy_router->patch();
```

#### Using functions, class methods or controllers

``` php
$wzy_router->get( 
	array(
		'as'   => 'simpleRoute',
		'uri'  => '/simple',
		'uses' => 'my_function'
	) 
);
```

``` php
$wzy_router->get( 
	array(
		'as'   => 'simpleRoute',
		'uri'  => '/simple',
		'uses' => array( $this, 'method' )
	)
) ;
```

``` php
$wzy_router->get( 
	array(
		'as'   => 'simpleRoute',
		'uri'  => '/simple',
		'uses' => __NAMESPACE__ . '\Controllers\SampleController@method'
	)
) ;
```

## Route Parameters

You can set route parameters in your URI by defining as `{param}`. These parameters then be accessed by your Closure or Controller as `$param`

``` php
$wzy_router->get( 
	array(
		'as'   => 'userProfile',
		'uri'  => '/user/{id}',
		'uses' => function( $id ) {
			return "User: {$id}";
		}
	) 
);
```

## Route Middleware

You set up middleware which runs when your route is accessed before the Closure is run. You can run more than one and are passed as an array.

``` php
class Middleware_One extends Middleware {

    public function handle( Request $request ) {
        if ( (int) $request->get( 'id' ) === 1 ) {
            return 'No';
        }

        return $this->next( $request );
    }
}
```
``` php
$wzy_router->get( 
	array(
		'as'   => 'simpleRoute',
		'uri'  => '/simple',
		'middlewares' => array( 'Middleware_One' ),
		'uses' => __NAMESPACE__ . '\Controllers\SampleController@method'
	) 
);
```

## Overall Example

``` php

class Middleware_One extends \wzy\Src\Middleware {

    public function handle( \wzy\Src\Request $request ) {
        if ( (int) $request->get( 'id' ) === 1 ) {
            return 'No';
        }

        return $this->next( $request );
    }
}

class Route_Test {

    protected \wzy\Src\Router $router;

    public function __construct() {
        global $wzy_router;

        $this->router = $wzy_router;
        $this->register_routes();
    }

    public function register_protected_routes() {
        $this->router->get( 
			array(
				'as'   => 'getTest',
				'uri'  => 'test/{id}',
				'uses' => array( $this, 'get' )
			) 
        );
    }

    protected function register_routes() {
        $this->router->group( 
			array(
				'prefix'      => '/protected',
				'middlewares' => array( 'Middleware_One' ),
				'uses'        => array( $this, 'register_protected_routes' )
			) 
        );

        $this->router->post( 
			array(
				'as'     => 'postTest',
				'uri'    => '/test/{id}',
				'uses'   => array( $this, 'post' ),
				'prefix' => ''
			) 
        );

        $this->router->put( 
			array(
				'as'   => 'putTest',
				'uri'  => '/test/{id}',
				'uses' => array( $this, 'put' )
			) 
        );
    }

    public function get( $id, \wzy\Src\Request $request ) {
        $all = $request->all();

        return new \wzy\Src\Responses\JSON_Response( $all );
    }

    public function post( $id ) {
        return 'POST: The ID is ' . $id;
    }

    public function put( $id ) {
        return 'PUT: The ID is ' . $id;
    }

}

new Route_Test();
```
