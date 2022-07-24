<?php

namespace wzy\Src;

abstract class Middleware {

	/**
	 * @var Router
	 */
	protected Router $router;

	/**
	 * @var array
	 */
	protected array $store;

	/**
	 * Called by WP_Router to run Middleware.
	 *
	 * @param  Request
	 * @param  Router
	 * @param  array
	 *
	 * @return mixed
	 */
	public function run( Request $request, Router $router, $store )	{
		$this->router = $router;
		$this->store  = $store;

		return $this->handle( $request );
	}

	/**
	 * Calls the next Middleware.
	 *
	 * @param  Request
	 *
	 * @return mixed
	 */
	public function next( Request $request ) {
		$this->router->next( $request, $this->router, $this->store );
	}

	/**
	 * Method to be implemented by each Middleware.
	 *
	 * @param  Request
	 *
	 * @return mixed
	 */
	abstract public function handle( Request $request );
}
