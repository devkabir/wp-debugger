<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WP Debugger - Variable Dump</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;500&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #090d16;
            --card-bg: #121824;
            --card-border: #1e293b;
            --card-hover-bg: #1a2336;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --accent-indigo: #818cf8;
            --accent-indigo-hover: #6366f1;
            --accent-indigo-bg: #1e1b4b;
            --accent-indigo-border: #312e81;
            --code-bg: #090d16;
            --font-sans: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            --font-mono: 'Fira Code', 'SF Mono', Monaco, Consolas, monospace;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-main);
            font-family: var(--font-sans);
            line-height: 1.5;
            padding: 2rem;
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        /* Header */
        .dump-header {
            background: linear-gradient(135deg, var(--card-bg) 0%, #151d2d 100%);
            border: 1px solid var(--card-border);
            border-left: 4px solid var(--accent-indigo);
            border-radius: 12px;
            padding: 1.5rem 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .dump-title-area {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .dump-badge {
            align-self: flex-start;
            background-color: var(--accent-indigo-bg);
            border: 1px solid var(--accent-indigo-border);
            color: var(--accent-indigo);
            padding: 0.25rem 0.6rem;
            border-radius: 9999px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .dump-title {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--text-main);
        }

        /* Search / Controls */
        .controls-area {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .search-input {
            background-color: var(--code-bg);
            border: 1px solid var(--card-border);
            color: var(--text-main);
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-family: var(--font-sans);
            font-size: 0.875rem;
            width: 250px;
            outline: none;
            transition: border-color 0.2s ease;
        }

        .search-input:focus {
            border-color: var(--accent-indigo);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            border: 1px solid transparent;
        }

        .btn-indigo {
            background-color: var(--accent-indigo-bg);
            color: var(--accent-indigo);
            border-color: var(--accent-indigo-border);
        }

        .btn-indigo:hover {
            background-color: var(--accent-indigo);
            color: var(--bg-color);
        }

        /* Dump Items */
        .dump-item-card {
            background-color: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            display: flex;
            flex-direction: column;
        }

        .card-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--card-border);
            background-color: rgba(255, 255, 255, 0.02);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-title {
            font-family: var(--font-mono);
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--accent-indigo);
        }

        .card-body {
            padding: 1.5rem;
            overflow: auto;
        }

        /* Tree Viewer */
        .tree-node {
            margin-left: 1.5rem;
            font-family: var(--font-mono);
            font-size: 0.85rem;
            line-height: 1.6;
        }

        .tree-node-trigger {
            cursor: pointer;
            user-select: none;
            color: var(--accent-indigo);
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }

        .tree-node-trigger::before {
            content: '▶';
            display: inline-block;
            font-size: 0.6rem;
            transition: transform 0.2s ease;
            color: var(--text-muted);
        }

        .tree-node.expanded > .tree-node-trigger::before {
            transform: rotate(90deg);
        }

        .tree-node-children {
            display: none;
            border-left: 1px dashed var(--card-border);
            margin-left: 0.5rem;
            padding-left: 0.5rem;
        }

        .tree-node.expanded > .tree-node-children {
            display: block;
        }

        .tree-leaf {
            font-family: var(--font-mono);
            font-size: 0.85rem;
            margin-left: 1.5rem;
            line-height: 1.6;
            word-break: break-all;
        }

        .tree-key {
            color: #f472b6; /* Pink */
        }

        .tree-value-string {
            color: #34d399; /* Green */
        }

        .tree-value-number {
            color: #fb7185; /* Light Red */
        }

        .tree-value-boolean {
            color: #60a5fa; /* Blue */
        }

        .tree-value-null {
            color: #94a3b8;
            font-style: italic;
        }

        /* Toast notification */
        .toast {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background-color: #1e293b;
            border: 1px solid var(--accent-indigo-border);
            color: #e2e8f0;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            z-index: 9999;
            transform: translateY(150%);
            transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            font-weight: 600;
            font-size: 0.875rem;
        }

        .toast.show {
            transform: translateY(0);
        }

        .toast-icon {
            color: var(--accent-indigo);
        }

        .highlight-match {
            background-color: rgba(254, 240, 138, 0.2);
            color: #fef08a;
            padding: 0 0.1rem;
            border-radius: 2px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header class="dump-header">
            <div class="dump-title-area">
                <span class="dump-badge">Var Dump</span>
                <h1 class="dump-title">Debugger Variable Inspector</h1>
            </div>
            <div class="controls-area">
                <input type="text" id="search-box" class="search-input" placeholder="Search keys or values..." oninput="handleSearch(this.value)">
                <button class="btn btn-indigo" onclick="expandAll()">Expand All</button>
                <button class="btn btn-indigo" onclick="collapseAll()">Collapse All</button>
            </div>
        </header>

        <!-- Variable Dumps -->
        <main id="dumps-container" style="display: flex; flex-direction: column; gap: 1.5rem;">
            <!-- Generated dynamically -->
        </main>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="toast">
        <svg class="toast-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
        <span id="toast-message">Copied!</span>
    </div>

    <script>
        // Retrieve variables dumped
        const wpDebuggerData = <?php echo json_encode($data, JSON_UNESCAPED_SLASHES); ?>;

        function showToast(message) {
            const toast = document.getElementById('toast');
            document.getElementById('toast-message').innerText = message;
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
            const val = wpDebuggerData[index];
            const jsonStr = JSON.stringify(val, null, 2);
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

        // Tree build helper
        function buildTreeHTML(value, key = null, filter = '') {
            const node = document.createElement('div');
            
            // Check value type
            if (value !== null && typeof value === 'object') {
                node.className = 'tree-node expanded'; // Expand by default
                
                const trigger = document.createElement('span');
                trigger.className = 'tree-node-trigger';
                
                if (key !== null) {
                    const keySpan = document.createElement('span');
                    keySpan.className = 'tree-key';
                    keySpan.innerHTML = highlightSearchText(`"${key}"`, filter);
                    trigger.appendChild(keySpan);
                    trigger.appendChild(document.createTextNode(': '));
                }
                
                const typeLabel = Array.isArray(value) ? `Array [${value.length}]` : `Object {${Object.keys(value).length}}`;
                const typeSpan = document.createElement('span');
                typeSpan.style.color = 'var(--text-muted)';
                typeSpan.innerText = typeLabel;
                trigger.appendChild(typeSpan);
                
                node.appendChild(trigger);

                const childrenContainer = document.createElement('div');
                childrenContainer.className = 'tree-node-children';
                
                let hasVisibleChildren = false;
                for (const k in value) {
                    if (value.hasOwnProperty(k)) {
                        const childNode = buildTreeHTML(value[k], k, filter);
                        if (childNode) {
                            childrenContainer.appendChild(childNode);
                            hasVisibleChildren = true;
                        }
                    }
                }
                
                node.appendChild(childrenContainer);

                // Add click listener to toggle expand
                trigger.addEventListener('click', (e) => {
                    e.stopPropagation();
                    node.classList.toggle('expanded');
                });

                // If filter is active and no children match, hide this node
                if (filter && !hasVisibleChildren && !matchesFilter(key, filter)) {
                    return null;
                }
                
            } else {
                // If filter is active and leaf does not match key or value, hide it
                if (filter && !matchesFilter(key, filter) && !matchesFilter(String(value), filter)) {
                    return null;
                }

                node.className = 'tree-leaf';
                
                if (key !== null) {
                    const keySpan = document.createElement('span');
                    keySpan.className = 'tree-key';
                    keySpan.innerHTML = highlightSearchText(`"${key}"`, filter);
                    node.appendChild(keySpan);
                    node.appendChild(document.createTextNode(': '));
                }

                const valueSpan = document.createElement('span');
                if (typeof value === 'string') {
                    valueSpan.className = 'tree-value-string';
                    valueSpan.innerHTML = highlightSearchText(`"${value}"`, filter);
                } else if (typeof value === 'number') {
                    valueSpan.className = 'tree-value-number';
                    valueSpan.innerHTML = highlightSearchText(String(value), filter);
                } else if (typeof value === 'boolean') {
                    valueSpan.className = 'tree-value-boolean';
                    valueSpan.innerHTML = highlightSearchText(value ? 'true' : 'false', filter);
                } else if (value === null) {
                    valueSpan.className = 'tree-value-null';
                    valueSpan.innerHTML = highlightSearchText('null', filter);
                } else {
                    valueSpan.innerHTML = highlightSearchText(String(value), filter);
                }
                node.appendChild(valueSpan);
            }

            return node;
        }

        function matchesFilter(text, filter) {
            if (!text) return false;
            return text.toLowerCase().includes(filter.toLowerCase());
        }

        function highlightSearchText(text, filter) {
            if (!filter) return text;
            const escFilter = filter.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&');
            const regex = new RegExp(`(${escFilter})`, 'gi');
            return text.replace(regex, '<span class="highlight-match">$1</span>');
        }

        // Render Dumps
        function renderDumps(filter = '') {
            const container = document.getElementById('dumps-container');
            container.innerHTML = '';

            wpDebuggerData.forEach((val, idx) => {
                const card = document.createElement('div');
                card.className = 'dump-item-card';

                const header = document.createElement('div');
                header.className = 'card-header';

                const title = document.createElement('span');
                title.className = 'card-title';
                title.innerText = `Dump #${idx + 1} (${typeof val})`;
                header.appendChild(title);

                const copyBtn = document.createElement('button');
                copyBtn.className = 'btn btn-indigo';
                copyBtn.style.padding = '0.3rem 0.75rem';
                copyBtn.style.fontSize = '0.75rem';
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
                    body.innerHTML = '<span class="empty-message">No matching keys or values found</span>';
                }

                card.appendChild(body);
                container.appendChild(card);
            });
        }

        // Search handler
        function handleSearch(val) {
            renderDumps(val);
        }

        // Expand/Collapse all nodes
        function expandAll() {
            const nodes = document.querySelectorAll('.tree-node');
            nodes.forEach(n => n.classList.add('expanded'));
        }

        function collapseAll() {
            const nodes = document.querySelectorAll('.tree-node');
            nodes.forEach(n => n.classList.remove('expanded'));
        }

        // Init
        document.addEventListener('DOMContentLoaded', () => {
            renderDumps();
        });
    </script>
</body>
</html>
