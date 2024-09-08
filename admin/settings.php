<?php
// Affichage de la page d'options
function inject_images_dashboard_page()
{
?>
    <div class="wrap">
        <h1>Paramètres du plugin Inject Images</h1>
        <form method="post" enctype="multipart/form-data" action="">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="inject_images_images">Téléverser des images</label></th>
                    <td>
                        <input type="file" name="inject_images_images[]" id="inject_images_images" class="custom-file-input" multiple />
                        <p class="description">Sélectionnez plusieurs images à télécharger dans le répertoire d'images.</p>
                    </td>
                </tr>
            </table>
            <button type="submit" name="submit" class="custom-submit-btn">Téléverser</button>
        </form>
    </div>
    <!-- Ajouter un espace pour le tutoriel -->
    <h2>Tutoriel : Comment utiliser le plugin Inject Images</h2>
    <p>Voici les étapes pour utiliser le plugin :</p>
    <ol>
        <li>Utilisez le formulaire ci-dessus pour téléverser plusieurs images.</li>
        <li>Insérez le shortcode <code>[inject_images]</code> dans une page ou un article pour afficher une image aléatoire.</li>
        <li>Le plugin va automatiquement sélectionner une image aléatoire depuis le répertoire et l'afficher.</li>
    </ol>
<?php
    // Traitement de l'upload d'image multiple
    if (isset($_FILES['inject_images_images']) && !empty($_FILES['inject_images_images']['name'][0])) {
        inject_images_handle_multiple_images_upload();
    }
}

// Gérer le téléversement multiple d'images
function inject_images_handle_multiple_images_upload()
{
    $uploaded_files = 0; // Compteur pour vérifier combien de fichiers ont été uploadés

    foreach ($_FILES['inject_images_images']['tmp_name'] as $key => $tmp_name) {
        if ($_FILES['inject_images_images']['error'][$key] === UPLOAD_ERR_OK) {
            // Vérifier le type de fichier
            $file_type = wp_check_filetype($_FILES['inject_images_images']['name'][$key]);
            $allowed_types = array('jpg', 'jpeg', 'png', 'gif');

            if (!in_array($file_type['ext'], $allowed_types)) {
                echo '<div class="notice notice-error is-dismissible"><p>Type de fichier non autorisé pour le fichier ' . esc_html($_FILES['inject_images_images']['name'][$key]) . '. Veuillez téléverser une image JPG, PNG ou GIF.</p></div>';
                continue;
            }

            // Chemin de destination dans un répertoire spécifique du plugin
            $destination = plugin_dir_path(__FILE__) . '../images/';

            // Créer le répertoire s'il n'existe pas
            if (!file_exists($destination)) {
                mkdir($destination, 0755, true);
            }

            // Obtenir le chemin du fichier téléversé
            $file_tmp = $tmp_name;
            $file_name = basename($_FILES['inject_images_images']['name'][$key]);
            $file_path = $destination . $file_name;

            // Déplacer le fichier vers le répertoire de destination
            if (move_uploaded_file($file_tmp, $file_path)) {
                $uploaded_files++;
                echo '<div class="notice notice-success is-dismissible"><p>L\'image ' . esc_html($file_name) . ' a été téléchargée avec succès !</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>Erreur lors du téléversement de l\'image ' . esc_html($file_name) . '.</p></div>';
            }
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>Erreur lors du téléchargement du fichier ' . esc_html($_FILES['inject_images_images']['name'][$key]) . '.</p></div>';
        }
    }

    if ($uploaded_files === 0) {
        echo '<div class="notice notice-warning is-dismissible"><p>Aucun fichier n\'a été téléversé.</p></div>';
    }
}
?>