<?php

namespace DevKabir\WPDebugger;

use DebugBar\DataCollector\MemoryCollector;
use DebugBar\DataCollector\PhpInfoCollector;
use DebugBar\DebugBar;
use DebugBar\JavascriptRenderer;
use DevKabir\WPDebugger\Collections\RemoteRequestCollector;

class Bar extends DebugBar
{
    protected JavascriptRenderer $renderer;

    public function __construct()
    {
        $this->renderer = $this->getJavascriptRenderer();
        $this->renderer->setOptions(array('base_url' => plugins_url('vendor/maximebf/debugbar/src/DebugBar/Resources/', FILE)));
        $this->renderer->setIncludeVendors(true);

        add_action('init', [$this, 'handleAjax']);
        add_action('admin_init', [$this, 'handleAjax']);

        add_action('wp_head', [$this, 'renderHead']);
        add_action('admin_head', [$this, 'renderHead']);

        add_action('wp_footer', [$this, 'renderDebugBar']);
        add_action('admin_footer', [$this, 'renderDebugBar']);

        add_action('wp_enqueue_scripts', [$this, 'addRendererAssets']);
        add_action('admin_enqueue_scripts', [$this, 'addRendererAssets']);


        $wpLogFile = WP_CONTENT_DIR . '/debug.log';

        $this->addCollector(new PhpInfoCollector());
        $this->addCollector(new MemoryCollector());
        !wp_doing_ajax() && $this->addCollector(new RemoteRequestCollector());
    }

    public function handleAjax()
    {
        if (wp_doing_ajax() && $this->shouldDisplayBar()) {
            $this->sendDataInHeaders(null, 'phpdebugbar', PHP_INT_MAX);
        }
    }

    public function shouldDisplayBar(): bool
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            return true;
        }

        return get_current_user_id() && (current_user_can('administrator') || is_super_admin(get_current_user_id()));
    }

    public function renderHead()
    {
        if ($this->shouldDisplayBar()) {
            echo $this->renderer->renderHead();
        }
    }

    public function renderDebugBar()
    {
        if ($this->shouldDisplayBar()) {
            echo $this->renderer->render();
        }
    }

    public function addRendererAssets()
    {
        wp_enqueue_style('wp_debugger_query_css', plugins_url('vendor/maximebf/debugbar/src/DebugBar/Resources/widgets/sqlqueries/widget.css', FILE));
        wp_enqueue_script('wp_debugger_query_js', plugins_url('vendor/maximebf/debugbar/src/DebugBar/Resources/widgets/sqlqueries/widget.js', FILE), [], false, true);
    }
}