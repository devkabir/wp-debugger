// Import Prism for syntax highlighting
import '../prism'
import './styles.css'
import templatesHtml from './templates.html?raw'

/**
 * Template loader and renderer
 * Uses tag-based templates with data-* attributes for consistent patterns
 */
class TemplateLoader {
  constructor() {
    this.templates = new Map()
    this.loadTemplates()
  }

  loadTemplates() {
    const parser = new DOMParser()
    const doc = parser.parseFromString(templatesHtml, 'text/html')
    const templateElements = doc.querySelectorAll('template')

    templateElements.forEach(template => {
      this.templates.set(template.id, template.content.cloneNode(true))
    })
  }

  get(templateId) {
    return this.templates.get(templateId)?.cloneNode(true)
  }

  /**
   * Bind data to template using data-content and data-href attributes
   */
  bind(template, data) {
    if (!template) return template

    // Bind text content
    Object.entries(data).forEach(([key, value]) => {
      const el = template.querySelector(`[data-content="${key}"]`)
      if (el) el.textContent = value
    })

    // Bind href attributes
    Object.entries(data).forEach(([key, value]) => {
      const el = template.querySelector(`[data-href="${key}"]`)
      if (el) el.href = value
    })

    // Bind custom attributes
    if (data.attrs) {
      Object.entries(data.attrs).forEach(([selector, attrs]) => {
        const el = template.querySelector(selector)
        if (el) {
          Object.entries(attrs).forEach(([attr, value]) => {
            el.setAttribute(attr, value)
          })
        }
      })
    }

    return template
  }

  /**
   * Render template to HTML string
   */
  render(templateId, data = {}) {
    const template = this.get(templateId)
    if (!template) return ''

    this.bind(template, data)

    const div = document.createElement('div')
    div.appendChild(template)
    return div.innerHTML
  }
}

/**
 * Renders the error page using data from wpDebuggerData
 */
class WPDebuggerApp {
  constructor(data) {
    this.data = data
    this.app = document.getElementById('wp-debugger')
    this.templates = new TemplateLoader()
  }

  render() {
    if (!this.app || !this.data) {
      console.error('WP Debugger: Missing app element or data')
      return
    }

    const template = this.templates.get('app-template')
    if (!template) return

    // Bind main message
    this.templates.bind(template, { message: this.data.message })

    // Fill slots
    const stackSlot = template.querySelector('[data-slot="stack-trace"]')
    const superglobalsSlot = template.querySelector('[data-slot="superglobals"]')

    if (stackSlot) stackSlot.innerHTML = this.renderStackTrace()
    if (superglobalsSlot) superglobalsSlot.innerHTML = this.renderSuperglobals()

    const copyButton = template.querySelector('[data-action="copy-stack"]')
    if (copyButton) {
      copyButton.addEventListener('click', () => this.copyStackTrace(copyButton))
    }

    const dismissButton = template.querySelector('[data-action="dismiss-error"]')
    if (dismissButton) {
      dismissButton.addEventListener('click', () => this.dismissError())
    }

    this.app.innerHTML = ''
    this.app.appendChild(template)

    // Highlight code after rendering
    requestAnimationFrame(() => {
      if (window.Prism) {
        Prism.highlightAll()
      }
    })
  }

  renderStackTrace() {
    if (!this.data.stackTrace || this.data.stackTrace.length === 0) {
      return this.templates.render('empty-state', { message: 'No stack trace available' })
    }

    return this.data.stackTrace.map((frame, index) => {
      return this.renderCodeFrame(frame, index)
    }).join('')
  }

  async copyStackTrace(button) {
    const text = this.buildStackTraceText()
    if (!text) return

    const originalLabel = button?.textContent
    const setLabel = label => {
      if (button) button.textContent = label
    }

    try {
      if (navigator.clipboard?.writeText) {
        await navigator.clipboard.writeText(text)
      } else {
        this.copyWithFallback(text)
      }

      setLabel('Copied!')
    } catch (error) {
      console.error('WP Debugger: Failed to copy stack trace', error)
      setLabel('Copy failed')
    } finally {
      if (button) {
        setTimeout(() => {
          setLabel(originalLabel ?? 'Copy for AI')
        }, 1500)
      }
    }
  }

  copyWithFallback(text) {
    const textarea = document.createElement('textarea')
    textarea.value = text
    textarea.setAttribute('readonly', '')
    textarea.style.position = 'absolute'
    textarea.style.left = '-9999px'
    document.body.appendChild(textarea)
    textarea.select()
    document.execCommand('copy')
    document.body.removeChild(textarea)
  }

  buildStackTraceText() {
    if (!this.data?.stackTrace || this.data.stackTrace.length === 0) {
      return ''
    }

    const frames = this.data.stackTrace.map((frame, index) => {
      const parts = [`${index + 1}) ${frame.file}:${frame.line}`]

      const argsForCopy = this.prepareArgsForCopy(frame.args)
      if (argsForCopy) {
        parts.push('  Args:')
        parts.push(this.indentBlock(argsForCopy, 4))
      }

      if (frame.snippet) {
        parts.push('  Code:')
        const snippet = frame.snippet.replace(/\t/g, '  ').trimEnd()
        parts.push(this.indentBlock(snippet, 4))
      }

      return parts.join('\n')
    }).join('\n\n')

    return `Error: ${this.data.message}\n\nStack Trace:\n${frames}`
  }

  renderCodeFrame(frame, index) {
    const template = this.templates.get('details-item')
    if (!template) return ''

    const details = template.querySelector('details')
    if (details) {
      if (index === 0) details.setAttribute('open', '')
      details.classList.add('code-frame')
    }

    // Build header with file link and line number
    const editorLink = `vscode://file/${frame.filePath}:${frame.line}`
    const fileLink = this.templates.render('file-link', { link: editorLink, text: frame.file })
    const headerHtml = `${fileLink} <span class="code-frame__line">Line ${frame.line}</span>`

    const headerEl = template.querySelector('[data-content="header"]')
    if (headerEl) headerEl.innerHTML = headerHtml

    // Build body with code block and optional arguments
    const bodySlot = template.querySelector('[data-slot="content"]')
    if (bodySlot) {
      // Add code block
      const codeTemplate = this.templates.get('code-block')
      if (codeTemplate) {
        const preEl = codeTemplate.querySelector('pre')
        const codeEl = codeTemplate.querySelector('code')

        if (preEl) {
          preEl.setAttribute('data-line', frame.line)
          preEl.setAttribute('data-line-offset', frame.startLine)
        }

        if (codeEl) {
          codeEl.setAttribute('class', 'language-php')
          codeEl.textContent = frame.snippet
        }

        bodySlot.appendChild(codeTemplate)
      }

      // Add arguments if present
      const hasArgs = frame.args && Object.keys(frame.args).length > 0
      if (hasArgs) {
        const argsHtml = this.renderArguments(frame.args)
        const argsDiv = document.createElement('div')
        argsDiv.innerHTML = argsHtml
        while (argsDiv.firstChild) {
          bodySlot.appendChild(argsDiv.firstChild)
        }
      }
    }

    const div = document.createElement('div')
    div.appendChild(template)
    return div.innerHTML
  }

  renderArguments(args) {
    const headerHtml = this.templates.render('section-header', { title: 'Arguments' })
    const variableHtml = this.renderVariableDump(args.join(','))
    return `<div class="mt-4">${headerHtml}${variableHtml}</div>`
  }

  renderSuperglobals() {
    if (!this.data.superglobals || Object.keys(this.data.superglobals).length === 0) {
      return this.templates.render('empty-state', { message: 'No superglobals available' })
    }

    return Object.entries(this.data.superglobals).map(([name, value], index) => {
      return this.renderVariable(name, value, index === 0)
    }).join('')
  }

  renderVariable(name, value, isOpen = false) {
    const template = this.templates.get('details-item')
    if (!template) return ''

    const details = template.querySelector('details')
    if (details) {
      if (isOpen) details.setAttribute('open', '')
      details.classList.add('variable-item')
    }

    this.templates.bind(template, { header: name })

    const bodySlot = template.querySelector('[data-slot="content"]')
    if (bodySlot) {
      bodySlot.innerHTML = this.renderVariableDump(value)
    }

    const div = document.createElement('div')
    div.appendChild(template)
    return div.innerHTML
  }

  renderVariableDump(variable) {
    const formatted = this.formatVariable(variable)
    return this.renderCodeBlock({ code: formatted, language: 'json' })
  }

  renderCodeBlock({ code, language = 'php', line, lineOffset }) {
    const template = this.templates.get('code-block')
    if (!template) return ''

    const preEl = template.querySelector('pre')
    const codeEl = template.querySelector('code')

    if (preEl) {
      if (typeof line === 'number') preEl.setAttribute('data-line', line)
      if (typeof lineOffset === 'number') preEl.setAttribute('data-line-offset', lineOffset)
    }

    if (codeEl) {
      codeEl.className = `language-${language}`
      codeEl.textContent = code
    }

    const div = document.createElement('div')
    div.appendChild(template)
    return div.innerHTML
  }

  formatVariable(value, indent = 0) {
    const indentStr = '  '.repeat(indent)

    if (value === null) {
      return 'null'
    }

    if (typeof value === 'boolean') {
      return value ? 'true' : 'false'
    }

    if (typeof value === 'string') {
      return `"${value}"`
    }

    if (typeof value === 'number') {
      return String(value)
    }

    if (Array.isArray(value)) {
      if (value.length === 0) {
        return '[]'
      }
      const items = value.map((item, index) => {
        return `${indentStr}  [${index}] => ${this.formatVariable(item, indent + 1)}`
      }).join('\n')
      return `[\n${items}\n${indentStr}]`
    }

    if (typeof value === 'object') {
      const keys = Object.keys(value)
      if (keys.length === 0) {
        return '{}'
      }
      const items = keys.map(key => {
        return `${indentStr}  "${key}" => ${this.formatVariable(value[key], indent + 1)}`
      }).join('\n')
      return `{\n${items}\n${indentStr}}`
    }

    return String(value)
  }

  indentBlock(text, spaces = 2) {
    const pad = ' '.repeat(spaces)
    return text.split('\n').map(line => `${pad}${line}`).join('\n')
  }

  prepareArgsForCopy(args) {
    if (!args || Object.keys(args).length === 0) {
      return ''
    }

    if (Array.isArray(args) && this.looksLikeErrorHandlerArgs(args)) {
      return 'omitted (PHP error handler args)'
    }

    return this.formatVariable(args)
  }

  looksLikeErrorHandlerArgs(args) {
    if (!Array.isArray(args)) return false
    if (args.length < 4) return false

    const [errno, message, file, line] = args
    return typeof errno === 'number'
      && typeof message === 'string'
      && typeof file === 'string'
      && (typeof line === 'number' || typeof line === 'string')
  }

  dismissError() {
    if (!this.app) return
    this.app.innerHTML = ''
    this.app.style.display = 'none'
    this.rememberIgnorePreference()
  }

  rememberIgnorePreference() {
    const maxAgeSeconds = 60 * 60 * 24 // 1 day
    document.cookie = `wp_debugger_ignore=1; path=/; max-age=${maxAgeSeconds}`
  }
}

// Initialize when DOM and data are ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initApp)
} else {
  initApp()
}

function initApp() {
  if (typeof wpDebuggerData !== 'undefined') {
    const app = new WPDebuggerApp(wpDebuggerData)
    app.render()
  } else {
    console.error('WP Debugger: wpDebuggerData not found')
  }
}
