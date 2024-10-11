<?php

namespace DevKabir\WPDebugger;

use Debug_Bar;
use DebugBar\StandardDebugBar;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

class Plugin {
	public function __construct() {
		$this->debugbar = new Debug_Bar()
	}

	public function init_error_page() {
		$whoops = new Run();
		$whoops->pushHandler( new PrettyPageHandler() );
		$whoops->register();
	}

	/**
	 * Intercepts outgoing HTTP requests and serves mock responses for predefined URLs.
	 * Stores POST request data in a transient for testing purposes.
	 *
	 * @param bool|array $preempt The preemptive response to return if available.
	 * @param array      $args    Array of HTTP request arguments, including method and body.
	 * @param string     $url     The URL of the outgoing HTTP request.
	 *
	 * @return mixed Mock response or false to allow original request.
	 */
	public function intercept_http_requests( $preempt, $args, $url ) {
		if ( strpos( $url, 'https://wpmudev.com/api/' ) === false ) {
			return $preempt;
		}

		$mock_logs_dir = WP_CONTENT_DIR . '/mock-logs';
		if ( ! is_dir( $mock_logs_dir ) ) {
			wp_mkdir_p( $mock_logs_dir );
		}

		$mock_urls = array(
			'/hosting' => json_encode(
				array(
					'is_enabled' => false,
					'waf'        => array(
						'is_active' => false,
					),
				)
			),
		);

		foreach ( $mock_urls as $mock_url => $mock_response ) {
			if ( strpos( $url, $mock_url ) !== false ) {
				if ( isset( $args['method'] ) && strtoupper( $args['method'] ) === 'POST' && isset( $args['body'] ) ) {
					$post_data     = wp_parse_args( $args['body'] );
					$transient_key = 'mock_post_data_' . md5( $url . $args['method'] );
					set_transient( $transient_key, $post_data, 60 * 60 ); // Store for 1 hour
				}

				write_log( array( $url, $args, $mock_response ), true, $mock_logs_dir );
				return array(
					'body'          => $mock_response,
					'response'      => array(
						'code'    => 200,
						'message' => 'OK',
					),
					'headers'       => array(),
					'cookies'       => array(),
					'http_response' => null,
				);
			}
		}

		return new \WP_Error( '404', 'Interceptor enabled by wp-logger plugin.' );
	}

	public function init_debugbar() {
		$debugbar = new StandardDebugBar();
        $debugbarRenderer = $debugbar->getJavascriptRenderer();

		
        // Use Debugbar to log queries, data, etc.
        $debugbar['messages']->addMessage('Debugbar is loaded!');
        
        // Example usage of logging a database query
        global $wpdb;
        $query = "SELECT * FROM $wpdb->posts LIMIT 10";
        $results = $wpdb->get_results($query);
        $debugbar['messages']->addMessage('Query executed: ' . $query);
        $debugbar['messages']->addMessage($results);
        // Add Debugbar HTML and JavaScript to WordPress footer
        add_action('wp_footer', function () use ($debugbarRenderer) {
            echo $debugbarRenderer->renderHead();
            echo $debugbarRenderer->render();
        });
	}
}
