<?php

namespace wzy\Src;

class Request {

	/**
	* $_Request & php://input
	*
	* @var array
	*/
	protected array $parameters;

	/**
	* $_GET
	*
	* @var array
	*/
	public array $query;

	/**
	* $_POST
	*
	* @var array
	*/
	public array $post;

    /**
     * $_SERVER
     *
     * @var array
     */
    public array $server;

    /**
     * $_FILES
     *
     * @var array
     */
    public array $files;

    /**
     * $_COOKIE
     *
     * @var array
     */
    public array $cookies;

    /**
     * request headers
     *
     * @var array
     */
    public array $headers;

    /**
     * List of available types.
     *
     * @var array
     */
   	public array $types = [
   		'parameters',
   		'query',
   		'post',
   		'cookies',
   		'files',
   		'server',
   		'headers',
    ];

   	/**
   	 * Build request.
   	 */
	public function __construct() {
		$this->parameters = $this->request();
		$this->query   	  = $_GET;
		$this->post       = $_POST;
		$this->cookies    = $_COOKIE;
		$this->files      = $_FILES;
		$this->server     = $_SERVER;
		$this->headers    = $this->get_all_headers();
	}

	/**
	 * getallheaders() as it does  not work as expected on some
	 * server architectures (e.g. Nginx), so use this instead.
	 *
	 * @return array
	 */
	public function get_all_headers(): array {

		if ( ! function_exists( 'getallheaders' ) ) {
			$headers = [];

			foreach ( $_SERVER as $name => $value ) {
				if( 0 === strpos( $name, 'HTTP_' ) ) {
					$new_name = str_replace( ' ', '-', ucwords( str_replace( '_', ' ', strtolower( substr( $name, 5 ) ) ) ) );

					$headers[$new_name] = $value;
				} elseif ( $name === 'CONTENT_TYPE' ) {
					$headers['Content-Type'] = $value;
				} elseif ( $name === 'CONTENT_LENGTH' ) {
					$headers['Content-Length'] = $value;
				}
			}

			return $headers;
		}

		return getallheaders();
	}


	/**
	 * Gets $_Request & php://input
	 *
	 * @throws \JsonException
	 *
	 * @return array
	 */
	public function request(): array {
		$request = $_REQUEST;
		$server  = $_SERVER;

		if ( isset( $server['CONTENT_TYPE'] ) && $server['CONTENT_TYPE'] === 'application/json' ) {
			$json = json_decode( file_get_contents( 'php://input' ), true, 512, JSON_THROW_ON_ERROR );

			if ( $json ) {
				$request = array_merge( $request, $json );
			}
		}

		return $request;
	}

	/**
	 * Validates a given type.
	 *
	 * @param  string
	 *
	 * @return void
	 */
	protected function check_type( $type ): void {
		if ( ! in_array( $type, $this->types, true ) ) {
			throw new \UnexpectedValueException( 'Expected a valid type on request method' );
		}
	}

	/**
	 * Gets a request parameter.
	 *
	 * @param  string
	 * @param  string
	 * @param  string
	 *
	 * @return ?string
	 */
	public function get( $var, $default = '', $type = 'parameters' ): ?string {
		$this->check_type( $type );

		if ( ! isset( $this->{$type}[$var] ) || empty( $this->{$type}[$var] ) ) {
			return $default;
		}

		return $this->{$type}[$var];
	}

	/**
	 * Check if a request parameter exists.
	 *
	 * @param  string
	 * @param  string
	 *
	 * @return bool
	 */
	public function has( $var, $type = 'parameters' ): bool {

		return $this->get( $var, null, $type ) !== null;
	}

	/**
	 * Return all the request parameters.
	 *
	 * @param  string
	 *
	 * @return mixed
	 */
	public function all( $type = 'parameters' )	{
		$this->check_type( $type );

		return $this->$type;
	}

	/**
	 * Merges an array into the request parameters.
	 *
	 * @param  array
	 * @param  string
	 *
	 * @return void
	 */
	public function merge( $data, $type = 'parameters' ): void {
		$this->check_type( $type );

		$this->{$type} = array_merge( $this->{$type}, $data );
	}

	/**
	 * Returns only the values specified by $keys
	 *
	 * @param  array
	 * @param  string
	 *
	 * @return array
	 */
	public function only( $keys, $type = 'parameters' ): array {
		$keys = is_array($keys) ? $keys : array($keys);

        $results = [];

        foreach ($keys as $key) {
        	if ( $this->has( $key, $type ) ) {
        		$results[ $key ] = $this->get( $key, $type );
        	}
        }

        return $results;
	}

	/**
	 * Return all the request parameters except values specified by $keys
	 *
	 * @param  array
	 * @param  string
	 *
	 * @return array
	 */
	public function except( $keys, $type = 'parameters' ): array {
		$keys = is_array($keys) ? $keys : array($keys);

        $results = $this->all( $type );

        foreach ( $keys as $key ) {
        	if ( isset( $results[$key] ) ) {
        		unset( $results[$key] );
        	}
        }

        return $results;
	}

	/**
	 * Gets method used, supporting _method
	 *
	 * @return string
	 */
	public function method(): string {
		$method = $_SERVER['REQUEST_METHOD'];

		if ( $method === 'POST' ) {
			if ( isset( $_SERVER['X-HTTP-METHOD-OVERRIDE'] ) ) {
				$method = $_SERVER['X-HTTP-METHOD-OVERRIDE'];
			} elseif ( $this->has( '_method' ) ) {
				$method = $this->get( '_method' );
			}
		}

		return strtoupper( $method );
	}

}
