<?php
/**
 * Elasticsearch Manager Class
 * 
 * Handles Elasticsearch integration for advanced search functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class EAS_Elasticsearch_Manager {
    
    private $host;
    private $index;
    private $enabled;
    private $client;
    
    public function __construct() {
        $this->enabled = get_option('eas_enable_elasticsearch', 'no') === 'yes';
        $this->host = get_option('eas_elasticsearch_host', 'localhost:9200');
        $this->index = get_option('eas_elasticsearch_index', 'environmental_platform');
        
        if ($this->enabled) {
            $this->init_client();
        }
        
        // Hook into post actions for real-time indexing
        add_action('save_post', array($this, 'index_post'), 10, 2);
        add_action('delete_post', array($this, 'delete_post_from_index'));
        add_action('wp_trash_post', array($this, 'delete_post_from_index'));
    }
    
    /**
     * Initialize Elasticsearch client
     */
    private function init_client() {
        // For production, you would use the official Elasticsearch PHP client
        // For this demo, we'll create a simple HTTP client wrapper
        $this->client = new EAS_Elasticsearch_Client($this->host);
    }
    
    /**
     * Check if Elasticsearch is available
     */
    public function is_available() {
        if (!$this->enabled || !$this->client) {
            return false;
        }
        
        return $this->client->ping();
    }
    
    /**
     * Create index with proper mapping
     */
    public function create_index() {
        if (!$this->is_available()) {
            return false;
        }
        
        $mapping = array(
            'mappings' => array(
                'properties' => array(
                    'post_id' => array('type' => 'integer'),
                    'post_title' => array(
                        'type' => 'text',
                        'analyzer' => 'environmental_analyzer',
                        'boost' => 2.0
                    ),
                    'post_content' => array(
                        'type' => 'text',
                        'analyzer' => 'environmental_analyzer'
                    ),
                    'post_excerpt' => array(
                        'type' => 'text',
                        'analyzer' => 'environmental_analyzer',
                        'boost' => 1.5
                    ),
                    'post_type' => array('type' => 'keyword'),
                    'post_status' => array('type' => 'keyword'),
                    'post_date' => array('type' => 'date'),
                    'post_author' => array('type' => 'integer'),
                    'categories' => array(
                        'type' => 'text',
                        'fields' => array(
                            'keyword' => array('type' => 'keyword')
                        )
                    ),
                    'tags' => array(
                        'type' => 'text',
                        'fields' => array(
                            'keyword' => array('type' => 'keyword')
                        )
                    ),
                    'meta_fields' => array('type' => 'object'),
                    'taxonomies' => array('type' => 'object'),
                    'location' => array(
                        'type' => 'geo_point'
                    ),
                    'environmental_impact' => array('type' => 'keyword'),
                    'project_status' => array('type' => 'keyword'),
                    'difficulty_level' => array('type' => 'keyword'),
                    'popularity_score' => array('type' => 'float'),
                    'recent_activity' => array('type' => 'date')
                )
            ),
            'settings' => array(
                'analysis' => array(
                    'analyzer' => array(
                        'environmental_analyzer' => array(
                            'type' => 'custom',
                            'tokenizer' => 'standard',
                            'filter' => array(
                                'lowercase',
                                'stop',
                                'environmental_synonyms',
                                'environmental_stemmer'
                            )
                        )
                    ),
                    'filter' => array(
                        'environmental_synonyms' => array(
                            'type' => 'synonym',
                            'synonyms' => array(
                                'eco,ecological,environment,environmental',
                                'green,sustainable,eco-friendly',
                                'climate,weather,atmospheric',
                                'pollution,contamination,toxic',
                                'renewable,clean,alternative',
                                'conservation,preservation,protection',
                                'biodiversity,ecosystem,habitat',
                                'carbon,greenhouse,emission',
                                'recycling,reuse,waste'
                            )
                        ),
                        'environmental_stemmer' => array(
                            'type' => 'stemmer',
                            'language' => 'english'
                        )
                    )
                )
            )
        );
        
        return $this->client->create_index($this->index, $mapping);
    }
    
    /**
     * Delete index
     */
    public function delete_index() {
        if (!$this->is_available()) {
            return false;
        }
        
        return $this->client->delete_index($this->index);
    }
    
    /**
     * Index a single post
     */
    public function index_post($post_id, $post = null) {
        if (!$this->is_available()) {
            return false;
        }
        
        if (!$post) {
            $post = get_post($post_id);
        }
        
        if (!$post || $post->post_status !== 'publish') {
            // Delete from index if not published
            $this->delete_post_from_index($post_id);
            return false;
        }
        
        // Skip auto-drafts and revisions
        if (in_array($post->post_status, array('auto-draft', 'revision'))) {
            return false;
        }
        
        $document = $this->prepare_document($post);
        
        if ($document) {
            return $this->client->index_document($this->index, $post_id, $document);
        }
        
        return false;
    }
    
    /**
     * Prepare document for indexing
     */
    private function prepare_document($post) {
        // Basic post data
        $document = array(
            'post_id' => $post->ID,
            'post_title' => $post->post_title,
            'post_content' => wp_strip_all_tags($post->post_content),
            'post_excerpt' => $post->post_excerpt,
            'post_type' => $post->post_type,
            'post_status' => $post->post_status,
            'post_date' => date('c', strtotime($post->post_date)),
            'post_author' => $post->post_author
        );
        
        // Categories
        $categories = wp_get_post_categories($post->ID, array('fields' => 'names'));
        $document['categories'] = $categories;
        
        // Tags
        $tags = wp_get_post_tags($post->ID, array('fields' => 'names'));
        $document['tags'] = array_column($tags, 'name');
        
        // Custom taxonomies
        $taxonomies = array();
        $post_taxonomies = get_object_taxonomies($post->post_type);
        foreach ($post_taxonomies as $taxonomy) {
            if (!in_array($taxonomy, array('category', 'post_tag'))) {
                $terms = wp_get_post_terms($post->ID, $taxonomy, array('fields' => 'names'));
                if (!is_wp_error($terms) && !empty($terms)) {
                    $taxonomies[$taxonomy] = $terms;
                }
            }
        }
        $document['taxonomies'] = $taxonomies;
        
        // Meta fields
        $meta_fields = array();
        $all_meta = get_post_meta($post->ID);
        
        foreach ($all_meta as $key => $values) {
            // Skip private meta fields
            if (substr($key, 0, 1) === '_') {
                continue;
            }
            
            // Handle special environmental platform meta fields
            $value = maybe_unserialize($values[0]);
            
            switch ($key) {
                case 'environmental_impact':
                    $document['environmental_impact'] = $value;
                    break;
                case 'project_status':
                    $document['project_status'] = $value;
                    break;
                case 'difficulty_level':
                    $document['difficulty_level'] = $value;
                    break;
                case 'location_lat':
                case 'location_lng':
                    // Handle location coordinates
                    if ($key === 'location_lat') {
                        $lat = floatval($value);
                        $lng = floatval(get_post_meta($post->ID, 'location_lng', true));
                        if ($lat && $lng) {
                            $document['location'] = array(
                                'lat' => $lat,
                                'lon' => $lng
                            );
                        }
                    }
                    break;
                default:
                    $meta_fields[$key] = $value;
                    break;
            }
        }
        
        $document['meta_fields'] = $meta_fields;
        
        // Calculate popularity score based on views, comments, etc.
        $document['popularity_score'] = $this->calculate_popularity_score($post);
        
        // Recent activity timestamp
        $document['recent_activity'] = date('c', strtotime($post->post_modified));
        
        return $document;
    }
    
    /**
     * Calculate popularity score for ranking
     */
    private function calculate_popularity_score($post) {
        $score = 0;
        
        // Comment count
        $score += $post->comment_count * 2;
        
        // Post views (if available)
        $views = get_post_meta($post->ID, 'post_views', true);
        if ($views) {
            $score += intval($views) * 0.1;
        }
        
        // Social shares (if available)
        $shares = get_post_meta($post->ID, 'social_shares', true);
        if ($shares) {
            $score += intval($shares) * 5;
        }
        
        // Recency factor (newer posts get slight boost)
        $days_old = (time() - strtotime($post->post_date)) / DAY_IN_SECONDS;
        $recency_boost = max(0, 30 - $days_old) * 0.1;
        $score += $recency_boost;
        
        return max(0, $score);
    }
    
    /**
     * Delete post from index
     */
    public function delete_post_from_index($post_id) {
        if (!$this->is_available()) {
            return false;
        }
        
        return $this->client->delete_document($this->index, $post_id);
    }
    
    /**
     * Bulk index all posts
     */
    public function bulk_index($batch_size = 100) {
        if (!$this->is_available()) {
            return false;
        }
        
        $processed = 0;
        $errors = array();
        
        // Get all published posts
        $args = array(
            'post_type' => get_post_types(array('public' => true)),
            'post_status' => 'publish',
            'posts_per_page' => $batch_size,
            'fields' => 'ids',
            'no_found_rows' => true
        );
        
        $offset = 0;
        
        do {
            $args['offset'] = $offset;
            $posts = get_posts($args);
            
            if (empty($posts)) {
                break;
            }
            
            $documents = array();
            foreach ($posts as $post_id) {
                $post = get_post($post_id);
                if ($post) {
                    $document = $this->prepare_document($post);
                    if ($document) {
                        $documents[] = array(
                            'id' => $post_id,
                            'document' => $document
                        );
                    }
                }
            }
            
            // Bulk index documents
            if (!empty($documents)) {
                $result = $this->client->bulk_index($this->index, $documents);
                if ($result === false) {
                    $errors[] = "Failed to index batch starting at offset $offset";
                }
            }
            
            $processed += count($posts);
            $offset += $batch_size;
            
            // Prevent memory issues
            wp_cache_flush();
            
        } while (count($posts) === $batch_size);
        
        return array(
            'processed' => $processed,
            'errors' => $errors
        );
    }
    
    /**
     * Search using Elasticsearch
     */
    public function search($query, $filters = array(), $args = array()) {
        if (!$this->is_available()) {
            return false;
        }
        
        $search_body = $this->build_search_query($query, $filters, $args);
        
        return $this->client->search($this->index, $search_body);
    }
    
    /**
     * Build Elasticsearch search query
     */
    private function build_search_query($query, $filters = array(), $args = array()) {
        $search_body = array(
            'query' => array(),
            'sort' => array(),
            'highlight' => array(
                'fields' => array(
                    'post_title' => new stdClass(),
                    'post_content' => new stdClass(),
                    'post_excerpt' => new stdClass()
                )
            ),
            'aggs' => array()
        );
        
        // Main search query
        if (!empty($query)) {
            $search_body['query'] = array(
                'bool' => array(
                    'must' => array(
                        array(
                            'multi_match' => array(
                                'query' => $query,
                                'fields' => array(
                                    'post_title^3',
                                    'post_excerpt^2',
                                    'post_content',
                                    'categories^2',
                                    'tags',
                                    'meta_fields.*'
                                ),
                                'type' => 'best_fields',
                                'fuzziness' => 'AUTO'
                            )
                        )
                    ),
                    'filter' => array(),
                    'should' => array(
                        // Boost recent content
                        array(
                            'range' => array(
                                'post_date' => array(
                                    'gte' => 'now-30d'
                                )
                            )
                        ),
                        // Boost popular content
                        array(
                            'range' => array(
                                'popularity_score' => array(
                                    'gte' => 10
                                )
                            )
                        )
                    )
                )
            );
        } else {
            // If no query, match all but still apply filters
            $search_body['query'] = array(
                'bool' => array(
                    'must' => array(
                        array('match_all' => new stdClass())
                    ),
                    'filter' => array()
                )
            );
        }
        
        // Apply filters
        $filters_array = array();
        
        // Post type filter
        if (!empty($filters['post_type'])) {
            $filters_array[] = array(
                'term' => array(
                    'post_type' => $filters['post_type']
                )
            );
        }
        
        // Category filter
        if (!empty($filters['category'])) {
            $filters_array[] = array(
                'term' => array(
                    'categories.keyword' => $filters['category']
                )
            );
        }
        
        // Environmental impact filter
        if (!empty($filters['environmental_impact'])) {
            $filters_array[] = array(
                'term' => array(
                    'environmental_impact' => $filters['environmental_impact']
                )
            );
        }
        
        // Project status filter
        if (!empty($filters['project_status'])) {
            $filters_array[] = array(
                'term' => array(
                    'project_status' => $filters['project_status']
                )
            );
        }
        
        // Date range filter
        if (!empty($filters['date_range'])) {
            $date_filter = $this->parse_date_range($filters['date_range']);
            if ($date_filter) {
                $filters_array[] = $date_filter;
            }
        }
        
        // Location filter (geo distance)
        if (!empty($filters['location']) && !empty($filters['distance'])) {
            $location_data = $this->parse_location($filters['location']);
            if ($location_data) {
                $filters_array[] = array(
                    'geo_distance' => array(
                        'distance' => $filters['distance'] . 'km',
                        'location' => $location_data
                    )
                );
            }
        }
        
        // Add filters to query
        if (!empty($filters_array)) {
            $search_body['query']['bool']['filter'] = $filters_array;
        }
        
        // Sort options
        $sort_option = isset($args['sort']) ? $args['sort'] : 'relevance';
        
        switch ($sort_option) {
            case 'date_desc':
                $search_body['sort'] = array(
                    array('post_date' => array('order' => 'desc'))
                );
                break;
            case 'date_asc':
                $search_body['sort'] = array(
                    array('post_date' => array('order' => 'asc'))
                );
                break;
            case 'popularity':
                $search_body['sort'] = array(
                    array('popularity_score' => array('order' => 'desc')),
                    '_score'
                );
                break;
            case 'title':
                $search_body['sort'] = array(
                    array('post_title.keyword' => array('order' => 'asc'))
                );
                break;
            default: // relevance
                $search_body['sort'] = array('_score');
                break;
        }
        
        // Pagination
        $page = isset($args['page']) ? max(1, intval($args['page'])) : 1;
        $per_page = isset($args['per_page']) ? intval($args['per_page']) : 10;
        
        $search_body['from'] = ($page - 1) * $per_page;
        $search_body['size'] = $per_page;
        
        // Aggregations for faceted search
        $search_body['aggs'] = array(
            'post_types' => array(
                'terms' => array('field' => 'post_type')
            ),
            'categories' => array(
                'terms' => array('field' => 'categories.keyword')
            ),
            'environmental_impacts' => array(
                'terms' => array('field' => 'environmental_impact')
            ),
            'project_statuses' => array(
                'terms' => array('field' => 'project_status')
            ),
            'date_histogram' => array(
                'date_histogram' => array(
                    'field' => 'post_date',
                    'calendar_interval' => 'month'
                )
            )
        );
        
        return $search_body;
    }
    
    /**
     * Parse date range filter
     */
    private function parse_date_range($date_range) {
        $ranges = array(
            'last_week' => 'now-7d',
            'last_month' => 'now-30d',
            'last_quarter' => 'now-3M',
            'last_year' => 'now-1y'
        );
        
        if (isset($ranges[$date_range])) {
            return array(
                'range' => array(
                    'post_date' => array(
                        'gte' => $ranges[$date_range]
                    )
                )
            );
        }
        
        return null;
    }
    
    /**
     * Parse location for geo queries
     */
    private function parse_location($location) {
        // If it's already coordinates
        if (is_array($location) && isset($location['lat']) && isset($location['lng'])) {
            return array(
                'lat' => floatval($location['lat']),
                'lon' => floatval($location['lng'])
            );
        }
        
        // If it's a string, try to geocode it
        if (is_string($location)) {
            // Here you would integrate with a geocoding service
            // For now, return null
            return null;
        }
        
        return null;
    }
    
    /**
     * Get aggregation data for faceted search
     */
    public function get_aggregations($query = '', $filters = array()) {
        if (!$this->is_available()) {
            return false;
        }
        
        $search_body = array(
            'size' => 0, // We only want aggregations
            'query' => array(
                'bool' => array(
                    'must' => array(),
                    'filter' => array()
                )
            ),
            'aggs' => array(
                'post_types' => array(
                    'terms' => array('field' => 'post_type')
                ),
                'categories' => array(
                    'terms' => array('field' => 'categories.keyword')
                ),
                'environmental_impacts' => array(
                    'terms' => array('field' => 'environmental_impact')
                ),
                'project_statuses' => array(
                    'terms' => array('field' => 'project_status')
                )
            )
        );
        
        if (!empty($query)) {
            $search_body['query']['bool']['must'][] = array(
                'multi_match' => array(
                    'query' => $query,
                    'fields' => array(
                        'post_title^3',
                        'post_content',
                        'post_excerpt^2'
                    )
                )
            );
        } else {
            $search_body['query']['bool']['must'][] = array('match_all' => new stdClass());
        }
        
        return $this->client->search($this->index, $search_body);
    }
}

/**
 * Simple Elasticsearch HTTP Client
 */
class EAS_Elasticsearch_Client {
    
    private $host;
    private $base_url;
    
    public function __construct($host) {
        $this->host = $host;
        $this->base_url = 'http://' . $host;
    }
    
    /**
     * Ping Elasticsearch server
     */
    public function ping() {
        $response = wp_remote_get($this->base_url);
        return !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
    }
    
    /**
     * Create index
     */
    public function create_index($index, $mapping) {
        $url = $this->base_url . '/' . $index;
        
        $response = wp_remote_request($url, array(
            'method' => 'PUT',
            'headers' => array('Content-Type' => 'application/json'),
            'body' => wp_json_encode($mapping)
        ));
        
        return !is_wp_error($response) && in_array(wp_remote_retrieve_response_code($response), array(200, 201));
    }
    
    /**
     * Delete index
     */
    public function delete_index($index) {
        $url = $this->base_url . '/' . $index;
        
        $response = wp_remote_request($url, array(
            'method' => 'DELETE'
        ));
        
        return !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
    }
    
    /**
     * Index document
     */
    public function index_document($index, $id, $document) {
        $url = $this->base_url . '/' . $index . '/_doc/' . $id;
        
        $response = wp_remote_request($url, array(
            'method' => 'PUT',
            'headers' => array('Content-Type' => 'application/json'),
            'body' => wp_json_encode($document)
        ));
        
        return !is_wp_error($response) && in_array(wp_remote_retrieve_response_code($response), array(200, 201));
    }
    
    /**
     * Delete document
     */
    public function delete_document($index, $id) {
        $url = $this->base_url . '/' . $index . '/_doc/' . $id;
        
        $response = wp_remote_request($url, array(
            'method' => 'DELETE'
        ));
        
        return !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
    }
    
    /**
     * Bulk index documents
     */
    public function bulk_index($index, $documents) {
        $url = $this->base_url . '/_bulk';
        
        $body = '';
        foreach ($documents as $doc) {
            $action = array(
                'index' => array(
                    '_index' => $index,
                    '_id' => $doc['id']
                )
            );
            $body .= wp_json_encode($action) . "\n";
            $body .= wp_json_encode($doc['document']) . "\n";
        }
        
        $response = wp_remote_post($url, array(
            'headers' => array('Content-Type' => 'application/x-ndjson'),
            'body' => $body,
            'timeout' => 60
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $response_body = wp_remote_retrieve_body($response);
        $data = json_decode($response_body, true);
        
        return isset($data['errors']) && !$data['errors'];
    }
    
    /**
     * Search documents
     */
    public function search($index, $search_body) {
        $url = $this->base_url . '/' . $index . '/_search';
        
        $response = wp_remote_post($url, array(
            'headers' => array('Content-Type' => 'application/json'),
            'body' => wp_json_encode($search_body),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $response_body = wp_remote_retrieve_body($response);
        return json_decode($response_body, true);
    }
}
