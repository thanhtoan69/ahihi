<?php
/**
 * Subscribers Management View
 * 
 * @package Environmental_Email_Marketing
 */

if (!defined('ABSPATH')) {
    exit;
}

$subscriber_manager = EEM_Subscriber_Manager::get_instance();
$lists = $subscriber_manager->get_lists();
$segments = $subscriber_manager->get_segments();
?>

<div class="eem-admin-page">
    <div class="eem-page-header">
        <div class="eem-page-title">
            <h1>
                <span class="eem-icon">👥</span>
                Subscribers Management
            </h1>
            <p class="eem-page-description">
                Manage your email subscribers, lists, and segments
            </p>
        </div>
        <div class="eem-page-actions">
            <button class="eem-btn eem-btn-secondary" id="export-subscribers">
                <span class="dashicons dashicons-download"></span>
                Export Subscribers
            </button>
            <button class="eem-btn eem-btn-secondary" id="import-subscribers">
                <span class="dashicons dashicons-upload"></span>
                Import Subscribers
            </button>
            <button class="eem-btn eem-btn-primary" id="add-subscriber">
                <span class="dashicons dashicons-plus"></span>
                Add Subscriber
            </button>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="eem-stats-grid eem-mb-4">
        <div class="eem-stat-card">
            <div class="eem-stat-icon">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <div class="eem-stat-content">
                <div class="eem-stat-number" id="total-subscribers">-</div>
                <div class="eem-stat-label">Total Subscribers</div>
            </div>
        </div>
        <div class="eem-stat-card">
            <div class="eem-stat-icon">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="eem-stat-content">
                <div class="eem-stat-number" id="active-subscribers">-</div>
                <div class="eem-stat-label">Active Subscribers</div>
            </div>
        </div>
        <div class="eem-stat-card">
            <div class="eem-stat-icon">
                <span class="dashicons dashicons-calendar-alt"></span>
            </div>
            <div class="eem-stat-content">
                <div class="eem-stat-number" id="new-this-month">-</div>
                <div class="eem-stat-label">New This Month</div>
            </div>
        </div>
        <div class="eem-stat-card">
            <div class="eem-stat-icon">
                <span class="dashicons dashicons-chart-line"></span>
            </div>
            <div class="eem-stat-content">
                <div class="eem-stat-number" id="growth-rate">-</div>
                <div class="eem-stat-label">Growth Rate</div>
            </div>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="eem-filters-section">
        <div class="eem-filters-row">
            <div class="eem-filter-group">
                <label for="subscriber-search">Search Subscribers:</label>
                <input type="text" id="subscriber-search" placeholder="Search by name or email..." class="eem-input">
            </div>
            <div class="eem-filter-group">
                <label for="filter-status">Status:</label>
                <select id="filter-status" class="eem-select">
                    <option value="">All Statuses</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="pending">Pending Confirmation</option>
                    <option value="unsubscribed">Unsubscribed</option>
                </select>
            </div>
            <div class="eem-filter-group">
                <label for="filter-list">List:</label>
                <select id="filter-list" class="eem-select">
                    <option value="">All Lists</option>
                    <?php foreach ($lists as $list): ?>
                        <option value="<?php echo esc_attr($list->id); ?>">
                            <?php echo esc_html($list->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="eem-filter-group">
                <label for="filter-segment">Segment:</label>
                <select id="filter-segment" class="eem-select">
                    <option value="">All Segments</option>
                    <?php foreach ($segments as $segment): ?>
                        <option value="<?php echo esc_attr($segment->id); ?>">
                            <?php echo esc_html($segment->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="eem-filter-group">
                <button class="eem-btn eem-btn-secondary" id="clear-filters">
                    Clear Filters
                </button>
            </div>
        </div>
    </div>

    <!-- Bulk Actions -->
    <div class="eem-bulk-actions" style="display: none;">
        <div class="eem-bulk-actions-content">
            <span class="eem-selected-count">0 subscribers selected</span>
            <select id="bulk-action" class="eem-select">
                <option value="">Bulk Actions</option>
                <option value="activate">Activate</option>
                <option value="deactivate">Deactivate</option>
                <option value="add-to-list">Add to List</option>
                <option value="remove-from-list">Remove from List</option>
                <option value="add-to-segment">Add to Segment</option>
                <option value="remove-from-segment">Remove from Segment</option>
                <option value="delete">Delete</option>
                <option value="export">Export Selected</option>
            </select>
            <button class="eem-btn eem-btn-primary" id="apply-bulk-action">Apply</button>
            <button class="eem-btn eem-btn-secondary" id="cancel-bulk-selection">Cancel</button>
        </div>
    </div>

    <!-- Subscribers Table -->
    <div class="eem-table-container">
        <table class="eem-table" id="subscribers-table">
            <thead>
                <tr>
                    <th class="eem-table-checkbox">
                        <input type="checkbox" id="select-all-subscribers">
                    </th>
                    <th class="eem-sortable" data-sort="email">
                        Email Address
                        <span class="eem-sort-icon"></span>
                    </th>
                    <th class="eem-sortable" data-sort="name">
                        Name
                        <span class="eem-sort-icon"></span>
                    </th>
                    <th class="eem-sortable" data-sort="status">
                        Status
                        <span class="eem-sort-icon"></span>
                    </th>
                    <th class="eem-sortable" data-sort="eco_score">
                        Eco Score
                        <span class="eem-sort-icon"></span>
                    </th>
                    <th class="eem-sortable" data-sort="subscription_date">
                        Subscribed
                        <span class="eem-sort-icon"></span>
                    </th>
                    <th class="eem-sortable" data-sort="last_activity">
                        Last Activity
                        <span class="eem-sort-icon"></span>
                    </th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="subscribers-table-body">
                <!-- Subscribers will be loaded here via AJAX -->
            </tbody>
        </table>
        
        <div class="eem-table-loading" id="subscribers-loading" style="display: none;">
            <div class="eem-spinner"></div>
            <p>Loading subscribers...</p>
        </div>
        
        <div class="eem-table-empty" id="subscribers-empty" style="display: none;">
            <div class="eem-empty-icon">👥</div>
            <h3>No Subscribers Found</h3>
            <p>No subscribers match your current filters. Try adjusting your search criteria or add your first subscriber.</p>
            <button class="eem-btn eem-btn-primary" onclick="document.getElementById('add-subscriber').click()">
                Add First Subscriber
            </button>
        </div>
    </div>

    <!-- Pagination -->
    <div class="eem-pagination" id="subscribers-pagination">
        <!-- Pagination will be generated here -->
    </div>
</div>

<!-- Add/Edit Subscriber Modal -->
<div id="subscriber-modal" class="eem-modal" style="display: none;">
    <div class="eem-modal-content eem-modal-large">
        <div class="eem-modal-header">
            <h3 id="subscriber-modal-title">Add New Subscriber</h3>
            <button class="eem-modal-close">&times;</button>
        </div>
        <div class="eem-modal-body">
            <form id="subscriber-form">
                <input type="hidden" id="subscriber-id" name="subscriber_id">
                
                <div class="eem-form-row">
                    <div class="eem-form-group eem-col-6">
                        <label for="subscriber-email">Email Address *</label>
                        <input type="email" id="subscriber-email" name="email" class="eem-input" required>
                    </div>
                    <div class="eem-form-group eem-col-6">
                        <label for="subscriber-status">Status</label>
                        <select id="subscriber-status" name="status" class="eem-select">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="pending">Pending Confirmation</option>
                        </select>
                    </div>
                </div>
                
                <div class="eem-form-row">
                    <div class="eem-form-group eem-col-6">
                        <label for="subscriber-first-name">First Name</label>
                        <input type="text" id="subscriber-first-name" name="first_name" class="eem-input">
                    </div>
                    <div class="eem-form-group eem-col-6">
                        <label for="subscriber-last-name">Last Name</label>
                        <input type="text" id="subscriber-last-name" name="last_name" class="eem-input">
                    </div>
                </div>
                
                <div class="eem-form-group">
                    <label for="subscriber-lists">Lists</label>
                    <div class="eem-checkbox-group" id="subscriber-lists">
                        <?php foreach ($lists as $list): ?>
                            <label class="eem-checkbox-label">
                                <input type="checkbox" name="lists[]" value="<?php echo esc_attr($list->id); ?>">
                                <?php echo esc_html($list->name); ?>
                                <span class="eem-checkbox-count">(<?php echo esc_html($list->subscriber_count); ?> subscribers)</span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="eem-form-group">
                    <label for="subscriber-preferences">Environmental Preferences</label>
                    <div class="eem-checkbox-group">
                        <label class="eem-checkbox-label">
                            <input type="checkbox" name="preferences[]" value="renewable_energy">
                            Renewable Energy
                        </label>
                        <label class="eem-checkbox-label">
                            <input type="checkbox" name="preferences[]" value="sustainable_living">
                            Sustainable Living
                        </label>
                        <label class="eem-checkbox-label">
                            <input type="checkbox" name="preferences[]" value="climate_change">
                            Climate Change
                        </label>
                        <label class="eem-checkbox-label">
                            <input type="checkbox" name="preferences[]" value="conservation">
                            Conservation
                        </label>
                        <label class="eem-checkbox-label">
                            <input type="checkbox" name="preferences[]" value="green_technology">
                            Green Technology
                        </label>
                        <label class="eem-checkbox-label">
                            <input type="checkbox" name="preferences[]" value="environmental_policy">
                            Environmental Policy
                        </label>
                    </div>
                </div>
                
                <div class="eem-form-row">
                    <div class="eem-form-group eem-col-6">
                        <label for="subscriber-location">Location</label>
                        <input type="text" id="subscriber-location" name="location" class="eem-input" placeholder="City, Country">
                    </div>
                    <div class="eem-form-group eem-col-6">
                        <label for="subscriber-source">Source</label>
                        <select id="subscriber-source" name="source" class="eem-select">
                            <option value="manual">Manual Entry</option>
                            <option value="website">Website Form</option>
                            <option value="import">Import</option>
                            <option value="api">API</option>
                            <option value="referral">Referral</option>
                        </select>
                    </div>
                </div>
                
                <div class="eem-form-group">
                    <label for="subscriber-notes">Notes</label>
                    <textarea id="subscriber-notes" name="notes" class="eem-textarea" rows="3" placeholder="Additional notes about this subscriber..."></textarea>
                </div>
                
                <div class="eem-form-group">
                    <label class="eem-checkbox-label">
                        <input type="checkbox" id="send-welcome-email" name="send_welcome_email" checked>
                        Send welcome email to new subscribers
                    </label>
                </div>
            </form>
        </div>
        <div class="eem-modal-footer">
            <button type="button" class="eem-btn eem-btn-secondary" onclick="EEMAdmin.closeModal('subscriber-modal')">Cancel</button>
            <button type="submit" form="subscriber-form" class="eem-btn eem-btn-primary">
                <span class="eem-btn-text">Save Subscriber</span>
                <span class="eem-btn-loading" style="display: none;">
                    <span class="eem-spinner-small"></span>
                    Saving...
                </span>
            </button>
        </div>
    </div>
</div>

<!-- Bulk Action Modals -->
<div id="bulk-list-modal" class="eem-modal" style="display: none;">
    <div class="eem-modal-content">
        <div class="eem-modal-header">
            <h3 id="bulk-list-modal-title">Add to List</h3>
            <button class="eem-modal-close">&times;</button>
        </div>
        <div class="eem-modal-body">
            <div class="eem-form-group">
                <label for="bulk-list-select">Select List:</label>
                <select id="bulk-list-select" class="eem-select">
                    <?php foreach ($lists as $list): ?>
                        <option value="<?php echo esc_attr($list->id); ?>">
                            <?php echo esc_html($list->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="eem-modal-footer">
            <button type="button" class="eem-btn eem-btn-secondary" onclick="EEMAdmin.closeModal('bulk-list-modal')">Cancel</button>
            <button type="button" class="eem-btn eem-btn-primary" id="apply-bulk-list-action">Apply</button>
        </div>
    </div>
</div>

<div id="bulk-segment-modal" class="eem-modal" style="display: none;">
    <div class="eem-modal-content">
        <div class="eem-modal-header">
            <h3 id="bulk-segment-modal-title">Add to Segment</h3>
            <button class="eem-modal-close">&times;</button>
        </div>
        <div class="eem-modal-body">
            <div class="eem-form-group">
                <label for="bulk-segment-select">Select Segment:</label>
                <select id="bulk-segment-select" class="eem-select">
                    <?php foreach ($segments as $segment): ?>
                        <option value="<?php echo esc_attr($segment->id); ?>">
                            <?php echo esc_html($segment->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="eem-modal-footer">
            <button type="button" class="eem-btn eem-btn-secondary" onclick="EEMAdmin.closeModal('bulk-segment-modal')">Cancel</button>
            <button type="button" class="eem-btn eem-btn-primary" id="apply-bulk-segment-action">Apply</button>
        </div>
    </div>
</div>

<!-- Import Modal -->
<div id="import-modal" class="eem-modal" style="display: none;">
    <div class="eem-modal-content eem-modal-large">
        <div class="eem-modal-header">
            <h3>Import Subscribers</h3>
            <button class="eem-modal-close">&times;</button>
        </div>
        <div class="eem-modal-body">
            <div class="eem-import-steps">
                <div class="eem-step active" data-step="1">
                    <h4>Step 1: Upload File</h4>
                    <div class="eem-upload-area" id="csv-upload-area">
                        <div class="eem-upload-icon">📄</div>
                        <p>Drag and drop your CSV file here, or click to browse</p>
                        <input type="file" id="csv-file-input" accept=".csv" style="display: none;">
                        <button class="eem-btn eem-btn-secondary" onclick="document.getElementById('csv-file-input').click()">
                            Choose File
                        </button>
                    </div>
                    <div class="eem-upload-info">
                        <p><strong>CSV Format Requirements:</strong></p>
                        <ul>
                            <li>First row should contain column headers</li>
                            <li>Required column: email</li>
                            <li>Optional columns: first_name, last_name, location, preferences</li>
                            <li>Maximum file size: 10MB</li>
                        </ul>
                    </div>
                </div>
                
                <div class="eem-step" data-step="2">
                    <h4>Step 2: Map Columns</h4>
                    <div id="column-mapping">
                        <!-- Column mapping will be generated here -->
                    </div>
                </div>
                
                <div class="eem-step" data-step="3">
                    <h4>Step 3: Import Settings</h4>
                    <div class="eem-form-group">
                        <label for="import-list">Add to List:</label>
                        <select id="import-list" class="eem-select">
                            <option value="">Select a list (optional)</option>
                            <?php foreach ($lists as $list): ?>
                                <option value="<?php echo esc_attr($list->id); ?>">
                                    <?php echo esc_html($list->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="eem-form-group">
                        <label class="eem-checkbox-label">
                            <input type="checkbox" id="send-welcome-emails" checked>
                            Send welcome emails to new subscribers
                        </label>
                    </div>
                    <div class="eem-form-group">
                        <label class="eem-checkbox-label">
                            <input type="checkbox" id="update-existing">
                            Update existing subscribers
                        </label>
                    </div>
                </div>
                
                <div class="eem-step" data-step="4">
                    <h4>Step 4: Import Progress</h4>
                    <div class="eem-import-progress">
                        <div class="eem-progress-bar">
                            <div class="eem-progress-fill" id="import-progress-fill"></div>
                        </div>
                        <div class="eem-progress-text" id="import-progress-text">Ready to import...</div>
                        <div class="eem-import-stats" id="import-stats" style="display: none;">
                            <div class="eem-stat-item">
                                <span class="eem-stat-label">Total:</span>
                                <span class="eem-stat-value" id="import-total">0</span>
                            </div>
                            <div class="eem-stat-item">
                                <span class="eem-stat-label">Imported:</span>
                                <span class="eem-stat-value" id="import-success">0</span>
                            </div>
                            <div class="eem-stat-item">
                                <span class="eem-stat-label">Skipped:</span>
                                <span class="eem-stat-value" id="import-skipped">0</span>
                            </div>
                            <div class="eem-stat-item">
                                <span class="eem-stat-label">Errors:</span>
                                <span class="eem-stat-value" id="import-errors">0</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="eem-modal-footer">
            <button type="button" class="eem-btn eem-btn-secondary" id="import-prev-step" style="display: none;">Previous</button>
            <button type="button" class="eem-btn eem-btn-secondary" onclick="EEMAdmin.closeModal('import-modal')">Cancel</button>
            <button type="button" class="eem-btn eem-btn-primary" id="import-next-step">Next</button>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Initialize subscribers management
    EEMAdmin.initializeSubscribersPage();
});
</script>
