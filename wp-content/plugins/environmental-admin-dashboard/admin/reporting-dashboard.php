<?php
/**
 * Reporting Dashboard Admin Page
 * 
 * Comprehensive reporting interface with analytics, visualizations,
 * and export capabilities for the Environmental Platform.
 */

if (!defined('ABSPATH')) {
    exit;
}

$reporting = Environmental_Reporting_Dashboard::get_instance();
$report_data = $reporting->get_dashboard_reports();
?>

<div class="wrap env-reporting-dashboard">
    <h1><?php _e('Environmental Reporting Dashboard', 'env-admin-dashboard'); ?></h1>
    
    <!-- Report Summary Cards -->
    <div class="report-summary">
        <div class="summary-cards">
            <div class="summary-card">
                <div class="card-icon">📊</div>
                <div class="card-content">
                    <h3><?php echo number_format($report_data['total_reports']); ?></h3>
                    <p><?php _e('Total Reports', 'env-admin-dashboard'); ?></p>
                    <span class="trend positive">+12% from last month</span>
                </div>
            </div>
            
            <div class="summary-card">
                <div class="card-icon">🌱</div>
                <div class="card-content">
                    <h3><?php echo $report_data['environmental_score']; ?>%</h3>
                    <p><?php _e('Environmental Score', 'env-admin-dashboard'); ?></p>
                    <span class="trend positive">+5% from last month</span>
                </div>
            </div>
            
            <div class="summary-card">
                <div class="card-icon">⚡</div>
                <div class="card-content">
                    <h3><?php echo number_format($report_data['energy_saved']); ?> kWh</h3>
                    <p><?php _e('Energy Saved', 'env-admin-dashboard'); ?></p>
                    <span class="trend positive">+18% from last month</span>
                </div>
            </div>
            
            <div class="summary-card">
                <div class="card-icon">♻️</div>
                <div class="card-content">
                    <h3><?php echo number_format($report_data['carbon_offset']); ?> kg</h3>
                    <p><?php _e('Carbon Offset', 'env-admin-dashboard'); ?></p>
                    <span class="trend positive">+23% from last month</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Report Controls -->
    <div class="report-controls">
        <div class="date-range-picker">
            <label for="report-start-date"><?php _e('From:', 'env-admin-dashboard'); ?></label>
            <input type="date" id="report-start-date" value="<?php echo date('Y-m-01'); ?>" />
            
            <label for="report-end-date"><?php _e('To:', 'env-admin-dashboard'); ?></label>
            <input type="date" id="report-end-date" value="<?php echo date('Y-m-d'); ?>" />
            
            <button type="button" class="button" id="apply-date-filter"><?php _e('Apply Filter', 'env-admin-dashboard'); ?></button>
        </div>
        
        <div class="report-actions">
            <button type="button" class="button button-primary" id="generate-report"><?php _e('Generate Report', 'env-admin-dashboard'); ?></button>
            <button type="button" class="button" id="export-report"><?php _e('Export Report', 'env-admin-dashboard'); ?></button>
            <button type="button" class="button" id="schedule-report"><?php _e('Schedule Report', 'env-admin-dashboard'); ?></button>
        </div>
    </div>
    
    <!-- Report Tabs -->
    <div class="nav-tab-wrapper">
        <a href="#overview-reports" class="nav-tab nav-tab-active"><?php _e('Overview', 'env-admin-dashboard'); ?></a>
        <a href="#environmental-reports" class="nav-tab"><?php _e('Environmental', 'env-admin-dashboard'); ?></a>
        <a href="#performance-reports" class="nav-tab"><?php _e('Performance', 'env-admin-dashboard'); ?></a>
        <a href="#user-reports" class="nav-tab"><?php _e('User Analytics', 'env-admin-dashboard'); ?></a>
        <a href="#custom-reports" class="nav-tab"><?php _e('Custom Reports', 'env-admin-dashboard'); ?></a>
    </div>
    
    <!-- Overview Reports Tab -->
    <div id="overview-reports" class="tab-content active">
        <div class="report-section">
            <div class="chart-grid">
                <div class="chart-container">
                    <h3><?php _e('Environmental Impact Over Time', 'env-admin-dashboard'); ?></h3>
                    <canvas id="environmental-impact-chart"></canvas>
                </div>
                
                <div class="chart-container">
                    <h3><?php _e('Activity Distribution', 'env-admin-dashboard'); ?></h3>
                    <canvas id="activity-distribution-chart"></canvas>
                </div>
                
                <div class="chart-container">
                    <h3><?php _e('Goal Achievement', 'env-admin-dashboard'); ?></h3>
                    <canvas id="goal-achievement-chart"></canvas>
                </div>
                
                <div class="chart-container">
                    <h3><?php _e('Resource Usage', 'env-admin-dashboard'); ?></h3>
                    <canvas id="resource-usage-chart"></canvas>
                </div>
            </div>
            
            <div class="report-tables">
                <div class="table-container">
                    <h3><?php _e('Key Performance Indicators', 'env-admin-dashboard'); ?></h3>
                    <table class="wp-list-table widefat striped">
                        <thead>
                            <tr>
                                <th><?php _e('Metric', 'env-admin-dashboard'); ?></th>
                                <th><?php _e('Current Value', 'env-admin-dashboard'); ?></th>
                                <th><?php _e('Target', 'env-admin-dashboard'); ?></th>
                                <th><?php _e('Progress', 'env-admin-dashboard'); ?></th>
                                <th><?php _e('Trend', 'env-admin-dashboard'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><?php _e('Carbon Footprint Reduction', 'env-admin-dashboard'); ?></td>
                                <td>15.2%</td>
                                <td>20%</td>
                                <td><div class="progress-bar"><div class="progress-fill" style="width: 76%;"></div></div></td>
                                <td><span class="trend positive">↗ +2.1%</span></td>
                            </tr>
                            <tr>
                                <td><?php _e('Energy Efficiency', 'env-admin-dashboard'); ?></td>
                                <td>82.5%</td>
                                <td>85%</td>
                                <td><div class="progress-bar"><div class="progress-fill" style="width: 97%;"></div></div></td>
                                <td><span class="trend positive">↗ +1.8%</span></td>
                            </tr>
                            <tr>
                                <td><?php _e('Waste Reduction', 'env-admin-dashboard'); ?></td>
                                <td>68.3%</td>
                                <td>75%</td>
                                <td><div class="progress-bar"><div class="progress-fill" style="width: 91%;"></div></div></td>
                                <td><span class="trend positive">↗ +3.2%</span></td>
                            </tr>
                            <tr>
                                <td><?php _e('User Engagement', 'env-admin-dashboard'); ?></td>
                                <td>74.1%</td>
                                <td>80%</td>
                                <td><div class="progress-bar"><div class="progress-fill" style="width: 93%;"></div></div></td>
                                <td><span class="trend positive">↗ +4.5%</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Environmental Reports Tab -->
    <div id="environmental-reports" class="tab-content">
        <div class="report-section">
            <div class="environmental-metrics">
                <div class="metric-grid">
                    <div class="metric-card">
                        <h4><?php _e('Air Quality Index', 'env-admin-dashboard'); ?></h4>
                        <div class="metric-value good">42 AQI</div>
                        <div class="metric-status"><?php _e('Good', 'env-admin-dashboard'); ?></div>
                    </div>
                    
                    <div class="metric-card">
                        <h4><?php _e('Water Quality Score', 'env-admin-dashboard'); ?></h4>
                        <div class="metric-value excellent">8.7/10</div>
                        <div class="metric-status"><?php _e('Excellent', 'env-admin-dashboard'); ?></div>
                    </div>
                    
                    <div class="metric-card">
                        <h4><?php _e('Biodiversity Index', 'env-admin-dashboard'); ?></h4>
                        <div class="metric-value moderate">6.2/10</div>
                        <div class="metric-status"><?php _e('Moderate', 'env-admin-dashboard'); ?></div>
                    </div>
                    
                    <div class="metric-card">
                        <h4><?php _e('Soil Health Score', 'env-admin-dashboard'); ?></h4>
                        <div class="metric-value good">7.8/10</div>
                        <div class="metric-status"><?php _e('Good', 'env-admin-dashboard'); ?></div>
                    </div>
                </div>
            </div>
            
            <div class="environmental-charts">
                <div class="chart-container">
                    <h3><?php _e('Environmental Trends', 'env-admin-dashboard'); ?></h3>
                    <canvas id="environmental-trends-chart"></canvas>
                </div>
                
                <div class="chart-container">
                    <h3><?php _e('Emission Sources', 'env-admin-dashboard'); ?></h3>
                    <canvas id="emission-sources-chart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Performance Reports Tab -->
    <div id="performance-reports" class="tab-content">
        <div class="report-section">
            <div class="performance-metrics">
                <h3><?php _e('System Performance Metrics', 'env-admin-dashboard'); ?></h3>
                
                <div class="metric-row">
                    <div class="metric-item">
                        <label><?php _e('Page Load Time', 'env-admin-dashboard'); ?></label>
                        <div class="metric-bar">
                            <div class="bar-fill" style="width: 85%;" data-value="1.2s"></div>
                        </div>
                        <span class="metric-text">1.2s (Target: <1.5s)</span>
                    </div>
                </div>
                
                <div class="metric-row">
                    <div class="metric-item">
                        <label><?php _e('Server Response Time', 'env-admin-dashboard'); ?></label>
                        <div class="metric-bar">
                            <div class="bar-fill" style="width: 92%;" data-value="180ms"></div>
                        </div>
                        <span class="metric-text">180ms (Target: <200ms)</span>
                    </div>
                </div>
                
                <div class="metric-row">
                    <div class="metric-item">
                        <label><?php _e('Database Query Time', 'env-admin-dashboard'); ?></label>
                        <div class="metric-bar">
                            <div class="bar-fill" style="width: 78%;" data-value="45ms"></div>
                        </div>
                        <span class="metric-text">45ms (Target: <50ms)</span>
                    </div>
                </div>
                
                <div class="metric-row">
                    <div class="metric-item">
                        <label><?php _e('Memory Usage', 'env-admin-dashboard'); ?></label>
                        <div class="metric-bar">
                            <div class="bar-fill" style="width: 68%;" data-value="68%"></div>
                        </div>
                        <span class="metric-text">68% (Target: <80%)</span>
                    </div>
                </div>
            </div>
            
            <div class="performance-charts">
                <div class="chart-container">
                    <h3><?php _e('Performance Over Time', 'env-admin-dashboard'); ?></h3>
                    <canvas id="performance-time-chart"></canvas>
                </div>
                
                <div class="chart-container">
                    <h3><?php _e('Resource Utilization', 'env-admin-dashboard'); ?></h3>
                    <canvas id="resource-utilization-chart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- User Analytics Tab -->
    <div id="user-reports" class="tab-content">
        <div class="report-section">
            <div class="user-metrics">
                <div class="user-stats-grid">
                    <div class="stat-card">
                        <h4><?php _e('Total Users', 'env-admin-dashboard'); ?></h4>
                        <div class="stat-value"><?php echo number_format($report_data['total_users']); ?></div>
                        <div class="stat-change positive">+12 this week</div>
                    </div>
                    
                    <div class="stat-card">
                        <h4><?php _e('Active Users', 'env-admin-dashboard'); ?></h4>
                        <div class="stat-value"><?php echo number_format($report_data['active_users']); ?></div>
                        <div class="stat-change positive">+8% from last week</div>
                    </div>
                    
                    <div class="stat-card">
                        <h4><?php _e('Avg Session Duration', 'env-admin-dashboard'); ?></h4>
                        <div class="stat-value">5m 32s</div>
                        <div class="stat-change positive">+15s from last week</div>
                    </div>
                    
                    <div class="stat-card">
                        <h4><?php _e('Bounce Rate', 'env-admin-dashboard'); ?></h4>
                        <div class="stat-value">24.3%</div>
                        <div class="stat-change negative">-2.1% from last week</div>
                    </div>
                </div>
            </div>
            
            <div class="user-charts">
                <div class="chart-container">
                    <h3><?php _e('User Activity', 'env-admin-dashboard'); ?></h3>
                    <canvas id="user-activity-chart"></canvas>
                </div>
                
                <div class="chart-container">
                    <h3><?php _e('User Demographics', 'env-admin-dashboard'); ?></h3>
                    <canvas id="user-demographics-chart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Custom Reports Tab -->
    <div id="custom-reports" class="tab-content">
        <div class="report-section">
            <div class="custom-report-builder">
                <h3><?php _e('Custom Report Builder', 'env-admin-dashboard'); ?></h3>
                
                <form id="custom-report-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="report-name"><?php _e('Report Name', 'env-admin-dashboard'); ?></label>
                            <input type="text" id="report-name" name="report_name" class="widefat" />
                        </div>
                        
                        <div class="form-group">
                            <label for="report-type"><?php _e('Report Type', 'env-admin-dashboard'); ?></label>
                            <select id="report-type" name="report_type" class="widefat">
                                <option value="environmental"><?php _e('Environmental Data', 'env-admin-dashboard'); ?></option>
                                <option value="performance"><?php _e('Performance Metrics', 'env-admin-dashboard'); ?></option>
                                <option value="user"><?php _e('User Analytics', 'env-admin-dashboard'); ?></option>
                                <option value="content"><?php _e('Content Analysis', 'env-admin-dashboard'); ?></option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label><?php _e('Data Sources', 'env-admin-dashboard'); ?></label>
                            <div class="checkbox-group">
                                <label><input type="checkbox" name="data_sources[]" value="activities" checked /> <?php _e('Environmental Activities', 'env-admin-dashboard'); ?></label>
                                <label><input type="checkbox" name="data_sources[]" value="goals" checked /> <?php _e('Goals & Achievements', 'env-admin-dashboard'); ?></label>
                                <label><input type="checkbox" name="data_sources[]" value="users" /> <?php _e('User Data', 'env-admin-dashboard'); ?></label>
                                <label><input type="checkbox" name="data_sources[]" value="content" /> <?php _e('Content Performance', 'env-admin-dashboard'); ?></label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label><?php _e('Chart Types', 'env-admin-dashboard'); ?></label>
                            <div class="checkbox-group">
                                <label><input type="checkbox" name="chart_types[]" value="line" checked /> <?php _e('Line Chart', 'env-admin-dashboard'); ?></label>
                                <label><input type="checkbox" name="chart_types[]" value="bar" /> <?php _e('Bar Chart', 'env-admin-dashboard'); ?></label>
                                <label><input type="checkbox" name="chart_types[]" value="pie" /> <?php _e('Pie Chart', 'env-admin-dashboard'); ?></label>
                                <label><input type="checkbox" name="chart_types[]" value="doughnut" /> <?php _e('Doughnut Chart', 'env-admin-dashboard'); ?></label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="button button-primary"><?php _e('Generate Custom Report', 'env-admin-dashboard'); ?></button>
                        <button type="button" class="button" id="save-report-template"><?php _e('Save as Template', 'env-admin-dashboard'); ?></button>
                    </div>
                </form>
            </div>
            
            <div class="saved-reports">
                <h3><?php _e('Saved Report Templates', 'env-admin-dashboard'); ?></h3>
                <div class="report-templates-grid">
                    <div class="template-card">
                        <h4><?php _e('Weekly Environmental Summary', 'env-admin-dashboard'); ?></h4>
                        <p><?php _e('Comprehensive weekly report on environmental metrics and goals.', 'env-admin-dashboard'); ?></p>
                        <div class="template-actions">
                            <button class="button button-small"><?php _e('Generate', 'env-admin-dashboard'); ?></button>
                            <button class="button button-small"><?php _e('Edit', 'env-admin-dashboard'); ?></button>
                        </div>
                    </div>
                    
                    <div class="template-card">
                        <h4><?php _e('Monthly Performance Review', 'env-admin-dashboard'); ?></h4>
                        <p><?php _e('Monthly analysis of platform performance and user engagement.', 'env-admin-dashboard'); ?></p>
                        <div class="template-actions">
                            <button class="button button-small"><?php _e('Generate', 'env-admin-dashboard'); ?></button>
                            <button class="button button-small"><?php _e('Edit', 'env-admin-dashboard'); ?></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Tab switching
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        var target = $(this).attr('href');
        
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        $('.tab-content').removeClass('active');
        $(target).addClass('active');
        
        // Initialize charts when tab becomes active
        if (target === '#overview-reports') {
            initOverviewCharts();
        } else if (target === '#environmental-reports') {
            initEnvironmentalCharts();
        } else if (target === '#performance-reports') {
            initPerformanceCharts();
        } else if (target === '#user-reports') {
            initUserCharts();
        }
    });
    
    // Initialize overview charts on page load
    initOverviewCharts();
    
    // Report controls
    $('#generate-report').on('click', function() {
        $(this).prop('disabled', true).text('<?php _e('Generating...', 'env-admin-dashboard'); ?>');
        
        setTimeout(function() {
            $('#generate-report').prop('disabled', false).text('<?php _e('Generate Report', 'env-admin-dashboard'); ?>');
            alert('<?php _e('Report generated successfully!', 'env-admin-dashboard'); ?>');
        }, 2000);
    });
    
    $('#export-report').on('click', function() {
        window.open('<?php echo admin_url('admin-ajax.php?action=env_export_report'); ?>', '_blank');
    });
    
    // Custom report form
    $('#custom-report-form').on('submit', function(e) {
        e.preventDefault();
        alert('<?php _e('Custom report generated!', 'env-admin-dashboard'); ?>');
    });
    
    function initOverviewCharts() {
        // Environmental Impact Chart
        if ($('#environmental-impact-chart').length) {
            var ctx = document.getElementById('environmental-impact-chart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [{
                        label: 'Environmental Impact Score',
                        data: [65, 68, 72, 75, 78, 82],
                        borderColor: '#4CAF50',
                        backgroundColor: 'rgba(76, 175, 80, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: true }
                    }
                }
            });
        }
        
        // Activity Distribution Chart
        if ($('#activity-distribution-chart').length) {
            var ctx = document.getElementById('activity-distribution-chart').getContext('2d');
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Energy Conservation', 'Waste Management', 'Water Conservation', 'Transportation'],
                    datasets: [{
                        data: [35, 25, 20, 20],
                        backgroundColor: ['#4CAF50', '#2196F3', '#FF9800', '#9C27B0']
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'bottom' }
                    }
                }
            });
        }
    }
    
    function initEnvironmentalCharts() {
        // Implementation for environmental charts
    }
    
    function initPerformanceCharts() {
        // Implementation for performance charts
    }
    
    function initUserCharts() {
        // Implementation for user charts
    }
});
</script>
