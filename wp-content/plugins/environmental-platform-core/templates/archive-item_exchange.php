<?php
/**
 * Archive Template for Item Exchanges
 * 
 * Displays a list of item exchange posts with filtering and search functionality
 */

get_header(); ?>

<style>
/* ===== ARCHIVE CONTAINER ===== */
.ep-exchange-archive {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
    background: #f8f9fa;
    min-height: 100vh;
}

/* ===== HEADER SECTION ===== */
.ep-archive-header {
    background: linear-gradient(135deg, #4CAF50, #8BC34A);
    color: white;
    padding: 60px 40px;
    text-align: center;
    margin-bottom: 40px;
    border-radius: 16px;
    box-shadow: 0 8px 30px rgba(76, 175, 80, 0.3);
}

.ep-archive-title {
    font-size: 3rem;
    margin-bottom: 15px;
    font-weight: bold;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
}

.ep-archive-description {
    font-size: 1.2rem;
    opacity: 0.9;
    max-width: 800px;
    margin: 0 auto 30px;
    line-height: 1.6;
}

.ep-archive-stats {
    display: flex;
    justify-content: center;
    gap: 40px;
    flex-wrap: wrap;
    margin-top: 30px;
}

.ep-stat-item {
    background: rgba(255,255,255,0.15);
    padding: 20px 30px;
    border-radius: 12px;
    text-align: center;
    backdrop-filter: blur(10px);
}

.ep-stat-number {
    font-size: 2rem;
    font-weight: bold;
    display: block;
    margin-bottom: 8px;
}

.ep-stat-label {
    font-size: 0.9rem;
    opacity: 0.9;
}

/* ===== FILTER SECTION ===== */
.ep-filters-container {
    background: white;
    padding: 30px;
    margin-bottom: 30px;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.ep-filters-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
}

.ep-filters-title {
    font-size: 1.3rem;
    font-weight: bold;
    color: #2E7D32;
    margin: 0;
}

.ep-view-toggle {
    display: flex;
    gap: 10px;
}

.ep-view-btn {
    padding: 8px 16px;
    background: #f0f0f0;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 0.9rem;
}

.ep-view-btn.active {
    background: #4CAF50;
    color: white;
}

.ep-view-btn:hover {
    background: #e0e0e0;
}

.ep-view-btn.active:hover {
    background: #45a049;
}

.ep-filters-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.ep-filter-group {
    display: flex;
    flex-direction: column;
}

.ep-filter-label {
    font-weight: 600;
    margin-bottom: 8px;
    color: #333;
    font-size: 0.9rem;
}

.ep-filter-select,
.ep-filter-input {
    padding: 12px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 0.9rem;
    transition: border-color 0.3s ease;
}

.ep-filter-select:focus,
.ep-filter-input:focus {
    outline: none;
    border-color: #4CAF50;
}

.ep-search-container {
    position: relative;
    grid-column: 1 / -1;
}

.ep-search-input {
    width: 100%;
    padding: 15px 50px 15px 20px;
    border: 2px solid #e0e0e0;
    border-radius: 25px;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.ep-search-input:focus {
    outline: none;
    border-color: #4CAF50;
    box-shadow: 0 0 10px rgba(76, 175, 80, 0.2);
}

.ep-search-btn {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    background: #4CAF50;
    color: white;
    border: none;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    cursor: pointer;
    transition: background 0.3s ease;
}

.ep-search-btn:hover {
    background: #45a049;
}

.ep-filters-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.ep-clear-filters {
    background: #ff6b6b;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 8px;
    cursor: pointer;
    transition: background 0.3s ease;
    font-size: 0.9rem;
}

.ep-clear-filters:hover {
    background: #ff5252;
}

.ep-results-info {
    color: #666;
    font-size: 0.9rem;
}

/* ===== MAIN CONTENT GRID ===== */
.ep-main-content {
    display: grid;
    grid-template-columns: 1fr 350px;
    gap: 30px;
}

/* ===== EXCHANGES GRID ===== */
.ep-exchanges-container {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.ep-exchanges-header {
    background: #f8f9fa;
    padding: 20px 30px;
    border-bottom: 1px solid #e0e0e0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.ep-sort-container {
    display: flex;
    align-items: center;
    gap: 15px;
}

.ep-sort-label {
    font-weight: 600;
    color: #333;
    font-size: 0.9rem;
}

.ep-sort-select {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 0.9rem;
}

.ep-exchanges-grid {
    display: grid;
    gap: 0;
}

.ep-exchanges-grid.grid-view {
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    padding: 20px;
    gap: 20px;
}

.ep-exchanges-grid.list-view {
    grid-template-columns: 1fr;
}

/* ===== EXCHANGE CARD ===== */
.ep-exchange-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.3s ease;
    position: relative;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.ep-exchange-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.ep-exchanges-grid.list-view .ep-exchange-card {
    display: grid;
    grid-template-columns: 200px 1fr auto;
    gap: 20px;
    padding: 20px;
    border-bottom: 1px solid #f0f0f0;
    border-radius: 0;
}

.ep-exchanges-grid.list-view .ep-exchange-card:last-child {
    border-bottom: none;
}

.ep-exchange-image {
    position: relative;
    height: 200px;
    overflow: hidden;
}

.ep-exchanges-grid.list-view .ep-exchange-image {
    height: 120px;
    border-radius: 8px;
}

.ep-exchange-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.ep-exchange-card:hover .ep-exchange-image img {
    transform: scale(1.05);
}

.ep-exchange-type-badge {
    position: absolute;
    top: 12px;
    left: 12px;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: bold;
    color: white;
    backdrop-filter: blur(10px);
}

.ep-type-offer {
    background: rgba(76, 175, 80, 0.9);
}

.ep-type-exchange {
    background: rgba(33, 150, 243, 0.9);
}

.ep-type-request {
    background: rgba(255, 152, 0, 0.9);
}

.ep-type-lending {
    background: rgba(156, 39, 176, 0.9);
}

.ep-urgent-badge {
    position: absolute;
    top: 12px;
    right: 12px;
    background: rgba(244, 67, 54, 0.9);
    color: white;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.7rem;
    font-weight: bold;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.7; }
    100% { opacity: 1; }
}

.ep-exchange-content {
    padding: 20px;
}

.ep-exchanges-grid.list-view .ep-exchange-content {
    padding: 0;
}

.ep-exchange-title {
    font-size: 1.1rem;
    font-weight: bold;
    margin-bottom: 10px;
    color: #333;
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.ep-exchange-title a {
    text-decoration: none;
    color: inherit;
    transition: color 0.3s ease;
}

.ep-exchange-title a:hover {
    color: #4CAF50;
}

.ep-exchange-description {
    color: #666;
    font-size: 0.9rem;
    line-height: 1.4;
    margin-bottom: 15px;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.ep-exchange-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 15px;
    font-size: 0.8rem;
    color: #666;
}

.ep-meta-item {
    display: flex;
    align-items: center;
    gap: 5px;
}

.ep-condition-badge {
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.7rem;
    font-weight: bold;
}

.ep-condition-new { background: #E8F5E8; color: #2E7D32; }
.ep-condition-like-new { background: #E8F5E8; color: #388E3C; }
.ep-condition-good { background: #FFF3E0; color: #F57C00; }
.ep-condition-fair { background: #FFF8E1; color: #FF8F00; }
.ep-condition-poor { background: #FFEBEE; color: #D32F2F; }

.ep-exchange-footer {
    padding: 15px 20px;
    background: #f8f9fa;
    border-top: 1px solid #f0f0f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.ep-exchanges-grid.list-view .ep-exchange-footer {
    padding: 0;
    background: transparent;
    border: none;
    flex-direction: column;
    align-items: flex-end;
    gap: 10px;
}

.ep-exchange-actions {
    display: flex;
    gap: 10px;
}

.ep-action-btn {
    padding: 8px 16px;
    border: none;
    border-radius: 6px;
    font-size: 0.8rem;
    cursor: pointer;
    transition: all 0.3s ease;
    font-weight: 500;
}

.ep-btn-primary {
    background: #4CAF50;
    color: white;
}

.ep-btn-primary:hover {
    background: #45a049;
}

.ep-btn-secondary {
    background: #f0f0f0;
    color: #333;
}

.ep-btn-secondary:hover {
    background: #e0e0e0;
}

.ep-exchange-stats {
    display: flex;
    gap: 15px;
    font-size: 0.8rem;
    color: #666;
}

.ep-stat {
    display: flex;
    align-items: center;
    gap: 4px;
}

/* ===== SIDEBAR ===== */
.ep-sidebar {
    display: flex;
    flex-direction: column;
    gap: 25px;
}

.ep-sidebar-widget {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.ep-widget-title {
    font-size: 1.2rem;
    font-weight: bold;
    margin-bottom: 20px;
    color: #2E7D32;
    display: flex;
    align-items: center;
    gap: 10px;
}

/* ===== FEATURED EXCHANGES ===== */
.ep-featured-exchanges .ep-featured-item {
    display: flex;
    gap: 15px;
    padding: 15px 0;
    border-bottom: 1px solid #f0f0f0;
}

.ep-featured-exchanges .ep-featured-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.ep-featured-thumbnail {
    width: 60px;
    height: 60px;
    border-radius: 8px;
    overflow: hidden;
    flex-shrink: 0;
}

.ep-featured-thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.ep-featured-content h4 {
    font-size: 0.9rem;
    margin: 0 0 5px 0;
    line-height: 1.3;
}

.ep-featured-content h4 a {
    text-decoration: none;
    color: #333;
}

.ep-featured-content h4 a:hover {
    color: #4CAF50;
}

.ep-featured-meta {
    font-size: 0.8rem;
    color: #666;
}

/* ===== EXCHANGE CATEGORIES ===== */
.ep-categories-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.ep-categories-list li {
    margin-bottom: 10px;
}

.ep-categories-list a {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px;
    background: #f8f9fa;
    text-decoration: none;
    color: #333;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.ep-categories-list a:hover {
    background: #4CAF50;
    color: white;
}

.ep-category-count {
    background: white;
    color: #666;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: bold;
}

.ep-categories-list a:hover .ep-category-count {
    background: rgba(255,255,255,0.2);
    color: white;
}

/* ===== EXCHANGE TIPS ===== */
.ep-tips-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.ep-tips-list li {
    padding: 10px 0;
    border-bottom: 1px solid #f0f0f0;
    font-size: 0.9rem;
    line-height: 1.4;
}

.ep-tips-list li:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.ep-tips-list li:before {
    content: "💡";
    margin-right: 8px;
}

/* ===== QUICK ACTIONS ===== */
.ep-quick-actions {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.ep-quick-action-btn {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 15px;
    background: linear-gradient(135deg, #4CAF50, #8BC34A);
    color: white;
    text-decoration: none;
    border-radius: 10px;
    transition: all 0.3s ease;
    font-weight: 500;
}

.ep-quick-action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
    color: white;
}

.ep-quick-action-btn.secondary {
    background: linear-gradient(135deg, #2196F3, #64B5F6);
}

.ep-quick-action-btn.secondary:hover {
    box-shadow: 0 4px 15px rgba(33, 150, 243, 0.3);
}

/* ===== PAGINATION ===== */
.ep-pagination {
    display: flex;
    justify-content: center;
    margin-top: 40px;
    gap: 10px;
}

.ep-pagination a,
.ep-pagination span {
    padding: 12px 18px;
    background: white;
    border: 1px solid #ddd;
    text-decoration: none;
    color: #333;
    border-radius: 8px;
    transition: all 0.3s ease;
    font-weight: 500;
}

.ep-pagination a:hover {
    background: #4CAF50;
    color: white;
    border-color: #4CAF50;
}

.ep-pagination .current {
    background: #4CAF50;
    color: white;
    border-color: #4CAF50;
}

/* ===== NO RESULTS ===== */
.ep-no-results {
    text-align: center;
    padding: 60px 20px;
    color: #666;
}

.ep-no-results-icon {
    font-size: 4rem;
    margin-bottom: 20px;
    opacity: 0.5;
}

.ep-no-results h3 {
    font-size: 1.5rem;
    margin-bottom: 15px;
    color: #333;
}

.ep-no-results p {
    font-size: 1rem;
    line-height: 1.6;
    margin-bottom: 25px;
}

/* ===== LOADING STATES ===== */
.ep-loading {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 40px;
}

.ep-spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #f3f3f3;
    border-top: 4px solid #4CAF50;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* ===== RESPONSIVE DESIGN ===== */
@media (max-width: 1200px) {
    .ep-main-content {
        grid-template-columns: 1fr 300px;
    }
}

@media (max-width: 968px) {
    .ep-main-content {
        grid-template-columns: 1fr;
    }
    
    .ep-archive-title {
        font-size: 2.5rem;
    }
    
    .ep-archive-stats {
        gap: 20px;
    }
    
    .ep-filters-grid {
        grid-template-columns: 1fr;
    }
    
    .ep-exchanges-grid.grid-view {
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    }
    
    .ep-exchanges-grid.list-view .ep-exchange-card {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .ep-exchanges-grid.list-view .ep-exchange-image {
        height: 200px;
    }
}

@media (max-width: 640px) {
    .ep-exchange-archive {
        padding: 15px;
    }
    
    .ep-archive-header {
        padding: 40px 20px;
    }
    
    .ep-archive-title {
        font-size: 2rem;
    }
    
    .ep-archive-stats {
        flex-direction: column;
        gap: 15px;
    }
    
    .ep-filters-container {
        padding: 20px;
    }
    
    .ep-exchanges-grid.grid-view {
        grid-template-columns: 1fr;
        padding: 15px;
    }
    
    .ep-sidebar-widget {
        padding: 20px;
    }
    
    .ep-filters-actions {
        flex-direction: column;
        align-items: stretch;
    }
}
</style>

<div class="ep-exchange-archive">
    <!-- Archive Header -->
    <div class="ep-archive-header">
        <h1 class="ep-archive-title">🔄 <?php _e('Item Exchange Hub', 'environmental-platform-core'); ?></h1>
        <p class="ep-archive-description">
            <?php _e('Join our circular economy! Share, exchange, lend, and discover items in your community. Every exchange helps reduce waste and builds stronger environmental connections.', 'environmental-platform-core'); ?>
        </p>
        
        <div class="ep-archive-stats">
            <div class="ep-stat-item">
                <span class="ep-stat-number" id="totalExchanges"><?php echo wp_count_posts('item_exchange')->publish; ?></span>
                <span class="ep-stat-label"><?php _e('Active Exchanges', 'environmental-platform-core'); ?></span>
            </div>
            <div class="ep-stat-item">
                <span class="ep-stat-number" id="totalMembers">
                    <?php 
                    $users_with_exchanges = get_users(array(
                        'meta_query' => array(
                            array(
                                'key' => '_has_exchanges',
                                'compare' => 'EXISTS'
                            )
                        ),
                        'count_total' => true
                    ));
                    echo $users_with_exchanges->get_total();
                    ?>
                </span>
                <span class="ep-stat-label"><?php _e('Community Members', 'environmental-platform-core'); ?></span>
            </div>
            <div class="ep-stat-item">
                <span class="ep-stat-number" id="itemsSaved">
                    <?php 
                    $saved_items = get_option('ep_total_items_saved', 2847);
                    echo number_format($saved_items);
                    ?>
                </span>
                <span class="ep-stat-label"><?php _e('Items Saved from Waste', 'environmental-platform-core'); ?></span>
            </div>
            <div class="ep-stat-item">
                <span class="ep-stat-number" id="carbonSaved">
                    <?php 
                    $carbon_saved = get_option('ep_total_carbon_saved', 12.4);
                    echo $carbon_saved;
                    ?>kg</span>
                <span class="ep-stat-label"><?php _e('CO₂ Saved', 'environmental-platform-core'); ?></span>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="ep-filters-container">
        <div class="ep-filters-header">
            <h3 class="ep-filters-title">🔍 <?php _e('Find Your Perfect Exchange', 'environmental-platform-core'); ?></h3>
            <div class="ep-view-toggle">
                <button class="ep-view-btn active" data-view="grid" title="<?php _e('Grid View', 'environmental-platform-core'); ?>">
                    <span class="dashicons dashicons-grid-view"></span>
                </button>
                <button class="ep-view-btn" data-view="list" title="<?php _e('List View', 'environmental-platform-core'); ?>">
                    <span class="dashicons dashicons-list-view"></span>
                </button>
            </div>
        </div>
        
        <form class="ep-filters-form" method="GET">
            <div class="ep-filters-grid">
                <div class="ep-filter-group">
                    <label class="ep-filter-label"><?php _e('Exchange Type', 'environmental-platform-core'); ?></label>
                    <select name="exchange_type" class="ep-filter-select">
                        <option value=""><?php _e('All Types', 'environmental-platform-core'); ?></option>
                        <option value="give_away" <?php selected(get_query_var('exchange_type'), 'give_away'); ?>><?php _e('Give Away', 'environmental-platform-core'); ?></option>
                        <option value="exchange" <?php selected(get_query_var('exchange_type'), 'exchange'); ?>><?php _e('Exchange', 'environmental-platform-core'); ?></option>
                        <option value="lending" <?php selected(get_query_var('exchange_type'), 'lending'); ?>><?php _e('Lending', 'environmental-platform-core'); ?></option>
                        <option value="request" <?php selected(get_query_var('exchange_type'), 'request'); ?>><?php _e('Looking For', 'environmental-platform-core'); ?></option>
                    </select>
                </div>
                
                <div class="ep-filter-group">
                    <label class="ep-filter-label"><?php _e('Category', 'environmental-platform-core'); ?></label>
                    <select name="exchange_category" class="ep-filter-select">
                        <option value=""><?php _e('All Categories', 'environmental-platform-core'); ?></option>
                        <?php
                        $categories = get_terms(array(
                            'taxonomy' => 'exchange_type',
                            'hide_empty' => false,
                        ));
                        foreach ($categories as $category) {
                            echo '<option value="' . esc_attr($category->slug) . '" ' . selected(get_query_var('exchange_category'), $category->slug, false) . '>' . esc_html($category->name) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                
                <div class="ep-filter-group">
                    <label class="ep-filter-label"><?php _e('Item Condition', 'environmental-platform-core'); ?></label>
                    <select name="item_condition" class="ep-filter-select">
                        <option value=""><?php _e('Any Condition', 'environmental-platform-core'); ?></option>
                        <option value="new" <?php selected(get_query_var('item_condition'), 'new'); ?>><?php _e('New', 'environmental-platform-core'); ?></option>
                        <option value="like_new" <?php selected(get_query_var('item_condition'), 'like_new'); ?>><?php _e('Like New', 'environmental-platform-core'); ?></option>
                        <option value="good" <?php selected(get_query_var('item_condition'), 'good'); ?>><?php _e('Good', 'environmental-platform-core'); ?></option>
                        <option value="fair" <?php selected(get_query_var('item_condition'), 'fair'); ?>><?php _e('Fair', 'environmental-platform-core'); ?></option>
                        <option value="poor" <?php selected(get_query_var('item_condition'), 'poor'); ?>><?php _e('Needs Repair', 'environmental-platform-core'); ?></option>
                    </select>
                </div>
                
                <div class="ep-filter-group">
                    <label class="ep-filter-label"><?php _e('Location', 'environmental-platform-core'); ?></label>
                    <select name="location" class="ep-filter-select">
                        <option value=""><?php _e('All Locations', 'environmental-platform-core'); ?></option>
                        <?php
                        $locations = get_terms(array(
                            'taxonomy' => 'region',
                            'hide_empty' => false,
                        ));
                        foreach ($locations as $location) {
                            echo '<option value="' . esc_attr($location->slug) . '" ' . selected(get_query_var('location'), $location->slug, false) . '>' . esc_html($location->name) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                
                <div class="ep-search-container">
                    <input 
                        type="text" 
                        name="search" 
                        class="ep-search-input" 
                        placeholder="<?php _e('Search for items...', 'environmental-platform-core'); ?>"
                        value="<?php echo esc_attr(get_query_var('search')); ?>"
                    >
                    <button type="submit" class="ep-search-btn">
                        <span class="dashicons dashicons-search"></span>
                    </button>
                </div>
            </div>
            
            <div class="ep-filters-actions">
                <button type="button" class="ep-clear-filters" onclick="clearFilters()">
                    <?php _e('Clear All Filters', 'environmental-platform-core'); ?>
                </button>
                <div class="ep-results-info">
                    <?php
                    global $wp_query;
                    $found_posts = $wp_query->found_posts;
                    printf(
                        _n(
                            'Showing %d exchange',
                            'Showing %d exchanges',
                            $found_posts,
                            'environmental-platform-core'
                        ),
                        $found_posts
                    );
                    ?>
                </div>
            </div>
        </form>
    </div>

    <!-- Main Content -->
    <div class="ep-main-content">
        <!-- Exchanges List -->
        <div class="ep-exchanges-container">
            <div class="ep-exchanges-header">
                <h3><?php _e('Available Exchanges', 'environmental-platform-core'); ?></h3>
                <div class="ep-sort-container">
                    <label class="ep-sort-label"><?php _e('Sort by:', 'environmental-platform-core'); ?></label>
                    <select class="ep-sort-select" onchange="sortExchanges(this.value)">
                        <option value="date_desc"><?php _e('Newest First', 'environmental-platform-core'); ?></option>
                        <option value="date_asc"><?php _e('Oldest First', 'environmental-platform-core'); ?></option>
                        <option value="title_asc"><?php _e('Title A-Z', 'environmental-platform-core'); ?></option>
                        <option value="title_desc"><?php _e('Title Z-A', 'environmental-platform-core'); ?></option>
                        <option value="popular"><?php _e('Most Popular', 'environmental-platform-core'); ?></option>
                    </select>
                </div>
            </div>
            
            <div class="ep-exchanges-grid grid-view" id="exchangesGrid">
                <?php if (have_posts()) : ?>
                    <?php while (have_posts()) : the_post(); ?>
                        <article class="ep-exchange-card" data-exchange-id="<?php the_ID(); ?>">
                            <div class="ep-exchange-image">
                                <?php if (has_post_thumbnail()) : ?>
                                    <a href="<?php the_permalink(); ?>">
                                        <?php the_post_thumbnail('medium'); ?>
                                    </a>
                                <?php else : ?>
                                    <a href="<?php the_permalink(); ?>">
                                        <img src="<?php echo plugin_dir_url(__FILE__) . '../assets/images/no-image-exchange.jpg'; ?>" alt="<?php _e('No image available', 'environmental-platform-core'); ?>">
                                    </a>
                                <?php endif; ?>
                                
                                <?php
                                $exchange_type = get_post_meta(get_the_ID(), '_exchange_type', true);
                                $is_urgent = get_post_meta(get_the_ID(), '_is_urgent', true);
                                ?>
                                
                                <div class="ep-exchange-type-badge ep-type-<?php echo esc_attr($exchange_type ?: 'exchange'); ?>">
                                    <?php
                                    $type_labels = array(
                                        'give_away' => __('Free', 'environmental-platform-core'),
                                        'exchange' => __('Exchange', 'environmental-platform-core'),
                                        'lending' => __('Lend', 'environmental-platform-core'),
                                        'request' => __('Wanted', 'environmental-platform-core')
                                    );
                                    echo esc_html($type_labels[$exchange_type] ?? __('Exchange', 'environmental-platform-core'));
                                    ?>
                                </div>
                                
                                <?php if ($is_urgent) : ?>
                                    <div class="ep-urgent-badge">
                                        <?php _e('Urgent!', 'environmental-platform-core'); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="ep-exchange-content">
                                <h3 class="ep-exchange-title">
                                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                </h3>
                                
                                <div class="ep-exchange-description">
                                    <?php echo wp_trim_words(get_the_excerpt(), 20, '...'); ?>
                                </div>
                                
                                <div class="ep-exchange-meta">
                                    <?php
                                    $condition = get_post_meta(get_the_ID(), '_item_condition', true);
                                    $location = get_post_meta(get_the_ID(), '_exchange_location', true);
                                    $estimated_value = get_post_meta(get_the_ID(), '_estimated_value', true);
                                    ?>
                                    
                                    <?php if ($condition) : ?>
                                        <div class="ep-meta-item">
                                            <span>⭐</span>
                                            <span class="ep-condition-badge ep-condition-<?php echo esc_attr($condition); ?>">
                                                <?php
                                                $condition_labels = array(
                                                    'new' => __('New', 'environmental-platform-core'),
                                                    'like_new' => __('Like New', 'environmental-platform-core'),
                                                    'good' => __('Good', 'environmental-platform-core'),
                                                    'fair' => __('Fair', 'environmental-platform-core'),
                                                    'poor' => __('Needs Repair', 'environmental-platform-core')
                                                );
                                                echo esc_html($condition_labels[$condition] ?? $condition);
                                                ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($location) : ?>
                                        <div class="ep-meta-item">
                                            <span>📍</span>
                                            <span><?php echo esc_html($location); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($estimated_value && $exchange_type !== 'give_away') : ?>
                                        <div class="ep-meta-item">
                                            <span>💰</span>
                                            <span><?php echo number_format($estimated_value); ?> VND</span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="ep-meta-item">
                                        <span>📅</span>
                                        <span><?php echo human_time_diff(get_the_time('U'), current_time('timestamp')) . ' ' . __('ago', 'environmental-platform-core'); ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="ep-exchange-footer">
                                <div class="ep-exchange-stats">
                                    <div class="ep-stat">
                                        <span>👁️</span>
                                        <span><?php echo get_post_meta(get_the_ID(), '_view_count', true) ?: 0; ?></span>
                                    </div>
                                    <div class="ep-stat">
                                        <span>❤️</span>
                                        <span><?php echo get_post_meta(get_the_ID(), '_like_count', true) ?: 0; ?></span>
                                    </div>
                                    <div class="ep-stat">
                                        <span>💬</span>
                                        <span><?php echo get_comments_number(); ?></span>
                                    </div>
                                </div>
                                
                                <div class="ep-exchange-actions">
                                    <button class="ep-action-btn ep-btn-secondary" onclick="saveExchange(<?php the_ID(); ?>)">
                                        <span class="dashicons dashicons-heart"></span>
                                        <?php _e('Save', 'environmental-platform-core'); ?>
                                    </button>
                                    <a href="<?php the_permalink(); ?>" class="ep-action-btn ep-btn-primary">
                                        <?php _e('View Details', 'environmental-platform-core'); ?>
                                    </a>
                                </div>
                            </div>
                        </article>
                    <?php endwhile; ?>
                <?php else : ?>
                    <div class="ep-no-results">
                        <div class="ep-no-results-icon">🔍</div>
                        <h3><?php _e('No exchanges found', 'environmental-platform-core'); ?></h3>
                        <p><?php _e('Try adjusting your filters or search terms. You can also be the first to post an exchange in this category!', 'environmental-platform-core'); ?></p>
                        <a href="/wp-admin/post-new.php?post_type=item_exchange" class="ep-action-btn ep-btn-primary">
                            <?php _e('Post New Exchange', 'environmental-platform-core'); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($wp_query->max_num_pages > 1) : ?>
                <div class="ep-pagination">
                    <?php
                    echo paginate_links(array(
                        'total' => $wp_query->max_num_pages,
                        'current' => max(1, get_query_var('paged')),
                        'mid_size' => 2,
                        'prev_text' => '« ' . __('Previous', 'environmental-platform-core'),
                        'next_text' => __('Next', 'environmental-platform-core') . ' »',
                    ));
                    ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <aside class="ep-sidebar">
            <!-- Quick Actions -->
            <div class="ep-sidebar-widget">
                <h4 class="ep-widget-title">
                    <span>🚀</span>
                    <?php _e('Quick Actions', 'environmental-platform-core'); ?>
                </h4>
                <div class="ep-quick-actions">
                    <a href="/wp-admin/post-new.php?post_type=item_exchange" class="ep-quick-action-btn">
                        <span class="dashicons dashicons-plus-alt"></span>
                        <?php _e('Post New Exchange', 'environmental-platform-core'); ?>
                    </a>
                    <a href="<?php echo get_permalink(get_option('ep_saved_exchanges_page')); ?>" class="ep-quick-action-btn secondary">
                        <span class="dashicons dashicons-heart"></span>
                        <?php _e('My Saved Items', 'environmental-platform-core'); ?>
                    </a>
                </div>
            </div>
            
            <!-- Featured Exchanges -->
            <div class="ep-sidebar-widget ep-featured-exchanges">
                <h4 class="ep-widget-title">
                    <span>⭐</span>
                    <?php _e('Featured Exchanges', 'environmental-platform-core'); ?>
                </h4>
                <?php
                $featured_exchanges = new WP_Query(array(
                    'post_type' => 'item_exchange',
                    'posts_per_page' => 5,
                    'meta_query' => array(
                        array(
                            'key' => '_is_featured',
                            'value' => '1',
                            'compare' => '='
                        )
                    )
                ));
                
                if ($featured_exchanges->have_posts()) :
                    while ($featured_exchanges->have_posts()) : $featured_exchanges->the_post();
                ?>
                    <div class="ep-featured-item">
                        <div class="ep-featured-thumbnail">
                            <?php if (has_post_thumbnail()) : ?>
                                <a href="<?php the_permalink(); ?>">
                                    <?php the_post_thumbnail('thumbnail'); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="ep-featured-content">
                            <h4><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h4>
                            <div class="ep-featured-meta">
                                <?php
                                $exchange_type = get_post_meta(get_the_ID(), '_exchange_type', true);
                                $type_labels = array(
                                    'give_away' => __('Free', 'environmental-platform-core'),
                                    'exchange' => __('Exchange', 'environmental-platform-core'),
                                    'lending' => __('Lend', 'environmental-platform-core'),
                                    'request' => __('Wanted', 'environmental-platform-core')
                                );
                                echo esc_html($type_labels[$exchange_type] ?? __('Exchange', 'environmental-platform-core'));
                                echo ' • ' . human_time_diff(get_the_time('U'), current_time('timestamp')) . ' ' . __('ago', 'environmental-platform-core');
                                ?>
                            </div>
                        </div>
                    </div>
                <?php
                    endwhile;
                    wp_reset_postdata();
                else :
                ?>
                    <p><?php _e('No featured exchanges at the moment.', 'environmental-platform-core'); ?></p>
                <?php endif; ?>
            </div>
            
            <!-- Exchange Categories -->
            <div class="ep-sidebar-widget">
                <h4 class="ep-widget-title">
                    <span>📂</span>
                    <?php _e('Browse Categories', 'environmental-platform-core'); ?>
                </h4>
                <ul class="ep-categories-list">
                    <?php
                    $categories = get_terms(array(
                        'taxonomy' => 'exchange_type',
                        'hide_empty' => true,
                        'number' => 8
                    ));
                    
                    foreach ($categories as $category) :
                    ?>
                        <li>
                            <a href="<?php echo get_term_link($category); ?>">
                                <span><?php echo esc_html($category->name); ?></span>
                                <span class="ep-category-count"><?php echo $category->count; ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <!-- Exchange Tips -->
            <div class="ep-sidebar-widget">
                <h4 class="ep-widget-title">
                    <span>💡</span>
                    <?php _e('Exchange Tips', 'environmental-platform-core'); ?>
                </h4>
                <ul class="ep-tips-list">
                    <li><?php _e('Take clear, well-lit photos from multiple angles', 'environmental-platform-core'); ?></li>
                    <li><?php _e('Be honest about item condition and any defects', 'environmental-platform-core'); ?></li>
                    <li><?php _e('Meet in safe, public places for exchanges', 'environmental-platform-core'); ?></li>
                    <li><?php _e('Communicate clearly about pickup/delivery preferences', 'environmental-platform-core'); ?></li>
                    <li><?php _e('Consider the environmental impact of your exchange', 'environmental-platform-core'); ?></li>
                </ul>
            </div>
            
            <!-- Environmental Impact -->
            <div class="ep-sidebar-widget">
                <h4 class="ep-widget-title">
                    <span>🌱</span>
                    <?php _e('Environmental Impact', 'environmental-platform-core'); ?>
                </h4>
                <div class="ep-impact-stats">
                    <div class="ep-impact-item">
                        <div class="ep-impact-number"><?php echo number_format(get_option('ep_total_items_saved', 2847)); ?></div>
                        <div class="ep-impact-label"><?php _e('Items saved from landfill', 'environmental-platform-core'); ?></div>
                    </div>
                    <div class="ep-impact-item">
                        <div class="ep-impact-number"><?php echo get_option('ep_total_carbon_saved', 12.4); ?>kg</div>
                        <div class="ep-impact-label"><?php _e('CO₂ emissions prevented', 'environmental-platform-core'); ?></div>
                    </div>
                    <div class="ep-impact-item">
                        <div class="ep-impact-number"><?php echo number_format(get_option('ep_community_connections', 1584)); ?></div>
                        <div class="ep-impact-label"><?php _e('Community connections made', 'environmental-platform-core'); ?></div>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</div>

<script>
// View Toggle Functionality
document.addEventListener('DOMContentLoaded', function() {
    const viewBtns = document.querySelectorAll('.ep-view-btn');
    const exchangesGrid = document.getElementById('exchangesGrid');
    
    viewBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            viewBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            const view = this.getAttribute('data-view');
            exchangesGrid.className = `ep-exchanges-grid ${view}-view`;
            
            // Save preference
            localStorage.setItem('ep_exchange_view_preference', view);
        });
    });
    
    // Load saved view preference
    const savedView = localStorage.getItem('ep_exchange_view_preference');
    if (savedView) {
        const savedBtn = document.querySelector(`[data-view="${savedView}"]`);
        if (savedBtn) {
            savedBtn.click();
        }
    }
});

// Clear Filters Function
function clearFilters() {
    const form = document.querySelector('.ep-filters-form');
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

// Sort Exchanges Function
function sortExchanges(sortBy) {
    const url = new URL(window.location);
    url.searchParams.set('orderby', sortBy);
    window.location.href = url.toString();
}

// Save Exchange Function
function saveExchange(exchangeId) {
    // Check if user is logged in
    if (!document.body.classList.contains('logged-in')) {
        alert('<?php _e('Please log in to save exchanges.', 'environmental-platform-core'); ?>');
        return;
    }
    
    // AJAX call to save exchange
    const data = new FormData();
    data.append('action', 'ep_save_exchange');
    data.append('exchange_id', exchangeId);
    data.append('nonce', epExchangeData.nonce);
    
    fetch(epExchangeData.ajaxUrl, {
        method: 'POST',
        body: data
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            const btn = event.target.closest('.ep-action-btn');
            btn.innerHTML = '<span class="dashicons dashicons-yes"></span> ' + 
                           '<?php _e('Saved!', 'environmental-platform-core'); ?>';
            btn.classList.add('saved');
            setTimeout(() => {
                btn.innerHTML = '<span class="dashicons dashicons-heart"></span> ' + 
                               '<?php _e('Save', 'environmental-platform-core'); ?>';
                btn.classList.remove('saved');
            }, 2000);
        } else {
            alert(result.data || '<?php _e('Error saving exchange.', 'environmental-platform-core'); ?>');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('<?php _e('Error saving exchange.', 'environmental-platform-core'); ?>');
    });
}

// Auto-refresh stats every 30 seconds
setInterval(function() {
    fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=ep_get_exchange_stats')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('totalExchanges').textContent = data.data.total_exchanges;
                document.getElementById('totalMembers').textContent = data.data.total_members;
                document.getElementById('itemsSaved').textContent = data.data.items_saved;
                document.getElementById('carbonSaved').textContent = data.data.carbon_saved + 'kg';
            }
        })
        .catch(error => console.error('Stats update error:', error));
}, 30000);

// Infinite scroll functionality
let isLoading = false;
let page = 1;

function loadMoreExchanges() {
    if (isLoading) return;
    
    isLoading = true;
    const loadingElement = document.createElement('div');
    loadingElement.className = 'ep-loading';
    loadingElement.innerHTML = '<div class="ep-spinner"></div>';
    
    document.getElementById('exchangesGrid').appendChild(loadingElement);
    
    const url = new URL(window.location);
    url.searchParams.set('paged', page + 1);
    
    fetch(url.toString())
        .then(response => response.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newCards = doc.querySelectorAll('.ep-exchange-card');
            
            loadingElement.remove();
            
            if (newCards.length > 0) {
                newCards.forEach(card => {
                    document.getElementById('exchangesGrid').appendChild(card);
                });
                page++;
            }
            
            isLoading = false;
        })
        .catch(error => {
            console.error('Load more error:', error);
            loadingElement.remove();
            isLoading = false;
        });
}

// Trigger infinite scroll when near bottom
window.addEventListener('scroll', function() {
    if ((window.innerHeight + window.scrollY) >= document.body.offsetHeight - 1000) {
        loadMoreExchanges();
    }
});
</script>

<?php get_footer(); ?>
