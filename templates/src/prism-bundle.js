// Import core Prism
import Prism from 'prismjs'

// Import language support - order matters!
import 'prismjs/components/prism-markup-templating' // Required for PHP
import 'prismjs/components/prism-php'
import 'prismjs/components/prism-php-extras'
import 'prismjs/components/prism-javascript'
import 'prismjs/components/prism-json'
import 'prismjs/components/prism-sql'
import 'prismjs/components/prism-bash'
import 'prismjs/components/prism-css'

// Import plugins
import 'prismjs/plugins/line-numbers/prism-line-numbers'
import 'prismjs/plugins/line-highlight/prism-line-highlight'
import 'prismjs/plugins/normalize-whitespace/prism-normalize-whitespace'

// Configure Prism
Prism.manual = true // Don't auto-highlight

// Configure normalize whitespace
if (Prism.plugins.NormalizeWhitespace) {
  Prism.plugins.NormalizeWhitespace.setDefaults({
    'remove-trailing': true,
    'remove-indent': false,
    'left-trim': false,
    'right-trim': true,
    'break-lines': 120,
    'indent': 0,
    'remove-initial-line-feed': false,
    'tabs-to-spaces': 2,
  })
}

// Make Prism available globally
window.Prism = Prism

export default Prism
