<?php

namespace BookneticApp\Providers\Services;

use BookneticApp\Providers\IoC\Attributes\Service;
use RuntimeException;

#[Service]
class MediaService
{
    public function uploadFolder($module = 'Base'): string
    {
        $upload_dir	= wp_upload_dir();
        $upload_dir = $upload_dir['basedir'] . '/booknetic/' . strtolower($module) . (empty($module) ? '' : '/');

        if (!is_dir($upload_dir) && !mkdir($upload_dir, 0755, true) && !is_dir($upload_dir)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $upload_dir));
        }

        return $upload_dir;
    }

    public function uploadedFile($fileName, $module = 'Base'): string
    {
        return $this->uploadFolder($module) . basename($fileName);
    }

    public function deleteUploadedFile($fileName, $module = 'Base'): bool
    {
        if (empty($fileName)) {
            return false;
        }

        $filePath = $this->uploadedFile($fileName, $module);

        if (!is_file($filePath) || !is_writable($filePath)) {
            return false;
        }

        return @unlink($filePath);
    }

    public function handleImageUpload(array $image, string $module = 'Base'): string
    {
        if (empty($image) || !isset($image['tmp_name']) || !is_string($image['tmp_name'])) {
            return '';
        }

        $pathInfo = pathinfo($image["name"]);
        $extension = strtolower($pathInfo['extension']);
        $allowedExtensions = ['jpg', 'jpeg', 'png'];

        if (!in_array($extension, $allowedExtensions)) {
            throw new RuntimeException(bkntc__('Only JPG and PNG images allowed!'));
        }

        $imageName = md5(base64_encode(random_int(1, 9999999) . microtime(true))) . '.' . $extension;
        $fileName = $this->uploadedFile($imageName, $module);

        move_uploaded_file($image['tmp_name'], $fileName);

        return $imageName;
    }

    public function getUrl(string $fileName, string $module = 'Base'): string
    {
        if (empty($fileName)) {
            return '';
        }

        $upload_dir = wp_upload_dir();

        return $upload_dir['baseurl'] . '/booknetic/' . strtolower($module) . '/' . basename($fileName);
    }

    public function copy(string $source, string $module = 'Base'): string
    {
        $oldPath = $this->uploadedFile($source, $module);

        if (!is_file($oldPath)) {
            return '';
        }

        $extension = pathinfo($source, PATHINFO_EXTENSION);
        $newImage  = md5(base64_encode(rand(1, 9999999) . microtime(true))) . '.' . $extension;
        $newPath   = $this->uploadedFile($newImage, $module);

        copy($oldPath, $newPath);

        return $newImage;
    }
}
