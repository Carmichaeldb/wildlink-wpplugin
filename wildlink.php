<?php
/*
Plugin Name: Wildlink
Description: A tool to create AI generated patient stories for wildlife rehabilitation centres.
Version: 1.0
Author: David Carmichael
*/

require_once plugin_dir_path(__FILE__) . 'includes/wildlink-db-setup.php';
require_once plugin_dir_path(__FILE__) . 'includes/wildlink-settings.php';
require_once plugin_dir_path(__FILE__) . 'includes/wildlink-admin.php';
require_once plugin_dir_path(__FILE__) . 'includes/wildlink-ai.php';
require_once plugin_dir_path(__FILE__) . 'includes/wildlink-shortcodes.php';

register_activation_hook(__FILE__, 'wildlink_create_tables');

// Frontend scripts
function wildlink_enqueue_scripts() {
    $script_path = plugin_dir_path(__FILE__) . 'build/frontend.js';
    $style_path = plugin_dir_path(__FILE__) . 'build/frontend.css';

    if (file_exists($script_path)) {
        $version = filemtime($script_path);
        wp_register_script(
            'wildlink-frontend',
            plugins_url('/build/frontend.js', __FILE__),
            ['wp-element', 'wp-api-fetch'],
            $version,
            true
        );

        $settings = get_option('wildlink_settings', array());
        $defaultColors = wildlink_get_default_colors();

        wp_localize_script('wildlink-frontend', 'wildlinkData', array(
            'apiRoot' => esc_url_raw(rest_url()),
            'nonce' => wp_create_nonce('wp_rest'),
            'settings' => array(
                'donation_url' => $settings['donation_url'] ?? '',
                'donation_message' => $settings['donation_message'] ?? '',
                'text_color' => $settings['text_color'] ?? $defaultColors['text_color'],
                'background_color' => $settings['background_color'] ?? $defaultColors['background_color'],
                'donation_background_color' => $settings['donation_background_color'] ?? $defaultColors['donation_background_color'],
                'donation_text_color' => $settings['donation_text_color'] ?? $defaultColors['donation_text_color'],
                'button_background_color' => $settings['button_background_color'] ?? $defaultColors['button_background_color'],
                'button_text_color' => $settings['button_text_color'] ?? $defaultColors['button_text_color'],
                'releasedBg' => $settings['releasedBg'] ?? $defaultColors['releasedBg'],
                'releasedText' => $settings['releasedText'] ?? $defaultColors['releasedText'],
                'inCareBg' => $settings['inCareBg'] ?? $defaultColors['inCareBg'],
                'inCareText' => $settings['inCareText'] ?? $defaultColors['inCareText'],
            ),
            'defaultColors' => $defaultColors,
            'timezoneOffset' => get_option('gmt_offset') * 60
        ));

        wp_enqueue_script('wildlink-frontend');
    }

    if (file_exists($style_path)) {
        wp_register_style(
            'wildlink-frontend-style',
            plugins_url('/build/frontend.css', __FILE__),
            [],
            $version
        );
        wp_enqueue_style('wildlink-frontend-style');
        
        wildlink_inject_css_variables();
    }
}
add_action('wp_enqueue_scripts', 'wildlink_enqueue_scripts');

// Enqueue admin scripts and styles
function wildlink_enqueue_admin_scripts($hook) {
    if (!strpos($hook, 'wildlink')) {
        return;
    }

    // Load plugin styles
    wp_enqueue_style(
        'wildlink-admin-style',
        plugins_url('/build/admin.css', __FILE__),
        [],
        filemtime(plugin_dir_path(__FILE__) . 'build/admin.css')
    );

    wp_enqueue_style('wp-color-picker');
    wp_enqueue_script('wp-color-picker');
    
    wp_enqueue_script(
        'wildlink-admin',
        plugins_url('/build/admin.js', __FILE__),
        ['react', 'react-dom', 'wp-element', 'wp-api-fetch', 'wp-color-picker'],
        filemtime(plugin_dir_path(__FILE__) . 'build/admin.js'),
        true
    );

    $settings = get_option('wildlink_settings', array());
    $defaultColors = wildlink_get_default_colors();

    wp_localize_script('wildlink-admin', 'wildlinkData', array(
        'debug' => true,
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('wildlink_nonce'),
        'adminPage' => $hook,
        'settings' => array(
            'donation_url' => $settings['donation_url'] ?? '',
            'donation_message' => $settings['donation_message'] ?? '',
            'text_color' => $settings['text_color'] ?? $defaultColors['text_color'],
            'background_color' => $settings['background_color'] ?? $defaultColors['background_color'],
            'donation_background_color' => $settings['donation_background_color'] ?? $defaultColors['donation_background_color'],
            'donation_text_color' => $settings['donation_text_color'] ?? $defaultColors['donation_text_color'],
            'button_background_color' => $settings['button_background_color'] ?? $defaultColors['button_background_color'],
            'button_text_color' => $settings['button_text_color'] ?? $defaultColors['button_text_color'],
            'releasedBg' => $settings['releasedBg'] ?? $defaultColors['releasedBg'],
            'releasedText' => $settings['releasedText'] ?? $defaultColors['releasedText'],
            'inCareBg' => $settings['inCareBg'] ?? $defaultColors['inCareBg'],
            'inCareText' => $settings['inCareText'] ?? $defaultColors['inCareText'],
        ),
        'defaultColors' => $defaultColors,
    ));
    
    // Add colors as CSS variables
    wildlink_inject_css_variables();
}
add_action('admin_enqueue_scripts', 'wildlink_enqueue_admin_scripts');

// Add shortcode for patient list
add_action('init', function() {
    add_shortcode('wildlink_patients', 'wildlink_patient_list_shortcode');
});

// Custom REST endpoint
add_action('rest_api_init', function() {
    // Get all options (species, conditions, treatments)
    register_rest_route('wildlink/v1', '/options', [
        'methods' => 'GET',
        'callback' => 'wildlink_get_options',
        'permission_callback' => '__return_true'
    ]);
    // Get patient list
    register_rest_route('wildlink/v1', '/patients', [
        'methods' => 'GET',
        'callback' => 'wildlink_get_patients_list',
        'permission_callback' => '__return_true',
        'args' => [
            'paginate' => [
                'required' => false,
                'default' => false,
                'type' => 'boolean'
            ],
            'page' => [
                'required' => false,
                'default' => 1,
                'type' => 'integer'
            ]
        ]
    ]);
    // Create a new patient
    register_rest_route('wildlink/v1', '/patient/new', [
        'methods' => 'POST',
        'callback' => 'wildlink_create_patient',
        'permission_callback' => '__return_true'
    ]);
    // Get, update, delete patient data
    register_rest_route('wildlink/v1', '/patient/(?P<id>[A-Z0-9]+)', [
        'methods' => ['GET', 'POST', 'DELETE'],
        'callback' => 'wildlink_handle_patient_request',
        'permission_callback' => '__return_true'
    ]); 

});

function wildlink_get_options() {
    global $wpdb;
    try {
        // Get species list
        $species = $wpdb->get_results(
            "SELECT id, common_name as label, scientific_name, description, image FROM {$wpdb->prefix}species"
        );

        // Get conditions list
        $conditions_list = $wpdb->get_results(
            "SELECT id, condition_name as label FROM {$wpdb->prefix}conditions"
        );

        // Get treatments list
        $treatments_list = $wpdb->get_results(
            "SELECT id, treatment_name as label FROM {$wpdb->prefix}treatments"
        );

        // Get age ranges
        $age_range_list = $wpdb->get_results(
            "SELECT id, range_name as label FROM {$wpdb->prefix}age_ranges"
        );

        return rest_ensure_response([
            'species_options' => $species,
            'conditions_options' => $conditions_list,
            'treatments_options' => $treatments_list,
            'age_range_options' => $age_range_list
        ]);
    } catch (Exception $e) {
        error_log('Wildlink error in get_options: ' . $e->getMessage());
        return new WP_Error(
            'options_error', 
            'Error loading data options', 
            ['status' => 500]
        );
    }
}

function wildlink_get_patients_list($request) {
    global $wpdb;
    
    try {
        // Get pagination parameters
        $paginate = $request->get_param('paginate') == '1';
        $page = max(1, (int)$request->get_param('page'));
        $settings = get_option('wildlink_settings', array());
        $per_page = isset($settings['cards_per_page']) ? (int)$settings['cards_per_page'] : 12;
        $offset = ($page - 1) * $per_page;

        // Base query parts
        $select = "SELECT pm.*, s.common_name as species, 
                  DATE_FORMAT(pm.created_at, '%Y-%m-%d %H:%i:%s') as story_created_at,
                  DATE_FORMAT(pm.updated_at, '%Y-%m-%d %H:%i:%s') as story_updated_at";
        $from = "FROM {$wpdb->prefix}patient_meta pm
                 LEFT JOIN {$wpdb->prefix}species s ON pm.species_id = s.id";
        $order = "ORDER BY pm.date_admitted DESC";

        if ($paginate) {
            // Get total count
            $count_query = "SELECT COUNT(*) FROM {$wpdb->prefix}patient_meta";
            $total = $wpdb->get_var($count_query);
            
            if ($total === null) {
                throw new Exception($wpdb->last_error ?: 'Failed to get total count');
            }

            // Build paginated query
            $limit = $wpdb->prepare("LIMIT %d OFFSET %d", $per_page, $offset);
            $query = "$select $from $order $limit";
            
            $patients = $wpdb->get_results($query);

            if ($patients === false) {
                throw new Exception($wpdb->last_error ?: 'Failed to get patients');
            }

            return rest_ensure_response([
                'data' => $patients,
                'total_pages' => ceil($total / $per_page),
                'current_page' => $page,
                'per_page' => $per_page,
                'total' => $total
            ]);
        } else {
            // Non-paginated query
            $query = "$select $from $order";
            $patients = $wpdb->get_results($query);

            if ($patients === false) {
                throw new Exception($wpdb->last_error ?: 'Failed to get patients');
            }

            return rest_ensure_response($patients);
        }

    } catch (Exception $e) {
        error_log('Error in wildlink_get_patients_list: ' . $e->getMessage());
        return new WP_Error(
            'database_error',
            $e->getMessage(),
            array('status' => 500)
        );
    }
}

function generate_patient_id() {
    global $wpdb;
    
    // Characters to use
    $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $length = 5;
    
    do {
        // Generate random 5-char ID
        $id = '';
        for ($i = 0; $i < $length; $i++) {
            $id .= $chars[random_int(0, strlen($chars) - 1)];
        }
        
        // Check if ID exists
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}patient_meta WHERE patient_id = %s",
            $id
        ));
    } while ($exists > 0);
    
    return $id;
}

function wildlink_create_patient($request) {
    global $wpdb;
    $data = $request->get_json_params();

    try {
        $wpdb->query('START TRANSACTION');

        // Validate required fields
        $required_fields = ['patient_case', 'species_id', 'date_admitted'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                throw new Exception("Missing required field: {$field}");
            }
        }

        // Generate unique patient_id
        $patient_id = generate_patient_id();

        // Insert patient
        $result = $wpdb->insert(
            $wpdb->prefix . 'patient_meta',
            [
                'patient_id' => $patient_id,
                'patient_case' => $data['patient_case'],
                'species_id' => $data['species_id'],
                'age_range_id' => $data ['age_range_id'],
                'date_admitted' => $data['date_admitted'],
                'location_found' => $data['location_found'] ?? '',
                'release_date' => $data['release_date'] ?? null,
                'patient_image' => !empty($data['user_uploaded_image']) ? 
                    $data['patient_image'] : 
                    $wpdb->get_var($wpdb->prepare(
                        "SELECT image FROM {$wpdb->prefix}species WHERE id = %d",
                        $data['species_id']
                    )),
                'patient_story' => $data['patient_story'] ?? ''
            ]
        );

        if ($result === false) {
            throw new Exception('Failed to create patient');
        }

        $id = $wpdb->insert_id;

        // Insert conditions
        if (!empty($data['patient_conditions'])) {
            foreach ($data['patient_conditions'] as $condition_id) {
                $wpdb->insert(
                    $wpdb->prefix . 'patient_conditions',
                    [
                        'patient_id' => $patient_id,
                        'condition_id' => $condition_id
                    ]
                );
            }
        }

        // Insert treatments
        if (!empty($data['patient_treatments'])) {
            foreach ($data['patient_treatments'] as $treatment_id) {
                $wpdb->insert(
                    $wpdb->prefix . 'patient_treatments',
                    [
                        'patient_id' => $patient_id,
                        'treatment_id' => $treatment_id
                    ]
                );
            }
        }

        $wpdb->query('COMMIT');
        return rest_ensure_response(['id' => $patient_id]);

    } catch (Exception $e) {
        $wpdb->query('ROLLBACK');
        error_log('Wildlink error: ' . $e->getMessage());
        return new WP_Error('database_error', 
                          'There was a problem processing your request', 
                          ['status' => 500]);
    }
}

function wildlink_get_patient_data($request) {
    global $wpdb;
    $patient_id = $request['id'];
    
    try {
        // Get patient meta
        $patient_meta = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}patient_meta WHERE patient_id = %s",
            $patient_id
        ));

        // Get patient conditions
        $conditions = $wpdb->get_col($wpdb->prepare(
            "SELECT condition_id FROM {$wpdb->prefix}patient_conditions WHERE patient_id = %d",
            $patient_meta->patient_id
        ));

        // Get patient treatments 
        $treatments = $wpdb->get_col($wpdb->prepare(
            "SELECT treatment_id FROM {$wpdb->prefix}patient_treatments WHERE patient_id = %d",
            $patient_meta->patient_id
        ));


        return rest_ensure_response([
            'patient' => $patient_meta,
            'patient_conditions' => $conditions,
            'patient_treatments' => $treatments,
        ]);
    } catch (Exception $e) {
        error_log('Wildlink error in get_patient_data: ' . $e->getMessage());
        return new WP_Error(
            'data_error',
            'Unable to retrieve patient information',
            ['status' => 404]
        );
    }
}

// Save patient data
function wildlink_update_patient_data($request) {
    global $wpdb;
    
    $patient_id = $request['id'];
    $data = $request->get_json_params();
    
    try {
        $wpdb->query('START TRANSACTION');
        // Validate required fields
        $required_fields = ['patient_case', 'species_id', 'date_admitted'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                throw new Exception("Missing required field: {$field}");
            }
        }

        // Save to patient_meta
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}patient_meta WHERE patient_id = %d",
            $patient_id
        ));
        
        $result = $wpdb->update(
            $wpdb->prefix . 'patient_meta',
            [
                'patient_case' => $data['patient_case'],
                'species_id' => $data['species_id'],
                'age_range_id' => $data ['age_range_id'],
                'date_admitted' => $data['date_admitted'],
                'location_found' => $data['location_found'] ?? '',
                'release_date' => $data['release_date'] ?? null,
                // If user uploaded image, use it, otherwise use species image
                'patient_image' => !empty($data['user_uploaded_image']) ? 
                    $data['patient_image'] : 
                    $wpdb->get_var($wpdb->prepare(
                        "SELECT image FROM {$wpdb->prefix}species WHERE id = %d",
                        $data['species_id']
                    )),
                'patient_story' => $data['patient_story']
            ],
            ['patient_id' => $patient_id]
        );

        // Save conditions
        if (!empty($data['patient_conditions'])) {
            $wpdb->query($wpdb->prepare(
                "DELETE FROM {$wpdb->prefix}patient_conditions WHERE patient_id = %d", 
                $patient_id
            ));
            
            foreach ($data['patient_conditions'] as $condition_id) {
                $result = $wpdb->insert(
                    $wpdb->prefix . 'patient_conditions',
                    ['patient_id' => $patient_id, 'condition_id' => $condition_id]
                );
                if ($result === false) {
                    throw new Exception('Failed to save condition: ' . $wpdb->last_error);
                }
            }
        }

        // Save treatments
        if (!empty($data['patient_treatments'])) {
            $wpdb->query($wpdb->prepare(
                "DELETE FROM {$wpdb->prefix}patient_treatments WHERE patient_id = %d", 
                $patient_id
            ));
            
            foreach ($data['patient_treatments'] as $treatment_id) {
                $result = $wpdb->insert(
                    $wpdb->prefix . 'patient_treatments',
                    ['patient_id' => $patient_id, 'treatment_id' => $treatment_id]
                );
                if ($result === false) {
                    throw new Exception('Failed to save treatment: ' . $wpdb->last_error);
                }
            }
        }

        $wpdb->query('COMMIT');
        return rest_ensure_response(['success' => true]);

    } catch (Exception $e) {
        $wpdb->query('ROLLBACK');
        error_log('Wildlink error: ' . $e->getMessage());
        return new WP_Error('database_error', 
                            'There was a problem processing your request', 
                            ['status' => 500]);
    }
}

// Delete patient data
function wildlink_delete_patient($request) {
    if ($request->get_method() === 'DELETE') {
        global $wpdb;
        $patient_id = $request['id'];

        // Start transaction
        $wpdb->query('START TRANSACTION');
        try {
            // Delete related records first
            $wpdb->delete($wpdb->prefix . 'patient_conditions', ['patient_id' => $patient_id]);
            $wpdb->delete($wpdb->prefix . 'patient_treatments', ['patient_id' => $patient_id]);
            
            // Delete patient
            $result = $wpdb->delete($wpdb->prefix . 'patient_meta', ['patient_id' => $patient_id]);
            
            if ($result === false) {
                throw new Exception('Failed to delete patient');
            }
            
            $wpdb->query('COMMIT');
            return rest_ensure_response(['success' => true]);
            
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            error_log('Wildlink error in delete_patient: ' . $e->getMessage());
            return new WP_Error(
                'delete_failed', 
                'Unable to delete the patient record', 
                ['status' => 500]
            );
        }
    }
}

function wildlink_handle_patient_request($request) {
    $method = $request->get_method();
    
    switch ($method) {
        case 'GET':
            return wildlink_get_patient_data($request);
        case 'POST':
            return wildlink_update_patient_data($request);
        case 'DELETE':
            return wildlink_delete_patient($request);
        default:
            return new WP_Error('invalid_method', 'Method not allowed');
    }
}