<?php

namespace DevKabir\WPDebugger\Collections;

use DebugBar\DataCollector\MessagesCollector;
use DebugBar\DataCollector\Renderable;
use DebugBar\DataCollector\AssetProvider;

class HookCollector extends MessagesCollector implements Renderable, AssetProvider {
    protected array $hooks = [];

    public function __construct() {
        parent::__construct('hooks');

        // Hook into all WordPress actions and filters
        foreach ($GLOBALS['wp_filter'] as $hook_name => $hook_object) {
            add_action($hook_name, function () use ($hook_name) {
                $this->collectHook($hook_name);
            }, PHP_INT_MAX);
        }
    }

    public function collectHook($hook_name) {
        $this->hooks[] = [
            'hook' => $hook_name,
            'time' => microtime(true)
        ];
        $this->addMessage("Hook triggered: {$hook_name}");
    }

    public function collect() {
        return $this->hooks;
    }

    public function getName() {
        return 'hooks';
    }

    public function getWidgets() {
        return [
            $this->getName() => [
                "icon" => "tags",
                "widget" => "PhpDebugBar.Widgets.HtmlVariableListWidget",
                "map" => $this->getName(),
                "default" => "{}",
            ]
        ];
    }
}
