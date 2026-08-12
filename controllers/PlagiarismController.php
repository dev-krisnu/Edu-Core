<?php

class PlagiarismController
{
    public function checkTextSimilarity(string $text1, string $text2): array
    {
        $words1 = $this->tokenize($text1);
        $words2 = $this->tokenize($text2);

        if (empty($words1) || empty($words2)) {
            return ['similarity' => 0, 'verdict' => 'No content to compare'];
        }

        $intersection = count(array_intersect($words1, $words2));
        $union = count(array_unique(array_merge($words1, $words2)));
        $similarity = round(($intersection / $union) * 100, 2);

        return [
            'similarity' => $similarity,
            'verdict' => $this->getVerdict($similarity),
            'matched_words' => $intersection,
            'total_unique_words' => $union
        ];
    }

    public function checkCodeSimilarity(string $code1, string $code2): array
    {
        $tokens1 = $this->extractCodeTokens($code1);
        $tokens2 = $this->extractCodeTokens($code2);

        $intersection = count(array_intersect($tokens1, $tokens2));
        $union = count(array_unique(array_merge($tokens1, $tokens2)));
        $similarity = $union > 0 ? round(($intersection / $union) * 100, 2) : 0;

        return [
            'similarity' => $similarity,
            'verdict' => $this->getVerdict($similarity),
            'method' => 'AST Token Matching',
            'tokens_matched' => $intersection
        ];
    }

    public function detectAIGenerated(string $text): array
    {
        $indicators = 0;
        $reasons = [];

        $sentences = preg_split('/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        if (count($sentences) > 3) {
            $lengths = array_map('strlen', $sentences);
            $avgLen = array_sum($lengths) / count($lengths);
            $variance = 0;
            foreach ($lengths as $l) {
                $variance += pow($l - $avgLen, 2);
            }
            $variance /= count($lengths);
            if ($variance < 100) {
                $indicators++;
                $reasons[] = 'Uniform sentence length pattern detected';
            }
        }

        $formalPhrases = ['Furthermore', 'Moreover', 'In conclusion', 'It is important to note', 'Additionally'];
        foreach ($formalPhrases as $phrase) {
            if (stripos($text, $phrase) !== false) {
                $indicators++;
                $reasons[] = "Formal AI phrase detected: \"{$phrase}\"";
                break;
            }
        }

        $aiScore = min($indicators * 35, 95);
        return [
            'ai_probability' => $aiScore,
            'verdict' => $aiScore > 60 ? 'Likely AI-generated' : ($aiScore > 30 ? 'Possibly AI-assisted' : 'Likely human-written'),
            'indicators' => $reasons
        ];
    }

    private function tokenize(string $text): array
    {
        $text = strtolower(preg_replace('/[^a-zA-Z0-9\s]/', '', $text));
        $words = array_filter(explode(' ', $text), fn($w) => strlen($w) > 3);
        return array_values($words);
    }

    private function extractCodeTokens(string $code): array
    {
        $code = preg_replace('/\/\/.*|\/\*.*?\*\//s', '', $code);
        preg_match_all('/\b(function|class|if|else|for|while|return|var|let|const|def|import|public|private|static|\w+)\b/', $code, $matches);
        return array_unique(array_map('strtolower', $matches[0] ?? []));
    }

    private function getVerdict(float $similarity): string
    {
        if ($similarity >= 80) return 'High plagiarism — Immediate review required';
        if ($similarity >= 50) return 'Moderate similarity — Further investigation needed';
        if ($similarity >= 25) return 'Low similarity — Likely original work';
        return 'Original — No significant overlap detected';
    }
}
