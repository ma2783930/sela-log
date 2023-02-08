<?php

namespace Sela\Helpers;

use Illuminate\Http\Testing\MimeType;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Symfony\Component\Mime\MimeTypes;

trait BinaryHelper
{
    private static ?MimeTypes $mime;

    /**
     * @param $binary
     * @param $name
     * @return \Illuminate\Http\UploadedFile
     */
    public static function fileFromBinary($binary, $name): UploadedFile
    {
        $tmpFile = tmpfile();

        fwrite($tmpFile, $binary);
        return new UploadedFile(
            stream_get_meta_data($tmpFile)['uri'],
            $name,
            MimeType::from($name),
            null
        );
    }

    /**
     * @param $value
     * @return bool
     */
    public static function isBinary($value): bool
    {
        return false === mb_detect_encoding((string)$value, null, true);
    }

    public static function getMimeTypes()
    {
        if (self::$mime === null) {
            self::$mime = new MimeTypes;
        }

        return self::$mime;
    }

    public static function mimeType($filename)
    {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        return Arr::first(self::getMimeTypes()->getMimeTypes($extension)) ?? 'application/octet-stream';
    }
}
