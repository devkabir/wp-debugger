import"./prism-smQVsIuM.js";const g=`<!-- Main App Shell -->
<template id="app-template">
    <div
        class="p-4 bg-gray-200 text-gray-800 absolute top-0 right-0 z-wp w-full mx-auto"
    >
        <section class="bg-white shadow-lg rounded-lg p-8 w-full">
            <header class="my-6 space-y-4">
                <h1
                    class="bg-red-50 border-l-4 border-red-400 text-red-700 p-4 rounded"
                    data-content="message"
                ></h1>
            </header>

            <div class="mb-6">
                <!-- Stack Trace Section -->
                <section class="exception__stack">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h2 class="text-xl font-semibold">Stack Trace</h2>
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                class="px-3 py-2 text-sm font-medium text-slate-800 bg-white border border-slate-200 rounded hover:bg-slate-50 shadow-sm"
                                data-action="copy-stack"
                            >
                                Copy for AI
                            </button>
                            <button
                                type="button"
                                class="px-3 py-2 text-sm font-medium text-slate-800 bg-white border border-slate-200 rounded hover:bg-slate-50 shadow-sm"
                                data-action="dismiss-error"
                            >
                                Ignore error
                            </button>
                        </div>
                    </div>
                    <div data-slot="stack-trace"></div>
                </section>

                <!-- Superglobals Section -->
                <aside class="my-6 space-y-4 w-full">
                    <h2 class="text-xl font-semibold">Superglobals</h2>
                    <div data-slot="superglobals"></div>
                </aside>
            </div>
        </section>
    </div>
</template>

<!-- Reusable Details/Summary Pattern (used for: stack frames, variables, arguments) -->
<template id="details-item">
    <details open>
        <summary
            class="cursor-pointer text-md font-medium p-2 rounded hover:bg-gray-100"
            data-content="header"
        ></summary>
        <div class="details-item__body" data-slot="content"></div>
    </details>
</template>

<!-- Code Block Pattern (used for: PHP code, variable dumps) -->
<template id="code-block">
    <pre><code data-content="code"></code></pre>
</template>

<!-- Link Pattern (used for: file paths) -->
<template id="file-link">
    <a
        class="text-indigo-600 hover:underline"
        data-href="link"
        data-content="text"
        title="Open in editor"
    ></a>
</template>

<!-- Empty State Pattern (reusable for any empty state) -->
<template id="empty-state">
    <p class="text-muted" data-content="message"></p>
</template>

<!-- Section Header Pattern (optional, for consistent section titles) -->
<template id="section-header">
    <div class="section-header">
        <h3 class="text-xl font-semibold" data-content="title"></h3>
    </div>
</template>
`;class f{constructor(){this.templates=new Map,this.loadTemplates()}loadTemplates(){new DOMParser().parseFromString(g,"text/html").querySelectorAll("template").forEach(n=>{this.templates.set(n.id,n.content.cloneNode(!0))})}get(e){return this.templates.get(e)?.cloneNode(!0)}bind(e,t){return e&&(Object.entries(t).forEach(([r,n])=>{const s=e.querySelector(`[data-content="${r}"]`);s&&(s.textContent=n)}),Object.entries(t).forEach(([r,n])=>{const s=e.querySelector(`[data-href="${r}"]`);s&&(s.href=n)}),t.attrs&&Object.entries(t.attrs).forEach(([r,n])=>{const s=e.querySelector(r);s&&Object.entries(n).forEach(([a,i])=>{s.setAttribute(a,i)})}),e)}render(e,t={}){const r=this.get(e);if(!r)return"";this.bind(r,t);const n=document.createElement("div");return n.appendChild(r),n.innerHTML}}class b{constructor(e){this.data=e,this.app=document.getElementById("wp-debugger"),this.templates=new f}render(){if(!this.app||!this.data){console.error("WP Debugger: Missing app element or data");return}const e=this.templates.get("app-template");if(!e)return;this.templates.bind(e,{message:this.data.message});const t=e.querySelector('[data-slot="stack-trace"]'),r=e.querySelector('[data-slot="superglobals"]');t&&(t.innerHTML=this.renderStackTrace()),r&&(r.innerHTML=this.renderSuperglobals());const n=e.querySelector('[data-action="copy-stack"]');n&&n.addEventListener("click",()=>this.copyStackTrace(n));const s=e.querySelector('[data-action="dismiss-error"]');s&&s.addEventListener("click",()=>this.dismissError()),this.app.innerHTML="",this.app.appendChild(e),requestAnimationFrame(()=>{window.Prism&&Prism.highlightAll()})}renderStackTrace(){return!this.data.stackTrace||this.data.stackTrace.length===0?this.templates.render("empty-state",{message:"No stack trace available"}):this.data.stackTrace.map((e,t)=>this.renderCodeFrame(e,t)).join("")}async copyStackTrace(e){const t=this.buildStackTraceText();if(!t)return;const r=e?.textContent,n=s=>{e&&(e.textContent=s)};try{navigator.clipboard?.writeText?await navigator.clipboard.writeText(t):this.copyWithFallback(t),n("Copied!")}catch(s){console.error("WP Debugger: Failed to copy stack trace",s),n("Copy failed")}finally{e&&setTimeout(()=>{n(r??"Copy for AI")},1500)}}copyWithFallback(e){const t=document.createElement("textarea");t.value=e,t.setAttribute("readonly",""),t.style.position="absolute",t.style.left="-9999px",document.body.appendChild(t),t.select(),document.execCommand("copy"),document.body.removeChild(t)}buildStackTraceText(){if(!this.data?.stackTrace||this.data.stackTrace.length===0)return"";const e=this.data.stackTrace.map((t,r)=>{const n=[`${r+1}) ${t.file}:${t.line}`],s=this.prepareArgsForCopy(t.args);if(s&&(n.push("  Args:"),n.push(this.indentBlock(s,4))),t.snippet){n.push("  Code:");const a=t.snippet.replace(/\t/g,"  ").trimEnd();n.push(this.indentBlock(a,4))}return n.join(`
`)}).join(`

`);return`Error: ${this.data.message}

Stack Trace:
${e}`}renderCodeFrame(e,t){const r=this.templates.get("details-item");if(!r)return"";const n=r.querySelector("details");n&&(t===0&&n.setAttribute("open",""),n.classList.add("code-frame"));const s=`vscode://file/${e.filePath}:${e.line}`,i=`${this.templates.render("file-link",{link:s,text:e.file})} <span class="code-frame__line">Line ${e.line}</span>`,l=r.querySelector('[data-content="header"]');l&&(l.innerHTML=i);const p=r.querySelector('[data-slot="content"]');if(p){const d=this.templates.get("code-block");if(d){const c=d.querySelector("pre"),o=d.querySelector("code");c&&(c.setAttribute("data-line",e.line),c.setAttribute("data-line-offset",e.startLine)),o&&(o.setAttribute("class","language-php"),o.textContent=e.snippet),p.appendChild(d)}if(e.args&&Object.keys(e.args).length>0){const c=this.renderArguments(e.args),o=document.createElement("div");for(o.innerHTML=c;o.firstChild;)p.appendChild(o.firstChild)}}const m=document.createElement("div");return m.appendChild(r),m.innerHTML}renderArguments(e){const t=this.templates.render("section-header",{title:"Arguments"}),r=this.renderVariableDump(e);return`<div class="mt-4">${t}${r}</div>`}renderSuperglobals(){return!this.data.superglobals||Object.keys(this.data.superglobals).length===0?this.templates.render("empty-state",{message:"No superglobals available"}):Object.entries(this.data.superglobals).map(([e,t],r)=>this.renderVariable(e,t,r===0)).join("")}renderVariable(e,t,r=!1){const n=this.templates.get("details-item");if(!n)return"";const s=n.querySelector("details");s&&(r&&s.setAttribute("open",""),s.classList.add("variable-item")),this.templates.bind(n,{header:e});const a=n.querySelector('[data-slot="content"]');a&&(a.innerHTML=this.renderVariableDump(t));const i=document.createElement("div");return i.appendChild(n),i.innerHTML}renderVariableDump(e){const t=this.formatVariable(e);return this.renderCodeBlock({code:t,language:"json"})}renderCodeBlock({code:e,language:t="php",line:r,lineOffset:n}){const s=this.templates.get("code-block");if(!s)return"";const a=s.querySelector("pre"),i=s.querySelector("code");a&&(typeof r=="number"&&a.setAttribute("data-line",r),typeof n=="number"&&a.setAttribute("data-line-offset",n)),i&&(i.className=`language-${t}`,i.textContent=e);const l=document.createElement("div");return l.appendChild(s),l.innerHTML}formatVariable(e,t=0){const r="  ".repeat(t);if(e===null)return"null";if(typeof e=="boolean")return e?"true":"false";if(typeof e=="string")return`"${e}"`;if(typeof e=="number")return String(e);if(Array.isArray(e))return e.length===0?"[]":`[
${e.map((s,a)=>`${r}  [${a}] => ${this.formatVariable(s,t+1)}`).join(`
`)}
${r}]`;if(typeof e=="object"){const n=Object.keys(e);return n.length===0?"{}":`{
${n.map(a=>`${r}  "${a}" => ${this.formatVariable(e[a],t+1)}`).join(`
`)}
${r}}`}return String(e)}indentBlock(e,t=2){const r=" ".repeat(t);return e.split(`
`).map(n=>`${r}${n}`).join(`
`)}prepareArgsForCopy(e){return!e||Object.keys(e).length===0?"":Array.isArray(e)&&this.looksLikeErrorHandlerArgs(e)?"omitted (PHP error handler args)":this.formatVariable(e)}looksLikeErrorHandlerArgs(e){if(!Array.isArray(e)||e.length<4)return!1;const[t,r,n,s]=e;return typeof t=="number"&&typeof r=="string"&&typeof n=="string"&&(typeof s=="number"||typeof s=="string")}dismissError(){this.app&&(this.app.innerHTML="",this.app.style.display="none",this.rememberIgnorePreference())}rememberIgnorePreference(){const e=this.generateErrorSignature();if(!e)return;const t=3600*24*7,r=encodeURIComponent(e);document.cookie=`wp_debugger_ignore_${r}=1; path=/; max-age=${t}`}generateErrorSignature(){if(!this.data?.message)return null;const e=this.data.stackTrace?.[0]?.file||"unknown",t=`${this.data.message}|${e}`;return this.simpleHash(t)}simpleHash(e){let t=0;for(let r=0;r<e.length;r++){const n=e.charCodeAt(r);t=(t<<5)-t+n,t=t&t}return Math.abs(t).toString(36)}}document.readyState==="loading"?document.addEventListener("DOMContentLoaded",h):h();function h(){typeof wpDebuggerData<"u"?new b(wpDebuggerData).render():console.error("WP Debugger: wpDebuggerData not found")}
