<?php
function wildlink_create_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    $species_table = $wpdb->prefix . 'species';
    $patient_meta_table = $wpdb->prefix . 'patient_meta';
    $conditions_table = $wpdb->prefix . 'conditions';
    $treatments_table = $wpdb->prefix . 'treatments';
    $age_ranges_table = $wpdb->prefix . 'age_ranges';
    $patient_conditions_table = $wpdb->prefix . 'patient_conditions';
    $patient_treatments_table = $wpdb->prefix . 'patient_treatments';

    // Create species table
    if ($wpdb->get_var("SHOW TABLES LIKE '$species_table'") != $species_table) {
        $species_sql = "CREATE TABLE $species_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            common_name varchar(255) NOT NULL,
            scientific_name varchar(255) NOT NULL,
            description text,
            image varchar(255) DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            INDEX common_name_idx (common_name)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($species_sql);

        // Insert initial species data
        $initial_species = [
            ['common_name' => 'Bald Eagle', 'scientific_name' => 'Haliaeetus leucocephalus', 'description' => 'The bald eagle is a bird of prey found in North America. A sea eagle, it has two known subspecies and forms a species pair with the white-tailed eagle, which occupies the same niche as the bald eagle in the Palearctic.', 'image' => plugins_url('images/bald-eagle.jpg', __FILE__)],
            ['common_name' => 'Common Raven', 'scientific_name' => 'Corvus corax', 'description' => 'The common raven is a large all-black passerine bird. It is the most widely distributed of all corvids, found across the Northern Hemisphere.', 'image' => plugins_url('images/common-raven.jpg', __FILE__)],
            ['common_name' => 'Raccoon', 'scientific_name' => 'Procyon lotor', 'description' => 'Raccoons are stocky animals with short legs and small, rounded ears. Their fur is gray, with dark black markings around their eyes, and black bands on their tail.', 'image' => plugins_url('images/racoon.png', __FILE__)],
            ['common_name' => 'Varied Thrush', 'scientific_name' => 'Ixoreus naevius', 'description' => 'The varied thrush is a member of the thrush family, Turdidae. It is the only species in the monotypic genus Ixoreus.', 'image' => plugins_url('images/varied-thrush.png', __FILE__)],
            ['common_name' => 'Sea Otter', 'scientific_name' => 'Enhydra lutris', 'description' => 'The sea otter is a marine mammal native to the coasts of the northern and eastern North Pacific Ocean. They are known for their playful behavior and use of tools, such as rocks, to crack open shellfish.', 'image' => plugins_url('images/sea-otter.png', __FILE__)],
            ['common_name' => 'River Otter', 'scientific_name' => 'Lontra canadensis', 'description' => 'The river otter is a semiaquatic mammal found in North America. They are skilled swimmers and feed on fish, crustaceans, and other aquatic prey.', 'image' => plugins_url('images/river-otter.png', __FILE__)],
            ['common_name' => 'Harbour Seal', 'scientific_name' => 'Pinniped', 'description' => 'Seals are marine mammals characterized by their streamlined bodies, flippers, and ability to swim gracefully underwater. They are found in both polar and temperate waters.', 'image' => plugins_url('images/harbour-seal.png', __FILE__)],
            ['common_name' => 'Sea Lion', 'scientific_name' => 'Otariinae', 'description' => 'Sea lions are marine mammals belonging to the family Otariidae. They are characterized by external ear flaps, long foreflippers, and the ability to walk on land using all four limbs.', 'image' => plugins_url('images/sea-lions.png', __FILE__)],
            ['common_name' => 'Marmot', 'scientific_name' => 'Marmota', 'description' => 'Marmots are large ground squirrels belonging to the genus Marmota. They are typically found in mountainous regions and are known for their burrowing behavior and loud warning calls.', 'image' => plugins_url('images/marmot.png', __FILE__)],
            ['common_name' => 'Roosevelt Elk', 'scientific_name' => 'Cervus canadensis roosevelti', 'description' => 'The Roosevelt elk is the largest of the four surviving subspecies of elk in North America. They are primarily found in the Pacific Northwest region.', 'image' => plugins_url('images/roosevelt-elk.png', __FILE__)],
            ['common_name' => 'Crow', 'scientific_name' => 'Corvus', 'description' => 'Crows are highly intelligent birds belonging to the genus Corvus. They are known for their adaptability, problem-solving skills, and distinct cawing calls.', 'image' => plugins_url('images/crow.png', __FILE__)],
            ['common_name' => 'Pileated Woodpecker', 'scientific_name' => 'Picidae', 'description' => 'Woodpeckers are a family of birds known for their drumming behavior on trees and ability to excavate wood to find insects. They have strong bills and specialized tongues.', 'image' => plugins_url('images/pileated-woodpecker.png', __FILE__)],
            ['common_name' => 'Violet Green Swallow', 'scientific_name' => 'Hirundinidae', 'description' => 'Swallows are small passerine birds known for their graceful aerial acrobatics. They feed on insects caught in flight and often build mud nests.', 'image' => plugins_url('images/violet-green-swallow.png', __FILE__)],
            ['common_name' => 'Least Sandpiper', 'scientific_name' => 'Scolopacidae', 'description' => 'Sandpipers are a diverse family of shorebirds found worldwide. They have long bills for probing in mud and sand, and they feed on small invertebrates.', 'image' => plugins_url('images/leasts-sandpiper.png', __FILE__)],
            ['common_name' => 'Deer', 'scientific_name' => 'Cervidae', 'description' => 'Deer are hoofed mammals belonging to the family Cervidae. They are known for their antlers, which are typically found on males, and their herbivorous diet.', 'image' => plugins_url('images/deer.png', __FILE__)]
        ];

        foreach ($initial_species as $species) {
            $wpdb->insert($species_table, $species);
        }
    }

    // Create Patient_meta Table
    if ($wpdb->get_var("SHOW TABLES LIKE '$patient_meta_table'") != $patient_meta_table) {
        $patient_meta_sql = "CREATE TABLE $patient_meta_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            patient_id bigint(20) UNSIGNED NOT NULL,
            patient_case varchar(255) NOT NULL,
            species_id mediumint(9) NOT NULL,
            date_admitted date NOT NULL,
            location_found varchar(100) NOT NULL,
            release_date date,
            patient_image varchar(255),
            patient_story text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            FOREIGN KEY (patient_id) REFERENCES {$wpdb->prefix}posts(ID) ON DELETE CASCADE,
            FOREIGN KEY (species_id) REFERENCES {$species_table}(id),
            INDEX patient_case_idx (patient_case),
            INDEX date_admitted_idx (date_admitted)
        ) $charset_collate;";
        dbDelta($patient_meta_sql);
    }

    // Create conditions table
    if ($wpdb->get_var("SHOW TABLES LIKE '$conditions_table'") != $conditions_table) {
        $conditions_sql = "CREATE TABLE $conditions_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            condition_name varchar(255) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        dbDelta($conditions_sql);

        // Insert initial conditions data
        $initial_conditions = [
            ['condition_name' => 'Cat Attack'],
            ['condition_name' => 'Dog Attack'],
            ['condition_name' => 'Broken Wing'],
            ['condition_name' => 'Infection'],
            ['condition_name' => 'Emaciation'],
            ['condition_name' => 'Window Strike'],
            ['condition_name' => 'Boat Strike'],
            ['condition_name' => 'Car Strike'],
            ['condition_name' => 'Head Trauma']
        ];

        foreach ($initial_conditions as $condition) {
            $wpdb->insert($conditions_table, $condition);
        }
    }

    // Create treatments table
    if ($wpdb->get_var("SHOW TABLES LIKE '$treatments_table'") != $treatments_table) {
        $treatments_sql = "CREATE TABLE $treatments_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            treatment_name varchar(255) NOT NULL,
            treatment_cost int(11) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        dbDelta($treatments_sql);

        // Insert initial treatments data
        $initial_treatments = [
            ['treatment_name' => 'Physiotherapy', 'treatment_cost' => 20000],
            ['treatment_name' => 'Fluid Therapy', 'treatment_cost' => 10000],
            ['treatment_name' => 'Wing Wrap', 'treatment_cost' => 6000],
            ['treatment_name' => 'Anti-Biotics', 'treatment_cost' => 15000],
            ['treatment_name' => 'Trauma Therapy', 'treatment_cost' => 30000],
            ['treatment_name' => 'Nutritional Support', 'treatment_cost' => 15000],
            ['treatment_name' => 'Orphan Care', 'treatment_cost' => 50000]
        ];

        foreach ($initial_treatments as $treatment) {
            $wpdb->insert($treatments_table, $treatment);
        }
    }

    // Create age_ranges table
    if ($wpdb->get_var("SHOW TABLES LIKE '$age_ranges_table'") != $age_ranges_table) {
        $age_ranges_sql = "CREATE TABLE $age_ranges_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            range_name varchar(255) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        dbDelta($age_ranges_sql);

        // Insert initial age ranges data
        $initial_age_ranges = [
            ['range_name' => 'Baby'],
            ['range_name' => 'Hatchling'],
            ['range_name' => 'Fledgling'],
            ['range_name' => 'Juvenile'],
            ['range_name' => 'Sub-Adult'],
            ['range_name' => 'Adult']
        ];

        foreach ($initial_age_ranges as $age_range) {
            $wpdb->insert($age_ranges_table, $age_range);
        }
    }

    // Create patient_conditions table
    if ($wpdb->get_var("SHOW TABLES LIKE '$patient_conditions_table'") != $patient_conditions_table) {
        $patient_conditions_sql = "CREATE TABLE $patient_conditions_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            patient_id bigint(20) UNSIGNED NOT NULL,
            condition_id mediumint(9) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            FOREIGN KEY (patient_id) REFERENCES {$wpdb->prefix}posts(ID) ON DELETE CASCADE,
            FOREIGN KEY (condition_id) REFERENCES $conditions_table(id) ON DELETE CASCADE
        ) $charset_collate;";
        dbDelta($patient_conditions_sql);
    }

    // Create patient_treatments table
    if ($wpdb->get_var("SHOW TABLES LIKE '$patient_treatments_table'") != $patient_treatments_table) {
        $patient_treatments_sql = "CREATE TABLE $patient_treatments_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            patient_id bigint(20) UNSIGNED NOT NULL,
            treatment_id mediumint(9) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            FOREIGN KEY (patient_id) REFERENCES {$wpdb->prefix}posts(ID) ON DELETE CASCADE,
            FOREIGN KEY (treatment_id) REFERENCES $treatments_table(id) ON DELETE CASCADE
        ) $charset_collate;";
        dbDelta($patient_treatments_sql);
    }
}

register_activation_hook(__FILE__, 'wildlink_create_tables');