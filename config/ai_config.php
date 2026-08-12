<?php
/**
 * EduCore - AI Engine Configuration
 * Supports Google Gemini (free tier) and Ollama (local)
 */
define('AI_PROVIDER', 'gemini'); // 'gemini' or 'ollama'

// Google Gemini API (get key from https://aistudio.google.com)
define('GEMINI_API_KEY', 'YOUR_GEMINI_API_KEY_HERE');
define('GEMINI_MODEL', 'gemini-2.0-flash');
define('GEMINI_API_URL', 'https://generativelanguage.googleapis.com/v1beta/models/' . GEMINI_MODEL . ':generateContent');

// Ollama (local fallback)
define('OLLAMA_URL', 'http://localhost:11434/api/generate');
define('OLLAMA_MODEL', 'llama3.2');

define('AI_MAX_TOKENS', 2048);
define('AI_TEMPERATURE', 0.7);
