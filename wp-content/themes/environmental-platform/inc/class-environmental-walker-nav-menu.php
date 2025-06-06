<?php
/**
 * Custom Navigation Walker for Environmental Platform Theme
 *
 * @package Environmental_Platform
 */

if (!class_exists('Environmental_Walker_Nav_Menu')) {
    
    class Environmental_Walker_Nav_Menu extends Walker_Nav_Menu {
        
        /**
         * Starts the list before the elements are added.
         *
         * @param string   $output Used to append additional content (passed by reference).
         * @param int      $depth  Depth of menu item. Used for padding.
         * @param stdClass $args   An object of wp_nav_menu() arguments.
         */
        public function start_lvl(&$output, $depth = 0, $args = null) {
            if (isset($args->item_spacing) && 'discard' === $args->item_spacing) {
                $t = '';
                $n = '';
            } else {
                $t = "\t";
                $n = "\n";
            }
            $indent = str_repeat($t, $depth);
            
            // Add environmental-themed dropdown styling
            $dropdown_class = 'dropdown-menu';
            if ($depth > 0) {
                $dropdown_class .= ' submenu';
            }
            
            $output .= "{$n}{$indent}<ul class=\"{$dropdown_class}\">{$n}";
        }

        /**
         * Ends the list after the elements are added.
         *
         * @param string   $output Used to append additional content (passed by reference).
         * @param int      $depth  Depth of menu item. Used for padding.
         * @param stdClass $args   An object of wp_nav_menu() arguments.
         */
        public function end_lvl(&$output, $depth = 0, $args = null) {
            if (isset($args->item_spacing) && 'discard' === $args->item_spacing) {
                $t = '';
                $n = '';
            } else {
                $t = "\t";
                $n = "\n";
            }
            $indent = str_repeat($t, $depth);
            $output .= "$indent</ul>{$n}";
        }

        /**
         * Starts the element output.
         *
         * @param string   $output Used to append additional content (passed by reference).
         * @param WP_Post  $item   Menu item data object.
         * @param int      $depth  Depth of menu item. Used for padding.
         * @param stdClass $args   An object of wp_nav_menu() arguments.
         * @param int      $id     Current item ID.
         */
        public function start_el(&$output, $item, $depth = 0, $args = null, $id = 0) {
            if (isset($args->item_spacing) && 'discard' === $args->item_spacing) {
                $t = '';
                $n = '';
            } else {
                $t = "\t";
                $n = "\n";
            }
            $indent = ($depth) ? str_repeat($t, $depth) : '';

            $classes = empty($item->classes) ? array() : (array) $item->classes;
            $classes[] = 'menu-item-' . $item->ID;

            // Add environmental-specific classes
            $environmental_classes = array();
            
            // Check for environmental keywords in menu item title or URL
            $item_title = strtolower($item->title);
            $item_url = strtolower($item->url);
            
            if (strpos($item_title, 'eco') !== false || strpos($item_title, 'green') !== false || 
                strpos($item_title, 'environment') !== false || strpos($item_title, 'sustainable') !== false) {
                $environmental_classes[] = 'eco-menu-item';
            }
            
            if (strpos($item_title, 'tip') !== false || strpos($item_title, 'guide') !== false) {
                $environmental_classes[] = 'tips-menu-item';
            }
            
            if (strpos($item_title, 'challenge') !== false || strpos($item_title, 'contest') !== false) {
                $environmental_classes[] = 'challenge-menu-item';
            }
            
            if (strpos($item_title, 'carbon') !== false || strpos($item_title, 'calculator') !== false) {
                $environmental_classes[] = 'calculator-menu-item';
            }

            // Add dropdown classes if item has children
            if (in_array('menu-item-has-children', $classes)) {
                $environmental_classes[] = 'has-dropdown';
                if ($depth === 0) {
                    $environmental_classes[] = 'dropdown-toggle';
                }
            }

            // Add active/current classes
            if (in_array('current-menu-item', $classes) || in_array('current-menu-parent', $classes)) {
                $environmental_classes[] = 'active';
            }

            $classes = array_merge($classes, $environmental_classes);

            /**
             * Filters the arguments for a single nav menu item.
             *
             * @param stdClass $args  An object of wp_nav_menu() arguments.
             * @param WP_Post  $item  Menu item data object.
             * @param int      $depth Depth of menu item. Used for padding.
             */
            $args = apply_filters('nav_menu_item_args', $args, $item, $depth);

            /**
             * Filters the CSS classes applied to a menu item's list item element.
             *
             * @param string[] $classes Array of the CSS classes that are applied to the menu item's `<li>` element.
             * @param WP_Post  $item    The current menu item.
             * @param stdClass $args    An object of wp_nav_menu() arguments.
             * @param int      $depth   Depth of menu item. Used for padding.
             */
            $class_names = join(' ', apply_filters('nav_menu_css_class', array_filter($classes), $item, $args, $depth));
            $class_names = $class_names ? ' class="' . esc_attr($class_names) . '"' : '';

            /**
             * Filters the ID applied to a menu item's list item element.
             *
             * @param string   $menu_id The ID that is applied to the menu item's `<li>` element.
             * @param WP_Post  $item    The current menu item.
             * @param stdClass $args    An object of wp_nav_menu() arguments.
             * @param int      $depth   Depth of menu item. Used for padding.
             */
            $id = apply_filters('nav_menu_item_id', 'menu-item-' . $item->ID, $item, $args, $depth);
            $id = $id ? ' id="' . esc_attr($id) . '"' : '';

            $output .= $indent . '<li' . $id . $class_names . '>';

            $attributes = !empty($item->attr_title) ? ' title="' . esc_attr($item->attr_title) . '"' : '';
            $attributes .= !empty($item->target) ? ' target="' . esc_attr($item->target) . '"' : '';
            $attributes .= !empty($item->xfn) ? ' rel="' . esc_attr($item->xfn) . '"' : '';
            $attributes .= !empty($item->url) ? ' href="' . esc_attr($item->url) . '"' : '';

            // Add environmental icons based on menu item content
            $menu_icon = '';
            if (strpos($item_title, 'home') !== false) {
                $menu_icon = '<i class="fas fa-home"></i> ';
            } elseif (strpos($item_title, 'tip') !== false || strpos($item_title, 'guide') !== false) {
                $menu_icon = '<i class="fas fa-leaf"></i> ';
            } elseif (strpos($item_title, 'challenge') !== false) {
                $menu_icon = '<i class="fas fa-trophy"></i> ';
            } elseif (strpos($item_title, 'calculator') !== false || strpos($item_title, 'carbon') !== false) {
                $menu_icon = '<i class="fas fa-calculator"></i> ';
            } elseif (strpos($item_title, 'blog') !== false || strpos($item_title, 'news') !== false) {
                $menu_icon = '<i class="fas fa-newspaper"></i> ';
            } elseif (strpos($item_title, 'contact') !== false) {
                $menu_icon = '<i class="fas fa-envelope"></i> ';
            } elseif (strpos($item_title, 'about') !== false) {
                $menu_icon = '<i class="fas fa-info-circle"></i> ';
            } elseif (strpos($item_title, 'donate') !== false || strpos($item_title, 'support') !== false) {
                $menu_icon = '<i class="fas fa-heart"></i> ';
            } elseif (strpos($item_title, 'shop') !== false || strpos($item_title, 'store') !== false) {
                $menu_icon = '<i class="fas fa-shopping-bag"></i> ';
            } elseif (strpos($item_title, 'profile') !== false || strpos($item_title, 'account') !== false) {
                $menu_icon = '<i class="fas fa-user"></i> ';
            }

            // Add dropdown arrow for parent items
            $dropdown_arrow = '';
            if (in_array('menu-item-has-children', $classes)) {
                $dropdown_arrow = ' <i class="fas fa-chevron-down dropdown-arrow"></i>';
            }

            $item_output = isset($args->before) ? $args->before : '';
            $item_output .= '<a' . $attributes . '>';
            $item_output .= $menu_icon;
            $item_output .= (isset($args->link_before) ? $args->link_before : '') . apply_filters('the_title', $item->title, $item->ID) . (isset($args->link_after) ? $args->link_after : '');
            $item_output .= $dropdown_arrow;
            $item_output .= '</a>';
            $item_output .= isset($args->after) ? $args->after : '';

            /**
             * Filters a menu item's starting output.
             *
             * @param string   $item_output The menu item's starting HTML output.
             * @param WP_Post  $item        Menu item data object.
             * @param int      $depth       Depth of menu item. Used for padding.
             * @param stdClass $args        An object of wp_nav_menu() arguments.
             */
            $output .= apply_filters('walker_nav_menu_start_el', $item_output, $item, $depth, $args);
        }

        /**
         * Ends the element output, if needed.
         *
         * @param string   $output Used to append additional content (passed by reference).
         * @param WP_Post  $item   Page data object. Not used.
         * @param int      $depth  Depth of page. Not Used.
         * @param stdClass $args   An object of wp_nav_menu() arguments.
         */
        public function end_el(&$output, $item, $depth = 0, $args = null) {
            if (isset($args->item_spacing) && 'discard' === $args->item_spacing) {
                $t = '';
                $n = '';
            } else {
                $t = "\t";
                $n = "\n";
            }
            $output .= "</li>{$n}";
        }
    }
}

/**
 * Fallback function for primary navigation
 */
function environmental_platform_fallback_menu() {
    echo '<ul class="navbar-nav">';
    echo '<li class="nav-item"><a class="nav-link" href="' . esc_url(home_url('/')) . '"><i class="fas fa-home"></i> ' . esc_html__('Home', 'environmental-platform') . '</a></li>';
    echo '<li class="nav-item"><a class="nav-link" href="' . esc_url(home_url('/environmental-tips/')) . '"><i class="fas fa-leaf"></i> ' . esc_html__('Eco Tips', 'environmental-platform') . '</a></li>';
    echo '<li class="nav-item"><a class="nav-link" href="' . esc_url(home_url('/blog/')) . '"><i class="fas fa-newspaper"></i> ' . esc_html__('Blog', 'environmental-platform') . '</a></li>';
    echo '<li class="nav-item"><a class="nav-link" href="' . esc_url(home_url('/carbon-calculator/')) . '"><i class="fas fa-calculator"></i> ' . esc_html__('Calculator', 'environmental-platform') . '</a></li>';
    echo '<li class="nav-item"><a class="nav-link" href="' . esc_url(home_url('/green-challenges/')) . '"><i class="fas fa-trophy"></i> ' . esc_html__('Challenges', 'environmental-platform') . '</a></li>';
    echo '<li class="nav-item"><a class="nav-link" href="' . esc_url(home_url('/contact/')) . '"><i class="fas fa-envelope"></i> ' . esc_html__('Contact', 'environmental-platform') . '</a></li>';
    echo '</ul>';
}

/**
 * Fallback function for footer navigation
 */
function environmental_platform_fallback_footer_menu() {
    echo '<ul class="footer-nav">';
    echo '<li><a href="' . esc_url(home_url('/')) . '">' . esc_html__('Home', 'environmental-platform') . '</a></li>';
    echo '<li><a href="' . esc_url(home_url('/about/')) . '">' . esc_html__('About', 'environmental-platform') . '</a></li>';
    echo '<li><a href="' . esc_url(home_url('/privacy-policy/')) . '">' . esc_html__('Privacy Policy', 'environmental-platform') . '</a></li>';
    echo '<li><a href="' . esc_url(home_url('/terms-of-service/')) . '">' . esc_html__('Terms of Service', 'environmental-platform') . '</a></li>';
    echo '<li><a href="' . esc_url(home_url('/contact/')) . '">' . esc_html__('Contact', 'environmental-platform') . '</a></li>';
    echo '</ul>';
}
