<?php
/**
 * Custom template tags for Environmental Platform theme
 *
 * @package Environmental_Platform
 * @since 1.0.0
 */

if (!function_exists('environmental_platform_posted_on')) :
    /**
     * Prints HTML with meta information for the current post-date/time.
     */
    function environmental_platform_posted_on() {
        $time_string = '<time class="entry-date published updated" datetime="%1$s">%2$s</time>';
        if (get_the_time('U') !== get_the_modified_time('U')) {
            $time_string = '<time class="entry-date published" datetime="%1$s">%2$s</time><time class="updated" datetime="%3$s">%4$s</time>';
        }

        $time_string = sprintf($time_string,
            esc_attr(get_the_date(DATE_W3C)),
            esc_html(get_the_date()),
            esc_attr(get_the_modified_date(DATE_W3C)),
            esc_html(get_the_modified_date())
        );

        $posted_on = sprintf(
            /* translators: %s: post date. */
            esc_html_x('Posted on %s', 'post date', 'environmental-platform'),
            '<a href="' . esc_url(get_permalink()) . '" rel="bookmark">' . $time_string . '</a>'
        );

        echo '<span class="posted-on">' . $posted_on . '</span>'; // WPCS: XSS OK.
    }
endif;

if (!function_exists('environmental_platform_posted_by')) :
    /**
     * Prints HTML with meta information for the current author.
     */
    function environmental_platform_posted_by() {
        $byline = sprintf(
            /* translators: %s: post author. */
            esc_html_x('by %s', 'post author', 'environmental-platform'),
            '<span class="author vcard"><a class="url fn n" href="' . esc_url(get_author_posts_url(get_the_author_meta('ID'))) . '">' . esc_html(get_the_author()) . '</a></span>'
        );

        echo '<span class="byline"> ' . $byline . '</span>'; // WPCS: XSS OK.
    }
endif;

if (!function_exists('environmental_platform_entry_footer')) :
    /**
     * Prints HTML with meta information for the categories, tags and comments.
     */
    function environmental_platform_entry_footer() {
        // Hide category and tag text for pages.
        if ('post' === get_post_type()) {
            /* translators: used between list items, there is a space after the comma */
            $categories_list = get_the_category_list(esc_html__(', ', 'environmental-platform'));
            if ($categories_list) {
                /* translators: 1: list of categories. */
                printf('<span class="cat-links">' . esc_html__('Posted in %1$s', 'environmental-platform') . '</span>', $categories_list); // WPCS: XSS OK.
            }

            /* translators: used between list items, there is a space after the comma */
            $tags_list = get_the_tag_list('', esc_html_x(', ', 'list item separator', 'environmental-platform'));
            if ($tags_list) {
                /* translators: 1: list of tags. */
                printf('<span class="tags-links">' . esc_html__('Tagged %1$s', 'environmental-platform') . '</span>', $tags_list); // WPCS: XSS OK.
            }
        }

        if (!is_single() && !post_password_required() && (comments_open() || get_comments_number())) {
            echo '<span class="comments-link">';
            comments_popup_link(
                sprintf(
                    wp_kses(
                        /* translators: %s: post title */
                        __('Leave a Comment<span class="screen-reader-text"> on %s</span>', 'environmental-platform'),
                        array(
                            'span' => array(
                                'class' => array(),
                            ),
                        )
                    ),
                    get_the_title()
                )
            );
            echo '</span>';
        }

        edit_post_link(
            sprintf(
                wp_kses(
                    /* translators: %s: Name of current post. Only visible to screen readers */
                    __('Edit <span class="screen-reader-text">%s</span>', 'environmental-platform'),
                    array(
                        'span' => array(
                            'class' => array(),
                        ),
                    )
                ),
                get_the_title()
            ),
            '<span class="edit-link">',
            '</span>'
        );
    }
endif;

if (!function_exists('environmental_platform_post_thumbnail')) :
    /**
     * Displays an optional post thumbnail.
     *
     * Wraps the post thumbnail in an anchor element on index views, or a div
     * element when on single views.
     */
    function environmental_platform_post_thumbnail() {
        if (post_password_required() || is_attachment() || !has_post_thumbnail()) {
            return;
        }

        if (is_singular()) :
            ?>
            <div class="post-thumbnail">
                <?php the_post_thumbnail(); ?>
            </div><!-- .post-thumbnail -->
        <?php else : ?>
            <a class="post-thumbnail" href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1">
                <?php
                the_post_thumbnail('post-thumbnail', array(
                    'alt' => the_title_attribute(array(
                        'echo' => false,
                    )),
                ));
                ?>
            </a>
        <?php
        endif; // End is_singular().
    }
endif;

if (!function_exists('environmental_platform_get_user_environmental_level')) :
    /**
     * Get user's environmental level
     */
    function environmental_platform_get_user_environmental_level($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        if (!$user_id) {
            return array(
                'level' => 0,
                'title' => __('Newcomer', 'environmental-platform'),
                'progress' => 0
            );
        }

        global $wpdb;
        $user = get_userdata($user_id);
        
        if (!$user) {
            return array(
                'level' => 0,
                'title' => __('Newcomer', 'environmental-platform'),
                'progress' => 0
            );
        }

        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT level, green_points FROM users WHERE email = %s",
            $user->user_email
        ));

        if ($stats) {
            $levels = array(
                1 => __('Eco Beginner', 'environmental-platform'),
                2 => __('Green Learner', 'environmental-platform'),
                3 => __('Eco Enthusiast', 'environmental-platform'),
                4 => __('Environmental Advocate', 'environmental-platform'),
                5 => __('Green Champion', 'environmental-platform'),
                6 => __('Eco Warrior', 'environmental-platform'),
                7 => __('Sustainability Expert', 'environmental-platform'),
                8 => __('Environmental Leader', 'environmental-platform'),
                9 => __('Planet Guardian', 'environmental-platform'),
                10 => __('Eco Master', 'environmental-platform')
            );

            $current_level = max(1, (int)$stats->level);
            $points = (int)$stats->green_points;
            
            // Calculate progress to next level
            $points_for_next_level = $current_level * 1000; // 1000 points per level
            $points_current_level = ($current_level - 1) * 1000;
            $progress = min(100, (($points - $points_current_level) / ($points_for_next_level - $points_current_level)) * 100);

            return array(
                'level' => $current_level,
                'title' => isset($levels[$current_level]) ? $levels[$current_level] : __('Eco Master', 'environmental-platform'),
                'progress' => round($progress, 1),
                'points' => $points,
                'points_needed' => max(0, $points_for_next_level - $points)
            );
        }

        return array(
            'level' => 1,
            'title' => __('Eco Beginner', 'environmental-platform'),
            'progress' => 0,
            'points' => 0,
            'points_needed' => 1000
        );
    }
endif;

if (!function_exists('environmental_platform_get_environmental_tip_of_day')) :
    /**
     * Get environmental tip of the day
     */
    function environmental_platform_get_environmental_tip_of_day() {
        $tips = array(
            __('Turn off lights when leaving a room to save energy and reduce your carbon footprint.', 'environmental-platform'),
            __('Use a reusable water bottle instead of single-use plastic bottles.', 'environmental-platform'),
            __('Walk, bike, or use public transportation instead of driving when possible.', 'environmental-platform'),
            __('Unplug electronic devices when not in use to prevent phantom energy consumption.', 'environmental-platform'),
            __('Choose products with minimal packaging to reduce waste.', 'environmental-platform'),
            __('Plant trees or support reforestation programs to offset carbon emissions.', 'environmental-platform'),
            __('Use cold water for washing clothes to save energy.', 'environmental-platform'),
            __('Compost organic waste to create nutrient-rich soil and reduce landfill waste.', 'environmental-platform'),
            __('Choose renewable energy sources when available.', 'environmental-platform'),
            __('Reduce meat consumption to lower your environmental impact.', 'environmental-platform'),
            __('Fix leaky faucets and pipes to conserve water.', 'environmental-platform'),
            __('Use LED bulbs which last longer and use less energy.', 'environmental-platform'),
            __('Buy local and seasonal produce to reduce transportation emissions.', 'environmental-platform'),
            __('Recycle properly according to your local guidelines.', 'environmental-platform'),
            __('Use a programmable thermostat to optimize heating and cooling.', 'environmental-platform'),
        );

        $day_of_year = date('z');
        $tip_index = $day_of_year % count($tips);
        
        return $tips[$tip_index];
    }
endif;

if (!function_exists('environmental_platform_format_carbon_footprint')) :
    /**
     * Format carbon footprint display
     */
    function environmental_platform_format_carbon_footprint($kg) {
        if ($kg < 1000) {
            return number_format($kg, 1) . ' kg CO₂';
        } else {
            return number_format($kg / 1000, 2) . ' tonnes CO₂';
        }
    }
endif;

if (!function_exists('environmental_platform_get_reading_time')) :
    /**
     * Calculate reading time for a post
     */
    function environmental_platform_get_reading_time($post_id = null) {
        if (!$post_id) {
            $post_id = get_the_ID();
        }

        $content = get_post_field('post_content', $post_id);
        $word_count = str_word_count(strip_tags($content));
        $reading_time = ceil($word_count / 200); // Assuming 200 words per minute

        if ($reading_time <= 1) {
            return __('1 min read', 'environmental-platform');
        } else {
            return sprintf(__('%d min read', 'environmental-platform'), $reading_time);
        }
    }
endif;

if (!function_exists('environmental_platform_display_environmental_score')) :
    /**
     * Display environmental score badge
     */
    function environmental_platform_display_environmental_score($score, $show_label = true) {
        if (!$score) {
            return '';
        }

        $score = (float)$score;
        $class = '';
        $label = '';

        if ($score >= 8) {
            $class = 'score-excellent';
            $label = __('Excellent Impact', 'environmental-platform');
        } elseif ($score >= 6) {
            $class = 'score-good';
            $label = __('Good Impact', 'environmental-platform');
        } elseif ($score >= 4) {
            $class = 'score-moderate';
            $label = __('Moderate Impact', 'environmental-platform');
        } else {
            $class = 'score-low';
            $label = __('Low Impact', 'environmental-platform');
        }

        $output = '<div class="environmental-score-badge ' . $class . '">';
        $output .= '<span class="score-value">' . number_format($score, 1) . '</span>';
        if ($show_label) {
            $output .= '<span class="score-label">' . $label . '</span>';
        }
        $output .= '</div>';

        return $output;
    }
endif;

if (!function_exists('wp_body_open')) :
    /**
     * Shim for sites older than 5.2.
     *
     * @link https://core.trac.wordpress.org/ticket/12563
     */
    function wp_body_open() {
        do_action('wp_body_open');
    }
endif;
