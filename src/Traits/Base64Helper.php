<?php

namespace Sela\Traits;

use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\File\File;

trait Base64Helper {
    /**
     * @param string $value
     * @return \Illuminate\Http\UploadedFile
     */
    public function convertBase64ToFile(string $value): UploadedFile
    {
        if (str_contains($value, ';base64')) {
            [, $value] = explode(';', $value);
            [, $value] = explode(',', $value);
        }

        $binaryData  = base64_decode($value);
        $tmpFile     = tmpfile();
        $tmpFilePath = stream_get_meta_data($tmpFile)['uri'];

        file_put_contents($tmpFilePath, $binaryData);

        $tmpFileObject = new File($tmpFilePath);
        $file          = new UploadedFile(
            $tmpFileObject->getPathname(),
            $tmpFileObject->getFilename(),
            $tmpFileObject->getMimeType(),
            0,
            true
        );

        app()->terminating(function () use ($tmpFile) {
            fclose($tmpFile);
        });

        return $file;
    }
}
