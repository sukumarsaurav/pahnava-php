<?php
/**
 * Reports Page
 */

if (!$adminAuth->hasPermission('view_reports')) {
    setAdminFlashMessage('You do not have permission to access this page.', 'danger');
    redirect('admin/');
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Reports</h1>
            <p class="text-muted">Analytics and reports</p>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="fas fa-chart-bar fa-3x text-primary mb-3"></i>
            <h4>Reports & Analytics</h4>
            <p class="text-muted">This feature is currently under development.</p>
        </div>
    </div>
</div>
