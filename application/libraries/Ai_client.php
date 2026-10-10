<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Ai_client - one interface over several chat-model providers.
 *
 *  - local     : any OpenAI-compatible server (e.g. Gemma on http://192.168.1.5:8080/v1)
 *  - openai    : ChatGPT (api.openai.com)
 *  - gemini    : Google Gemini (generativelanguage.googleapis.com)
 *  - deepseek  : DeepSeek (api.deepseek.com, OpenAI-compatible, text only)
 *  - openrouter: OpenRouter (openrouter.ai, OpenAI-compatible, supports multiple models)
 *
 * chat() takes OpenAI-style messages [{role, content}] and returns the reply text.
 */
class Ai_client {

    private $provider;
    private $base_url;
    private $model;
    private $api_key;
    private $timeout;

    /** Token counts of the last call: ['in' => int, 'out' => int] */
    public $last_usage = ['in' => 0, 'out' => 0];

    public function __construct($config = []) {
        $this->configure($config);
    }

    public function configure(array $config) {
        $this->provider = $config['provider'] ?? 'local';
        $this->base_url = rtrim($config['base_url'] ?? '', '/');
        $this->model    = $config['model'] ?? '';
        $this->api_key  = $config['api_key'] ?? '';
        $this->timeout  = (int) ($config['timeout'] ?? 120);

        // OpenAI-compatible servers live under /v1 — accept the bare tunnel link too
        if ($this->provider === 'local' && $this->base_url !== '') {
            $this->base_url = preg_replace('#/chat/completions$#', '', $this->base_url);
            if (!preg_match('#/v\d+$#', $this->base_url)) {
                $this->base_url .= '/v1';
            }
        }
        if ($this->provider === 'openai' && $this->base_url === '') {
            $this->base_url = 'https://api.openai.com/v1';
        }
        if ($this->provider === 'deepseek' && $this->base_url === '') {
            $this->base_url = 'https://api.deepseek.com/v1';
        }
        if ($this->provider === 'openrouter' && $this->base_url === '') {
            $this->base_url = 'https://openrouter.ai/api/v1';
        }
        if ($this->provider === 'gemini' && $this->base_url === '') {
            $this->base_url = 'https://generativelanguage.googleapis.com/v1beta';
        }
        return $this;
    }

    /**
     * @param array $messages [{role: system|user|assistant, content: string}]
     * @param array $opts     temperature, json (bool: ask for a JSON object), images (data URLs, last user turn)
     * @return string
     * @throws Exception on transport / provider error
     */
    public function chat(array $messages, array $opts = []) {
        $this->last_usage = ['in' => 0, 'out' => 0];
        if ($this->provider === 'gemini') {
            return $this->_gemini($messages, $opts);
        }
        return $this->_openai_compatible($messages, $opts);
    }

    // ───────────────────────── OpenAI-compatible (local + ChatGPT) ─────────────────────────

    private function _openai_compatible(array $messages, array $opts) {
        if ($this->base_url === '') {
            throw new Exception('AI server URL is not configured.');
        }

        if (!empty($opts['images'])) {
            $messages = $this->_attach_images_openai($messages, $opts['images']);
        }

        $body = [
            'model'       => $this->model ?: 'default',
            'messages'    => $messages,
            'temperature' => $opts['temperature'] ?? 0.2,
        ];
        // Only hosted OpenAI reliably supports response_format; local servers vary
        if (!empty($opts['max_tokens'])) {
            // OpenAI's current models take max_completion_tokens; other compatible servers use max_tokens
            $body[$this->provider === 'openai' ? 'max_completion_tokens' : 'max_tokens'] = (int) $opts['max_tokens'];
        }
        if (!empty($opts['json']) && in_array($this->provider, ['openai', 'deepseek', 'openrouter'], true)) {
            $body['response_format'] = ['type' => 'json_object'];
        }

        $headers = ['Content-Type: application/json'];
        $headers[] = 'Authorization: Bearer ' . ($this->api_key !== '' ? $this->api_key : 'not-needed');

        $res = $this->_post($this->base_url . '/chat/completions', $body, $headers);

        if (isset($res['error'])) {
            $msg = is_array($res['error']) ? ($res['error']['message'] ?? json_encode($res['error'])) : $res['error'];
            throw new Exception('AI error: ' . $msg);
        }
        $this->last_usage = ['in' => (int) ($res['usage']['prompt_tokens'] ?? 0), 'out' => (int) ($res['usage']['completion_tokens'] ?? 0)];
        $text = $res['choices'][0]['message']['content'] ?? null;
        if ($text === null) {
            throw new Exception('AI returned an empty response.');
        }
        return is_array($text) ? implode('', array_column($text, 'text')) : (string) $text;
    }

    private function _attach_images_openai(array $messages, array $images) {
        for ($i = count($messages) - 1; $i >= 0; $i--) {
            if ($messages[$i]['role'] === 'user') {
                $parts = [['type' => 'text', 'text' => (string) $messages[$i]['content']]];
                foreach ($images as $url) {
                    $parts[] = ['type' => 'image_url', 'image_url' => ['url' => $url]];
                }
                $messages[$i]['content'] = $parts;
                break;
            }
        }
        return $messages;
    }

    // ───────────────────────── Gemini ─────────────────────────

    private function _gemini(array $messages, array $opts) {
        if ($this->api_key === '') {
            throw new Exception('Gemini API key is not configured.');
        }
        $model = $this->model ?: 'gemini-2.0-flash';

        $system   = [];
        $contents = [];
        foreach ($messages as $m) {
            if ($m['role'] === 'system') { $system[] = $m['content']; continue; }
            $contents[] = [
                'role'  => $m['role'] === 'assistant' ? 'model' : 'user',
                'parts' => [['text' => (string) $m['content']]],
            ];
        }

        if (!empty($opts['images']) && $contents) {
            $last = count($contents) - 1;
            foreach ($opts['images'] as $url) {
                if (preg_match('#^data:([^;]+);base64,(.+)$#', $url, $mm)) {
                    $contents[$last]['parts'][] = ['inline_data' => ['mime_type' => $mm[1], 'data' => $mm[2]]];
                }
            }
        }

        $body = [
            'contents'         => $contents,
            'generationConfig' => ['temperature' => $opts['temperature'] ?? 0.2],
        ];
        if ($system) {
            $body['systemInstruction'] = ['parts' => [['text' => implode("\n\n", $system)]]];
        }
        if (!empty($opts['json'])) {
            $body['generationConfig']['responseMimeType'] = 'application/json';
        }
        if (!empty($opts['max_tokens'])) {
            $body['generationConfig']['maxOutputTokens'] = (int) $opts['max_tokens'];
        }

        $url = $this->base_url . '/models/' . rawurlencode($model) . ':generateContent';
        $res = $this->_post($url, $body, ['Content-Type: application/json', 'x-goog-api-key: ' . $this->api_key]);

        if (isset($res['error'])) {
            throw new Exception('Gemini error: ' . ($res['error']['message'] ?? 'unknown'));
        }
        $this->last_usage = ['in' => (int) ($res['usageMetadata']['promptTokenCount'] ?? 0),
                             'out' => (int) (($res['usageMetadata']['candidatesTokenCount'] ?? 0) + ($res['usageMetadata']['thoughtsTokenCount'] ?? 0))];
        $parts = $res['candidates'][0]['content']['parts'] ?? [];
        $text  = implode('', array_map(function ($p) { return $p['text'] ?? ''; }, $parts));
        if ($text === '') {
            throw new Exception('Gemini returned an empty response.');
        }
        return $text;
    }

    // ───────────────────────── HTTP ─────────────────────────

    private function _post($url, array $body, array $headers) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($body, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => $this->timeout,
        ]);
        $raw  = curl_exec($ch);
        $err  = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false) {
            throw new Exception('Could not reach the AI server (' . $err . ').');
        }
        $json = json_decode($raw, true);
        if (!is_array($json)) {
            throw new Exception('AI server returned HTTP ' . $code . ': ' . mb_substr(strip_tags($raw), 0, 200));
        }
        return $json;
    }

    /**
     * Pull the first JSON object out of a model reply (small models wrap it in prose or ```json fences).
     */
    public static function extract_json($text) {
        $text = trim((string) $text);
        if (preg_match('/```(?:json)?\s*(\{.*\})\s*```/s', $text, $m)) {
            $text = $m[1];
        }
        $start = strpos($text, '{');
        $end   = strrpos($text, '}');
        if ($start === false || $end === false || $end < $start) return null;
        $data = json_decode(substr($text, $start, $end - $start + 1), true);
        return is_array($data) ? $data : null;
    }
}
