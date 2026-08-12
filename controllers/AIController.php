<?php
require_once __DIR__ . '/../config/ai_config.php';

class AIController
{
    public function generateQuestions(string $topic, string $syllabus, int $count, string $difficulty, string $bloomLevel): array
    {
        $prompt = "Generate {$count} exam questions on '{$topic}'.\n"
            . "Syllabus: {$syllabus}\n"
            . "Difficulty: {$difficulty}\n"
            . "Bloom's Taxonomy Level: {$bloomLevel}\n"
            . "Format each question as JSON with: question_text, question_type (mcq/short/code), options (array for mcq), correct_answer, marks, bloom_level.\n"
            . "Return ONLY a valid JSON array.";

        $response = $this->callAI($prompt);
        $questions = json_decode($response, true);
        return is_array($questions) ? $questions : [['question_text' => $response, 'question_type' => 'short', 'marks' => 5]];
    }

    public function helpdeskResponse(string $message, ?string $context = null): string
    {
        $prompt = "You are EduCore AI Helpdesk, a friendly educational assistant for a school/college ERP system. "
            . "Help with: courses, exams, fees, library, placements, and general academic queries.\n"
            . ($context ? "User context: {$context}\n" : "")
            . "Student/Faculty question: {$message}\n"
            . "Give a concise, helpful answer in 2-4 sentences.";

        return $this->callAI($prompt);
    }

    public function analyzeResume(string $resumeText, string $jobDescription): array
    {
        $prompt = "Analyze this resume against the job description and return JSON with: fitment_score (0-100), matching_skills (array), gaps (array), recommendation (string).\n\n"
            . "Job Description:\n{$jobDescription}\n\nResume:\n{$resumeText}\n\nReturn ONLY valid JSON.";

        $response = $this->callAI($prompt);
        $result = json_decode($response, true);
        return is_array($result) ? $result : ['fitment_score' => 0, 'recommendation' => $response];
    }

    public function remedialAnalysis(array $scores): string
    {
        $scoresJson = json_encode($scores);
        $prompt = "Based on these student performance scores across subjects/concepts: {$scoresJson}, "
            . "identify weak areas and suggest targeted study materials. Be specific and actionable.";

        return $this->callAI($prompt);
    }

    private function callAI(string $prompt): string
    {
        if (AI_PROVIDER === 'ollama') {
            return $this->callOllama($prompt);
        }
        return $this->callGemini($prompt);
    }

    private function callGemini(string $prompt): string
    {
        if (GEMINI_API_KEY === 'YOUR_GEMINI_API_KEY_HERE') {
            return $this->fallbackResponse($prompt);
        }

        $payload = [
            'contents' => [['parts' => [['text' => $prompt]]]],
            'generationConfig' => [
                'temperature' => AI_TEMPERATURE,
                'maxOutputTokens' => AI_MAX_TOKENS,
            ]
        ];

        $ch = curl_init(GEMINI_API_URL . '?key=' . GEMINI_API_KEY);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 30,
        ]);
        $result = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($result, true);
        return $data['candidates'][0]['content']['parts'][0]['text'] ?? $this->fallbackResponse($prompt);
    }

    private function callOllama(string $prompt): string
    {
        $payload = ['model' => OLLAMA_MODEL, 'prompt' => $prompt, 'stream' => false];
        $ch = curl_init(OLLAMA_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 60,
        ]);
        $result = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($result, true);
        return $data['response'] ?? $this->fallbackResponse($prompt);
    }

    private function fallbackResponse(string $prompt): string
    {
        if (stripos($prompt, 'helpdesk') !== false || stripos($prompt, 'question:') !== false) {
            return "I'm EduCore AI Assistant (demo mode). Configure your Gemini API key in config/ai_config.php for full AI capabilities. "
                . "For now: check your dashboard for courses, exams, and fees. Visit the library QR desk for book circulation.";
        }
        return '[{"question_text":"Explain the core concepts of the given topic with examples.","question_type":"short","marks":10,"bloom_level":"Understand"}]';
    }
}
