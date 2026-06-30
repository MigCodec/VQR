<?php

namespace App\Services;

use App\Models\DocumentType;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class DocumentAiExtractor
{
    public function extract(string $absolutePath, string $mimeType, DocumentType $documentType): array
    {
        $apiKey = config('services.openrouter.api_key');

        if (! $apiKey || ! is_file($absolutePath)) {
            return [];
        }

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->asJson()
            ->timeout(45)
            ->post('https://openrouter.ai/api/v1/chat/completions', [
                'model' => config('services.openrouter.model'),
                'plugins' => $this->pluginsFor($mimeType),
                'messages' => [[
                    'role' => 'user',
                    'content' => $this->contentFor($absolutePath, $mimeType, $documentType),
                ]],
                'temperature' => 0,
                'response_format' => [
                    'type' => 'json_schema',
                    'json_schema' => [
                        'name' => 'vqr_vehicle_document',
                        'strict' => true,
                        'schema' => [
                            'type' => 'object',
                            'additionalProperties' => false,
                            'properties' => [
                                'document_type' => ['type' => ['string', 'null']],
                                'folio' => ['type' => ['string', 'null']],
                                'plate' => ['type' => ['string', 'null']],
                                'issued_at' => ['type' => ['string', 'null'], 'description' => 'YYYY-MM-DD'],
                                'expires_at' => ['type' => ['string', 'null'], 'description' => 'YYYY-MM-DD'],
                                'issuer' => ['type' => ['string', 'null']],
                                'confidence' => ['type' => 'number', 'minimum' => 0, 'maximum' => 1],
                            ],
                            'required' => [
                                'document_type',
                                'folio',
                                'plate',
                                'issued_at',
                                'expires_at',
                                'issuer',
                                'confidence',
                            ],
                        ],
                    ],
                ],
            ]);

        if (! $response->successful()) {
            return [];
        }

        return $this->normalize($this->decodeContent(
            $response->json('choices.0.message.content')
        ));
    }

    private function pluginsFor(string $mimeType): array
    {
        if ($mimeType !== 'application/pdf') {
            return [];
        }

        return [[
            'id' => 'file-parser',
            'pdf' => [
                'engine' => config('services.openrouter.pdf_engine', 'cloudflare-ai'),
            ],
        ]];
    }

    private function contentFor(string $absolutePath, string $mimeType, DocumentType $documentType): array
    {
        $prompt = 'Extrae datos de este documento vehicular chileno para VQR. '
            .'Tipo esperado: '.$documentType->name.'. '
            .'Devuelve solo JSON valido. Usa fechas en formato YYYY-MM-DD. '
            .'Si un dato no aparece claramente, usa null. No inventes fechas.';

        $fileData = 'data:'.$mimeType.';base64,'.base64_encode((string) file_get_contents($absolutePath));

        if (Str::startsWith($mimeType, 'image/')) {
            return [
                ['type' => 'text', 'text' => $prompt],
                ['type' => 'image_url', 'image_url' => ['url' => $fileData]],
            ];
        }

        return [
            ['type' => 'text', 'text' => $prompt],
            ['type' => 'file', 'file' => [
                'filename' => basename($absolutePath),
                'file_data' => $fileData,
            ]],
        ];
    }

    private function decodeContent(?string $content): array
    {
        if (! $content) {
            return [];
        }

        $content = trim($content);
        $content = preg_replace('/^```(?:json)?|```$/m', '', $content) ?: $content;
        $decoded = json_decode(trim($content), true);

        return is_array($decoded) ? $decoded : [];
    }

    private function normalize(array $data): array
    {
        return [
            'document_type' => $this->stringOrNull($data['document_type'] ?? null),
            'folio' => $this->stringOrNull($data['folio'] ?? null),
            'plate' => $this->stringOrNull($data['plate'] ?? null),
            'issued_at' => $this->dateOrNull($data['issued_at'] ?? null),
            'expires_at' => $this->dateOrNull($data['expires_at'] ?? null),
            'issuer' => $this->stringOrNull($data['issuer'] ?? null),
            'confidence' => is_numeric($data['confidence'] ?? null)
                ? max(0, min(1, (float) $data['confidence']))
                : 0,
        ];
    }

    private function stringOrNull(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function dateOrNull(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
