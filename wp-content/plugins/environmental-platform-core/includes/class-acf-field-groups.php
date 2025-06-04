<?php
/**
 * Environmental Platform ACF Field Groups
 * 
 * Manages Advanced Custom Fields integration for all custom post types
 * 
 * @package Environmental_Platform_Core
 * @since 1.0.0 - Phase 30
 */

if (!defined('ABSPATH')) {
    exit;
}

class EP_ACF_Field_Groups {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('acf/init', array($this, 'register_field_groups'));
        add_action('acf/save_post', array($this, 'save_environmental_data'));
        add_filter('acf/location/rule_types', array($this, 'add_location_rules'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_acf_scripts'));
    }
    
    /**
     * Register all field groups for environmental platform
     */
    public function register_field_groups() {
        if (!function_exists('acf_add_local_field_group')) {
            return;
        }
        
        $this->register_environmental_article_fields();
        $this->register_environmental_report_fields();
        $this->register_environmental_alert_fields();
        $this->register_environmental_event_fields();
        $this->register_environmental_project_fields();
        $this->register_eco_product_fields();
        $this->register_community_post_fields();
        $this->register_educational_resource_fields();
        $this->register_waste_classification_fields();
        $this->register_petition_fields();
        $this->register_item_exchange_fields();
        $this->register_global_environmental_fields();
    }
    
    /**
     * Environmental Articles Field Group
     */
    private function register_environmental_article_fields() {
        acf_add_local_field_group(array(
            'key' => 'group_env_article_fields',
            'title' => 'Environmental Article Details',
            'fields' => array(
                array(
                    'key' => 'field_article_impact_score',
                    'label' => 'Environmental Impact Score',
                    'name' => 'environmental_impact_score',
                    'type' => 'number',
                    'min' => 1,
                    'max' => 100,
                    'default_value' => 50,
                    'instructions' => 'Rate the environmental impact of this article (1-100)',
                ),
                array(
                    'key' => 'field_article_carbon_data',
                    'label' => 'Carbon Impact Data',
                    'name' => 'carbon_impact_data',
                    'type' => 'group',
                    'sub_fields' => array(
                        array(
                            'key' => 'field_carbon_footprint',
                            'label' => 'Carbon Footprint (kg CO2)',
                            'name' => 'carbon_footprint_kg',
                            'type' => 'number',
                            'step' => 0.01,
                        ),
                        array(
                            'key' => 'field_carbon_offset',
                            'label' => 'Carbon Offset Potential',
                            'name' => 'carbon_offset_potential',
                            'type' => 'select',
                            'choices' => array(
                                'low' => 'Low Impact',
                                'medium' => 'Medium Impact',
                                'high' => 'High Impact',
                                'critical' => 'Critical Impact'
                            ),
                        ),
                    ),
                ),
                array(
                    'key' => 'field_article_research_data',
                    'label' => 'Research & Sources',
                    'name' => 'research_data',
                    'type' => 'repeater',
                    'button_label' => 'Add Research Source',
                    'sub_fields' => array(
                        array(
                            'key' => 'field_source_title',
                            'label' => 'Source Title',
                            'name' => 'source_title',
                            'type' => 'text',
                        ),
                        array(
                            'key' => 'field_source_url',
                            'label' => 'Source URL',
                            'name' => 'source_url',
                            'type' => 'url',
                        ),
                        array(
                            'key' => 'field_source_credibility',
                            'label' => 'Source Credibility',
                            'name' => 'source_credibility',
                            'type' => 'select',
                            'choices' => array(
                                'peer_reviewed' => 'Peer Reviewed',
                                'government' => 'Government Source',
                                'ngo' => 'NGO Report',
                                'academic' => 'Academic Institution',
                                'industry' => 'Industry Report'
                            ),
                        ),
                    ),
                ),
                array(
                    'key' => 'field_article_action_items',
                    'label' => 'Action Items for Readers',
                    'name' => 'action_items',
                    'type' => 'repeater',
                    'button_label' => 'Add Action Item',
                    'sub_fields' => array(
                        array(
                            'key' => 'field_action_title',
                            'label' => 'Action Title',
                            'name' => 'action_title',
                            'type' => 'text',
                        ),
                        array(
                            'key' => 'field_action_description',
                            'label' => 'Action Description',
                            'name' => 'action_description',
                            'type' => 'textarea',
                        ),
                        array(
                            'key' => 'field_action_difficulty',
                            'label' => 'Difficulty Level',
                            'name' => 'action_difficulty',
                            'type' => 'select',
                            'choices' => array(
                                'easy' => 'Easy (Anyone can do)',
                                'moderate' => 'Moderate (Some effort required)',
                                'challenging' => 'Challenging (Significant commitment)',
                                'expert' => 'Expert (Professional/technical skills)'
                            ),
                        ),
                    ),
                ),
                array(
                    'key' => 'field_article_geo_relevance',
                    'label' => 'Geographic Relevance',
                    'name' => 'geographic_relevance',
                    'type' => 'checkbox',
                    'choices' => array(
                        'global' => 'Global',
                        'asia_pacific' => 'Asia Pacific',
                        'vietnam' => 'Vietnam',
                        'southeast_asia' => 'Southeast Asia',
                        'mekong_delta' => 'Mekong Delta',
                        'urban' => 'Urban Areas',
                        'rural' => 'Rural Areas',
                        'coastal' => 'Coastal Regions'
                    ),
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'env_article',
                    ),
                ),
            ),
            'menu_order' => 0,
            'position' => 'normal',
            'style' => 'default',
            'instruction_placement' => 'label',
        ));
    }
    
    /**
     * Environmental Reports Field Group
     */
    private function register_environmental_report_fields() {
        acf_add_local_field_group(array(
            'key' => 'group_env_report_fields',
            'title' => 'Environmental Report Data',
            'fields' => array(
                array(
                    'key' => 'field_report_type',
                    'label' => 'Report Type',
                    'name' => 'report_type',
                    'type' => 'select',
                    'choices' => array(
                        'impact_assessment' => 'Environmental Impact Assessment',
                        'monitoring' => 'Environmental Monitoring',
                        'compliance' => 'Compliance Report',
                        'research' => 'Research Study',
                        'audit' => 'Environmental Audit',
                        'restoration' => 'Restoration Progress',
                    ),
                ),
                array(
                    'key' => 'field_report_methodology',
                    'label' => 'Research Methodology',
                    'name' => 'research_methodology',
                    'type' => 'group',
                    'sub_fields' => array(
                        array(
                            'key' => 'field_study_period',
                            'label' => 'Study Period',
                            'name' => 'study_period',
                            'type' => 'date_range_picker',
                        ),
                        array(
                            'key' => 'field_sample_size',
                            'label' => 'Sample Size',
                            'name' => 'sample_size',
                            'type' => 'number',
                        ),
                        array(
                            'key' => 'field_data_collection_methods',
                            'label' => 'Data Collection Methods',
                            'name' => 'data_collection_methods',
                            'type' => 'checkbox',
                            'choices' => array(
                                'field_surveys' => 'Field Surveys',
                                'satellite_data' => 'Satellite Data',
                                'sensor_networks' => 'Sensor Networks',
                                'laboratory_analysis' => 'Laboratory Analysis',
                                'interviews' => 'Stakeholder Interviews',
                                'community_reporting' => 'Community Reporting'
                            ),
                        ),
                    ),
                ),
                array(
                    'key' => 'field_report_metrics',
                    'label' => 'Environmental Metrics',
                    'name' => 'environmental_metrics',
                    'type' => 'repeater',
                    'button_label' => 'Add Metric',
                    'sub_fields' => array(
                        array(
                            'key' => 'field_metric_name',
                            'label' => 'Metric Name',
                            'name' => 'metric_name',
                            'type' => 'text',
                        ),
                        array(
                            'key' => 'field_metric_value',
                            'label' => 'Current Value',
                            'name' => 'metric_value',
                            'type' => 'number',
                            'step' => 0.01,
                        ),
                        array(
                            'key' => 'field_metric_unit',
                            'label' => 'Unit of Measurement',
                            'name' => 'metric_unit',
                            'type' => 'text',
                            'placeholder' => 'e.g., kg CO2, ppm, mg/L',
                        ),
                        array(
                            'key' => 'field_metric_baseline',
                            'label' => 'Baseline Value',
                            'name' => 'metric_baseline',
                            'type' => 'number',
                            'step' => 0.01,
                        ),
                        array(
                            'key' => 'field_metric_trend',
                            'label' => 'Trend',
                            'name' => 'metric_trend',
                            'type' => 'select',
                            'choices' => array(
                                'improving' => 'Improving',
                                'stable' => 'Stable',
                                'declining' => 'Declining',
                                'critical' => 'Critical'
                            ),
                        ),
                    ),
                ),
                array(
                    'key' => 'field_report_recommendations',
                    'label' => 'Recommendations',
                    'name' => 'recommendations',
                    'type' => 'repeater',
                    'button_label' => 'Add Recommendation',
                    'sub_fields' => array(
                        array(
                            'key' => 'field_recommendation_title',
                            'label' => 'Recommendation Title',
                            'name' => 'recommendation_title',
                            'type' => 'text',
                        ),
                        array(
                            'key' => 'field_recommendation_description',
                            'label' => 'Description',
                            'name' => 'recommendation_description',
                            'type' => 'textarea',
                        ),
                        array(
                            'key' => 'field_recommendation_priority',
                            'label' => 'Priority Level',
                            'name' => 'recommendation_priority',
                            'type' => 'select',
                            'choices' => array(
                                'urgent' => 'Urgent',
                                'high' => 'High',
                                'medium' => 'Medium',
                                'low' => 'Low'
                            ),
                        ),
                        array(
                            'key' => 'field_recommendation_timeline',
                            'label' => 'Implementation Timeline',
                            'name' => 'recommendation_timeline',
                            'type' => 'select',
                            'choices' => array(
                                'immediate' => 'Immediate (0-30 days)',
                                'short_term' => 'Short Term (1-6 months)',
                                'medium_term' => 'Medium Term (6-24 months)',
                                'long_term' => 'Long Term (2+ years)'
                            ),
                        ),
                    ),
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'env_report',
                    ),
                ),
            ),
            'menu_order' => 0,
            'position' => 'normal',
            'style' => 'default',
        ));
    }
    
    /**
     * Environmental Alerts Field Group
     */
    private function register_environmental_alert_fields() {
        acf_add_local_field_group(array(
            'key' => 'group_env_alert_fields',
            'title' => 'Environmental Alert Information',
            'fields' => array(
                array(
                    'key' => 'field_alert_severity',
                    'label' => 'Alert Severity Level',
                    'name' => 'alert_severity',
                    'type' => 'select',
                    'choices' => array(
                        'info' => 'Information',
                        'warning' => 'Warning',
                        'urgent' => 'Urgent',
                        'critical' => 'Critical',
                        'emergency' => 'Emergency'
                    ),
                    'default_value' => 'info',
                ),
                array(
                    'key' => 'field_alert_type',
                    'label' => 'Alert Type',
                    'name' => 'alert_type',
                    'type' => 'select',
                    'choices' => array(
                        'air_quality' => 'Air Quality',
                        'water_pollution' => 'Water Pollution',
                        'soil_contamination' => 'Soil Contamination',
                        'wildlife_threat' => 'Wildlife Threat',
                        'climate_extreme' => 'Climate Extreme',
                        'industrial_accident' => 'Industrial Accident',
                        'natural_disaster' => 'Natural Disaster'
                    ),
                ),
                array(
                    'key' => 'field_alert_location',
                    'label' => 'Alert Location',
                    'name' => 'alert_location',
                    'type' => 'group',
                    'sub_fields' => array(
                        array(
                            'key' => 'field_affected_area',
                            'label' => 'Affected Area',
                            'name' => 'affected_area',
                            'type' => 'text',
                        ),
                        array(
                            'key' => 'field_coordinates',
                            'label' => 'GPS Coordinates',
                            'name' => 'gps_coordinates',
                            'type' => 'text',
                            'placeholder' => 'Latitude, Longitude',
                        ),
                        array(
                            'key' => 'field_radius_impact',
                            'label' => 'Impact Radius (km)',
                            'name' => 'impact_radius_km',
                            'type' => 'number',
                            'step' => 0.1,
                        ),
                    ),
                ),
                array(
                    'key' => 'field_alert_timeline',
                    'label' => 'Alert Timeline',
                    'name' => 'alert_timeline',
                    'type' => 'group',
                    'sub_fields' => array(
                        array(
                            'key' => 'field_incident_start',
                            'label' => 'Incident Start Time',
                            'name' => 'incident_start_time',
                            'type' => 'date_time_picker',
                        ),
                        array(
                            'key' => 'field_expected_duration',
                            'label' => 'Expected Duration',
                            'name' => 'expected_duration',
                            'type' => 'select',
                            'choices' => array(
                                'hours' => 'Hours',
                                'days' => 'Days',
                                'weeks' => 'Weeks',
                                'months' => 'Months',
                                'ongoing' => 'Ongoing'
                            ),
                        ),
                        array(
                            'key' => 'field_next_update',
                            'label' => 'Next Update Scheduled',
                            'name' => 'next_update_time',
                            'type' => 'date_time_picker',
                        ),
                    ),
                ),
                array(
                    'key' => 'field_alert_actions',
                    'label' => 'Recommended Actions',
                    'name' => 'recommended_actions',
                    'type' => 'repeater',
                    'button_label' => 'Add Action',
                    'sub_fields' => array(
                        array(
                            'key' => 'field_action_text',
                            'label' => 'Action Description',
                            'name' => 'action_description',
                            'type' => 'textarea',
                            'rows' => 3,
                        ),
                        array(
                            'key' => 'field_action_urgency',
                            'label' => 'Action Urgency',
                            'name' => 'action_urgency',
                            'type' => 'select',
                            'choices' => array(
                                'immediate' => 'Immediate',
                                'within_hours' => 'Within Hours',
                                'within_days' => 'Within Days',
                                'preventive' => 'Preventive'
                            ),
                        ),
                    ),
                ),
                array(
                    'key' => 'field_alert_contacts',
                    'label' => 'Emergency Contacts',
                    'name' => 'emergency_contacts',
                    'type' => 'repeater',
                    'button_label' => 'Add Contact',
                    'sub_fields' => array(
                        array(
                            'key' => 'field_contact_name',
                            'label' => 'Contact Name/Organization',
                            'name' => 'contact_name',
                            'type' => 'text',
                        ),
                        array(
                            'key' => 'field_contact_phone',
                            'label' => 'Phone Number',
                            'name' => 'contact_phone',
                            'type' => 'text',
                        ),
                        array(
                            'key' => 'field_contact_role',
                            'label' => 'Role/Responsibility',
                            'name' => 'contact_role',
                            'type' => 'text',
                        ),
                    ),
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'env_alert',
                    ),
                ),
            ),
            'menu_order' => 0,
            'position' => 'normal',
            'style' => 'default',
        ));
    }
    
    /**
     * Environmental Events Field Group
     */
    private function register_environmental_event_fields() {
        acf_add_local_field_group(array(
            'key' => 'group_env_event_fields',
            'title' => 'Environmental Event Details',
            'fields' => array(
                array(
                    'key' => 'field_event_datetime',
                    'label' => 'Event Date & Time',
                    'name' => 'event_datetime',
                    'type' => 'group',
                    'sub_fields' => array(
                        array(
                            'key' => 'field_event_start',
                            'label' => 'Start Date & Time',
                            'name' => 'event_start_datetime',
                            'type' => 'date_time_picker',
                            'required' => 1,
                        ),
                        array(
                            'key' => 'field_event_end',
                            'label' => 'End Date & Time',
                            'name' => 'event_end_datetime',
                            'type' => 'date_time_picker',
                        ),
                        array(
                            'key' => 'field_event_timezone',
                            'label' => 'Timezone',
                            'name' => 'event_timezone',
                            'type' => 'select',
                            'choices' => array(
                                'asia_ho_chi_minh' => 'Asia/Ho_Chi_Minh (UTC+7)',
                                'utc' => 'UTC (GMT)',
                                'local' => 'Local Time'
                            ),
                            'default_value' => 'asia_ho_chi_minh',
                        ),
                    ),
                ),
                array(
                    'key' => 'field_event_location',
                    'label' => 'Event Location',
                    'name' => 'event_location',
                    'type' => 'group',
                    'sub_fields' => array(
                        array(
                            'key' => 'field_venue_name',
                            'label' => 'Venue Name',
                            'name' => 'venue_name',
                            'type' => 'text',
                        ),
                        array(
                            'key' => 'field_venue_address',
                            'label' => 'Address',
                            'name' => 'venue_address',
                            'type' => 'textarea',
                            'rows' => 3,
                        ),
                        array(
                            'key' => 'field_venue_coordinates',
                            'label' => 'GPS Coordinates',
                            'name' => 'venue_coordinates',
                            'type' => 'text',
                            'placeholder' => 'Latitude, Longitude',
                        ),
                        array(
                            'key' => 'field_venue_capacity',
                            'label' => 'Venue Capacity',
                            'name' => 'venue_capacity',
                            'type' => 'number',
                        ),
                        array(
                            'key' => 'field_event_format',
                            'label' => 'Event Format',
                            'name' => 'event_format',
                            'type' => 'select',
                            'choices' => array(
                                'in_person' => 'In Person',
                                'virtual' => 'Virtual/Online',
                                'hybrid' => 'Hybrid (In-person + Virtual)'
                            ),
                        ),
                    ),
                ),
                array(
                    'key' => 'field_event_registration',
                    'label' => 'Registration Information',
                    'name' => 'event_registration',
                    'type' => 'group',
                    'sub_fields' => array(
                        array(
                            'key' => 'field_registration_required',
                            'label' => 'Registration Required',
                            'name' => 'registration_required',
                            'type' => 'true_false',
                            'default_value' => 1,
                        ),
                        array(
                            'key' => 'field_registration_deadline',
                            'label' => 'Registration Deadline',
                            'name' => 'registration_deadline',
                            'type' => 'date_time_picker',
                            'conditional_logic' => array(
                                array(
                                    array(
                                        'field' => 'field_registration_required',
                                        'operator' => '==',
                                        'value' => '1',
                                    ),
                                ),
                            ),
                        ),
                        array(
                            'key' => 'field_registration_fee',
                            'label' => 'Registration Fee (VND)',
                            'name' => 'registration_fee',
                            'type' => 'number',
                            'default_value' => 0,
                        ),
                        array(
                            'key' => 'field_registration_url',
                            'label' => 'Registration URL',
                            'name' => 'registration_url',
                            'type' => 'url',
                        ),
                    ),
                ),
                array(
                    'key' => 'field_event_organizers',
                    'label' => 'Event Organizers',
                    'name' => 'event_organizers',
                    'type' => 'repeater',
                    'button_label' => 'Add Organizer',
                    'sub_fields' => array(
                        array(
                            'key' => 'field_organizer_name',
                            'label' => 'Organization Name',
                            'name' => 'organizer_name',
                            'type' => 'text',
                        ),
                        array(
                            'key' => 'field_organizer_contact',
                            'label' => 'Contact Person',
                            'name' => 'organizer_contact',
                            'type' => 'text',
                        ),
                        array(
                            'key' => 'field_organizer_email',
                            'label' => 'Email',
                            'name' => 'organizer_email',
                            'type' => 'email',
                        ),
                        array(
                            'key' => 'field_organizer_role',
                            'label' => 'Role',
                            'name' => 'organizer_role',
                            'type' => 'select',
                            'choices' => array(
                                'primary' => 'Primary Organizer',
                                'co_organizer' => 'Co-organizer',
                                'sponsor' => 'Sponsor',
                                'partner' => 'Partner'
                            ),
                        ),
                    ),
                ),
                array(
                    'key' => 'field_event_agenda',
                    'label' => 'Event Agenda',
                    'name' => 'event_agenda',
                    'type' => 'repeater',
                    'button_label' => 'Add Agenda Item',
                    'sub_fields' => array(
                        array(
                            'key' => 'field_agenda_time',
                            'label' => 'Time',
                            'name' => 'agenda_time',
                            'type' => 'time_picker',
                        ),
                        array(
                            'key' => 'field_agenda_title',
                            'label' => 'Activity Title',
                            'name' => 'agenda_title',
                            'type' => 'text',
                        ),
                        array(
                            'key' => 'field_agenda_description',
                            'label' => 'Description',
                            'name' => 'agenda_description',
                            'type' => 'textarea',
                            'rows' => 3,
                        ),
                        array(
                            'key' => 'field_agenda_speaker',
                            'label' => 'Speaker/Facilitator',
                            'name' => 'agenda_speaker',
                            'type' => 'text',
                        ),
                    ),
                ),
                array(
                    'key' => 'field_event_sustainability',
                    'label' => 'Sustainability Measures',
                    'name' => 'event_sustainability',
                    'type' => 'checkbox',
                    'choices' => array(
                        'carbon_neutral' => 'Carbon Neutral Event',
                        'zero_waste' => 'Zero Waste Goal',
                        'local_sourcing' => 'Locally Sourced Materials',
                        'public_transport' => 'Accessible by Public Transport',
                        'renewable_energy' => 'Powered by Renewable Energy',
                        'paperless' => 'Paperless Event',
                        'reusable_materials' => 'Reusable Materials Only'
                    ),
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'env_event',
                    ),
                ),
            ),
            'menu_order' => 0,
            'position' => 'normal',
            'style' => 'default',
        ));
    }
    
    /**
     * Environmental Projects Field Group
     */
    private function register_environmental_project_fields() {
        acf_add_local_field_group(array(
            'key' => 'group_env_project_fields',
            'title' => 'Environmental Project Management',
            'fields' => array(
                array(
                    'key' => 'field_project_overview',
                    'label' => 'Project Overview',
                    'name' => 'project_overview',
                    'type' => 'group',
                    'sub_fields' => array(
                        array(
                            'key' => 'field_project_status',
                            'label' => 'Project Status',
                            'name' => 'project_status',
                            'type' => 'select',
                            'choices' => array(
                                'planning' => 'Planning Phase',
                                'funding' => 'Seeking Funding',
                                'active' => 'Active Implementation',
                                'monitoring' => 'Monitoring & Evaluation',
                                'completed' => 'Completed',
                                'on_hold' => 'On Hold',
                                'cancelled' => 'Cancelled'
                            ),
                        ),
                        array(
                            'key' => 'field_project_duration',
                            'label' => 'Project Duration',
                            'name' => 'project_duration',
                            'type' => 'group',
                            'sub_fields' => array(
                                array(
                                    'key' => 'field_start_date',
                                    'label' => 'Start Date',
                                    'name' => 'start_date',
                                    'type' => 'date_picker',
                                ),
                                array(
                                    'key' => 'field_end_date',
                                    'label' => 'Expected End Date',
                                    'name' => 'end_date',
                                    'type' => 'date_picker',
                                ),
                                array(
                                    'key' => 'field_duration_months',
                                    'label' => 'Duration (Months)',
                                    'name' => 'duration_months',
                                    'type' => 'number',
                                ),
                            ),
                        ),
                    ),
                ),
                array(
                    'key' => 'field_project_goals',
                    'label' => 'Project Goals & Objectives',
                    'name' => 'project_goals',
                    'type' => 'repeater',
                    'button_label' => 'Add Goal',
                    'sub_fields' => array(
                        array(
                            'key' => 'field_goal_title',
                            'label' => 'Goal Title',
                            'name' => 'goal_title',
                            'type' => 'text',
                        ),
                        array(
                            'key' => 'field_goal_description',
                            'label' => 'Goal Description',
                            'name' => 'goal_description',
                            'type' => 'textarea',
                        ),
                        array(
                            'key' => 'field_goal_target_metric',
                            'label' => 'Target Metric',
                            'name' => 'goal_target_metric',
                            'type' => 'text',
                        ),
                        array(
                            'key' => 'field_goal_progress',
                            'label' => 'Current Progress (%)',
                            'name' => 'goal_progress_percent',
                            'type' => 'range',
                            'min' => 0,
                            'max' => 100,
                            'step' => 5,
                        ),
                    ),
                ),
                array(
                    'key' => 'field_project_budget',
                    'label' => 'Project Budget',
                    'name' => 'project_budget',
                    'type' => 'group',
                    'sub_fields' => array(
                        array(
                            'key' => 'field_total_budget',
                            'label' => 'Total Budget (VND)',
                            'name' => 'total_budget_vnd',
                            'type' => 'number',
                        ),
                        array(
                            'key' => 'field_funding_secured',
                            'label' => 'Funding Secured (VND)',
                            'name' => 'funding_secured_vnd',
                            'type' => 'number',
                        ),
                        array(
                            'key' => 'field_funding_sources',
                            'label' => 'Funding Sources',
                            'name' => 'funding_sources',
                            'type' => 'repeater',
                            'button_label' => 'Add Funding Source',
                            'sub_fields' => array(
                                array(
                                    'key' => 'field_funder_name',
                                    'label' => 'Funder Name',
                                    'name' => 'funder_name',
                                    'type' => 'text',
                                ),
                                array(
                                    'key' => 'field_funding_amount',
                                    'label' => 'Amount (VND)',
                                    'name' => 'funding_amount',
                                    'type' => 'number',
                                ),
                                array(
                                    'key' => 'field_funding_type',
                                    'label' => 'Funding Type',
                                    'name' => 'funding_type',
                                    'type' => 'select',
                                    'choices' => array(
                                        'grant' => 'Grant',
                                        'donation' => 'Donation',
                                        'government' => 'Government Funding',
                                        'private' => 'Private Investment',
                                        'crowdfunding' => 'Crowdfunding'
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                array(
                    'key' => 'field_project_team',
                    'label' => 'Project Team',
                    'name' => 'project_team',
                    'type' => 'repeater',
                    'button_label' => 'Add Team Member',
                    'sub_fields' => array(
                        array(
                            'key' => 'field_member_name',
                            'label' => 'Name',
                            'name' => 'member_name',
                            'type' => 'text',
                        ),
                        array(
                            'key' => 'field_member_role',
                            'label' => 'Role',
                            'name' => 'member_role',
                            'type' => 'text',
                        ),
                        array(
                            'key' => 'field_member_organization',
                            'label' => 'Organization',
                            'name' => 'member_organization',
                            'type' => 'text',
                        ),
                        array(
                            'key' => 'field_member_contact',
                            'label' => 'Contact Email',
                            'name' => 'member_contact',
                            'type' => 'email',
                        ),
                    ),
                ),
                array(
                    'key' => 'field_environmental_impact',
                    'label' => 'Environmental Impact Metrics',
                    'name' => 'environmental_impact',
                    'type' => 'group',
                    'sub_fields' => array(
                        array(
                            'key' => 'field_carbon_reduction',
                            'label' => 'Carbon Reduction Target (kg CO2)',
                            'name' => 'carbon_reduction_kg',
                            'type' => 'number',
                            'step' => 0.01,
                        ),
                        array(
                            'key' => 'field_area_protected',
                            'label' => 'Area Protected/Restored (hectares)',
                            'name' => 'area_protected_hectares',
                            'type' => 'number',
                            'step' => 0.01,
                        ),
                        array(
                            'key' => 'field_people_benefited',
                            'label' => 'Number of People Benefited',
                            'name' => 'people_benefited',
                            'type' => 'number',
                        ),
                        array(
                            'key' => 'field_species_protected',
                            'label' => 'Species Protected',
                            'name' => 'species_protected',
                            'type' => 'number',
                        ),
                    ),
                ),
                array(
                    'key' => 'field_volunteer_opportunities',
                    'label' => 'Volunteer Opportunities',
                    'name' => 'volunteer_opportunities',
                    'type' => 'group',
                    'sub_fields' => array(
                        array(
                            'key' => 'field_volunteers_needed',
                            'label' => 'Volunteers Needed',
                            'name' => 'volunteers_needed',
                            'type' => 'true_false',
                        ),
                        array(
                            'key' => 'field_volunteer_roles',
                            'label' => 'Volunteer Roles Available',
                            'name' => 'volunteer_roles',
                            'type' => 'repeater',
                            'button_label' => 'Add Role',
                            'conditional_logic' => array(
                                array(
                                    array(
                                        'field' => 'field_volunteers_needed',
                                        'operator' => '==',
                                        'value' => '1',
                                    ),
                                ),
                            ),
                            'sub_fields' => array(
                                array(
                                    'key' => 'field_role_title',
                                    'label' => 'Role Title',
                                    'name' => 'role_title',
                                    'type' => 'text',
                                ),
                                array(
                                    'key' => 'field_role_description',
                                    'label' => 'Role Description',
                                    'name' => 'role_description',
                                    'type' => 'textarea',
                                ),
                                array(
                                    'key' => 'field_time_commitment',
                                    'label' => 'Time Commitment',
                                    'name' => 'time_commitment',
                                    'type' => 'text',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'env_project',
                    ),
                ),
            ),
            'menu_order' => 0,
            'position' => 'normal',
            'style' => 'default',
        ));
    }
    
    /**
     * Eco Products Field Group
     */
    private function register_eco_product_fields() {
        acf_add_local_field_group(array(
            'key' => 'group_eco_product_fields',
            'title' => 'Eco Product Information',
            'fields' => array(
                array(
                    'key' => 'field_product_sustainability',
                    'label' => 'Sustainability Information',
                    'name' => 'product_sustainability',
                    'type' => 'group',
                    'sub_fields' => array(
                        array(
                            'key' => 'field_sustainability_score',
                            'label' => 'Sustainability Score (1-100)',
                            'name' => 'sustainability_score',
                            'type' => 'range',
                            'min' => 1,
                            'max' => 100,
                            'step' => 1,
                            'default_value' => 50,
                        ),
                        array(
                            'key' => 'field_eco_certifications',
                            'label' => 'Eco Certifications',
                            'name' => 'eco_certifications',
                            'type' => 'checkbox',
                            'choices' => array(
                                'organic' => 'Organic Certified',
                                'fair_trade' => 'Fair Trade',
                                'recycled_content' => 'Recycled Content',
                                'biodegradable' => 'Biodegradable',
                                'carbon_neutral' => 'Carbon Neutral',
                                'energy_star' => 'Energy Star',
                                'forest_stewardship' => 'FSC Certified',
                                'green_seal' => 'Green Seal'
                            ),
                        ),
                        array(
                            'key' => 'field_carbon_footprint',
                            'label' => 'Carbon Footprint (kg CO2e)',
                            'name' => 'carbon_footprint_product',
                            'type' => 'number',
                            'step' => 0.01,
                        ),
                    ),
                ),
                array(
                    'key' => 'field_product_lifecycle',
                    'label' => 'Product Lifecycle',
                    'name' => 'product_lifecycle',
                    'type' => 'group',
                    'sub_fields' => array(
                        array(
                            'key' => 'field_materials_source',
                            'label' => 'Materials Source',
                            'name' => 'materials_source',
                            'type' => 'textarea',
                            'rows' => 3,
                        ),
                        array(
                            'key' => 'field_manufacturing_location',
                            'label' => 'Manufacturing Location',
                            'name' => 'manufacturing_location',
                            'type' => 'text',
                        ),
                        array(
                            'key' => 'field_recyclability',
                            'label' => 'Recyclability Percentage',
                            'name' => 'recyclability_percentage',
                            'type' => 'range',
                            'min' => 0,
                            'max' => 100,
                            'step' => 5,
                        ),
                        array(
                            'key' => 'field_disposal_instructions',
                            'label' => 'Disposal Instructions',
                            'name' => 'disposal_instructions',
                            'type' => 'textarea',
                            'rows' => 3,
                        ),
                    ),
                ),
                array(
                    'key' => 'field_product_pricing',
                    'label' => 'Pricing Information',
                    'name' => 'product_pricing',
                    'type' => 'group',
                    'sub_fields' => array(
                        array(
                            'key' => 'field_price_vnd',
                            'label' => 'Price (VND)',
                            'name' => 'price_vnd',
                            'type' => 'number',
                        ),
                        array(
                            'key' => 'field_price_comparison',
                            'label' => 'Price vs Conventional Alternative',
                            'name' => 'price_comparison',
                            'type' => 'select',
                            'choices' => array(
                                'lower' => 'Lower Cost',
                                'same' => 'Similar Cost',
                                'slightly_higher' => 'Slightly Higher (+10-25%)',
                                'higher' => 'Higher (+25-50%)',
                                'premium' => 'Premium (+50%+)'
                            ),
                        ),
                        array(
                            'key' => 'field_bulk_pricing',
                            'label' => 'Bulk Pricing Available',
                            'name' => 'bulk_pricing_available',
                            'type' => 'true_false',
                        ),
                    ),
                ),
                array(
                    'key' => 'field_supplier_info',
                    'label' => 'Supplier Information',
                    'name' => 'supplier_info',
                    'type' => 'group',
                    'sub_fields' => array(
                        array(
                            'key' => 'field_supplier_name',
                            'label' => 'Supplier/Brand Name',
                            'name' => 'supplier_name',
                            'type' => 'text',
                        ),
                        array(
                            'key' => 'field_supplier_contact',
                            'label' => 'Contact Information',
                            'name' => 'supplier_contact',
                            'type' => 'group',
                            'sub_fields' => array(
                                array(
                                    'key' => 'field_supplier_email',
                                    'label' => 'Email',
                                    'name' => 'supplier_email',
                                    'type' => 'email',
                                ),
                                array(
                                    'key' => 'field_supplier_phone',
                                    'label' => 'Phone',
                                    'name' => 'supplier_phone',
                                    'type' => 'text',
                                ),
                                array(
                                    'key' => 'field_supplier_website',
                                    'label' => 'Website',
                                    'name' => 'supplier_website',
                                    'type' => 'url',
                                ),
                            ),
                        ),
                        array(
                            'key' => 'field_local_supplier',
                            'label' => 'Local Supplier (Vietnam)',
                            'name' => 'local_supplier',
                            'type' => 'true_false',
                        ),
                    ),
                ),
                array(
                    'key' => 'field_product_reviews',
                    'label' => 'Product Reviews & Ratings',
                    'name' => 'product_reviews',
                    'type' => 'group',
                    'sub_fields' => array(
                        array(
                            'key' => 'field_eco_rating',
                            'label' => 'Eco Rating (1-5 stars)',
                            'name' => 'eco_rating',
                            'type' => 'range',
                            'min' => 1,
                            'max' => 5,
                            'step' => 0.5,
                        ),
                        array(
                            'key' => 'field_user_rating',
                            'label' => 'User Rating (1-5 stars)',
                            'name' => 'user_rating',
                            'type' => 'range',
                            'min' => 1,
                            'max' => 5,
                            'step' => 0.5,
                        ),
                        array(
                            'key' => 'field_total_reviews',
                            'label' => 'Total Number of Reviews',
                            'name' => 'total_reviews',
                            'type' => 'number',
                        ),
                    ),
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'eco_product',
                    ),
                ),
            ),
            'menu_order' => 0,
            'position' => 'normal',
            'style' => 'default',
        ));
    }
    
    /**
     * Community Posts Field Group
     */
    private function register_community_post_fields() {
        acf_add_local_field_group(array(
            'key' => 'group_community_post_fields',
            'title' => 'Community Post Details',
            'fields' => array(
                array(
                    'key' => 'field_post_type_community',
                    'label' => 'Post Type',
                    'name' => 'community_post_type',
                    'type' => 'select',
                    'choices' => array(
                        'discussion' => 'Discussion Topic',
                        'question' => 'Question',
                        'tip' => 'Environmental Tip',
                        'success_story' => 'Success Story',
                        'help_request' => 'Help Request',
                        'initiative' => 'Community Initiative',
                        'event_sharing' => 'Event Sharing'
                    ),
                ),
                array(
                    'key' => 'field_community_engagement',
                    'label' => 'Community Engagement',
                    'name' => 'community_engagement',
                    'type' => 'group',
                    'sub_fields' => array(
                        array(
                            'key' => 'field_looking_for',
                            'label' => 'Looking For',
                            'name' => 'looking_for',
                            'type' => 'checkbox',
                            'choices' => array(
                                'advice' => 'Advice & Tips',
                                'collaboration' => 'Collaboration',
                                'volunteers' => 'Volunteers',
                                'funding' => 'Funding/Support',
                                'information' => 'Information/Resources',
                                'networking' => 'Networking'
                            ),
                        ),
                        array(
                            'key' => 'field_expertise_level',
                            'label' => 'Author Expertise Level',
                            'name' => 'author_expertise_level',
                            'type' => 'select',
                            'choices' => array(
                                'beginner' => 'Beginner',
                                'intermediate' => 'Intermediate',
                                'advanced' => 'Advanced',
                                'expert' => 'Expert/Professional'
                            ),
                        ),
                        array(
                            'key' => 'field_discussion_tags',
                            'label' => 'Discussion Tags',
                            'name' => 'discussion_tags',
                            'type' => 'checkbox',
                            'choices' => array(
                                'urgent' => 'Urgent',
                                'beginner_friendly' => 'Beginner Friendly',
                                'local_vietnam' => 'Local to Vietnam',
                                'global_relevance' => 'Globally Relevant',
                                'research_based' => 'Research Based',
                                'personal_experience' => 'Personal Experience'
                            ),
                        ),
                    ),
                ),
                array(
                    'key' => 'field_environmental_focus',
                    'label' => 'Environmental Focus Areas',
                    'name' => 'environmental_focus_areas',
                    'type' => 'checkbox',
                    'choices' => array(
                        'climate_change' => 'Climate Change',
                        'renewable_energy' => 'Renewable Energy',
                        'waste_management' => 'Waste Management',
                        'water_conservation' => 'Water Conservation',
                        'biodiversity' => 'Biodiversity',
                        'sustainable_living' => 'Sustainable Living',
                        'green_technology' => 'Green Technology',
                        'environmental_justice' => 'Environmental Justice',
                        'agriculture' => 'Sustainable Agriculture',
                        'transportation' => 'Green Transportation'
                    ),
                ),
                array(
                    'key' => 'field_location_relevance',
                    'label' => 'Location Relevance',
                    'name' => 'location_relevance',
                    'type' => 'group',
                    'sub_fields' => array(
                        array(
                            'key' => 'field_specific_location',
                            'label' => 'Specific Location',
                            'name' => 'specific_location',
                            'type' => 'text',
                            'placeholder' => 'City, Province, Vietnam',
                        ),
                        array(
                            'key' => 'field_geographic_scope',
                            'label' => 'Geographic Scope',
                            'name' => 'geographic_scope',
                            'type' => 'select',
                            'choices' => array(
                                'neighborhood' => 'Neighborhood',
                                'city' => 'City/Municipal',
                                'province' => 'Province/Region',
                                'national' => 'National (Vietnam)',
                                'regional' => 'Regional (Southeast Asia)',
                                'global' => 'Global'
                            ),
                        ),
                    ),
                ),
                array(
                    'key' => 'field_success_metrics',
                    'label' => 'Success Story Metrics',
                    'name' => 'success_metrics',
                    'type' => 'group',
                    'conditional_logic' => array(
                        array(
                            array(
                                'field' => 'field_post_type_community',
                                'operator' => '==',
                                'value' => 'success_story',
                            ),
                        ),
                    ),
                    'sub_fields' => array(
                        array(
                            'key' => 'field_impact_achieved',
                            'label' => 'Impact Achieved',
                            'name' => 'impact_achieved',
                            'type' => 'textarea',
                            'rows' => 3,
                        ),
                        array(
                            'key' => 'field_people_involved',
                            'label' => 'Number of People Involved',
                            'name' => 'people_involved',
                            'type' => 'number',
                        ),
                        array(
                            'key' => 'field_timeline_success',
                            'label' => 'Timeline to Success',
                            'name' => 'timeline_to_success',
                            'type' => 'text',
                            'placeholder' => 'e.g., 6 months, 2 years',
                        ),
                        array(
                            'key' => 'field_resources_used',
                            'label' => 'Resources Used/Budget',
                            'name' => 'resources_used',
                            'type' => 'text',
                        ),
                    ),
                ),
                array(
                    'key' => 'field_attachments',
                    'label' => 'Supporting Materials',
                    'name' => 'supporting_materials',
                    'type' => 'group',
                    'sub_fields' => array(
                        array(
                            'key' => 'field_images',
                            'label' => 'Additional Images',
                            'name' => 'additional_images',
                            'type' => 'gallery',
                            'max' => 10,
                        ),
                        array(
                            'key' => 'field_documents',
                            'label' => 'Documents/Resources',
                            'name' => 'documents',
                            'type' => 'repeater',
                            'button_label' => 'Add Document',
                            'sub_fields' => array(
                                array(
                                    'key' => 'field_doc_file',
                                    'label' => 'File',
                                    'name' => 'document_file',
                                    'type' => 'file',
                                    'return_format' => 'array',
                                ),
                                array(
                                    'key' => 'field_doc_description',
                                    'label' => 'Description',
                                    'name' => 'document_description',
                                    'type' => 'text',
                                ),
                            ),
                        ),
                        array(
                            'key' => 'field_external_links',
                            'label' => 'External Links',
                            'name' => 'external_links',
                            'type' => 'repeater',
                            'button_label' => 'Add Link',
                            'sub_fields' => array(
                                array(
                                    'key' => 'field_link_title',
                                    'label' => 'Link Title',
                                    'name' => 'link_title',
                                    'type' => 'text',
                                ),
                                array(
                                    'key' => 'field_link_url',
                                    'label' => 'URL',
                                    'name' => 'link_url',
                                    'type' => 'url',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'community_post',
                    ),
                ),
            ),
            'menu_order' => 0,
            'position' => 'normal',
            'style' => 'default',
        ));
    }
    
    /**
     * Educational Resources Field Group
     */
    private function register_educational_resource_fields() {
        acf_add_local_field_group(array(
            'key' => 'group_edu_resource_fields',
            'title' => 'Educational Resource Details',
            'fields' => array(
                array(
                    'key' => 'field_resource_type',
                    'label' => 'Resource Type',
                    'name' => 'educational_resource_type',
                    'type' => 'select',
                    'choices' => array(
                        'guide' => 'How-to Guide',
                        'course' => 'Online Course',
                        'video' => 'Video Tutorial',
                        'infographic' => 'Infographic',
                        'toolkit' => 'Toolkit/Kit',
                        'research' => 'Research Paper',
                        'case_study' => 'Case Study',
                        'interactive' => 'Interactive Tool'
                    ),
                ),
                array(
                    'key' => 'field_education_level',
                    'label' => 'Education Level',
                    'name' => 'target_education_level',
                    'type' => 'select',
                    'choices' => array(
                        'elementary' => 'Elementary (6-11 years)',
                        'middle_school' => 'Middle School (12-14 years)',
                        'high_school' => 'High School (15-18 years)',
                        'university' => 'University/College',
                        'adult' => 'Adult Education',
                        'professional' => 'Professional Development',
                        'all_ages' => 'All Ages'
                    ),
                ),
                array(
                    'key' => 'field_learning_objectives',
                    'label' => 'Learning Objectives',
                    'name' => 'learning_objectives',
                    'type' => 'repeater',
                    'button_label' => 'Add Learning Objective',
                    'sub_fields' => array(
                        array(
                            'key' => 'field_objective_text',
                            'label' => 'Objective',
                            'name' => 'objective_text',
                            'type' => 'textarea',
                            'rows' => 2,
                        ),
                        array(
                            'key' => 'field_objective_level',
                            'label' => 'Cognitive Level',
                            'name' => 'objective_level',
                            'type' => 'select',
                            'choices' => array(
                                'remember' => 'Remember (Facts)',
                                'understand' => 'Understand (Concepts)',
                                'apply' => 'Apply (Procedures)',
                                'analyze' => 'Analyze (Elements)',
                                'evaluate' => 'Evaluate (Judgments)',
                                'create' => 'Create (Generate)'
                            ),
                        ),
                    ),
                ),
                array(
                    'key' => 'field_resource_content',
                    'label' => 'Resource Content Structure',
                    'name' => 'resource_content',
                    'type' => 'group',
                    'sub_fields' => array(
                        array(
                            'key' => 'field_estimated_duration',
                            'label' => 'Estimated Study Duration',
                            'name' => 'estimated_duration',
                            'type' => 'text',
                            'placeholder' => 'e.g., 30 minutes, 2 hours, 1 week',
                        ),
                        array(
                            'key' => 'field_difficulty_level',
                            'label' => 'Difficulty Level',
                            'name' => 'difficulty_level',
                            'type' => 'select',
                            'choices' => array(
                                'beginner' => 'Beginner',
                                'intermediate' => 'Intermediate',
                                'advanced' => 'Advanced',
                                'expert' => 'Expert'
                            ),
                        ),
                        array(
                            'key' => 'field_prerequisites',
                            'label' => 'Prerequisites',
                            'name' => 'prerequisites',
                            'type' => 'textarea',
                            'rows' => 3,
                        ),
                        array(
                            'key' => 'field_materials_needed',
                            'label' => 'Materials/Tools Needed',
                            'name' => 'materials_needed',
                            'type' => 'textarea',
                            'rows' => 3,
                        ),
                    ),
                ),
                array(
                    'key' => 'field_content_modules',
                    'label' => 'Content Modules/Sections',
                    'name' => 'content_modules',
                    'type' => 'repeater',
                    'button_label' => 'Add Module',
                    'sub_fields' => array(
                        array(
                            'key' => 'field_module_title',
                            'label' => 'Module Title',
                            'name' => 'module_title',
                            'type' => 'text',
                        ),
                        array(
                            'key' => 'field_module_content',
                            'label' => 'Module Content',
                            'name' => 'module_content',
                            'type' => 'wysiwyg',
                            'toolbar' => 'basic',
                        ),
                        array(
                            'key' => 'field_module_resources',
                            'label' => 'Module Resources',
                            'name' => 'module_resources',
                            'type' => 'repeater',
                            'button_label' => 'Add Resource',
                            'sub_fields' => array(
                                array(
                                    'key' => 'field_resource_name',
                                    'label' => 'Resource Name',
                                    'name' => 'resource_name',
                                    'type' => 'text',
                                ),
                                array(
                                    'key' => 'field_resource_type_detail',
                                    'label' => 'Type',
                                    'name' => 'resource_type_detail',
                                    'type' => 'select',
                                    'choices' => array(
                                        'video' => 'Video',
                                        'document' => 'Document',
                                        'link' => 'External Link',
                                        'download' => 'Download',
                                        'quiz' => 'Quiz/Assessment'
                                    ),
                                ),
                                array(
                                    'key' => 'field_resource_file',
                                    'label' => 'File/URL',
                                    'name' => 'resource_file',
                                    'type' => 'text',
                                ),
                            ),
                        ),
                    ),
                ),
                array(
                    'key' => 'field_assessment',
                    'label' => 'Assessment & Evaluation',
                    'name' => 'assessment',
                    'type' => 'group',
                    'sub_fields' => array(
                        array(
                            'key' => 'field_has_quiz',
                            'label' => 'Includes Quiz/Assessment',
                            'name' => 'has_quiz',
                            'type' => 'true_false',
                        ),
                        array(
                            'key' => 'field_quiz_questions',
                            'label' => 'Quiz Questions',
                            'name' => 'quiz_questions',
                            'type' => 'repeater',
                            'button_label' => 'Add Question',
                            'conditional_logic' => array(
                                array(
                                    array(
                                        'field' => 'field_has_quiz',
                                        'operator' => '==',
                                        'value' => '1',
                                    ),
                                ),
                            ),
                            'sub_fields' => array(
                                array(
                                    'key' => 'field_question_text',
                                    'label' => 'Question',
                                    'name' => 'question_text',
                                    'type' => 'textarea',
                                    'rows' => 2,
                                ),
                                array(
                                    'key' => 'field_question_type',
                                    'label' => 'Question Type',
                                    'name' => 'question_type',
                                    'type' => 'select',
                                    'choices' => array(
                                        'multiple_choice' => 'Multiple Choice',
                                        'true_false' => 'True/False',
                                        'short_answer' => 'Short Answer',
                                        'essay' => 'Essay'
                                    ),
                                ),
                                array(
                                    'key' => 'field_correct_answer',
                                    'label' => 'Correct Answer',
                                    'name' => 'correct_answer',
                                    'type' => 'text',
                                ),
                            ),
                        ),
                        array(
                            'key' => 'field_certificate_available',
                            'label' => 'Certificate Available',
                            'name' => 'certificate_available',
                            'type' => 'true_false',
                        ),
                    ),
                ),
                array(
                    'key' => 'field_resource_metadata',
                    'label' => 'Resource Metadata',
                    'name' => 'resource_metadata',
                    'type' => 'group',
                    'sub_fields' => array(
                        array(
                            'key' => 'field_author_credentials',
                            'label' => 'Author Credentials',
                            'name' => 'author_credentials',
                            'type' => 'textarea',
                            'rows' => 3,
                        ),
                        array(
                            'key' => 'field_last_updated',
                            'label' => 'Last Updated',
                            'name' => 'content_last_updated',
                            'type' => 'date_picker',
                        ),
                        array(
                            'key' => 'field_language_available',
                            'label' => 'Languages Available',
                            'name' => 'languages_available',
                            'type' => 'checkbox',
                            'choices' => array(
                                'vietnamese' => 'Vietnamese',
                                'english' => 'English',
                                'mandarin' => 'Mandarin',
                                'french' => 'French',
                                'spanish' => 'Spanish'
                            ),
                        ),
                        array(
                            'key' => 'field_usage_rights',
                            'label' => 'Usage Rights',
                            'name' => 'usage_rights',
                            'type' => 'select',
                            'choices' => array(
                                'public_domain' => 'Public Domain',
                                'creative_commons' => 'Creative Commons',
                                'educational_use' => 'Educational Use Only',
                                'restricted' => 'Restricted Use'
                            ),
                        ),
                    ),
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'edu_resource',
                    ),
                ),
            ),
            'menu_order' => 0,
            'position' => 'normal',
            'style' => 'default',
        ));
    }
    
    /**
     * Waste Classification Field Group
     */
    private function register_waste_classification_fields() {
        acf_add_local_field_group(array(
            'key' => 'group_waste_class_fields',
            'title' => 'Waste Classification Information',
            'fields' => array(
                array(
                    'key' => 'field_waste_category',
                    'label' => 'Waste Category',
                    'name' => 'waste_category',
                    'type' => 'select',
                    'choices' => array(
                        'organic' => 'Organic Waste',
                        'recyclable' => 'Recyclable Materials',
                        'hazardous' => 'Hazardous Waste',
                        'electronic' => 'Electronic Waste',
                        'construction' => 'Construction Waste',
                        'medical' => 'Medical Waste',
                        'industrial' => 'Industrial Waste',
                        'general' => 'General Waste'
                    ),
                ),
                array(
                    'key' => 'field_waste_properties',
                    'label' => 'Waste Properties',
                    'name' => 'waste_properties',
                    'type' => 'group',
                    'sub_fields' => array(
                        array(
                            'key' => 'field_biodegradable',
                            'label' => 'Biodegradable',
                            'name' => 'is_biodegradable',
                            'type' => 'true_false',
                        ),
                        array(
                            'key' => 'field_recyclable',
                            'label' => 'Recyclable',
                            'name' => 'is_recyclable',
                            'type' => 'true_false',
                        ),
                        array(
                            'key' => 'field_compostable',
                            'label' => 'Compostable',
                            'name' => 'is_compostable',
                            'type' => 'true_false',
                        ),
                        array(
                            'key' => 'field_toxic',
                            'label' => 'Contains Toxic Materials',
                            'name' => 'contains_toxic',
                            'type' => 'true_false',
                        ),
                        array(
                            'key' => 'field_decomposition_time',
                            'label' => 'Decomposition Time',
                            'name' => 'decomposition_time',
                            'type' => 'text',
                            'placeholder' => 'e.g., 2-5 weeks, 100-1000 years',
                        ),
                    ),
                ),
                array(
                    'key' => 'field_disposal_methods',
                    'label' => 'Proper Disposal Methods',
                    'name' => 'disposal_methods',
                    'type' => 'repeater',
                    'button_label' => 'Add Disposal Method',
                    'sub_fields' => array(
                        array(
                            'key' => 'field_method_name',
                            'label' => 'Method Name',
                            'name' => 'disposal_method_name',
                            'type' => 'text',
                        ),
                        array(
                            'key' => 'field_method_description',
                            'label' => 'Method Description',
                            'name' => 'method_description',
                            'type' => 'textarea',
                            'rows' => 3,
                        ),
                        array(
                            'key' => 'field_method_effectiveness',
                            'label' => 'Environmental Effectiveness',
                            'name' => 'method_effectiveness',
                            'type' => 'select',
                            'choices' => array(
                                'excellent' => 'Excellent',
                                'good' => 'Good',
                                'fair' => 'Fair',
                                'poor' => 'Poor'
                            ),
                        ),
                        array(
                            'key' => 'field_method_availability',
                            'label' => 'Availability in Vietnam',
                            'name' => 'method_availability',
                            'type' => 'select',
                            'choices' => array(
                                'widely_available' => 'Widely Available',
                                'major_cities' => 'Major Cities Only',
                                'limited' => 'Limited Availability',
                                'not_available' => 'Not Available'
                            ),
                        ),
                    ),
                ),
                array(
                    'key' => 'field_recycling_info',
                    'label' => 'Recycling Information',
                    'name' => 'recycling_info',
                    'type' => 'group',
                    'conditional_logic' => array(
                        array(
                            array(
                                'field' => 'field_recyclable',
                                'operator' => '==',
                                'value' => '1',
                            ),
                        ),
                    ),
                    'sub_fields' => array(
                        array(
                            'key' => 'field_recycling_process',
                            'label' => 'Recycling Process',
                            'name' => 'recycling_process',
                            'type' => 'textarea',
                            'rows' => 4,
                        ),
                        array(
                            'key' => 'field_recycled_products',
                            'label' => 'What It Becomes After Recycling',
                            'name' => 'recycled_products',
                            'type' => 'textarea',
                            'rows' => 3,
                        ),
                        array(
                            'key' => 'field_recycling_centers',
                            'label' => 'Recycling Centers in Vietnam',
                            'name' => 'recycling_centers',
                            'type' => 'repeater',
                            'button_label' => 'Add Center',
                            'sub_fields' => array(
                                array(
                                    'key' => 'field_center_name',
                                    'label' => 'Center Name',
                                    'name' => 'center_name',
                                    'type' => 'text',
                                ),
                                array(
                                    'key' => 'field_center_location',
                                    'label' => 'Location',
                                    'name' => 'center_location',
                                    'type' => 'text',
                                ),
                                array(
                                    'key' => 'field_center_contact',
                                    'label' => 'Contact Information',
                                    'name' => 'center_contact',
                                    'type' => 'text',
                                ),
                            ),
                        ),
                    ),
                ),
                array(
                    'key' => 'field_environmental_impact',
                    'label' => 'Environmental Impact Assessment',
                    'name' => 'environmental_impact_assessment',
                    'type' => 'group',
                    'sub_fields' => array(
                        array(
                            'key' => 'field_carbon_footprint_waste',
                            'label' => 'Carbon Footprint (kg CO2e per ton)',
                            'name' => 'carbon_footprint_per_ton',
                            'type' => 'number',
                            'step' => 0.01,
                        ),
                        array(
                            'key' => 'field_landfill_impact',
                            'label' => 'Landfill Impact Score (1-10)',
                            'name' => 'landfill_impact_score',
                            'type' => 'range',
                            'min' => 1,
                            'max' => 10,
                            'step' => 1,
                        ),
                        array(
                            'key' => 'field_water_pollution_risk',
                            'label' => 'Water Pollution Risk',
                            'name' => 'water_pollution_risk',
                            'type' => 'select',
                            'choices' => array(
                                'none' => 'No Risk',
                                'low' => 'Low Risk',
                                'moderate' => 'Moderate Risk',
                                'high' => 'High Risk',
                                'severe' => 'Severe Risk'
                            ),
                        ),
                        array(
                            'key' => 'field_soil_contamination_risk',
                            'label' => 'Soil Contamination Risk',
                            'name' => 'soil_contamination_risk',
                            'type' => 'select',
                            'choices' => array(
                                'none' => 'No Risk',
                                'low' => 'Low Risk',
                                'moderate' => 'Moderate Risk',
                                'high' => 'High Risk',
                                'severe' => 'Severe Risk'
                            ),
                        ),
                    ),
                ),
                array(
                    'key' => 'field_prevention_tips',
                    'label' => 'Waste Prevention Tips',
                    'name' => 'waste_prevention_tips',
                    'type' => 'repeater',
                    'button_label' => 'Add Prevention Tip',
                    'sub_fields' => array(
                        array(
                            'key' => 'field_tip_title',
                            'label' => 'Tip Title',
                            'name' => 'prevention_tip_title',
                            'type' => 'text',
                        ),
                        array(
                            'key' => 'field_tip_description',
                            'label' => 'Tip Description',
                            'name' => 'tip_description',
                            'type' => 'textarea',
                            'rows' => 3,
                        ),
                        array(
                            'key' => 'field_tip_difficulty',
                            'label' => 'Implementation Difficulty',
                            'name' => 'tip_difficulty',
                            'type' => 'select',
                            'choices' => array(
                                'very_easy' => 'Very Easy',
                                'easy' => 'Easy',
                                'moderate' => 'Moderate',
                                'challenging' => 'Challenging'
                            ),
                        ),
                    ),
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'waste_class',
                    ),
                ),
            ),
            'menu_order' => 0,
            'position' => 'normal',
            'style' => 'default',
        ));
    }
    
    /**
     * Petition Fields Group
     */
    private function register_petition_fields() {
        acf_add_local_field_group(array(
            'key' => 'group_petition_fields',
            'title' => 'Petition Information',
            'fields' => array(
                array(
                    'key' => 'field_petition_target',
                    'label' => 'Petition Target',
                    'name' => 'petition_target',
                    'type' => 'group',
                    'sub_fields' => array(
                        array(
                            'key' => 'field_target_organization',
                            'label' => 'Target Organization/Authority',
                            'name' => 'target_organization',
                            'type' => 'text',
                        ),
                        array(
                            'key' => 'field_target_level',
                            'label' => 'Target Level',
                            'name' => 'target_level',
                            'type' => 'select',
                            'choices' => array(
                                'local' => 'Local Government',
                                'provincial' => 'Provincial Government',
                                'national' => 'National Government',
                                'international' => 'International Organization',
                                'corporate' => 'Corporate/Business',
                                'institutional' => 'Educational Institution'
                            ),
                        ),
                        array(
                            'key' => 'field_target_contact',
                            'label' => 'Target Contact Information',
                            'name' => 'target_contact',
                            'type' => 'textarea',
                            'rows' => 3,
                        ),
                    ),
                ),
                array(
                    'key' => 'field_petition_goals',
                    'label' => 'Petition Goals',
                    'name' => 'petition_goals',
                    'type' => 'group',
                    'sub_fields' => array(
                        array(
                            'key' => 'field_signature_goal',
                            'label' => 'Signature Goal',
                            'name' => 'signature_goal',
                            'type' => 'number',
                        ),
                        array(
                            'key' => 'field_current_signatures',
                            'label' => 'Current Signatures',
                            'name' => 'current_signatures',
                            'type' => 'number',
                            'default_value' => 0,
                        ),
                        array(
                            'key' => 'field_deadline',
                            'label' => 'Petition Deadline',
                            'name' => 'petition_deadline',
                            'type' => 'date_picker',
                        ),
                        array(
                            'key' => 'field_desired_outcome',
                            'label' => 'Desired Outcome',
                            'name' => 'desired_outcome',
                            'type' => 'textarea',
                            'rows' => 4,
                        ),
                    ),
                ),
                array(
                    'key' => 'field_petition_background',
                    'label' => 'Background Information',
                    'name' => 'petition_background',
                    'type' => 'group',
                    'sub_fields' => array(
                        array(
                            'key' => 'field_problem_statement',
                            'label' => 'Problem Statement',
                            'name' => 'problem_statement',
                            'type' => 'wysiwyg',
                            'toolbar' => 'basic',
                        ),
                        array(
                            'key' => 'field_supporting_evidence',
                            'label' => 'Supporting Evidence',
                            'name' => 'supporting_evidence',
                            'type' => 'repeater',
                            'button_label' => 'Add Evidence',
                            'sub_fields' => array(
                                array(
                                    'key' => 'field_evidence_type',
                                    'label' => 'Evidence Type',
                                    'name' => 'evidence_type',
                                    'type' => 'select',
                                    'choices' => array(
                                        'scientific_study' => 'Scientific Study',
                                        'government_data' => 'Government Data',
                                        'news_report' => 'News Report',
                                        'expert_opinion' => 'Expert Opinion',
                                        'community_testimony' => 'Community Testimony',
                                        'legal_precedent' => 'Legal Precedent'
                                    ),
                                ),
                                array(
                                    'key' => 'field_evidence_title',
                                    'label' => 'Evidence Title',
                                    'name' => 'evidence_title',
                                    'type' => 'text',
                                ),
                                array(
                                    'key' => 'field_evidence_link',
                                    'label' => 'Link/Reference',
                                    'name' => 'evidence_link',
                                    'type' => 'url',
                                ),
                                array(
                                    'key' => 'field_evidence_summary',
                                    'label' => 'Summary',
                                    'name' => 'evidence_summary',
                                    'type' => 'textarea',
                                    'rows' => 3,
                                ),
                            ),
                        ),
                    ),
                ),
                array(
                    'key' => 'field_petition_organizers',
                    'label' => 'Petition Organizers',
                    'name' => 'petition_organizers',
                    'type' => 'repeater',
                    'button_label' => 'Add Organizer',
                    'sub_fields' => array(
                        array(
                            'key' => 'field_organizer_name_petition',
                            'label' => 'Name',
                            'name' => 'organizer_name',
                            'type' => 'text',
                        ),
                        array(
                            'key' => 'field_organizer_title',
                            'label' => 'Title/Position',
                            'name' => 'organizer_title',
                            'type' => 'text',
                        ),
                        array(
                            'key' => 'field_organizer_organization_petition',
                            'label' => 'Organization',
                            'name' => 'organizer_organization',
                            'type' => 'text',
                        ),
                        array(
                            'key' => 'field_organizer_bio',
                            'label' => 'Bio/Credentials',
                            'name' => 'organizer_bio',
                            'type' => 'textarea',
                            'rows' => 3,
                        ),
                    ),
                ),
                array(
                    'key' => 'field_petition_updates',
                    'label' => 'Petition Updates',
                    'name' => 'petition_updates',
                    'type' => 'repeater',
                    'button_label' => 'Add Update',
                    'sub_fields' => array(
                        array(
                            'key' => 'field_update_date',
                            'label' => 'Update Date',
                            'name' => 'update_date',
                            'type' => 'date_picker',
                        ),
                        array(
                            'key' => 'field_update_title',
                            'label' => 'Update Title',
                            'name' => 'update_title',
                            'type' => 'text',
                        ),
                        array(
                            'key' => 'field_update_content',
                            'label' => 'Update Content',
                            'name' => 'update_content',
                            'type' => 'wysiwyg',
                            'toolbar' => 'basic',
                        ),
                        array(
                            'key' => 'field_milestone_reached',
                            'label' => 'Milestone Reached',
                            'name' => 'milestone_reached',
                            'type' => 'true_false',
                        ),
                   
                    ),
                ),
                array(
                    'key' => 'field_petition_status',
                    'label' => 'Petition Status & Progress',
                    'name' => 'petition_status_progress',
                    'type' => 'group',
                    'sub_fields' => array(
                        array(
                            'key' => 'field_petition_status_value',
                            'label' => 'Current Status',
                            'name' => 'petition_status',
                            'type' => 'select',
                            'choices' => array(
                                'active' => 'Active - Collecting Signatures',
                                'submitted' => 'Submitted to Target',
                                'under_review' => 'Under Review',
                                'responded' => 'Response Received',
                                'successful' => 'Successful - Goal Achieved',
                                'unsuccessful' => 'Unsuccessful',
                                'ongoing' => 'Ongoing Campaign'
                            ),
                        ),
                        array(
                            'key' => 'field_success_metrics',
                            'label' => 'Success Metrics',
                            'name' => 'success_metrics_petition',
                            'type' => 'group',
                            'sub_fields' => array(
                                array(
                                    'key' => 'field_media_coverage',
                                    'label' => 'Media Coverage Count',
                                    'name' => 'media_coverage_count',
                                    'type' => 'number',
                                ),
                                array(
                                    'key' => 'field_social_shares',
                                    'label' => 'Social Media Shares',
                                    'name' => 'social_media_shares',
                                    'type' => 'number',
                                ),
                                array(
                                    'key' => 'field_official_response',
                                    'label' => 'Official Response Received',
                                    'name' => 'official_response_received',
                                    'type' => 'true_false',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'env_petition',
                    ),
                ),
            ),
            'menu_order' => 0,
            'position' => 'normal',
            'style' => 'default',        ));
    }
    
    /**
     * Item Exchange Field Group
     */
    private function register_item_exchange_fields() {
        acf_add_local_field_group(array(
            'key' => 'group_item_exchange',
            'title' => 'Item Exchange Details',
            'fields' => array(
                // Item Information
                array(
                    'key' => 'field_exchange_item_details',
                    'label' => 'Item Information',
                    'name' => 'exchange_item_details',
                    'type' => 'group',
                    'sub_fields' => array(
                        array(
                            'key' => 'field_item_condition',
                            'label' => 'Item Condition',
                            'name' => 'item_condition',
                            'type' => 'select',
                            'required' => 1,
                            'choices' => array(
                                'new' => 'New',
                                'like_new' => 'Like New',
                                'good' => 'Good',
                                'fair' => 'Fair',
                                'needs_repair' => 'Needs Repair'
                            ),
                        ),
                        array(
                            'key' => 'field_item_category',
                            'label' => 'Item Category',
                            'name' => 'item_category',
                            'type' => 'select',
                            'required' => 1,
                            'choices' => array(
                                'electronics' => 'Electronics',
                                'furniture' => 'Furniture',
                                'clothing' => 'Clothing',
                                'books' => 'Books',
                                'tools' => 'Tools',
                                'appliances' => 'Appliances',
                                'sports' => 'Sports Equipment',
                                'toys' => 'Toys & Games',
                                'other' => 'Other'
                            ),
                        ),
                        array(
                            'key' => 'field_item_value',
                            'label' => 'Estimated Value ($)',
                            'name' => 'item_estimated_value',
                            'type' => 'number',
                            'min' => 0,
                        ),
                        array(
                            'key' => 'field_item_images',
                            'label' => 'Item Images',
                            'name' => 'item_images',
                            'type' => 'gallery',
                            'max' => 10,
                            'preview_size' => 'medium',
                        ),
                    ),
                ),
                
                // Exchange Details
                array(
                    'key' => 'field_exchange_details',
                    'label' => 'Exchange Information',
                    'name' => 'exchange_details',
                    'type' => 'group',
                    'sub_fields' => array(
                        array(
                            'key' => 'field_exchange_type',
                            'label' => 'Exchange Type',
                            'name' => 'exchange_type',
                            'type' => 'select',
                            'required' => 1,
                            'choices' => array(
                                'give_away' => 'Give Away (Free)',
                                'trade' => 'Trade for Other Item',
                                'lend' => 'Lend (Temporary)',
                                'sell' => 'Sell'
                            ),
                        ),
                        array(
                            'key' => 'field_wanted_items',
                            'label' => 'Wanted in Exchange',
                            'name' => 'wanted_items',
                            'type' => 'textarea',
                            'conditional_logic' => array(
                                array(
                                    array(
                                        'field' => 'field_exchange_type',
                                        'operator' => '==',
                                        'value' => 'trade',
                                    ),
                                ),
                            ),
                        ),
                        array(
                            'key' => 'field_selling_price',
                            'label' => 'Selling Price ($)',
                            'name' => 'selling_price',
                            'type' => 'number',
                            'min' => 0,
                            'conditional_logic' => array(
                                array(
                                    array(
                                        'field' => 'field_exchange_type',
                                        'operator' => '==',
                                        'value' => 'sell',
                                    ),
                                ),
                            ),
                        ),
                        array(
                            'key' => 'field_lending_duration',
                            'label' => 'Lending Duration',
                            'name' => 'lending_duration',
                            'type' => 'select',
                            'choices' => array(
                                '1_week' => '1 Week',
                                '2_weeks' => '2 Weeks',
                                '1_month' => '1 Month',
                                '3_months' => '3 Months',
                                'negotiable' => 'Negotiable'
                            ),
                            'conditional_logic' => array(
                                array(
                                    array(
                                        'field' => 'field_exchange_type',
                                        'operator' => '==',
                                        'value' => 'lend',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                
                // Location & Pickup
                array(
                    'key' => 'field_pickup_details',
                    'label' => 'Pickup & Location',
                    'name' => 'pickup_details',
                    'type' => 'group',
                    'sub_fields' => array(
                        array(
                            'key' => 'field_pickup_location',
                            'label' => 'Pickup Location',
                            'name' => 'pickup_location',
                            'type' => 'text',
                            'required' => 1,
                        ),
                        array(
                            'key' => 'field_delivery_available',
                            'label' => 'Delivery Available',
                            'name' => 'delivery_available',
                            'type' => 'true_false',
                        ),
                        array(
                            'key' => 'field_delivery_radius',
                            'label' => 'Delivery Radius (km)',
                            'name' => 'delivery_radius',
                            'type' => 'number',
                            'min' => 0,
                            'conditional_logic' => array(
                                array(
                                    array(
                                        'field' => 'field_delivery_available',
                                        'operator' => '==',
                                        'value' => '1',
                                    ),
                                ),
                            ),
                        ),
                        array(
                            'key' => 'field_available_times',
                            'label' => 'Available Times',
                            'name' => 'available_times',
                            'type' => 'textarea',
                            'placeholder' => 'e.g., Weekends, Evenings after 6pm',
                        ),
                    ),
                ),
                
                // Environmental Impact
                array(
                    'key' => 'field_environmental_impact_exchange',
                    'label' => 'Environmental Impact',
                    'name' => 'environmental_impact_exchange',
                    'type' => 'group',
                    'sub_fields' => array(
                        array(
                            'key' => 'field_waste_diverted',
                            'label' => 'Estimated Waste Diverted (kg)',
                            'name' => 'waste_diverted_kg',
                            'type' => 'number',
                            'min' => 0,
                            'step' => 0.1,
                        ),
                        array(
                            'key' => 'field_carbon_saved',
                            'label' => 'Estimated CO2 Saved (kg)',
                            'name' => 'co2_saved_kg',
                            'type' => 'number',
                            'min' => 0,
                            'step' => 0.1,
                        ),
                        array(
                            'key' => 'field_circular_economy_contribution',
                            'label' => 'Circular Economy Contribution',
                            'name' => 'circular_economy_contribution',
                            'type' => 'range',
                            'min' => 1,
                            'max' => 10,
                            'default_value' => 5,
                        ),
                    ),
                ),
                
                // Exchange Status
                array(
                    'key' => 'field_exchange_status',
                    'label' => 'Exchange Status',
                    'name' => 'exchange_status',
                    'type' => 'select',
                    'choices' => array(
                        'available' => 'Available',
                        'pending' => 'Pending Exchange',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled'
                    ),
                    'default_value' => 'available',
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'item_exchange',
                    ),
                ),
            ),
            'menu_order' => 0,
            'position' => 'normal',
            'style' => 'default',
        ));
    }
    
    /**
     * Global Environmental Fields (shared across post types)
     */
    private function register_global_environmental_fields() {
        acf_add_local_field_group(array(
            'key' => 'group_global_environmental',
            'title' => 'Global Environmental Data',
            'fields' => array(
                // Sustainability Metrics
                array(
                    'key' => 'field_sustainability_metrics',
                    'label' => 'Sustainability Metrics',
                    'name' => 'sustainability_metrics',
                    'type' => 'group',
                    'sub_fields' => array(
                        array(
                            'key' => 'field_carbon_footprint',
                            'label' => 'Carbon Footprint (kg CO2)',
                            'name' => 'carbon_footprint_kg',
                            'type' => 'number',
                            'min' => 0,
                            'step' => 0.01,
                        ),
                        array(
                            'key' => 'field_water_usage',
                            'label' => 'Water Usage (liters)',
                            'name' => 'water_usage_liters',
                            'type' => 'number',
                            'min' => 0,
                            'step' => 0.1,
                        ),
                        array(
                            'key' => 'field_energy_consumption',
                            'label' => 'Energy Consumption (kWh)',
                            'name' => 'energy_consumption_kwh',
                            'type' => 'number',
                            'min' => 0,
                            'step' => 0.01,
                        ),
                        array(
                            'key' => 'field_renewable_energy_percentage',
                            'label' => 'Renewable Energy %',
                            'name' => 'renewable_energy_percentage',
                            'type' => 'range',
                            'min' => 0,
                            'max' => 100,
                            'default_value' => 0,
                        ),
                    ),
                ),
                
                // Environmental Certifications
                array(
                    'key' => 'field_environmental_certifications',
                    'label' => 'Environmental Certifications',
                    'name' => 'environmental_certifications',
                    'type' => 'repeater',
                    'button_label' => 'Add Certification',
                    'sub_fields' => array(
                        array(
                            'key' => 'field_certification_name',
                            'label' => 'Certification Name',
                            'name' => 'certification_name',
                            'type' => 'select',
                            'choices' => array(
                                'iso14001' => 'ISO 14001',
                                'leed' => 'LEED Certified',
                                'energy_star' => 'Energy Star',
                                'cradle_to_cradle' => 'Cradle to Cradle',
                                'fair_trade' => 'Fair Trade',
                                'organic' => 'Organic Certified',
                                'carbon_neutral' => 'Carbon Neutral',
                                'b_corp' => 'B Corporation',
                                'other' => 'Other'
                            ),
                        ),
                        array(
                            'key' => 'field_certification_level',
                            'label' => 'Certification Level',
                            'name' => 'certification_level',
                            'type' => 'text',
                        ),
                        array(
                            'key' => 'field_certification_date',
                            'label' => 'Certification Date',
                            'name' => 'certification_date',
                            'type' => 'date_picker',
                            'display_format' => 'd/m/Y',
                            'return_format' => 'Y-m-d',
                        ),
                        array(
                            'key' => 'field_certification_expiry',
                            'label' => 'Expiry Date',
                            'name' => 'certification_expiry',
                            'type' => 'date_picker',
                            'display_format' => 'd/m/Y',
                            'return_format' => 'Y-m-d',
                        ),
                    ),
                ),
                
                // UN SDG Alignment
                array(
                    'key' => 'field_un_sdg_alignment',
                    'label' => 'UN SDG Alignment',
                    'name' => 'un_sdg_alignment',
                    'type' => 'checkbox',
                    'choices' => array(
                        'sdg1' => '1. No Poverty',
                        'sdg2' => '2. Zero Hunger',
                        'sdg3' => '3. Good Health and Well-being',
                        'sdg4' => '4. Quality Education',
                        'sdg5' => '5. Gender Equality',
                        'sdg6' => '6. Clean Water and Sanitation',
                        'sdg7' => '7. Affordable and Clean Energy',
                        'sdg8' => '8. Decent Work and Economic Growth',
                        'sdg9' => '9. Industry, Innovation and Infrastructure',
                        'sdg10' => '10. Reduced Inequalities',
                        'sdg11' => '11. Sustainable Cities and Communities',
                        'sdg12' => '12. Responsible Consumption and Production',
                        'sdg13' => '13. Climate Action',
                        'sdg14' => '14. Life Below Water',
                        'sdg15' => '15. Life on Land',
                        'sdg16' => '16. Peace, Justice and Strong Institutions',
                        'sdg17' => '17. Partnerships for the Goals'
                    ),
                ),
                
                // Environmental Tags
                array(
                    'key' => 'field_environmental_tags',
                    'label' => 'Environmental Tags',
                    'name' => 'environmental_tags',
                    'type' => 'select',
                    'multiple' => 1,
                    'choices' => array(
                        'zero_waste' => 'Zero Waste',
                        'circular_economy' => 'Circular Economy',
                        'renewable_energy' => 'Renewable Energy',
                        'biodiversity' => 'Biodiversity',
                        'ocean_conservation' => 'Ocean Conservation',
                        'forest_conservation' => 'Forest Conservation',
                        'sustainable_agriculture' => 'Sustainable Agriculture',
                        'green_technology' => 'Green Technology',
                        'climate_resilience' => 'Climate Resilience',
                        'environmental_justice' => 'Environmental Justice'
                    ),
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'env_article',
                    ),
                ),
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'env_report',
                    ),
                ),
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'env_project',
                    ),
                ),
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'eco_product',
                    ),
                ),
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'community_post',
                    ),
                ),
            ),
            'menu_order' => 10,
            'position' => 'side',
            'style' => 'default',
        ));
    }
      /**
     * Save environmental data when ACF fields are updated
     */
    public function save_environmental_data($post_id) {
        // Prevent infinite loops
        if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
            return;
        }
        
        $post_type = get_post_type($post_id);
        
        // Custom save logic for environmental data
        switch ($post_type) {
            case 'env_article':
                $this->save_article_environmental_data($post_id);
                break;
            case 'env_project':
                $this->save_project_environmental_data($post_id);
                break;
            case 'eco_product':
                $this->save_product_environmental_data($post_id);
                break;
            case 'item_exchange':
                $this->save_exchange_environmental_data($post_id);
                break;
        }
        
        // Update global environmental scores
        $this->update_global_environmental_scores($post_id);
        
        // Sync with custom database tables if needed
        do_action('ep_acf_save_post', $post_id);
    }
    
    /**
     * Save article environmental data
     */
    private function save_article_environmental_data($post_id) {
        $impact_score = get_field('environmental_impact_score', $post_id);
        if ($impact_score) {
            update_post_meta($post_id, '_environmental_score', $impact_score);
        }
        
        // Calculate carbon data significance
        $carbon_data = get_field('carbon_footprint_data', $post_id);
        if ($carbon_data && is_array($carbon_data)) {
            $total_carbon = 0;
            foreach ($carbon_data as $data) {
                if (isset($data['carbon_amount'])) {
                    $total_carbon += floatval($data['carbon_amount']);
                }
            }
            update_post_meta($post_id, '_total_carbon_impact', $total_carbon);
        }
    }
    
    /**
     * Save project environmental data
     */
    private function save_project_environmental_data($post_id) {
        $impact_metrics = get_field('environmental_impact_metrics', $post_id);
        if ($impact_metrics && is_array($impact_metrics)) {
            // Calculate overall project impact score
            $total_score = 0;
            $metric_count = 0;
            
            foreach ($impact_metrics as $metric) {
                if (isset($metric['baseline_value']) && isset($metric['target_value'])) {
                    $improvement = floatval($metric['target_value']) - floatval($metric['baseline_value']);
                    $score = ($improvement > 0) ? min(10, $improvement / floatval($metric['baseline_value']) * 10) : 0;
                    $total_score += $score;
                    $metric_count++;
                }
            }
            
            if ($metric_count > 0) {
                $average_score = $total_score / $metric_count;
                update_post_meta($post_id, '_project_environmental_score', $average_score);
            }
        }
    }
    
    /**
     * Save product environmental data
     */
    private function save_product_environmental_data($post_id) {
        $sustainability_score = get_field('sustainability_score', $post_id);
        if ($sustainability_score) {
            update_post_meta($post_id, '_product_sustainability_score', $sustainability_score);
        }
        
        // Update product eco-rating based on lifecycle assessment
        $lifecycle = get_field('product_lifecycle', $post_id);
        if ($lifecycle && is_array($lifecycle)) {
            $eco_rating = $this->calculate_product_eco_rating($lifecycle);
            update_post_meta($post_id, '_product_eco_rating', $eco_rating);
        }
    }
    
    /**
     * Save exchange environmental data
     */
    private function save_exchange_environmental_data($post_id) {
        $waste_diverted = get_field('waste_diverted_kg', $post_id);
        $co2_saved = get_field('co2_saved_kg', $post_id);
        
        if ($waste_diverted && $co2_saved) {
            $environmental_impact = ($waste_diverted * 0.5) + ($co2_saved * 2); // Custom formula
            update_post_meta($post_id, '_exchange_environmental_impact', $environmental_impact);
        }
    }
    
    /**
     * Update global environmental scores
     */
    private function update_global_environmental_scores($post_id) {
        $carbon_footprint = get_field('carbon_footprint_kg', $post_id);
        $water_usage = get_field('water_usage_liters', $post_id);
        $energy_consumption = get_field('energy_consumption_kwh', $post_id);
        $renewable_percentage = get_field('renewable_energy_percentage', $post_id);
        
        // Calculate overall environmental score
        $score = 100; // Start with perfect score
        
        // Deduct points based on environmental impact
        if ($carbon_footprint) {
            $score -= min(30, $carbon_footprint / 100); // Max 30 points deduction for carbon
        }
        
        if ($water_usage) {
            $score -= min(20, $water_usage / 1000); // Max 20 points deduction for water
        }
        
        if ($energy_consumption) {
            $score -= min(25, $energy_consumption / 50); // Max 25 points deduction for energy
        }
        
        // Add points for renewable energy usage
        if ($renewable_percentage) {
            $score += ($renewable_percentage / 100) * 25; // Up to 25 bonus points
        }
        
        $score = max(0, min(100, $score)); // Ensure score is between 0-100
        update_post_meta($post_id, '_global_environmental_score', $score);
    }
    
    /**
     * Calculate product eco-rating based on lifecycle assessment
     */
    private function calculate_product_eco_rating($lifecycle) {
        $rating = 5; // Start with average rating
        
        foreach ($lifecycle as $phase) {
            if (isset($phase['environmental_impact']) && isset($phase['sustainability_measures'])) {
                $impact = intval($phase['environmental_impact']);
                $measures = count($phase['sustainability_measures']);
                
                // Higher impact reduces rating, more measures increase it
                $phase_score = (10 - $impact) + ($measures * 0.5);
                $rating += ($phase_score - 5) * 0.2; // Adjust overall rating
            }
        }
        
        return max(1, min(10, round($rating, 1)));
    }
    
    /**
     * Export ACF field groups to PHP for version control
     */
    public function export_field_groups_to_php() {
        if (!current_user_can('manage_options')) {
            return false;
        }
        
        $field_groups = acf_get_local_field_groups();
        $export_code = "<?php\n/**\n * Environmental Platform ACF Field Groups Export\n * Generated on: " . date('Y-m-d H:i:s') . "\n */\n\n";
        $export_code .= "if (!defined('ABSPATH')) {\n    exit;\n}\n\n";
        $export_code .= "if (function_exists('acf_add_local_field_group')) {\n\n";
        
        foreach ($field_groups as $field_group) {
            if (strpos($field_group['key'], 'group_') === 0) {
                $export_code .= "    // " . $field_group['title'] . "\n";
                $export_code .= "    acf_add_local_field_group(" . var_export($field_group, true) . ");\n\n";
            }
        }
        
        $export_code .= "}\n";
        
        // Save to file
        $upload_dir = wp_upload_dir();
        $export_file = $upload_dir['basedir'] . '/acf-field-groups-export-' . date('Y-m-d-H-i-s') . '.php';
        
        if (file_put_contents($export_file, $export_code)) {
            return $export_file;
        }
        
        return false;
    }
    
    /**
     * Import ACF field groups from PHP file
     */
    public function import_field_groups_from_php($file_path) {
        if (!current_user_can('manage_options') || !file_exists($file_path)) {
            return false;
        }
        
        // Include the PHP file to register field groups
        include_once $file_path;
        
        return true;
    }
    
    /**
     * Add custom location rules for ACF
     */
    public function add_location_rules($choices) {
        $choices['Environmental']['environmental_impact'] = 'Environmental Impact Level';
        $choices['Environmental']['sustainability_score'] = 'Sustainability Score';
        return $choices;
    }
    
    /**
     * Enqueue ACF-specific scripts
     */
    public function enqueue_acf_scripts() {
        wp_enqueue_script(
            'ep-acf-admin',
            EP_CORE_PLUGIN_URL . 'assets/acf-admin.js',
            array('jquery', 'acf-input'),
            EP_CORE_VERSION,
            true
        );
        
        wp_enqueue_style(
            'ep-acf-admin',
            EP_CORE_PLUGIN_URL . 'assets/acf-admin.css',
            array('acf-input'),
            EP_CORE_VERSION
        );
    }
}

// Initialize ACF Field Groups
new EP_ACF_Field_Groups();
