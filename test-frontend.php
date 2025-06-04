<?php
/**
 * Frontend Template Test Page
 * 
 * Tests the frontend templates and functionality
 */

// Load WordPress
require_once('wp-config.php');
require_once('wp-load.php');

// Check if we need to create test data
if (isset($_GET['create_test_data'])) {
    // Create some test exchange posts
    for ($i = 1; $i <= 3; $i++) {
        $post_data = array(
            'post_title' => 'Test Exchange Item ' . $i,
            'post_content' => 'This is a test exchange item #' . $i . ' for testing the frontend functionality.',
            'post_status' => 'publish',
            'post_type' => 'item_exchange',
            'meta_input' => array(
                'exchange_type' => ($i % 2 == 0) ? 'trade' : 'free',
                'item_condition' => 'good',
                'environmental_impact' => rand(5, 50),
                'co2_savings' => rand(1, 10),
                'location' => 'Test Location ' . $i,
                'contact_method' => 'message'
            )
        );
        
        $post_id = wp_insert_post($post_data);
        if ($post_id) {
            echo "<div class='success'>Created test exchange post ID: $post_id</div>";
        }
    }
}

get_header();
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Frontend Template Test - Environmental Item Exchange</title>
    
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .test-section { margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
        .status { padding: 10px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }
        .button { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px; display: inline-block; margin: 5px; }
        .exchange-test { background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 5px; }
    </style>
    
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>

<div class="container">
    <h1>Environmental Item Exchange - Frontend Template Test</h1>
    
    <div class="test-section">
        <h2>Template System Test</h2>
        
        <?php
        // Check if frontend templates class is loaded
        if (class_exists('EIE_Frontend_Templates')) {
            echo '<div class="success">✓ EIE_Frontend_Templates class is available</div>';
        } else {
            echo '<div class="error">✗ EIE_Frontend_Templates class not found</div>';
        }
        
        // Check template files
        $template_files = array(
            'single-item_exchange.php' => 'Single Exchange Template',
            'archive-item_exchange.php' => 'Archive Exchange Template',
            'partials/exchange-card.php' => 'Exchange Card Partial'
        );
        
        $template_path = get_template_directory() . '/';
        $plugin_template_path = WP_PLUGIN_DIR . '/environmental-item-exchange/templates/';
        
        foreach ($template_files as $file => $name) {
            if (file_exists($plugin_template_path . $file)) {
                echo "<div class='success'>✓ $name found in plugin</div>";
            } else {
                echo "<div class='error'>✗ $name missing from plugin</div>";
            }
        }
        ?>
    </div>
    
    <div class="test-section">
        <h2>Exchange Posts Test</h2>
        
        <?php
        // Get exchange posts
        $exchanges = get_posts(array(
            'post_type' => 'item_exchange',
            'posts_per_page' => 5,
            'post_status' => 'publish'
        ));
        
        if ($exchanges) {
            echo '<div class="success">✓ Found ' . count($exchanges) . ' exchange posts</div>';
            
            foreach ($exchanges as $exchange) {
                echo '<div class="exchange-test">';
                echo '<h4>' . get_the_title($exchange) . '</h4>';
                echo '<p>' . wp_trim_words(get_the_content(null, false, $exchange), 20) . '</p>';
                echo '<small>ID: ' . $exchange->ID . ' | Date: ' . get_the_date('', $exchange) . '</small>';
                echo '<br><a href="' . get_permalink($exchange->ID) . '" class="button">View Exchange</a>';
                echo '</div>';
            }
        } else {
            echo '<div class="info">No exchange posts found.</div>';
            echo '<a href="?create_test_data=1" class="button">Create Test Data</a>';
        }
        ?>
    </div>
    
    <div class="test-section">
        <h2>JavaScript & CSS Test</h2>
        
        <div id="js-test-result" class="info">Testing JavaScript...</div>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var testDiv = document.getElementById('js-test-result');
            
            // Test if jQuery is loaded
            if (typeof jQuery !== 'undefined') {
                testDiv.innerHTML += '<br>✓ jQuery is loaded (version: ' + jQuery.fn.jquery + ')';
                testDiv.className = 'success';
            } else {
                testDiv.innerHTML += '<br>✗ jQuery not loaded';
                testDiv.className = 'error';
            }
            
            // Test AJAX URL
            if (typeof eie_ajax !== 'undefined') {
                testDiv.innerHTML += '<br>✓ EIE AJAX object available';
                testDiv.innerHTML += '<br>AJAX URL: ' + eie_ajax.ajax_url;
            } else {
                testDiv.innerHTML += '<br>⚠ EIE AJAX object not available';
            }
            
            // Test CSS
            var testElement = document.createElement('div');
            testElement.className = 'eie-test-element';
            testElement.style.display = 'none';
            document.body.appendChild(testElement);
            
            var styles = window.getComputedStyle(testElement);
            if (styles.display === 'none') {
                testDiv.innerHTML += '<br>✓ CSS can be applied';
            }
            
            document.body.removeChild(testElement);
        });
        </script>
    </div>
    
    <div class="test-section">
        <h2>Archive Page Test</h2>
        
        <?php
        $archive_url = get_post_type_archive_link('item_exchange');
        if ($archive_url) {
            echo '<div class="success">✓ Archive URL available</div>';
            echo '<a href="' . $archive_url . '" class="button">View All Exchanges</a>';
        } else {
            echo '<div class="error">✗ Archive URL not available</div>';
        }
        ?>
    </div>
    
    <div class="test-section">
        <h2>AJAX Functionality Test</h2>
        
        <button id="test-ajax" class="button">Test AJAX Search</button>
        <div id="ajax-result"></div>
        
        <script>
        document.getElementById('test-ajax').addEventListener('click', function() {
            var resultDiv = document.getElementById('ajax-result');
            resultDiv.innerHTML = 'Testing AJAX...';
            
            if (typeof jQuery !== 'undefined' && typeof eie_ajax !== 'undefined') {
                jQuery.ajax({
                    url: eie_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'eie_search_exchanges',
                        nonce: eie_ajax.nonce,
                        search_term: 'test'
                    },
                    success: function(response) {
                        resultDiv.innerHTML = '<div class="success">✓ AJAX call successful</div><pre>' + JSON.stringify(response, null, 2) + '</pre>';
                    },
                    error: function(xhr, status, error) {
                        resultDiv.innerHTML = '<div class="error">✗ AJAX call failed: ' + error + '</div>';
                    }
                });
            } else {
                resultDiv.innerHTML = '<div class="error">✗ AJAX prerequisites not available</div>';
            }
        });
        </script>
    </div>
    
    <div class="test-section">
        <h2>Quick Navigation</h2>
        <a href="wp-admin/edit.php?post_type=item_exchange" class="button">Admin: View Exchanges</a>
        <a href="wp-admin/post-new.php?post_type=item_exchange" class="button">Admin: Add Exchange</a>
        <a href="<?php echo get_post_type_archive_link('item_exchange'); ?>" class="button">View Exchange Archive</a>
        <a href="test-plugin-database.php" class="button">Database Test</a>
    </div>
    
</div>

<?php wp_footer(); ?>
</body>
</html>
