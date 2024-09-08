<?php
/*
Plugin Name: Inject Images
Description: Téléverse des images, sélectionne une image aléatoirement, et déplace les images utilisées dans un répertoire séparé. Enregistre l'URL et le titre des articles où les images sont utilisées.
Version: 1.5
Author: Jerome Fraisse
Author URI: https://jerome-fraisse.fr
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: inject-images
Requires PHP: 7.2
Requires at least: 5.0
Tested up to: 6.0
*/
if (!defined('ABSPATH')) {
    exit;
}
// Inclure le fichier settings.php
require_once plugin_dir_path(__FILE__) . 'admin/settings.php';

// Fonction pour écrire des messages dans le fichier de log personnalisé
function inject_images_write_log($message)
{
    $log_file = plugin_dir_path(__FILE__) . 'inject-images-log.txt';
    $current_time = date('Y-m-d H:i:s');
    $formatted_message = '[' . $current_time . '] ' . $message . PHP_EOL;

    // Écrire le message dans le fichier de log
    file_put_contents($log_file, $formatted_message, FILE_APPEND);
}

// Ajouter une page d'administration pour le plugin
function inject_images_admin_menu()
{
    inject_images_write_log('Ajout de la page d’administration Inject Images');
    add_menu_page(
        'Inject Images',                   // Nom de la page
        'Inject Images',                   // Nom du menu
        'manage_options',                  // Permissions nécessaires
        'inject_images_dashboard',         // Slug de la page
        'inject_images_dashboard_page',    // Fonction qui affiche la page
        'dashicons-images-alt2',           // Icône du menu
        6                                  // Position dans le menu
    );
}
add_action('admin_menu', 'inject_images_admin_menu');

// Fonction pour créer la table lors de l'activation du plugin
function inject_images_create_database_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'inject_images_used';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        post_id bigint(20) NOT NULL,
        image_name varchar(255) NOT NULL,
        article_title varchar(255) NOT NULL,
        article_url varchar(255) NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    // Vérifier si la table a bien été créée
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        inject_images_write_log('Erreur : La table inject_images_used n\'a pas été créée.');
    } else {
        inject_images_write_log('La table inject_images_used a été créée avec succès.');
    }
}
register_activation_hook(__FILE__, 'inject_images_create_database_table');

// Fonction pour enregistrer l'utilisation de l'image dans un article
function inject_images_record_usage($post_id, $image_name, $article_title, $article_url)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'inject_images_used';

    // Insérer les informations de l'image pour cet article
    $result = $wpdb->insert(
        $table_name,
        array(
            'post_id' => $post_id,
            'image_name' => $image_name,
            'article_title' => $article_title,
            'article_url' => $article_url
        ),
        array(
            '%d',
            '%s',
            '%s',
            '%s'
        )
    );

    // Vérifier si l'enregistrement a réussi et capturer les erreurs SQL
    if (false === $result) {
        $wpdb->show_errors();  // Activer l'affichage des erreurs SQL
        inject_images_write_log("Erreur SQL : " . $wpdb->last_error);
        inject_images_write_log("Erreur : L'enregistrement de l'image '$image_name' pour l'article ID $post_id a échoué.");
    } else {
        inject_images_write_log("Succès : L'image '$image_name' a été enregistrée pour l'article ID $post_id.");
    }
}

// Shortcode pour afficher une image aléatoire et enregistrer l'utilisation
function inject_images_shortcode()
{
    global $post;

    inject_images_write_log('Shortcode appelé pour l’article ID : ' . $post->ID);

    // Vérifier si l'image a déjà été définie pour cet article
    $imageUrl = get_post_meta($post->ID, '_inject_images_image', true);

    // Si aucune image n'est enregistrée, en choisir une aléatoirement depuis "stock-images"
    if (!$imageUrl) {
        inject_images_write_log('Aucune image existante trouvée pour l’article. Sélection d\'une nouvelle image.');

        // Chemin des dossiers
        $stockDir = plugin_dir_path(__FILE__) . 'stock-images/';
        $usedDir = plugin_dir_path(__FILE__) . 'images-utilisees/';

        // Vérifier si le répertoire "stock-images" existe
        if (!file_exists($stockDir)) {
            inject_images_write_log('Erreur : Le répertoire "stock-images" n\'existe pas.');
            return '<div class="container-fluid text-center"><p>Le répertoire des images disponibles n\'existe pas.</p></div>';
        }

        // Récupérer toutes les images du répertoire "stock-images"
        $images = glob($stockDir . "*.{jpg,jpeg,png,gif}", GLOB_BRACE);

        if (empty($images)) {
            inject_images_write_log('Erreur : Aucune image disponible dans "stock-images".');
            return '<div class="container-fluid text-center"><p>Aucune image disponible dans le répertoire des images disponibles.</p></div>';
        }

        // Sélectionner une image aléatoire
        $randomImage = $images[array_rand($images)];
        $imageUrl = plugins_url('images-utilisees/' . basename($randomImage), __FILE__);

        // Déplacer l'image vers le dossier "images-utilisees"
        rename($randomImage, $usedDir . basename($randomImage));
        inject_images_write_log('Image "' . basename($randomImage) . '" déplacée vers "images-utilisees".');

        // Sauvegarder l'image dans les métadonnées du post
        update_post_meta($post->ID, '_inject_images_image', $imageUrl);

        // Enregistrer l'image utilisée avec l'URL et le titre de l'article
        $article_url = get_permalink($post->ID);
        $article_title = get_the_title($post->ID);
        inject_images_record_usage($post->ID, basename($randomImage), $article_title, $article_url);
    }

    // Retourner l'image (déjà utilisée ou nouvellement assignée)
    return '<div class="container-fluid text-center">
                <img src="' . esc_url($imageUrl) . '" alt="Image Injectée" class="img-fluid">
            </div>';
}
add_shortcode('inject_images', 'inject_images_shortcode');

// Inclure le mode d'emploi et les paramètres dans la page du tableau de bord
function inject_images_dashboard_page()
{
    inject_images_settings_page(); // Appeler la fonction des paramètres
    include plugin_dir_path(__FILE__) . 'mode_emploi.php'; // Inclure le mode d'emploi

    // Affichage de la rubrique qui montre où chaque image est utilisée
    echo '<h2>Liste des articles utilisant des images</h2>';
    echo '<table class="widefat fixed" cellspacing="0">';
    echo '<thead><tr><th>Image</th><th>Article</th><th>URL</th></tr></thead>';
    echo '<tbody>';

    global $wpdb;
    $table_name = $wpdb->prefix . 'inject_images_used';

    // Récupérer les informations enregistrées dans la base de données
    $results = $wpdb->get_results("SELECT image_name, article_title, article_url FROM $table_name");

    // Vérification du résultat
    if (empty($results)) {
        inject_images_write_log('Erreur : Aucune donnée récupérée depuis la base de données.');
        echo '<tr><td colspan="3">Aucune image utilisée pour l\'instant.</td></tr>';
    } else {
        inject_images_write_log('Données récupérées avec succès depuis la base de données.');
        foreach ($results as $row) {
            echo '<tr>';
            echo '<td>' . esc_html($row->image_name) . '</td>';
            echo '<td>' . esc_html($row->article_title) . '</td>';
            echo '<td><a href="' . esc_url($row->article_url) . '">' . esc_html($row->article_url) . '</a></td>';
            echo '</tr>';
        }
    }

    echo '</tbody></table>';
}

// Enqueue le fichier CSS personnalisé pour le dashboard du plugin
function inject_images_enqueue_styles()
{
    wp_enqueue_style('inject_images_custom_css', plugins_url('/css/style.css', __FILE__));
}
add_action('admin_enqueue_scripts', 'inject_images_enqueue_styles');
