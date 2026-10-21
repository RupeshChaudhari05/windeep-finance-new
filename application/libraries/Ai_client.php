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
 *  - aicredits : AICredits (aicredits.in/v1, OpenAI-compatible, best pricing for India)
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
    public $last_usage = ['in' => 0, 'out' => 0, 'cached' => 0];

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
        if ($this->provider === 'aicredits' && $this->base_url === '') {
            $this->base_url = 'https://aicredits.in/v1';
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
        $this->last_usage = ['in' => 0, 'out' => 0, 'cached' => 0];
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
        if (!empty($opts['json']) && in_array($this->provider, ['openai', 'deepseek', 'openrouter', 'aicredits'], true)) {
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

    // ───────────────────────── Tool calling (agent loop) ─────────────────────────

    /**
     * One step of an agent loop. Messages are OpenAI-style and may contain assistant turns with
     * `tool_calls` and `tool` turns with results — append the returned `message` and your tool results,
     * then call again.
     *
     * @param array $tools [{name, description, parameters (JSON schema)}]
     * @return array ['content' => string, 'tool_calls' => [{id, name, args(array)}], 'message' => assistant message]
     */
    public function chat_tools(array $messages, array $tools, array $opts = []) {
        $this->last_usage = ['in' => 0, 'out' => 0, 'cached' => 0];
        return $this->provider === 'gemini'
            ? $this->_gemini_tools($messages, $tools, $opts)
            : $this->_openai_tools($messages, $tools, $opts);
    }

    private function _openai_tools(array $messages, array $tools, array $opts) {
        if ($this->base_url === '') {
            throw new Exception('AI server URL is not configured.');
        }
        if (!empty($opts['images'])) {
            $messages = $this->_attach_images_openai($messages, $opts['images']);
        }
        // Drop private keys used by other providers
        $messages = array_map(function ($m) { unset($m['_gemini_parts']); return $m; }, $messages);

        $body = [
            'model'       => $this->model ?: 'default',
            'messages'    => $messages,
            'temperature' => $opts['temperature'] ?? 0.2,
        ];
        if ($tools) {
            $body['tools'] = array_map(function ($t) {
                return ['type' => 'function', 'function' => ['name' => $t['name'], 'description' => $t['description'], 'parameters' => $t['parameters']]];
            }, $tools);
            $body['tool_choice'] = 'auto';
        }
        if (!empty($opts['max_tokens'])) {
            $body[$this->provider === 'openai' ? 'max_completion_tokens' : 'max_tokens'] = (int) $opts['max_tokens'];
        }

        $headers = ['Content-Type: application/json', 'Authorization: Bearer ' . ($this->api_key !== '' ? $this->api_key : 'not-needed')];
        $res = $this->_post($this->base_url . '/chat/completions', $body, $headers);
        if (isset($res['error'])) {
            $msg = is_array($res['error']) ? ($res['error']['message'] ?? json_encode($res['error'])) : $res['error'];
            throw new Exception('AI error: ' . $msg);
        }
        $this->last_usage = ['in' => (int) ($res['usage']['prompt_tokens'] ?? 0), 'out' => (int) ($res['usage']['completion_tokens'] ?? 0),
                             'cached' => (int) ($res['usage']['prompt_tokens_details']['cached_tokens'] ?? ($res['usage']['prompt_cache_hit_tokens'] ?? 0))];

        $msg = $res['choices'][0]['message'] ?? null;
        if (!is_array($msg)) {
            throw new Exception('AI returned an empty response.');
        }
        $content = $msg['content'] ?? '';
        if (is_array($content)) $content = implode('', array_column($content, 'text'));

        $calls = [];
        foreach ((array) ($msg['tool_calls'] ?? []) as $i => $tc) {
            $args = json_decode((string) ($tc['function']['arguments'] ?? '{}'), true);
            $calls[] = ['id' => $tc['id'] ?? ('call_' . $i), 'name' => (string) ($tc['function']['name'] ?? ''), 'args' => is_array($args) ? $args : []];
        }

        $assistant = ['role' => 'assistant', 'content' => (string) $content];
        if ($calls) {
            $assistant['tool_calls'] = array_map(function ($c) {
                return ['id' => $c['id'], 'type' => 'function',
                        'function' => ['name' => $c['name'], 'arguments' => json_encode((object) $c['args'], JSON_UNESCAPED_UNICODE)]];
            }, $calls);
        }
        // DeepSeek reasoner wants its reasoning echoed back on tool turns
        if (isset($msg['reasoning_content'])) $assistant['reasoning_content'] = $msg['reasoning_content'];

        return ['content' => (string) $content, 'tool_calls' => $calls, 'message' => $assistant];
    }

    private function _gemini_tools(array $messages, array $tools, array $opts) {
        if ($this->api_key === '') {
            throw new Exception('Gemini API key is not configured.');
        }
        $model = $this->model ?: 'gemini-2.0-flash';

        // Index of the admin's own last message (images go there, not on a tool-result turn)
        $last_user = null;
        foreach ($messages as $i => $m) if ($m['role'] === 'user') $last_user = $i;

        $system = [];
        $contents = [];
        $names = [];   // tool_call_id => function name, for functionResponse
        foreach ($messages as $i => $m) {
            if ($m['role'] === 'system') { $system[] = $m['content']; continue; }
            if ($m['role'] === 'assistant') {
                if (!empty($m['_gemini_parts'])) {
                    $parts = $m['_gemini_parts'];   // replayed verbatim (keeps thought signatures)
                } else {
                    $parts = [];
                    if ((string) ($m['content'] ?? '') !== '') $parts[] = ['text' => (string) $m['content']];
                    foreach ((array) ($m['tool_calls'] ?? []) as $tc) {
                        $parts[] = ['functionCall' => ['name' => $tc['function']['name'], 'args' => (object) (json_decode($tc['function']['arguments'], true) ?: [])]];
                    }
                }
                foreach ((array) ($m['tool_calls'] ?? []) as $tc) $names[$tc['id']] = $tc['function']['name'];
                if ($parts) $contents[] = ['role' => 'model', 'parts' => $parts];
                continue;
            }
            if ($m['role'] === 'tool') {
                $resp = json_decode((string) $m['content'], true);
                $part = ['functionResponse' => ['name' => $m['name'] ?? ($names[$m['tool_call_id']] ?? 'tool'),
                                                'response' => is_array($resp) ? (object) $resp : ['result' => (string) $m['content']]]];
                // Consecutive tool results share one user turn
                $n = count($contents);
                if ($n && $contents[$n - 1]['role'] === 'user' && isset($contents[$n - 1]['parts'][0]['functionResponse'])) {
                    $contents[$n - 1]['parts'][] = $part;
                } else {
                    $contents[] = ['role' => 'user', 'parts' => [$part]];
                }
                continue;
            }
            $parts = [['text' => (string) $m['content']]];
            if ($i === $last_user && !empty($opts['images'])) {
                foreach ($opts['images'] as $url) {
                    if (preg_match('#^data:([^;]+);base64,(.+)$#', $url, $mm)) {
                        $parts[] = ['inline_data' => ['mime_type' => $mm[1], 'data' => $mm[2]]];
                    }
                }
            }
            $contents[] = ['role' => 'user', 'parts' => $parts];
        }

        // Gemini wants user/model turns to alternate — merge neighbours with the same role
        $merged = [];
        foreach ($contents as $ct) {
            $n = count($merged);
            if ($n && $merged[$n - 1]['role'] === $ct['role']) $merged[$n - 1]['parts'] = array_merge($merged[$n - 1]['parts'], $ct['parts']);
            else $merged[] = $ct;
        }
        $contents = $merged;

        $body = [
            'contents'         => $contents,
            'generationConfig' => ['temperature' => $opts['temperature'] ?? 0.2],
        ];
        if ($system) $body['systemInstruction'] = ['parts' => [['text' => implode("\n\n", $system)]]];
        if (!empty($opts['max_tokens'])) $body['generationConfig']['maxOutputTokens'] = (int) $opts['max_tokens'];
        if ($tools) {
            $body['tools'] = [['functionDeclarations' => array_map(function ($t) {
                return ['name' => $t['name'], 'description' => $t['description'], 'parameters' => $t['parameters']];
            }, $tools)]];
        }

        $url = $this->base_url . '/models/' . rawurlencode($model) . ':generateContent';
        $res = $this->_post($url, $body, ['Content-Type: application/json', 'x-goog-api-key: ' . $this->api_key]);
        if (isset($res['error'])) {
            throw new Exception('Gemini error: ' . ($res['error']['message'] ?? 'unknown'));
        }
        $this->last_usage = ['in' => (int) ($res['usageMetadata']['promptTokenCount'] ?? 0),
                             'out' => (int) (($res['usageMetadata']['candidatesTokenCount'] ?? 0) + ($res['usageMetadata']['thoughtsTokenCount'] ?? 0))];

        $this->last_usage['cached'] = (int) ($res['usageMetadata']['cachedContentTokenCount'] ?? 0);
        $parts = $res['candidates'][0]['content']['parts'] ?? [];
        $text = '';
        $calls = [];
        foreach ($parts as $i => $p) {
            if (isset($p['text']) && empty($p['thought'])) $text .= $p['text'];
            if (isset($p['functionCall'])) {
                $calls[] = ['id' => 'g' . $i . '_' . substr(md5(uniqid('', true)), 0, 6), 'name' => (string) $p['functionCall']['name'],
                            'args' => (array) ($p['functionCall']['args'] ?? [])];
            }
        }
        if ($text === '' && !$calls) {
            throw new Exception('Gemini returned an empty response.');
        }
        $assistant = ['role' => 'assistant', 'content' => $text, '_gemini_parts' => $parts];
        if ($calls) {
            $assistant['tool_calls'] = array_map(function ($c) {
                return ['id' => $c['id'], 'type' => 'function', 'function' => ['name' => $c['name'], 'arguments' => json_encode((object) $c['args'], JSON_UNESCAPED_UNICODE)]];
            }, $calls);
        }
        return ['content' => $text, 'tool_calls' => $calls, 'message' => $assistant];
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

    /** Optional callable run about every 10 s while waiting for the AI server (keeps streamed replies alive). */
    public $heartbeat = null;

    private function _post($url, array $body, array $headers) {
        // Text pulled out of PDFs can hold invalid UTF-8; without the flag json_encode returns false
        // and an empty request is sent.
        $payload = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        if ($payload === false) throw new Exception('Could not prepare the AI request: ' . json_last_error_msg());
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => $this->timeout,
        ]);
        if (is_callable($this->heartbeat)) {
            $last = time();
            $beat = $this->heartbeat;
            curl_setopt($ch, CURLOPT_NOPROGRESS, false);
            curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, function () use (&$last, $beat) {
                if (time() - $last >= 10) { $last = time(); $beat(); }
                return 0;
            });
        }
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
