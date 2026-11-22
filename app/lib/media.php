<?php
/**
 * Media uploads + library listing.
 *
 * Every uploaded image (JPG, PNG, GIF, WebP, AVIF, BMP — plus HEIC/TIFF when the
 * server has Imagick) is converted to a compressed WebP:
 *   - fixes phone-photo rotation (EXIF orientation), strips metadata
 *   - scales down to fit inside "Max size" (Admin → Settings, default 2400 px)
 *   - encodes at "Quality" (default 80); small PNG graphics also try lossless
 *     WebP and keep whichever file is smaller
 * Imagick is used when available (keeps GIF animation), otherwise GD.
 */
final class Media
{
    const IMAGE_TYPES = [
        'image/jpeg' => 'jpg',
        'image/pjpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
        'image/avif' => 'avif',
        'image/bmp' => 'bmp',
        'image/x-ms-bmp' => 'bmp',
        'image/heic' => 'heic',
        'image/heif' => 'heif',
        'image/tiff' => 'tif',
    ];
    /** Formats a browser can show as-is (stored unconverted only if conversion is impossible). */
    const WEB_SAFE = ['image/jpeg', 'image/pjpeg', 'image/png', 'image/webp', 'image/gif', 'image/avif'];
    const OTHER_TYPES = [
        'video/mp4' => 'mp4',
        'video/webm' => 'webm',
        'application/pdf' => 'pdf',
    ];
    const DEFAULT_QUALITY = 80;
    const DEFAULT_MAX = 2400;

    /** @return array{0:int,1:int} [quality 40–95, max side 600–6000 px] */
    public static function settings(): array
    {
        $q = (int)Settings::get('img_quality', (string)self::DEFAULT_QUALITY);
        $max = (int)Settings::get('img_max', (string)self::DEFAULT_MAX);
        return [max(40, min(95, $q ?: self::DEFAULT_QUALITY)), max(600, min(6000, $max ?: self::DEFAULT_MAX))];
    }

    /** Image library used for WebP conversion: 'imagick', 'gd' or '' (none). */
    public static function engine(): string
    {
        static $engine = null;
        if ($engine !== null) {
            return $engine;
        }
        if (class_exists('Imagick')) {
            try {
                if (Imagick::queryFormats('WEBP')) {
                    return $engine = 'imagick';
                }
            } catch (Throwable $e) {
            }
        }
        if (function_exists('imagewebp') && function_exists('imagecreatetruecolor')) {
            return $engine = 'gd';
        }
        return $engine = '';
    }

    public static function engineLabel(): string
    {
        switch (self::engine()) {
            case 'imagick':
                return 'Imagick — all images converted to WebP (GIF animation kept)';
            case 'gd':
                return 'GD — all images converted to WebP (animated GIFs are kept as GIF)';
            default:
                return 'Not available — ask your host to enable the PHP "gd" (with WebP) or "imagick" extension';
        }
    }

    /**
     * Store an uploaded file ($_FILES entry). Returns the media row plus
     * 'original_size' and 'converted' (true when it was turned into WebP).
     * @throws RuntimeException with a user-facing message
     */
    public static function upload(array $file, ?int $userId): array
    {
        if (!isset($file['error']) || is_array($file['error'])) {
            throw new RuntimeException('Invalid upload.');
        }
        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new RuntimeException('File is larger than the server allows (' . ini_get('upload_max_filesize') . ').');
            case UPLOAD_ERR_NO_FILE:
                throw new RuntimeException('No file was selected.');
            default:
                throw new RuntimeException('Upload failed (error ' . (int)$file['error'] . ').');
        }
        if (!is_uploaded_file($file['tmp_name']) && !defined('ST_CLI')) {
            throw new RuntimeException('Invalid upload.');
        }

        $mime = self::detectMime($file['tmp_name']);
        $types = self::IMAGE_TYPES + self::OTHER_TYPES;
        if (!isset($types[$mime])) {
            throw new RuntimeException('This file type is not allowed. Use JPG, PNG, WebP, GIF, AVIF, MP4, WebM or PDF.');
        }
        $isImage = isset(self::IMAGE_TYPES[$mime]);
        $max = $isImage ? 25 * 1024 * 1024 : 120 * 1024 * 1024;
        $originalSize = (int)$file['size'];
        if ($originalSize > $max) {
            throw new RuntimeException('File is too large (max ' . human_bytes($max) . ').');
        }

        $ext = $types[$mime];
        $base = slugify(pathinfo((string)$file['name'], PATHINFO_FILENAME)) ?: 'file';
        $base = substr($base, 0, 60);
        $dirRel = '/uploads/' . date('Y') . '/' . date('m');
        $dirAbs = ST_ROOT . $dirRel;
        if (!is_dir($dirAbs) && !mkdir($dirAbs, 0755, true) && !is_dir($dirAbs)) {
            throw new RuntimeException('Could not create the uploads folder. Check folder permissions.');
        }

        $width = $height = null;
        $converted = false;
        $name = $base . '-' . substr(random_token(4), 0, 6);

        if ($isImage) {
            $result = self::toWebp($file['tmp_name'], $mime, $dirAbs . '/' . $name . '.webp');
            if ($result) {
                [$width, $height] = $result;
                $ext = 'webp';
                $mime = 'image/webp';
                $converted = true;
            } elseif (!in_array($mime, self::WEB_SAFE, true)) {
                throw new RuntimeException('This server cannot convert ' . strtoupper($ext) . ' images. Please upload a JPG or PNG instead.');
            }
        }

        $target = $dirAbs . '/' . $name . '.' . $ext;
        if (!$converted) {
            $moved = defined('ST_CLI') ? copy($file['tmp_name'], $target) : move_uploaded_file($file['tmp_name'], $target);
            if (!$moved) {
                throw new RuntimeException('Could not save the file. Check that /uploads is writable.');
            }
            if ($isImage) {
                $info = @getimagesize($target);
                if ($info) {
                    [$width, $height] = $info;
                }
            }
        }
        @chmod($target, 0644);

        $row = [
            'path' => $dirRel . '/' . $name . '.' . $ext,
            'original_name' => mb_substr((string)$file['name'], 0, 255),
            'mime' => $mime,
            'size' => (int)filesize($target),
            'width' => $width,
            'height' => $height,
            'user_id' => $userId,
            'created_at' => now(),
        ];
        $row['id'] = DB::insert('media', $row);
        $row['original_size'] = $originalSize;
        $row['converted'] = $converted;
        return $row;
    }

    /** "photo.jpg → WebP · 3.2 MB → 240 KB (−92%)" */
    public static function summary(array $row): string
    {
        $text = (string)$row['original_name'];
        if (!empty($row['converted'])) {
            $before = (int)($row['original_size'] ?? 0);
            $after = (int)$row['size'];
            $text .= ' → WebP';
            if ($before > 0) {
                $pct = (int)round(100 - ($after / $before) * 100);
                $text .= ' · ' . human_bytes($before) . ' → ' . human_bytes($after) . ($pct > 0 ? " (−{$pct}%)" : '');
            }
        }
        return $text;
    }

    private static function detectMime(string $tmp): string
    {
        $mime = '';
        if (function_exists('finfo_open')) {
            $f = finfo_open(FILEINFO_MIME_TYPE);
            $mime = (string)finfo_file($f, $tmp);
            finfo_close($f);
        }
        if ($mime === '' || $mime === 'application/octet-stream') {
            $info = @getimagesize($tmp);
            if ($info) {
                $mime = (string)$info['mime'];
            }
        }
        return $mime ?: 'application/octet-stream';
    }

    /** Scale (w, h) to fit inside max×max, never upscaling. */
    private static function fit(int $w, int $h, int $max): array
    {
        if ($w <= $max && $h <= $max) {
            return [$w, $h];
        }
        $ratio = min($max / $w, $max / $h);
        return [max(1, (int)round($w * $ratio)), max(1, (int)round($h * $ratio))];
    }

    /**
     * Convert any supported image to a compressed WebP at $dest.
     * @return array{0:int,1:int}|null final [width, height], or null when it can't be converted
     */
    private static function toWebp(string $src, string $mime, string $dest): ?array
    {
        $engine = self::engine();
        if ($engine === '') {
            return null;
        }
        // Big phone photos need memory to decode; raise the limit when the host allows it.
        $limit = ini_get('memory_limit');
        if ($limit !== '-1' && (int)$limit < 512) {
            @ini_set('memory_limit', '512M');
        }
        [$quality, $max] = self::settings();

        $result = null;
        if ($engine === 'imagick') {
            $result = self::viaImagick($src, $mime, $dest, $quality, $max);
        }
        if ($result === null && function_exists('imagewebp')) {
            $result = self::viaGd($src, $mime, $dest, $quality, $max);
        }
        if ($result === null) {
            @unlink($dest);
            return null;
        }
        // An already-optimised WebP that wasn't resized: keep whichever is smaller.
        if ($mime === 'image/webp' && $result[2] === false && filesize($dest) >= filesize($src)) {
            copy($src, $dest);
        }
        return [$result[0], $result[1]];
    }

    /** @return array{0:int,1:int,2:bool}|null [w, h, resized] */
    private static function viaImagick(string $src, string $mime, string $dest, int $quality, int $max): ?array
    {
        try {
            $im = new Imagick();
            $im->readImage($src);
            if ($im->getNumberImages() > 1) {
                // Animated GIF / WebP: keep every frame.
                $im = $im->coalesceImages();
                $im->setFirstIterator();
                [$w, $h] = [$im->getImageWidth(), $im->getImageHeight()];
                [$nw, $nh] = self::fit($w, $h, $max);
                foreach ($im as $frame) {
                    if ($nw !== $w) {
                        $frame->resizeImage($nw, $nh, Imagick::FILTER_LANCZOS, 1);
                    }
                    $frame->stripImage();
                    $frame->setImageFormat('webp');
                    $frame->setImageCompressionQuality($quality);
                }
                $im->setOption('webp:method', '4');
                $ok = $im->writeImages($dest, true);
            } else {
                self::imagickOrient($im);
                [$w, $h] = [$im->getImageWidth(), $im->getImageHeight()];
                [$nw, $nh] = self::fit($w, $h, $max);
                if ($nw !== $w) {
                    $im->resizeImage($nw, $nh, Imagick::FILTER_LANCZOS, 1);
                }
                $im->stripImage();
                $im->setImageFormat('webp');
                $im->setImageCompressionQuality($quality);
                $im->setOption('webp:method', '6');
                $ok = $im->writeImage($dest);
                if ($ok && $mime === 'image/png' && $nw * $nh <= 2000000) {
                    $tmp = $dest . '.lossless';
                    $im->setOption('webp:lossless', 'true');
                    if ($im->writeImage('webp:' . $tmp) && is_file($tmp) && filesize($tmp) < filesize($dest)) {
                        rename($tmp, $dest);
                    }
                    @unlink($tmp);
                }
            }
            $im->clear();
            if (!$ok || !is_file($dest) || filesize($dest) === 0) {
                return null;
            }
            return [$nw, $nh, $nw !== $w];
        } catch (Throwable $e) {
            error_log('Imagick WebP conversion failed: ' . $e->getMessage());
            return null;
        }
    }

    private static function imagickOrient(Imagick $im): void
    {
        switch ($im->getImageOrientation()) {
            case Imagick::ORIENTATION_TOPRIGHT:
                $im->flopImage();
                break;
            case Imagick::ORIENTATION_BOTTOMRIGHT:
                $im->rotateImage('#000', 180);
                break;
            case Imagick::ORIENTATION_BOTTOMLEFT:
                $im->flipImage();
                break;
            case Imagick::ORIENTATION_LEFTTOP:
                $im->transposeImage();
                break;
            case Imagick::ORIENTATION_RIGHTTOP:
                $im->rotateImage('#000', 90);
                break;
            case Imagick::ORIENTATION_RIGHTBOTTOM:
                $im->transverseImage();
                break;
            case Imagick::ORIENTATION_LEFTBOTTOM:
                $im->rotateImage('#000', -90);
                break;
        }
        $im->setImageOrientation(Imagick::ORIENTATION_TOPLEFT);
    }

    /** @return array{0:int,1:int,2:bool}|null [w, h, resized] */
    private static function viaGd(string $src, string $mime, string $dest, int $quality, int $max): ?array
    {
        $info = @getimagesize($src);
        if (!$info || $info[0] * $info[1] > 50000000) {
            return null; // unreadable, or too many pixels for shared-host memory
        }
        if ($mime === 'image/gif' && self::isAnimatedGif($src)) {
            return null; // GD would drop the animation — keep the GIF
        }
        $loaders = [
            'image/jpeg' => 'imagecreatefromjpeg',
            'image/pjpeg' => 'imagecreatefromjpeg',
            'image/png' => 'imagecreatefrompng',
            'image/webp' => 'imagecreatefromwebp',
            'image/gif' => 'imagecreatefromgif',
            'image/avif' => 'imagecreatefromavif',
            'image/bmp' => 'imagecreatefrombmp',
            'image/x-ms-bmp' => 'imagecreatefrombmp',
        ];
        $loader = $loaders[$mime] ?? null;
        if (!$loader || !function_exists($loader)) {
            return null;
        }
        $img = @$loader($src);
        if (!$img) {
            return null;
        }
        if (!imageistruecolor($img)) {
            imagepalettetotruecolor($img);
        }
        if ($mime === 'image/jpeg' || $mime === 'image/pjpeg') {
            $img = self::gdOrient($img, $src);
        }

        $w = imagesx($img);
        $h = imagesy($img);
        [$nw, $nh] = self::fit($w, $h, $max);
        if ($nw !== $w) {
            $out = imagecreatetruecolor($nw, $nh);
            imagealphablending($out, false);
            imagesavealpha($out, true);
            imagefill($out, 0, 0, imagecolorallocatealpha($out, 0, 0, 0, 127));
            imagecopyresampled($out, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
            imagedestroy($img);
            $img = $out;
        } else {
            imagealphablending($img, false);
            imagesavealpha($img, true);
        }

        $ok = imagewebp($img, $dest, $quality);
        if ($ok && $mime === 'image/png' && $nw * $nh <= 2000000 && defined('IMG_WEBP_LOSSLESS')) {
            $tmp = $dest . '.lossless';
            if (@imagewebp($img, $tmp, IMG_WEBP_LOSSLESS) && is_file($tmp) && filesize($tmp) > 0 && filesize($tmp) < filesize($dest)) {
                rename($tmp, $dest);
            }
            @unlink($tmp);
        }
        imagedestroy($img);
        if (!$ok || !is_file($dest) || filesize($dest) === 0) {
            return null;
        }
        return [$nw, $nh, $nw !== $w];
    }

    /** Rotate/flip a JPEG according to its EXIF orientation (phones store photos sideways). */
    private static function gdOrient($img, string $src)
    {
        if (!function_exists('exif_read_data')) {
            return $img;
        }
        $exif = @exif_read_data($src);
        $o = (int)($exif['Orientation'] ?? 1);
        $rotate = function ($image, int $angle) {
            $r = imagerotate($image, $angle, 0);
            if ($r) {
                imagedestroy($image);
                return $r;
            }
            return $image;
        };
        switch ($o) {
            case 2:
                imageflip($img, IMG_FLIP_HORIZONTAL);
                break;
            case 3:
                $img = $rotate($img, 180);
                break;
            case 4:
                imageflip($img, IMG_FLIP_VERTICAL);
                break;
            case 5:
                $img = $rotate($img, -90);
                imageflip($img, IMG_FLIP_HORIZONTAL);
                break;
            case 6:
                $img = $rotate($img, -90);
                break;
            case 7:
                $img = $rotate($img, 90);
                imageflip($img, IMG_FLIP_HORIZONTAL);
                break;
            case 8:
                $img = $rotate($img, 90);
                break;
        }
        return $img;
    }

    private static function isAnimatedGif(string $file): bool
    {
        $data = @file_get_contents($file);
        return $data !== false && preg_match_all('#\x00\x21\xF9\x04.{4}\x00[\x2C\x21]#s', $data) > 1;
    }

    /** Uploaded files (newest first). */
    public static function uploads(int $limit = 500): array
    {
        $out = [];
        foreach (DB::all('SELECT * FROM {media} ORDER BY id DESC LIMIT ' . (int)$limit) as $r) {
            if (!is_file(ST_ROOT . $r['path'])) {
                continue;
            }
            $out[] = [
                'id' => (int)$r['id'],
                'url' => $r['path'],
                'name' => $r['original_name'],
                'mime' => $r['mime'],
                'size' => (int)$r['size'],
                'w' => $r['width'] ? (int)$r['width'] : null,
                'h' => $r['height'] ? (int)$r['height'] : null,
                'kind' => 'upload',
                'date' => $r['created_at'],
            ];
        }
        return $out;
    }

    /** Images that shipped with the site (/images). */
    public static function bundled(): array
    {
        $root = ST_ROOT . '/images';
        if (!is_dir($root)) {
            return [];
        }
        $out = [];
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
        foreach ($it as $file) {
            /** @var SplFileInfo $file */
            $ext = strtolower($file->getExtension());
            if (!in_array($ext, ['webp', 'jpg', 'jpeg', 'png', 'gif', 'avif', 'mp4', 'webm', 'svg'], true)) {
                continue;
            }
            $rel = '/images' . str_replace('\\', '/', substr($file->getPathname(), strlen($root)));
            $out[] = [
                'id' => null,
                'url' => $rel,
                'name' => $file->getFilename(),
                'mime' => in_array($ext, ['mp4', 'webm'], true) ? 'video/' . $ext : 'image/' . ($ext === 'jpg' ? 'jpeg' : $ext),
                'size' => $file->getSize(),
                'w' => null,
                'h' => null,
                'kind' => 'bundled',
                'date' => date('Y-m-d H:i:s', $file->getMTime()),
            ];
        }
        usort($out, function ($a, $b) {
            return strcmp($a['url'], $b['url']);
        });
        return $out;
    }

    public static function delete(int $id): bool
    {
        $row = DB::row('SELECT * FROM {media} WHERE id = ?', [$id]);
        if (!$row) {
            return false;
        }
        $abs = realpath(ST_ROOT . $row['path']);
        $uploads = realpath(ST_ROOT . '/uploads');
        if ($abs && $uploads && str_starts_with($abs, $uploads) && is_file($abs)) {
            @unlink($abs);
        }
        DB::exec('DELETE FROM {media} WHERE id = ?', [$id]);
        return true;
    }
}
