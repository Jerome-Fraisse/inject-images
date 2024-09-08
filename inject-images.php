<?php
/*
Plugin Name: Inject Images
Description: Permet de téléverser des images et d'injecter une image aléatoire dans un article, qui reste statique après la première génération.
Version: 1.2
Author: Jerome Fraisse
*/

// Ajouter une page d'administration pour le plugin
function inject_images_admin_menu()
{
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

// Enqueue le fichier CSS personnalisé pour le dashboard du plugin
function inject_images_enqueue_styles()
{
    wp_enqueue_style('inject_images_custom_css', plugins_url('/css/style.css', __FILE__));
}
add_action('admin_enqueue_scripts', 'inject_images_enqueue_styles');

// Shortcode pour afficher une image aléatoire et la rendre statique après la première génération
function inject_images_shortcode()
{
    global $post;

    // Vérifier si l'image a déjà été définie pour cet article
    $imageUrl = get_post_meta($post->ID, '_inject_images_image', true);

    // Si aucune image n'est enregistrée, en choisir une aléatoirement
    if (!$imageUrl) {
        // Chemin du répertoire des images dans le plugin
        $directory = plugin_dir_path(__FILE__) . 'images/';

        // Vérifier si le répertoire existe
        if (!file_exists($directory)) {
            return '<div class="container-fluid text-center"><p>Le répertoire d\'images n\'existe pas.</p></div>';
        }

        // Récupérer toutes les images du répertoire
        $images = glob($directory . "*.{jpg,jpeg,png,gif}", GLOB_BRACE);

        if (empty($images)) {
            return '<div class="container-fluid text-center"><p>Aucune image disponible dans le répertoire.</p></div>';
        }

        // Sélectionner une image aléatoire
        $randomImage = $images[array_rand($images)];
        $imageUrl = plugins_url('images/' . basename($randomImage), __FILE__);

        // Sauvegarder l'image dans les métadonnées du post
        update_post_meta($post->ID, '_inject_images_image', $imageUrl);
    }

    // Retourner l'image
    return '<div class="container-fluid text-center">
                <img src="' . esc_url($imageUrl) . '" alt="Image Rotative" class="img-fluid">
            </div>';
}
add_shortcode('inject_images', 'inject_images_shortcode');

// Inclure la page d'options pour le dashboard
require_once plugin_dir_path(__FILE__) . 'admin/settings.php';
