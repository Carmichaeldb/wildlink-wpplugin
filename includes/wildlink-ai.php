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

    // Get and decrypt the API key
    $api_key = wildlink_get_api_key();
    
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

    // Get settings and check for prompt template
    $settings = get_option('wildlink_settings', array());
    if (empty($settings['story_prompt_template'])) {
        error_log('No prompt template found in settings - using default');
        $prompt_template = 'Create a detailed 2-paragraph story about a {age_range} {species} (Case #{patient_case}) in our wildlife rehabilitation center\'s care. The animal was admitted on {date_admitted} and has been in care for {days_in_care} days (current date: {current_date}). The story should focus specifically on the medical journey and recovery process.

First paragraph: Describe the circumstances of admission, found in {location_found}. Detail the specific medical conditions: {conditions}. Explain how these conditions affect the animal and why they required professional care.

Second paragraph: Focus on the treatment progress SO FAR, keeping in mind this animal has only been in care for {days_in_care} days. If {days_in_care} is less than 7 days, focus on initial response to treatment and immediate care plans rather than long-term progress. If {days_in_care} is more than 7 days, you may describe longer-term progress. Explain how each treatment ({treatments}) is contributing to the recovery process. Keep the timeline realistic - do not imply weeks or months of progress unless the admission date supports this.

Important guidelines:
- Maintain medical accuracy while being engaging
- Specifically address each listed condition and treatment
- Focus on the rehabilitation process rather than general observations
- Use professional but accessible language
- Do not include specific staff names or center location
- Keep the tone hopeful but realistic about the recovery process
- CRITICAL: Ensure all timeline references match the actual time in care ({days_in_care} days)';
    } else {
        $prompt_template = $settings['story_prompt_template'];
    }

    error_log('Final prompt sent to AI: ' . $prompt_template);

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
    $url = 'https://api.openai.com/v1/chat/completions';
    
    $response = wp_remote_post($url, array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,  // Now using decrypted key
            'Content-Type' => 'application/json',
        ),
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
        ]),
        'timeout' => 30
    ));

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
