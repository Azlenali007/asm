/**
 * ApexSMM Enterprise - Vanilla JavaScript & Alpine Utilities
 */

document.addEventListener('DOMContentLoaded', () => {
    // Auto-dismiss alerts after 5 seconds if desired
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.4s ease';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 400);
        }, 6000);
    });
});

// Global copy-to-clipboard utility
function copyToClipboard(text, btnElement) {
    if (!navigator.clipboard) {
        const temp = document.createElement('textarea');
        temp.value = text;
        document.body.appendChild(temp);
        temp.select();
        document.execCommand('copy');
        document.body.removeChild(temp);
    } else {
        navigator.clipboard.writeText(text);
    }

    if (btnElement) {
        const originalText = btnElement.innerText;
        btnElement.innerText = 'Copied!';
        setTimeout(() => {
            btnElement.innerText = originalText;
        }, 2000);
    }
}
