<?php
session_start();
require_once 'database.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$db = new Database();
$user = $db->getUserById($_SESSION['user']['id']);

// Default avatar URL
$defaultAvatar = 'https://ui-avatars.com/api/?name=' . urlencode($user['first_name'] . ' ' . $user['last_name']) . '&background=0D8ABC&color=fff&size=128';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name']);
    $lastName = trim($_POST['last_name']);
    $bio = isset($_POST['bio']) ? trim($_POST['bio']) : '';
    // Hobbies and social_links come as JSON strings from hidden inputs
    $hobbies = isset($_POST['hobbies']) ? trim($_POST['hobbies']) : null;
    $social_links = isset($_POST['social_links']) ? trim($_POST['social_links']) : null;
    $avatar = $user['avatar'];

    // Handle avatar upload (images + videos)
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/';
        if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
        $fileExtension = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg','jpeg','png','gif','mp4','mov','webm','avi','mkv'];
        if (in_array($fileExtension, $allowedExtensions)) {
            $fileName = uniqid() . '.' . $fileExtension;
            $filePath = $uploadDir . $fileName;
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $filePath)) {
                $avatar = $filePath;
                // create a post entry for this media so it appears in photos/feed
                if (method_exists($db, 'createPost')) {
                    try { $db->createPost($_SESSION['user']['id'], 'Updated profile picture', $filePath, 'public'); } catch (Exception $e) { /* ignore */ }
                }
            }
        }
    }

    // Update user; handle optional 'bio' column
    try {
        // Try to update including optional columns; if columns don't exist the ALTER in Database constructor should have added them
    $stmt = $db->pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, bio = ?, avatar = ?, hobbies = ?, social_links = ? WHERE id = ?");
    $stmt->execute([$firstName, $lastName, $bio, $avatar, $hobbies, $social_links, $_SESSION['user']['id']]);
    } catch (Exception $e) {
        // Fallback: update without optional columns
        $stmt = $db->pdo->prepare("UPDATE users SET first_name=?, last_name=?, avatar=? WHERE id=?");
        $stmt->execute([$firstName, $lastName, $avatar, $_SESSION['user']['id']]);
    }

    // Refresh session user data so updated bio/avatar/name appear immediately
    $updatedUser = $db->getUserById($_SESSION['user']['id']);
    if ($updatedUser) {
        $_SESSION['user'] = $updatedUser;
    }

    header('Location: profile.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Edit Profile</title>
    <link rel="stylesheet" href="styles.css" />
</head>
<body>
    <main class="profile-edit card" style="max-width:480px;margin:40px auto;padding:20px;">
        <h2>Edit Profile</h2>
        <form method="POST" enctype="multipart/form-data" id="editProfileForm">
            <div style="display:flex;gap:12px;">
                <div style="flex:1;">
                    <label>First Name</label>
                    <input type="text" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required />
                </div>
                <div style="flex:1;">
                    <label>Last Name</label>
                    <input type="text" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required />
                </div>
            </div>
            <label>Bio</label>
            <textarea name="bio" rows="3"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>

            <label>Hobbies (emoji + one word)</label>
            <div id="hobbiesSection">
                <div id="hobbiesList"></div>
                <div style="display:flex;gap:8px;margin-top:8px;">
                    <input id="hobbyEmoji" placeholder="🙂" maxlength="2" style="width:56px;padding:8px;border-radius:8px;border:1px solid var(--border);background:transparent;color:var(--text);" />
                    <input id="hobbyWord" placeholder="cycling" maxlength="20" style="flex:1;padding:8px;border-radius:8px;border:1px solid var(--border);background:transparent;color:var(--text);" />
                    <button type="button" id="addHobbyBtn" class="action">Add</button>
                </div>
                <input type="hidden" name="hobbies" id="hobbiesInput" />
            </div>

            <label>Social / Website Links</label>
            <div id="socialLinksSection">
                <div id="socialLinksList"></div>
                <div style="display:flex;gap:8px;margin-top:8px;">
                    <input id="socialLabel" placeholder="Twitter" maxlength="30" style="padding:8px;border-radius:8px;border:1px solid var(--border);background:transparent;color:var(--text);width:140px;" />
                    <input id="socialUrl" placeholder="https://twitter.com/you" style="flex:1;padding:8px;border-radius:8px;border:1px solid var(--border);background:transparent;color:var(--text);" />
                    <button type="button" id="addSocialBtn" class="action">Add</button>
                </div>
                <input type="hidden" name="social_links" id="socialLinksInput" />
            </div>

            <label>Profile Picture</label>
            <div style="display:flex;gap:12px;align-items:center;">
                <img id="avatarPreview" src="<?php echo $user['avatar'] ? htmlspecialchars($user['avatar']) : $defaultAvatar; ?>" alt="Avatar preview" style="width:80px;height:80px;border-radius:50%;object-fit:cover;border:1px solid var(--border);" />
                <div style="flex:1;">
                    <input type="file" name="avatar" id="avatarInput" accept="image/*,video/*" />
                    <div style="color:var(--text-dim);font-size:13px;margin-top:6px;">Choose an image or video. Media will be saved to your photos/feed.</div>
                </div>
            </div>

            <button type="submit" id="saveProfileBtn" style="margin-top:16px;">Save Changes</button>
        </form>
        <a href="profile.php" style="display:block;margin-top:16px;text-align:center;     background-image: linear-gradient(45deg, #2d8fc8, transparent);">Back to Profile</a>
        <script>
            document.addEventListener('DOMContentLoaded', function(){
                var avatarInput = document.getElementById('avatarInput');
                var avatarPreview = document.getElementById('avatarPreview');
                if (avatarInput) {
                    avatarInput.addEventListener('change', function(e){
                        var f = e.target.files[0];
                        if (!f) return;
                        var reader = new FileReader();
                        reader.onload = function(ev){
                            if (f.type.startsWith('image/')) {
                                avatarPreview.src = ev.target.result;
                            } else {
                                avatarPreview.src = 'https://via.placeholder.com/80x80?text=VIDEO';
                            }
                        };
                        reader.readAsDataURL(f);
                    });
                }
                // Hobbies and social links client-side logic
                var hobbiesInput = document.getElementById('hobbiesInput');
                var hobbiesList = document.getElementById('hobbiesList');
                var addHobbyBtn = document.getElementById('addHobbyBtn');
                var hobbyEmoji = document.getElementById('hobbyEmoji');
                var hobbyWord = document.getElementById('hobbyWord');
                var socialList = document.getElementById('socialLinksList');
                var socialInput = document.getElementById('socialLinksInput');
                var addSocialBtn = document.getElementById('addSocialBtn');
                var socialLabel = document.getElementById('socialLabel');
                var socialUrl = document.getElementById('socialUrl');

                var existingHobbies = <?php echo json_encode($user['hobbies'] ? json_decode($user['hobbies'], true) : []); ?>;
                var existingSocial = <?php echo json_encode($user['social_links'] ? json_decode($user['social_links'], true) : []); ?>;

                function renderHobbies(){
                    hobbiesList.innerHTML = '';
                    existingHobbies.forEach(function(h, idx){
                        var el = document.createElement('div');
                        el.className = 'hobbyItem';
                        el.style.display='flex'; el.style.alignItems='center'; el.style.gap='8px'; el.style.marginTop='8px';
                        var span = document.createElement('span'); span.textContent = (h.emoji || '') + ' ' + (h.word || '');
                        var btn = document.createElement('button'); btn.type='button'; btn.textContent='Remove'; btn.className='action';
                        btn.addEventListener('click', function(){ existingHobbies.splice(idx,1); renderHobbies(); });
                        el.appendChild(span); el.appendChild(btn);
                        hobbiesList.appendChild(el);
                    });
                    hobbiesInput.value = JSON.stringify(existingHobbies);
                }

                function renderSocial(){
                    socialList.innerHTML = '';
                    existingSocial.forEach(function(s, idx){
                        var el = document.createElement('div');
                        el.className = 'socialItem';
                        el.style.display='flex'; el.style.alignItems='center'; el.style.justifyContent='space-between'; el.style.marginTop='8px';
                        var left = document.createElement('div'); left.innerHTML = '<strong>'+escapeHtml(s.label || '')+'</strong>: <a href="'+escapeHtml(s.url || '')+'" target="_blank">'+escapeHtml(s.url || '')+'</a>';
                        var btn = document.createElement('button'); btn.type='button'; btn.textContent='Remove'; btn.className='action';
                        btn.addEventListener('click', function(){ existingSocial.splice(idx,1); renderSocial(); });
                        el.appendChild(left); el.appendChild(btn);
                        socialList.appendChild(el);
                    });
                    socialInput.value = JSON.stringify(existingSocial);
                }

                function escapeHtml(str){ return String(str||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

                addHobbyBtn.addEventListener('click', function(){
                    var emoji = hobbyEmoji.value.trim(); var word = hobbyWord.value.trim();
                    if(!emoji || !word) { alert('Provide emoji and one-word hobby'); return; }
                    existingHobbies.push({emoji:emoji, word:word}); hobbyEmoji.value=''; hobbyWord.value=''; renderHobbies();
                });
                addSocialBtn.addEventListener('click', function(){
                    var label = socialLabel.value.trim(); var url = socialUrl.value.trim();
                    if(!url) { alert('Provide a URL'); return; }
                    existingSocial.push({label:label, url:url}); socialLabel.value=''; socialUrl.value=''; renderSocial();
                });

                renderHobbies(); renderSocial();
            });
        </script>
    </main>
</body>
</html>
