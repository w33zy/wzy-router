<?php

namespace wzy\Src\Responses;

use wzy\Src\Response;

class Redirect_Response extends Response {

	/**
	 * Builds redirect response.
	 *
	 * @param string
	 * @param integer
	 * @param array
	 */
    public function __construct( $url, $status = 302, $headers = [] ) {
    	$headers[] = 'Location: ' . $url;

    	parent::__construct( null, $status, $headers );
    }
}
