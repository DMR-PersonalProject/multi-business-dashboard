<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;

/**
 * Validation rules and storage for uploaded pictures (user photos, business images and logos).
 */
class ImageUpload
{
    public const DISK = 'uploads';

    public const MAX_KILOBYTES = 2048;

    /**
     * Raster images only (no SVG), checked against the file's content, up to 2 MB.
     */
    public static function rules(bool $required = false): array
    {
        return [
            $required ? 'required' : 'nullable',
            'file',
            'image',
            'mimes:jpg,jpeg,png,gif,webp',
            'mimetypes:image/jpeg,image/png,image/gif,image/webp',
            'max:'.self::MAX_KILOBYTES,
        ];
    }

    /**
     * Store the file under a random name whose extension comes from the file's
     * content, never from the uploaded filename. Returns the stored filename.
     */
    public static function store(UploadedFile $file, string $folder): string
    {
        $name = $file->hashName();

        $file->storeAs($folder, $name, self::DISK);

        return $name;
    }
}
