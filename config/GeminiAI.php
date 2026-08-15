<?php
/**
 * Enhanced Gemini AI Integration
 * Handles all AI-powered features for EduCore
 */

declare(strict_types=1);

require_once __DIR__ . '/env.php';
require_once __DIR__ . '/ai_config.php';

class GeminiAI
{
    private string $apiKey;
    private string $model;
    private string $baseUrl;
    private int $maxTokens;
    private float $temperature;

    public function __construct()
    {
        $this->apiKey = GEMINI_API_KEY;
        $this->model = GEMINI_MODEL;
        $this->baseUrl = GEMINI_API_URL;
        $this->maxTokens = AI_MAX_TOKENS;
        $this->temperature = AI_TEMPERATURE;

        if (empty($this->apiKey) || $this->apiKey === '') {
            throw new Exception('Gemini API key not configured. Set GEMINI_API_KEY in .env');
        }
    }

    /**
     * Generate exam questions using AI
     */
    public function generateQuestions(string $topic, int $count = 5, string $difficulty = 'medium'): array
    {
        $prompt = "Generate $count multiple choice exam questions about '$topic' with difficulty level '$difficulty'. 
        For each question, provide:
        1. Question text
        2. Four options (A, B, C, D)
        3. Correct answer
        4. Explanation
        
        Format as JSON array.";

        $response = $this->callAPI($prompt);
        return $this->parseJSON($response);
    }

    /**
     * Generate AI tutor response
     */
    public function tutorResponse(string $topic, string $question, string $userRole = 'student'): string
    {
        $contextPrompt = $userRole === 'student' 
            ? "You are an educational tutor helping a student understand the topic."
            : "You are an educational content expert providing detailed explanations.";

        $prompt = "$contextPrompt

Topic: $topic
Student Question: $question

Provide a clear, helpful, and educational response that helps the student understand the concept. 
Use examples and break down complex ideas into simpler parts.";

        return $this->callAPI($prompt);
    }

    /**
     * Analyze student resume for placement matching
     */
    public function analyzeResume(string $resumeText, string $jobDescription): array
    {
        $prompt = "Analyze the following resume against the job description and provide:
        1. Overall match percentage (0-100)
        2. Matching skills
        3. Missing skills
        4. Recommendations for improvement
        5. Interview preparation tips

Resume:
$resumeText

Job Description:
$jobDescription

Format response as JSON.";

        $response = $this->callAPI($prompt);
        return $this->parseJSON($response);
    }

    /**
     * Provide remedial learning suggestions
     */
    public function remedialAnalysis(string $studentName, string $subject, array $weakAreas, float $performanceScore): array
    {
        $weakAreasList = implode(', ', $weakAreas);

        $prompt = "Create a personalized remedial learning plan for:
        Student: $studentName
        Subject: $subject
        Weak Areas: $weakAreasList
        Current Performance: $performanceScore%

        Provide:
        1. Root cause analysis
        2. Specific learning objectives
        3. Recommended resources and topics
        4. Practice problems to focus on
        5. Timeline for improvement (1-4 weeks)

Format as JSON.";

        $response = $this->callAPI($prompt);
        return $this->parseJSON($response);
    }

    /**
     * Check content for plagiarism detection (using AI analysis)
     */
    public function analyzePlagiarism(string $content): array
    {
        $prompt = "Analyze the following text for potential plagiarism indicators:
        1. Check for uncommon phrasing patterns
        2. Identify sections that might be copied
        3. Suggest original rewrites
        4. Provide plagiarism risk score (0-100)

Text:
$content

Format response as JSON with fields: risk_score, potential_issues, suggestions.";

        $response = $this->callAPI($prompt);
        return $this->parseJSON($response);
    }

    /**
     * Generate lesson plan
     */
    public function generateLessonPlan(string $topic, string $gradeLevel, int $durationMinutes = 45): array
    {
        $prompt = "Create a detailed lesson plan for:
        Topic: $topic
        Grade Level: $gradeLevel
        Duration: $durationMinutes minutes

        Include:
        1. Learning objectives
        2. Introduction activity
        3. Main content sections with time allocation
        4. Interactive activities
        5. Assessment methods
        6. Conclusion and summary
        7. Homework assignment

Format as JSON.";

        $response = $this->callAPI($prompt);
        return $this->parseJSON($response);
    }

    /**
     * Help desk chatbot response
     */
    public function helpdeskResponse(string $question, string $userRole = 'student'): string
    {
        $roleContext = match($userRole) {
            'faculty' => 'You are a helpful support assistant for faculty members.',
            'admin' => 'You are an administrative support specialist.',
            'parent' => 'You are a customer service representative for parents.',
            default => 'You are a helpful support assistant for students.'
        };

        $prompt = "$roleContext

User Question: $question

Provide a helpful, concise response. If you need to reference documentation or features, mention them clearly.
Keep response under 250 words.";

        return $this->callAPI($prompt);
    }

    /**
     * Call Gemini API
     */
    private function callAPI(string $prompt): string
    {
        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'maxOutputTokens' => $this->maxTokens,
                'temperature' => $this->temperature
            ]
        ];

        $url = $this->baseUrl . '?key=' . urlencode($this->apiKey);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($httpCode !== 200) {
            error_log("[Gemini API Error] HTTP $httpCode: $response");
            return "Error: Failed to get AI response (HTTP $httpCode)";
        }

        if ($curlError) {
            error_log("[Gemini API cURL Error] $curlError");
            return "Error: Connection failed";
        }

        $data = json_decode($response, true);

        if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            error_log('[Gemini API] Unexpected response format: ' . $response);
            return "Error: Invalid response format from AI";
        }

        return $data['candidates'][0]['content']['parts'][0]['text'];
    }

    /**
     * Parse JSON from AI response
     */
    private function parseJSON(string $response): array
    {
        // Try to extract JSON from response
        preg_match('/\{[\s\S]*\}|\[[\s\S]*\]/', $response, $matches);

        if (!empty($matches[0])) {
            $decoded = json_decode($matches[0], true);
            return $decoded ?: ['raw' => $response];
        }

        return ['raw' => $response];
    }

    /**
     * Stream response for real-time chat
     */
    public function streamResponse(string $prompt, callable $callback): void
    {
        // Note: Streaming requires different API implementation
        // For now, return full response
        $response = $this->callAPI($prompt);
        $callback($response);
    }
}

/**
 * Fallback to Ollama if Gemini not available
 */
class OllamaAI
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = 'http://localhost:11434/api/generate';
    }

    public function generateResponse(string $prompt): string
    {
        $payload = [
            'model' => 'llama3.2',
            'prompt' => $prompt,
            'stream' => false
        ];

        $ch = curl_init($this->baseUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            return "Error: Ollama service unavailable";
        }

        $data = json_decode($response, true);
        return $data['response'] ?? 'Error: No response generated';
    }
}

/**
 * AI Factory - Returns appropriate AI backend
 */
class AIFactory
{
    public static function create(): GeminiAI|OllamaAI
    {
        $provider = env('AI_PROVIDER', 'gemini');

        return match($provider) {
            'ollama' => new OllamaAI(),
            default => new GeminiAI()
        };
    }
}
