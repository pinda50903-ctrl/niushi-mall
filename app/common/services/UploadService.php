<?php

declare(strict_types=1);

namespace app\common\services;

use app\common\components\ApiException;
use app\common\model\Attachment;
use Yii;
use yii\web\UploadedFile;

/**
 * 本地文件上传：校验扩展名与大小，落库 nm_attachment
 */
final class UploadService
{
    public const MAX_SIZE = 10 * 1024 * 1024; // 10MB

    public const ALLOW_IMAGE = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico'];
    public const ALLOW_VIDEO = ['mp4', 'mov', 'm4v'];
    public const ALLOW_FILE = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'zip', 'txt'];

    public static function image(UploadedFile $file, int $tenantId): Attachment
    {
        return self::store($file, $tenantId, array_merge(self::ALLOW_IMAGE, self::ALLOW_VIDEO));
    }

    public static function file(UploadedFile $file, int $tenantId): Attachment
    {
        return self::store($file, $tenantId, array_merge(self::ALLOW_IMAGE, self::ALLOW_VIDEO, self::ALLOW_FILE));
    }

    private static function store(UploadedFile $file, int $tenantId, array $allow): Attachment
    {
        if ($file->hasError) {
            throw new ApiException('文件上传失败（错误码 ' . $file->error . '）', ApiException::VALIDATE);
        }
        $ext = strtolower($file->getExtension());
        if (!in_array($ext, $allow, true)) {
            throw new ApiException('不支持的文件类型：' . $ext, ApiException::VALIDATE);
        }
        if ($file->size > self::MAX_SIZE) {
            throw new ApiException('文件大小超过限制（10MB）', ApiException::VALIDATE);
        }

        $subdir = date('Ymd');
        $dir = Yii::getAlias('@uploads') . DIRECTORY_SEPARATOR . $subdir;
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            throw new ApiException('存储目录创建失败', ApiException::SERVER_ERROR);
        }
        $name = bin2hex(random_bytes(10)) . '.' . $ext;
        $fullPath = $dir . DIRECTORY_SEPARATOR . $name;
        if (!$file->saveAs($fullPath)) {
            throw new ApiException('文件保存失败', ApiException::SERVER_ERROR);
        }

        $attachment = new Attachment();
        $attachment->tenant_id = $tenantId;
        $attachment->name = $file->baseName;
        $attachment->path = 'uploads/' . $subdir . '/' . $name;
        $attachment->url = self::url('uploads/' . $subdir . '/' . $name);
        $attachment->size = $file->size;
        $attachment->ext = $ext;
        $attachment->storage = 'local';
        if (!$attachment->save()) {
            throw new ApiException('附件记录保存失败', ApiException::SERVER_ERROR);
        }
        return $attachment;
    }

    /** 相对路径转绝对 URL */
    public static function url(string $path): string
    {
        if ($path === '' || preg_match('#^https?://#', $path)) {
            return $path;
        }
        $base = trim((string)(\Yii::$app->params['cdnUrl'] ?? ''));
        if ($base === '') {
            $base = \Yii::$app->request->getHostInfo();
        }
        return $base . '/' . ltrim($path, '/');
    }
}
