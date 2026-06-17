import '../prism/index'
import './styles.css';

document.addEventListener('DOMContentLoaded', function () {
    const dumpContent = document.getElementById('dump-content');
    if (window.wpDebuggerData) {
        dumpContent.textContent = JSON.stringify(window.wpDebuggerData, null, 2);
        Prism.highlightElement(dumpContent);
    }
});
