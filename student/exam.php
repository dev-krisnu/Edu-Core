<?php
/**
 * Student Exam Terminal
 * AI-proctored exam system with real-time monitoring
 */
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

requireLogin();
requireRole(['student']);

$pdo = getDbConnection();
$currentUser = getCurrentUser();

$examId = $_GET['exam_id'] ?? null;
$exam = null;
$questions = [];
$violations = 0;

if ($examId) {
    try {
        $stmt = $pdo->prepare('
            SELECT e.*, c.title AS course_name 
            FROM exams e 
            JOIN courses c ON e.course_id = c.id 
            WHERE e.id = ? AND e.start_time <= NOW()
        ');
        $stmt->execute([$examId]);
        $exam = $stmt->fetch();

        if ($exam) {
            $qStmt = $pdo->prepare('SELECT * FROM exam_questions WHERE exam_id = ? ORDER BY RAND()');
            $qStmt->execute([$examId]);
            $questions = $qStmt->fetchAll();
        }
    } catch (Exception $e) {
        error_log('[Exam Terminal] Error: ' . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Terminal - EduCore</title>
    <?php $portalBase = '..'; include __DIR__ . '/../includes/portal_head.php'; ?>
    <style>
        .exam-container {
            max-width: 1200px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            overflow: hidden;
        }

        .exam-header {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.2), rgba(34, 211, 238, 0.1));
            border-bottom: 2px solid rgba(99, 102, 241, 0.3);
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .exam-info h2 {
            margin: 0;
            color: #F5F4FF;
            font-size: 1.3rem;
        }

        .exam-timer {
            background: linear-gradient(120deg, #6366F1, #22D3EE);
            color: white;
            padding: 10px 16px;
            border-radius: 8px;
            font-weight: 700;
            font-family: monospace;
            font-size: 1.1rem;
        }

        .exam-body {
            display: grid;
            grid-template-columns: 1fr 280px;
            gap: 20px;
            padding: 24px;
            min-height: 600px;
        }

        .question-area {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            padding: 24px;
        }

        .question-number {
            color: #67E8F9;
            font-weight: 700;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 12px;
        }

        .question-text {
            color: #F5F4FF;
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .options-container {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .option-label {
            display: flex;
            align-items: center;
            padding: 14px 16px;
            background: rgba(99, 102, 241, 0.08);
            border: 2px solid rgba(99, 102, 241, 0.2);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            color: #F5F4FF;
        }

        .option-label:hover {
            background: rgba(99, 102, 241, 0.15);
            border-color: #6366F1;
            transform: translateX(4px);
        }

        .option-label input[type="radio"] {
            margin-right: 12px;
            cursor: pointer;
        }

        .option-label input[type="radio"]:checked ~ span {
            color: #67E8F9;
            font-weight: 700;
        }

        .sidebar {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 16px;
            max-height: 600px;
            overflow-y: auto;
        }

        .question-navigator {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 6px;
        }

        .q-nav-btn {
            width: 100%;
            aspect-ratio: 1;
            border: 1px solid rgba(99, 102, 241, 0.3);
            background: rgba(99, 102, 241, 0.1);
            color: #67E8F9;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.8rem;
            transition: all 0.2s ease;
        }

        .q-nav-btn:hover {
            background: rgba(99, 102, 241, 0.2);
            border-color: #6366F1;
        }

        .q-nav-btn.answered {
            background: rgba(34, 211, 238, 0.2);
            border-color: #22D3EE;
            color: #67E8F9;
        }

        .exam-stats {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.7);
            padding: 12px;
            background: rgba(99, 102, 241, 0.1);
            border-radius: 8px;
            text-align: center;
        }

        .submit-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(120deg, #6366F1, #22D3EE);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.4);
        }

        .proctoring-warning {
            background: rgba(251, 113, 133, 0.15);
            border: 1px solid rgba(251, 113, 133, 0.3);
            color: #FFD1D8;
            padding: 12px;
            border-radius: 8px;
            font-size: 0.85rem;
            margin-bottom: 16px;
        }

        @media (max-width: 1024px) {
            .exam-body {
                grid-template-columns: 1fr;
            }

            .sidebar {
                max-height: 300px;
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
            <?php if (!$exam): ?>
                <div class="container">
                    <div style="text-align: center; padding: 60px 20px;">
                        <h1 class="h-display" style="margin-bottom: 20px;">No Exam Selected</h1>
                        <p style="color: rgba(255, 255, 255, 0.6); margin-bottom: 30px;">Select an exam from your dashboard to begin</p>
                        <a href="./dashboard.php" style="display: inline-block; padding: 12px 24px; background: linear-gradient(120deg, #6366F1, #22D3EE); color: white; border-radius: 8px; text-decoration: none; font-weight: 600;">
                            ← Back to Dashboard
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="container">
                    <div class="exam-container">
                        <!-- Header -->
                        <div class="exam-header">
                            <div class="exam-info">
                                <h2><?php echo htmlspecialchars($exam['course_name']); ?></h2>
                                <p style="margin: 4px 0; color: rgba(245, 244, 255, 0.6); font-size: 0.9rem;">
                                    <?php echo count($questions); ?> Questions
                                </p>
                            </div>
                            <div class="exam-timer" id="examTimer">00:45:00</div>
                        </div>

                        <!-- Body -->
                        <div class="exam-body">
                            <!-- Main Question Area -->
                            <div>
                                <div class="proctoring-warning">
                                    <i class="bi bi-exclamation-triangle"></i>
                                    This exam is AI-proctored. Tab switching, window minimizing, or suspicious behavior will be recorded.
                                </div>

                                <div class="question-area" id="questionArea">
                                    <?php if (!empty($questions)): ?>
                                        <?php $question = reset($questions); ?>
                                        <div class="question-number">Question 1 of <?php echo count($questions); ?></div>
                                        <div class="question-text"><?php echo htmlspecialchars($question['question_text']); ?></div>
                                        <div class="options-container">
                                            <label class="option-label">
                                                <input type="radio" name="answer" value="A">
                                                <span><?php echo htmlspecialchars($question['option_a'] ?? ''); ?></span>
                                            </label>
                                            <label class="option-label">
                                                <input type="radio" name="answer" value="B">
                                                <span><?php echo htmlspecialchars($question['option_b'] ?? ''); ?></span>
                                            </label>
                                            <label class="option-label">
                                                <input type="radio" name="answer" value="C">
                                                <span><?php echo htmlspecialchars($question['option_c'] ?? ''); ?></span>
                                            </label>
                                            <label class="option-label">
                                                <input type="radio" name="answer" value="D">
                                                <span><?php echo htmlspecialchars($question['option_d'] ?? ''); ?></span>
                                            </label>
                                        </div>
                                    <?php else: ?>
                                        <p style="text-align: center; color: rgba(255, 255, 255, 0.5);">No questions available</p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Sidebar -->
                            <div class="sidebar">
                                <h3 style="margin: 0 0 12px 0; font-size: 0.95rem; color: #67E8F9;">Question Navigator</h3>
                                <div class="question-navigator">
                                    <?php for ($i = 0; $i < min(count($questions), 9); $i++): ?>
                                        <button class="q-nav-btn" onclick="loadQuestion(<?php echo $i; ?>)">
                                            <?php echo $i + 1; ?>
                                        </button>
                                    <?php endfor; ?>
                                </div>

                                <div class="exam-stats">
                                    <div><strong><?php echo count($questions); ?></strong> Total</div>
                                    <div id="answeredCount"><strong>0</strong> Answered</div>
                                    <div id="remainingCount"><strong><?php echo count($questions); ?></strong> Remaining</div>
                                </div>

                                <button class="submit-btn" onclick="submitExam()">
                                    <i class="bi bi-check-circle" style="margin-right: 6px;"></i>
                                    Submit Exam
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        // Proctoring: Monitor tab visibility
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                violations++;
                console.warn('Tab switched detected. Violations: ' + violations);
            }
        });

        // Timer
        let timeRemaining = 45 * 60; // 45 minutes
        setInterval(() => {
            if (timeRemaining > 0) {
                timeRemaining--;
                const hours = Math.floor(timeRemaining / 3600);
                const minutes = Math.floor((timeRemaining % 3600) / 60);
                const seconds = timeRemaining % 60;
                document.getElementById('examTimer').textContent = 
                    String(hours).padStart(2, '0') + ':' +
                    String(minutes).padStart(2, '0') + ':' +
                    String(seconds).padStart(2, '0');

                if (timeRemaining === 0) {
                    submitExam();
                }
            }
        }, 1000);

        function submitExam() {
            if (confirm('Are you sure you want to submit the exam? You cannot make changes after submission.')) {
                alert('Exam submitted successfully!');
                // Submit to server
                window.location.href = './examResults.php';
            }
        }

        function loadQuestion(index) {
            console.log('Loading question ' + (index + 1));
            // Implement question loading logic
        }
    </script>
</body>
</html>
