<?php
// dump() can run several times in one request, so every instance gets its own id and scoped script.
$uid = uniqid( 'wpd-dump-' );
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="robots" content="noindex" />
    <title>WP Debugger - Variable Dump</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Host+Grotesk:wght@400;500&family=Atkinson+Hyperlegible+Mono:wght@400;500&display=swap"
      rel="stylesheet"
    />
    <style>
      /* Tokens live on the wrapper, not :root, because a dump can print inside another page and must not restyle it. */
      .wpd-dump {
        color-scheme: dark;
        --space-3xs: 2px;
        --space-2xs: 4px;
        --space-xs: 8px;
        --space-sm: 12px;
        --space-md: 16px;
        --space-lg: 24px;
        --space-xl: 40px;
        --text-xs: 0.75rem;
        --text-sm: 0.875rem;
        --text-md: 1rem;
        --text-display: 1.44rem;
        --ease-out: cubic-bezier(0.16, 1, 0.3, 1);
        --dur-micro: 120ms;
        --dur-short: 220ms;
        --container: 1200px;
        --gutter: 32px;
        --z-toast: 100001;
        --color-paper: oklch(17% 0.008 250);
        --color-paper-2: oklch(21% 0.009 250);
        --color-paper-3: oklch(13.5% 0.007 250);
        --color-ink: oklch(95% 0.005 250);
        --color-ink-2: oklch(83% 0.008 250);
        --color-muted: oklch(71% 0.01 250);
        --color-rule: oklch(31% 0.01 250);
        --color-rule-2: oklch(42% 0.012 250);
        --color-accent-soft: oklch(29% 0.05 80);
        --color-focus: oklch(82% 0.15 80);
        --color-cool: oklch(77% 0.12 245);
        --color-ok: oklch(76% 0.15 150);
        --font-display: "Host Grotesk", ui-sans-serif, system-ui, sans-serif;
        --font-body: var(--font-display);
        --font-outlier: "Atkinson Hyperlegible Mono", ui-monospace, monospace;
        --radius-card: 10px;
        --radius-pill: 6px;
        --radius-chip: 4px;
        --rule-hair: 1px;
        --tracking-display: -0.03em;
        --display-weight: 500;
      }

      @media (prefers-color-scheme: light) {
        .wpd-dump {
          color-scheme: light;
          --color-paper: oklch(98% 0.006 85);
          --color-paper-2: oklch(100% 0 0);
          --color-paper-3: oklch(95.5% 0.008 85);
          --color-ink: oklch(19% 0.01 60);
          --color-ink-2: oklch(32% 0.01 60);
          --color-muted: oklch(46% 0.012 60);
          --color-rule: oklch(88% 0.008 85);
          --color-rule-2: oklch(76% 0.01 85);
          --color-accent-soft: oklch(93% 0.04 80);
          --color-focus: oklch(50% 0.13 70);
          --color-cool: oklch(50% 0.14 250);
          --color-ok: oklch(50% 0.13 150);
        }
      }

      html,
      body {
        overflow-x: clip;
      }
      .wpd-dump,
      .wpd-dump *,
      .wpd-dump *::before,
      .wpd-dump *::after {
        box-sizing: border-box;
      }
      .wpd-dump {
        padding: var(--space-lg) var(--gutter);
        background: var(--color-paper);
        color: var(--color-ink);
        font-family: var(--font-body);
        font-size: var(--text-md);
        line-height: 1.55;
        text-align: left;
        -webkit-font-smoothing: antialiased;
      }
      .wpd-dump ::selection {
        background: var(--color-accent-soft);
      }
      .wpd-dump :focus-visible {
        outline: 2px solid var(--color-focus);
        outline-offset: 2px;
      }
      .wpd-dump h1 {
        margin: 0;
        color: var(--color-ink);
        font-family: var(--font-display);
        font-size: var(--text-display);
        font-weight: var(--display-weight);
        letter-spacing: var(--tracking-display);
        line-height: 1.1;
        overflow-wrap: break-word;
        min-width: 0;
        text-wrap: balance;
      }
      .wpd-dump button,
      .wpd-dump input {
        font: inherit;
      }
      @media (max-width: 40rem) {
        .wpd-dump {
          --gutter: 16px;
        }
      }
      @media (prefers-reduced-motion: reduce) {
        .wpd-dump *,
        .wpd-dump *::before,
        .wpd-dump *::after {
          transition-duration: 0.01ms !important;
        }
      }

      .wpd-dump .container {
        display: flex;
        flex-direction: column;
        gap: var(--space-md);
        width: 100%;
        max-width: var(--container);
        margin-inline: auto;
      }

      .wpd-dump .dump-header {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: var(--space-md);
        padding: var(--space-md) var(--space-lg);
        background: var(--color-paper-2);
        border: var(--rule-hair) solid var(--color-rule);
        border-radius: var(--radius-card);
      }
      .wpd-dump .controls-area {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: var(--space-xs);
      }

      .wpd-dump .search-input {
        width: 16rem;
        min-height: 40px;
        margin: 0;
        padding: 0 var(--space-sm);
        border: var(--rule-hair) solid var(--color-rule);
        border-radius: var(--radius-pill);
        background: var(--color-paper-3);
        box-shadow: none;
        color: var(--color-ink);
        font-size: var(--text-sm);
        line-height: 1.4;
        transition: border-color var(--dur-micro) var(--ease-out);
      }
      .wpd-dump .search-input::placeholder {
        color: var(--color-muted);
      }
      .wpd-dump .search-input:hover {
        border-color: var(--color-rule-2);
      }
      .wpd-dump .search-input:focus {
        border-color: var(--color-focus);
        box-shadow: none;
        outline: none;
      }
      .wpd-dump .search-input:focus-visible {
        outline: 2px solid var(--color-focus);
        outline-offset: 2px;
      }
      .wpd-dump .search-input:disabled {
        opacity: 0.5;
        cursor: not-allowed;
      }

      .wpd-dump .btn {
        display: inline-flex;
        align-items: center;
        gap: var(--space-xs);
        min-height: 40px;
        margin: 0;
        padding: 0 var(--space-md);
        border: var(--rule-hair) solid var(--color-rule-2);
        border-radius: var(--radius-pill);
        background: var(--color-paper-2);
        color: var(--color-ink);
        font-size: var(--text-sm);
        font-weight: 500;
        cursor: pointer;
        transition: background-color var(--dur-micro) var(--ease-out);
      }
      .wpd-dump .btn:hover {
        background: var(--color-rule);
      }
      .wpd-dump .btn:active {
        background: var(--color-paper-3);
      }
      .wpd-dump .btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
      }
      .wpd-dump .btn--small {
        min-height: 32px;
        padding: 0 var(--space-sm);
        font-size: var(--text-xs);
      }

      .wpd-dump .dumps {
        display: flex;
        flex-direction: column;
        gap: var(--space-md);
      }
      .wpd-dump .dump-item-card {
        overflow: hidden;
        background: var(--color-paper-2);
        border: var(--rule-hair) solid var(--color-rule);
        border-radius: var(--radius-card);
      }
      .wpd-dump .card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--space-sm);
        min-height: 48px;
        padding: var(--space-xs) var(--space-md);
        border-bottom: var(--rule-hair) solid var(--color-rule);
      }
      .wpd-dump .card-title {
        font-size: var(--text-sm);
        font-weight: 500;
        color: var(--color-ink-2);
      }
      .wpd-dump .card-body {
        max-height: 70vh;
        overflow: auto;
        padding: var(--space-md);
        background: var(--color-paper-3);
      }

      .wpd-dump .tree-node,
      .wpd-dump .tree-leaf {
        margin-left: var(--space-md);
        font-family: var(--font-outlier);
        font-size: var(--text-sm);
        line-height: 1.6;
        overflow-wrap: anywhere;
      }
      .wpd-dump .card-body > .tree-node,
      .wpd-dump .card-body > .tree-leaf {
        margin-left: 0;
      }
      .wpd-dump .tree-node-trigger {
        display: inline-flex;
        align-items: center;
        gap: var(--space-2xs);
        margin: 0;
        padding: 0 var(--space-2xs);
        border: 0;
        border-radius: var(--radius-chip);
        background: transparent;
        color: inherit;
        font: inherit;
        text-align: left;
        cursor: pointer;
        transition: background-color var(--dur-micro) var(--ease-out);
      }
      .wpd-dump .tree-node-trigger:hover {
        background: var(--color-rule);
      }
      .wpd-dump .tree-node-trigger:active {
        background: var(--color-paper-2);
      }
      .wpd-dump .tree-node-trigger::before {
        content: "▸";
        color: var(--color-muted);
        transition: transform var(--dur-micro) var(--ease-out);
      }
      .wpd-dump .tree-node.expanded > .tree-node-trigger::before {
        transform: rotate(90deg);
      }
      .wpd-dump .tree-node-children {
        display: none;
        margin-left: var(--space-2xs);
        padding-left: var(--space-2xs);
        border-left: var(--rule-hair) solid var(--color-rule);
      }
      .wpd-dump .tree-node.expanded > .tree-node-children {
        display: block;
      }
      .wpd-dump .tree-key {
        color: var(--color-ink-2);
      }
      .wpd-dump .tree-count,
      .wpd-dump .tree-value-null {
        color: var(--color-muted);
      }
      .wpd-dump .tree-value-string {
        color: var(--color-cool);
      }
      .wpd-dump .tree-value-number,
      .wpd-dump .tree-value-boolean {
        color: var(--color-ink);
        font-variant-numeric: tabular-nums slashed-zero;
      }
      .wpd-dump .highlight-match {
        padding: 0 var(--space-3xs);
        border-radius: var(--radius-chip);
        background: var(--color-accent-soft);
        color: var(--color-ink);
      }
      .wpd-dump .empty-message {
        margin: 0;
        color: var(--color-muted);
        font-size: var(--text-sm);
      }

      .wpd-dump .toast {
        position: fixed;
        right: var(--space-md);
        bottom: var(--space-md);
        z-index: var(--z-toast);
        display: flex;
        align-items: center;
        gap: var(--space-xs);
        padding: var(--space-xs) var(--space-md);
        border: var(--rule-hair) solid var(--color-rule-2);
        border-radius: var(--radius-pill);
        background: var(--color-paper-2);
        color: var(--color-ink);
        font-size: var(--text-sm);
        font-weight: 500;
        transform: translateY(calc(100% + var(--space-xl)));
        transition: transform var(--dur-short) var(--ease-out);
      }
      .wpd-dump .toast.show {
        transform: none;
      }
      .wpd-dump .toast-icon {
        display: block;
        color: var(--color-ok);
      }
    </style>
  </head>
  <body>
    <div class="wpd-dump" id="<?php echo esc_attr( $uid ); ?>">
      <div class="container">
        <header class="dump-header">
          <h1 class="dump-title">Debugger variable inspector</h1>
          <div class="controls-area">
            <input
              type="text"
              class="search-input"
              placeholder="Search keys or values…"
              aria-label="Search keys or values"
            />
            <button type="button" class="btn" data-action="expand">
              Expand all
            </button>
            <button type="button" class="btn" data-action="collapse">
              Collapse all
            </button>
          </div>
        </header>

        <main class="dumps"></main>
      </div>

      <div class="toast" role="status" aria-live="polite">
        <svg
          class="toast-icon"
          aria-hidden="true"
          width="16"
          height="16"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2.5"
          stroke-linecap="round"
          stroke-linejoin="round"
        >
          <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
          <polyline points="22 4 12 14.01 9 11.01"></polyline>
        </svg>
        <span class="toast-message">Copied!</span>
      </div>
    </div>

    <script>
      (() => {
      	const root = document.getElementById(<?php echo json_encode( $uid ); ?>);
      	const wpDebuggerData = <?php echo json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE ); ?>;
      	const container = root.querySelector('.dumps');
      	const toast = root.querySelector('.toast');

      	function showToast(message) {
      		toast.querySelector('.toast-message').innerText = message;
      		toast.classList.add('show');
      		setTimeout(() => {
      			toast.classList.remove('show');
      		}, 3000);
      	}

      	function fallbackCopyText(text, successMessage) {
      		const textArea = document.createElement('textarea');
      		textArea.value = text;
      		textArea.style.top = '0';
      		textArea.style.left = '0';
      		textArea.style.position = 'fixed';
      		textArea.style.opacity = '0';
      		document.body.appendChild(textArea);
      		textArea.focus();
      		textArea.select();
      		try {
      			const successful = document.execCommand('copy');
      			if (successful) {
      				showToast(successMessage);
      			} else {
      				console.error('Fallback copy failed');
      			}
      		} catch (err) {
      			console.error('Fallback copy failed: ', err);
      		}
      		document.body.removeChild(textArea);
      	}

      	function copyJson(index) {
      		const jsonStr = JSON.stringify(wpDebuggerData[index], null, 2);
      		if (navigator.clipboard && navigator.clipboard.writeText) {
      			navigator.clipboard.writeText(jsonStr).then(() => {
      				showToast('Copied JSON to clipboard');
      			}).catch(err => {
      				console.error('Could not copy JSON: ', err);
      				fallbackCopyText(jsonStr, 'Copied JSON to clipboard');
      			});
      		} else {
      			fallbackCopyText(jsonStr, 'Copied JSON to clipboard');
      		}
      	}

      	// Writes text into el as text nodes, wrapping each match in <mark>; dumped data never reaches innerHTML.
      	function appendHighlighted(el, text, filter) {
      		if (!filter) {
      			el.textContent = text;
      			return;
      		}
      		const lower = text.toLowerCase();
      		const needle = filter.toLowerCase();
      		let from = 0;
      		let at;
      		while ((at = lower.indexOf(needle, from)) !== -1) {
      			el.append(text.slice(from, at));
      			const mark = document.createElement('mark');
      			mark.className = 'highlight-match';
      			mark.textContent = text.slice(at, at + needle.length);
      			el.append(mark);
      			from = at + needle.length;
      		}
      		el.append(text.slice(from));
      	}

      	function matchesFilter(text, filter) {
      		return text !== null && String(text).toLowerCase().includes(filter.toLowerCase());
      	}

      	function keySpan(key, filter) {
      		const span = document.createElement('span');
      		span.className = 'tree-key';
      		appendHighlighted(span, `"${key}"`, filter);
      		return span;
      	}

      	function buildTreeHTML(value, key = null, filter = '') {
      		const node = document.createElement('div');

      		if (value !== null && typeof value === 'object') {
      			node.className = 'tree-node expanded';

      			const trigger = document.createElement('button');
      			trigger.type = 'button';
      			trigger.className = 'tree-node-trigger';
      			trigger.setAttribute('aria-expanded', 'true');

      			if (key !== null) {
      				trigger.append(keySpan(key, filter), ': ');
      			}

      			const typeSpan = document.createElement('span');
      			typeSpan.className = 'tree-count';
      			typeSpan.innerText = Array.isArray(value) ? `Array [${value.length}]` : `Object {${Object.keys(value).length}}`;
      			trigger.appendChild(typeSpan);
      			node.appendChild(trigger);

      			const childrenContainer = document.createElement('div');
      			childrenContainer.className = 'tree-node-children';

      			let hasVisibleChildren = false;
      			for (const k in value) {
      				if (Object.prototype.hasOwnProperty.call(value, k)) {
      					const childNode = buildTreeHTML(value[k], k, filter);
      					if (childNode) {
      						childrenContainer.appendChild(childNode);
      						hasVisibleChildren = true;
      					}
      				}
      			}
      			node.appendChild(childrenContainer);

      			trigger.addEventListener('click', (e) => {
      				e.stopPropagation();
      				trigger.setAttribute('aria-expanded', node.classList.toggle('expanded') ? 'true' : 'false');
      			});

      			// If filter is active and no children match, hide this node.
      			if (filter && !hasVisibleChildren && !matchesFilter(key, filter)) {
      				return null;
      			}
      		} else {
      			// If filter is active and leaf does not match key or value, hide it.
      			if (filter && !matchesFilter(key, filter) && !matchesFilter(String(value), filter)) {
      				return null;
      			}

      			node.className = 'tree-leaf';

      			if (key !== null) {
      				node.append(keySpan(key, filter), ': ');
      			}

      			const valueSpan = document.createElement('span');
      			let text = String(value);
      			if (typeof value === 'string') {
      				valueSpan.className = 'tree-value-string';
      				text = `"${value}"`;
      			} else if (typeof value === 'number') {
      				valueSpan.className = 'tree-value-number';
      			} else if (typeof value === 'boolean') {
      				valueSpan.className = 'tree-value-boolean';
      			} else if (value === null) {
      				valueSpan.className = 'tree-value-null';
      			}
      			appendHighlighted(valueSpan, text, filter);
      			node.appendChild(valueSpan);
      		}

      		return node;
      	}

      	function renderDumps(filter = '') {
      		container.innerHTML = '';

      		wpDebuggerData.forEach((val, idx) => {
      			const card = document.createElement('section');
      			card.className = 'dump-item-card';

      			const header = document.createElement('div');
      			header.className = 'card-header';

      			const title = document.createElement('span');
      			title.className = 'card-title';
      			title.innerText = `Dump #${idx + 1} (${typeof val})`;
      			header.appendChild(title);

      			const copyBtn = document.createElement('button');
      			copyBtn.type = 'button';
      			copyBtn.className = 'btn btn--small';
      			copyBtn.innerText = 'Copy JSON';
      			copyBtn.onclick = () => copyJson(idx);
      			header.appendChild(copyBtn);
      			card.appendChild(header);

      			const body = document.createElement('div');
      			body.className = 'card-body';
      			const tree = buildTreeHTML(val, null, filter);
      			if (tree) {
      				body.appendChild(tree);
      			} else {
      				body.innerHTML = '<p class="empty-message">No matching keys or values found</p>';
      			}
      			card.appendChild(body);
      			container.appendChild(card);
      		});
      	}

      	function setAllExpanded(expanded) {
      		root.querySelectorAll('.tree-node').forEach(n => {
      			n.classList.toggle('expanded', expanded);
      			n.firstChild.setAttribute('aria-expanded', expanded ? 'true' : 'false');
      		});
      	}

      	root.querySelector('.search-input').addEventListener('input', e => renderDumps(e.target.value));
      	root.querySelector('[data-action="expand"]').addEventListener('click', () => setAllExpanded(true));
      	root.querySelector('[data-action="collapse"]').addEventListener('click', () => setAllExpanded(false));

      	// The script sits after its markup, so the elements exist already; no DOMContentLoaded needed.
      	renderDumps();
      })();
    </script>
  </body>
</html>
