/**
 * Family Banking System - Main Vanilla JavaScript
 */

document.addEventListener('DOMContentLoaded', function () {
    // Sidebar toggle for mobile devices
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarWrapper = document.getElementById('sidebar-wrapper');

    if (sidebarToggle && sidebarWrapper) {
        sidebarToggle.addEventListener('click', function (e) {
            e.preventDefault();
            sidebarWrapper.classList.toggle('toggled');
        });
    }

    // Auto-formatting currency inputs with 3-digit comma separation
    const currencyInputs = document.querySelectorAll('.currency-input');
    currencyInputs.forEach(function (input) {
        input.addEventListener('input', function (e) {
            let value = this.value.replace(/,/g, '').replace(/[^0-9]/g, '');
            if (value) {
                this.value = parseInt(value, 10).toLocaleString('en-US');
            } else {
                this.value = '';
            }
        });
    });

    // Auto-hide alert messages after 6 seconds
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 6000);
    });
});

/**
 * Print specific HTML element by ID
 */
function printReceipt(elementId) {
    window.print();
}
