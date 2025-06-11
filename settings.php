<?php
require_once 'includes/header.php';

// Rediriger si non connecté
if (!$is_logged_in) {
    header('Location: login.php');
    exit();
}

// Récupérer les paramètres actuels
$user_settings = get_user_settings($conn, $_SESSION['user_id']);
$user_data = get_user_by_id($conn, $_SESSION['user_id']);

// Variables pour les messages
$error = '';
$success = '';
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'profile';

// Traitement du formulaire selon l'onglet actif
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Profil
        if ($active_tab === 'profile') {
            $new_data = [
                'username' => clean_input($_POST['username']),
                'full_name' => clean_input($_POST['full_name']),
                'bio' => clean_input($_POST['bio'])
            ];
            
            // Vérifier si le nom d'utilisateur a changé
            if ($new_data['username'] !== $user_data['username']) {
                // Vérifier si le nouveau nom d'utilisateur est disponible
                $check = get_user_by_username($conn, $new_data['username']);
                if ($check) {
                    throw new Exception("Ce nom d'utilisateur est déjà pris");
                }
            }
            
            if (update_user_profile($conn, $_SESSION['user_id'], $new_data)) {
                $success = "Profil mis à jour avec succès";
                $user_data = array_merge($user_data, $new_data);
            } else {
                throw new Exception("Erreur lors de la mise à jour du profil");
            }
            
            // Photo de profil
            if (!empty($_FILES['profile_pic']['name'])) {
                $upload_result = upload_image($_FILES['profile_pic'], 'uploads/profile_pics/');
                if ($upload_result['success']) {
                    if (update_profile_picture($conn, $_SESSION['user_id'], $upload_result['file_path'])) {
                        $success .= $success ? " et photo de profil mise à jour" : "Photo de profil mise à jour";
                        $user_data['profile_pic'] = $upload_result['file_path'];
                    } else {
                        throw new Exception("Erreur lors de la mise à jour de la photo de profil");
                    }
                } else {
                    throw new Exception($upload_result['message']);
                }
            }
        }
        // Confidentialité
        elseif ($active_tab === 'privacy') {
            $new_settings = [
                'private_account' => isset($_POST['private_account']) ? 1 : 0,
                'notification_comments' => isset($_POST['notification_comments']) ? 1 : 0,
                'notification_likes' => isset($_POST['notification_likes']) ? 1 : 0,
                'notification_follows' => isset($_POST['notification_follows']) ? 1 : 0,
                'notification_mentions' => isset($_POST['notification_mentions']) ? 1 : 0
            ];
            
            if (update_user_settings($conn, $_SESSION['user_id'], $new_settings)) {
                $success = "Paramètres de confidentialité mis à jour";
                $user_settings = array_merge($user_settings, $new_settings);
            } else {
                throw new Exception("Erreur lors de la mise à jour des paramètres");
            }
        }
        // Mot de passe
        elseif ($active_tab === 'password') {
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];
            
            if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                throw new Exception("Tous les champs sont obligatoires");
            }
            
            if ($new_password !== $confirm_password) {
                throw new Exception("Les nouveaux mots de passe ne correspondent pas");
            }
            
            if (strlen($new_password) < 6) {
                throw new Exception("Le mot de passe doit contenir au moins 6 caractères");
            }
            
            if (update_password($conn, $_SESSION['user_id'], $current_password, $new_password)) {
                $success = "Mot de passe mis à jour avec succès";
            } else {
                throw new Exception("Mot de passe actuel incorrect");
            }
        }
        // Apparence
        elseif ($active_tab === 'appearance') {
            $new_settings = [
                'language' => clean_input($_POST['language']),
                'theme' => clean_input($_POST['theme'])
            ];
            
            if (update_user_settings($conn, $_SESSION['user_id'], $new_settings)) {
                $success = "Préférences d'apparence mises à jour";
                $user_settings = array_merge($user_settings, $new_settings);
                
                // Mettre à jour la session avec le thème sélectionné
                $_SESSION['theme'] = $new_settings['theme'];
            } else {
                throw new Exception("Erreur lors de la mise à jour des préférences");
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<div class="settings-page">
    <div class="settings-container">
        <br>
        <h1>Paramètres</h1>
        <br>
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <div class="settings-tabs">
            <a href="?tab=profile" class="<?php echo $active_tab === 'profile' ? 'active' : ''; ?>">
                <i class="fas fa-user"></i> Profil
            </a>
            <a href="?tab=privacy" class="<?php echo $active_tab === 'privacy' ? 'active' : ''; ?>">
                <i class="fas fa-lock"></i> Confidentialité
            </a>
            <a href="?tab=password" class="<?php echo $active_tab === 'password' ? 'active' : ''; ?>">
                <i class="fas fa-key"></i> Mot de passe
            </a>
            <a href="?tab=appearance" class="<?php echo $active_tab === 'appearance' ? 'active' : ''; ?>">
                <i class="fas fa-palette"></i> Apparence
            </a>
        </div>
        
        <div class="settings-content">
            <!-- Onglet Profil -->
            <?php if ($active_tab === 'profile'): ?>
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="profile_pic">Photo de profil</label>
                        <div class="profile-pic-upload">
                            <img src="uploads/profile_pics/<?php echo $user_data['profile_pic']; ?>" 
                                 alt="Photo de profil actuelle" 
                                 id="profile-pic-preview">
                            <label for="profile_pic" class="upload-btn">
                                <i class="fas fa-camera"></i> Changer
                                <input type="file" id="profile_pic" name="profile_pic" accept="image/*" style="display: none;">
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="username">Nom d'utilisateur</label>
                        <input type="text" id="username" name="username" 
                               value="<?php echo htmlspecialchars($user_data['username']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="full_name">Nom complet</label>
                        <input type="text" id="full_name" name="full_name" 
                               value="<?php echo htmlspecialchars($user_data['full_name']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="bio">Bio</label>
                        <textarea id="bio" name="bio" rows="3"><?php echo htmlspecialchars($user_data['bio']); ?></textarea>
                        <p class="hint">Maximum 150 caractères</p>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                    </div>
                </form>
            
            <!-- Onglet Confidentialité -->
            <?php elseif ($active_tab === 'privacy'): ?>
                <form method="POST">
                    <div class="form-group checkbox-group">
                        <label>
                            <input type="checkbox" name="private_account" 
                                   <?php echo $user_settings['private_account'] ? 'checked' : ''; ?>>
                            <span>Compte privé</span>
                            <p class="hint">Seuls vos abonnés pourront voir vos publications</p>
                        </label>
                    </div>
                    
                    <h3>Notifications</h3>
                    
                    <div class="form-group checkbox-group">
                        <label>
                            <input type="checkbox" name="notification_comments" 
                                   <?php echo $user_settings['notification_comments'] ? 'checked' : ''; ?>>
                            <span>Commentaires</span>
                            <p class="hint">Recevoir des notifications pour les nouveaux commentaires</p>
                        </label>
                    </div>
                    
                    <div class="form-group checkbox-group">
                        <label>
                            <input type="checkbox" name="notification_likes" 
                                   <?php echo $user_settings['notification_likes'] ? 'checked' : ''; ?>>
                            <span>J'aime</span>
                            <p class="hint">Recevoir des notifications pour les nouveaux j'aime</p>
                        </label>
                    </div>
                    
                    <div class="form-group checkbox-group">
                        <label>
                            <input type="checkbox" name="notification_follows" 
                                   <?php echo $user_settings['notification_follows'] ? 'checked' : ''; ?>>
                            <span>Nouveaux abonnés</span>
                            <p class="hint">Recevoir des notifications pour les nouveaux abonnés</p>
                        </label>
                    </div>
                    
                    <div class="form-group checkbox-group">
                        <label>
                            <input type="checkbox" name="notification_mentions" 
                                   <?php echo $user_settings['notification_mentions'] ? 'checked' : ''; ?>>
                            <span>Mentions</span>
                            <p class="hint">Recevoir des notifications quand quelqu'un vous mentionne</p>
                        </label>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                    </div>
                </form>
            
            <!-- Onglet Mot de passe -->
            <?php elseif ($active_tab === 'password'): ?>
                <form method="POST">
                    <div class="form-group">
                        <label for="current_password">Mot de passe actuel</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">Nouveau mot de passe</label>
                        <input type="password" id="new_password" name="new_password" required>
                        <p class="hint">Minimum 6 caractères</p>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirmer le nouveau mot de passe</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Changer le mot de passe</button>
                    </div>
                </form>
            
            <!-- Onglet Apparence -->
            <?php elseif ($active_tab === 'appearance'): ?>
                <form method="POST">
                    <div class="form-group">
                        <label for="language">Langue</label>
                        <select id="language" name="language">
                            <option value="fr" <?php echo $user_settings['language'] === 'fr' ? 'selected' : ''; ?>>Français</option>
                            <option value="en" <?php echo $user_settings['language'] === 'en' ? 'selected' : ''; ?>>English</option>
                            <option value="es" <?php echo $user_settings['language'] === 'es' ? 'selected' : ''; ?>>Español</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Thème</label>
                        <div class="theme-options">
                            <label class="theme-option">
                                <input type="radio" name="theme" value="light" 
                                       <?php echo $user_settings['theme'] === 'light' ? 'checked' : ''; ?>>
                                <div class="theme-preview light-theme">
                                    <span>Clair</span>
                                </div>
                            </label>
                            
                            <label class="theme-option">
                                <input type="radio" name="theme" value="dark" 
                                       <?php echo $user_settings['theme'] === 'dark' ? 'checked' : ''; ?>>
                                <div class="theme-preview dark-theme">
                                    <span>Sombre</span>
                                </div>
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Aperçu de la nouvelle photo de profil
document.getElementById('profile_pic').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(event) {
            document.getElementById('profile-pic-preview').src = event.target.result;
        };
        reader.readAsDataURL(file);
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>