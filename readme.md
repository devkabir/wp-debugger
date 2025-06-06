# WordPress Debugger

A comprehensive debugging tool designed to help WordPress developers identify, analyze, and resolve errors efficiently. This plugin provides enhanced error pages, detailed stack traces, performance monitoring, and HTTP request debugging capabilities.

## 🚀 Features

- **Enhanced Error Pages**: Beautiful, detailed error pages with syntax-highlighted code snippets
- **Debug Bar**: Real-time performance monitoring with execution time, memory usage, and database queries
- **HTTP Request Debugging**: Monitor and log all outgoing HTTP requests
- **Stack Trace Analysis**: Detailed stack traces with clickable file links for IDE integration
- **Multiple IDE Support**: Direct links to open files in VS Code, PhpStorm, and Sublime Text
- **Performance Metrics**: Track page load times, memory usage, and database query performance
- **Flexible Logging**: Configurable log levels with automatic log rotation
- **JSON API Support**: Handles both web and API error responses appropriately

## 📋 Requirements

- **WordPress**: 5.3 or higher
- **PHP**: 7.1 or higher
- **Debug Mode**: WP_DEBUG must be enabled

## 🔧 Installation

### Manual Installation

1. Download the plugin as a ZIP file
2. Upload the ZIP file through WordPress Admin → Plugins → Add New → Upload Plugin
3. Activate the plugin through the 'Plugins' menu in WordPress

### Git Installation

```bash
cd /path/to/wordpress/wp-content/plugins/
git clone https://github.com/devkabir/wp-debugger.git
```

## ⚙️ Configuration

### Basic Setup

Add these constants to your `wp-config.php` file:

```php
// Enable WordPress debugging (required)
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );

// Optional: Enable API debugging (logs all HTTP requests)
define( 'WP_DEBUGGER_API_DEBUG', true );

// Optional: Enable HTTP request interception for testing
define( 'ENABLE_MOCK_HTTP_INTERCEPTOR', true );
```

### Advanced Configuration

```php
// Set environment type for conditional loading
define( 'WP_ENVIRONMENT_TYPE', 'development' );

// Custom log directory (optional)
define( 'WP_DEBUGGER_LOG_DIR', '/custom/log/path/' );

// Set preferred IDE for file links
define( 'WP_DEBUGGER_EDITOR', 'vscode' ); // Options: vscode, phpstorm, sublime
```

## 🎯 Usage

### Basic Debugging Functions

#### Logging Variables
```php
// Log any variable to debug log
write_log( $variable );
write_log( $array, $object, $string ); // Multiple variables

// Log with context
write_log( 'User login attempt', $user_data );
```

#### Variable Dumping
```php
// Dump variable and continue execution
dump( $variable );

// Dump variable and stop execution
dd( $variable );

// Dump multiple variables
dump( $var1, $var2, $var3 );
```

#### Stack Trace Analysis
```php
// Log current stack trace
log_stack_trace( debug_backtrace() );

// Format stack trace for analysis
$formatted_trace = format_stack_trace( debug_backtrace() );
```

#### Trigger Debug Page
```php
// Force display debug page at any point
init_debugger();
```

### WordPress-Specific Debugging

#### Filter Callbacks Analysis
```php
// Dump all callbacks for a specific filter
dump_filter_callbacks( 'wp_enqueue_scripts' );

// Log callbacks instead of dumping
dump_filter_callbacks( 'init', false );
```

#### Hook Debugging
```php
// Debug specific action/filter
add_action( 'init', function() {
    dump( 'Init hook fired' );
});
```

### HTTP Request Debugging

Monitor outgoing HTTP requests automatically when `WP_DEBUGGER_API_DEBUG` is enabled:

```php
// All HTTP requests are automatically logged
// Check logs in: wp-content/logs/[domain]-api-debug.log

// Example logged request:
wp_remote_get( 'https://api.example.com/data' );
// Logs: URL, response, status code, request headers/body
```

### Performance Monitoring

The debug bar automatically tracks:
- Page execution time
- Memory usage (current and peak)
- Database queries count and execution time
- Plugin loading times

Access debug data programmatically:
```php
// Add custom metrics to debug bar
add_filter( 'wp_debugger_contents', function( $contents ) {
    $contents['Custom Metrics'] = [
        'Custom Timer' => microtime( true ) - $start_time,
        'Custom Memory' => memory_get_usage(),
    ];
    return $contents;
});
```

## 🎨 Customization

### Custom Error Handlers

```php
// Add custom error handling
add_action( 'wp_debugger_before_error', function( $throwable ) {
    // Custom logging or notifications
    error_log( 'Critical error: ' . $throwable->getMessage() );
});
```

### Template Customization

Templates are located in `assets/templates/`:
- `page/` - Error page templates
- `bar/` - Debug bar templates

Override templates by copying to your theme:
```
your-theme/
└── wp-debugger/
    ├── page/
    │   ├── layout.html
    │   ├── exception.html
    │   └── code.html
    └── bar/
        ├── bar.html
        └── item.html
```

### Styling Customization

Add custom CSS:
```php
add_action( 'wp_enqueue_scripts', function() {
    if ( class_exists( 'DevKabir\WPDebugger\Plugin' ) ) {
        wp_enqueue_style( 'custom-debugger', 'path/to/custom.css' );
    }
});
```

## 🔧 Development Tools

### Assets Building

The plugin uses Tailwind CSS for styling. To modify styles:

```bash
cd assets/
npm install
npm run build    # Build production assets
npm run watch    # Watch for changes during development
```

### Available Scripts

```bash
npm run build:bar    # Build debug bar styles
npm run build:page   # Build error page styles
npm run watch:bar    # Watch debug bar styles
npm run watch:page   # Watch error page styles
```

## 📊 Error Page Features

### Code Snippets
- Syntax-highlighted PHP code
- Configurable context lines (default: 5 lines before/after error)
- Line numbers and error highlighting
- Direct IDE integration links

### Stack Trace
- Full stack trace with file paths and line numbers
- Function arguments display
- Collapsible sections for better readability
- Editor links for quick file opening

### System Information
- All superglobal variables ($_GET, $_POST, $_SERVER, etc.)
- HTTP headers
- Session data
- File upload information

## 🎯 IDE Integration

### VS Code
```php
define( 'WP_DEBUGGER_EDITOR', 'vscode' );
```
Generates links like: `vscode://file/path/to/file.php:123`

### PhpStorm
```php
define( 'WP_DEBUGGER_EDITOR', 'phpstorm' );
```
Generates links like: `phpstorm://open?file=/path/to/file.php&line=123`

### Sublime Text
```php
define( 'WP_DEBUGGER_EDITOR', 'sublime' );
```
Generates links like: `subl://open?url=file:///path/to/file.php&line=123`

## 📝 Logging

### Log Levels
- `DEBUG` - Detailed debugging information
- `INFO` - General information (default)
- `WARNING` - Warning messages
- `ERROR` - Error conditions
- `CRITICAL` - Critical conditions

### Log Rotation
- Automatic log rotation when files exceed 1MB
- Keeps 5 backup files by default
- Logs stored in `wp-content/logs/`

### Custom Logging
```php
// Create custom logger
$logger = new \DevKabir\WPDebugger\Log( 'custom-log.log' );
$logger->write( 'Custom message', 'ERROR' );

// Log with different levels
write_log( 'Debug info', 'DEBUG' );
write_log( 'Warning message', 'WARNING' );
write_log( 'Error occurred', 'ERROR' );
```

## 🚨 Best Practices

### Production Safety
```php
// Always check for debug mode
if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
    dump( $sensitive_data );
}

// Use environment-specific constants
if ( 'development' === wp_get_environment_type() ) {
    init_debugger();
}
```

### Performance Considerations
```php
// Avoid logging in loops
foreach ( $large_array as $item ) {
    // Don't do this:
    // write_log( $item );
}

// Do this instead:
write_log( $large_array );
```

### Security Notes
- Never use in production environments
- Disable when WP_DEBUG is false
- Sensitive data is automatically filtered from logs
- File paths are sanitized in output

## 🛠️ API Reference

### Core Classes

#### `DevKabir\WPDebugger\Plugin`
Main plugin class and entry point.

```php
$plugin = DevKabir\WPDebugger\Plugin::get_instance();
$plugin->throw_exception(); // Trigger debug page
$is_json = $plugin->is_json_request(); // Check request type
```

#### `DevKabir\WPDebugger\Log`
Logging functionality with rotation.

```php
$log = new DevKabir\WPDebugger\Log( 'filename.log' );
$log->write( $message, $level );
$log->clear_log();
```

#### `DevKabir\WPDebugger\Error_Page`
Error page generation and handling.

```php
$error_page = new DevKabir\WPDebugger\Error_Page();
// Automatically handles errors and exceptions
```

#### `DevKabir\WPDebugger\Debug_Bar`
Performance monitoring bar.

```php
$debug_bar = new DevKabir\WPDebugger\Debug_Bar();
$debug_bar->add_message( 'Custom message', 'icon' );
```

### Global Functions

| Function | Description | Example |
|----------|-------------|---------|
| `write_log()` | Log variables to file | `write_log( $data, 'ERROR' )` |
| `dump()` | Display formatted variable | `dump( $variable )` |
| `dd()` | Dump and die | `dd( $debug_data )` |
| `init_debugger()` | Trigger debug page | `init_debugger()` |
| `log_stack_trace()` | Log current stack trace | `log_stack_trace( debug_backtrace() )` |
| `dump_filter_callbacks()` | Show filter callbacks | `dump_filter_callbacks( 'init' )` |

## 🔍 Troubleshooting

### Common Issues

**Plugin not activating**
- Ensure WP_DEBUG is enabled in wp-config.php
- Check PHP version compatibility (7.1+)
- Verify file permissions

**Error pages not showing**
- Confirm WP_DEBUG is true
- Check if another error handler is interfering
- Verify plugin is activated

**Debug bar not appearing**
- Ensure you're logged in as administrator
- Check if admin toolbar is enabled
- Verify plugin assets are loading correctly

**Logs not being created**
- Check wp-content/logs directory permissions
- Verify disk space availability
- Ensure wp-content directory is writable

### Debug Mode

Enable verbose debugging:
```php
// Add to wp-config.php for detailed debugging
define( 'WP_DEBUGGER_VERBOSE', true );
ini_set( 'display_errors', 1 );
ini_set( 'display_startup_errors', 1 );
error_reporting( E_ALL );
```

## 📄 License

This plugin is licensed under the GPL v2 or later.

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/new-feature`
3. Commit your changes: `git commit -am 'Add new feature'`
4. Push to the branch: `git push origin feature/new-feature`
5. Submit a pull request

## 📞 Support

- **Documentation**: [GitHub Wiki](https://github.com/devkabir/wp-debugger/wiki)
- **Issues**: [GitHub Issues](https://github.com/devkabir/wp-debugger/issues)
- **Support Forum**: [WordPress.org Support](https://wordpress.org/support/plugin/wp-debugger)

## 🎯 Roadmap

- [ ] WordPress multisite support
- [ ] Error reporting dashboard
- [ ] Email notifications for critical errors
- [ ] Integration with external error tracking services
- [ ] Performance profiling tools
- [ ] Custom error templates
- [ ] Real-time error monitoring
- [ ] Database query optimization suggestions

---

**Made with ❤️ for WordPress developers**
