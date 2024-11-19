<?php
function wildlink_create_species_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'species';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
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
    dbDelta($sql);

    // Insert initial species data
    $initial_species = [
        ['common_name' => 'Bald Eagle', 'scientific_name' => 'Haliaeetus leucocephalus', 'description' => 'The bald eagle is a bird of prey found in North America. A sea eagle, it has two known subspecies and forms a species pair with the white-tailed eagle, which occupies the same niche as the bald eagle in the Palearctic.', 'image' => plugins_url('images/species-images/bald-eagle.jpg', __FILE__)],
        ['common_name' => 'Common Raven', 'scientific_name' => 'Corvus corax', 'description' => 'The common raven is a large all-black passerine bird. It is the most widely distributed of all corvids, found across the Northern Hemisphere.', 'image' => plugins_url('images/species-images/common-raven.jpg', __FILE__)],
        ['common_name' => 'Raccoon', 'scientific_name' => 'Procyon lotor', 'description' => 'Raccoons are stocky animals with short legs and small, rounded ears. Their fur is gray, with dark black markings around their eyes, and black bands on their tail.', 'image' => plugins_url('images/species-images/racoon.png', __FILE__)],
        ['common_name' => 'Varied Thrush', 'scientific_name' => 'Ixoreus naevius', 'description' => 'The varied thrush is a member of the thrush family, Turdidae. It is the only species in the monotypic genus Ixoreus.', 'image' => plugins_url('images/species-images/varied-thrush.png', __FILE__)],
        ['common_name' => 'Sea Otter', 'scientific_name' => 'Enhydra lutris', 'description' => 'The sea otter is a marine mammal native to the coasts of the northern and eastern North Pacific Ocean. They are known for their playful behavior and use of tools, such as rocks, to crack open shellfish.', 'image' => plugins_url('images/species-images/sea-otter.png', __FILE__)],
        ['common_name' => 'River Otter', 'scientific_name' => 'Lontra canadensis', 'description' => 'The river otter is a semiaquatic mammal found in North America. They are skilled swimmers and feed on fish, crustaceans, and other aquatic prey.', 'image' => plugins_url('images/species-images/river-otter.png', __FILE__)],
        ['common_name' => 'Harbour Seal', 'scientific_name' => 'Pinniped', 'description' => 'Seals are marine mammals characterized by their streamlined bodies, flippers, and ability to swim gracefully underwater. They are found in both polar and temperate waters.', 'image' => plugins_url('images/species-images/harbour-seal.png', __FILE__)],
        ['common_name' => 'Sea Lion', 'scientific_name' => 'Otariinae', 'description' => 'Sea lions are marine mammals belonging to the family Otariidae. They are characterized by external ear flaps, long foreflippers, and the ability to walk on land using all four limbs.', 'image' => plugins_url('images/species-images/sea-lions.png', __FILE__)],
        ['common_name' => 'Marmot', 'scientific_name' => 'Marmota', 'description' => 'Marmots are large ground squirrels belonging to the genus Marmota. They are typically found in mountainous regions and are known for their burrowing behavior and loud warning calls.', 'image' => plugins_url('images/species-images/marmot.png', __FILE__)],
        ['common_name' => 'Roosevelt Elk', 'scientific_name' => 'Cervus canadensis roosevelti', 'description' => 'The Roosevelt elk is the largest of the four surviving subspecies of elk in North America. They are primarily found in the Pacific Northwest region.', 'image' => plugins_url('images/species-images/roosevelt-elk.png', __FILE__)],
        ['common_name' => 'Crow', 'scientific_name' => 'Corvus', 'description' => 'Crows are highly intelligent birds belonging to the genus Corvus. They are known for their adaptability, problem-solving skills, and distinct cawing calls.', 'image' => plugins_url('images/species-images/crow.png', __FILE__)],
        ['common_name' => 'Pileated Woodpecker', 'scientific_name' => 'Picidae', 'description' => 'Woodpeckers are a family of birds known for their drumming behavior on trees and ability to excavate wood to find insects. They have strong bills and specialized tongues.', 'image' => plugins_url('images/species-images/pileated-woodpecker.png', __FILE__)],
        ['common_name' => 'Violet Green Swallow', 'scientific_name' => 'Hirundinidae', 'description' => 'Swallows are small passerine birds known for their graceful aerial acrobatics. They feed on insects caught in flight and often build mud nests.', 'image' => plugins_url('images/species-images/violet-green-swallow.png', __FILE__)],
        ['common_name' => 'Least Sandpiper', 'scientific_name' => 'Scolopacidae', 'description' => 'Sandpipers are a diverse family of shorebirds found worldwide. They have long bills for probing in mud and sand, and they feed on small invertebrates.', 'image' => plugins_url('images/species-images/leasts-sandpiper.png', __FILE__)],
        ['common_name' => 'Deer', 'scientific_name' => 'Cervidae', 'description' => 'Deer are hoofed mammals belonging to the family Cervidae. They are known for their antlers, which are typically found on males, and their herbivorous diet.', 'image' => plugins_url('images/species-images/deer.png', __FILE__)]]
    ];

    foreach ($initial_species as $species) {
        $wpdb->insert($table_name, $species);
    }
}

register_activation_hook(__FILE__, 'wildlink_create_species_table');