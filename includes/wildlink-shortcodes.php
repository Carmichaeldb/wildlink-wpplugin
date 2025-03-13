<?php
function wildlink_patient_list_shortcode($atts) {
    wp_enqueue_style('wildlink-frontend-style');
    wp_enqueue_script('wildlink-frontend');
    
    return '<div id="wildlink-patient-list"></div>';
}
add_shortcode('wildlink_patients', 'wildlink_patient_list_shortcode');