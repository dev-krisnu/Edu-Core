<?php
/**
 * My Profile - view/edit own info and upload a profile picture.
 * Works for every role; lives at project root like logout.php so
 * every portal page (any depth) can link to it consistently.
 */
declare(strict_types=1);
session_start();

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/FileUpload.php';
require_once __DIR__ . '/includes/auth_check.php';

requireLogin();

$currentUser = getCurrentUser();
$pdo = getDbConnection();

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_FILES['photo']['name'])) {
        // Only allow image types for a profile picture, even though
        // FileUploadHandler's general allow-list includes documents too.
        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
            $message = 'Profile picture must be JPG or PNG.';
            $messageType = 'error';
        } else {
            $uploader = new FileUploadHandler(__DIR__ . '/uploads');
            $result = $uploader->upload($_FILES['photo'], 'avatars');
            if ($result['success']) {
                // Remove the old photo file, if any, before saving the new path.
                $old = $pdo->prepare("SELECT photo FROM users WHERE id = ?");
                $old->execute([$currentUser['id']]);
                $oldPhoto = $old->fetchColumn();

                $stmt = $pdo->prepare("UPDATE users SET photo = ? WHERE id = ?");
                $stmt->execute([$result['file'], $currentUser['id']]);

                if ($oldPhoto) {
                    $oldPath = __DIR__ . '/uploads/' . $oldPhoto;
                    if (is_file($oldPath)) {
                        @unlink($oldPath);
                    }
                }

                $message = 'Profile picture updated!';
                $messageType = 'success';
                $currentUser['photo'] = $result['file'];
            } else {
                $message = 'Upload failed: ' . ($result['error'] ?? 'unknown error');
                $messageType = 'error';
            }
        }
    } elseif (($_POST['action'] ?? '') === 'update_details') {
        $phone = trim($_POST['phone'] ?? '');
        $stmt = $pdo->prepare("UPDATE users SET phone = ? WHERE id = ?");
        $stmt->execute([$phone, $currentUser['id']]);
        $message = 'Details updated!';
        $messageType = 'success';
        $currentUser['phone'] = $phone;
    }
}

// Re-fetch full row (phone etc aren't in the lean getCurrentUser() select)
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$currentUser['id']]);
$fullUser = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - EduCore</title>
    <?php $portalBase = '.'; include __DIR__ . '/includes/portal_head.php'; ?>
    <style>
        .profile-container { max-width: 640px; margin: 0 auto; }
        .avatar-wrap { display: flex; align-items: center; gap: 24px; margin-bottom: 30px; }
        .avatar-img {
            width: 100px; height: 100px; border-radius: 50%; object-fit: cover;
            border: 3px solid rgba(99, 102, 241, 0.4);
        }
        .avatar-fallback {
            width: 100px; height: 100px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 2.2rem; font-weight: 700; color: #fff;
            background: linear-gradient(135deg, #6366F1, #22D3EE);
            border: 3px solid rgba(99, 102, 241, 0.4);
        }
        .profile-card {
            background: rgba(255,255,255,0.03); border: 1px solid rgba(99,102,241,0.2);
            border-radius: 16px; padding: 28px; margin-bottom: 20px;
        }
        .form-label { display:block; margin-bottom:8px; color: rgba(245,244,255,0.8); font-weight:600; font-size:0.9rem; }
        .form-input { width:100%; padding:12px; border-radius:8px; border:1px solid rgba(99,102,241,0.3); background:rgba(255,255,255,0.05); color:#F5F4FF; font-family:inherit; }
        .submit-btn { padding:12px 24px; background:linear-gradient(120deg,#6366F1,#22D3EE); color:#fff; border:none; border-radius:8px; font-weight:600; cursor:pointer; }
        .message { padding:14px; border-radius:8px; margin-bottom:20px; border-left:4px solid; }
        .message.success { background: rgba(16,185,129,0.1); border-color:#10B981; color:#6EE7B7; }
        .message.error { background: rgba(239,68,68,0.1); border-color:#EF4444; color:#FCA5A5; }
    </style>
</head>
<body class="portal-page" data-role="<?php echo htmlspecialchars($fullUser['role']); ?>">
    <div class="aurora-bg"><div class="aurora-blob b1"></div><div class="aurora-blob b2"></div><div class="aurora-blob b3"></div></div>
    <div class="grain"></div>
    <div class="container-shell">
        <?php $portalBase = '.'; include __DIR__ . '/includes/sidebar.php'; ?>
        <main style="flex: 1; z-index: 2; padding: 40px 20px;">
            <div class="profile-container">
                <h1 class="h-display" style="margin: 0 0 24px 0;">My Profile</h1>

                <?php if ($message): ?>
                    <div class="message <?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>

                <div class="profile-card">
                    <div class="avatar-wrap">
                        <?php if (!empty($fullUser['photo']) && is_file(__DIR__ . '/uploads/' . $fullUser['photo'])): ?>
                            <img src="uploads/<?php echo htmlspecialchars($fullUser['photo']); ?>" class="avatar-img" alt="Profile photo">
                        <?php else: ?>
                            <div class="avatar-fallback"><?php echo htmlspecialchars(strtoupper(substr($fullUser['full_name'], 0, 1))); ?></div>
                        <?php endif; ?>
                        <div>
                            <div style="font-weight:700; font-size:1.2rem; color:#F5F4FF;"><?php echo htmlspecialchars($fullUser['full_name']); ?></div>
                            <div style="color:rgba(245,244,255,0.6);"><?php echo htmlspecialchars($fullUser['email']); ?></div>
                            <div style="color:rgba(245,244,255,0.5); font-size:0.85rem; text-transform:capitalize;"><?php echo htmlspecialchars(str_replace('_', ' ', $fullUser['role'])); ?></div>
                        </div>
                    </div>
                    <form method="POST" enctype="multipart/form-data">
                        <label class="form-label">Upload New Photo (JPG/PNG)</label>
                        <input type="file" name="photo" accept=".jpg,.jpeg,.png" class="form-input" required>
                        <button type="submit" class="submit-btn" style="margin-top:14px;"><i class="bi bi-upload"></i> Upload Photo</button>
                    </form>
                </div>

                <div class="profile-card">
                    <h3 style="color:#F5F4FF; margin:0 0 16px 0;">Contact Details</h3>
                    <form method="POST">
                        <input type="hidden" name="action" value="update_details">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="phone" class="form-input" value="<?php echo htmlspecialchars($fullUser['phone'] ?? ''); ?>">
                        <button type="submit" class="submit-btn" style="margin-top:14px;">Save Details</button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
