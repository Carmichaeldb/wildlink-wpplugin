<?php
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

    add_submenu_page(
        'wildlink-patients',
        'Settings',
        'Settings',
        'manage_options',
        'wildlink-settings',
        'wildlink_render_settings_page'
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

function wildlink_render_settings_page() {
    echo '<div class="wrap">';
    echo '<h1>WildLink Settings</h1>';
    echo '<div id="wildlink-settings-root"></div>';
    echo '</div>';
}