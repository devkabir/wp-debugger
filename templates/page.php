<?php
if ( ! function_exists( 'get_args_preview' ) ) {
	function get_args_preview( array $args ): string {
		$preview = array();
		foreach ( $args as $arg ) {
			if ( is_null( $arg ) ) {
				$preview[] = 'null';
			} elseif ( is_bool( $arg ) ) {
				$preview[] = $arg ? 'true' : 'false';
			} elseif ( is_numeric( $arg ) ) {
				$preview[] = (string) $arg;
			} elseif ( is_string( $arg ) ) {
				$truncated = strlen( $arg ) > 15 ? substr( $arg, 0, 12 ) . '…' : $arg;
				$preview[] = '"' . $truncated . '"';
			} elseif ( is_array( $arg ) ) {
				$preview[] = empty( $arg ) ? '[]' : '[…]';
			} else {
				$preview[] = gettype( $arg );
			}
		}
		$joined = implode( ', ', $preview );
		return strlen( $joined ) > 30 ? substr( $joined, 0, 27 ) . '…' : $joined;
	}
}

if ( ! function_exists( 'get_display_path' ) ) {
	function get_display_path( string $path ): string {
		$home = rtrim( (string) getenv( 'HOME' ), '/' );

		if ( '' !== $home && $path === $home ) {
			return '~';
		}

		if ( '' !== $home && 0 === strpos( $path, $home . '/' ) ) {
			return '~' . substr( $path, strlen( $home ) );
		}

		return $path;
	}
}

// $_REQUEST is what Error_Page sends; $_SERVER always has a tab.
$tab_labels = array(
	'$_REQUEST' => 'Request',
	'$_FILES'   => 'Files',
	'$_COOKIE'  => 'Cookies',
	'$_SESSION' => 'Session',
	'$_SERVER'  => 'Server',
);
$tab_keys   = array_values(
	array_filter(
		array_keys( $tab_labels ),
		static fn( $key ) => '$_SERVER' === $key || ! empty( $superglobals[ $key ] )
	)
);
$active_tab = $tab_keys[0];
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<meta name="robots" content="noindex">
	<title>
		<?php echo esc_html( $type . ': ' . $message ); ?>
	</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link
		href="https://fonts.googleapis.com/css2?family=Host+Grotesk:wght@400;500&family=Atkinson+Hyperlegible+Mono:wght@400;500&display=swap"
		rel="stylesheet">
	<style>
		:root {
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
			--text-lg: 1.2rem;
			--text-display: 1.728rem;
			--ease-out: cubic-bezier(0.16, 1, 0.3, 1);
			--dur-micro: 120ms;
			--dur-short: 220ms;
			--container: 1440px;
			--gutter: 32px;
			--z-screen: 100000;
			--z-toast: 100001;
			--color-paper: oklch(17% 0.008 250);
			--color-paper-2: oklch(21% 0.009 250);
			--color-paper-3: oklch(13.5% 0.007 250);
			--color-ink: oklch(95% 0.005 250);
			--color-ink-2: oklch(83% 0.008 250);
			--color-muted: oklch(71% 0.01 250);
			--color-rule: oklch(31% 0.01 250);
			--color-rule-2: oklch(42% 0.012 250);
			--color-accent: oklch(82% 0.15 80);
			--color-accent-ink: oklch(18% 0.04 75);
			--color-focus: oklch(82% 0.15 80);
			--color-cool: oklch(77% 0.12 245);
			--color-cool-soft: oklch(29% 0.05 245);
			--color-ok: oklch(76% 0.15 150);
			--color-bad: oklch(70% 0.17 25);
			--color-bad-soft: oklch(24.5% 0.035 25);
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
			:root {
				color-scheme: light;
				--color-paper: oklch(98% 0.006 85);
				--color-paper-2: oklch(100% 0 0);
				--color-paper-3: oklch(95.5% 0.008 85);
				--color-ink: oklch(19% 0.01 60);
				--color-ink-2: oklch(32% 0.01 60);
				--color-muted: oklch(46% 0.012 60);
				--color-rule: oklch(88% 0.008 85);
				--color-rule-2: oklch(76% 0.01 85);
				--color-accent: oklch(50% 0.13 70);
				--color-accent-ink: oklch(99% 0.005 85);
				--color-focus: oklch(50% 0.13 70);
				--color-cool: oklch(50% 0.14 250);
				--color-cool-soft: oklch(93% 0.03 250);
				--color-ok: oklch(50% 0.13 150);
				--color-bad: oklch(50% 0.18 25);
				--color-bad-soft: oklch(94% 0.03 25);
			}
		}

		*,
		*::before,
		*::after {
			box-sizing: border-box;
		}

		html,
		body {
			overflow-x: clip;
		}

		html {
			scrollbar-color: var(--color-rule-2) var(--color-paper);
		}

		body {
			margin: 0;
			background: var(--color-paper);
			color: var(--color-ink);
			font-family: var(--font-body);
			font-size: var(--text-md);
			line-height: 1.55;
			-webkit-font-smoothing: antialiased;
		}

		::selection {
			background: color-mix(in oklab, var(--color-accent) 25%, transparent);
		}

		:focus-visible {
			outline: 2px solid var(--color-focus);
			outline-offset: 2px;
		}

		h1,
		h2 {
			margin: 0;
			color: var(--color-ink);
			font-family: var(--font-display);
			font-weight: var(--display-weight);
			letter-spacing: var(--tracking-display);
			line-height: 1.1;
			overflow-wrap: break-word;
			min-width: 0;
			text-wrap: balance;
		}

		p {
			margin: 0;
		}

		svg {
			display: block;
		}

		button {
			font: inherit;
		}

		@media (max-width: 40rem) {
			:root {
				--gutter: 16px;
			}
		}

		@media (prefers-reduced-motion: reduce) {

			*,
			*::before,
			*::after {
				transition-duration: 0.01ms !important;
				scroll-behavior: auto !important;
			}
		}

		/* A fatal can land after WordPress already printed a page (wp-admin), so the screen covers it instead of trailing it. */
		.screen {
			position: fixed;
			inset: 0;
			z-index: var(--z-screen);
			overflow-y: auto;
			padding-block: var(--space-lg);
			background: var(--color-paper);
			color: var(--color-ink);
			font-family: var(--font-body);
			font-size: var(--text-md);
			line-height: 1.55;
			text-align: left;
		}

		.container {
			display: flex;
			flex-direction: column;
			gap: var(--space-md);
			width: 100%;
			max-width: var(--container);
			margin-inline: auto;
			padding-inline: var(--gutter);
		}

		.error-header {
			display: flex;
			flex-wrap: wrap;
			align-items: flex-end;
			justify-content: space-between;
			gap: var(--space-md) var(--space-lg);
			padding: var(--space-lg);
			background: var(--color-paper-2);
			border: var(--rule-hair) solid var(--color-rule);
			border-radius: var(--radius-card);
		}

		.error-info {
			display: flex;
			flex: 1 1 32rem;
			flex-direction: column;
			gap: var(--space-xs);
			min-width: 0;
		}

		.error-title {
			max-width: 60ch;
			font-size: var(--text-display);
		}

		.error-meta {
			display: flex;
			flex-wrap: wrap;
			align-items: center;
			gap: var(--space-2xs) var(--space-sm);
			font-size: var(--text-sm);
		}

		.error-type {
			color: var(--color-bad);
			font-weight: 500;
		}

		.error-where {
			min-width: 0;
			font-family: var(--font-outlier);
			color: var(--color-ink-2);
			overflow-wrap: anywhere;
			font-variant-numeric: tabular-nums slashed-zero;
		}

		.actions-bar {
			display: flex;
			flex-wrap: wrap;
			gap: var(--space-xs);
		}

		.btn {
			display: inline-flex;
			align-items: center;
			gap: var(--space-xs);
			min-height: 40px;
			padding: var(--space-xs) var(--space-md);
			border: var(--rule-hair) solid var(--color-rule-2);
			border-radius: var(--radius-pill);
			background: var(--color-paper-2);
			color: var(--color-ink);
			font-size: var(--text-sm);
			font-weight: 500;
			cursor: pointer;
			transition: background-color var(--dur-micro) var(--ease-out), border-color var(--dur-micro) var(--ease-out);
		}

		.btn:hover {
			background: var(--color-rule);
		}

		.btn:active {
			background: var(--color-paper-3);
		}

		.btn:disabled {
			opacity: 0.5;
			cursor: not-allowed;
		}

		.btn--primary {
			border-color: var(--color-accent);
			background: var(--color-accent);
			color: var(--color-accent-ink);
		}

		.btn--primary:hover {
			border-color: var(--color-accent);
			background: color-mix(in oklab, var(--color-accent) 85%, var(--color-ink));
		}

		.btn--primary:active {
			background: color-mix(in oklab, var(--color-accent) 80%, var(--color-paper));
		}

		.btn-copy-path {
			position: relative;
			display: inline-grid;
			flex: none;
			place-items: center;
			width: 32px;
			height: 32px;
			padding: 0;
			border: 0;
			border-radius: var(--radius-pill);
			background: transparent;
			color: var(--color-muted);
			cursor: pointer;
			transition: background-color var(--dur-micro) var(--ease-out), color var(--dur-micro) var(--ease-out);
		}

		.btn-copy-path::before {
			content: "";
			position: absolute;
			inset: -6px;
		}

		.btn-copy-path:hover {
			background: var(--color-rule);
			color: var(--color-ink);
		}

		.btn-copy-path:active {
			background: var(--color-paper-3);
		}

		/* One surface cut by 1px seams: the rule colour shows through the grid gap. */
		.workspace {
			display: grid;
			grid-template-columns: minmax(0, 22rem) minmax(0, 1fr);
			gap: var(--rule-hair);
			overflow: hidden;
			background: var(--color-rule);
			border: var(--rule-hair) solid var(--color-rule);
			border-radius: var(--radius-card);
		}

		.workspace-main {
			display: grid;
			grid-template-rows: minmax(0, 1fr) auto;
			gap: var(--rule-hair);
			min-width: 0;
		}

		.workspace-panel {
			display: flex;
			flex-direction: column;
			min-width: 0;
			background: var(--color-paper-2);
		}

		@media (max-width: 64rem) {
			.workspace {
				grid-template-columns: minmax(0, 1fr);
			}
		}

		.panel-header {
			display: flex;
			align-items: center;
			justify-content: space-between;
			gap: var(--space-sm);
			min-height: 48px;
			padding: var(--space-xs) var(--space-md);
			border-bottom: var(--rule-hair) solid var(--color-rule);
		}

		.panel-title {
			min-width: 0;
			font-size: var(--text-sm);
			font-weight: 500;
			letter-spacing: 0;
			color: var(--color-ink-2);
			overflow-wrap: anywhere;
		}

		.panel-title--path {
			font-family: var(--font-outlier);
			font-weight: 400;
			font-variant-numeric: tabular-nums slashed-zero;
		}

		.panel-actions {
			display: inline-flex;
			gap: var(--space-2xs);
		}

		.trace-list {
			display: flex;
			flex-direction: column;
			max-height: 36rem;
			overflow-y: auto;
		}

		.trace-item {
			display: flex;
			flex-direction: column;
			gap: var(--space-3xs);
			width: 100%;
			padding: var(--space-sm) var(--space-md);
			border: 0;
			border-bottom: var(--rule-hair) solid var(--color-rule);
			background: transparent;
			color: inherit;
			text-align: left;
			cursor: pointer;
			transition: background-color var(--dur-micro) var(--ease-out);
		}

		.trace-item:hover {
			background: var(--color-paper);
		}

		.trace-item:active {
			background: var(--color-paper-3);
		}

		.trace-item.active {
			background: var(--color-cool-soft);
		}

		.trace-item:focus-visible,
		.tab-btn:focus-visible {
			outline-offset: -2px;
		}

		.trace-item-header {
			display: flex;
			align-items: center;
			justify-content: space-between;
			gap: var(--space-xs);
		}

		.trace-index {
			font-family: var(--font-outlier);
			font-size: var(--text-xs);
			color: var(--color-cool);
			font-variant-numeric: tabular-nums slashed-zero;
		}

		.trace-trigger-badge {
			padding: 0 var(--space-2xs);
			border: var(--rule-hair) solid var(--color-bad);
			border-radius: var(--radius-chip);
			background: var(--color-bad-soft);
			color: var(--color-bad);
			font-size: var(--text-xs);
			font-weight: 500;
		}

		.trace-func {
			font-family: var(--font-outlier);
			font-size: var(--text-sm);
			color: var(--color-ink);
			overflow-wrap: anywhere;
		}

		.trace-arg-preview {
			color: var(--color-muted);
		}

		.trace-file-info {
			font-family: var(--font-outlier);
			font-size: var(--text-xs);
			color: var(--color-muted);
			overflow-wrap: anywhere;
			font-variant-numeric: tabular-nums slashed-zero;
		}

		.code-container {
			flex: 1;
			max-height: 36rem;
			overflow: auto;
			background: var(--color-paper-3);
			font-family: var(--font-outlier);
			font-size: var(--text-sm);
			--code-line-height: 1.6rem;
		}

		.line-numbers {
			display: none;
		}

		.code-lines {
			min-width: max-content;
			margin: 0;
			padding: var(--space-sm) 0;
			font: inherit;
			line-height: var(--code-line-height);
			white-space: pre;
		}

		.code-row {
			display: grid;
			grid-template-columns: 4rem minmax(0, 1fr);
			min-height: var(--code-line-height);
		}

		.code-row-number {
			padding-inline: var(--space-xs) var(--space-sm);
			text-align: right;
			color: var(--color-muted);
			user-select: none;
			font-variant-numeric: tabular-nums slashed-zero;
		}

		.code-line {
			display: block;
			padding-inline: var(--space-md);
			color: var(--color-ink-2);
			tab-size: 4;
		}

		.code-row.highlight {
			background: var(--color-bad-soft);
		}

		.code-row.highlight .code-row-number {
			color: var(--color-bad);
		}

		.code-row.highlight .code-line {
			color: var(--color-ink);
		}

		.arguments-content {
			max-height: 16rem;
			overflow-y: auto;
			padding: var(--space-md);
		}

		.arg-list {
			display: flex;
			flex-direction: column;
			gap: var(--space-sm);
		}

		.arg-row+.arg-row {
			padding-top: var(--space-sm);
			border-top: var(--rule-hair) solid var(--color-rule);
		}

		.arg-label {
			margin-bottom: var(--space-2xs);
			font-size: var(--text-xs);
			font-weight: 500;
			color: var(--color-muted);
		}

		.tabs-card {
			overflow: hidden;
			background: var(--color-paper-2);
			border: var(--rule-hair) solid var(--color-rule);
			border-radius: var(--radius-card);
		}

		.tabs-header {
			display: flex;
			overflow-x: auto;
			border-bottom: var(--rule-hair) solid var(--color-rule);
		}

		.tab-btn {
			min-height: 44px;
			padding: 0 var(--space-md);
			border: 0;
			border-bottom: 2px solid transparent;
			background: transparent;
			color: var(--color-muted);
			font-size: var(--text-sm);
			font-weight: 500;
			white-space: nowrap;
			cursor: pointer;
			transition: color var(--dur-micro) var(--ease-out), border-color var(--dur-micro) var(--ease-out);
		}

		.tab-btn:hover {
			color: var(--color-ink);
		}

		.tab-btn:active {
			background: var(--color-paper-3);
		}

		.tab-btn.active {
			border-bottom-color: var(--color-cool);
			color: var(--color-ink);
		}

		.tab-content {
			display: none;
			max-height: 24rem;
			overflow-y: auto;
			padding: var(--space-md);
		}

		.tab-content.active {
			display: block;
		}

		.tree-node,
		.tree-leaf {
			margin-left: var(--space-md);
			font-family: var(--font-outlier);
			font-size: var(--text-sm);
			line-height: 1.6;
			overflow-wrap: anywhere;
		}

		.tree-node-trigger {
			display: inline-flex;
			align-items: center;
			gap: var(--space-2xs);
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

		.tree-node-trigger:hover {
			background: var(--color-rule);
		}

		.tree-node-trigger:active {
			background: var(--color-paper-3);
		}

		.tree-node-trigger::before {
			content: "▸";
			color: var(--color-muted);
			transition: transform var(--dur-micro) var(--ease-out);
		}

		.tree-node.expanded>.tree-node-trigger::before {
			transform: rotate(90deg);
		}

		.tree-node-children {
			display: none;
			margin-left: var(--space-2xs);
			padding-left: var(--space-2xs);
			border-left: var(--rule-hair) solid var(--color-rule);
		}

		.tree-node.expanded>.tree-node-children {
			display: block;
		}

		.tree-key {
			color: var(--color-ink-2);
		}

		.tree-count,
		.tree-value-null {
			color: var(--color-muted);
		}

		.tree-value-string {
			color: var(--color-cool);
		}

		.tree-value-number,
		.tree-value-boolean {
			color: var(--color-ink);
			font-variant-numeric: tabular-nums slashed-zero;
		}

		.empty-message {
			color: var(--color-muted);
			font-size: var(--text-sm);
		}

		.trace-list>.empty-message {
			padding: var(--space-md);
		}

		.toast {
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

		.toast.show {
			transform: none;
		}

		.toast-icon {
			color: var(--color-ok);
		}
	</style>
	<script>
		const wpDebuggerData =
		<?php
		echo json_encode(
			array(
				'message'      => $message,
				'type'         => $type,
				'stackTrace'   => $stackTrace,
				'superglobals' => $superglobals,
				'triggerPoint' => $triggerPoint,
			),
			JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
		);
		?>
		;
		const wpDebuggerHomePath = <?php echo json_encode( rtrim( (string) getenv( 'HOME' ), '/' ), JSON_UNESCAPED_SLASHES ); ?>;
		let activeFrameIndex = 0;

		function showToast(e) {
			const t = document.getElementById("toast");
			document.getElementById("toast-message").innerText = e;
			t.classList.add("show");
			setTimeout(() => {
				t.classList.remove("show")
			}, 3000)
		}

		function fallbackCopyText(text, successMessage) {
			const textArea = document.createElement("textarea");
			textArea.value = text;
			textArea.style.top = "0";
			textArea.style.left = "0";
			textArea.style.position = "fixed";
			textArea.style.opacity = "0";
			document.body.appendChild(textArea);
			textArea.focus();
			textArea.select();
			try {
				const successful = document.execCommand("copy");
				if (successful) {
					showToast(successMessage);
				} else {
					console.error("Fallback copy failed");
				}
			} catch (err) {
				console.error("Fallback copy failed: ", err);
			}
			document.body.removeChild(textArea);
		}

		function copyText(e) {
			if (navigator.clipboard && navigator.clipboard.writeText) {
				navigator.clipboard.writeText(e).then(() => {
					showToast("Path copied to clipboard")
				}).catch(err => {
					console.error("Could not copy text: ", err);
					fallbackCopyText(e, "Path copied to clipboard");
				});
			} else {
				fallbackCopyText(e, "Path copied to clipboard");
			}
		}

		function getVsCodeUrl(filePath, line) {
			const encodedPath = String(filePath).split("/").map(encodeURIComponent).join("/");
			return `vscode://file${encodedPath}:${Number(line) || 1}:1`;
		}

		function getDisplayPath(filePath) {
			const path = String(filePath);
			if (!wpDebuggerHomePath) return path;
			if (path === wpDebuggerHomePath) return "~";
			if (path.startsWith(`${wpDebuggerHomePath}/`)) {
				return `~${path.slice(wpDebuggerHomePath.length)}`;
			}
			return path;
		}

		function selectFrame(e) {
			activeFrameIndex = e;
			document.querySelectorAll(".trace-item").forEach((t, r) => {
				t.classList.toggle("active", r === e);
				t.setAttribute("aria-current", r === e ? "true" : "false");
			});
			const r = wpDebuggerData.stackTrace[e];
			if (!r) return;
			document.getElementById("code-viewer-filename").innerText = getDisplayPath(r.filePath || r.file) + ":" + r.line;
			document.getElementById("code-viewer-copy-btn").onclick = () => copyText(r.filePath + ":" + r.line);
			document.getElementById("code-viewer-vscode-link").href = getVsCodeUrl(r.filePath, r.line);
			const o = Number(r.startLine) || Number(r.line) || 1,
				c = Number(r.line) || o,
				a = String(r.snippet || "").split(/\r?\n/),
				n = document.getElementById("code-line-numbers");
			n.innerHTML = "";
			const l = document.getElementById("code-content");
			l.innerHTML = "";
			a.forEach((e, t) => {
				const r = o + t;
				const a = document.createElement("span");
				a.className = "code-row-number";
				a.textContent = r;

				const s = document.createElement("span");
				s.className = "code-line";
				s.textContent = e || " ";

				const i = document.createElement("span");
				i.className = "code-row" + (r === c ? " highlight" : "");
				i.appendChild(a);
				i.appendChild(s);
				l.appendChild(i)
			});
			const s = l.querySelector(".highlight");
			if (s) {
				setTimeout(() => {
					s.scrollIntoView({ behavior: "smooth", block: "center", inline: "nearest" })
				}, 50)
			}
			renderArguments(r.args);
		}

		function renderArguments(args) {
			const container = document.getElementById("arguments-content");
			if (!container) return;
			container.innerHTML = "";

			if (!args || Object.keys(args).length === 0) {
				container.innerHTML = '<p class="empty-message">No arguments for this frame.</p>';
				return;
			}

			const list = document.createElement("div");
			list.className = "arg-list";

			for (const key in args) {
				if (args.hasOwnProperty(key)) {
					const argValue = args[key];
					const argRow = document.createElement("div");
					argRow.className = "arg-row";

					const argLabel = document.createElement("div");
					argLabel.className = "arg-label";
					argLabel.innerText = `Argument #${key}:`;

					const treeNode = buildTreeHTML(argValue);
					if (argValue !== null && typeof argValue === "object") {
						treeNode.classList.add("expanded");
						treeNode.firstChild.setAttribute("aria-expanded", "true");
					}

					argRow.appendChild(argLabel);
					argRow.appendChild(treeNode);
					list.appendChild(argRow);
				}
			}
			container.appendChild(list);
		}

		function copyForAI() {
			const e = wpDebuggerData;
			let t = "### WP Debugger: Error Report\n\n";
			t += `**Error:** \`${e.message}\`\n`;
			t += `**Location:** \`${getDisplayPath(e.triggerPoint.file)}\` on line \`${e.triggerPoint.line}\`\n\n`;
			t += "#### Primary Code Snippet:\n";
			if (e.stackTrace && e.stackTrace[0]) {
				const r = e.stackTrace[0];
				t += `File: \`${getDisplayPath(r.filePath || r.file)}\` (Lines ${r.startLine} - ${r.endLine})\n`;
				t += "```php\n";
				r.snippet.split("\n").forEach((snippetLine, index) => {
					const lineNumber = Number(r.startLine) + index;
					const marker = lineNumber === Number(r.line) ? " -> " : "    ";
					t += `${lineNumber}${marker}${snippetLine}\n`
				});
				t += "```\n\n"
			}
			t += "#### Stack Trace:\n";
			e.stackTrace.forEach((frame, index) => {
				let functionName = "main()";
				if (frame.function) {
					functionName = frame.class ? `${frame.class}${frame.type}${frame.function}()` : `${frame.function}()`
				}
				t += `${index}. \`${functionName}\` in \`${getDisplayPath(frame.filePath || frame.file)}:${frame.line}\`\n`
			});
			if (navigator.clipboard && navigator.clipboard.writeText) {
				navigator.clipboard.writeText(t).then(() => {
					showToast("Error report copied for AI!")
				}).catch(err => {
					console.error("Copy failed: ", err);
					fallbackCopyText(t, "Error report copied for AI!");
				});
			} else {
				fallbackCopyText(t, "Error report copied for AI!");
			}
		}

		function ignoreError() {
			document.cookie = "wp_debugger_ignore=1; path=/; max-age=31536000";
			showToast("Muted. Reloading…");
			setTimeout(() => {
				window.location.reload()
			}, 1000)
		}

		function switchTab(e) {
			document.querySelectorAll(".tab-btn").forEach(t => {
				t.classList.toggle("active", t.dataset.tab === e);
				t.setAttribute("aria-selected", t.dataset.tab === e ? "true" : "false");
			});
			document.querySelectorAll(".tab-content").forEach(t => {
				t.classList.toggle("active", t.id === `tab-content-${e}`);
			})
		}

		function createTree(e, t) {
			const r = document.getElementById(e);
			if (!r) return;
			r.innerHTML = "";
			if (Object.keys(t).length === 0) {
				r.innerHTML = '<p class="empty-message">No entries</p>';
				return
			}
			const o = buildTreeHTML(t);
			// Open the top level so a tab shows its entries at once.
			o.classList.add("expanded");
			o.firstChild.setAttribute("aria-expanded", "true");
			r.appendChild(o)
		}

		function buildTreeHTML(e, t = null) {
			const r = document.createElement("div");
			if (e !== null && typeof e === "object") {
				r.className = "tree-node";
				const o = document.createElement("button");
				o.type = "button";
				o.className = "tree-node-trigger";
				o.setAttribute("aria-expanded", "false");
				if (t !== null) {
					const e = document.createElement("span");
					e.className = "tree-key";
					e.innerText = `"${t}"`;
					o.appendChild(e);
					o.appendChild(document.createTextNode(": "))
				}
				const a = Array.isArray(e) ? `Array [${e.length}]` : `Object {${Object.keys(e).length}}`;
				const oLabel = document.createElement("span");
				oLabel.className = "tree-count";
				oLabel.innerText = a;
				o.appendChild(oLabel);
				r.appendChild(o);
				const n = document.createElement("div");
				n.className = "tree-node-children";
				for (const t in e) {
					if (e.hasOwnProperty(t)) {
						n.appendChild(buildTreeHTML(e[t], t))
					}
				}
				r.appendChild(n);
				o.addEventListener("click", e => {
					e.stopPropagation();
					o.setAttribute("aria-expanded", r.classList.toggle("expanded") ? "true" : "false");
				})
			} else {
				r.className = "tree-leaf";
				if (t !== null) {
					const e = document.createElement("span");
					e.className = "tree-key";
					e.innerText = `"${t}"`;
					r.appendChild(e);
					r.appendChild(document.createTextNode(": "))
				}
				const o = document.createElement("span");
				if (typeof e === "string") {
					o.className = "tree-value-string";
					o.innerText = `"${e}"`
				} else if (typeof e === "number") {
					o.className = "tree-value-number";
					o.innerText = e
				} else if (typeof e === "boolean") {
					o.className = "tree-value-boolean";
					o.innerText = e ? "true" : "false"
				} else if (e === null) {
					o.className = "tree-value-null";
					o.innerText = "null"
				} else {
					o.innerText = String(e)
				}
				r.appendChild(o)
			}
			return r
		}

		document.addEventListener("DOMContentLoaded", () => {
			if (wpDebuggerData.stackTrace && wpDebuggerData.stackTrace.length > 0) {
				selectFrame(0)
			}
			for (const e in wpDebuggerData.superglobals) {
				if (wpDebuggerData.superglobals.hasOwnProperty(e)) {
					const t = e.replace("$", "");
					createTree(`tree-${t}`, wpDebuggerData.superglobals[e])
				}
			}
		});
	</script>
</head>

<body>
	<div class="screen">
		<div class="container">
			<header class="error-header">
				<div class="error-info">
					<h1 class="error-title">
						<?php echo esc_html( $message ); ?>
					</h1>
					<p class="error-meta">
						<span class="error-type">
							<?php echo esc_html( $type ); ?>
						</span>
						<span class="error-where">
							<?php echo esc_html( get_display_path( $triggerPoint['file'] ) ); ?>:
							<?php echo esc_html( $triggerPoint['line'] ); ?>
						</span>
						<button type="button" class="btn-copy-path"
							onclick="copyText('<?php echo esc_js( $triggerPoint['file'] ); ?>:<?php echo esc_js( $triggerPoint['line'] ); ?>')"
							title="Copy path to clipboard" aria-label="Copy path to clipboard"><svg aria-hidden="true"
								width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
								stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
								<rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
								<path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
							</svg></button>
					</p>
				</div>
				<div class="actions-bar">
					<button type="button" class="btn btn--primary" onclick="copyForAI()"><svg aria-hidden="true"
							width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
							stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
							<path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
							<rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect>
						</svg>Copy for AI</button>
					<button type="button" class="btn" onclick="ignoreError()"><svg aria-hidden="true" width="16"
							height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
							stroke-linecap="round" stroke-linejoin="round">
							<path d="M18 6L6 18M6 6l12 12"></path>
						</svg>Ignore</button>
				</div>
			</header>
			<main class="workspace">
				<section class="workspace-panel" aria-labelledby="trace-title">
					<div class="panel-header">
						<h2 class="panel-title" id="trace-title">Stack trace</h2>
					</div>
					<div class="trace-list" id="trace-list">
						<?php if ( empty( $stackTrace ) ) : ?>
						<p class="empty-message">No stack trace available.</p>
						<?php else : ?>
						<?php foreach ( $stackTrace as $index => $frame ) : ?>
						<?php
							$is_trigger = ( $frame['filePath'] === $triggerPoint['file'] && $frame['line'] === $triggerPoint['line'] );
							$func_name  = 'main';
							if ( ! empty( $frame['function'] ) ) {
								$func_name = ! empty( $frame['class'] ) ? $frame['class'] . $frame['type'] . $frame['function'] : $frame['function'];
							}
							?>
						<button type="button" class="trace-item<?php echo 0 === $index ? ' active' : ''; ?>"
							aria-current="<?php echo 0 === $index ? 'true' : 'false'; ?>"
							onclick="selectFrame(<?php echo (int) $index; ?>)">
							<span class="trace-item-header"><span class="trace-index">#
									<?php echo (int) $index; ?>
								</span>
								<?php if ( $is_trigger ) : ?>
								<span class="trace-trigger-badge">Trigger point</span>
								<?php endif; ?>
							</span>
							<span class="trace-func">
								<?php echo esc_html( $func_name ); ?>(<span class="trace-arg-preview">
									<?php echo esc_html( get_args_preview( $frame['args'] ?? array() ) ); ?>
								</span>)
							</span>
							<span class="trace-file-info">
								<?php echo esc_html( get_display_path( $frame['file'] ?? $frame['filePath'] ?? '' ) ); ?>:
								<?php echo esc_html( $frame['line'] ?? '' ); ?>
							</span>
						</button>
						<?php endforeach; ?>
						<?php endif; ?>
					</div>
				</section>
				<div class="workspace-main">
					<section class="workspace-panel" aria-label="Code">
						<div class="panel-header">
							<span class="panel-title panel-title--path" id="code-viewer-filename">File code
								snippet</span>
							<div class="panel-actions">
								<a class="btn-copy-path" id="code-viewer-vscode-link" href="#" title="Open in VS Code"
									aria-label="Open in VS Code"><svg aria-hidden="true" width="16" height="16"
										viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
										stroke-linecap="round" stroke-linejoin="round">
										<path d="M15 3h6v6"></path>
										<path d="M10 14 21 3"></path>
										<path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
									</svg></a>
								<button type="button" class="btn-copy-path" id="code-viewer-copy-btn"
									title="Copy file path" aria-label="Copy file path"><svg aria-hidden="true"
										width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
										stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
										<rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
										<path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
									</svg></button>
							</div>
						</div>
						<div class="code-container">
							<div class="line-numbers" id="code-line-numbers"></div>
							<pre class="code-lines"><code id="code-content"></code></pre>
						</div>
					</section>
					<section class="workspace-panel" aria-labelledby="arguments-title">
						<div class="panel-header">
							<h2 class="panel-title" id="arguments-title">Arguments</h2>
						</div>
						<div class="arguments-content" id="arguments-content"></div>
					</section>
				</div>
			</main>
			<section class="tabs-card" aria-label="Request data">
				<div class="tabs-header" role="tablist">
					<?php foreach ( $tab_keys as $key ) : ?>
					<button type="button" class="tab-btn<?php echo $active_tab === $key ? ' active' : ''; ?>" role="tab"
						aria-selected="<?php echo $active_tab === $key ? 'true' : 'false'; ?>"
						aria-controls="tab-content-<?php echo esc_attr( $key ); ?>"
						data-tab="<?php echo esc_attr( $key ); ?>"
						onclick="switchTab('<?php echo esc_attr( $key ); ?>')">
						<?php echo esc_html( $tab_labels[ $key ] ); ?>
					</button>
					<?php endforeach; ?>
				</div>
				<?php foreach ( $tab_keys as $key ) : ?>
				<div class="tab-content<?php echo $active_tab === $key ? ' active' : ''; ?>"
					id="tab-content-<?php echo esc_attr( $key ); ?>" role="tabpanel">
					<div id="tree-<?php echo esc_attr( str_replace( '$', '', $key ) ); ?>"></div>
				</div>
				<?php endforeach; ?>
			</section>
		</div>
	</div>
	<div id="toast" class="toast" role="status" aria-live="polite"><svg aria-hidden="true" class="toast-icon" width="16"
			height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
			stroke-linejoin="round">
			<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
			<polyline points="22 4 12 14.01 9 11.01"></polyline>
		</svg><span id="toast-message">Copied!</span></div>

</body>

</html>