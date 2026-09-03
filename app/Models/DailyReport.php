<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DailyReport extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['user_id', 'sub_module_id', 'report_date', 'description'];

    protected $casts = [
        'report_date' => 'date',
    ];

    public static function parseTasks(mixed $value, ?string $fallbackTitle = null): array
    {
        if (empty($value)) {
            return filled($fallbackTitle) ? [trim(strip_tags($fallbackTitle))] : [];
        }

        $rawStrings = [];

        if (is_array($value)) {
            $rawStrings = $value;
        } elseif (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $rawStrings = $decoded;
            } else {
                $rawStrings = [$value];
            }
        }

        $tasks = [];

        foreach ($rawStrings as $chunk) {
            if (!is_string($chunk)) {
                continue;
            }

            $text = $chunk;

            // 1. Convert HTML block tags and line breaks into newlines
            $text = preg_replace('/<\s*br\s*\/?>/i', "\n", $text);
            $text = preg_replace('/<\s*\/\s*(li|p|div|tr|h[1-6]|blockquote|section)\s*>/i', "\n", $text);
            $text = preg_replace('/<\s*(li|p|div|tr|h[1-6]|blockquote|section)[^>]*>/i', "\n", $text);
            $text = preg_replace('/<\s*\/?\s*(ul|ol|table|tbody|thead)[^>]*>/i', "\n", $text);

            // 2. Remove &nbsp; entities and non-breaking spaces
            $text = str_ireplace(['&nbsp;', '&#160;', '&amp;nbsp;'], ' ', $text);
            $text = str_replace(["\xc2\xa0", "\u{00a0}"], ' ', $text);

            // 3. Decode HTML entities
            $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $text = str_ireplace(['&nbsp;', '&#160;'], ' ', $text);
            $text = str_replace(["\xc2\xa0", "\u{00a0}"], ' ', $text);

            // 4. Strip any remaining inline HTML tags
            $text = strip_tags($text);

            // 5. Fix concatenated sentences where tags were stripped without spaces
            $text = preg_replace('/([a-z0-9\)])\.([A-Z])/u', "$1.\n$2", $text);
            $text = preg_replace('/([A-Z]{2,})([A-Z][a-z])/u', "$1\n$2", $text);
            $text = preg_replace('/([a-z])([A-Z][a-z])/u', "$1\n$2", $text);

            // 6. Split by newlines
            $lines = preg_split('/[\r\n]+/', $text);

            foreach ($lines as $line) {
                // Split internal bullet points if present
                $parts = preg_split('/(?<=\s|^)[-*•\x{2022}]\s+/u', $line);
                foreach ($parts as $part) {
                    $part = preg_replace('/[^\S\r\n]+/', ' ', $part);
                    $part = preg_replace('/\s+([,.:;!?])/', '$1', $part);
                    $part = preg_replace('/^[-*•\x{2022}]\s*/u', '', $part);
                    $part = trim($part);

                    if ($part !== '') {
                        $tasks[] = $part;
                    }
                }
            }
        }

        if (empty($tasks) && filled($fallbackTitle)) {
            $tasks[] = trim(strip_tags($fallbackTitle));
        }

        return $tasks;
    }

    protected function description(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn ($value) => self::parseTasks($value),
            set: function ($value) {
                if (is_array($value)) {
                    return json_encode(array_values(array_filter($value, fn($item) => filled($item))));
                }
                return $value;
            }
        );
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subModule()
    {
        return $this->belongsTo(SubModule::class);
    }

    public function reportImages()
    {
        return $this->hasMany(ReportImage::class);
    }
}
