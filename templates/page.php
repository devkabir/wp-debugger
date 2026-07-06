<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<title>WordPress Debugger - Error Encountered</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link
		href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;500&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
		rel="stylesheet">
	<style>
		:root {
			--bg-color: #090d16;
			--card-bg: #121824;
			--card-border: #1e293b;
			--card-hover-bg: #1a2336;
			--text-main: #f8fafc;
			--text-muted: #94a3b8;
			--accent-red: #f87171;
			--accent-red-hover: #ef4444;
			--accent-red-bg: #450a0a;
			--accent-red-border: #7f1d1d;
			--accent-indigo: #818cf8;
			--accent-indigo-hover: #6366f1;
			--accent-indigo-bg: #1e1b4b;
			--accent-indigo-border: #312e81;
			--accent-warning: #fb923c;
			--accent-warning-hover: #f97316;
			--accent-warning-bg: #431407;
			--accent-warning-border: #7c2d12;
			--code-bg: #090d16;
			--font-sans: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
			--font-mono: 'Fira Code', 'SF Mono', Monaco, Consolas, monospace
		}

		* {
			box-sizing: border-box;
			margin: 0;
			padding: 0
		}

		body {
			background-color: var(--bg-color);
			color: var(--text-main);
			font-family: var(--font-sans);
			line-height: 1.4;
			padding: 1rem;
			min-height: 100vh
		}

		.container {
			max-width: 1400px;
			margin: 0 auto;
			display: flex;
			flex-direction: column;
			gap: 0.75rem
		}

		.error-header {
			background: linear-gradient(135deg, var(--card-bg) 0%, #151d2d 100%);
			border: 1px solid var(--card-border);
			border-left: 4px solid var(--accent-red);
			border-radius: 8px;
			padding: 0.75rem 1rem;
			box-shadow: 0 10px 30px rgba(0, 0, 0, .25);
			backdrop-filter: blur(10px);
			display: flex;
			flex-direction: row;
			align-items: center;
			justify-content: space-between;
			gap: 0.75rem;
			flex-wrap: wrap;
			position: relative;
			overflow: hidden
		}

		.error-header::after {
			content: '';
			position: absolute;
			top: 0;
			right: 0;
			width: 100px;
			height: 100px;
			background: radial-gradient(circle, rgba(248, 113, 113, .08) 0%, transparent 70%);
			pointer-events: none
		}

		.error-header.warning {
			border-left-color: var(--accent-warning)
		}

		.error-header.warning::after {
			background: radial-gradient(circle, rgba(251, 146, 60, .08) 0%, transparent 70%)
		}

		.error-header.notice,
		.error-header.deprecated,
		.error-header.strict-notice {
			border-left-color: var(--accent-indigo)
		}

		.error-header.notice::after,
		.error-header.deprecated::after,
		.error-header.strict-notice::after {
			background: radial-gradient(circle, rgba(129, 140, 248, .08) 0%, transparent 70%)
		}

		.error-info {
			display: flex;
			align-items: center;
			gap: 0.5rem;
			flex-wrap: wrap;
			flex: 1;
			min-width: 300px
		}

		.error-badge {
			background-color: var(--accent-red-bg);
			border: 1px solid var(--accent-red-border);
			color: var(--accent-red);
			padding: 0.15rem 0.4rem;
			border-radius: 9999px;
			font-size: 0.65rem;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: 0.05em
		}

		.error-badge.warning {
			background-color: var(--accent-warning-bg);
			border-color: var(--accent-warning-border);
			color: var(--accent-warning)
		}

		.error-badge.notice,
		.error-badge.deprecated,
		.error-badge.strict-notice {
			background-color: var(--accent-indigo-bg);
			border-color: var(--accent-indigo-border);
			color: var(--accent-indigo)
		}

		.error-title {
			font-size: 1.1rem;
			font-weight: 800;
			color: var(--text-main);
			word-break: break-word;
			line-height: 1.2
		}

		.error-meta {
			font-family: var(--font-mono);
			font-size: 0.75rem;
			color: var(--accent-red);
			background-color: rgba(248, 113, 113, .04);
			padding: 0.2rem 0.5rem;
			border-radius: 4px;
			border: 1px dashed rgba(248, 113, 113, .2);
			display: inline-flex;
			align-items: center;
			gap: 0.25rem;
			word-break: break-all
		}

		.error-meta.warning {
			color: var(--accent-warning);
			background-color: rgba(251, 146, 60, .04);
			border-color: rgba(251, 146, 60, .2)
		}

		.error-meta.notice,
		.error-meta.deprecated,
		.error-meta.strict-notice {
			color: var(--accent-indigo);
			background-color: rgba(129, 140, 248, .04);
			border-color: rgba(129, 140, 248, .2)
		}

		.actions-bar {
			display: flex;
			justify-content: flex-end;
			gap: 0.5rem;
			flex-wrap: wrap
		}

		.btn {
			display: inline-flex;
			align-items: center;
			gap: 0.375rem;
			padding: 0.5rem 1rem;
			border-radius: 6px;
			font-weight: 600;
			font-size: 0.8rem;
			cursor: pointer;
			transition: all .2s ease;
			text-decoration: none;
			border: 1px solid transparent
		}

		.btn-indigo {
			background-color: var(--accent-indigo-bg);
			color: var(--accent-indigo);
			border-color: var(--accent-indigo-border)
		}

		.btn-indigo:hover {
			background-color: var(--accent-indigo);
			color: var(--bg-color);
			box-shadow: 0 0 12px rgba(129, 140, 248, .3);
			transform: translateY(-1px)
		}

		.btn-red {
			background-color: var(--accent-red-bg);
			color: var(--accent-red);
			border-color: var(--accent-red-border)
		}

		.btn-red:hover {
			background-color: var(--accent-red);
			color: var(--bg-color);
			box-shadow: 0 0 12px rgba(248, 113, 113, .3);
			transform: translateY(-1px)
		}

		.btn-copy-path {
			background: none;
			border: none;
			color: var(--text-muted);
			cursor: pointer;
			padding: 0.15rem;
			border-radius: 4px;
			display: inline-flex;
			align-items: center;
			justify-content: center
		}

		.btn-copy-path:hover {
			color: var(--text-main);
			background-color: rgba(255, 255, 255, .1)
		}

		.workspace {
			display: grid;
			grid-template-columns: 380px 1fr;
			gap: 0.75rem;
			min-height: 450px
		}

		@media (max-width:1024px) {
			.workspace {
				grid-template-columns: 1fr
			}
		}

		.workspace-panel {
			background-color: var(--card-bg);
			border: 1px solid var(--card-border);
			border-radius: 8px;
			overflow: hidden;
			display: flex;
			flex-direction: column;
			box-shadow: 0 4px 20px rgba(0, 0, 0, .15)
		}

		.panel-header {
			padding: 0.625rem 0.875rem;
			border-bottom: 1px solid var(--card-border);
			background-color: rgba(255, 255, 255, .02);
			display: flex;
			justify-content: space-between;
			align-items: center
		}

		.panel-title {
			font-size: 0.875rem;
			font-weight: 700;
			color: var(--text-main);
			letter-spacing: 0.02em
		}

		.trace-list {
			overflow-y: auto;
			max-height: 500px;
			display: flex;
			flex-direction: column
		}

		.trace-item {
			padding: 0.625rem 0.875rem;
			border-bottom: 1px solid var(--card-border);
			cursor: pointer;
			transition: all .2s ease;
			text-align: left;
			background: none;
			border: none;
			border-left: 3px solid transparent;
			display: flex;
			flex-direction: column;
			gap: 0.25rem
		}

		.trace-item:hover {
			background-color: var(--card-hover-bg)
		}

		.trace-item.active {
			background-color: rgba(129, 140, 248, .06);
			border-left-color: var(--accent-indigo)
		}

		.trace-item-header {
			display: flex;
			align-items: center;
			justify-content: space-between;
			gap: 0.5rem
		}

		.trace-index {
			font-family: var(--font-mono);
			font-size: 0.7rem;
			color: var(--accent-indigo);
			font-weight: 600
		}

		.trace-trigger-badge {
			background-color: var(--accent-red-bg);
			color: var(--accent-red);
			font-size: 0.6rem;
			font-weight: 700;
			padding: 0.05rem 0.3rem;
			border-radius: 3px;
			text-transform: uppercase
		}

		.trace-func {
			font-family: var(--font-mono);
			font-size: 0.8rem;
			font-weight: 500;
			color: var(--text-main);
			word-break: break-all
		}

		.trace-file-info {
			font-size: 0.7rem;
			color: var(--text-muted);
			word-break: break-all
		}

		.code-viewer-panel {
			flex-grow: 1
		}

		.code-container {
			position: relative;
			flex-grow: 1;
			background-color: var(--code-bg);
			overflow: auto;
			max-height: 500px;
			font-family: var(--font-mono);
			font-size: 0.8rem;
			display: flex
		}

		.line-numbers {
			padding: 0.75rem 0.5rem;
			border-right: 1px solid var(--card-border);
			text-align: right;
			user-select: none;
			color: #4b5563;
			background-color: rgba(0, 0, 0, .15)
		}

		.code-lines {
			padding: 0.75rem 0;
			width: 100%;
			overflow: hidden;
			white-space: pre
		}

		.code-line {
			padding: 0 0.875rem;
			display: block;
			min-height: 1.4em;
			color: #e2e8f0
		}

		.code-line.highlight {
			background-color: rgba(239, 68, 68, .12);
			border-left: 3px solid var(--accent-red);
			padding-left: calc(0.875rem - 3px);
			color: #fca5a5
		}

		.tabs-card {
			background-color: var(--card-bg);
			border: 1px solid var(--card-border);
			border-radius: 8px;
			overflow: hidden;
			box-shadow: 0 4px 20px rgba(0, 0, 0, .15)
		}

		.tabs-header {
			display: flex;
			border-bottom: 1px solid var(--card-border);
			background-color: rgba(255, 255, 255, .02);
			overflow-x: auto
		}

		.tab-btn {
			padding: 0.625rem 1rem;
			background: none;
			border: none;
			border-bottom: 2px solid transparent;
			color: var(--text-muted);
			font-weight: 600;
			font-size: 0.8rem;
			cursor: pointer;
			transition: all .2s ease;
			white-space: nowrap
		}

		.tab-btn:hover {
			color: var(--text-main);
			background-color: rgba(255, 255, 255, .01)
		}

		.tab-btn.active {
			color: var(--accent-indigo);
			border-bottom-color: var(--accent-indigo);
			background-color: rgba(129, 140, 248, .02)
		}

		.tab-content {
			padding: 1rem;
			display: none;
			overflow-y: auto;
			max-height: 400px
		}

		.tab-content.active {
			display: block
		}

		.tree-node {
			margin-left: 1rem;
			font-family: var(--font-mono);
			font-size: 0.8rem;
			line-height: 1.4
		}

		.tree-node-trigger {
			cursor: pointer;
			user-select: none;
			color: var(--accent-indigo);
			display: inline-flex;
			align-items: center;
			gap: 0.2rem
		}

		.tree-node-trigger::before {
			content: '▶';
			display: inline-block;
			font-size: 0.55rem;
			transition: transform .2s ease;
			color: var(--text-muted)
		}

		.tree-node.expanded>.tree-node-trigger::before {
			transform: rotate(90deg)
		}

		.tree-node-children {
			display: none;
			border-left: 1px dashed var(--card-border);
			margin-left: 0.375rem;
			padding-left: 0.375rem
		}

		.tree-node.expanded>.tree-node-children {
			display: block
		}

		.tree-leaf {
			font-family: var(--font-mono);
			font-size: 0.8rem;
			margin-left: 1rem;
			line-height: 1.4;
			word-break: break-all
		}

		.tree-key {
			color: #f472b6
		}

		.tree-value-string {
			color: #34d399
		}

		.tree-value-number {
			color: #fb7185
		}

		.tree-value-boolean {
			color: #60a5fa
		}

		.tree-value-null {
			color: #94a3b8;
			font-style: italic
		}

		.empty-message {
			color: var(--text-muted);
			font-style: italic;
			font-size: 0.8rem
		}

		.toast {
			position: fixed;
			bottom: 1rem;
			right: 1rem;
			background-color: #1e293b;
			border: 1px solid var(--accent-indigo-border);
			color: #e2e8f0;
			padding: 0.5rem 1rem;
			border-radius: 6px;
			box-shadow: 0 10px 25px rgba(0, 0, 0, .3);
			display: flex;
			align-items: center;
			gap: 0.375rem;
			z-index: 9999;
			transform: translateY(150%);
			transition: transform .3s cubic-bezier(.16, 1, .3, 1);
			font-weight: 600;
			font-size: 0.8rem
		}

		.toast.show {
			transform: translateY(0)
		}

		.toast-icon {
			color: var(--accent-indigo)
		}

		@media (prefers-color-scheme:light) {
			:root {
				--bg-color: #f8fafc;
				--card-bg: #ffffff;
				--card-border: #e2e8f0;
				--card-hover-bg: #f1f5f9;
				--text-main: #0f172a;
				--text-muted: #64748b;
				--accent-red-bg: #fee2e2;
				--accent-red-border: #fca5a5;
				--accent-indigo-bg: #e0e7ff;
				--accent-indigo-border: #c7d2fe;
				--accent-warning-bg: #ffedd5;
				--accent-warning-border: #fed7aa;
				--code-bg: #f8fafc
			}

			.error-header {
				background: linear-gradient(135deg, var(--card-bg) 0%, #f1f5f9 100%)
			}

			.panel-header {
				background-color: rgba(0, 0, 0, .02)
			}

			.btn-copy-path:hover {
				background-color: rgba(0, 0, 0, 0.05)
			}

			.code-line {
				color: #334155
			}

			.line-numbers {
				color: #64748b;
				background-color: rgba(0, 0, 0, 0.03)
			}

			.tree-key {
				color: #c2185b
			}

			.tree-value-string {
				color: #0f766e
			}

			.tree-value-number {
				color: #be123c
			}

			.tree-value-boolean {
				color: #1d4ed8
			}

			.toast {
				background-color: #ffffff;
				color: #0f172a;
				border-color: var(--card-border)
			}
		}
	</style>
</head>

<body>
	<div class="container">
		<header class="error-header <?php echo esc_attr( strtolower( str_replace( ' ', '-', $type ) ) ); ?>">
			<div class="error-info"><span
					class="error-badge <?php echo esc_attr( strtolower( str_replace( ' ', '-', $type ) ) ); ?>"><?php echo esc_html( $type ); ?></span>
				<h1 class="error-title"><?php echo esc_html( $message ); ?></h1>
				<div class="error-meta <?php echo esc_attr( strtolower( str_replace( ' ', '-', $type ) ) ); ?>">
					<span><?php echo esc_html( $triggerPoint['file'] ); ?>:<?php echo esc_html( $triggerPoint['line'] ); ?></span><button
						class="btn-copy-path"
						onclick="copyText('<?php echo esc_js( $triggerPoint['file'] ); ?>:<?php echo esc_js( $triggerPoint['line'] ); ?>')"
						title="Copy path to clipboard"><svg width="12" height="12" viewBox="0 0 24 24" fill="none"
							stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
							<rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
							<path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
						</svg></button>
				</div>
			</div>
			<div class="actions-bar"><button class="btn btn-indigo" onclick="copyForAI()"><svg width="14" height="14"
						viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
						stroke-linejoin="round">
						<path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
						<rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect>
					</svg>Copy for AI</button><button class="btn btn-red" onclick="ignoreError()"><svg width="14"
						height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
						stroke-linecap="round" stroke-linejoin="round">
						<path d="M18 6L6 18M6 6l12 12"></path>
					</svg>Ignore</button></div>
		</header>
		<main class="workspace">
			<section class="workspace-panel">
				<div class="panel-header"><span class="panel-title">Stack Trace</span></div>
				<div class="trace-list" id="trace-list"><?php if ( empty( $stackTrace ) ) : ?>
						<div class="trace-item empty-message">No stack trace available.</div>
					<?php else : ?> 	<?php foreach ( $stackTrace as $index => $frame ) : ?>
							<?php
							$is_trigger = ( $frame['filePath'] === $triggerPoint['file'] && $frame['line'] === $triggerPoint['line'] );
							$func_name = 'main';
							if ( ! empty( $frame['function'] ) ) {
								$func_name = ! empty( $frame['class'] ) ? $frame['class'] . $frame['type'] . $frame['function'] : $frame['function'];
							}
							?><button class="trace-item<?php echo $index === 0 ? ' active' : ''; ?>"
								onclick="selectFrame(<?php echo $index; ?>)">
								<div class="trace-item-header"><span
										class="trace-index">#<?php echo $index; ?></span><?php if ( $is_trigger ) : ?><span
											class="trace-trigger-badge">Trigger Point</span><?php endif; ?></div><span
									class="trace-func"><?php echo esc_html( $func_name ); ?>()</span><span
									class="trace-file-info"><?php echo esc_html( $frame['file'] ); ?>:<?php echo esc_html( $frame['line'] ); ?></span>
							</button><?php endforeach; ?><?php endif; ?>
				</div>
			</section>
			<section class="workspace-panel code-viewer-panel">
				<div class="panel-header"><span class="panel-title" id="code-viewer-filename">File Code
						Snippet</span><button class="btn-copy-path" id="code-viewer-copy-btn"
						title="Copy file path"><svg width="14" height="14" viewBox="0 0 24 24" fill="none"
							stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
							<rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
							<path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
						</svg></button></div>
				<div class="code-container">
					<div class="line-numbers" id="code-line-numbers"></div>
					<pre class="code-lines"><code id="code-content"></code></pre>
				</div>
			</section>
		</main>
		<footer class="tabs-card">
			<div class="tabs-header">
				<?php
				$tab_keys = array( '$_GET', '$_POST', '$_FILES', '$_COOKIE', '$_SESSION', '$_SERVER' );
				$active_tab = '';
				foreach ( $tab_keys as $key ) {
					$has_data = ! empty( $superglobals[ $key ] );
					if ( $key === '$_SERVER' )
						$has_data = true;
					if ( $has_data ) {
						if ( empty( $active_tab ) )
							$active_tab = $key;
						$class = $active_tab === $key ? ' active' : '';
						echo sprintf( '<button class="tab-btn%s" onclick="switchTab(\'%s\')">%s</button>', $class, esc_attr( $key ), esc_html( $key ) );
					}
				}
				?>
			</div><?php foreach ( $tab_keys as $key ) : ?>
				<?php
				$has_data = ! empty( $superglobals[ $key ] );
				if ( $key === '$_SERVER' )
					$has_data = true;
				if ( $has_data ) :
					$class = $active_tab === $key ? ' active' : '';
					$val = $superglobals[ $key ] ?? array();
					?>
					<div class="tab-content<?php echo $class; ?>" id="tab-content-<?php echo esc_attr( $key ); ?>">
						<div id="tree-<?php echo esc_attr( str_replace( '$', '', $key ) ); ?>"></div>
					</div><?php endif; ?><?php endforeach; ?>
		</footer>
	</div>
	<div id="toast" class="toast"><svg class="toast-icon" width="16" height="16" viewBox="0 0 24 24" fill="none"
			stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
			<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
			<polyline points="22 4 12 14.01 9 11.01"></polyline>
		</svg><span id="toast-message">Copied!</span></div>
	<script>const wpDebuggerData = <?php echo json_encode( array( 'message' => $message, 'type' => $type, 'stackTrace' => $stackTrace, 'superglobals' => $superglobals, 'triggerPoint' => $triggerPoint ), JSON_UNESCAPED_SLASHES ); ?>; let activeFrameIndex = 0; function showToast(e) { const t = document.getElementById("toast"); document.getElementById("toast-message").innerText = e; t.classList.add("show"); setTimeout(() => { t.classList.remove("show") }, 3e3) } function copyText(e) { navigator.clipboard.writeText(e).then(() => { showToast("Path copied to clipboard") }).catch(e => { console.error("Could not copy text: ", e) }) } function selectFrame(e) { activeFrameIndex = e; const t = document.querySelectorAll(".trace-item"); t.forEach((t, r) => { if (r === e) { t.classList.add("active") } else { t.classList.remove("active") } }); const r = wpDebuggerData.stackTrace[e]; if (!r) return; document.getElementById("code-viewer-filename").innerText = r.file + ":" + r.line; document.getElementById("code-viewer-copy-btn").onclick = () => copyText(r.filePath + ":" + r.line); const o = r.startLine, c = r.line, a = r.snippet.split("\n"), n = document.getElementById("code-line-numbers"); n.innerHTML = ""; const l = document.getElementById("code-content"); l.innerHTML = ""; a.forEach((e, t) => { const r = o + t, a = document.createElement("div"); a.style.height = "1.4em"; a.style.padding = "0 0.5rem"; a.style.fontWeight = r === c ? "700" : "400"; a.style.color = r === c ? "var(--accent-red)" : "#4b5563"; a.innerText = r; n.appendChild(a); const s = document.createElement("span"); s.className = "code-line" + (r === c ? " highlight" : ""); s.innerText = e || " "; l.appendChild(s) }); const s = l.querySelector(".highlight"); if (s) { setTimeout(() => { s.scrollIntoView({ behavior: "smooth", block: "center", inline: "nearest" }) }, 50) } } function copyForAI() { const e = wpDebuggerData; let t = "### WP Debugger: Error Report\n\n"; t += `**Error:** \`${e.message}\`\n`; t += `**Location:** \`${e.triggerPoint.file}\` on line \`${e.triggerPoint.line}\`\n\n`; t += "#### Primary Code Snippet:\n"; if (e.stackTrace && e.stackTrace[0]) { const r = e.stackTrace[0]; t += `File: \`${r.filePath}\` (Lines ${r.startLine} - ${r.endLine})\n`; t += "```php\n"; r.snippet.split("\n").forEach((e, a) => { const o = r.startLine + a; const l = o === r.line ? " -> " : "    "; t += `${o}${l}${e}\n` }); t += "```\n\n" } t += "#### Stack Trace:\n"; e.stackTrace.forEach((e, r) => { let o = "main()"; if (e.function) { o = e.class ? `${e.class}${e.type}${e.function}()` : `${e.function}()` } t += `${r}. \`${o}\` in \`${e.file}:${e.line}\`\n` }); navigator.clipboard.writeText(t).then(() => { showToast("Error report copied for AI!") }).catch(e => { console.error("Copy failed: ", e) }) } function ignoreError() { document.cookie = "wp_debugger_ignore=1; path=/; max-age=31536000"; showToast("Muted. Reloading..."); setTimeout(() => { window.location.reload() }, 1e3) } function switchTab(e) { const t = document.querySelectorAll(".tab-btn"); t.forEach(t => { if (t.innerText === e) { t.classList.add("active") } else { t.classList.remove("active") } }); const r = document.querySelectorAll(".tab-content"); r.forEach(t => { if (t.id === `tab-content-${e}`) { t.classList.add("active") } else { t.classList.remove("active") } }) } function createTree(e, t) { const r = document.getElementById(e); if (!r) return; r.innerHTML = ""; if (Object.keys(t).length === 0) { r.innerHTML = '<span class="empty-message">No entries</span>'; return } const o = buildTreeHTML(t); r.appendChild(o) } function buildTreeHTML(e, t = null) { const r = document.createElement("div"); if (e !== null && typeof e === "object") { r.className = "tree-node"; const o = document.createElement("span"); o.className = "tree-node-trigger"; if (t !== null) { const e = document.createElement("span"); e.className = "tree-key"; e.innerText = `"${t}"`; o.appendChild(e); o.appendChild(document.createTextNode(": ")) } const a = Array.isArray(e) ? `Array [${e.length}]` : `Object {${Object.keys(e).length}}`; const oLabel = document.createElement("span"); oLabel.style.color = "var(--text-muted)"; oLabel.innerText = a; o.appendChild(oLabel); r.appendChild(o); const n = document.createElement("div"); n.className = "tree-node-children"; for (const t in e) { if (e.hasOwnProperty(t)) { n.appendChild(buildTreeHTML(e[t], t)) } } r.appendChild(n); o.addEventListener("click", e => { e.stopPropagation(); r.classList.toggle("expanded") }) } else { r.className = "tree-leaf"; if (t !== null) { const e = document.createElement("span"); e.className = "tree-key"; e.innerText = `"${t}"`; r.appendChild(e); r.appendChild(document.createTextNode(": ")) } const o = document.createElement("span"); if (typeof e === "string") { o.className = "tree-value-string"; o.innerText = `"${e}"` } else if (typeof e === "number") { o.className = "tree-value-number"; o.innerText = e } else if (typeof e === "boolean") { o.className = "tree-value-boolean"; o.innerText = e ? "true" : "false" } else if (e === null) { o.className = "tree-value-null"; o.innerText = "null" } else { o.innerText = String(e) } r.appendChild(o) } return r } document.addEventListener("DOMContentLoaded", () => { if (wpDebuggerData.stackTrace && wpDebuggerData.stackTrace.length > 0) { selectFrame(0) } for (const e in wpDebuggerData.superglobals) { if (wpDebuggerData.superglobals.hasOwnProperty(e)) { const t = e.replace("$", ""); createTree(`tree-${t}`, wpDebuggerData.superglobals[e]) } } });</script>
</body>

</html>