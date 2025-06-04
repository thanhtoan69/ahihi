<?php
/**
 * Frontend System Test
 * 
 * Tests all frontend functionality including templates, AJAX, and user interactions
 */

// Load WordPress
require_once('wp-config.php');
require_once('wp-load.php');

get_header();
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Frontend System Test - Environmental Item Exchange</title>
    
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; line-height: 1.6; }
        .container { max-width: 1200px; margin: 0 auto; }
        .test-section { margin: 30px 0; padding: 20px; border: 1px solid #ddd; border-radius: 8px; background: #f9f9f9; }
        .status { padding: 10px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; }
        .button { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px; display: inline-block; margin: 5px; cursor: pointer; border: none; }
        .button:hover { background: #005a87; }
        .exchange-card { background: white; border: 1px solid #ddd; border-radius: 8px; padding: 15px; margin: 10px; display: inline-block; width: 300px; vertical-align: top; }
        .exchange-title { font-weight: bold; color: #333; margin-bottom: 10px; }
        .exchange-meta { font-size: 0.9em; color: #666; }
        .search-form { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .search-input { width: 300px; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
        .results-container { min-height: 200px; background: white; padding: 20px; border-radius: 8px; }
        .loading { text-align: center; padding: 40px; color: #666; }
        #ajax-result { background: white; padding: 15px; border-radius: 5px; margin-top: 15px; }
    </style>
    
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>

<div class="container">
    <h1>Environmental Item Exchange - Frontend System Test</h1>
    
    <!-- Plugin Status Section -->
    <div class="test-section">
        <h2>🔍 Plugin Status Check</h2>
        
        <?php
        // Check if plugin is active
        $active_plugins = get_option('active_plugins', array());
        $plugin_active = in_array('environmental-item-exchange/environmental-item-exchange.php', $active_plugins);
        
        if ($plugin_active) {
            echo '<div class="success">✓ Environmental Item Exchange plugin is active</div>';
        } else {
            echo '<div class="error">✗ Plugin is not active</div>';
            echo '<a href="wp-admin/plugins.php" class="button">Activate Plugin</a>';
        }
        
        // Check post type
        if (post_type_exists('item_exchange')) {
            echo '<div class="success">✓ item_exchange post type is registered</div>';
        } else {
            echo '<div class="error">✗ item_exchange post type not found</div>';
        }
        
        // Check template files
        $template_files = array(
            'single-item_exchange.php',
            'archive-item_exchange.php',
            'partials/exchange-card.php'
        );
        
        $template_path = WP_PLUGIN_DIR . '/environmental-item-exchange/templates/';
        foreach ($template_files as $template) {
            if (file_exists($template_path . $template)) {
                echo '<div class="success">✓ Template found: ' . $template . '</div>';
            } else {
                echo '<div class="error">✗ Template missing: ' . $template . '</div>';
            }
        }
        ?>
    </div>
    
    <!-- Exchange Data Section -->
    <div class="test-section">
        <h2>📦 Exchange Data Test</h2>
        
        <?php
        // Get all exchanges
        $exchanges = get_posts(array(
            'post_type' => 'item_exchange',
            'posts_per_page' => 10,
            'post_status' => 'publish'
        ));
        
        if ($exchanges) {
            echo '<div class="success">✓ Found ' . count($exchanges) . ' exchange(s)</div>';
            
            echo '<div class="results-container">';
            foreach ($exchanges as $exchange) {
                $exchange_type = get_post_meta($exchange->ID, 'exchange_type', true);
                $condition = get_post_meta($exchange->ID, 'item_condition', true);
                $impact = get_post_meta($exchange->ID, 'environmental_impact', true);
                
                echo '<div class="exchange-card">';
                echo '<div class="exchange-title">' . get_the_title($exchange) . '</div>';
                echo '<div class="exchange-meta">';
                echo 'Type: ' . ($exchange_type ?: 'N/A') . '<br>';
                echo 'Condition: ' . ($condition ?: 'N/A') . '<br>';
                echo 'Impact Score: ' . ($impact ?: 'N/A') . '<br>';
                echo 'Date: ' . get_the_date('', $exchange) . '<br>';
                echo '</div>';
                echo '<a href="' . get_permalink($exchange->ID) . '" class="button">View Details</a>';
                echo '</div>';
            }
            echo '</div>';
            
        } else {
            echo '<div class="warning">⚠ No exchanges found</div>';
            echo '<a href="wp-admin/post-new.php?post_type=item_exchange" class="button">Create Test Exchange</a>';
        }
        
        // Archive link test
        $archive_url = get_post_type_archive_link('item_exchange');
        if ($archive_url) {
            echo '<div class="info">Archive URL: <a href="' . $archive_url . '">' . $archive_url . '</a></div>';
        }
        ?>
    </div>
    
    <!-- Search Functionality Test -->
    <div class="test-section">
        <h2>🔍 Search Functionality Test</h2>
        
        <div class="search-form">
            <h3>Test Search Form</h3>
            <input type="text" id="search-input" class="search-input" placeholder="Enter search term (e.g., 'bike', 'free', 'garden')" value="test">
            <button id="search-btn" class="button">Search Exchanges</button>
            <button id="reset-search" class="button" style="background: #666;">Reset</button>
        </div>
        
        <div id="search-results" class="results-container">
            <div class="loading">Click "Search Exchanges" to test the search functionality</div>
        </div>
    </div>
    
    <!-- AJAX Functionality Test -->
    <div class="test-section">
        <h2>⚡ AJAX Functionality Test</h2>
        
        <div id="ajax-test-controls">
            <button id="test-ajax-search" class="button">Test AJAX Search</button>
            <button id="test-ajax-save" class="button">Test Save Exchange</button>
            <button id="test-ajax-contact" class="button">Test Contact Owner</button>
        </div>
        
        <div id="ajax-result">
            <div class="info">Click any button above to test AJAX functionality</div>
        </div>
    </div>
    
    <!-- Template System Test -->
    <div class="test-section">
        <h2>🎨 Template System Test</h2>
        
        <?php
        // Test template loading
        $frontend_templates_class = 'Environmental_Item_Exchange_Frontend_Templates';
        if (class_exists($frontend_templates_class)) {
            echo '<div class="success">✓ Frontend Templates class available</div>';
            
            try {
                $template_instance = $frontend_templates_class::get_instance();
                echo '<div class="success">✓ Template instance created successfully</div>';
            } catch (Exception $e) {
                echo '<div class="error">✗ Template instance creation failed: ' . $e->getMessage() . '</div>';
            }
        } else {
            echo '<div class="error">✗ Frontend Templates class not found</div>';
        }
        
        // Test shortcodes
        echo '<h4>Shortcode Tests</h4>';
        $shortcodes = array('exchange_search', 'exchange_map', 'user_exchanges', 'exchange_stats');
        
        foreach ($shortcodes as $shortcode) {
            if (shortcode_exists($shortcode)) {
                echo '<div class="success">✓ Shortcode registered: [' . $shortcode . ']</div>';
            } else {
                echo '<div class="warning">⚠ Shortcode not found: [' . $shortcode . ']</div>';
            }
        }
        ?>
    </div>
    
    <!-- Navigation Links -->
    <div class="test-section">
        <h2>🔗 Quick Navigation</h2>
        
        <a href="wp-admin/edit.php?post_type=item_exchange" class="button">Admin: Manage Exchanges</a>
        <a href="wp-admin/post-new.php?post_type=item_exchange" class="button">Admin: Add Exchange</a>
        <a href="<?php echo get_post_type_archive_link('item_exchange'); ?>" class="button">View Archive</a>
        <a href="minimal-activation-test.php" class="button">Activation Test</a>
        <a href="test-plugin-database.php" class="button">Database Test</a>
    </div>
    
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Frontend test script loaded');
    
    // Check if jQuery and AJAX are available
    if (typeof jQuery !== 'undefined') {
        console.log('jQuery available, version:', jQuery.fn.jquery);
    } else {
        console.log('jQuery not available');
    }
    
    if (typeof eie_ajax !== 'undefined') {
        console.log('EIE AJAX object available:', eie_ajax);
    } else {
        console.log('EIE AJAX object not available');
    }
    
    // Search functionality
    const searchBtn = document.getElementById('search-btn');
    const searchInput = document.getElementById('search-input');
    const searchResults = document.getElementById('search-results');
    const resetBtn = document.getElementById('reset-search');
    
    if (searchBtn) {
        searchBtn.addEventListener('click', function() {
            const searchTerm = searchInput.value.trim();
            
            searchResults.innerHTML = '<div class="loading">Searching for: "' + searchTerm + '"...</div>';
            
            // Simple WordPress search using fetch
            const searchUrl = '<?php echo home_url('/'); ?>?s=' + encodeURIComponent(searchTerm) + '&post_type=item_exchange';
            
            fetch(searchUrl)
                .then(response => response.text())
                .then(html => {
                    // Parse the response to extract results
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    
                    // Look for exchange posts in the response
                    const posts = doc.querySelectorAll('.post, article, .exchange-item');
                    
                    if (posts.length > 0) {
                        searchResults.innerHTML = '<div class="success">Found ' + posts.length + ' result(s)</div>';
                        posts.forEach(post => {
                            const title = post.querySelector('h1, h2, h3, .entry-title, .post-title');
                            if (title) {
                                searchResults.innerHTML += '<div class="exchange-card">' + title.outerHTML + '</div>';
                            }
                        });
                    } else {
                        searchResults.innerHTML = '<div class="info">No results found for: "' + searchTerm + '"</div>';
                    }
                })
                .catch(error => {
                    console.error('Search error:', error);
                    searchResults.innerHTML = '<div class="error">Search failed: ' + error.message + '</div>';
                });
        });
    }
    
    if (resetBtn) {
        resetBtn.addEventListener('click', function() {
            searchInput.value = '';
            searchResults.innerHTML = '<div class="loading">Enter a search term and click "Search Exchanges"</div>';
        });
    }
    
    // AJAX test buttons
    const ajaxTestButtons = {
        'test-ajax-search': function() {
            testAjaxCall('eie_search_exchanges', {search_term: 'test'}, 'Search');
        },
        'test-ajax-save': function() {
            testAjaxCall('eie_save_exchange', {exchange_id: 1}, 'Save');
        },
        'test-ajax-contact': function() {
            testAjaxCall('eie_contact_owner', {exchange_id: 1, message: 'Test message'}, 'Contact');
        }
    };
    
    Object.keys(ajaxTestButtons).forEach(buttonId => {
        const button = document.getElementById(buttonId);
        if (button) {
            button.addEventListener('click', ajaxTestButtons[buttonId]);
        }
    });
    
    function testAjaxCall(action, data, name) {
        const resultDiv = document.getElementById('ajax-result');
        resultDiv.innerHTML = '<div class="loading">Testing ' + name + ' AJAX call...</div>';
        
        if (typeof jQuery !== 'undefined' && typeof eie_ajax !== 'undefined') {
            jQuery.ajax({
                url: eie_ajax.ajax_url,
                type: 'POST',
                data: Object.assign({
                    action: action,
                    nonce: eie_ajax.nonce
                }, data),
                success: function(response) {
                    resultDiv.innerHTML = '<div class="success">✓ ' + name + ' AJAX call successful</div>';
                    resultDiv.innerHTML += '<pre>' + JSON.stringify(response, null, 2) + '</pre>';
                },
                error: function(xhr, status, error) {
                    resultDiv.innerHTML = '<div class="error">✗ ' + name + ' AJAX call failed</div>';
                    resultDiv.innerHTML += '<div class="info">Status: ' + status + '<br>Error: ' + error + '</div>';
                }
            });
        } else {
            resultDiv.innerHTML = '<div class="error">✗ AJAX prerequisites not available</div>';
            if (typeof jQuery === 'undefined') {
                resultDiv.innerHTML += '<div class="info">jQuery not loaded</div>';
            }
            if (typeof eie_ajax === 'undefined') {
                resultDiv.innerHTML += '<div class="info">EIE AJAX object not available</div>';
            }
        }
    }
});
</script>

<?php wp_footer(); ?>
</body>
</html>
