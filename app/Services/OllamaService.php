<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OllamaService
{
    protected string $apiUrl;

    protected string $model;

    protected string $apiKey;

    public function __construct()
    {
        $this->apiUrl = rtrim((string) config('services.ollama.api_url'), '/');
        $this->model = (string) config('services.ollama.model');
        $this->apiKey = (string) config('services.ollama.api_key');
    }

    public function configured(): bool
    {
        return $this->apiKey !== '' && $this->apiUrl !== '';
    }

    /**
     * Send a chat prompt to the Ollama-compatible endpoint and return the reply.
     */
    public function chat(string $prompt, string $system = 'You are a helpful assistant.'): string
    {
        if (! $this->configured()) {
            throw new RuntimeException('Ollama is not configured. Set OLLAMA_API_URL and OLLAMA_API_KEY in your .env file.');
        }

        $response = Http::timeout(180)
            ->connectTimeout(30)
            ->retry(2, 500)
            ->withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Accept' => 'application/json',
            ])
            ->post("{$this->apiUrl}/api/chat", [
                'model' => $this->model,
                'stream' => false,
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Ollama request failed: '.$this->summarizeError($response));
        }

        $content = data_get($response->json(), 'message.content');

        if (! is_string($content) || trim($content) === '') {
            throw new RuntimeException('Ollama returned an empty response.');
        }

        return trim($content);
    }

    /**
     * Try to extract a short, useful error message from a failed response.
     */
    protected function summarizeError(Response $response): string
    {
        if ($response instanceof RequestException && $response->response) {
            return (string) $response->response->status();
        }

        $message = data_get($response->json(), 'error');

        if (is_string($message) && $message !== '') {
            return $message;
        }

        return (string) $response->status();
    }
}
