<?php
/*
Plugin Name: Wildlink
Description: A tool to create AI generated patient stories for wildlife rehabilitation centres.
Version: 1.0
Author: David Carmichael
*/

require_once plugin_dir_path(__FILE__) . 'includes/wildlink-db-setup.php';
register_activation_hook(__FILE__, 'wildlink_create_tables');

function wildlink_enqueue_scripts() {
    $script_path = plugin_dir_path(__FILE__) . 'build/index.js';
    $style_path = plugin_dir_path(__FILE__) . 'build/index.css';

    // Check if the build file exists (for development mode, it won't)
    if (file_exists($script_path)) {
        $version = filemtime($script_path); // Use filemtime for cache-busting in production
    } else {
        $version = time(); // Use a timestamp for development mode
    }
    wp_register_script(
        'wildlink',
        plugins_url('/build/index.js', __FILE__),
        ['wp-element'], 
        $version,
        true
    );

    wp_enqueue_script('wildlink-script');

    // Register and enqueue the main stylesheet if it exists
    if (file_exists($style_path)) {
        wp_register_style(
            'wildlink-style',
            plugins_url('/build/index.css', __FILE__),
            [],
            $version
        );
        wp_enqueue_style('wildlink-style');
    }

    // div container for the React app
    echo '<div id="wildlink"></div>';
}

add_action('wp_enqueue_scripts', 'wildlink_enqueue_scripts');

// Register custom post type
function wildlink_register_post_type() {
    register_post_type('patient', [
        'labels' => [
            'name' => __('Patients'),
            'singular_name' => __('Patient'),
        ],
        'public' => true,
        'has_archive' => true,
        'supports' => ['title', 'editor', 'thumbnail'],
        'show_in_rest' => true,
    ]);
}

add_action('init', 'wildlink_register_post_type');

// Add custom meta boxes
function wildlink_add_meta_boxes() {
    add_meta_box(
        'patient_details',
        __('Patient Details'),
        'wildlink_render_meta_box',
        'patient',
        'normal',
        'high'
    );
}

function wildlink_render_meta_box($post) {
    wp_nonce_field('wildlink_nonce_action', 'wildlink_nonce');

    // Retrieve existing values from the database.
    $patient_case = get_post_meta($post->ID, '_patient_case', true);
    $date_admitted = get_post_meta($post->ID, '_date_admitted', true);
    $species_id = get_post_meta($post->ID, '_species_id', true);
    $location_found = get_post_meta($post->ID, '_location_found', true);
    $is_released = get_post_meta($post->ID, '_is_released', true);
    $release_date = get_post_meta($post->ID, '_release_date', true);
    $age_range_id = get_post_meta($post->ID, '_age_range_id', true);
    $patient_conditions = get_post_meta($post->ID, '_conditions', true);
    $patient_treatments = get_post_meta($post->ID, '_treatments', true);

    // Fetch species from the database
    global $wpdb;
    $species_table = $wpdb->prefix . 'species';
    $species = $wpdb->get_results("SELECT id, common_name FROM $species_table");

    //Fetch age ranges from the database
    $age_ranges_table = $wpdb->prefix . 'age_ranges';
    $age_ranges = $wpdb->get_results("SELECT id, range_name FROM $age_ranges_table");

    // Fetch conditions from the database
    $conditions_table = $wpdb->prefix . 'conditions';
    $conditions_list = $wpdb->get_results("SELECT id, condition_name FROM $conditions_table");

    // Fetch treatments from the database
    $treatments_table = $wpdb->prefix . 'treatments';
    $treatments_list = $wpdb->get_results("SELECT id, treatment_name FROM $treatments_table");

    // Fetch existing conditions and treatments for the patient
    $patient_conditions = $wpdb->get_col($wpdb->prepare("SELECT condition_id FROM {$wpdb->prefix}patient_conditions WHERE patient_id = %d", $post->ID));
    $patient_treatments = $wpdb->get_col($wpdb->prepare("SELECT treatment_id FROM {$wpdb->prefix}patient_treatments WHERE patient_id = %d", $post->ID));

    // Ensure conditions and treatments are arrays
    $patient_conditions = is_array($patient_conditions) ? $patient_conditions : [];
    $patient_treatments = is_array($patient_treatments) ? $patient_treatments : [];

    // Render the meta box form.
    ?>
    <label for="patient_case"><?php _e('Patient Case', 'wildlink'); ?></label>
    <input type="text" name="patient_case" id="patient_case" value="<?php echo esc_attr($patient_case); ?>" />

    <label for="date_admitted"><?php _e('Date Admitted', 'wildlink'); ?></label>
    <input type="date" name="date_admitted" id="date_admitted" value="<?php echo esc_attr($date_admitted); ?>" />

    <label for="species_id"><?php _e('Species', 'wildlink'); ?></label>
    <select name="species_id" id="species_id">
        <?php foreach ($species as $specie) : ?>
            <option value="<?php echo esc_attr($specie->id); ?>" <?php selected($species_id, $specie->id); ?>>
                <?php echo esc_html($specie->common_name); ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label for="location_found"><?php _e('Location Found', 'wildlink'); ?></label>
    <input type="text" name="location_found" id="location_found" value="<?php echo esc_attr($location_found); ?>" />

    <label for="is_released"><?php _e('Is Released', 'wildlink'); ?></label>
    <input type="checkbox" name="is_released" id="is_released" value="1" <?php checked($is_released, 1); ?> />

    <label for="release_date"><?php _e('Release Date', 'wildlink'); ?></label>
    <input type="date" name="release_date" id="release_date" value="<?php echo esc_attr($release_date); ?>" />

    <label for="age_range_id"><?php _e('Age Range', 'wildlink'); ?></label>
    <select name="age_range_id" id="age_range_id">
        <?php foreach ($age_ranges as $age_range) : ?>
            <option value="<?php echo esc_attr($age_range->id); ?>" <?php selected($age_range_id, $age_range->id); ?>>
                <?php echo esc_html($age_range->range_name); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <label for="conditions"><?php _e('Conditions', 'wildlink'); ?></label>
    <select name="conditions[]" id="conditions" multiple>
        <?php foreach ($conditions_list as $condition) : ?>
            <option value="<?php echo esc_attr($condition->id); ?>" <?php echo in_array($condition->id, $patient_conditions) ? 'selected' : ''; ?>>
                <?php echo esc_html($condition->condition_name); ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label for="treatments"><?php _e('Treatments', 'wildlink'); ?></label>
    <select name="treatments[]" id="treatments" multiple>
        <?php foreach ($treatments_list as $treatment) : ?>
            <option value="<?php echo esc_attr($treatment->id); ?>" <?php echo in_array($treatment->id, $patient_treatments) ? 'selected' : ''; ?>>
                <?php echo esc_html($treatment->treatment_name); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?php
}

add_action('add_meta_boxes', 'wildlink_add_meta_boxes');

// Save custom meta box data
function wildlink_save_meta_box($post_id) {
    // Check nonce for security.
    if (!isset($_POST['wildlink_nonce']) || !wp_verify_nonce($_POST['wildlink_nonce'], 'wildlink_nonce_action')) {
        return;
    }

    // Save or update the meta fields.
    update_post_meta($post_id, '_patient_case', sanitize_text_field($_POST['patient_case']));
    update_post_meta($post_id, '_date_admitted', sanitize_text_field($_POST['date_admitted']));
    update_post_meta($post_id, '_species_id', sanitize_text_field($_POST['species_id']));
    update_post_meta($post_id, '_location_found', sanitize_text_field($_POST['location_found']));
    update_post_meta($post_id, '_is_released', isset($_POST['is_released']) ? 1 : 0);
    update_post_meta($post_id, '_release_date', sanitize_text_field($_POST['release_date']));
    update_post_meta($post_id, '_age_range_id', sanitize_text_field($_POST['age_range_id']));
    update_post_meta($post_id, '_conditions', array_map('sanitize_text_field', $_POST['conditions']));
    update_post_meta($post_id, '_treatments', array_map('sanitize_text_field', $_POST['treatments']));
}

add_action('save_post', 'wildlink_save_meta_box');