// Import Prism for syntax highlighting
import './prism-bundle.js'

/**
 * Renders the error page using data from wpDebuggerData
 */
class WPDebuggerApp {
  constructor(data) {
    this.data = data
    this.app = document.getElementById('app')
  }

  render() {
    if (!this.app || !this.data) {
      console.error('WP Debugger: Missing app element or data')
      return
    }

    this.app.innerHTML = this.getTemplate()

    // Highlight code after rendering
    requestAnimationFrame(() => {
      if (window.Prism) {
        Prism.highlightAll()
      }
    })
  }

  getTemplate() {
    return `
      <div class="debugger-shell">
        <section class="exception">
          ${this.renderHeader()}
          ${this.renderContent()}
        </section>
      </div>
    `
  }

  renderHeader() {
    return `
      <header class="exception__header">
        <p class="eyebrow">WP Debugger</p>
        <h1 class="exception__title">${this.escapeHtml(this.data.message)}</h1>
        <p class="exception__subtitle">A snapshot of what went wrong, with traceable code and the request context.</p>
      </header>
    `
  }

  renderContent() {
    return `
      <section class="exception__grid">
        <div class="exception__stack">
          <h2 class="section-title">Stack Trace</h2>
          ${this.renderStackTrace()}
        </div>
        <aside class="exception__context">
          <h2 class="section-title">Superglobals</h2>
          <div class="accordion">
            ${this.renderSuperglobals()}
          </div>
        </aside>
      </section>
    `
  }

  renderStackTrace() {
    if (!this.data.stackTrace || this.data.stackTrace.length === 0) {
      return '<p class="text-muted">No stack trace available</p>'
    }

    return this.data.stackTrace.map((frame, index) => {
      return this.renderCodeFrame(frame, index)
    }).join('')
  }

  renderCodeFrame(frame, index) {
    const editorLink = `vscode://file/${frame.file}:${frame.line}`
    const hasArgs = frame.args && Object.keys(frame.args).length > 0

    return `
      <details class="code-frame" ${index === 0 ? 'open' : ''}>
        <summary class="code-frame__header">
          <div class="code-frame__info">
            <a href="${this.escapeHtml(editorLink)}" class="code-frame__file" title="Open in editor">
              ${this.escapeHtml(frame.file)}
            </a>
            <span class="code-frame__line">Line ${frame.line}</span>
          </div>
        </summary>
        <div class="code-frame__content">
          <pre class="code-frame__code"><code class="language-php line-numbers" data-start="${frame.startLine}" data-line="${frame.line}">${this.escapeHtml(frame.snippet)}</code></pre>
          ${hasArgs ? this.renderArgs(frame.args) : ''}
        </div>
      </details>
    `
  }

  renderArgs(args) {
    return `
      <div class="mt-4">
        <h3 class="text-xl font-semibold">Arguments</h3>
        ${this.renderVariableDump(args)}
      </div>
    `
  }

  renderSuperglobals() {
    if (!this.data.superglobals || Object.keys(this.data.superglobals).length === 0) {
      return '<p class="text-muted">No superglobals available</p>'
    }

    return Object.entries(this.data.superglobals).map(([name, value], index) => {
      return this.renderVariable(name, value, index === 0)
    }).join('')
  }

  renderVariable(name, value, isOpen = false) {
    return `
      <details class="variable-item" ${isOpen ? 'open' : ''}>
        <summary class="variable-item__header">
          <span class="variable-item__name">${this.escapeHtml(name)}</span>
        </summary>
        <div class="variable-item__content">
          ${this.renderVariableDump(value)}
        </div>
      </details>
    `
  }

  renderVariableDump(variable) {
    const formatted = this.formatVariable(variable)
    return `
      <div class="variable-dump">
        <pre class="variable-dump__content"><code class="language-php">${this.escapeHtml(formatted)}</code></pre>
      </div>
    `
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

  escapeHtml(text) {
    const div = document.createElement('div')
    div.textContent = text
    return div.innerHTML
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
