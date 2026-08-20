<?php
/**
 * AI Question Generation - Auto-generate exam questions
 * Use AI to generate questions with various difficulty levels
 */
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/GeminiAI.php';
require_once __DIR__ . '/../includes/auth_check.php';

requireLogin();
requireRole(['faculty']);

$currentUser = getCurrentUser();
$pdo = getDbConnection();

$generatedQuestions = [];
$error = '';

// Handle AJAX request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['topic'])) {
    header('Content-Type: application/json');

    $topic = trim($_POST['topic']);
    $count = intval($_POST['count'] ?? 5);
    $difficulty = trim($_POST['difficulty'] ?? 'medium');

    if (empty($topic) || $count < 1 || $count > 20) {
        echo json_encode(['success' => false, 'error' => 'Invalid input']);
        exit;
    }

    try {
        $ai = AIFactory::create();
        $questions = $ai->generateQuestions($topic, $count, $difficulty);

        // Normalize AI output to the shape the frontend actually expects:
        // { question, options: [string, ...], correct_answer: <index> }.
        // Both GeminiAI's raw JSON and the DemoAI fallback previously
        // returned question_text + an associative options object keyed
        // A/B/C/D with correct_answer as a letter - the JS did
        // `(q.options || []).map(...)`, which throws when options is an
        // object rather than an array, silently breaking rendering
        // after a response had already come back successfully.
        $normalized = [];
        foreach ($questions as $q) {
            $questionText = $q['question'] ?? $q['question_text'] ?? 'Question';
            $rawOptions = $q['options'] ?? [];
            $correctRaw = $q['correct_answer'] ?? null;

            if (is_array($rawOptions) && array_keys($rawOptions) !== range(0, count($rawOptions) - 1)) {
                // Associative (A/B/C/D) - flatten to an indexed array and
                // resolve the letter-keyed correct answer to its index.
                $letters = array_keys($rawOptions);
                $optionsList = array_values($rawOptions);
                $correctIndex = is_string($correctRaw) ? array_search(strtoupper($correctRaw), array_map('strtoupper', $letters), true) : $correctRaw;
            } else {
                $optionsList = array_values((array) $rawOptions);
                $correctIndex = is_numeric($correctRaw) ? (int) $correctRaw : 0;
            }

            $normalized[] = [
                'question' => $questionText,
                'options' => $optionsList,
                'correct_answer' => $correctIndex !== false ? $correctIndex : 0,
                'explanation' => $q['explanation'] ?? '',
            ];
        }

        echo json_encode([
            'success' => true,
            'questions' => $normalized,
            'topic' => $topic,
            'difficulty' => $difficulty,
            'count' => count($normalized)
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to generate questions: ' . $e->getMessage()
        ]);
    }
    exit;
}

// Save a generated question into the question bank so it's actually
// reusable (the "Save Question" button previously just alert()'d
// "Question saved" with no INSERT anywhere behind it).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['save_question'])) {
    header('Content-Type: application/json');

    $questionText = trim($_POST['question_text'] ?? '');
    $optionsJson = $_POST['options'] ?? '[]';
    $correctAnswer = trim($_POST['correct_answer'] ?? '');
    $difficulty = trim($_POST['difficulty'] ?? 'medium');
    $courseId = !empty($_POST['course_id']) ? (int) $_POST['course_id'] : null;

    if (empty($questionText)) {
        echo json_encode(['success' => false, 'error' => 'Question text is required.']);
        exit;
    }

    // Validate the options are actually JSON before storing (the column
    // is JSON-typed) rather than trusting the client blindly.
    json_decode($optionsJson);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $optionsJson = '[]';
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO exam_questions (course_id, created_by, question_text, question_type, difficulty, options, correct_answer, marks)
            VALUES (?, ?, ?, 'mcq', ?, ?, ?, 5)
        ");
        $stmt->execute([$courseId, $currentUser['id'], $questionText, $difficulty, $optionsJson, $correctAnswer]);
        echo json_encode(['success' => true, 'message' => 'Question saved to your question bank.']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Failed to save: ' . $e->getMessage()]);
    }
    exit;
}

// Delete an already-saved question from this page's own list too
// (was previously an inert button with no handler at all).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['delete_question_id'])) {
    $qid = (int) $_POST['delete_question_id'];
    $pdo->prepare("DELETE FROM exam_questions WHERE id = ? AND created_by = ?")->execute([$qid, $currentUser['id']]);
    header('Location: aiQuestionGeneration.php');
    exit;
}

// Fetch existing question banks
$stmt = $pdo->prepare("
    SELECT eq.*, c.title AS course_name
    FROM exam_questions eq
    LEFT JOIN courses c ON eq.course_id = c.id
    WHERE eq.created_by = ?
    ORDER BY eq.created_at DESC
    LIMIT 20
");
$stmt->execute([$currentUser['id']]);
$existingQuestions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Question Generation - EduCore</title>
    <?php $portalBase = '..'; include __DIR__ . '/../includes/portal_head.php'; ?>
    <style>
        .generator-container {
            max-width: 1100px;
            margin: 0 auto;
        }

        .generator-section {
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.15), rgba(168, 85, 247, 0.08));
            border: 1px solid rgba(139, 92, 246, 0.3);
            border-radius: 16px;
            padding: 28px;
            margin-bottom: 30px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 150px 150px auto;
            gap: 12px;
            margin-bottom: 16px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-label {
            margin-bottom: 6px;
            color: rgba(245, 244, 255, 0.8);
            font-weight: 600;
            font-size: 0.9rem;
        }

        .form-input,
        .form-select {
            padding: 10px;
            border-radius: 8px;
            border: 1px solid rgba(139, 92, 246, 0.3);
            background: rgba(255, 255, 255, 0.05);
            color: #F5F4FF;
            font-family: inherit;
            font-size: 0.9rem;
        }

        .form-input::placeholder {
            color: rgba(245, 244, 255, 0.4);
        }

        .form-input:focus,
        .form-select:focus {
            outline: none;
            border-color: #8B5CF6;
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.2);
        }

        .generate-btn {
            padding: 10px 20px;
            background: linear-gradient(120deg, #8B5CF6, #D8B4FE);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            align-self: flex-end;
        }

        .generate-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(139, 92, 246, 0.4);
        }

        .generate-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .questions-list {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .question-item {
            background: rgba(139, 92, 246, 0.1);
            border: 1px solid rgba(139, 92, 246, 0.2);
            border-radius: 12px;
            padding: 16px;
            transition: all 0.3s ease;
        }

        .question-item:hover {
            background: rgba(139, 92, 246, 0.15);
            border-color: rgba(139, 92, 246, 0.4);
            transform: translateX(4px);
        }

        .question-number {
            display: inline-block;
            width: 32px;
            height: 32px;
            background: linear-gradient(120deg, #8B5CF6, #D8B4FE);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .question-text {
            font-weight: 600;
            color: #F5F4FF;
            margin-bottom: 10px;
            line-height: 1.4;
        }

        .options-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 12px;
        }

        .option {
            padding: 8px 12px;
            background: rgba(99, 102, 241, 0.05);
            border-radius: 6px;
            font-size: 0.9rem;
            color: rgba(245, 244, 255, 0.8);
        }

        .correct-answer {
            background: rgba(16, 185, 129, 0.1);
            color: #6EE7B7;
            font-weight: 600;
            border-left: 3px solid #10B981;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            margin-top: 12px;
        }

        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-save {
            background: linear-gradient(120deg, #10B981, #6EE7B7);
            color: white;
        }

        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(16, 185, 129, 0.3);
        }

        .btn-delete {
            background: rgba(239, 68, 68, 0.1);
            color: #FCA5A5;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .btn-delete:hover {
            background: rgba(239, 68, 68, 0.2);
        }

        .section-title {
            font-size: 1.2rem;
            font-weight: 700;
            margin: 28px 0 16px 0;
            color: #F5F4FF;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .loading {
            display: none;
            text-align: center;
            padding: 20px;
            color: rgba(245, 244, 255, 0.6);
        }

        .spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(139, 92, 246, 0.3);
            border-top-color: #8B5CF6;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        @media (max-width: 1024px) {
            .form-grid {
                grid-template-columns: 1fr 1fr;
            }

            .generate-btn {
                grid-column: 1 / -1;
            }
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body class="portal-page" data-role="faculty">
    <div class="aurora-bg">
        <div class="aurora-blob b1"></div>
        <div class="aurora-blob b2"></div>
        <div class="aurora-blob b3"></div>
    </div>
    <div class="grain"></div>

    <div class="container-shell">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <main style="flex: 1; z-index: 2; padding: 40px 20px;">
            <div class="generator-container">
                <!-- Header -->
                <div style="margin-bottom: 30px;">
                    <h1 class="h-display" style="margin: 0 0 8px 0;">AI Question Generation</h1>
                    <p style="color: rgba(255, 255, 255, 0.6); margin: 0;">Generate exam questions automatically using AI</p>
                </div>

                <!-- Generator Section -->
                <div class="generator-section">
                    <h2 style="margin: 0 0 20px 0; color: #F5F4FF;">
                        <i class="bi bi-stars"></i> Generate New Questions
                    </h2>

                    <form id="generatorForm">
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Topic/Subject</label>
                                <input 
                                    type="text" 
                                    class="form-input" 
                                    name="topic" 
                                    placeholder="e.g., Photosynthesis, Calculus"
                                    required
                                >
                            </div>

                            <div class="form-group">
                                <label class="form-label">Course/Chapter</label>
                                <input 
                                    type="text" 
                                    class="form-input" 
                                    name="course" 
                                    placeholder="e.g., Biology 101"
                                >
                            </div>

                            <div class="form-group">
                                <label class="form-label">Difficulty</label>
                                <select class="form-select" name="difficulty">
                                    <option value="easy">Easy</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="hard">Hard</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Number</label>
                                <input 
                                    type="number" 
                                    class="form-input" 
                                    name="count" 
                                    min="1" 
                                    max="20" 
                                    value="5"
                                >
                            </div>

                            <button type="button" class="generate-btn" onclick="generateQuestions()">
                                <i class="bi bi-lightning"></i> Generate
                            </button>
                        </div>
                    </form>

                    <div class="loading" id="loading">
                        <div class="spinner"></div>
                        <p>Generating questions... please wait</p>
                    </div>
                </div>

                <!-- Generated Questions -->
                <h2 class="section-title" id="resultsTitle" style="display: none;">
                    <i class="bi bi-check-circle"></i> Generated Questions
                </h2>

                <div id="questionsContainer"></div>

                <!-- Existing Questions -->
                <?php if (count($existingQuestions) > 0): ?>
                    <h2 class="section-title">
                        <i class="bi bi-card-list"></i> My Question Banks (<?php echo count($existingQuestions); ?>)
                    </h2>

                    <div class="questions-list">
                        <?php foreach ($existingQuestions as $q): 
                            $difficulty = $q['difficulty'] ?? 'medium';
                        ?>
                            <div class="question-item">
                                <span class="question-number"><?php echo substr($q['id'], 0, 1); ?></span>
                                <div class="question-text">
                                    <?php echo htmlspecialchars(substr($q['question_text'], 0, 100)); ?>...
                                </div>
                                <div style="font-size: 0.85rem; color: rgba(245, 244, 255, 0.6); margin-bottom: 10px;">
                                    <strong>Course:</strong> <?php echo htmlspecialchars($q['course_name'] ?? 'General'); ?> | 
                                    <strong>Level:</strong> <?php echo ucfirst($difficulty); ?>
                                </div>
                                <div class="action-buttons">
                                    <a href="questionBank.php" class="action-btn btn-save" style="text-decoration:none; display:inline-block;">
                                        <i class="bi bi-bookmark"></i> Manage in Question Bank
                                    </a>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this question?');">
                                        <input type="hidden" name="delete_question_id" value="<?php echo (int) $q['id']; ?>">
                                        <button type="submit" class="action-btn btn-delete">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        async function generateQuestions() {
            const topic = document.querySelector('input[name="topic"]').value.trim();
            const difficulty = document.querySelector('select[name="difficulty"]').value;
            const count = parseInt(document.querySelector('input[name="count"]').value);

            if (!topic) {
                alert('Please enter a topic');
                return;
            }

            document.getElementById('loading').style.display = 'block';

            try {
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 35000); // don't hang forever

                const response = await fetch('./aiQuestionGeneration.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'topic=' + encodeURIComponent(topic) + 
                          '&difficulty=' + encodeURIComponent(difficulty) +
                          '&count=' + count,
                    signal: controller.signal
                });
                clearTimeout(timeoutId);

                const data = await response.json();
                document.getElementById('loading').style.display = 'none';

                if (data.success) {
                    displayQuestions(data.questions, data.difficulty);
                } else {
                    alert('Error: ' + data.error);
                }
            } catch (err) {
                document.getElementById('loading').style.display = 'none';
                console.error('Error:', err);
                if (err.name === 'AbortError') {
                    alert('The AI service took too long to respond. Please try again.');
                } else {
                    alert('Failed to generate questions');
                }
            }
        }

        function displayQuestions(questions, difficulty) {
            const container = document.getElementById('questionsContainer');
            const resultsTitle = document.getElementById('resultsTitle');
            
            resultsTitle.style.display = 'flex';
            container.innerHTML = '';

            questions.forEach((q, index) => {
                const qDiv = document.createElement('div');
                qDiv.className = 'question-item';
                
                const optionsHtml = (q.options || []).map((opt, i) => {
                    const isCorrect = i === q.correct_answer;
                    return `<div class="option ${isCorrect ? 'correct-answer' : ''}">
                        ${String.fromCharCode(65 + i)}. ${opt} ${isCorrect ? '✓' : ''}
                    </div>`;
                }).join('');

                qDiv.innerHTML = `
                    <span class="question-number">${index + 1}</span>
                    <div class="question-text">${q.question || 'Question ' + (index + 1)}</div>
                    <div class="options-list">${optionsHtml}</div>
                    <div class="action-buttons">
                        <button class="action-btn btn-save" onclick="saveGeneratedQuestion(this, ${JSON.stringify(JSON.stringify(q))}, ${JSON.stringify(difficulty)})">
                            <i class="bi bi-check"></i> Save
                        </button>
                        <button class="action-btn btn-delete" onclick="this.parentElement.parentElement.remove()">
                            <i class="bi bi-x"></i> Remove
                        </button>
                    </div>
                `;
                
                container.appendChild(qDiv);
            });
        }

        async function saveGeneratedQuestion(btn, questionJson, difficulty) {
            const q = JSON.parse(questionJson);
            btn.disabled = true;
            btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Saving...';

            try {
                const body = new URLSearchParams();
                body.set('save_question', '1');
                body.set('question_text', q.question || '');
                body.set('options', JSON.stringify(q.options || []));
                body.set('correct_answer', (q.options && q.options[q.correct_answer] !== undefined) ? q.options[q.correct_answer] : '');
                body.set('difficulty', difficulty || 'medium');

                const res = await fetch('./aiQuestionGeneration.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: body.toString()
                });
                const data = await res.json();

                if (data.success) {
                    btn.innerHTML = '<i class="bi bi-check-circle-fill"></i> Saved';
                    btn.style.opacity = '0.7';
                } else {
                    alert('Error: ' + data.error);
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-check"></i> Save';
                }
            } catch (err) {
                alert('Failed to save question.');
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-check"></i> Save';
            }
        }
    </script>
</body>
</html>
