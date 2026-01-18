// Import core Prism
import Prism from 'prismjs'
import './styles.css'
// Import language support - order matters!
import 'prismjs/components/prism-markup-templating' // Required for PHP
import 'prismjs/components/prism-php'
import 'prismjs/components/prism-php-extras'
import 'prismjs/components/prism-javascript'
import 'prismjs/components/prism-json'
import 'prismjs/components/prism-css'

// Import plugins
import 'prismjs/plugins/line-highlight/prism-line-highlight.min.js'

// Make Prism available globally
window.Prism = Prism

export default Prism
