<?php

namespace wzy\Src;

class Router {

    /**
     * Store any created routes.
 	 *
     * @var array
     */
    protected array $routes = [
	    'GET' 	 => [],
	    'HEAD'   => [],
	    'POST' 	 => [],
	    'PUT' 	 => [],
	    'PATCH'  => [],
	    'DELETE' => [],
	    'named'  => []
    ];

    /**
     * @var boolean
     */
    protected $testing;

    /**
     * @var Request
     */
    protected Request $request;

    /**
     * @var string
     */
    protected string $rewrite_prefix = 'wzy_router';

    /**
     * @var string
     */
    protected string $parameter_pattern = '/{([\w]+)}/';

    /**
     * @var string
     */
    protected string $value_pattern = '(?P<$1>[^\/]+)';

    /**
     * @var string
     */
    protected string $value_pattern_replace = '([^\/]+)';

    /**
     * @var array
     */
    protected $prefix = '';

    /**
     * @var array
     */
    protected array $middlewares = [];

    /**
     * Adds the action hooks for WordPress.
     */
    public function __construct( $testing = false ) {
    	$this->testing = $testing;
    	$this->request = new Request;

        add_action('wp_loaded', 	[ $this, 'flush' ] );
        add_action('init', 			[ $this, 'boot' ] );
        add_action('parse_request', [ $this, 'parse_request' ] );
    }

	/**
	 * Boot the router.
	 *
	 * @return void
	 */
    public function boot(): void {

        add_rewrite_tag( "%{$this->rewrite_prefix}_route%", '(.+)' );

        $method = $this->request->method();

        foreach ( $this->routes[$method] as $id => $route ) {
            $this->add_route( $route, $id, $method );
        }
    }


    /**
     * Adds route to the Router.
	 *
     * @param  string  $method  The HTTP method
     * @param  array   $attrs   The attributes of our custom route
     *
     * @return bool
     */
	public function add( string $method, array $attrs ): bool {

		foreach ( array( 'uri', 'uses' ) as $key ) {
            if ( ! isset( $attrs[ $key ] ) ) {
                 throw new \InvalidArgumentException( "Missing {$key} definition for route" );
            }
        }

        $attrs['uri'] = ltrim( $attrs['uri'] , '/' );

        if ( isset( $attrs['prefix'] ) ) {
        	$attrs['prefix'] = $this->prefix . $attrs['prefix'];
        } else {
        	$attrs['prefix'] = $this->prefix;
        }

        $attrs['prefix'] = ltrim( $attrs['prefix'] , '/' );

        if ( isset( $attrs['middlewares'] ) ) {
        	$middlewares = $attrs['middlewares'];

        	if ( is_string( $middlewares ) ) {
				$middlewares = [ $middlewares ];
			}

			$attrs['middlewares'] = array_merge( $this->middlewares, $middlewares );
        } else {
        	$attrs['middlewares'] = $this->middlewares;
        }

        $route = apply_filters( 'wzy_router_create_route', $attrs, $method );

        $this->routes[ $method ][] = $route;

        if ( isset( $route['as'] ) ) {
            $this->routes['named'][$method . '::' . $route['as']] = $route;
        }

        return true;
	}

	/**
	 *	Starts a new router group.
	 *
	 * @param  array  $attrs  The attributes of our custom route
	 *
	 * @return void
	 */
	public function group( array $attrs ): void {

		if ( isset( $attrs['middlewares'] ) ) {
			if ( is_string( $attrs['middlewares'] ) ) {
				$this->middlewares[] = $attrs['middlewares'];
			} else {
				$this->middlewares = $attrs['middlewares'];
			}
		}

		if ( isset( $attrs['prefix'] ) ) {
			$this->prefix = $attrs['prefix'];
		}

		$this->fetch( $attrs['uses'], [] );

		$this->middlewares = [];
		$this->prefix = '';
	}

	/**
	 * Adds the route to WordPress.
	 *
	 * @param $route
	 * @param $id
	 * @param $method
	 *
	 * @return void
	 */
    protected function add_route( $route, $id, $method ): void {
        $params = [
            'id' => $id,
            'parameters' => []
        ];

        $uri = $route['uri'];

        if( ! empty( $route['prefix'] ) ) {
        	$uri = $route['prefix'] . '/' . $route['uri'];
        }

        $uri = '^' . preg_replace(
            $this->parameter_pattern,
            $this->value_pattern_replace,
            str_replace( '/', '\\/', $uri )
        );

        $url     = 'index.php?';
        $matches = [];

        if ( preg_match_all( $this->parameter_pattern, $route['uri'], $matches ) ) {
            foreach( $matches[1] as $id => $param ) {
            	$param_reference = "{$this->rewrite_prefix}_param_{$param}";
        		$url 			.= "{$param_reference}=\$matches[" . ($id + 1) . ']&';

                add_rewrite_tag("%{$param_reference}%", '(.+)');

                $params['parameters'][$param] = null;
            }
        }

        add_rewrite_rule( $uri . '$', "{$url}{$this->rewrite_prefix}_route=" . urlencode( wp_json_encode( $params ) ), 'top' );
    }

	/**
	 * Catches requests and checks if they contain 'wzy_router_route'
	 * before passing them to 'process_request'
	 *
	 * @param  bool  $direct
	 * @param  \WP   $wp
	 *
	 * @throws \JsonException
	 *
	 * @return  mixed
	 */
    public function parse_request( \WP $wp, bool $direct = false ) {

    	if ( $this->testing && ! $direct ) {
    		return;
    	}

    	$route_key = "{$this->rewrite_prefix}_route";

        if ( ! array_key_exists( $route_key, $wp->query_vars ) ) {
            return;
        }

        $data  		= json_decode( $wp->query_vars[ $route_key ], true, 512, JSON_THROW_ON_ERROR );
        $route 		= null;
        $id 		= null;
        $name 		= null;
        $parameters = null;

        foreach( array('id', 'name', 'parameters' ) as $key ) {
			if ( isset( $data[$key] ) ) {
				$$key= $data[$key];
			}
        }

		$method = $this->request->method();

        if ( isset( $this->routes[$method][$id] ) ) {
            $route = $this->routes[$method][$id];
        } elseif ( isset( $this->routes['named'][$name] ) ) {
            $route = $this->routes['named'][$name];
        }

        if ( ! isset( $route ) ) {
            return;
        }

        foreach ( $parameters as $key => $val ) {
        	$reference = "{$this->rewrite_prefix}_param_{$key}";

            if ( ! isset( $wp->query_vars[$reference] ) ) {
                return;
            }

            $parameters[$key] = $wp->query_vars[$reference];
        }

        $response = $this->process_request( $route, $parameters );

        if ( $this->testing ) {
    		return $response;
    	}

       	die;
    }

	/**
	 * Handles the response of the route.
	 *
	 * @param  array  $route  The route's parameters
	 * @param  array  $args
	 *
	 * @return mixed|void
	 */
    public function process_request( array $route, array $args = [] ) {
    	$request = new Request;
    	$request->merge( $args );

		$store = [
			'middlewares' => $route['middlewares'],
			'route' => $route,
			'args' => $args
		];

    	return $this->next( $request, $this, $store, true );
    }

	/**
	 * @param  \wzy\Request  $request
	 * @param  \wzy\Router   $router
	 * @param  array         $store
	 * @param  bool          $first
	 *
	 * @return mixed|void
	 */
	public function next( Request $request, Router $router, array $store, bool $first = false ) {

    	if ( ( isset( $store['middlewares'][0] ) && $first ) || isset( $store['middlewares'][1] ) ) {
	    	if ( ! $first ) 	{
	    		array_shift( $store['middlewares'] );
	    	}

	    	$response = $this->fetch(
				$store['middlewares'][0] .'@run',
				[
		            $request,
		            $this,
		            $store
				]
		    );
    	} else {
    		$store['args']['request'] = $request;
    		$response = $this->fetch( $store['route']['uses'], $store['args'] );
    	}

    	if ( $this->testing ) {
    		return $response;
    	}

    	echo $response;
    }

    /**
     * Fetches a controller or callbacks response.
     *
     * @param  string|callable $callback
     * @param  array           $args
     *
     * @return mixed
     */
    public function fetch( $callback, array $args = [] ) {
        if ( is_string( $callback ) ) {
            [ $class, $method ] = explode( '@', $callback, 2 );
            $controller         = new $class;

            return call_user_func_array( array( $controller, $method ), $args );
        }

        return call_user_func_array( $callback, $args );
    }

    /**
     * Flushes WordPress's rewrite rules.
     *
     * @return void
     */
    public function flush(): void {
        flush_rewrite_rules();
    }

    /**
     * Returns the route by name.
     */
	public function name( $name ) {
		$methods = array_keys( $this->routes );

		foreach ( $methods as $method ) {
			$method_name = $method . '::' . $name;

			if ( isset( $this->routes['named'][ $method_name ] ) ) {
				return array_merge( $this->routes['named'][ $method_name ], [ 'method' => $method ] );
			}
		}

		return false;
	}

    /**
     * Returns the uri of named route.
     */
	public function uri( string $name ) {
		$route = $this->name( $name );

		if ( $route ) {
			if( ! empty( $route['prefix'] ) ) {
	        	return $route['prefix'] . '/' . $route['uri'];
	        }

			return $route['uri'];
		}

		return false;
	}

	/**
     * Returns the url of named route.
     */
	public function url( string $name ) {
		$uri = $this->uri( $name );
		$uri = ltrim( $uri, '/' );

		if ( $uri ) {
			return get_bloginfo( 'url' ) . '/' . $uri;
		}

		return false;
	}

	/**
     * Returns the method of named route.
     */
	public function method( string $name )	{
		$route = $this->name( $name );

		if ( $route ) {
			return $route['method'];
		}

		return false;
	}

    /**
     * Helper method for adding route.
     */
	public function get( array $attrs ): bool {
		return $this->add( 'GET', $attrs );
	}

    /**
     * Helper method for adding route.
     */
	public function post( array $attrs ): bool {
		return $this->add( 'POST', $attrs );
	}

    /**
     * Helper method for adding route.
     */
	public function put( array $attrs ): bool {
		return $this->add( 'PUT', $attrs );
	}

    /**
     * Helper method for adding route.
     */
	public function patch( array $attrs ): bool {
		return $this->add( 'PATCH', $attrs );
	}

    /**
     * Helper method for adding route.
     */
	public function delete( array $attrs ): bool {
		return $this->add( 'DELETE', $attrs );
	}

	/**
	 * Helper method for getting the Request object.
	 */
	public function request(): Request {

		return $this->request;
	}
}
