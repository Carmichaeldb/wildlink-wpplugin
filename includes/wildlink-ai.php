<?php

function wildlink_generate_story($patient_data) {
    //Debug mode: true for testing so API isn't actually called.
    $debug_mode = false;

    if ($debug_mode) {
        error_log('DEBUG MODE - Patient data received: ' . print_r($patient_data, true));
        
        // Calculate days in care
        $days_in_care = round((strtotime(current_time('Y-m-d')) - strtotime($patient_data['date_admitted'])) / (60 * 60 * 24));
        
        // Return just the string
        return "DEBUG MODE - Data Being Sent to AI:\n\n" .
               "Patient Case: {$patient_data['patient_case']}\n" .
               "age: {$patient_data['age']}\n" .
               "Species: {$patient_data['species']}\n" .
               "Location: {$patient_data['location_found']}\n" .
               "Date Admitted: {$patient_data['date_admitted']}\n" .
               "Conditions: {$patient_data['conditions']}\n" .
               "Treatments: {$patient_data['treatments']}\n" .
               "Days in care: {$days_in_care}\n\n" .
               "Current Date: " . current_time('Y-m-d');
    }

    // Get OpenAI API key from settings
    $settings = get_option('wildlink_settings');
    $api_key = $settings['openai_api_key'];

    if (empty($api_key)) {
        return new WP_Error('no_api_key', 'OpenAI API key is not configured');
    }

    // Get the current date and the admission date
    $current_date = current_time('Y-m-d');
    $admission_date = $patient_data['date_admitted'];

    // Calculate days in care
    $days_in_care = (strtotime($current_date) - strtotime($admission_date)) / (60 * 60 * 24);

    error_log('Days in care: ' . $days_in_care);
    error_log('Current date: ' . $current_date);
    error_log('Admission date: ' . $admission_date);

    // Get the prompt template from settings
    $prompt_template = $settings['story_prompt_template'];

    // Replace placeholders with actual data
    $prompt = str_replace(
        [
            '{patient_case}',
            '{species}',
            '{location_found}',
            '{date_admitted}',
            '{age_range}',
            '{conditions}',
            '{treatments}',
            '{current_date}',
            '{days_in_care}'
        ],
        [
            $patient_data['patient_case'],
            $patient_data['species'],
            $patient_data['location_found'],
            $patient_data['date_admitted'],
            $patient_data['age'],
            $patient_data['conditions'],
            $patient_data['treatments'],
            $current_date,
            round($days_in_care)
        ],
        $prompt_template
    );

    error_log('Final prompt sent to AI: ' . $prompt);

    // Call OpenAI API with increased timeout
    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'timeout' => 30, // Increase timeout to 30 seconds
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json',
        ],
        'body' => json_encode([
            'model' => 'gpt-4',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a wildlife rehabilitation center storyteller. Your goal is to create engaging, accurate, and hopeful stories about animals in care.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.7,
            'max_tokens' => 1000
        ])
    ]);

    if (is_wp_error($response)) {
        return $response;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (isset($body['error'])) {
        return new WP_Error('openai_error', $body['error']['message']);
    }

    return $body['choices'][0]['message']['content'];
}

// Register the REST API endpoint for story generation
add_action('rest_api_init', function () {
    register_rest_route('wildlink/v1', '/generate-story', [
        'methods' => 'POST',
        'callback' => 'wildlink_handle_story_generation',
        'permission_callback' => function () {
            return current_user_can('edit_posts');
        }
    ]);
});

function wildlink_handle_story_generation($request) {
    $patient_data = $request->get_params();
    
    // Validate required fields
    $required_fields = ['patient_case', 'species', 'location_found', 'date_admitted'];
    foreach ($required_fields as $field) {
        if (empty($patient_data[$field])) {
            return new WP_Error('missing_field', "Missing required field: $field");
        }
    }

    $story = wildlink_generate_story($patient_data);
    
    if (is_wp_error($story)) {
        return $story;
    }

    return rest_ensure_response(['story' => $story]);
}
