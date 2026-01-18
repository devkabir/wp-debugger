import '../prism/index'
import './styles.css';

document.addEventListener('DOMContentLoaded', function () {
    const dumpContent = document.getElementById('dump-content');
    if (window.wpDebuggerDumpData) {
        dumpContent.textContent = JSON.stringify(window.wpDebuggerDumpData, null, 2);
        Prism.highlightElement(dumpContent);
    }
});
