# WP Debugger

This tool helps developers see and understand errors in WordPress.

## What it does

* Changes big error pages to show:

  * The error message, list of function calls, code parts, and source with highlights
  * Data from superglobals
  * A “Copy for AI” button and an “Ignore error” button (this hides the page when you reload)
* Shows errors in JSON format for JSON or CLI requests
* Can also log HTTP requests and fake responses if `ENABLE_MOCK_HTTP_INTERCEPTOR` is true
* Saves logs in files inside `wp-content/logs/`, and rotates them

## How to install

1. Put the plugin folder into `wp-content/plugins/wp-debugger/`
2. Turn it on in WordPress
   (Use `?disable_debug=1` in the URL to stop it from loading)

## How to use

* Cause an error to see the error page, or use `init_debugger()`
* Use these helper functions in `functions.php`:

  * `write_log( ...$messages )` — saves messages to `wp-content/logs/0-debugger.log` (auto-rotates)
  * `format_stack_trace( $trace )` and `log_stack_trace( $trace )` — clean up and save function call lists
  * `dump( ...$vars )` — shows data in a nice way (HTML or JSON)
  * `dd( ...$vars )` — dump and stop right away
  * `dump_filter_callbacks( $filter, $dump = true )` — look at hook functions (show or save them)
  * `recursively_decode_json( $data )` and `debugger_format_variable( $value )` — helper functions for logging
* Skip the error page with `?skip_wp_debugger=1`, or press “Ignore error” to set a cookie that hides it

## HTTP debugging

* Uses `http_api_debug` to save request and response logs to `wp-content/logs/{domain}-requests.log` (errors go to `{domain}-errors.log`)
* If `ENABLE_MOCK_HTTP_INTERCEPTOR` is true, some URLs return fake data from code instead of making a real request
