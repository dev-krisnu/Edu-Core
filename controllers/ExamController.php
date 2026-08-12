<?php
require_once __DIR__ . '/../config/database.php';

class ExamController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = getDB();
    }

    public function getExams(?string $status = null): array
    {
        $sql = 'SELECT e.*, c.title AS course_title, u.full_name AS creator_name
                FROM exams e
                LEFT JOIN courses c ON e.course_id = c.id
                LEFT JOIN users u ON e.created_by = u.id';
        if ($status) {
            $sql .= ' WHERE e.status = ?';
            $stmt = $this->db->prepare($sql . ' ORDER BY e.start_time DESC');
            $stmt->execute([$status]);
        } else {
            $stmt = $this->db->query($sql . ' ORDER BY e.start_time DESC');
        }
        return $stmt->fetchAll();
    }

    public function getExamQuestions(int $examId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM exam_questions WHERE exam_id = ? ORDER BY id');
        $stmt->execute([$examId]);
        return $stmt->fetchAll();
    }

    public function createExam(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO exams (title, course_id, duration_minutes, total_marks, start_time, end_time, proctored, status, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['title'], $data['course_id'], $data['duration_minutes'] ?? 60,
            $data['total_marks'] ?? 100, $data['start_time'], $data['end_time'],
            $data['proctored'] ?? 1, $data['status'] ?? 'draft', $data['created_by']
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function addQuestions(int $examId, array $questions): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO exam_questions (exam_id, question_text, question_type, options, correct_answer, marks, bloom_level)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        foreach ($questions as $q) {
            $stmt->execute([
                $examId,
                $q['question_text'],
                $q['question_type'] ?? 'mcq',
                isset($q['options']) ? json_encode($q['options']) : null,
                $q['correct_answer'] ?? '',
                $q['marks'] ?? 5,
                $q['bloom_level'] ?? 'Understand'
            ]);
        }
    }

    public function autoGrade(array $answers, array $questions): array
    {
        $total = 0;
        $earned = 0;
        $results = [];

        foreach ($questions as $q) {
            $total += $q['marks'];
            $studentAnswer = $answers[$q['id']] ?? '';
            $isCorrect = strtolower(trim($studentAnswer)) === strtolower(trim($q['correct_answer']));
            if ($isCorrect) {
                $earned += $q['marks'];
            }
            $results[] = [
                'question_id' => $q['id'],
                'correct' => $isCorrect,
                'marks_awarded' => $isCorrect ? $q['marks'] : 0
            ];
        }

        return [
            'total_marks' => $total,
            'earned_marks' => $earned,
            'percentage' => $total > 0 ? round(($earned / $total) * 100, 2) : 0,
            'results' => $results
        ];
    }
}
