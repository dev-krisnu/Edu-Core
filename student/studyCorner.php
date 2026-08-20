<?php
/**
 * Study Corner - Learning Resources Hub
 * Access study materials, notes, and resources
 */
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

requireLogin();
requireRole(['student']);

$currentUser = getCurrentUser();
$pdo = getDbConnection();

// Fetch courses
$stmt = $pdo->query("SELECT DISTINCT title AS course_name FROM courses LIMIT 10");
$courses = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Fetch faculty-uploaded resources (lecture notes, slides, materials)
$stmt = $pdo->query("
    SELECT r.*, c.title AS course_title, u.full_name AS uploader_name
    FROM resources r
    LEFT JOIN courses c ON c.id = r.course_id
    LEFT JOIN users u ON u.id = r.uploaded_by
    ORDER BY r.created_at DESC
    LIMIT 20
");
$uploadedResources = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch exam questions as study materials
$stmt = $pdo->prepare("
    SELECT eq.*, e.title AS exam_title
    FROM exam_questions eq
    LEFT JOIN exams e ON eq.exam_id = e.id
    ORDER BY eq.id DESC
    LIMIT 20
");
$stmt->execute();
$studyMaterials = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Study Corner - EduCore</title>
    <?php $portalBase = '..'; include __DIR__ . '/../includes/portal_head.php'; ?>
    <style>
        .study-container {
            max-width: 1100px;
            margin: 0 auto;
        }

        .quick-access-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 12px;
            margin-bottom: 30px;
        }

        .quick-access-btn {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.2), rgba(34, 211, 238, 0.1));
            border: 1px solid rgba(99, 102, 241, 0.3);
            border-radius: 12px;
            padding: 16px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            color: #F5F4FF;
            text-decoration: none;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }

        .quick-access-btn:hover {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.3), rgba(34, 211, 238, 0.2));
            border-color: rgba(99, 102, 241, 0.5);
            transform: translateY(-2px);
        }

        .quick-access-icon {
            font-size: 1.8rem;
            color: #22D3EE;
        }

        .quick-access-text {
            font-size: 0.85rem;
            font-weight: 600;
            line-height: 1.2;
        }

        .resources-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 16px;
            margin-bottom: 30px;
        }

        .resource-card {
            background: rgba(99, 102, 241, 0.1);
            border: 1px solid rgba(99, 102, 241, 0.2);
            border-radius: 12px;
            padding: 18px;
            transition: all 0.3s ease;
        }

        .resource-card:hover {
            background: rgba(99, 102, 241, 0.15);
            border-color: rgba(99, 102, 241, 0.4);
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(99, 102, 241, 0.2);
        }

        .resource-icon {
            font-size: 2rem;
            margin-bottom: 12px;
            color: #22D3EE;
        }

        .resource-title {
            font-weight: 700;
            color: #F5F4FF;
            margin-bottom: 8px;
            font-size: 1.05rem;
        }

        .resource-description {
            font-size: 0.85rem;
            color: rgba(245, 244, 255, 0.6);
            margin-bottom: 12px;
            line-height: 1.4;
        }

        .resource-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 12px;
            border-top: 1px solid rgba(99, 102, 241, 0.1);
            font-size: 0.8rem;
        }

        .access-btn {
            padding: 6px 12px;
            background: linear-gradient(120deg, #6366F1, #22D3EE);
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .access-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(99, 102, 241, 0.3);
        }

        .difficulty-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .difficulty-easy {
            background: rgba(16, 185, 129, 0.2);
            color: #6EE7B7;
        }

        .difficulty-medium {
            background: rgba(251, 146, 60, 0.2);
            color: #FDBA74;
        }

        .difficulty-hard {
            background: rgba(239, 68, 68, 0.2);
            color: #FCA5A5;
        }

        .study-tips {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(34, 211, 238, 0.05));
            border: 1px solid rgba(99, 102, 241, 0.2);
            border-radius: 12px;
            padding: 20px;
            margin-top: 30px;
        }

        .tips-title {
            font-weight: 700;
            color: #F5F4FF;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .tips-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
        }

        .tip-item {
            background: rgba(99, 102, 241, 0.05);
            padding: 12px;
            border-radius: 8px;
            border-left: 3px solid #6366F1;
        }

        .tip-text {
            font-size: 0.9rem;
            color: rgba(245, 244, 255, 0.8);
            line-height: 1.4;
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

        @media (max-width: 768px) {
            .quick-access-grid {
                grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            }

            .resources-grid {
                grid-template-columns: 1fr;
            }

            .tips-list {
                grid-template-columns: 1fr;
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
            <div class="study-container">
                <!-- Header -->
                <div style="margin-bottom: 30px;">
                    <h1 class="h-display" style="margin: 0 0 8px 0;">Study Corner</h1>
                    <p style="color: rgba(255, 255, 255, 0.6); margin: 0;">Access learning resources and study materials</p>
                </div>

                <!-- Quick Access -->
                <h2 class="section-title">
                    <i class="bi bi-lightning-fill"></i> Quick Access
                </h2>
                <div class="quick-access-grid">
                    <button class="quick-access-btn" onclick="document.getElementById('facultyResourcesSection').scrollIntoView({behavior:'smooth'})">
                        <i class="bi bi-file-text quick-access-icon"></i>
                        <span class="quick-access-text">Study Notes</span>
                    </button>
                    <button class="quick-access-btn" onclick="document.getElementById('practiceQuestionsSection').scrollIntoView({behavior:'smooth'})">
                        <i class="bi bi-pencil-square quick-access-icon"></i>
                        <span class="quick-access-text">Practice Tests</span>
                    </button>
                    <button class="quick-access-btn" disabled style="opacity:0.45; cursor:not-allowed;" title="Not available yet">
                        <i class="bi bi-play-circle quick-access-icon"></i>
                        <span class="quick-access-text">Video Lectures</span>
                        <span style="font-size:0.7rem; opacity:0.8;">Coming soon</span>
                    </button>
                    <button class="quick-access-btn" disabled style="opacity:0.45; cursor:not-allowed;" title="Not available yet">
                        <i class="bi bi-book quick-access-icon"></i>
                        <span class="quick-access-text">E-Books</span>
                        <span style="font-size:0.7rem; opacity:0.8;">Coming soon</span>
                    </button>
                    <button class="quick-access-btn" disabled style="opacity:0.45; cursor:not-allowed;" title="Not available yet">
                        <i class="bi bi-newspaper quick-access-icon"></i>
                        <span class="quick-access-text">Research Papers</span>
                        <span style="font-size:0.7rem; opacity:0.8;">Coming soon</span>
                    </button>
                    <button class="quick-access-btn" disabled style="opacity:0.45; cursor:not-allowed;" title="Not available yet">
                        <i class="bi bi-chat-left-quote quick-access-icon"></i>
                        <span class="quick-access-text">Discussion Forum</span>
                        <span style="font-size:0.7rem; opacity:0.8;">Coming soon</span>
                    </button>
                </div>

                <!-- Faculty-Uploaded Resources -->
                <h2 class="section-title" id="facultyResourcesSection">
                    <i class="bi bi-cloud-arrow-down"></i> Faculty Resources (<?php echo count($uploadedResources); ?>)
                </h2>

                <?php if (count($uploadedResources) > 0): ?>
                    <div class="resources-grid">
                        <?php foreach ($uploadedResources as $r): ?>
                            <div class="resource-card">
                                <div class="resource-icon">
                                    <i class="bi bi-file-earmark-text"></i>
                                </div>
                                <div class="resource-title">
                                    <?php echo htmlspecialchars($r['title']); ?>
                                </div>
                                <div class="resource-description">
                                    <strong>Course:</strong> <?php echo htmlspecialchars($r['course_title'] ?? 'General'); ?><br>
                                    <strong>By:</strong> <?php echo htmlspecialchars($r['uploader_name'] ?? 'Faculty'); ?>
                                </div>
                                <div class="resource-footer">
                                    <span class="difficulty-badge difficulty-medium">
                                        <?php echo date('M d, Y', strtotime($r['created_at'])); ?>
                                    </span>
                                    <a href="../uploads/<?php echo htmlspecialchars($r['file_path']); ?>" target="_blank" class="access-btn">
                                        Download
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="color: rgba(245, 244, 255, 0.5);">No resources uploaded by faculty yet.</p>
                <?php endif; ?>

                <!-- Learning Resources -->
                <h2 class="section-title">
                    <i class="bi bi-book-half"></i> Practice Questions
                </h2>

                <?php if (count($studyMaterials) > 0): ?>
                    <div class="resources-grid">
                        <?php foreach ($studyMaterials as $material): 
                            $difficulty = $material['difficulty'] ?? 'medium';
                            $diffClass = 'difficulty-' . strtolower($difficulty);
                        ?>
                            <div class="resource-card">
                                <div class="resource-icon">
                                    <i class="bi bi-lightbulb"></i>
                                </div>
                                <div class="resource-title">
                                    <?php echo htmlspecialchars(substr($material['question_text'] ?? 'Study Material', 0, 50)); ?>...
                                </div>
                                <div class="resource-description">
                                    <strong>Type:</strong> Multiple Choice Question<br>
                                    <strong>Topic:</strong> General Concepts
                                </div>
                                <div class="resource-footer">
                                    <span class="difficulty-badge <?php echo $diffClass; ?>">
                                        <?php echo ucfirst($difficulty); ?>
                                    </span>
                                    <button class="access-btn" type="button"
                                        onclick='openPracticeModal(<?php echo json_encode([
                                            "question_text" => $material["question_text"],
                                            "options" => $material["options"],
                                            "correct_answer" => $material["correct_answer"],
                                            "difficulty" => $difficulty,
                                        ]); ?>)'>
                                        Access
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Study Tips -->
                <div class="study-tips">
                    <h3 class="tips-title">
                        <i class="bi bi-star"></i> Study Tips for Success
                    </h3>
                    <div class="tips-list">
                        <div class="tip-item">
                            <div class="tip-text">
                                <strong>1. Time Management</strong><br>
                                Create a study schedule and stick to it consistently.
                            </div>
                        </div>
                        <div class="tip-item">
                            <div class="tip-text">
                                <strong>2. Active Learning</strong><br>
                                Take notes and practice problems while studying.
                            </div>
                        </div>
                        <div class="tip-item">
                            <div class="tip-text">
                                <strong>3. Regular Revision</strong><br>
                                Review concepts regularly to improve retention.
                            </div>
                        </div>
                        <div class="tip-item">
                            <div class="tip-text">
                                <strong>4. Group Study</strong><br>
                                Discuss topics with peers for better understanding.
                            </div>
                        </div>
                        <div class="tip-item">
                            <div class="tip-text">
                                <strong>5. Use AI Tutor</strong><br>
                                Get personalized help from our AI tutor anytime.
                            </div>
                        </div>
                        <div class="tip-item">
                            <div class="tip-text">
                                <strong>6. Practice Tests</strong><br>
                                Take regular tests to evaluate your progress.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <div id="practiceOverlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:999; align-items:center; justify-content:center;" onclick="if(event.target===this) document.getElementById('practiceOverlay').style.display='none'">
        <div style="background:#1a1a3e; border:1px solid rgba(99,102,241,0.3); border-radius:16px; padding:28px; max-width:520px; width:90%; max-height:80vh; overflow-y:auto;">
            <div style="display:flex; justify-content:space-between; align-items:start; margin-bottom:16px;">
                <h3 style="color:#F5F4FF; margin:0;">Practice Question</h3>
                <button type="button" onclick="document.getElementById('practiceOverlay').style.display='none'" style="background:none; border:none; color:rgba(245,244,255,0.6); font-size:1.4rem; cursor:pointer;">&times;</button>
            </div>
            <p style="color:rgba(245,244,255,0.9); line-height:1.6;" id="pr_question"></p>
            <div id="pr_options"></div>
            <button type="button" id="pr_reveal_btn" onclick="revealPracticeAnswer()" style="margin-top:14px; padding:10px 18px; border:none; border-radius:8px; background:linear-gradient(120deg,#6366F1,#22D3EE); color:#fff; cursor:pointer;">Show Answer</button>
            <p id="pr_answer" style="display:none; margin-top:14px; padding-top:14px; border-top:1px solid rgba(255,255,255,0.1); color:#6EE7B7; font-weight:600;"></p>
        </div>
    </div>
    <script>
        let currentPracticeAnswer = '';
        function openPracticeModal(m) {
            document.getElementById('pr_question').textContent = m.question_text;
            document.getElementById('pr_answer').style.display = 'none';
            document.getElementById('pr_reveal_btn').style.display = 'inline-block';
            currentPracticeAnswer = m.correct_answer || '';

            let optionsHtml = '';
            if (m.options) {
                try {
                    const opts = typeof m.options === 'string' ? JSON.parse(m.options) : m.options;
                    const list = Array.isArray(opts) ? opts : Object.values(opts);
                    optionsHtml = '<ul style="color:rgba(245,244,255,0.75); margin-top:10px;">' + list.map(o => `<li>${o}</li>`).join('') + '</ul>';
                } catch (e) { /* short-answer / essay question, no options */ }
            }
            document.getElementById('pr_options').innerHTML = optionsHtml;
            document.getElementById('practiceOverlay').style.display = 'flex';
        }
        function revealPracticeAnswer() {
            const el = document.getElementById('pr_answer');
            el.textContent = 'Answer: ' + (currentPracticeAnswer || 'Not specified');
            el.style.display = 'block';
            document.getElementById('pr_reveal_btn').style.display = 'none';
        }
    </script>
</body>
</html>
