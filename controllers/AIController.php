<?php
require_once __DIR__ . '/../config/ai_config.php';
require_once __DIR__ . '/../config/AICache.php';

class AIController
{
    private const MAX_PROMPT_CHARS = 1800;

    public function generateQuestions(string $topic, string $syllabus, int $count, string $difficulty, string $bloomLevel): array
    {
        $count = min(max($count, 1), 10);
        $prompt = "Generate {$count} exam Qs on '{$topic}'. Diff:{$difficulty}, Bloom:{$bloomLevel}. Syllabus:{$syllabus}. "
            . "JSON array only: [{question_text,question_type,options,correct_answer,marks,bloom_level}]";

        $response = $this->callAI($prompt);
        $questions = json_decode($this->extractJson($response), true);
        return is_array($questions) ? $questions : [['question_text' => $response, 'question_type' => 'short', 'marks' => 5]];
    }

    public function helpdeskResponse(string $message, ?string $context = null): string
    {
        $prompt = "EduCore helpdesk. {$context}. Q: {$message}. Answer in 2-3 short sentences.";
        return $this->callAI($prompt);
    }

    public function tutorResponse(string $topic, string $question): string
    {
        $prompt = "Tutor for '{$topic}'. Student asks: {$question}. Explain simply with 1 example. Max 150 words.";
        return $this->callAI($prompt);
    }

    public function analyzeResume(string $resumeText, string $jobDescription): array
    {
        $resumeText = substr($resumeText, 0, 800);
        $jobDescription = substr($jobDescription, 0, 600);
        $prompt = "Match resume to job. JSON only: {fitment_score,matching_skills[],gaps[],recommendation}.\nJob:{$jobDescription}\nResume:{$resumeText}";

        $response = $this->callAI($prompt);
        $result = json_decode($this->extractJson($response), true);
        return is_array($result) ? $result : ['fitment_score' => 65, 'recommendation' => $response];
    }

    public function remedialAnalysis(array $scores): string
    {
        $scoresJson = substr(json_encode($scores), 0, 500);
        $prompt = "Student scores: {$scoresJson}. List weak areas + 3 study tips. Max 120 words.";
        return $this->callAI($prompt);
    }

    public function analyzePlagiarism(string $text): array
    {
        $text = substr($text, 0, 1000);
        $prompt = "Plagiarism check. JSON: {risk_score,issues[],suggestions[]}. Text:{$text}";
        $response = $this->callAI($prompt);
        $result = json_decode($this->extractJson($response), true);
        return is_array($result) ? $result : ['risk_score' => 0, 'issues' => [], 'suggestions' => [$response]];
    }

    private function callAI(string $prompt): string
    {
        $prompt = substr(trim($prompt), 0, self::MAX_PROMPT_CHARS);

        $cached = AICache::get($prompt);
        if ($cached !== null) {
            return $cached;
        }

        $response = AI_PROVIDER === 'ollama'
            ? $this->callOllama($prompt)
            : $this->callGemini($prompt);

        if ($response && !str_starts_with($response, 'Error:')) {
            AICache::set($prompt, $response);
        }
        return $response;
    }

    private function callGemini(string $prompt): string
    {
        if (GEMINI_API_KEY === '' || GEMINI_API_KEY === 'your_gemini_api_key_here') {
            return $this->fallbackResponse($prompt);
        }

        $payload = [
            'contents' => [['parts' => [['text' => $prompt]]]],
            'generationConfig' => [
                'temperature' => AI_TEMPERATURE,
                'maxOutputTokens' => AI_MAX_TOKENS,
            ],
        ];

        $ch = curl_init(GEMINI_API_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-goog-api-key: ' . GEMINI_API_KEY,
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 25,
        ]);
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        $data = json_decode((string) $result, true);
        if ($curlError !== '' || $httpCode !== 200) {
            $message = $data['error']['message'] ?? ($curlError ?: 'The AI service did not return a response.');
            error_log("[Gemini API Error] HTTP {$httpCode}: {$message}");
            return "Error: {$message}";
        }
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
            CURLOPT_TIMEOUT => 45,
        ]);
        $result = curl_exec($ch);
        curl_close($ch);

        $data = json_decode((string) $result, true);
        return $data['response'] ?? $this->fallbackResponse($prompt);
    }

    private function extractJson(string $response): string
    {
        if (preg_match('/\{[\s\S]*\}|\[[\s\S]*\]/', $response, $m)) {
            return $m[0];
        }
        return $response;
    }

    private function fallbackResponse(string $prompt): string
    {
        if (stripos($prompt, 'helpdesk') !== false || stripos($prompt, 'Q:') !== false) {
            return 'I\'m EduCore AI (demo). Add GEMINI_API_KEY in .env for live answers. Check your dashboard for courses, exams, fees, and library.';
        }
        if (stripos($prompt, 'Tutor') !== false) {
            return 'Here\'s a concise explanation: break the topic into key concepts, review definitions, then practice with one worked example. Add your Gemini key in .env for personalized tutoring.';
        }
        if (stripos($prompt, 'Plagiarism') !== false) {
            return '{"risk_score":15,"issues":["Demo mode"],"suggestions":["Configure Gemini API for full analysis"]}';
        }
        if (stripos($prompt, 'resume') !== false || stripos($prompt, 'Match') !== false) {
            return '{"fitment_score":72,"matching_skills":["Programming","Teamwork"],"gaps":["Cloud certs"],"recommendation":"Good match — highlight projects in interview."}';
        }
        return '[{"question_text":"Explain core concepts with examples.","question_type":"short","marks":10,"bloom_level":"Understand"}]';
    }
}
