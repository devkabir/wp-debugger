<?php

namespace DevKabir\WPDebugger;

use DebugBar\DataCollector\MemoryCollector;
use DebugBar\DataCollector\PhpInfoCollector;
use DebugBar\DataCollector\RequestDataCollector;
use DebugBar\DebugBar;
use DebugBar\JavascriptRenderer;
use DevKabir\WPDebugger\Collections\HookCollector;
use DevKabir\WPDebugger\Collections\RemoteRequestCollector;

class Bar extends DebugBar {
	protected JavascriptRenderer $renderer;
	public static $instance = null;

	public static function get_instance(): Bar {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
	private function __construct() {
		$this->renderer = $this->getJavascriptRenderer();
		$this->renderer->setIncludeVendors( true );

		add_action( 'init', [ $this, 'handleAjax' ] );
		add_action( 'admin_init', [ $this, 'handleAjax' ] );

		add_action( 'wp_head', [ $this, 'renderHead' ] );
		add_action( 'admin_head', [ $this, 'renderHead' ] );

		add_action( 'wp_footer', [ $this, 'renderDebugBar' ] );
		add_action( 'admin_footer', [ $this, 'renderDebugBar' ] );

		$this->addCollector( new PhpInfoCollector() );
		$this->addCollector( new MemoryCollector() );
		$this->addCollector( new RequestDataCollector() );
		// $this->addCollector(new RemoteRequestCollector());
		// $this->addCollector(new HookCollector());
	}

	public function handleAjax() {
		if ( wp_doing_ajax() && $this->shouldDisplayBar() ) {
			$this->sendDataInHeaders( null, 'phpdebugbar', PHP_INT_MAX );
		}
	}

	public function shouldDisplayBar(): bool {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			return true;
		}

		return get_current_user_id() && ( current_user_can( 'administrator' ) || is_super_admin( get_current_user_id() ) );
	}

	public function renderHead() {
		if ( $this->shouldDisplayBar() ) {
			echo $this->renderer->renderHead();
		}
	}

	public function renderDebugBar() {
		if ( $this->shouldDisplayBar() ) {
			echo $this->renderer->render();
		}
	}

	public function addRendererAssets() {
		wp_enqueue_style( 'wp_debugger_query_css', plugins_url( 'assets/css/wp-debugger.css', FILE ) );
		wp_enqueue_script( 'wp_debugger_query_js', plugins_url( 'assets/js/wp-debugger.js', FILE ), [], false, true );
	}
}