<?php
/**
 * Faculty - Question Bank
 * Manage collection of exam questions
 */
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

requireLogin();
requireRole(['faculty']);

$currentUser = getCurrentUser();
$pdo = getDbConnection();

$difficulty_filter = trim($_GET['difficulty'] ?? '');
$course_filter = intval($_GET['course'] ?? 0);

// Fetch questions with filters
$sql = "SELECT eq.*, c.course_name,
               COUNT(e.id) as times_used
        FROM exam_questions eq
        LEFT JOIN courses c ON eq.course_id = c.id
        LEFT JOIN exams e ON eq.exam_id = e.id
        WHERE eq.created_by = ?";
$params = [$currentUser['id']];

if ($difficulty_filter) {
    $sql .= " AND eq.difficulty = ?";
    $params[] = $difficulty_filter;
}

if ($course_filter) {
    $sql .= " AND eq.course_id = ?";
    $params[] = $course_filter;
}

$sql .= " GROUP BY eq.id ORDER BY eq.created_at DESC LIMIT 50";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get faculty courses
$stmt = $pdo->prepare("SELECT id, course_name FROM courses WHERE faculty_id = ? ORDER BY course_name");
$stmt->execute([$currentUser['id']]);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get difficulty counts
$stmt = $pdo->prepare("SELECT difficulty, COUNT(*) as count FROM exam_questions WHERE created_by = ? GROUP BY difficulty");
$stmt->execute([$currentUser['id']]);
$difficultyCounts = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $difficultyCounts[$row['difficulty']] = $row['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Question Bank - EduCore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="../assets/css/educore.css" rel="stylesheet">
    <link href="../assets/css/themes.css" rel="stylesheet">
    <style>
        .bank-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 12px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: rgba(139, 92, 246, 0.1);
            border: 1px solid rgba(139, 92, 246, 0.2);
            border-radius: 12px;
            padding: 14px;
            text-align: center;
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: #D8B4FE;
            margin-bottom: 4px;
        }

        .stat-label {
            font-size: 0.8rem;
            color: rgba(245, 244, 255, 0.6);
            text-transform: uppercase;
        }

        .filter-bar {
            display: flex;
            gap: 8px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 8px 14px;
            background: rgba(139, 92, 246, 0.1);
            border: 1px solid rgba(139, 92, 246, 0.3);
            color: rgba(245, 244, 255, 0.8);
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .filter-btn.active {
            background: linear-gradient(120deg, #8B5CF6, #D8B4FE);
            border-color: transparent;
            color: white;
        }

        .questions-table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(139, 92, 246, 0.2);
            border-radius: 14px;
            overflow: hidden;
        }

        .questions-table th {
            background: rgba(139, 92, 246, 0.1);
            padding: 14px;
            text-align: left;
            border-bottom: 2px solid rgba(139, 92, 246, 0.3);
            color: #F5F4FF;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .questions-table td {
            padding: 14px;
            border-bottom: 1px solid rgba(139, 92, 246, 0.1);
            color: rgba(245, 244, 255, 0.8);
        }

        .questions-table tbody tr:hover {
            background: rgba(139, 92, 246, 0.05);
        }

        .difficulty-badge {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 6px;
            font-size: 0.8rem;
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

        .type-badge {
            display: inline-block;
            padding: 4px 8px;
            background: rgba(139, 92, 246, 0.2);
            color: #D8B4FE;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .action-btn {
            padding: 6px 12px;
            background: linear-gradient(120deg, #8B5CF6, #D8B4FE);
            color: white;
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
            box-shadow: 0 6px 12px rgba(139, 92, 246, 0.3);
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
            .questions-table {
                font-size: 0.85rem;
            }

            .questions-table th,
            .questions-table td {
                padding: 8px;
            }

            .action-btn {
                padding: 4px 8px;
                font-size: 0.7rem;
                margin-right: 2px;
            }
        }
    </style>
</head>
<body data-role="faculty">
    <div class="aurora-bg">
        <div class="aurora-blob b1"></div>
        <div class="aurora-blob b2"></div>
        <div class="aurora-blob b3"></div>
    </div>
    <div class="grain"></div>

    <div class="container-shell">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <main style="flex: 1; z-index: 2; padding: 40px 20px;">
            <div class="bank-container">
                <!-- Header -->
                <div style="margin-bottom: 30px;">
                    <h1 class="h-display" style="margin: 0 0 8px 0;">Question Bank</h1>
                    <p style="color: rgba(255, 255, 255, 0.6); margin: 0;">Manage your collection of exam questions</p>
                </div>

                <!-- Statistics -->
                <div class="stats-cards">
                    <div class="stat-card">
                        <div class="stat-value"><?php echo count($questions); ?></div>
                        <div class="stat-label">Total Questions</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $difficultyCounts['easy'] ?? 0; ?></div>
                        <div class="stat-label">Easy</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $difficultyCounts['medium'] ?? 0; ?></div>
                        <div class="stat-label">Medium</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $difficultyCounts['hard'] ?? 0; ?></div>
                        <div class="stat-label">Hard</div>
                    </div>
                </div>

                <!-- Filter Bar -->
                <h2 class="section-title">
                    <i class="bi bi-funnel"></i> Filter Questions
                </h2>
                <div class="filter-bar">
                    <a href="?" class="filter-btn <?php echo !$difficulty_filter && !$course_filter ? 'active' : ''; ?>">
                        All Questions
                    </a>
                    <a href="?difficulty=easy" class="filter-btn <?php echo $difficulty_filter === 'easy' ? 'active' : ''; ?>">
                        Easy
                    </a>
                    <a href="?difficulty=medium" class="filter-btn <?php echo $difficulty_filter === 'medium' ? 'active' : ''; ?>">
                        Medium
                    </a>
                    <a href="?difficulty=hard" class="filter-btn <?php echo $difficulty_filter === 'hard' ? 'active' : ''; ?>">
                        Hard
                    </a>
                </div>

                <!-- Questions Table -->
                <h2 class="section-title">
                    <i class="bi bi-list-check"></i> Questions (<?php echo count($questions); ?>)
                </h2>

                <?php if (count($questions) > 0): ?>
                    <table class="questions-table">
                        <thead>
                            <tr>
                                <th>Question</th>
                                <th>Type</th>
                                <th>Course</th>
                                <th>Difficulty</th>
                                <th>Times Used</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($questions as $question): 
                                $createdDate = new DateTime($question['created_at']);
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars(substr($question['question_text'], 0, 40)); ?>...</td>
                                    <td><span class="type-badge"><?php echo ucfirst($question['question_type'] ?? 'MCQ'); ?></span></td>
                                    <td><?php echo htmlspecialchars($question['course_name'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="difficulty-badge difficulty-<?php echo strtolower($question['difficulty']); ?>">
                                            <?php echo ucfirst($question['difficulty']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $question['times_used']; ?></td>
                                    <td><?php echo $createdDate->format('M d, Y'); ?></td>
                                    <td>
                                        <button class="action-btn" onclick="alert('View: ' + '<?php echo addslashes(substr($question['question_text'], 0, 30)); ?>')">
                                            <i class="bi bi-eye"></i> View
                                        </button>
                                        <button class="action-btn" onclick="alert('Edit: ' + '<?php echo addslashes(substr($question['question_text'], 0, 30)); ?>')">
                                            <i class="bi bi-pencil"></i> Edit
                                        </button>
                                        <button class="action-btn" onclick="alert('Delete')">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px; background: rgba(139, 92, 246, 0.05); border-radius: 12px; color: rgba(245, 244, 255, 0.6);">
                        <i class="bi bi-inbox" style="font-size: 2rem; margin-bottom: 10px;"></i>
                        <p>No questions in your question bank yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
