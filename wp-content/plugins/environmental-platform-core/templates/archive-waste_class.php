<?php
/**
 * Archive Waste Classifications Template
 * 
 * @package Environmental_Platform_Core
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header(); ?>

<style>
/* ===== MAIN CONTAINER ===== */
.waste-archive {
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    min-height: 100vh;
    padding: 20px 0;
}

.waste-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 20px;
}

/* ===== ARCHIVE HEADER ===== */
.waste-archive-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 60px 40px;
    border-radius: 20px;
    margin-bottom: 40px;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.waste-archive-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="20" cy="20" r="2" fill="rgba(255,255,255,0.1)"/><circle cx="80" cy="80" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="40" cy="60" r="1.5" fill="rgba(255,255,255,0.1)"/></svg>');
    animation: float 20s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-20px); }
}

.waste-archive-title {
    font-size: 3.5rem;
    margin-bottom: 20px;
    font-weight: bold;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
    position: relative;
    z-index: 1;
}

.waste-archive-description {
    font-size: 1.2rem;
    line-height: 1.6;
    margin-bottom: 30px;
    opacity: 0.9;
    position: relative;
    z-index: 1;
}

.waste-stats {
    display: flex;
    justify-content: center;
    gap: 40px;
    flex-wrap: wrap;
    position: relative;
    z-index: 1;
}

.waste-stat-item {
    text-align: center;
    background: rgba(255,255,255,0.1);
    padding: 20px;
    border-radius: 15px;
    backdrop-filter: blur(10px);
    transition: transform 0.3s ease;
}

.waste-stat-item:hover {
    transform: translateY(-5px);
}

.waste-stat-number {
    display: block;
    font-size: 2.5rem;
    font-weight: bold;
    margin-bottom: 5px;
}

.waste-stat-label {
    font-size: 0.9rem;
    opacity: 0.8;
}

/* ===== FILTERS SECTION ===== */
.waste-filters {
    background: white;
    padding: 30px;
    border-radius: 15px;
    margin-bottom: 30px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}

.filters-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    flex-wrap: wrap;
    gap: 20px;
}

.filters-title {
    font-size: 1.3rem;
    font-weight: bold;
    color: #333;
    margin: 0;
}

.view-controls {
    display: flex;
    gap: 15px;
    align-items: center;
}

.view-toggle {
    display: flex;
    background: #f0f0f0;
    border-radius: 10px;
    padding: 3px;
}

.view-btn {
    padding: 10px 15px;
    border: none;
    background: transparent;
    cursor: pointer;
    border-radius: 7px;
    font-size: 0.9rem;
    font-weight: 500;
    color: #666;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}

.view-btn.active {
    background: #667eea;
    color: white;
    box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
}

.view-btn:hover {
    color: #333;
}

.view-btn.active:hover {
    color: white;
}

.filters-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 25px;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.filter-label {
    font-weight: 600;
    color: #333;
    font-size: 0.9rem;
}

.filter-select,
.filter-input {
    padding: 12px 15px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    background: white;
}

.filter-select:focus,
.filter-input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.search-container {
    grid-column: 1 / -1;
    display: flex;
    gap: 10px;
}

.search-input {
    flex: 1;
    padding: 15px 20px;
    border: 2px solid #e0e0e0;
    border-radius: 10px;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.search-input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.search-btn {
    padding: 15px 25px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s ease;
}

.search-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}

.filters-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.clear-filters {
    padding: 10px 20px;
    background: #f8f9fa;
    color: #666;
    border: 1px solid #ddd;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.3s ease;
}

.clear-filters:hover {
    background: #e9ecef;
    color: #333;
}

.results-info {
    color: #666;
    font-size: 0.9rem;
}

/* ===== MAIN CONTENT ===== */
.main-content {
    display: grid;
    grid-template-columns: 1fr 350px;
    gap: 40px;
}

/* ===== WASTE CLASSES GRID ===== */
.waste-classes-container {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}

.waste-classes-header {
    padding: 25px 30px;
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    border-bottom: 1px solid #e0e0e0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.waste-classes-header h3 {
    margin: 0;
    font-size: 1.3rem;
    color: #333;
}

.sort-container {
    display: flex;
    align-items: center;
    gap: 10px;
}

.sort-label {
    font-size: 0.9rem;
    color: #666;
    font-weight: 500;
}

.sort-select {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 0.9rem;
    background: white;
}

.waste-classes-grid {
    padding: 30px;
    display: grid;
    gap: 25px;
    transition: all 0.3s ease;
}

.waste-classes-grid.grid-view {
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
}

.waste-classes-grid.list-view {
    grid-template-columns: 1fr;
}

/* ===== WASTE CLASS CARD ===== */
.waste-class-card {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    border: 1px solid #f0f0f0;
    position: relative;
}

.waste-class-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
}

.waste-classes-grid.list-view .waste-class-card {
    display: grid;
    grid-template-columns: 200px 1fr auto;
    gap: 25px;
    align-items: center;
    padding: 20px;
}

.waste-classes-grid.list-view .waste-class-card:last-child {
    border-bottom: none;
}

.waste-image {
    position: relative;
    height: 200px;
    overflow: hidden;
}

.waste-classes-grid.list-view .waste-image {
    height: 120px;
    border-radius: 10px;
}

.waste-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.waste-class-card:hover .waste-image img {
    transform: scale(1.05);
}

.waste-type-badge {
    position: absolute;
    top: 15px;
    left: 15px;
    padding: 6px 12px;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: bold;
    color: white;
    backdrop-filter: blur(10px);
}

.type-organic { background: rgba(76, 175, 80, 0.9); }
.type-recyclable { background: rgba(33, 150, 243, 0.9); }
.type-electronic { background: rgba(255, 152, 0, 0.9); }
.type-hazardous { background: rgba(244, 67, 54, 0.9); }
.type-medical { background: rgba(156, 39, 176, 0.9); }
.type-general { background: rgba(96, 125, 139, 0.9); }

.recyclability-badge {
    position: absolute;
    top: 15px;
    right: 15px;
    padding: 6px 12px;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: bold;
    backdrop-filter: blur(10px);
}

.recyclable-yes { background: rgba(76, 175, 80, 0.9); color: white; }
.recyclable-partial { background: rgba(255, 193, 7, 0.9); color: white; }
.recyclable-no { background: rgba(244, 67, 54, 0.9); color: white; }

.waste-content {
    padding: 25px;
}

.waste-classes-grid.list-view .waste-content {
    padding: 0;
}

.waste-title {
    font-size: 1.2rem;
    font-weight: bold;
    margin-bottom: 12px;
    color: #333;
    line-height: 1.3;
}

.waste-title a {
    text-decoration: none;
    color: inherit;
    transition: color 0.3s ease;
}

.waste-title a:hover {
    color: #667eea;
}

.waste-description {
    color: #666;
    font-size: 0.9rem;
    line-height: 1.5;
    margin-bottom: 20px;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.waste-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 20px;
    font-size: 0.8rem;
    color: #666;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 5px;
}

.disposal-method {
    padding: 4px 10px;
    background: #f0f8ff;
    color: #2196F3;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 500;
}

.hazard-level {
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 500;
}

.hazard-low { background: #e8f5e8; color: #2e7d32; }
.hazard-medium { background: #fff3e0; color: #f57c00; }
.hazard-high { background: #ffebee; color: #d32f2f; }

.waste-footer {
    padding: 20px 25px;
    background: #f8f9fa;
    border-top: 1px solid #f0f0f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.waste-classes-grid.list-view .waste-footer {
    padding: 0;
    background: transparent;
    border: none;
    flex-direction: column;
    align-items: flex-end;
    gap: 10px;
}

.waste-actions {
    display: flex;
    gap: 10px;
}

.action-btn {
    padding: 8px 16px;
    border: none;
    border-radius: 6px;
    font-size: 0.8rem;
    cursor: pointer;
    transition: all 0.3s ease;
    font-weight: 500;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.btn-primary {
    background: #667eea;
    color: white;
}

.btn-primary:hover {
    background: #5a67d8;
    transform: translateY(-1px);
}

.btn-secondary {
    background: #f0f0f0;
    color: #333;
}

.btn-secondary:hover {
    background: #e0e0e0;
}

.waste-stats {
    display: flex;
    gap: 15px;
    font-size: 0.8rem;
    color: #666;
}

.stat {
    display: flex;
    align-items: center;
    gap: 4px;
}

/* ===== SIDEBAR ===== */
.sidebar {
    display: flex;
    flex-direction: column;
    gap: 25px;
}

.sidebar-widget {
    background: white;
    padding: 25px;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}

.widget-title {
    font-size: 1.2rem;
    font-weight: bold;
    margin-bottom: 20px;
    color: #333;
    display: flex;
    align-items: center;
    gap: 10px;
}

/* ===== WASTE CATEGORIES ===== */
.categories-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.categories-list li {
    margin-bottom: 12px;
}

.categories-list a {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 15px;
    background: #f8f9fa;
    text-decoration: none;
    color: #333;
    border-radius: 10px;
    transition: all 0.3s ease;
    font-weight: 500;
}

.categories-list a:hover {
    background: #667eea;
    color: white;
    transform: translateX(5px);
}

.category-count {
    background: white;
    color: #666;
    padding: 4px 10px;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: bold;
}

.categories-list a:hover .category-count {
    background: rgba(255,255,255,0.2);
    color: white;
}

/* ===== DISPOSAL GUIDE ===== */
.disposal-guide {
    background: linear-gradient(135deg, #e3f2fd, #f3e5f5);
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 20px;
}

.guide-steps {
    list-style: none;
    padding: 0;
    margin: 0;
}

.guide-steps li {
    padding: 12px 0;
    border-bottom: 1px solid rgba(255,255,255,0.3);
    font-size: 0.9rem;
    line-height: 1.4;
    display: flex;
    align-items: flex-start;
    gap: 10px;
}

.guide-steps li:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.step-number {
    background: #667eea;
    color: white;
    padding: 4px 8px;
    border-radius: 50%;
    font-size: 0.8rem;
    font-weight: bold;
    min-width: 24px;
    text-align: center;
    flex-shrink: 0;
}

/* ===== QUICK SCANNER ===== */
.quick-scanner {
    text-align: center;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border-radius: 15px;
    padding: 25px;
}

.scanner-icon {
    font-size: 3rem;
    margin-bottom: 15px;
    opacity: 0.9;
}

.scanner-btn {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 12px 24px;
    background: rgba(255,255,255,0.2);
    color: white;
    text-decoration: none;
    border-radius: 25px;
    font-weight: 600;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
    margin-top: 15px;
}

.scanner-btn:hover {
    background: rgba(255,255,255,0.3);
    transform: translateY(-2px);
    color: white;
}

/* ===== ENVIRONMENTAL IMPACT ===== */
.impact-stats {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.impact-item {
    text-align: center;
    padding: 15px;
    background: linear-gradient(135deg, #e8f5e8, #f1f8e9);
    border-radius: 10px;
}

.impact-number {
    font-size: 1.5rem;
    font-weight: bold;
    color: #2e7d32;
    margin-bottom: 5px;
}

.impact-label {
    font-size: 0.8rem;
    color: #666;
    line-height: 1.2;
}

/* ===== PAGINATION ===== */
.pagination {
    display: flex;
    justify-content: center;
    margin-top: 40px;
    gap: 10px;
}

.pagination a,
.pagination span {
    padding: 12px 18px;
    background: white;
    border: 1px solid #ddd;
    text-decoration: none;
    color: #333;
    border-radius: 8px;
    transition: all 0.3s ease;
    font-weight: 500;
}

.pagination a:hover {
    background: #667eea;
    color: white;
    border-color: #667eea;
    transform: translateY(-2px);
}

.pagination .current {
    background: #667eea;
    color: white;
    border-color: #667eea;
}

/* ===== NO RESULTS ===== */
.no-results {
    text-align: center;
    padding: 60px 20px;
    color: #666;
}

.no-results-icon {
    font-size: 4rem;
    margin-bottom: 20px;
    opacity: 0.5;
}

.no-results h3 {
    font-size: 1.5rem;
    margin-bottom: 15px;
    color: #333;
}

.no-results p {
    font-size: 1rem;
    line-height: 1.6;
    margin-bottom: 25px;
}

/* ===== LOADING STATES ===== */
.loading {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 40px;
}

.spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #f3f3f3;
    border-top: 4px solid #667eea;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* ===== RESPONSIVE DESIGN ===== */
@media (max-width: 1200px) {
    .main-content {
        grid-template-columns: 1fr 300px;
    }
}

@media (max-width: 968px) {
    .main-content {
        grid-template-columns: 1fr;
    }
    
    .waste-archive-title {
        font-size: 2.5rem;
    }
    
    .waste-stats {
        gap: 20px;
    }
    
    .filters-grid {
        grid-template-columns: 1fr;
    }
    
    .waste-classes-grid.grid-view {
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    }
    
    .waste-classes-grid.list-view .waste-class-card {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .waste-classes-grid.list-view .waste-image {
        height: 200px;
    }
    
    .impact-stats {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 640px) {
    .waste-archive {
        padding: 15px 0;
    }
    
    .waste-container {
        padding: 0 15px;
    }
    
    .waste-archive-header {
        padding: 40px 20px;
    }
    
    .waste-archive-title {
        font-size: 2rem;
    }
    
    .waste-stats {
        flex-direction: column;
        gap: 15px;
    }
    
    .waste-filters {
        padding: 20px;
    }
    
    .waste-classes-grid.grid-view {
        grid-template-columns: 1fr;
        padding: 15px;
    }
    
    .sidebar-widget {
        padding: 20px;
    }
    
    .filters-actions {
        flex-direction: column;
        align-items: stretch;
    }
    
    .view-controls {
        flex-direction: column;
        align-items: stretch;
    }
}
</style>

<div class="waste-archive">
    <div class="waste-container">
        <!-- Archive Header -->
        <div class="waste-archive-header">
            <h1 class="waste-archive-title">🗂️ <?php _e('Waste Classification Guide', 'environmental-platform-core'); ?></h1>
            <p class="waste-archive-description">
                <?php _e('Learn how to properly identify, sort, and dispose of different types of waste. Make a positive environmental impact through proper waste management.', 'environmental-platform-core'); ?>
            </p>
            
            <div class="waste-stats">
                <?php
                $total_classes = wp_count_posts('waste_class')->publish;
                $recyclable_count = get_posts(array(
                    'post_type' => 'waste_class',
                    'numberposts' => -1,
                    'tax_query' => array(
                        array(
                            'taxonomy' => 'recyclability',
                            'field' => 'slug',
                            'terms' => 'recyclable'
                        )
                    ),
                    'fields' => 'ids'
                ));
                $hazardous_count = get_posts(array(
                    'post_type' => 'waste_class',
                    'numberposts' => -1,
                    'tax_query' => array(
                        array(
                            'taxonomy' => 'waste_type',
                            'field' => 'slug',
                            'terms' => 'hazardous'
                        )
                    ),
                    'fields' => 'ids'
                ));
                ?>
                
                <div class="waste-stat-item">
                    <span class="waste-stat-number"><?php echo $total_classes; ?></span>
                    <span class="waste-stat-label"><?php _e('Waste Types', 'environmental-platform-core'); ?></span>
                </div>
                <div class="waste-stat-item">
                    <span class="waste-stat-number"><?php echo count($recyclable_count); ?></span>
                    <span class="waste-stat-label"><?php _e('Recyclable', 'environmental-platform-core'); ?></span>
                </div>
                <div class="waste-stat-item">
                    <span class="waste-stat-number"><?php echo count($hazardous_count); ?></span>
                    <span class="waste-stat-label"><?php _e('Hazardous', 'environmental-platform-core'); ?></span>
                </div>
                <div class="waste-stat-item">
                    <span class="waste-stat-number">94%</span>
                    <span class="waste-stat-label"><?php _e('Accuracy Rate', 'environmental-platform-core'); ?></span>
                </div>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="waste-filters">
            <div class="filters-header">
                <h3 class="filters-title">🔍 <?php _e('Find Waste Classification', 'environmental-platform-core'); ?></h3>
                <div class="view-controls">
                    <div class="view-toggle">
                        <button type="button" class="view-btn active" data-view="grid">
                            <span class="dashicons dashicons-grid-view"></span>
                            <?php _e('Grid', 'environmental-platform-core'); ?>
                        </button>
                        <button type="button" class="view-btn" data-view="list">
                            <span class="dashicons dashicons-list-view"></span>
                            <?php _e('List', 'environmental-platform-core'); ?>
                        </button>
                    </div>
                </div>
            </div>
            
            <form class="filters-form" method="GET">
                <div class="filters-grid">
                    <!-- Waste Type Filter -->
                    <div class="filter-group">
                        <label for="waste-type-filter" class="filter-label"><?php _e('Waste Type', 'environmental-platform-core'); ?></label>
                        <select id="waste-type-filter" name="waste_type" class="filter-select">
                            <option value=""><?php _e('All Types', 'environmental-platform-core'); ?></option>
                            <?php
                            $waste_types = get_terms(array(
                                'taxonomy' => 'waste_type',
                                'hide_empty' => true
                            ));
                            foreach ($waste_types as $type) :
                            ?>
                                <option value="<?php echo esc_attr($type->slug); ?>" <?php selected(get_query_var('waste_type'), $type->slug); ?>>
                                    <?php echo esc_html($type->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Recyclability Filter -->
                    <div class="filter-group">
                        <label for="recyclability-filter" class="filter-label"><?php _e('Recyclability', 'environmental-platform-core'); ?></label>
                        <select id="recyclability-filter" name="recyclability" class="filter-select">
                            <option value=""><?php _e('All', 'environmental-platform-core'); ?></option>
                            <?php
                            $recyclability = get_terms(array(
                                'taxonomy' => 'recyclability',
                                'hide_empty' => true
                            ));
                            foreach ($recyclability as $recycle) :
                            ?>
                                <option value="<?php echo esc_attr($recycle->slug); ?>" <?php selected(get_query_var('recyclability'), $recycle->slug); ?>>
                                    <?php echo esc_html($recycle->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Disposal Method Filter -->
                    <div class="filter-group">
                        <label for="disposal-method-filter" class="filter-label"><?php _e('Disposal Method', 'environmental-platform-core'); ?></label>
                        <select id="disposal-method-filter" name="disposal_method" class="filter-select">
                            <option value=""><?php _e('All Methods', 'environmental-platform-core'); ?></option>
                            <?php
                            $disposal_methods = get_terms(array(
                                'taxonomy' => 'disposal_method',
                                'hide_empty' => true
                            ));
                            foreach ($disposal_methods as $method) :
                            ?>
                                <option value="<?php echo esc_attr($method->slug); ?>" <?php selected(get_query_var('disposal_method'), $method->slug); ?>>
                                    <?php echo esc_html($method->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Hazard Level Filter -->
                    <div class="filter-group">
                        <label for="hazard-level-filter" class="filter-label"><?php _e('Hazard Level', 'environmental-platform-core'); ?></label>
                        <select id="hazard-level-filter" name="hazard_level" class="filter-select">
                            <option value=""><?php _e('All Levels', 'environmental-platform-core'); ?></option>
                            <option value="low" <?php selected(get_query_var('hazard_level'), 'low'); ?>><?php _e('Low', 'environmental-platform-core'); ?></option>
                            <option value="medium" <?php selected(get_query_var('hazard_level'), 'medium'); ?>><?php _e('Medium', 'environmental-platform-core'); ?></option>
                            <option value="high" <?php selected(get_query_var('hazard_level'), 'high'); ?>><?php _e('High', 'environmental-platform-core'); ?></option>
                        </select>
                    </div>
                    
                    <!-- Search -->
                    <div class="search-container">
                        <input type="text" id="search-input" name="s" class="search-input" 
                               placeholder="<?php _e('Search waste classifications...', 'environmental-platform-core'); ?>"
                               value="<?php echo get_search_query(); ?>">
                        <button type="submit" class="search-btn">
                            <span class="dashicons dashicons-search"></span>
                            <?php _e('Search', 'environmental-platform-core'); ?>
                        </button>
                    </div>
                </div>
                
                <div class="filters-actions">
                    <button type="button" class="clear-filters" onclick="clearFilters()">
                        <span class="dashicons dashicons-dismiss"></span>
                        <?php _e('Clear Filters', 'environmental-platform-core'); ?>
                    </button>
                    <div class="results-info">
                        <?php
                        global $wp_query;
                        $found_posts = $wp_query->found_posts;
                        printf(_n('Showing %d waste classification', 'Showing %d waste classifications', $found_posts, 'environmental-platform-core'), $found_posts);
                        ?>
                    </div>
                </div>
            </form>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Waste Classes List -->
            <div class="waste-classes-container">
                <div class="waste-classes-header">
                    <h3><?php _e('Waste Classifications', 'environmental-platform-core'); ?></h3>
                    <div class="sort-container">
                        <label for="sort-classes" class="sort-label"><?php _e('Sort by:', 'environmental-platform-core'); ?></label>
                        <select id="sort-classes" class="sort-select" onchange="sortClasses(this.value)">
                            <option value="title-asc"><?php _e('Name (A-Z)', 'environmental-platform-core'); ?></option>
                            <option value="title-desc"><?php _e('Name (Z-A)', 'environmental-platform-core'); ?></option>
                            <option value="date-desc"><?php _e('Recently Added', 'environmental-platform-core'); ?></option>
                            <option value="popularity"><?php _e('Most Viewed', 'environmental-platform-core'); ?></option>
                        </select>
                    </div>
                </div>
                
                <div class="waste-classes-grid grid-view" id="wasteClassesGrid">
                    <?php if (have_posts()) : ?>
                        <?php while (have_posts()) : the_post(); ?>
                            <article class="waste-class-card" data-waste-id="<?php the_ID(); ?>">
                                <!-- Waste Image -->
                                <div class="waste-image">
                                    <?php if (has_post_thumbnail()) : ?>
                                        <a href="<?php the_permalink(); ?>">
                                            <?php the_post_thumbnail('medium', array('class' => 'img-responsive')); ?>
                                        </a>
                                    <?php else : ?>
                                        <div class="default-image">
                                            <span class="dashicons dashicons-trash" style="font-size: 3rem; color: #ccc; line-height: 200px;"></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Waste Type Badge -->
                                    <?php 
                                    $waste_type = get_the_terms(get_the_ID(), 'waste_type');
                                    if ($waste_type && !is_wp_error($waste_type)) :
                                    ?>
                                        <span class="waste-type-badge type-<?php echo esc_attr(strtolower($waste_type[0]->slug)); ?>">
                                            <?php echo esc_html($waste_type[0]->name); ?>
                                        </span>
                                    <?php endif; ?>
                                    
                                    <!-- Recyclability Badge -->
                                    <?php 
                                    $recyclability = get_the_terms(get_the_ID(), 'recyclability');
                                    if ($recyclability && !is_wp_error($recyclability)) :
                                    ?>
                                        <span class="recyclability-badge recyclable-<?php echo esc_attr(strtolower($recyclability[0]->slug)); ?>">
                                            <?php echo esc_html($recyclability[0]->name); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Waste Content -->
                                <div class="waste-content">
                                    <header class="waste-header">
                                        <h3 class="waste-title">
                                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                        </h3>
                                        
                                        <div class="waste-meta">
                                            <?php
                                            $disposal_method = get_post_meta(get_the_ID(), '_ep_disposal_method', true);
                                            $hazard_level = get_post_meta(get_the_ID(), '_ep_hazard_level', true);
                                            $decomposition_time = get_post_meta(get_the_ID(), '_ep_decomposition_time', true);
                                            ?>
                                            
                                            <?php if ($disposal_method) : ?>
                                                <div class="meta-item">
                                                    <span class="disposal-method"><?php echo esc_html($disposal_method); ?></span>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if ($hazard_level) : ?>
                                                <div class="meta-item">
                                                    <span class="hazard-level hazard-<?php echo esc_attr(strtolower($hazard_level)); ?>">
                                                        <?php echo esc_html(ucfirst($hazard_level)); ?> <?php _e('Risk', 'environmental-platform-core'); ?>
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if ($decomposition_time) : ?>
                                                <div class="meta-item">
                                                    <span class="dashicons dashicons-clock"></span>
                                                    <?php echo esc_html($decomposition_time); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </header>
                                    
                                    <!-- Waste Description -->
                                    <div class="waste-description">
                                        <?php echo wp_trim_words(get_the_excerpt(), 20); ?>
                                    </div>
                                </div>
                                
                                <!-- Waste Footer -->
                                <div class="waste-footer">
                                    <div class="waste-actions">
                                        <a href="<?php the_permalink(); ?>" class="action-btn btn-primary">
                                            <span class="dashicons dashicons-visibility"></span>
                                            <?php _e('View Guide', 'environmental-platform-core'); ?>
                                        </a>
                                        <button type="button" class="action-btn btn-secondary" onclick="saveWasteClass(<?php the_ID(); ?>)">
                                            <span class="dashicons dashicons-heart"></span>
                                            <?php _e('Save', 'environmental-platform-core'); ?>
                                        </button>
                                    </div>
                                    
                                    <div class="waste-stats">
                                        <div class="stat">
                                            <span class="dashicons dashicons-visibility"></span>
                                            <?php echo get_post_meta(get_the_ID(), '_view_count', true) ?: '0'; ?>
                                        </div>
                                        <div class="stat">
                                            <span class="dashicons dashicons-share"></span>
                                            <?php echo get_post_meta(get_the_ID(), '_share_count', true) ?: '0'; ?>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        <?php endwhile; ?>
                    <?php else : ?>
                        <div class="no-results">
                            <div class="no-results-icon">🔍</div>
                            <h3><?php _e('No waste classifications found', 'environmental-platform-core'); ?></h3>
                            <p><?php _e('Try adjusting your filters or search terms. Our database is constantly growing with new waste classification guides.', 'environmental-platform-core'); ?></p>
                            <a href="<?php echo home_url('/contact'); ?>" class="action-btn btn-primary">
                                <?php _e('Suggest New Classification', 'environmental-platform-core'); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($wp_query->max_num_pages > 1) : ?>
                    <div class="pagination">
                        <?php
                        echo paginate_links(array(
                            'total' => $wp_query->max_num_pages,
                            'current' => max(1, get_query_var('paged')),
                            'mid_size' => 2,
                            'prev_text' => '<span class="dashicons dashicons-arrow-left-alt2"></span> ' . __('Previous', 'environmental-platform-core'),
                            'next_text' => __('Next', 'environmental-platform-core') . ' <span class="dashicons dashicons-arrow-right-alt2"></span>',
                        ));
                        ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <aside class="sidebar">
                <!-- Quick AI Scanner -->
                <div class="sidebar-widget quick-scanner">
                    <div class="scanner-icon">📷</div>
                    <h4 class="widget-title" style="color: white; margin-bottom: 10px;">
                        <?php _e('AI Waste Scanner', 'environmental-platform-core'); ?>
                    </h4>
                    <p style="margin-bottom: 15px; opacity: 0.9;">
                        <?php _e('Take a photo to instantly identify waste type and disposal method', 'environmental-platform-core'); ?>
                    </p>
                    <a href="#" class="scanner-btn" onclick="openWasteScanner()">
                        <span class="dashicons dashicons-camera"></span>
                        <?php _e('Scan Waste Item', 'environmental-platform-core'); ?>
                    </a>
                </div>
                
                <!-- Waste Categories -->
                <div class="sidebar-widget">
                    <h4 class="widget-title">
                        <span>📂</span>
                        <?php _e('Browse Categories', 'environmental-platform-core'); ?>
                    </h4>
                    <ul class="categories-list">
                        <?php
                        $categories = get_terms(array(
                            'taxonomy' => 'waste_type',
                            'hide_empty' => true,
                            'number' => 8
                        ));
                        
                        foreach ($categories as $category) :
                        ?>
                            <li>
                                <a href="<?php echo get_term_link($category); ?>">
                                    <?php echo esc_html($category->name); ?>
                                    <span class="category-count"><?php echo $category->count; ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <!-- Disposal Guide -->
                <div class="sidebar-widget">
                    <h4 class="widget-title">
                        <span>📋</span>
                        <?php _e('General Disposal Guide', 'environmental-platform-core'); ?>
                    </h4>
                    <div class="disposal-guide">
                        <ol class="guide-steps">
                            <li>
                                <span class="step-number">1</span>
                                <?php _e('Identify the waste type using our classification guide', 'environmental-platform-core'); ?>
                            </li>
                            <li>
                                <span class="step-number">2</span>
                                <?php _e('Clean and prepare the item according to guidelines', 'environmental-platform-core'); ?>
                            </li>
                            <li>
                                <span class="step-number">3</span>
                                <?php _e('Sort into appropriate disposal bins or collection points', 'environmental-platform-core'); ?>
                            </li>
                            <li>
                                <span class="step-number">4</span>
                                <?php _e('Follow local disposal regulations and schedules', 'environmental-platform-core'); ?>
                            </li>
                        </ol>
                    </div>
                </div>
                
                <!-- Environmental Impact -->
                <div class="sidebar-widget">
                    <h4 class="widget-title">
                        <span>🌱</span>
                        <?php _e('Environmental Impact', 'environmental-platform-core'); ?>
                    </h4>
                    <div class="impact-stats">
                        <div class="impact-item">
                            <div class="impact-number"><?php echo number_format(get_option('waste_items_classified', 15847)); ?></div>
                            <div class="impact-label"><?php _e('Items Classified', 'environmental-platform-core'); ?></div>
                        </div>
                        <div class="impact-item">
                            <div class="impact-number"><?php echo get_option('co2_saved_classification', 284); ?>kg</div>
                            <div class="impact-label"><?php _e('CO₂ Saved', 'environmental-platform-core'); ?></div>
                        </div>
                        <div class="impact-item">
                            <div class="impact-number"><?php echo get_option('recycling_rate', 78); ?>%</div>
                            <div class="impact-label"><?php _e('Recycling Rate', 'environmental-platform-core'); ?></div>
                        </div>
                        <div class="impact-item">
                            <div class="impact-number"><?php echo number_format(get_option('active_users_classification', 3247)); ?></div>
                            <div class="impact-label"><?php _e('Active Users', 'environmental-platform-core'); ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- Disposal Locations -->
                <div class="sidebar-widget">
                    <h4 class="widget-title">
                        <span>📍</span>
                        <?php _e('Find Disposal Locations', 'environmental-platform-core'); ?>
                    </h4>
                    <p><?php _e('Locate recycling centers and disposal facilities near you.', 'environmental-platform-core'); ?></p>
                    <a href="<?php echo get_permalink(get_option('disposal_locations_page')); ?>" class="action-btn btn-primary" style="width: 100%; text-align: center; margin-top: 15px;">
                        <span class="dashicons dashicons-location"></span>
                        <?php _e('Find Locations', 'environmental-platform-core'); ?>
                    </a>
                </div>
            </aside>
        </div>
    </div>
</div>

<script>
// View Toggle Functionality
document.addEventListener('DOMContentLoaded', function() {
    const viewBtns = document.querySelectorAll('.view-btn');
    const wasteClassesGrid = document.getElementById('wasteClassesGrid');
    
    viewBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            viewBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            const view = this.getAttribute('data-view');
            wasteClassesGrid.className = `waste-classes-grid ${view}-view`;
            
            // Save preference
            localStorage.setItem('waste_view_preference', view);
        });
    });
    
    // Load saved view preference
    const savedView = localStorage.getItem('waste_view_preference');
    if (savedView) {
        const savedBtn = document.querySelector(`[data-view="${savedView}"]`);
        if (savedBtn) {
            savedBtn.click();
        }
    }
});

// Clear Filters Function
function clearFilters() {
    const form = document.querySelector('.filters-form');
    const inputs = form.querySelectorAll('input, select');
    
    inputs.forEach(input => {
        if (input.type === 'text' || input.type === 'search') {
            input.value = '';
        } else if (input.tagName === 'SELECT') {
            input.selectedIndex = 0;
        }
    });
    
    // Submit form to reload with cleared filters
    form.submit();
}

// Sort Waste Classes Function
function sortClasses(sortBy) {
    const url = new URL(window.location);
    url.searchParams.set('orderby', sortBy);
    window.location.href = url.toString();
}

// Save Waste Class Function
function saveWasteClass(wasteId) {
    // Check if user is logged in
    if (!document.body.classList.contains('logged-in')) {
        alert('<?php _e('Please log in to save waste classifications.', 'environmental-platform-core'); ?>');
        return;
    }
    
    // AJAX call to save waste class
    const data = new FormData();
    data.append('action', 'save_waste_class');
    data.append('waste_id', wasteId);
    data.append('nonce', wasteData.nonce);
    
    fetch(wasteData.ajaxUrl, {
        method: 'POST',
        body: data
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            const btn = event.target.closest('.action-btn');
            btn.innerHTML = '<span class="dashicons dashicons-yes"></span> ' + 
                           '<?php _e('Saved!', 'environmental-platform-core'); ?>';
            btn.classList.add('saved');
            setTimeout(() => {
                btn.innerHTML = '<span class="dashicons dashicons-heart"></span> ' + 
                               '<?php _e('Save', 'environmental-platform-core'); ?>';
                btn.classList.remove('saved');
            }, 2000);
        } else {
            alert(result.data || '<?php _e('Error saving classification.', 'environmental-platform-core'); ?>');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('<?php _e('Error saving classification.', 'environmental-platform-core'); ?>');
    });
}

// Open Waste Scanner Function
function openWasteScanner() {
    // Check if user is logged in
    if (!document.body.classList.contains('logged-in')) {
        alert('<?php _e('Please log in to use the waste scanner.', 'environmental-platform-core'); ?>');
        return;
    }
    
    // Open camera modal or redirect to scanner page
    window.open('/waste-scanner', '_blank', 'width=800,height=600');
}

// Auto-refresh stats every 60 seconds
setInterval(function() {
    fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=get_waste_stats')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update stats if needed
                console.log('Stats updated:', data.data);
            }
        })
        .catch(error => console.error('Stats update error:', error));
}, 60000);

// Lazy Loading for Images
if ('IntersectionObserver' in window) {
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.classList.remove('lazy');
                imageObserver.unobserve(img);
            }
        });
    });

    document.querySelectorAll('img[data-src]').forEach(img => {
        imageObserver.observe(img);
    });
}

// Filter Form Enhancement
document.querySelector('.filters-form').addEventListener('change', function(e) {
    if (e.target.matches('select')) {
        // Auto-submit form when filter changes
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.set(e.target.name, e.target.value);
        urlParams.delete('paged'); // Reset pagination
        
        const newUrl = window.location.pathname + '?' + urlParams.toString();
        window.history.pushState({}, '', newUrl);
        
        // You could implement AJAX loading here instead of page refresh
        window.location.reload();
    }
});

// Keyboard Shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + F to focus search
    if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
        e.preventDefault();
        document.getElementById('search-input').focus();
    }
    
    // Escape to clear search
    if (e.key === 'Escape') {
        const searchInput = document.getElementById('search-input');
        if (searchInput === document.activeElement) {
            searchInput.value = '';
        }
    }
});
</script>

<?php get_footer(); ?>
