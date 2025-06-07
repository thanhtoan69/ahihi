<?php
/**
 * Analytics View
 * 
 * @package Environmental_Email_Marketing
 */

if (!defined('ABSPATH')) {
    exit;
}

$analytics_tracker = EEM_Analytics_Tracker::get_instance();
?>

<div class="eem-admin-page">
    <div class="eem-page-header">
        <div class="eem-page-title">
            <h1>
                <span class="eem-icon">📊</span>
                Email Marketing Analytics
            </h1>
            <p class="eem-page-description">
                Track performance, engagement, and environmental impact of your email campaigns
            </p>
        </div>
        <div class="eem-page-actions">
            <div class="eem-date-range-selector">
                <select id="analytics-date-range" class="eem-select">
                    <option value="7">Last 7 days</option>
                    <option value="30" selected>Last 30 days</option>
                    <option value="90">Last 90 days</option>
                    <option value="365">Last year</option>
                    <option value="custom">Custom range</option>
                </select>
            </div>
            <button class="eem-btn eem-btn-secondary" id="export-analytics">
                <span class="dashicons dashicons-download"></span>
                Export Report
            </button>
            <button class="eem-btn eem-btn-secondary" id="refresh-analytics">
                <span class="dashicons dashicons-update"></span>
                Refresh Data
            </button>
        </div>
    </div>

    <!-- Key Metrics Cards -->
    <div class="eem-analytics-overview">
        <div class="eem-metrics-grid">
            <div class="eem-metric-card">
                <div class="eem-metric-icon">
                    <span class="dashicons dashicons-email-alt"></span>
                </div>
                <div class="eem-metric-content">
                    <div class="eem-metric-number" id="emails-sent">-</div>
                    <div class="eem-metric-label">Emails Sent</div>
                    <div class="eem-metric-change" id="emails-sent-change">-</div>
                </div>
            </div>
            
            <div class="eem-metric-card">
                <div class="eem-metric-icon">
                    <span class="dashicons dashicons-visibility"></span>
                </div>
                <div class="eem-metric-content">
                    <div class="eem-metric-number" id="open-rate">-</div>
                    <div class="eem-metric-label">Open Rate</div>
                    <div class="eem-metric-change" id="open-rate-change">-</div>
                </div>
            </div>
            
            <div class="eem-metric-card">
                <div class="eem-metric-icon">
                    <span class="dashicons dashicons-admin-links"></span>
                </div>
                <div class="eem-metric-content">
                    <div class="eem-metric-number" id="click-rate">-</div>
                    <div class="eem-metric-label">Click Rate</div>
                    <div class="eem-metric-change" id="click-rate-change">-</div>
                </div>
            </div>
            
            <div class="eem-metric-card">
                <div class="eem-metric-icon">
                    <span class="dashicons dashicons-businessman"></span>
                </div>
                <div class="eem-metric-content">
                    <div class="eem-metric-number" id="conversion-rate">-</div>
                    <div class="eem-metric-label">Conversion Rate</div>
                    <div class="eem-metric-change" id="conversion-rate-change">-</div>
                </div>
            </div>
        </div>
        
        <!-- Environmental Impact Metrics -->
        <div class="eem-environmental-metrics">
            <h3 class="eem-section-title">
                <span class="eem-icon">🌱</span>
                Environmental Impact Analytics
            </h3>
            <div class="eem-metrics-grid">
                <div class="eem-metric-card eem-metric-eco">
                    <div class="eem-metric-icon">
                        <span>🌍</span>
                    </div>
                    <div class="eem-metric-content">
                        <div class="eem-metric-number" id="eco-actions-triggered">-</div>
                        <div class="eem-metric-label">Eco Actions Triggered</div>
                        <div class="eem-metric-change" id="eco-actions-change">-</div>
                    </div>
                </div>
                
                <div class="eem-metric-card eem-metric-eco">
                    <div class="eem-metric-icon">
                        <span>♻️</span>
                    </div>
                    <div class="eem-metric-content">
                        <div class="eem-metric-number" id="carbon-offset">-</div>
                        <div class="eem-metric-label">Carbon Offset (kg)</div>
                        <div class="eem-metric-change" id="carbon-offset-change">-</div>
                    </div>
                </div>
                
                <div class="eem-metric-card eem-metric-eco">
                    <div class="eem-metric-icon">
                        <span>🌱</span>
                    </div>
                    <div class="eem-metric-content">
                        <div class="eem-metric-number" id="avg-eco-score">-</div>
                        <div class="eem-metric-label">Avg Eco Score</div>
                        <div class="eem-metric-change" id="avg-eco-score-change">-</div>
                    </div>
                </div>
                
                <div class="eem-metric-card eem-metric-eco">
                    <div class="eem-metric-icon">
                        <span>📈</span>
                    </div>
                    <div class="eem-metric-content">
                        <div class="eem-metric-number" id="engagement-growth">-</div>
                        <div class="eem-metric-label">Engagement Growth</div>
                        <div class="eem-metric-change" id="engagement-growth-change">-</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="eem-charts-section">
        <div class="eem-chart-container">
            <div class="eem-chart-header">
                <h3>Email Performance Trends</h3>
                <div class="eem-chart-controls">
                    <button class="eem-chart-btn active" data-metric="opens">Opens</button>
                    <button class="eem-chart-btn" data-metric="clicks">Clicks</button>
                    <button class="eem-chart-btn" data-metric="conversions">Conversions</button>
                    <button class="eem-chart-btn" data-metric="unsubscribes">Unsubscribes</button>
                </div>
            </div>
            <div class="eem-chart-wrapper">
                <canvas id="performance-chart" width="800" height="400"></canvas>
            </div>
        </div>
        
        <div class="eem-chart-container">
            <div class="eem-chart-header">
                <h3>Environmental Impact Trends</h3>
                <div class="eem-chart-controls">
                    <button class="eem-chart-btn active" data-metric="eco_actions">Eco Actions</button>
                    <button class="eem-chart-btn" data-metric="carbon_offset">Carbon Offset</button>
                    <button class="eem-chart-btn" data-metric="eco_score">Eco Score</button>
                </div>
            </div>
            <div class="eem-chart-wrapper">
                <canvas id="environmental-chart" width="800" height="400"></canvas>
            </div>
        </div>
    </div>

    <!-- Detailed Analytics -->
    <div class="eem-analytics-details">
        <div class="eem-analytics-tabs">
            <button class="eem-tab-btn active" data-tab="campaigns">Campaign Performance</button>
            <button class="eem-tab-btn" data-tab="subscribers">Subscriber Analytics</button>
            <button class="eem-tab-btn" data-tab="segments">Segment Analysis</button>
            <button class="eem-tab-btn" data-tab="environmental">Environmental Impact</button>
            <button class="eem-tab-btn" data-tab="ab-tests">A/B Test Results</button>
        </div>
        
        <!-- Campaign Performance Tab -->
        <div class="eem-tab-content active" id="campaigns-tab">
            <div class="eem-tab-header">
                <h3>Campaign Performance Analysis</h3>
                <div class="eem-tab-filters">
                    <select id="campaign-filter" class="eem-select">
                        <option value="">All Campaigns</option>
                        <option value="newsletter">Newsletters</option>
                        <option value="promotional">Promotional</option>
                        <option value="automated">Automated</option>
                    </select>
                </div>
            </div>
            
            <div class="eem-table-container">
                <table class="eem-table" id="campaigns-analytics-table">
                    <thead>
                        <tr>
                            <th>Campaign</th>
                            <th>Sent</th>
                            <th>Opens</th>
                            <th>Clicks</th>
                            <th>Conversions</th>
                            <th>Unsubscribes</th>
                            <th>Eco Impact</th>
                            <th>ROI</th>
                        </tr>
                    </thead>
                    <tbody id="campaigns-analytics-body">
                        <!-- Campaign analytics data will be loaded here -->
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Subscriber Analytics Tab -->
        <div class="eem-tab-content" id="subscribers-tab">
            <div class="eem-tab-header">
                <h3>Subscriber Engagement Analysis</h3>
            </div>
            
            <div class="eem-subscriber-analytics">
                <div class="eem-analytics-chart-row">
                    <div class="eem-chart-half">
                        <h4>Subscriber Growth</h4>
                        <canvas id="subscriber-growth-chart" width="400" height="300"></canvas>
                    </div>
                    <div class="eem-chart-half">
                        <h4>Engagement Distribution</h4>
                        <canvas id="engagement-distribution-chart" width="400" height="300"></canvas>
                    </div>
                </div>
                
                <div class="eem-table-container">
                    <table class="eem-table" id="subscriber-engagement-table">
                        <thead>
                            <tr>
                                <th>Segment</th>
                                <th>Subscribers</th>
                                <th>Avg Open Rate</th>
                                <th>Avg Click Rate</th>
                                <th>Avg Eco Score</th>
                                <th>Engagement Trend</th>
                            </tr>
                        </thead>
                        <tbody id="subscriber-engagement-body">
                            <!-- Subscriber engagement data will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Segment Analysis Tab -->
        <div class="eem-tab-content" id="segments-tab">
            <div class="eem-tab-header">
                <h3>Segment Performance Analysis</h3>
            </div>
            
            <div class="eem-segment-analytics">
                <div class="eem-segment-comparison">
                    <canvas id="segment-comparison-chart" width="800" height="400"></canvas>
                </div>
                
                <div class="eem-table-container">
                    <table class="eem-table" id="segments-analytics-table">
                        <thead>
                            <tr>
                                <th>Segment</th>
                                <th>Size</th>
                                <th>Open Rate</th>
                                <th>Click Rate</th>
                                <th>Conversion Rate</th>
                                <th>Eco Engagement</th>
                                <th>Revenue</th>
                            </tr>
                        </thead>
                        <tbody id="segments-analytics-body">
                            <!-- Segment analytics data will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Environmental Impact Tab -->
        <div class="eem-tab-content" id="environmental-tab">
            <div class="eem-tab-header">
                <h3>Environmental Impact Dashboard</h3>
            </div>
            
            <div class="eem-environmental-dashboard">
                <div class="eem-impact-summary">
                    <div class="eem-impact-card">
                        <div class="eem-impact-icon">🌍</div>
                        <div class="eem-impact-content">
                            <h4>Total Environmental Actions</h4>
                            <div class="eem-impact-number" id="total-env-actions">-</div>
                            <div class="eem-impact-description">Actions taken by subscribers</div>
                        </div>
                    </div>
                    
                    <div class="eem-impact-card">
                        <div class="eem-impact-icon">♻️</div>
                        <div class="eem-impact-content">
                            <h4>Carbon Footprint Reduced</h4>
                            <div class="eem-impact-number" id="total-carbon-reduced">-</div>
                            <div class="eem-impact-description">kg CO₂ equivalent saved</div>
                        </div>
                    </div>
                    
                    <div class="eem-impact-card">
                        <div class="eem-impact-icon">🌱</div>
                        <div class="eem-impact-content">
                            <h4>Average Eco Score</h4>
                            <div class="eem-impact-number" id="community-eco-score">-</div>
                            <div class="eem-impact-description">Community engagement level</div>
                        </div>
                    </div>
                    
                    <div class="eem-impact-card">
                        <div class="eem-impact-icon">📈</div>
                        <div class="eem-impact-content">
                            <h4>Environmental ROI</h4>
                            <div class="eem-impact-number" id="environmental-roi">-</div>
                            <div class="eem-impact-description">Impact per email sent</div>
                        </div>
                    </div>
                </div>
                
                <div class="eem-impact-charts">
                    <div class="eem-chart-container">
                        <h4>Environmental Actions by Category</h4>
                        <canvas id="environmental-actions-chart" width="400" height="300"></canvas>
                    </div>
                    
                    <div class="eem-chart-container">
                        <h4>Eco Score Distribution</h4>
                        <canvas id="eco-score-distribution-chart" width="400" height="300"></canvas>
                    </div>
                </div>
                
                <div class="eem-table-container">
                    <table class="eem-table" id="environmental-actions-table">
                        <thead>
                            <tr>
                                <th>Action Type</th>
                                <th>Total Actions</th>
                                <th>Unique Subscribers</th>
                                <th>CO₂ Impact (kg)</th>
                                <th>Trend</th>
                            </tr>
                        </thead>
                        <tbody id="environmental-actions-body">
                            <!-- Environmental actions data will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- A/B Test Results Tab -->
        <div class="eem-tab-content" id="ab-tests-tab">
            <div class="eem-tab-header">
                <h3>A/B Test Results</h3>
            </div>
            
            <div class="eem-ab-test-analytics">
                <div class="eem-table-container">
                    <table class="eem-table" id="ab-tests-table">
                        <thead>
                            <tr>
                                <th>Test Name</th>
                                <th>Status</th>
                                <th>Variants</th>
                                <th>Winner</th>
                                <th>Confidence</th>
                                <th>Improvement</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="ab-tests-body">
                            <!-- A/B test data will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Export Modal -->
<div id="export-modal" class="eem-modal" style="display: none;">
    <div class="eem-modal-content">
        <div class="eem-modal-header">
            <h3>Export Analytics Report</h3>
            <button class="eem-modal-close">&times;</button>
        </div>
        <div class="eem-modal-body">
            <div class="eem-form-group">
                <label for="export-format">Format:</label>
                <select id="export-format" class="eem-select">
                    <option value="csv">CSV</option>
                    <option value="pdf">PDF Report</option>
                    <option value="excel">Excel</option>
                </select>
            </div>
            
            <div class="eem-form-group">
                <label for="export-data">Data to Include:</label>
                <div class="eem-checkbox-group">
                    <label class="eem-checkbox-label">
                        <input type="checkbox" name="export_data[]" value="overview" checked>
                        Overview Metrics
                    </label>
                    <label class="eem-checkbox-label">
                        <input type="checkbox" name="export_data[]" value="campaigns" checked>
                        Campaign Performance
                    </label>
                    <label class="eem-checkbox-label">
                        <input type="checkbox" name="export_data[]" value="subscribers" checked>
                        Subscriber Analytics
                    </label>
                    <label class="eem-checkbox-label">
                        <input type="checkbox" name="export_data[]" value="environmental" checked>
                        Environmental Impact
                    </label>
                    <label class="eem-checkbox-label">
                        <input type="checkbox" name="export_data[]" value="segments">
                        Segment Analysis
                    </label>
                    <label class="eem-checkbox-label">
                        <input type="checkbox" name="export_data[]" value="ab_tests">
                        A/B Test Results
                    </label>
                </div>
            </div>
            
            <div class="eem-form-group">
                <label for="export-date-range">Date Range:</label>
                <select id="export-date-range" class="eem-select">
                    <option value="7">Last 7 days</option>
                    <option value="30">Last 30 days</option>
                    <option value="90">Last 90 days</option>
                    <option value="365">Last year</option>
                    <option value="all">All time</option>
                </select>
            </div>
        </div>
        <div class="eem-modal-footer">
            <button type="button" class="eem-btn eem-btn-secondary" onclick="EEMAdmin.closeModal('export-modal')">Cancel</button>
            <button type="button" class="eem-btn eem-btn-primary" id="start-export">
                <span class="dashicons dashicons-download"></span>
                Export Report
            </button>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Initialize analytics page
    EEMAdmin.initializeAnalyticsPage();
});
</script>
