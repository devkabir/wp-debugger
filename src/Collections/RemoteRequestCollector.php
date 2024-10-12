<?php

namespace DevKabir\WPDebugger\Collections;

use DebugBar\DataCollector\AssetProvider;
use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;

class RemoteRequestCollector extends DataCollector implements Renderable, AssetProvider {
    protected $useHtmlVarDumper = true;
    /**
     * @var array
     */
    private array $remoteRequests = [];

    public function __construct() {
        // Hook into WordPress HTTP API to log wp_remote_request calls
        add_filter('pre_http_request', [$this, 'startRequest'], 10, 3);
        add_action('http_api_debug', [$this, 'endRequest'], 10, 5);
    }

    /**
     * Start request logging before the request is made.
     *
     * @param bool|array $preempt
     * @param array $args
     * @param string $url
     * @return bool|array
     */
    public function startRequest($preempt, array $args, string $url) {
        $this->remoteRequests[$url] = [
            'start_time' => (microtime(true)),
        ];

        return $preempt;
    }

    /**
     * End request logging after the request is completed.
     *
     * @param mixed $response
     * @param string $context
     * @param string $class
     * @param array $args
     * @param string $url
     */
    public function endRequest($response, string $context, string $class, array $args, string $url) {
            $this->remoteRequests[$url]['end_time'] = microtime(true);
            $this->remoteRequests[$url]['duration'] = $this->getDataFormatter()->formatDuration($this->remoteRequests[$url]['end_time'] - $this->remoteRequests[$url]['start_time']);
            $this->remoteRequests[$url]['response_code'] = is_wp_error($response) ? $response->get_error_message() : $response['response']['code'];
            $this->remoteRequests[$url]['transport'] = $class;
            $this->remoteRequests[$url] = $this->getVarDumper()->renderVar($this->remoteRequests[$url]);
    }

    /**
     * Collect the data.
     *
     * @return array
     */
    public function collect() {
        return $this->remoteRequests;
    }

    /**
     * Get the name of the collector.
     *
     * @return string
     */
    public function getName() {
        return 'api-requests';
    }

    /**
     * Get the assets for the DebugBar.
     *
     * @return array
     */
    public function getAssets() {
        return $this->isHtmlVarDumperUsed() ? $this->getVarDumper()->getAssets() : array();
    }

    /**
     * Get the widgets to display in the DebugBar.
     *
     * @return array
     */
    public function getWidgets() {
        $widget = $this->isHtmlVarDumperUsed()
            ? "PhpDebugBar.Widgets.HtmlVariableListWidget"
            : "PhpDebugBar.Widgets.VariableListWidget";

        return [
            $this->getName() => [
                "icon" => "tags",
                "widget" => $widget,
                "map" => $this->getName(),
                "default" => "{}"
            ]
        ];
    }
}
