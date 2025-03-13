<?php
function wildlink_register_settings() {
    register_setting('wildlink_options', 'wildlink_settings', array(
        'type' => 'object',
        'default' => array(
            'openai_api_key' => '',
            'ai_model' => 'gpt-4o',
            'story_prompt_template' => "Create a detailed 2-paragraph story about a {age} {species} (Identified as {patient_case}) in our wildlife rehabilitation center's care. The animal was admitted on {date_admitted} and has been in care for {days_in_care} days (current date: {current_date}). The story should focus specifically on the medical journey and recovery process.

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
            'donation_url' => '',
            'donation_message' => 'Want to support this patient\'s recovery? Please consider donating to us!',
            'cards_per_page' => 10,
            'text_color' => '',
            'background_color' => '',
            'donation_background_color' => '',
            'donation_text_color' => '',
            'button_background_color' => '',
            'button_text_color' => '',
            'releasedBg' => '',
            'releasedText' => '',
            'inCareBg' => '',
            'inCareText' => '',
        )
    ));

    // REST API endpoints for settings
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

    // Get default settings
    register_rest_route('wildlink/v1', '/settings/defaults', array(
        'methods' => 'GET',
        'callback' => 'wildlink_get_default_settings',
        'permission_callback' => function() {
            return current_user_can('manage_options');
        }
    ));
}
add_action('rest_api_init', 'wildlink_register_settings');

// Get settings
function wildlink_get_settings() {
    try {
        $settings = get_option('wildlink_settings');
        $defaultColors = wildlink_get_default_colors();
        
        $response = array(
            'openai_api_key' => !empty($settings['openai_api_key']) ? wildlink_get_api_key() : '',
            'ai_model' => $settings['ai_model'] ?? 'gpt-4o',
            'story_prompt_template' => $settings['story_prompt_template'] ?? '',
            'donation_url' => $settings['donation_url'] ?? '',
            'donation_message' => $settings['donation_message'] ?? '',
            'cards_per_page' => $settings['cards_per_page'] ?? 10,
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
        );
        
        return rest_ensure_response($response);
    } catch (Exception $e) {
        error_log('Error retrieving wildlink settings: ' . $e->getMessage());
        return new WP_Error('settings_error', 'Failed to retrieve settings', ['status' => 500]);
    }
}

// Get default settings
function wildlink_get_default_settings() {
    $defaults = get_registered_settings()['wildlink_settings']['default'];
    $defaultColors = wildlink_get_default_colors();
    
    $response = array(
        'openai_api_key' => '',
        'ai_model' => 'gpt-4o',
        'story_prompt_template' => $defaults['story_prompt_template'],
        'donation_url' => '',
        'donation_message' => $defaults['donation_message'],
        'cards_per_page' => $defaults['cards_per_page'],
        'text' => $defaultColors['text_color'],
        'background' => $defaultColors['background_color'],
        'donationBackground' => $defaultColors['donation_background_color'],
        'donationText' => $defaultColors['donation_text_color'],
        'buttonBackground' => $defaultColors['button_background_color'],
        'buttonText' => $defaultColors['button_text_color'],
        'releasedBg' => $defaultColors['releasedBg'],
        'releasedText' => $defaultColors['releasedText'],
        'inCareBg' => $defaultColors['inCareBg'],
        'inCareText' => $defaultColors['inCareText'],
    );
    
    return rest_ensure_response($response);
}

function wildlink_update_settings($request) {
    try {
        $existing_settings = get_option('wildlink_settings', array());
        $new_settings = $request->get_params();
        $merged_settings = $existing_settings;

        foreach ($new_settings as $key => $value) {
            $merged_settings[$key] = $value;
        }

        // AI model validation
        $valid_models = ['gpt-4o', 'gpt-4o-mini'];
        if (!empty($merged_settings['ai_model']) && !in_array($merged_settings['ai_model'], $valid_models)) {
            $merged_settings['ai_model'] = 'gpt-4o'; // Default if invalid
        }

        // Cards per page validation
        if (isset($merged_settings['cards_per_page'])) {
            $cards_per_page = absint($merged_settings['cards_per_page']);
            if ($cards_per_page < 3 || $cards_per_page > 100) {
                $merged_settings['cards_per_page'] = 10; // Default if out of range
            }
        }

        // Encrypt API key
        if (!empty($merged_settings['openai_api_key'])) {
            try {
                $key = wp_salt('auth');
                $method = "AES-256-CBC";
                $iv = random_bytes(openssl_cipher_iv_length($method));
                $encrypted = openssl_encrypt($merged_settings['openai_api_key'], $method, $key, 0, $iv);
                $merged_settings['openai_api_key'] = base64_encode($iv . $encrypted);
            } catch (Exception $e) {
                error_log('Encryption error: ' . $e->getMessage());
                return new WP_Error('encryption_failed', 'Failed to encrypt API key: ' . $e->getMessage());
            }
        }
        
        $final_settings = array();

        // Sanitize settings
        $final_settings = array(
            'openai_api_key' => sanitize_text_field($merged_settings['openai_api_key'] ?? ''),
            'ai_model' => sanitize_text_field($merged_settings['ai_model'] ?? 'gpt-4o'),
            'story_prompt_template' => wp_kses_post($merged_settings['story_prompt_template'] ?? ''),
            'donation_url' => esc_url_raw($merged_settings['donation_url'] ?? ''),
            'donation_message' => sanitize_text_field($merged_settings['donation_message'] ?? ''),
            'cards_per_page' => absint($merged_settings['cards_per_page'] ?? 9),
            'text_color' => wildlink_sanitize_hex_color($merged_settings['text_color'] ?? ''),
            'background_color' => wildlink_sanitize_hex_color($merged_settings['background_color'] ?? ''),
            'donation_background_color' => wildlink_sanitize_hex_color($merged_settings['donation_background_color'] ?? ''),
            'donation_text_color' => wildlink_sanitize_hex_color($merged_settings['donation_text_color'] ?? ''),
            'button_background_color' => wildlink_sanitize_hex_color($merged_settings['button_background_color'] ?? ''),
            'button_text_color' => wildlink_sanitize_hex_color($merged_settings['button_text_color'] ?? ''),
            'releasedBg' => wildlink_sanitize_hex_color($merged_settings['releasedBg'] ?? ''),
            'releasedText' => wildlink_sanitize_hex_color($merged_settings['releasedText'] ?? ''),
            'inCareBg' => wildlink_sanitize_hex_color($merged_settings['inCareBg'] ?? ''),
            'inCareText' => wildlink_sanitize_hex_color($merged_settings['inCareText'] ?? ''),
        );

        update_option('wildlink_settings', $final_settings);
        
        return wildlink_get_settings();
    } catch (Exception $e) {
        error_log('Wildlink settings error: ' . $e->getMessage());
        return new WP_Error('settings_error', 'Failed to update settings', ['status' => 500]);
    }
}

// Default colors
function wildlink_get_default_colors() {
    return array(
        'text_color' => '#333333',
        'background_color' => '#ffffff',
        'donation_background_color' => '#f5f5f5',
        'donation_text_color' => '#333333',
        'button_background_color' => '#0073aa',
        'button_text_color' => '#ffffff',
        'releasedBg' => '#c8e6c9',
        'releasedText' => '#2e7d32',
        'inCareBg' => '#ffdce0',
        'inCareText' => '#d32f2f',
    );
}

// Helper function to calculate hover color
function wildlink_calculate_hover_color($color, $darken_amount = 30) {
    $rgb = sscanf($color, "#%02x%02x%02x");
    if ($rgb && count($rgb) === 3) {
        return sprintf("#%02x%02x%02x", 
            max(0, $rgb[0] - $darken_amount),
            max(0, $rgb[1] - $darken_amount),
            max(0, $rgb[2] - $darken_amount)
        );
    }
    return '#005177'; // Default fallback if calculation fails
}

// Create CSS variables
function wildlink_inject_css_variables() {
    // Get current settings
    $settings = get_option('wildlink_settings', array());
    $defaultColors = wildlink_get_default_colors();
    
    // use settings or defaults
    $text = $settings['text_color'] ?? $defaultColors['text_color'];
    $background = $settings['background_color'] ?? $defaultColors['background_color'];
    $donationBg = $settings['donation_background_color'] ?? $defaultColors['donation_background_color'];
    $donationText = $settings['donation_text_color'] ?? $defaultColors['donation_text_color'];
    $buttonBg = $settings['button_background_color'] ?? $defaultColors['button_background_color'];
    $buttonText = $settings['button_text_color'] ?? $defaultColors['button_text_color'];
    $releasedBg = $settings['releasedBg'] ?? $defaultColors['releasedBg'];
    $releasedText = $settings['releasedText'] ?? $defaultColors['releasedText'];
    $inCareBg = $settings['inCareBg'] ?? $defaultColors['inCareBg'];
    $inCareText = $settings['inCareText'] ?? $defaultColors['inCareText'];
    
    $buttonBgHover = wildlink_calculate_hover_color($buttonBg);
    
    $custom_css = "
        :root {
            --wildlink-text: {$text};
            --wildlink-background: {$background};
            --wildlink-donation-bg: {$donationBg};
            --wildlink-donation-text: {$donationText};
            --wildlink-button-bg: {$buttonBg};
            --wildlink-button-text: {$buttonText};
            --wildlink-button-bg-hover: {$buttonBgHover};
            --wildlink-released-bg: {$releasedBg};
            --wildlink-released-text: {$releasedText};
            --wildlink-in-care-bg: {$inCareBg};
            --wildlink-in-care-text: {$inCareText};
        }
    ";
    
    // Add to both frontend and admin
    $style_handle = is_admin() ? 'wildlink-admin-style' : 'wildlink-frontend-style';
    wp_add_inline_style($style_handle, $custom_css);
}

// Sanitize hex colors
function wildlink_sanitize_hex_color($color) {
    if ('' === $color) {
        return '';
    }
    
    // Hex color with hash
    if (preg_match('|^#([A-Fa-f0-9]{3}){1,2}$|', $color)) {
        return $color;
    // Hex color without hash
    } elseif (preg_match('|^([A-Fa-f0-9]{3}){1,2}$|', $color)) {
        return '#' . $color;
    }
    
    return '';
}

// Decrypted API key
function wildlink_get_api_key() {
    try {
        $settings = get_option('wildlink_settings');
        
        if (empty($settings['openai_api_key'])) {
            return '';
        }

        $key = wp_salt('auth');
        $method = "AES-256-CBC";
        $decoded = base64_decode($settings['openai_api_key']);
        
        if ($decoded === false) {
            throw new Exception('Invalid encoded API key');
        }

        $iv_length = openssl_cipher_iv_length($method);
        
        if (strlen($decoded) <= $iv_length) {
            throw new Exception('Encoded API key too short');
        }

        $iv = substr($decoded, 0, $iv_length);
        $encrypted = substr($decoded, $iv_length);
        $decrypted = openssl_decrypt($encrypted, $method, $key, 0, $iv);

        if ($decrypted === false) {
            throw new Exception('Failed to decrypt API key');
        }
        
        return $decrypted;
    } catch (Exception $e) {
        error_log('Decryption error: ' . $e->getMessage());
        return '';
    }
}

function wildlink_refresh_colors() {
    ob_start();
    wildlink_inject_css_variables();
    ob_end_clean();
    
    return rest_ensure_response(array('success' => true));
}
