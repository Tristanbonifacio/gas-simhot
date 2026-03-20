/**
 * GAS-SIMHOT — assets/script.js
 * Handles the standalone root index.html demo widget.
 * All dashboard logic lives inside each dashboard file.
 */

function simulateLeak() {
    const level = Math.floor(Math.random() * (600 - 400 + 1)) + 400;
    updateDisplay(level);
}

function resetSystem() {
    updateDisplay(0);
}

function updateDisplay(value) {
    const display   = document.getElementById('gas-value');
    const indicator = document.getElementById('status-indicator');
    const alertText = document.getElementById('alert-text');

    if (!display) return;

    display.innerText = value;

    if (value >= 400) {
        indicator.className  = 'danger';
        alertText.innerText  = '⚠️ WARNING: LPG LEAK DETECTED!';
        alertText.style.color = '#ff4c4c';
    } else {
        indicator.className  = 'safe';
        alertText.innerText  = 'Status: System Normal';
        alertText.style.color = '';
    }
}
