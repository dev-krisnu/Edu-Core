<?php
/**
 * Project Submit - Assignment Submission
 * Upload and submit projects/assignments
 */
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/FileUpload.php';
require_once __DIR__ . '/../includes/auth_check.php';

requireLogin();
requireRole(['student']);

$currentUser = getCurrentUser();
$pdo = getDbConnection();
$uploadHandler = new FileUploadHandler();

$message = '';
$messageType = '';

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['project_file'])) {
    try {
        $result = $uploadHandler->upload($_FILES['project_file'], 'projects');
        if ($result['success']) {
            $message = 'Project submitted successfully!';
            $messageType = 'success';
        } else {
            $message = 'File upload failed. Please try again.';
            $messageType = 'error';
        }
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Fetch assigned projects
$stmt = $pdo->prepare("
    SELECT e.*, e.title AS exam_name, e.end_time AS exam_date
    FROM exams e
    WHERE e.status IN ('scheduled', 'active')
    ORDER BY e.end_time DESC
    LIMIT 10
");
$stmt->execute();
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch submitted projects
$stmt = $pdo->prepare("
    SELECT * FROM system_logs 
    WHERE user_id = ? AND action LIKE 'project_submit%'
    ORDER BY created_at DESC
    LIMIT 10
");
$stmt->execute([$currentUser['id']]);
$submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Submit - EduCore</title>
    <?php $portalBase = '..'; include __DIR__ . '/../includes/portal_head.php'; ?>
    <style>
        .projects-container {
            max-width: 1000px;
            margin: 0 auto;
        }

        .upload-section {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.15), rgba(34, 211, 238, 0.08));
            border: 2px dashed rgba(99, 102, 241, 0.4);
            border-radius: 16px;
            padding: 40px;
            text-align: center;
            margin-bottom: 30px;
            transition: all 0.3s ease;
        }

        .upload-section:hover {
            border-color: #6366F1;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.2), rgba(34, 211, 238, 0.12));
        }

        .upload-icon {
            font-size: 3rem;
            color: #6366F1;
            margin-bottom: 16px;
        }

        .upload-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: #F5F4FF;
            margin-bottom: 8px;
        }

        .upload-description {
            color: rgba(245, 244, 255, 0.6);
            margin-bottom: 20px;
            font-size: 0.95rem;
        }

        .file-input-wrapper {
            position: relative;
            display: inline-block;
        }

        .file-input-wrapper input[type="file"] {
            display: none;
        }

        .file-input-btn {
            padding: 12px 30px;
            background: linear-gradient(120deg, #6366F1, #22D3EE);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .file-input-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.4);
        }

        .form-group {
            margin-bottom: 16px;
            text-align: left;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            color: rgba(245, 244, 255, 0.8);
            font-weight: 600;
            font-size: 0.9rem;
        }

        .form-input {
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid rgba(99, 102, 241, 0.3);
            background: rgba(255, 255, 255, 0.05);
            color: #F5F4FF;
            font-family: inherit;
            font-size: 0.9rem;
        }

        .form-input:focus {
            outline: none;
            border-color: #6366F1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
        }

        .submit-btn {
            padding: 12px 30px;
            background: linear-gradient(120deg, #6366F1, #22D3EE);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.4);
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

        .projects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 16px;
            margin-bottom: 30px;
        }

        .project-card {
            background: rgba(99, 102, 241, 0.1);
            border: 1px solid rgba(99, 102, 241, 0.2);
            border-radius: 12px;
            padding: 16px;
            transition: all 0.3s ease;
        }

        .project-card:hover {
            background: rgba(99, 102, 241, 0.15);
            border-color: rgba(99, 102, 241, 0.4);
            transform: translateY(-4px);
        }

        .project-title {
            font-weight: 700;
            color: #F5F4FF;
            margin-bottom: 8px;
        }

        .project-meta {
            font-size: 0.85rem;
            color: rgba(245, 244, 255, 0.6);
            margin-bottom: 10px;
        }

        .due-date {
            display: inline-block;
            padding: 6px 10px;
            background: rgba(249, 115, 22, 0.2);
            color: #FDBA74;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-top: 10px;
        }

        .section-title {
            font-size: 1.2rem;
            font-weight: 700;
            margin: 30px 0 16px 0;
            color: #F5F4FF;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .submissions-table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(99, 102, 241, 0.2);
            border-radius: 12px;
            overflow: hidden;
        }

        .submissions-table th {
            background: rgba(99, 102, 241, 0.1);
            padding: 12px;
            text-align: left;
            border-bottom: 2px solid rgba(99, 102, 241, 0.3);
            color: #F5F4FF;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .submissions-table td {
            padding: 12px;
            border-bottom: 1px solid rgba(99, 102, 241, 0.1);
            color: rgba(245, 244, 255, 0.8);
        }

        @media (max-width: 768px) {
            .projects-grid {
                grid-template-columns: 1fr;
            }

            .upload-section {
                padding: 24px;
            }
        }
    </style>
</head>
<body class="portal-page" data-role="student">
    <div class="aurora-bg">
        <div class="aurora-blob b1"></div>
        <div class="aurora-blob b2"></div>
        <div class="aurora-blob b3"></div>
    </div>
    <div class="grain"></div>

    <div class="container-shell">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <main style="flex: 1; z-index: 2; padding: 40px 20px;">
            <div class="projects-container">
                <!-- Header -->
                <div style="margin-bottom: 30px;">
                    <h1 class="h-display" style="margin: 0 0 8px 0;">Project Submit</h1>
                    <p style="color: rgba(255, 255, 255, 0.6); margin: 0;">Submit your projects and assignments here</p>
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
                    <div class="upload-title">Submit Your Project</div>
                    <div class="upload-description">
                        Drag and drop your files here or click to browse
                    </div>

                    <form method="POST" enctype="multipart/form-data" style="display: inline;">
                        <div style="margin-bottom: 20px;">
                            <div class="form-group">
                                <label class="form-label">Project Title</label>
                                <input type="text" class="form-input" name="project_title" placeholder="Enter project title" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Course/Subject</label>
                                <input type="text" class="form-input" name="course" placeholder="Select course" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Description</label>
                                <textarea class="form-input" name="description" placeholder="Brief project description" style="resize: vertical; min-height: 80px;"></textarea>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Select File</label>
                                <div class="file-input-wrapper">
                                    <input type="file" id="projectFile" name="project_file" accept=".pdf,.doc,.docx,.zip,.ppt,.pptx,.jpg,.png" required>
                                    <label for="projectFile" class="file-input-btn">
                                        <i class="bi bi-paperclip"></i>
                                        Choose File
                                    </label>
                                    <span id="fileName" style="margin-left: 10px; color: rgba(245, 244, 255, 0.6);"></span>
                                </div>
                                <div style="font-size: 0.8rem; color: rgba(245, 244, 255, 0.5); margin-top: 8px;">
                                    Supported formats: PDF, DOC, DOCX, ZIP, PPT, PPTX, JPG, PNG (Max 50MB)
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="submit-btn">
                            <i class="bi bi-upload"></i> Submit Project
                        </button>
                    </form>
                </div>

                <!-- Assigned Projects -->
                <h2 class="section-title">
                    <i class="bi bi-list-task"></i> Assigned Projects (<?php echo count($projects); ?>)
                </h2>

                <?php if (count($projects) > 0): ?>
                    <div class="projects-grid">
                        <?php foreach ($projects as $project): 
                            $dueDate = new DateTime($project['exam_date']);
                            $today = new DateTime();
                            $isOverdue = $dueDate < $today;
                        ?>
                            <div class="project-card">
                                <div class="project-title"><?php echo htmlspecialchars($project['exam_name'] ?? 'Project'); ?></div>
                                <div class="project-meta">
                                    <i class="bi bi-info-circle"></i> Status: Pending
                                </div>
                                <div class="project-meta">
                                    <i class="bi bi-calendar"></i> <?php echo $dueDate->format('M d, Y'); ?>
                                </div>
                                <?php if ($isOverdue): ?>
                                    <div class="due-date" style="background: rgba(239, 68, 68, 0.2); color: #FCA5A5;">
                                        ⚠ OVERDUE
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Submission History -->
                <h2 class="section-title">
                    <i class="bi bi-file-check"></i> Submission History (<?php echo count($submissions); ?>)
                </h2>

                <?php if (count($submissions) > 0): ?>
                    <table class="submissions-table">
                        <thead>
                            <tr>
                                <th>Project Name</th>
                                <th>Submitted Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($submissions as $submission): 
                                $submittedDate = new DateTime($submission['created_at']);
                            ?>
                                <tr>
                                    <td><i class="bi bi-file-earmark"></i> Project #<?php echo substr($submission['id'], 0, 6); ?></td>
                                    <td><?php echo $submittedDate->format('M d, Y H:i'); ?></td>
                                    <td>
                                        <span style="color: #6EE7B7; font-weight: 600;">
                                            <i class="bi bi-check-circle"></i> Submitted
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        document.getElementById('projectFile').addEventListener('change', function() {
            document.getElementById('fileName').textContent = this.files[0]?.name || '';
        });
    </script>
</body>
</html>
