<?php

global $UI_lang;

$UI_lang = array(
    'FR' => array(
        'key' => 'Clé',
        'taxonomy' => 'Taxonomie',
        'conc_concur' => 'Concepts Concurrents',
        'conc_comp' => 'Concepts Complémentaire',
        'etymology' => 'Étymologie',
        'contained_by' => 'Contained by',
        'containing' => 'Containing',
        'in_relation' => 'En relation avec',
        'view_users' => 'Usagers',
        'add_record' => 'Nouvelle Expression',
        'home' => 'Accueil',
        'login' => 'Connexion',
        'logout' => 'Déconnexion',
        'search' => 'Recherche',
        'user_tab_user_col' => 'Identifiant/Courriel',
        'user_tab_type_col' => 'Type',
        'user_tab_created_col' => 'Crée',
        'users' => 'Usagers',
        'list_tab_exp_col' => 'Expression',
        'list_tab_descriptor_col' => 'Descripteur',
        'results' => 'Résultats',
        'edit' => 'Edition',
        'save' => 'Sauvegarder',
        'cancel' => 'Annulez',
        'delete' => 'Supprimer',
        'no' => 'Non',
        'yes' => 'Oui',
        'add' => 'Ajouter',
        'add_user' => 'Ajouter un utilisateur',
        'appears_in_table' => 'Tableaux',
        'table_relations' => 'Liste de relations',
        'table_graph' => 'Graph',
        'password' => 'Mot de passe',
        'conf_pass' => 'Confirmer le mot de passe',
        'user' => 'Username',
        'back_to_res' => 'Retour aux résultats',
        'show_empty_cells' => 'Afficher les expressions vides',
        'taxonomic' => 'Taxonomic',
        'concurring_concepts' => 'Concurring Concepts',
        'example' => 'Example',
        'descriptor' => 'Descriptor',
        'complementary_concepts' => 'Complementary Concept',
        'etymology' => 'Étymology',
        'paradigmatic_curcuits' => 'Circuits Paradigmatique',
        'disable' => 'Disable',
        'substance' => 'Substance',
        'attribute' => 'Attribute',
        'mode' => 'Mode',
        'filter_show_all' => 'Show All',
        'filter_keys_only' => 'Keys Only'
    ),
    'EN' => array(
        'key' => 'Key',
        'taxonomy' => 'Taxonomy',
        'conc_concur' => 'Concurrent Concepts',
        'conc_comp' => 'Complementary Concepts',
        'etymology' => 'Etymology',
        'contained_by' => 'Contained by',
        'containing' => 'Containing',
        'in_relation' => 'In relation to',
        'view_users' => 'Users',
        'add_record' => 'New Expression',
        'home' => 'Home',
        'login' => 'Login',
        'logout' => 'Logout',
        'search' => 'Search',
        'user_tab_user_col' => 'Username/Email',
        'user_tab_type_col' => 'Type',
        'user_tab_created_col' => 'Created',
        'users' => 'Users',
        'list_tab_exp_col' => 'Expression',
        'list_tab_descriptor_col' => 'Descriptor',
        'results' => 'Results',
        'edit' => 'Edit',
        'save' => 'Save',
        'cancel' => 'Cancel',
        'delete' => 'Delete',
        'no' => 'No',
        'yes' => 'Yes',
        'add' => 'Add',
        'add_user' => 'Add User',
        'appears_in_table' => 'Table',
        'table_relations' => 'Lists of relations',
        'table_graph' => 'Graph',
        'password' => 'Password',
        'conf_pass' => 'Confirm Password',
        'user' => 'Username',
        'back_to_res' => 'Back to Results',
        'show_empty_cells' => 'Show Empty Expressions',
        'taxonomic' => 'Taxonomic',
        'concurring_concepts' => 'Concurring Concepts',
        'example' => 'Example',
        'descriptor' => 'Descriptor',
        'complementary_concepts' => 'Complementary Concept',
        'etymology' => 'Etymology',
        'paradigmatic_curcuits' => 'Paradigmatic Circuits',
        'turn_off_comp_conc' => 'Disable Complementary Concepts',
        'disable' => 'Disable',
        'substance' => 'Substance',
        'attribute' => 'Attribute',
        'mode' => 'Mode',
        'filter_show_all' => 'Show All',
        'filter_keys_only' => 'Keys Only'
    )
);

function trans_phrase($key, $lang = 'EN') {
    global $UI_lang;
    if (array_key_exists($key, $UI_lang[$lang]))
        return $UI_lang[$lang][$key];
    
    return "Can't find translation for '".$key."'";
}

?>