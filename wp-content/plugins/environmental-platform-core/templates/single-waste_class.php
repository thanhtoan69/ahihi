<?php
/**
 * Single Waste Classification Template
 *
 * @package Environmental_Platform_Core
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header(); ?>

<div class="ep-waste-container">
    <?php while (have_posts()) : the_post(); ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class('ep-waste-single'); ?>>
            
            <!-- Waste Classification Header -->
            <header class="ep-waste-header">
                <div class="ep-waste-hero">
                    <?php if (has_post_thumbnail()) : ?>
                        <div class="ep-waste-image">
                            <?php the_post_thumbnail('large'); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="ep-waste-info">
                        <h1 class="ep-waste-title"><?php the_title(); ?></h1>
                        
                        <div class="ep-waste-classification">
                            <?php
                            $waste_type = get_the_terms(get_the_ID(), 'waste_type');
                            if ($waste_type && !is_wp_error($waste_type)) :
                            ?>
                                <div class="ep-waste-type">
                                    <span class="ep-label"><?php _e('Type:', 'environmental-platform-core'); ?></span>
                                    <span class="ep-type-badge type-<?php echo esc_attr(strtolower($waste_type[0]->slug)); ?>">
                                        <?php echo esc_html($waste_type[0]->name); ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                            
                            <?php
                            $recyclability = get_the_terms(get_the_ID(), 'recyclability');
                            if ($recyclability && !is_wp_error($recyclability)) :
                            ?>
                                <div class="ep-recyclability">
                                    <span class="ep-label"><?php _e('Recyclability:', 'environmental-platform-core'); ?></span>
                                    <span class="ep-recyclable-badge <?php echo esc_attr('recyclable-' . strtolower($recyclability[0]->slug)); ?>">
                                        <?php echo esc_html($recyclability[0]->name); ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="ep-waste-meta">
                            <div class="ep-meta-item">
                                <i class="dashicons dashicons-calendar-alt"></i>
                                <span><?php echo get_the_date(); ?></span>
                            </div>
                            <div class="ep-meta-item">
                                <i class="dashicons dashicons-admin-users"></i>
                                <span><?php the_author(); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Waste Content -->
            <div class="ep-waste-content">
                <div class="ep-waste-main">
                    <!-- Description -->
                    <section class="ep-waste-description">
                        <h2><?php _e('Description', 'environmental-platform-core'); ?></h2>
                        <div class="ep-description-content">
                            <?php the_content(); ?>
                        </div>
                    </section>

                    <!-- Identification Guide -->
                    <?php
                    $identification_guide = get_post_meta(get_the_ID(), '_ep_identification_guide', true);
                    if ($identification_guide):
                    ?>
                        <section class="ep-identification">
                            <h2><?php _e('How to Identify', 'environmental-platform-core'); ?></h2>
                            <div class="ep-identification-content">
                                <?php echo wp_kses_post($identification_guide); ?>
                            </div>
                        </section>
                    <?php endif; ?>

                    <!-- Disposal Instructions -->
                    <?php
                    $disposal_instructions = get_post_meta(get_the_ID(), '_ep_disposal_instructions', true);
                    if ($disposal_instructions):
                    ?>
                        <section class="ep-disposal">
                            <h2><?php _e('Proper Disposal Method', 'environmental-platform-core'); ?></h2>
                            <div class="ep-disposal-content">
                                <?php echo wp_kses_post($disposal_instructions); ?>
                            </div>
                            
                            <!-- Disposal Steps -->
                            <?php
                            $disposal_steps = get_post_meta(get_the_ID(), '_ep_disposal_steps', true);
                            if ($disposal_steps && is_array($disposal_steps)):
                            ?>
                                <div class="ep-disposal-steps">
                                    <h3><?php _e('Step-by-Step Guide', 'environmental-platform-core'); ?></h3>
                                    <ol class="ep-steps-list">
                                        <?php foreach ($disposal_steps as $step): ?>
                                            <li class="ep-step-item">
                                                <div class="ep-step-content">
                                                    <h4><?php echo esc_html($step['title']); ?></h4>
                                                    <p><?php echo esc_html($step['description']); ?></p>
                                                    <?php if (!empty($step['icon'])): ?>
                                                        <div class="ep-step-icon">
                                                            <i class="dashicons dashicons-<?php echo esc_attr($step['icon']); ?>"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </li>
                                        <?php endforeach; ?>
                                    </ol>
                                </div>
                            <?php endif; ?>
                        </section>
                    <?php endif; ?>

                    <!-- Environmental Impact -->
                    <?php
                    $environmental_impact = get_post_meta(get_the_ID(), '_ep_environmental_impact', true);
                    if ($environmental_impact):
                    ?>
                        <section class="ep-environmental-impact">
                            <h2><?php _e('Environmental Impact', 'environmental-platform-core'); ?></h2>
                            <div class="ep-impact-content">
                                <?php echo wp_kses_post($environmental_impact); ?>
                            </div>
                            
                            <!-- Impact Statistics -->
                            <div class="ep-impact-stats">
                                <?php
                                $decomposition_time = get_post_meta(get_the_ID(), '_ep_decomposition_time', true);
                                $carbon_footprint = get_post_meta(get_the_ID(), '_ep_carbon_footprint', true);
                                $recycling_rate = get_post_meta(get_the_ID(), '_ep_recycling_rate', true);
                                ?>
                                
                                <?php if ($decomposition_time): ?>
                                    <div class="ep-stat-item">
                                        <div class="ep-stat-icon">
                                            <i class="dashicons dashicons-clock"></i>
                                        </div>
                                        <div class="ep-stat-content">
                                            <div class="ep-stat-number"><?php echo esc_html($decomposition_time); ?></div>
                                            <div class="ep-stat-label"><?php _e('Decomposition Time', 'environmental-platform-core'); ?></div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($carbon_footprint): ?>
                                    <div class="ep-stat-item">
                                        <div class="ep-stat-icon">
                                            <i class="dashicons dashicons-cloud"></i>
                                        </div>
                                        <div class="ep-stat-content">
                                            <div class="ep-stat-number"><?php echo esc_html($carbon_footprint); ?> kg</div>
                                            <div class="ep-stat-label"><?php _e('CO₂ per unit', 'environmental-platform-core'); ?></div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($recycling_rate): ?>
                                    <div class="ep-stat-item">
                                        <div class="ep-stat-icon">
                                            <i class="dashicons dashicons-update"></i>
                                        </div>
                                        <div class="ep-stat-content">
                                            <div class="ep-stat-number"><?php echo esc_html($recycling_rate); ?>%</div>
                                            <div class="ep-stat-label"><?php _e('Recycling Rate', 'environmental-platform-core'); ?></div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </section>
                    <?php endif; ?>

                    <!-- Alternatives and Suggestions -->
                    <?php
                    $alternatives = get_post_meta(get_the_ID(), '_ep_alternatives', true);
                    if ($alternatives):
                    ?>
                        <section class="ep-alternatives">
                            <h2><?php _e('Eco-Friendly Alternatives', 'environmental-platform-core'); ?></h2>
                            <div class="ep-alternatives-content">
                                <?php echo wp_kses_post($alternatives); ?>
                            </div>
                        </section>
                    <?php endif; ?>

                    <!-- Common Misconceptions -->
                    <?php
                    $misconceptions = get_post_meta(get_the_ID(), '_ep_misconceptions', true);
                    if ($misconceptions && is_array($misconceptions)):
                    ?>
                        <section class="ep-misconceptions">
                            <h2><?php _e('Common Misconceptions', 'environmental-platform-core'); ?></h2>
                            <div class="ep-misconceptions-list">
                                <?php foreach ($misconceptions as $misconception): ?>
                                    <div class="ep-misconception-item">
                                        <div class="ep-myth">
                                            <h4><?php _e('Myth:', 'environmental-platform-core'); ?></h4>
                                            <p><?php echo esc_html($misconception['myth']); ?></p>
                                        </div>
                                        <div class="ep-fact">
                                            <h4><?php _e('Fact:', 'environmental-platform-core'); ?></h4>
                                            <p><?php echo esc_html($misconception['fact']); ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>

                    <!-- Quick Reference Card -->
                    <section class="ep-quick-reference">
                        <h2><?php _e('Quick Reference Card', 'environmental-platform-core'); ?></h2>
                        <div class="ep-reference-card">
                            <div class="ep-reference-item">
                                <strong><?php _e('Can be recycled:', 'environmental-platform-core'); ?></strong>
                                <span class="ep-reference-value">
                                    <?php
                                    $can_recycle = get_post_meta(get_the_ID(), '_ep_can_recycle', true);
                                    echo $can_recycle ? __('Yes', 'environmental-platform-core') : __('No', 'environmental-platform-core');
                                    ?>
                                </span>
                            </div>
                            
                            <div class="ep-reference-item">
                                <strong><?php _e('Special handling:', 'environmental-platform-core'); ?></strong>
                                <span class="ep-reference-value">
                                    <?php
                                    $special_handling = get_post_meta(get_the_ID(), '_ep_special_handling', true);
                                    echo $special_handling ? __('Required', 'environmental-platform-core') : __('Not required', 'environmental-platform-core');
                                    ?>
                                </span>
                            </div>
                            
                            <div class="ep-reference-item">
                                <strong><?php _e('Hazardous:', 'environmental-platform-core'); ?></strong>
                                <span class="ep-reference-value">
                                    <?php
                                    $hazardous = get_post_meta(get_the_ID(), '_ep_hazardous', true);
                                    echo $hazardous ? __('Yes', 'environmental-platform-core') : __('No', 'environmental-platform-core');
                                    ?>
                                </span>
                            </div>
                            
                            <?php
                            $disposal_bin = get_post_meta(get_the_ID(), '_ep_disposal_bin', true);
                            if ($disposal_bin):
                            ?>
                                <div class="ep-reference-item">
                                    <strong><?php _e('Disposal bin:', 'environmental-platform-core'); ?></strong>
                                    <span class="ep-reference-value ep-bin-type bin-<?php echo esc_attr(strtolower($disposal_bin)); ?>">
                                        <?php echo esc_html($disposal_bin); ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>
                </div>

                <!-- Sidebar -->
                <aside class="ep-waste-sidebar">
                    <!-- AI Waste Scanner -->
                    <div class="ep-sidebar-widget ep-ai-scanner">
                        <h3><?php _e('AI Waste Scanner', 'environmental-platform-core'); ?></h3>
                        <p><?php _e('Use our AI to identify waste types from photos', 'environmental-platform-core'); ?></p>
                        <button class="ep-scanner-btn" onclick="openWasteScanner()">
                            <i class="dashicons dashicons-camera"></i>
                            <?php _e('Scan Waste Item', 'environmental-platform-core'); ?>
                        </button>
                    </div>

                    <!-- Related Waste Types -->
                    <?php
                    $related_waste = new WP_Query(array(
                        'post_type' => 'waste_class',
                        'posts_per_page' => 5,
                        'post__not_in' => array(get_the_ID()),
                        'tax_query' => array(
                            array(
                                'taxonomy' => 'waste_type',
                                'field' => 'term_id',
                                'terms' => wp_get_post_terms(get_the_ID(), 'waste_type', array('fields' => 'ids'))
                            )
                        )
                    ));
                    ?>
                    
                    <?php if ($related_waste->have_posts()) : ?>
                        <div class="ep-sidebar-widget ep-related-waste">
                            <h3><?php _e('Similar Waste Types', 'environmental-platform-core'); ?></h3>
                            <div class="ep-related-list">
                                <?php while ($related_waste->have_posts()) : $related_waste->the_post(); ?>
                                    <div class="ep-related-item">
                                        <?php if (has_post_thumbnail()) : ?>
                                            <div class="ep-related-thumbnail">
                                                <a href="<?php the_permalink(); ?>">
                                                    <?php the_post_thumbnail('thumbnail'); ?>
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                        <div class="ep-related-content">
                                            <h4><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h4>
                                            <p><?php echo wp_trim_words(get_the_excerpt(), 10); ?></p>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                        <?php wp_reset_postdata(); ?>
                    <?php endif; ?>

                    <!-- Disposal Locations -->
                    <?php
                    $disposal_locations = get_post_meta(get_the_ID(), '_ep_disposal_locations', true);
                    if ($disposal_locations && is_array($disposal_locations)):
                    ?>
                        <div class="ep-sidebar-widget ep-disposal-locations">
                            <h3><?php _e('Disposal Locations', 'environmental-platform-core'); ?></h3>
                            <div class="ep-locations-list">
                                <?php foreach ($disposal_locations as $location): ?>
                                    <div class="ep-location-item">
                                        <h4><?php echo esc_html($location['name']); ?></h4>
                                        <p class="ep-location-address"><?php echo esc_html($location['address']); ?></p>
                                        <?php if (!empty($location['hours'])): ?>
                                            <p class="ep-location-hours">
                                                <strong><?php _e('Hours:', 'environmental-platform-core'); ?></strong>
                                                <?php echo esc_html($location['hours']); ?>
                                            </p>
                                        <?php endif; ?>
                                        <?php if (!empty($location['phone'])): ?>
                                            <p class="ep-location-phone">
                                                <strong><?php _e('Phone:', 'environmental-platform-core'); ?></strong>
                                                <a href="tel:<?php echo esc_attr($location['phone']); ?>">
                                                    <?php echo esc_html($location['phone']); ?>
                                                </a>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Tips Widget -->
                    <div class="ep-sidebar-widget ep-tips">
                        <h3><?php _e('Pro Tips', 'environmental-platform-core'); ?></h3>
                        <ul class="ep-tips-list">
                            <li><?php _e('Always clean containers before recycling', 'environmental-platform-core'); ?></li>
                            <li><?php _e('Remove caps and lids when specified', 'environmental-platform-core'); ?></li>
                            <li><?php _e('Check local recycling guidelines', 'environmental-platform-core'); ?></li>
                            <li><?php _e('When in doubt, ask your local facility', 'environmental-platform-core'); ?></li>
                        </ul>
                    </div>

                    <!-- Waste Statistics -->
                    <div class="ep-sidebar-widget ep-waste-stats">
                        <h3><?php _e('Global Statistics', 'environmental-platform-core'); ?></h3>
                        <div class="ep-stats-grid">
                            <div class="ep-stat-box">
                                <div class="ep-stat-number">2.01B</div>
                                <div class="ep-stat-label"><?php _e('Tons of waste/year', 'environmental-platform-core'); ?></div>
                            </div>
                            <div class="ep-stat-box">
                                <div class="ep-stat-number">36%</div>
                                <div class="ep-stat-label"><?php _e('Recycling rate', 'environmental-platform-core'); ?></div>
                            </div>
                        </div>
                    </div>
                </aside>
            </div>

        </article>
    <?php endwhile; ?>
</div>

<style>
.ep-waste-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.ep-waste-single {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow: hidden;
}

.ep-waste-hero {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 30px;
    padding: 40px;
    background: linear-gradient(135deg, #6c757d, #495057);
    color: white;
}

.ep-waste-image img {
    width: 100%;
    max-width: 300px;
    height: auto;
    border-radius: 8px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.3);
}

.ep-waste-title {
    font-size: 2.5em;
    margin: 0 0 20px 0;
    font-weight: bold;
}

.ep-waste-classification {
    display: flex;
    flex-direction: column;
    gap: 15px;
    margin-bottom: 20px;
}

.ep-waste-classification > div {
    display: flex;
    align-items: center;
    gap: 10px;
}

.ep-label {
    font-weight: 600;
    opacity: 0.9;
}

.ep-type-badge,
.ep-recyclable-badge {
    padding: 5px 15px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: bold;
    text-transform: uppercase;
}

/* Waste type colors */
.type-organic { background: #28a745; }
.type-plastic { background: #007bff; }
.type-metal { background: #6c757d; }
.type-glass { background: #17a2b8; }
.type-paper { background: #ffc107; color: #000; }
.type-electronic { background: #dc3545; }
.type-hazardous { background: #6f42c1; }

/* Recyclability colors */
.recyclable-yes { background: #28a745; }
.recyclable-no { background: #dc3545; }
.recyclable-limited { background: #ffc107; color: #000; }
.recyclable-special { background: #17a2b8; }

.ep-waste-meta {
    display: flex;
    gap: 20px;
    opacity: 0.9;
}

.ep-meta-item {
    display: flex;
    align-items: center;
    gap: 5px;
}

.ep-waste-content {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 40px;
    padding: 40px;
}

.ep-waste-main section {
    margin-bottom: 40px;
}

.ep-waste-main h2 {
    color: #495057;
    border-bottom: 2px solid #6c757d;
    padding-bottom: 10px;
    margin-bottom: 20px;
}

.ep-disposal-steps {
    margin-top: 30px;
}

.ep-steps-list {
    counter-reset: step-counter;
    list-style: none;
    padding: 0;
}

.ep-step-item {
    counter-increment: step-counter;
    position: relative;
    padding: 20px 20px 20px 60px;
    margin-bottom: 20px;
    background: #f8f9fa;
    border-radius: 8px;
    border-left: 4px solid #6c757d;
}

.ep-step-item::before {
    content: counter(step-counter);
    position: absolute;
    left: 20px;
    top: 20px;
    width: 30px;
    height: 30px;
    background: #6c757d;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}

.ep-step-content h4 {
    margin: 0 0 10px 0;
    color: #495057;
}

.ep-step-icon {
    margin-top: 10px;
    color: #6c757d;
}

.ep-impact-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-top: 30px;
}

.ep-stat-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
    border-left: 4px solid #6c757d;
}

.ep-stat-icon {
    font-size: 24px;
    color: #6c757d;
}

.ep-stat-number {
    font-size: 1.5em;
    font-weight: bold;
    color: #495057;
    margin-bottom: 5px;
}

.ep-stat-label {
    color: #6c757d;
    font-size: 0.9em;
}

.ep-misconceptions-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.ep-misconception-item {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
}

.ep-myth {
    background: #f8d7da;
    padding: 15px;
    border-radius: 4px;
    border-left: 4px solid #dc3545;
}

.ep-fact {
    background: #d4edda;
    padding: 15px;
    border-radius: 4px;
    border-left: 4px solid #28a745;
}

.ep-myth h4,
.ep-fact h4 {
    margin: 0 0 10px 0;
    font-size: 1em;
}

.ep-myth h4 {
    color: #721c24;
}

.ep-fact h4 {
    color: #155724;
}

.ep-reference-card {
    background: #f8f9fa;
    padding: 25px;
    border-radius: 8px;
    border: 2px solid #dee2e6;
}

.ep-reference-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid #dee2e6;
}

.ep-reference-item:last-child {
    border-bottom: none;
}

.ep-reference-value {
    font-weight: 600;
}

.ep-bin-type {
    padding: 4px 12px;
    border-radius: 15px;
    font-size: 0.9em;
    text-transform: uppercase;
}

.bin-general { background: #6c757d; color: white; }
.bin-recycling { background: #007bff; color: white; }
.bin-organic { background: #28a745; color: white; }
.bin-hazardous { background: #dc3545; color: white; }

.ep-waste-sidebar {
    display: flex;
    flex-direction: column;
    gap: 30px;
}

.ep-sidebar-widget {
    background: #f8f9fa;
    padding: 25px;
    border-radius: 8px;
    border-left: 4px solid #6c757d;
}

.ep-sidebar-widget h3 {
    margin: 0 0 20px 0;
    color: #495057;
    font-size: 1.2em;
}

.ep-scanner-btn {
    width: 100%;
    background: linear-gradient(45deg, #007bff, #0056b3);
    color: white;
    border: none;
    padding: 15px;
    border-radius: 8px;
    font-weight: bold;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    transition: all 0.3s ease;
}

.ep-scanner-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 123, 255, 0.4);
}

.ep-related-item {
    display: flex;
    gap: 15px;
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 1px solid #dee2e6;
}

.ep-related-thumbnail img {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 4px;
}

.ep-related-content h4 {
    margin: 0 0 5px 0;
    font-size: 0.9em;
}

.ep-related-content h4 a {
    color: #495057;
    text-decoration: none;
}

.ep-related-content h4 a:hover {
    color: #007bff;
}

.ep-related-content p {
    margin: 0;
    color: #6c757d;
    font-size: 0.8em;
}

.ep-location-item {
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 1px solid #dee2e6;
}

.ep-location-item h4 {
    margin: 0 0 5px 0;
    color: #495057;
}

.ep-location-address,
.ep-location-hours,
.ep-location-phone {
    margin: 5px 0;
    font-size: 0.9em;
    color: #6c757d;
}

.ep-location-phone a {
    color: #007bff;
    text-decoration: none;
}

.ep-tips-list {
    list-style: none;
    padding: 0;
}

.ep-tips-list li {
    padding: 10px 0;
    border-bottom: 1px solid #dee2e6;
    position: relative;
    padding-left: 25px;
}

.ep-tips-list li::before {
    content: "💡";
    position: absolute;
    left: 0;
    top: 10px;
}

.ep-stats-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
}

.ep-stat-box {
    text-align: center;
    background: white;
    padding: 20px;
    border-radius: 8px;
}

.ep-stat-box .ep-stat-number {
    font-size: 1.8em;
    font-weight: bold;
    color: #6c757d;
    margin-bottom: 5px;
}

.ep-stat-box .ep-stat-label {
    color: #6c757d;
    font-size: 0.8em;
}

/* Responsive Design */
@media (max-width: 768px) {
    .ep-waste-container {
        padding: 10px;
    }
    
    .ep-waste-hero {
        grid-template-columns: 1fr;
        text-align: center;
    }
    
    .ep-waste-content {
        grid-template-columns: 1fr;
        padding: 20px;
    }
    
    .ep-waste-title {
        font-size: 2em;
    }
    
    .ep-misconception-item {
        grid-template-columns: 1fr;
    }
    
    .ep-impact-stats {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 480px) {
    .ep-waste-classification {
        align-items: flex-start;
    }
    
    .ep-waste-classification > div {
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
    }
    
    .ep-reference-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
    }
}
</style>

<script>
function openWasteScanner() {
    // This would integrate with a camera/AI scanning feature
    alert('AI Waste Scanner feature coming soon! This will allow you to take a photo and identify waste types automatically.');
}
</script>

<?php get_footer(); ?>
