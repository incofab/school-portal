<?php

namespace App\Enums\Media;

enum MediaKind: string
{
    case Image = 'image';
    case Video = 'video';
    case Audio = 'audio';
    case File = 'file';

    public static function fromMimeType(?string $mimeType): self
    {
        return match (true) {
            str_starts_with((string) $mimeType, 'image/') => self::Image,
            str_starts_with((string) $mimeType, 'video/') => self::Video,
            str_starts_with((string) $mimeType, 'audio/') => self::Audio,
            default => self::File,
        };
    }
}
