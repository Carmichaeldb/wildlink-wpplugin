<?php
/*
Plugin Name: Wildlink
Description: A tool to create AI generated patient stories for wildlife rehabilitation centres.
Version: 1.0
Author: David Carmichael
*/

require_once plugin_dir_path(__FILE__) . 'includes/wildlink-db-setup.php';

register_activation_hook(__FILE__, 'wildlink_create_tables');

// Frontend scripts
function wildlink_enqueue_scripts() {
    $script_path = plugin_dir_path(__FILE__) . 'build/index.js';
    $style_path = plugin_dir_path(__FILE__) . 'build/index.css';

    if (file_exists($script_path)) {
        $version = filemtime($script_path);
        wp_register_script(
            'wildlink-frontend',
            plugins_url('/build/index.js', __FILE__),
            ['wp-element'],
            $version,
            true
        );
        wp_enqueue_script('wildlink-frontend');
    }

    if (file_exists($style_path)) {
        wp_register_style(
            'wildlink-frontend-style',
            plugins_url('/build/index.css', __FILE__),
            [],
            $version
        );
        wp_enqueue_style('wildlink-frontend-style');
    }
}
add_action('wp_enqueue_scripts', 'wildlink_enqueue_scripts');

// Add admin menu
function wildlink_admin_menu() {
    add_menu_page(
        'Patients',
        'Patients',
        'manage_options',
        'wildlink-patients',
        'wildlink_patients_page',
        'dashicons-clipboard',
        30
    );

    add_submenu_page(
        'wildlink-patients',
        'Add New Patient',
        'Add New',
        'manage_options',
        'wildlink-add-patient',
        'wildlink_add_patient_page'
    );
}
add_action('admin_menu', 'wildlink_admin_menu');

// Admin page handlers
function wildlink_patients_page() {
    echo '<div id="wildlink-admin-root"></div>';
}

function wildlink_add_patient_page() {
    // Add debug logging
    error_log('Rendering patient form page');
    
    // Add wrapper div for WP admin
    echo '<div class="wrap">';
    echo '<h1 class="wp-heading-inline">Add/Edit Patient</h1>';
    // Add form container
    echo '<div id="patient-form-root"></div>';
    echo '</div>';
}

// Enqueue admin scripts and styles
function wildlink_enqueue_admin_scripts($hook) {
    if (!strpos($hook, 'wildlink')) {
        return;
    }

    // Load theme styles
    
    wp_enqueue_style(
        'wildlink-theme-vars',
        get_template_directory_uri() . '/style.css',
        [],
        wp_get_theme()->get('Version')
    );

    // Load plugin styles
    wp_enqueue_style(
        'wildlink-admin-style',
        plugins_url('/build/admin.css', __FILE__),
        ['wildlink-theme-vars'],
        filemtime(plugin_dir_path(__FILE__) . 'build/admin.css')
    );
    
    wp_enqueue_script(
        'wildlink-admin',
        plugins_url('/build/admin.js', __FILE__),
        ['react', 'react-dom', 'wp-element', 'wp-api-fetch'],
        filemtime(plugin_dir_path(__FILE__) . 'build/admin.js'),
        true
    );

    wp_localize_script('wildlink-admin', 'wildlinkData', array(
        'debug' => true,
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('wildlink_nonce'),
        'adminPage' => $hook
    ));
}
add_action('admin_enqueue_scripts', 'wildlink_enqueue_admin_scripts');

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
        'permission_callback' => '__return_true'
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

        return rest_ensure_response([
            'species_options' => $species,
            'conditions_options' => $conditions_list,
            'treatments_options' => $treatments_list,
        ]);
    } catch (Exception $e) {
        return new WP_Error('db_error', $e->getMessage());
    }
}

function wildlink_get_patients_list() {
    global $wpdb;
    
    $patients = $wpdb->get_results("
        SELECT pm.*, s.common_name as species
        FROM {$wpdb->prefix}patient_meta pm
        LEFT JOIN {$wpdb->prefix}species s ON pm.species_id = s.id
        ORDER BY pm.date_admitted DESC
    ");

    return rest_ensure_response($patients);
}

function generate_patient_id() {
    global $wpdb;
    
    // Characters to use (excluding similar looking ones)
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
        return new WP_Error('insert_failed', $e->getMessage());
    }
}

function wildlink_get_patient_data($request) {
    global $wpdb;
    $patient_id = $request['id'];
    
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
}

// Save patient data
function wildlink_update_patient_data($request) {
    global $wpdb;
    
    $patient_id = $request['id'];
    $data = $request->get_json_params();

    // logging to verify data while testing
    error_log('Saving patient data for ID: ' . $patient_id);
    error_log('Received data: ' . print_r($data, true));
    
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
                'date_admitted' => $data['date_admitted'],
                'location_found' => $data['location_found'] ?? '',
                'release_date' => $data['release_date'] ?? null,
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

        error_log('Successfully saved patient data');
        $wpdb->query('COMMIT');
        return rest_ensure_response(['success' => true]);

    } catch (Exception $e) {
        error_log('Error saving patient data: ' . $e->getMessage());
        return new WP_Error('save_failed', $e->getMessage(), ['status' => 500]);
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
            return new WP_Error('delete_failed', $e->getMessage());
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