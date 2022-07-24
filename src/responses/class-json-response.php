<?php

namespace wzy\Src\Responses;

use wzy\Src\Response;

class JSON_Response extends Response {

	/**
	 * Builds a json response.
	 *
	 * @param  mixed
	 * @param  integer
	 * @param  array
	 */
    public function __construct( $data = null, $status = 200, $headers = [] ) {
    	$headers[] = 'Content-Type: application/json';
    	$data      = wp_json_encode( $data );

    	parent::__construct( $data, $status, $headers );
    }
}
