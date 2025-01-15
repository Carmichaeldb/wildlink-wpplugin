<?php
function wildlink_register_settings() {
    register_setting('wildlink_options', 'wildlink_settings', array(
        'type' => 'object',
        'default' => array(
            'openai_api_key' => '',
            'story_prompt_template' => "Create a hopeful 2 paragraph narrative about a wild animal currently being treated at our wildlife rehabilitation center, focusing on the patient's resilience and recovery. 
                Do not refer to the center's location. Details:
                - Case Number: {patient_case}
                - Species: {species}
                - Found at: {location_found}
                - Admission Date: {date_admitted}
                - Age Range: {age_range}
                - Conditions: {conditions}
                - Required Treatments: {treatments}

                Emphasize the dedicated care by our rehabilitation staff and volunteers using general terms like \"team members\". 
                Ensure realistic timelines based on the admission date for treatments immediate and future.
                The story should inspire support through its focus on the animal's recovery process. 
                Avoid using specific names or locations beyond what is listed here.",
            'cards_per_page' => 9,
            'show_release_status' => true,
            'show_admission_date' => true,
            'default_species_image' => '',
            'max_daily_generations' => 50
        )
    ));

    // Register REST API endpoints
    register_rest_route('wildlink/v1', '/settings', array(
        array(
            'methods' => 'GET',
            'callback' => 'wildlink_get_settings',
            'permission_callback' => function() {
                return current_user_can('manage_options');
            }
        ),
        array(
            'methods' => 'POST',
            'callback' => 'wildlink_update_settings',
            'permission_callback' => function() {
                return current_user_can('manage_options');
            }
        )
    ));
}
add_action('rest_api_init', 'wildlink_register_settings');

function wildlink_get_settings() {
    $settings = get_option('wildlink_settings');
    return rest_ensure_response($settings);
}

function wildlink_update_settings($request) {
    $settings = $request->get_params();
    
    // Sanitize and validate the settings
    $sanitized = array(
        'openai_api_key' => sanitize_text_field($settings['openai_api_key']),
        'story_prompt_template' => wp_kses_post($settings['story_prompt_template']),
        'cards_per_page' => absint($settings['cards_per_page']),
        'show_release_status' => (bool) $settings['show_release_status'],
        'show_admission_date' => (bool) $settings['show_admission_date'],
        'default_species_image' => esc_url_raw($settings['default_species_image']),
        'max_daily_generations' => absint($settings['max_daily_generations'])
    );

    update_option('wildlink_settings', $sanitized);
    return rest_ensure_response($sanitized);
}
