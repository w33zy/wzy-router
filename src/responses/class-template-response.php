<?php

namespace wzy\Src\Responses;

use wzy\Src\Response;

class Template_Response extends Response {

	/**
	 * Renders a template as a response.
	 *
	 * @param string
	 * @param array
	 * @param integer
	 * @param array
	 */
    public function __construct( $file, $variables = [], $status = 200, $headers = array() ) {
    	$path = $this->file_path( $file );

    	ob_start();

        extract( $variables, EXTR_OVERWRITE );
        require $path;

		$data = ob_get_clean();

	    parent::__construct( $data, $status, $headers );
    }

    /**
     * Gets path to file, always some shorthand syntax {}
     *
     * @param  string
     * @return string
     */
    protected function file_path( $path ): string {
	    $path = str_replace( [ '{root}', '{wp-content}', '{active-theme}' ], [ ABSPATH, WP_CONTENT_DIR, get_stylesheet_directory() ], $path );

		return $path;
    }
}
