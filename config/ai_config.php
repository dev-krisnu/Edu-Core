<?php
/**
 * EduCore - AI Engine Configuration
 * Supports Google Gemini (free tier) and Ollama (local)
 * Configuration loaded from .env file
 */

require_once __DIR__ . '/env.php';

define('AI_PROVIDER', env('AI_PROVIDER', 'gemini')); // 'gemini' or 'ollama'

// Google Gemini API (get key from https://aistudio.google.com)
define('GEMINI_API_KEY', env('GEMINI_API_KEY', ''));
define('GEMINI_MODEL', env('GEMINI_MODEL', 'gemini-2.5-flash'));
define('GEMINI_API_URL', 'https://generativelanguage.googleapis.com/v1beta/models/' . GEMINI_MODEL . ':generateContent');

// Ollama (local fallback)
define('OLLAMA_URL', 'http://localhost:11434/api/generate');
define('OLLAMA_MODEL', 'llama3.2');

define('AI_MAX_TOKENS', (int) env('AI_MAX_TOKENS', 512));
define('AI_TEMPERATURE', (float) env('AI_TEMPERATURE', 0.5));
define('AI_CACHE_TTL', (int) env('AI_CACHE_TTL', 3600));
