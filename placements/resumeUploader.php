<?php
/**
 * Placements - Resume Uploader
 * Upload and manage student resumes
 */
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/FileUpload.php';
require_once __DIR__ . '/../includes/auth_check.php';

requireLogin();
requireRole(['tpo']);

$currentUser = getCurrentUser();
$pdo = getDbConnection();

$message = '';
$messageType = '';

// Handle resume upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['resume'])) {
    try {
        $uploader = new FileUploadHandler(__DIR__ . '/../uploads');
        $result = $uploader->upload($_FILES['resume'], 'resumes');

        if ($result['success']) {
            $student_id = intval($_POST['student_id']);
            $stmt = $pdo->prepare("
                INSERT INTO student_resumes (student_id, file_path, file_size, uploaded_at)
                VALUES (?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE file_path = VALUES(file_path), file_size = VALUES(file_size), uploaded_at = NOW()
            ");
            $stmt->execute([$student_id, $result['file'], $result['size']]);
            $message = 'Resume uploaded successfully!';
            $messageType = 'success';
        } else {
            $message = 'Upload failed: ' . $result['file'];
            $messageType = 'error';
        }
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Fetch all student resumes
$stmt = $pdo->prepare("
    SELECT sr.*, u.name as student_name, u.email,
           COUNT(DISTINCT pa.id) as applications
    FROM student_resumes sr
    JOIN users u ON sr.student_id = u.id
    LEFT JOIN placement_applications pa ON u.id = pa.student_id
    GROUP BY sr.id
    ORDER BY sr.uploaded_at DESC
    LIMIT 50
");
$stmt->execute([]);
$resumes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get students without resumes
$stmt = $pdo->query("
    SELECT u.id, u.name, u.email
    FROM users u
    LEFT JOIN student_resumes sr ON u.id = sr.student_id
    WHERE u.role = 'student' AND sr.id IS NULL
    LIMIT 20
");
$studentsWithoutResumes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resume Uploader - EduCore</title>
    <?php $portalBase = '..'; include __DIR__ . '/../includes/portal_head.php'; ?>
    <style>
        .resumes-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .upload-section {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.2), rgba(253, 224, 71, 0.1));
            border: 2px dashed rgba(245, 158, 11, 0.3);
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 30px;
            text-align: center;
        }

        .upload-icon {
            font-size: 3rem;
            color: #FCD34D;
            margin-bottom: 10px;
        }

        .upload-text {
            color: rgba(245, 244, 255, 0.7);
            margin-bottom: 16px;
        }

        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
        }

        .file-input {
            position: absolute;
            opacity: 0;
            cursor: pointer;
        }

        .upload-btn {
            padding: 12px 24px;
            background: linear-gradient(120deg, #F59E0B, #FCD34D);
            color: #18181B;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-block;
        }

        .upload-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(245, 158, 11, 0.4);
        }

        .message {
            padding: 14px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid;
        }

        .message.success {
            background: rgba(16, 185, 129, 0.1);
            border-color: #10B981;
            color: #6EE7B7;
        }

        .message.error {
            background: rgba(239, 68, 68, 0.1);
            border-color: #EF4444;
            color: #FCA5A5;
        }

        .resumes-table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(245, 158, 11, 0.2);
            border-radius: 14px;
            overflow: hidden;
        }

        .resumes-table th {
            background: rgba(245, 158, 11, 0.1);
            padding: 14px;
            text-align: left;
            border-bottom: 2px solid rgba(245, 158, 11, 0.3);
            color: #F5F4FF;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .resumes-table td {
            padding: 14px;
            border-bottom: 1px solid rgba(245, 158, 11, 0.1);
            color: rgba(245, 244, 255, 0.8);
        }

        .resumes-table tbody tr:hover {
            background: rgba(245, 158, 11, 0.05);
        }

        .file-badge {
            display: inline-block;
            padding: 4px 8px;
            background: rgba(245, 158, 11, 0.2);
            color: #FCD34D;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .action-btn {
            padding: 6px 12px;
            background: linear-gradient(120deg, #F59E0B, #FCD34D);
            color: #18181B;
            border: none;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-right: 4px;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(245, 158, 11, 0.3);
        }

        .section-title {
            font-size: 1.2rem;
            font-weight: 700;
            margin: 24px 0 16px 0;
            color: #F5F4FF;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            background: rgba(245, 158, 11, 0.05);
            border-radius: 12px;
            color: rgba(245, 244, 255, 0.6);
        }

        @media (max-width: 768px) {
            .resumes-table {
                font-size: 0.85rem;
            }

            .resumes-table th,
            .resumes-table td {
                padding: 8px;
            }

            .upload-section {
                padding: 20px;
            }
        }
    </style>
</head>
<body class="portal-page" data-role="placements">
    <div class="aurora-bg">
        <div class="aurora-blob b1"></div>
        <div class="aurora-blob b2"></div>
        <div class="aurora-blob b3"></div>
    </div>
    <div class="grain"></div>

    <div class="container-shell">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <main style="flex: 1; z-index: 2; padding: 40px 20px;">
            <div class="resumes-container">
                <!-- Header -->
                <div style="margin-bottom: 30px;">
                    <h1 class="h-display" style="margin: 0 0 8px 0;">Resume Management</h1>
                    <p style="color: rgba(255, 255, 255, 0.6); margin: 0;">Upload and manage student resumes</p>
                </div>

                <?php if ($message): ?>
                    <div class="message <?php echo $messageType; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <!-- Upload Section -->
                <div class="upload-section">
                    <div class="upload-icon">
                        <i class="bi bi-cloud-arrow-up"></i>
                    </div>
                    <div class="upload-text">
                        <p style="margin: 0 0 8px 0;">Upload Student Resume</p>
                        <small style="color: rgba(245, 244, 255, 0.5);">PDF, DOC, or DOCX format • Max 50MB</small>
                    </div>

                    <form method="POST" enctype="multipart/form-data" style="margin-top: 16px;">
                        <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
                            <div class="file-input-wrapper">
                                <input type="file" id="resume" name="resume" class="file-input" accept=".pdf,.doc,.docx" required>
                                <label for="resume" class="upload-btn">
                                    <i class="bi bi-folder-open"></i> Choose File
                                </label>
                            </div>

                            <select name="student_id" style="padding: 12px 16px; background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.3); border-radius: 8px; color: #F5F4FF; font-family: inherit; font-weight: 600;" required>
                                <option value="">Select Student</option>
                                <?php foreach ($studentsWithoutResumes as $student): ?>
                                    <option value="<?php echo $student['id']; ?>">
                                        <?php echo htmlspecialchars($student['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <button type="submit" class="upload-btn">
                                <i class="bi bi-upload"></i> Upload Resume
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Uploaded Resumes -->
                <h2 class="section-title">
                    <i class="bi bi-file-earmark-pdf"></i> Uploaded Resumes (<?php echo count($resumes); ?>)
                </h2>

                <?php if (count($resumes) > 0): ?>
                    <table class="resumes-table">
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Email</th>
                                <th>File Size</th>
                                <th>Uploaded Date</th>
                                <th>Applications</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($resumes as $resume): 
                                $uploadedDate = new DateTime($resume['uploaded_at']);
                                $fileSize = $resume['file_size'] > 1024*1024 ? 
                                    number_format($resume['file_size']/(1024*1024), 2) . ' MB' : 
                                    number_format($resume['file_size']/1024, 2) . ' KB';
                            ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($resume['student_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($resume['email']); ?></td>
                                    <td><span class="file-badge"><?php echo $fileSize; ?></span></td>
                                    <td><?php echo $uploadedDate->format('M d, Y'); ?></td>
                                    <td><?php echo $resume['applications']; ?> drive(s)</td>
                                    <td>
                                        <button class="action-btn" onclick="alert('Download: ' + '<?php echo addslashes($resume['student_name']); ?>')">
                                            <i class="bi bi-download"></i> Download
                                        </button>
                                        <button class="action-btn" onclick="alert('Share with recruiters')">
                                            <i class="bi bi-share"></i> Share
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data">
                        <i class="bi bi-inbox" style="font-size: 2rem; margin-bottom: 10px;"></i>
                        <p>No resumes uploaded yet. Start by uploading student resumes.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
