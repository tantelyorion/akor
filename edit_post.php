<?php
require_once 'includes/header.php';

if (!$is_logged_in) {
    header('Location: login.php');
    exit();
}

$post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$post = get_post_details($conn, $post_id);

// Vérifier si le post existe et appartient à l'utilisateur courant
if (!$post || $post['user_id'] != $_SESSION['user_id']) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';
$new_image_path = $post['image_url']; // Conserver l'image actuelle par défaut

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $caption = clean_input($_POST['caption']);
    
    // Traitement du nouveau média s'il est uploadé
    if (!empty($_FILES['media']['name'])) {
        $upload_result = upload_image($_FILES['media'], 'uploads/posts/');
        
        if ($upload_result['success']) {
            // Supprimer l'ancien média
            $old_image_path = 'uploads/posts/' . $post['image_url'];
            if (file_exists($old_image_path) && is_file($old_image_path)) {
                unlink($old_image_path);
            }
            
            $new_image_path = $upload_result['file_path'];
        } else {
            $error = $upload_result['message'];
        }
    }
    
    if (empty($error)) {
        if (empty($caption)) {
            $error = 'La légende ne peut pas être vide';
        } else {
            // Mettre à jour le post dans la base de données
            $query = "UPDATE posts SET image_url = ?, caption = ?, updated_at = CURRENT_TIMESTAMP WHERE post_id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ssi", $new_image_path, $caption, $post_id);
            
            if (mysqli_stmt_execute($stmt)) {
                // Mettre à jour les hashtags
                mysqli_query($conn, "DELETE FROM post_hashtags WHERE post_id = $post_id");
                save_hashtags($conn, $post_id, $caption);
                
                $success = 'Publication mise à jour avec succès';
                $post['caption'] = $caption;
                $post['image_url'] = $new_image_path;
            } else {
                $error = 'Une erreur est survenue lors de la mise à jour de la publication';
            }
        }
    }
}
?>

<div class="edit-post-page">
    <div class="edit-post-container">
        <h2>Modifier la publication</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="media-section">
                <div class="media-preview">
                    <img id="media-preview" src="uploads/posts/<?php echo $post['image_url']; ?>" alt="Media preview">
                </div>
                
                <div class="media-upload">
                    <label for="media" class="upload-label">
                        <i class="fas fa-camera"></i>
                        <span>Changer le média</span>
                        <input type="file" id="media" name="media" accept="image/*, video/*" style="display: none;">
                    </label>
                    <p class="file-info" id="file-info">Aucun fichier sélectionné</p>
                </div>
            </div>
            
            <div class="form-group">
                <label for="caption">Légende :</label>
                <textarea name="caption" id="caption" rows="4" required><?php echo htmlspecialchars($post['caption']); ?></textarea>
                <p class="hint">Utilisez #hashtags pour rendre votre publication plus visible</p>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                <a href="post.php?id=<?php echo $post_id; ?>" class="btn btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
</div>

<script>
// Aperçu du nouveau média avant upload
document.getElementById('media').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        document.getElementById('file-info').textContent = file.name;
        
        // Vérifier si c'est une image ou une vidéo
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(event) {
                document.getElementById('media-preview').src = event.target.result;
                document.getElementById('media-preview').style.display = 'block';
            };
            reader.readAsDataURL(file);
        } else if (file.type.startsWith('video/')) {
            document.getElementById('media-preview').style.display = 'none';
            document.getElementById('file-info').textContent += ' (vidéo)';
        }
    } else {
        document.getElementById('file-info').textContent = 'Aucun fichier sélectionné';
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>