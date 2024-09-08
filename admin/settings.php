<?php
// Affichage de la page d'options
function inject_images_settings_page()
{
    // Vérification et création des répertoires si nécessaire
    $stockDir = plugin_dir_path(__FILE__) . '../stock-images/';
    $usedDir = plugin_dir_path(__FILE__) . '../images-utilisees/';

    if (!file_exists($stockDir)) {
        mkdir($stockDir, 0755, true);
    }

    if (!file_exists($usedDir)) {
        mkdir($usedDir, 0755, true);
    }

    // Obtenir l'URL du répertoire stock-images et images-utilisees
    $stockImagesUrl = site_url() . '/wp-content/plugins/inject-images/stock-images/';
    $usedImagesUrl = site_url() . '/wp-content/plugins/inject-images/images-utilisees/';

?>
    <div class="wrap_tool">
        <h1>Paramètres du plugin Inject Images</h1>

        <!-- Formulaire pour téléverser des images dans "stock-images" -->
        <form method="post" enctype="multipart/form-data" action="" id="uploadForm">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="inject_images_images">Téléverser des images</label></th>
                    <td>
                        <input type="file" name="inject_images_images[]" id="inject_images_images" class="custom-file-input" multiple />
                        <p class="description">Sélectionnez plusieurs images à télécharger dans le répertoire "stock-images".</p>
                    </td>
                </tr>
            </table>
            <button type="submit" name="submit" class="custom-submit-btn">Téléverser</button>
        </form>

        <!-- Script pour recharger la page après l'upload -->
        <script type="text/javascript">
            document.getElementById('uploadForm').onsubmit = function() {
                setTimeout(function() {
                    window.location.reload();
                }, 1000); // Recharger la page après 1 seconde
            };
        </script>

        <!-- Aperçu des images dans "stock-images" -->
        <h2>Images disponibles (stock-images)</h2>
        <div style="display: flex; flex-wrap: wrap;">
            <?php
            // Récupérer toutes les images du répertoire "stock-images"
            $images = glob($stockDir . "*.{jpg,jpeg,png,gif}", GLOB_BRACE);

            if (empty($images)) {
                echo '<p>Aucune image disponible.</p>';
            } else {
                foreach ($images as $image) {
                    $imageUrl = $stockImagesUrl . basename($image);

                    // Afficher l'image avec une taille uniforme de 160x120
                    echo '<div style="margin: 10px; text-align: center;">';
                    echo '<img src="' . esc_url($imageUrl) . '" alt="Image" style="width: 160px; height: 120px; display: block; margin-bottom: 5px;">';
                    echo '</div>';
                }
            }
            ?>
        </div>

        <!-- Aperçu des images dans "images-utilisees" -->
        <h2>Images utilisées (images-utilisees)</h2>
        <div style="display: flex; flex-wrap: wrap;">
            <?php
            // Récupérer toutes les images du répertoire "images-utilisees"
            $usedImages = glob($usedDir . "*.{jpg,jpeg,png,gif}", GLOB_BRACE);

            if (empty($usedImages)) {
                echo '<p>Aucune image utilisée.</p>';
            } else {
                foreach ($usedImages as $usedImage) {
                    $imageUrl = $usedImagesUrl . basename($usedImage);

                    // Afficher l'image utilisée avec une taille uniforme de 160x120
                    echo '<div style="margin: 10px; text-align: center;">';
                    echo '<img src="' . esc_url($imageUrl) . '" alt="Image" style="width: 160px; height: 120px; display: block; margin-bottom: 5px;">';
                    echo '</div>';
                }
            }
            ?>
        </div>
    </div>
<?php
    // Traitement de l'upload d'image multiple
    if (isset($_FILES['inject_images_images']) && !empty($_FILES['inject_images_images']['name'][0])) {
        inject_images_handle_multiple_images_upload();
    }
}

// Fonction pour gérer le téléchargement d'images multiples
function inject_images_handle_multiple_images_upload()
{
    // Chemin du répertoire stock-images
    $stockDir = plugin_dir_path(__FILE__) . '../stock-images/';

    // Vérifier chaque fichier téléchargé
    foreach ($_FILES['inject_images_images']['name'] as $key => $value) {
        // Vérifier s'il y a une erreur de téléchargement
        if ($_FILES['inject_images_images']['error'][$key] == UPLOAD_ERR_OK) {
            $tmp_name = $_FILES['inject_images_images']['tmp_name'][$key];
            $name = basename($_FILES['inject_images_images']['name'][$key]);

            // Déplacer l'image téléchargée vers le répertoire stock-images
            move_uploaded_file($tmp_name, $stockDir . $name);
        }
    }
}
?>