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

// Enqueue admin scripts and styles
function wildlink_enqueue_admin_scripts() {
    
    wp_enqueue_script(
        'wildlink-admin',
        plugins_url('/build/admin.js', __FILE__),
        ['react', 'react-dom', 'wp-element'],
        filemtime(plugin_dir_path(__FILE__) . 'build/admin.js'),
        true
    );

    wp_localize_script('wildlink-admin', 'wildlinkData', array(
        'debug' => true,
        'postId' => get_the_ID(),
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('wildlink_nonce')
    ));
}
add_action('admin_enqueue_scripts', 'wildlink_enqueue_admin_scripts');

// Register custom post type
function wildlink_register_post_type() {
    register_post_type('patient', [
        'labels' => [
            'name' => __('Patients'),
            'singular_name' => __('Patient'),
        ],
        'public' => true,
        'has_archive' => true,
        'supports' => ['title', 'editor'],
        'show_in_rest' => true,
    ]);
}
add_action('init', 'wildlink_register_post_type');

// Custom REST endpoint
add_action('rest_api_init', function() {
    register_rest_route('wildlink/v1', '/patient/(?P<id>\d+)', [
        [
            'methods' => 'POST',
            'callback' => 'wildlink_save_patient_data',
            'permission_callback' => function() {
                return current_user_can('edit_posts');
            }
        ],
        [
            'methods' => 'GET',
            'callback' => 'wildlink_get_patient_data',
            'permission_callback' => function() {
                return current_user_can('edit_posts');
            }
        ]
    ]);
});

function wildlink_add_meta_box() {
    add_meta_box(
        'wildlink_patient_form',
        'Patient Details',
        'wildlink_render_form',
        'patient',
        'normal',
        'high'
    );

    // Remove default editor
    remove_post_type_support('patient', 'editor');
}
add_action('add_meta_boxes', 'wildlink_add_meta_box');

function wildlink_render_form($post) {
    // Container for React to mount
    echo '<div id="patient-form-root" data-post-id="' . esc_attr($post->ID) . '"></div>';
}

function wildlink_get_patient_data($request) {
    global $wpdb;
    $post_id = $request['id'];
    
    // Get patient meta
    $patient_meta = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}patient_meta WHERE patient_id = %d",
        $post_id
    ));

    // Get patient conditions
    $conditions = $wpdb->get_col($wpdb->prepare(
        "SELECT condition_id FROM {$wpdb->prefix}patient_conditions WHERE patient_id = %d",
        $post_id
    ));

    // Get patient treatments 
    $treatments = $wpdb->get_col($wpdb->prepare(
        "SELECT treatment_id FROM {$wpdb->prefix}patient_treatments WHERE patient_id = %d",
        $post_id
    ));

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
        'patient' => $patient_meta,
        'patient_conditions' => $conditions,
        'patient_treatments' => $treatments,
        'species_options' => $species,
        'conditions_options' => $conditions_list,
        'treatments_options' => $treatments_list,
    ]);
}

// Save patient data
function wildlink_save_patient_data($request) {
    global $wpdb;
    
    $post_id = $request['id'];
    $data = $request->get_json_params();

    // logging to verify data while testing
    error_log('Saving patient data for ID: ' . $post_id);
    error_log('Received data: ' . print_r($data, true));
    
    try {
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
            $post_id
        ));
        
        if ($exists) {
            // Update Patient
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
                    'patient_story' => $data['patient_story'],
                ],
                ['patient_id' => $post_id],
                ['%s', '%d', '%s', '%s', '%s', '%s'],
                ['%d']
            );
            if ($result === false) {
                throw new Exception('Failed to update patient meta: ' . $wpdb->last_error);
            }
        } else {
            // Insert new Patient
            $result = $wpdb->insert(
                $wpdb->prefix . 'patient_meta',
                [
                    'patient_id' => $post_id,
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
                    'patient_story' => $data['patient_story'],
                ]
            );
            if ($result === false) {
                throw new Exception('Failed to insert patient meta: ' . $wpdb->last_error);
            }
        }

        // Save conditions
        if (!empty($data['patient_conditions'])) {
            $wpdb->query($wpdb->prepare(
                "DELETE FROM {$wpdb->prefix}patient_conditions WHERE patient_id = %d", 
                $post_id
            ));
            
            foreach ($data['patient_conditions'] as $condition_id) {
                $result = $wpdb->insert(
                    $wpdb->prefix . 'patient_conditions',
                    ['patient_id' => $post_id, 'condition_id' => $condition_id]
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
                $post_id
            ));
            
            foreach ($data['patient_treatments'] as $treatment_id) {
                $result = $wpdb->insert(
                    $wpdb->prefix . 'patient_treatments',
                    ['patient_id' => $post_id, 'treatment_id' => $treatment_id]
                );
                if ($result === false) {
                    throw new Exception('Failed to save treatment: ' . $wpdb->last_error);
                }
            }
        }

        error_log('Successfully saved patient data');
        return rest_ensure_response(['success' => true]);

    } catch (Exception $e) {
        error_log('Error saving patient data: ' . $e->getMessage());
        return new WP_Error('save_failed', $e->getMessage(), ['status' => 500]);
    }
}