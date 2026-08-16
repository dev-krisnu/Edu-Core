<?php
/**
 * File-based AI response cache — reduces Gemini API calls & token usage.
 */
declare(strict_types=1);

class AICache
{
    private static function dir(): string
    {
        $dir = dirname(__DIR__) . '/cache/ai';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return $dir;
    }

    public static function get(string $prompt): ?string
    {
        $ttl = (int)(defined('AI_CACHE_TTL') ? AI_CACHE_TTL : 3600);
        $file = self::dir() . '/' . hash('sha256', $prompt) . '.json';
        if (!file_exists($file)) {
            return null;
        }
        $data = json_decode((string)file_get_contents($file), true);
        if (!is_array($data) || ($data['expires'] ?? 0) < time()) {
            @unlink($file);
            return null;
        }
        return $data['response'] ?? null;
    }

    public static function set(string $prompt, string $response): void
    {
        $ttl = (int)(defined('AI_CACHE_TTL') ? AI_CACHE_TTL : 3600);
        $file = self::dir() . '/' . hash('sha256', $prompt) . '.json';
        file_put_contents($file, json_encode([
            'response' => $response,
            'expires'  => time() + $ttl,
        ]));
    }
}
