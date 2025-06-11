/**
 * Admin Panel JavaScript
 * Common functionality for admin interface
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
        alerts.forEach(function(alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
    
    // Confirm delete actions
    const deleteButtons = document.querySelectorAll('[data-confirm-delete]');
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            const message = this.getAttribute('data-confirm-delete') || 'Are you sure you want to delete this item?';
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        });
    });
    
    // Form validation
    const forms = document.querySelectorAll('.needs-validation');
    forms.forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });
    
    // File upload preview
    const fileInputs = document.querySelectorAll('input[type="file"][data-preview]');
    fileInputs.forEach(function(input) {
        input.addEventListener('change', function() {
            const file = this.files[0];
            const previewId = this.getAttribute('data-preview');
            const preview = document.getElementById(previewId);
            
            if (file && preview) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });
    });
    
    // Sidebar toggle for mobile
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.sidebar');
    
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('show');
        });
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 991 && 
                !sidebar.contains(e.target) && 
                !sidebarToggle.contains(e.target) &&
                sidebar.classList.contains('show')) {
                sidebar.classList.remove('show');
            }
        });
    }
});

/**
 * Show notification
 */
function showNotification(message, type = 'info', duration = 5000) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Insert at the beginning of main content
    const mainContent = document.querySelector('.main-content');
    if (mainContent) {
        mainContent.insertBefore(alertDiv, mainContent.firstChild);
    }
    
    // Auto-hide after duration
    if (duration > 0) {
        setTimeout(() => {
            const alert = new bootstrap.Alert(alertDiv);
            alert.close();
        }, duration);
    }
}

/**
 * AJAX helper function
 */
function makeAjaxRequest(url, options = {}) {
    const defaultOptions = {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': window.csrfToken
        }
    };
    
    const finalOptions = { ...defaultOptions, ...options };
    
    return fetch(url, finalOptions)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .catch(error => {
            console.error('AJAX request failed:', error);
            showNotification('An error occurred. Please try again.', 'danger');
            throw error;
        });
}

/**
 * Format currency for display
 */
function formatCurrency(amount) {
    return '₹' + parseFloat(amount).toLocaleString('en-IN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

/**
 * Format date for display
 */
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-IN', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

/**
 * Debounce function
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Copy to clipboard
 */
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showNotification('Copied to clipboard!', 'success', 2000);
    }).catch(() => {
        showNotification('Failed to copy to clipboard', 'danger', 2000);
    });
}

/**
 * Toggle loading state on button
 */
function toggleButtonLoading(button, loading = true) {
    if (loading) {
        button.disabled = true;
        button.dataset.originalText = button.innerHTML;
        button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Loading...';
    } else {
        button.disabled = false;
        button.innerHTML = button.dataset.originalText || button.innerHTML;
    }
}

/**
 * Bulk actions handler
 */
function handleBulkActions() {
    const bulkActionSelect = document.getElementById('bulkAction');
    const bulkActionBtn = document.getElementById('bulkActionBtn');
    const checkboxes = document.querySelectorAll('input[name="selected_items[]"]:checked');
    
    if (!bulkActionSelect || !bulkActionBtn) return;
    
    const action = bulkActionSelect.value;
    const selectedIds = Array.from(checkboxes).map(cb => cb.value);
    
    if (!action) {
        showNotification('Please select an action', 'warning');
        return;
    }
    
    if (selectedIds.length === 0) {
        showNotification('Please select at least one item', 'warning');
        return;
    }
    
    if (!confirm(`Are you sure you want to ${action} ${selectedIds.length} item(s)?`)) {
        return;
    }
    
    toggleButtonLoading(bulkActionBtn, true);
    
    makeAjaxRequest('ajax/bulk-actions.php', {
        method: 'POST',
        body: JSON.stringify({
            action: action,
            ids: selectedIds
        })
    })
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification(data.message || 'Action failed', 'danger');
        }
    })
    .finally(() => {
        toggleButtonLoading(bulkActionBtn, false);
    });
}

/**
 * Select all checkboxes
 */
function toggleSelectAll(masterCheckbox) {
    const checkboxes = document.querySelectorAll('input[name="selected_items[]"]');
    checkboxes.forEach(checkbox => {
        checkbox.checked = masterCheckbox.checked;
    });
    updateBulkActionButton();
}

/**
 * Update bulk action button state
 */
function updateBulkActionButton() {
    const checkboxes = document.querySelectorAll('input[name="selected_items[]"]:checked');
    const bulkActionBtn = document.getElementById('bulkActionBtn');
    
    if (bulkActionBtn) {
        bulkActionBtn.disabled = checkboxes.length === 0;
    }
}

/**
 * Initialize data tables
 */
function initializeDataTable(tableId, options = {}) {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    // Add sorting functionality
    const headers = table.querySelectorAll('th[data-sort]');
    headers.forEach(header => {
        header.style.cursor = 'pointer';
        header.addEventListener('click', function() {
            const column = this.dataset.sort;
            const currentOrder = this.dataset.order || 'asc';
            const newOrder = currentOrder === 'asc' ? 'desc' : 'asc';
            
            // Update URL with sort parameters
            const url = new URL(window.location);
            url.searchParams.set('sort', column);
            url.searchParams.set('order', newOrder);
            window.location.href = url.toString();
        });
    });
}

/**
 * Initialize charts
 */
function initializeChart(canvasId, config) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return null;
    
    const ctx = canvas.getContext('2d');
    return new Chart(ctx, config);
}

/**
 * Export data
 */
function exportData(format, endpoint) {
    const url = new URL(endpoint, window.location.origin);
    url.searchParams.set('export', format);
    
    // Add current filters
    const currentUrl = new URL(window.location);
    for (const [key, value] of currentUrl.searchParams) {
        if (key !== 'page') {
            url.searchParams.set(key, value);
        }
    }
    
    window.open(url.toString(), '_blank');
}

/**
 * Auto-save form data
 */
function enableAutoSave(formId, endpoint, interval = 30000) {
    const form = document.getElementById(formId);
    if (!form) return;
    
    let autoSaveTimer;
    let hasChanges = false;
    
    // Track changes
    form.addEventListener('input', () => {
        hasChanges = true;
        clearTimeout(autoSaveTimer);
        autoSaveTimer = setTimeout(autoSave, interval);
    });
    
    function autoSave() {
        if (!hasChanges) return;
        
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        
        makeAjaxRequest(endpoint, {
            method: 'POST',
            body: JSON.stringify(data)
        })
        .then(response => {
            if (response.success) {
                hasChanges = false;
                showNotification('Draft saved', 'info', 2000);
            }
        })
        .catch(() => {
            // Retry after 5 seconds
            setTimeout(autoSave, 5000);
        });
    }
}

// Global event listeners
document.addEventListener('change', function(e) {
    if (e.target.name === 'selected_items[]') {
        updateBulkActionButton();
    }
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + S to save
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        const saveBtn = document.querySelector('button[type="submit"], .btn-save');
        if (saveBtn) saveBtn.click();
    }
    
    // Escape to close modals
    if (e.key === 'Escape') {
        const openModal = document.querySelector('.modal.show');
        if (openModal) {
            const modal = bootstrap.Modal.getInstance(openModal);
            if (modal) modal.hide();
        }
    }
});
