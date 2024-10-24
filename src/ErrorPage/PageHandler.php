<?php

namespace DevKabir\WPDebugger\ErrorPage;

use Throwable;
use const DevKabir\WPDebugger\FILE;

class PageHandler {
    private Throwable $exception;
    private string $pluginUrl;
    private string $templatePath;

    /**
     * PageHandler constructor is private to implement singleton pattern.
     *
     * @param Throwable $exception The exception to handle.
     */
    public function __construct(Throwable $exception) {
        $this->exception = $exception;
        $this->pluginUrl = plugins_url('assets/', FILE);
        $this->templatePath = plugin_dir_path(FILE) . 'assets/templates/';
        $this->render();
        die();
    }

    /**
     * Renders the exception by loading the HTML template and replacing placeholders.
     */
    private function render() {
        $template = $this->get_template('layout');
        $data = [
            '{{tailwind_css_url}}' => $this->pluginUrl . 'tailwind.css',
            '{{prism_css_url}}' => $this->pluginUrl . 'prism.css',
            '{{prism_js_url}}' => $this->pluginUrl . 'prism.js',
            '{{exception_message}}' => htmlspecialchars($this->exception->getMessage()),
            '{{code_snippets}}' => $this->generateCodeSnippets(),
            '{{superglobals}}' => $this->generateSuperglobals(),
        ];

        $output = $this->compile($data, $template);
        http_response_code(500);
        echo $output;
    }

    /**
     * Generates the HTML for code snippets based on the exception trace.
     *
     * @return string The HTML content for the code snippets.
     */
    private function generateCodeSnippets(): string {
        $trace = $this->exception->getTrace();
        $codeSnippetTemplate = $this->get_template('code');
        $codeSnippets = '';

        foreach ($trace as $frame) {
            if (!isset($frame['file']) || !is_readable($frame['file'])) {
                continue;
            }

            $filePath = $frame['file'];
            $line = $frame['line'];
            $fileName = basename($filePath);
            $editor = "vscode://file/$filePath:$line";
            $fileContent = file_get_contents($filePath) ?? '';
			$lines = explode("\n", $fileContent);
			$startLine = max(0, $frame['line'] - 10);
			$endLine = min(count($lines), $frame['line'] + 10);
			$snippet = implode("\n", array_slice($lines, $startLine, $endLine - $startLine));
			
            $snippetPlaceholders = [
                '{{file}}' => $fileName,
                '{{editor_link}}' => htmlspecialchars($editor),
                '{{file_path}}' => htmlspecialchars($filePath),
				'{{start_line}}' => $startLine,
				'{{end_line}}' => $endLine,
                '{{line_number}}' => $frame['line'],
                '{{code_snippet}}' => htmlspecialchars($snippet),
            ];

            $codeSnippets .= $this->compile($snippetPlaceholders, $codeSnippetTemplate);
        }

        return $codeSnippets;
    }

    /**
     * Loads the specified template file content.
     *
     * @param string $name
     * @return string
     */
    public function get_template($name) {
        $template = $this->templatePath . $name . '.html';

        if (!file_exists($template)) {
            die("Template: $template not found.");
        }

        return file_get_contents($template);
    }

    /**
     * Generates the HTML for the superglobals section.
     *
     * @return string The HTML content for the superglobals.
     */
    private function generateSuperglobals(): string {
        $superglobals = [
            '$_GET' => $_GET,
            '$_POST' => $_POST,
            '$_SERVER' => $_SERVER,
            '$_FILES' => $_FILES,
            '$_COOKIE' => $_COOKIE,
            '$_SESSION' => $_SESSION ?? [],
            '$_ENV' => $_ENV,
        ];

        $template = $this->get_template('global');
        $output = '';

        foreach ($superglobals as $name => $value) {
            if (empty($value)) {
                continue;
            }

            $data = [
                '{{name}}' => $name,
                '{{value}}' => json_encode($value, JSON_PRETTY_PRINT),
            ];

            $output .= $this->compile($data, $template);
        }

        return $output;
    }

    /**
     * Replaces placeholders in the template with provided data.
     *
     * @param array $data
     * @param string $template
     * @return string
     */
    public function compile(array $data, $template): string {
        return str_replace(array_keys($data), array_values($data), $template);
    }
}
