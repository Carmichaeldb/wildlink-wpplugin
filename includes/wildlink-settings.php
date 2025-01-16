<?php
function wildlink_register_settings() {
    register_setting('wildlink_options', 'wildlink_settings', array(
        'type' => 'object',
        'default' => array(
            'openai_api_key' => '',
            'story_prompt_template' => "Create a detailed 2-paragraph story about a {age_range} {species} (Case# {patient_case}) in our wildlife rehabilitation center's care. The animal was admitted on {date_admitted} and has been in care for {days_in_care} days (current date: {current_date}). The story should focus specifically on the medical journey and recovery process.

First paragraph: Describe the circumstances of admission, found in {location_found}. Detail the specific medical conditions: {conditions}. Explain how these conditions affect the animal and why they required professional care.

Second paragraph: Focus on the treatment progress SO FAR, keeping in mind this animal has only been in care for {days_in_care} days. If {days_in_care} is less than 7 days, focus on initial response to treatment and immediate care plans rather than long-term progress. If {days_in_care} is more than 7 days, you may describe longer-term progress. Explain how each treatment ({treatments}) is contributing to the recovery process. Keep the timeline realistic - do not imply weeks or months of progress unless the admission date supports this.

Important guidelines:
- Maintain medical accuracy while being engaging
- Specifically address each listed condition and treatment
- Focus on the rehabilitation process rather than general observations
- Use professional but accessible language
- Do not include specific staff names or center location
- Keep the tone hopeful but realistic about the recovery process
- CRITICAL: Ensure all timeline references match the actual time in care ({days_in_care} days)
- Use natural language when describing the animals time in our care",
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
    register_rest_route('wildlink/v1', '/settings/defaults', array(
        'methods' => 'GET',
        'callback' => 'wildlink_get_default_settings',
        'permission_callback' => function() {
            return current_user_can('manage_options');
        }
    ));
}
add_action('rest_api_init', 'wildlink_register_settings');

function wildlink_get_settings() {
    $settings = get_option('wildlink_settings');
    return rest_ensure_response($settings);
}

function wildlink_get_default_settings() {
    // Get the default settings from the registration
    $defaults = get_registered_settings()['wildlink_settings']['default'];
    return rest_ensure_response($defaults);
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
