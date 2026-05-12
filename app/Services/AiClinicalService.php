<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Clinical AI helper.
 *
 * Calls a real LLM provider (OpenAI / Gemini / Anthropic) when configured via
 * .env (AI_PROVIDER, AI_API_KEY, AI_MODEL). Otherwise returns the caller's
 * `mock` closure so the demo always produces realistic suggestions even
 * without an API key.
 *
 * Always returns an array. On error returns ['error' => '...'] so the front-end
 * can display the message in a SweetAlert.
 */
class AiClinicalService
{
    /**
     * @param  string  $prompt        Full prompt text.
     * @param  int     $maxTokens     Output token budget.
     * @param  array{mock?: callable, temperature?: float}  $opts
     */
    public static function call(string $prompt, int $maxTokens = 1500, array $opts = []): array
    {
        $temperature = (float) ($opts['temperature'] ?? 0.7);
        $provider = config('services.ai.provider', env('AI_PROVIDER'));
        $apiKey   = config('services.ai.api_key',  env('AI_API_KEY'));
        $model    = config('services.ai.model',    env('AI_MODEL'));

        if (! $provider || ! $apiKey) {
            if (isset($opts['mock']) && is_callable($opts['mock'])) {
                $mock = ($opts['mock'])();
                $mock['_source'] = 'mock';
                return $mock;
            }
            return ['error' => 'AI is not configured. Set AI_PROVIDER and AI_API_KEY in .env, or pass a mock fallback.'];
        }

        try {
            $result = match ($provider) {
                'openai'    => self::callOpenAi($apiKey, $prompt, $model, $maxTokens, $temperature),
                'gemini'    => self::callGemini($apiKey, $prompt, $model, $maxTokens, $temperature),
                'anthropic' => self::callAnthropic($apiKey, $prompt, $model, $maxTokens, $temperature),
                default     => ['error' => "Unsupported AI provider: {$provider}"],
            };
            if (! isset($result['error'])) $result['_source'] = $provider;
            return $result;
        } catch (\Throwable $e) {
            Log::error('AiClinicalService failed', ['provider' => $provider, 'error' => $e->getMessage()]);
            if (isset($opts['mock']) && is_callable($opts['mock'])) {
                $mock = ($opts['mock'])();
                $mock['_source'] = 'mock-fallback';
                $mock['_warning'] = 'Live AI call failed; showing offline suggestion.';
                return $mock;
            }
            return ['error' => 'AI service error: ' . $e->getMessage()];
        }
    }

    private static function callOpenAi(string $apiKey, string $prompt, ?string $model, int $maxTokens, float $temperature): array
    {
        $model = $model ?: 'gpt-4o-mini';
        $response = Http::timeout(45)
            ->withHeaders(['Authorization' => "Bearer {$apiKey}"])
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a clinical documentation assistant. Always respond with valid JSON only — no prose, no code fences.'],
                    ['role' => 'user',   'content' => $prompt],
                ],
                'max_tokens'      => $maxTokens,
                'temperature'     => $temperature,
                'response_format' => ['type' => 'json_object'],
            ]);

        if (! $response->successful()) {
            return ['error' => 'OpenAI: ' . ($response->json('error.message') ?? $response->body())];
        }
        $text = $response->json('choices.0.message.content', '');
        return self::decodeJson($text);
    }

    private static function callGemini(string $apiKey, string $prompt, ?string $model, int $maxTokens, float $temperature): array
    {
        $model = $model ?: 'gemini-2.0-flash';
        $response = Http::timeout(45)->post(
            "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}",
            [
                'contents' => [['parts' => [['text' => $prompt]]]],
                'generationConfig' => [
                    'temperature'      => $temperature,
                    'maxOutputTokens'  => $maxTokens,
                    'responseMimeType' => 'application/json',
                ],
            ]
        );
        if (! $response->successful()) {
            return ['error' => 'Gemini: ' . ($response->json('error.message') ?? $response->body())];
        }
        $text = $response->json('candidates.0.content.parts.0.text', '');
        return self::decodeJson($text);
    }

    private static function callAnthropic(string $apiKey, string $prompt, ?string $model, int $maxTokens, float $temperature): array
    {
        $model = $model ?: 'claude-haiku-4-5-20251001';
        $response = Http::timeout(45)
            ->withHeaders([
                'x-api-key'         => $apiKey,
                'anthropic-version' => '2023-06-01',
            ])
            ->post('https://api.anthropic.com/v1/messages', [
                'model'       => $model,
                'max_tokens'  => $maxTokens,
                'temperature' => $temperature,
                'system'      => 'You are a clinical documentation assistant. Respond with valid JSON only.',
                'messages'    => [['role' => 'user', 'content' => $prompt]],
            ]);
        if (! $response->successful()) {
            return ['error' => 'Anthropic: ' . ($response->json('error.message') ?? $response->body())];
        }
        $text = $response->json('content.0.text', '');
        return self::decodeJson($text);
    }

    private static function decodeJson(string $text): array
    {
        $text = trim($text);
        // Strip ```json fences if present
        if (str_starts_with($text, '```')) {
            $text = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', $text);
        }
        $decoded = json_decode($text, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['error' => 'AI returned invalid JSON: ' . json_last_error_msg()];
        }
        return is_array($decoded) ? $decoded : ['error' => 'AI returned non-object payload.'];
    }
}
