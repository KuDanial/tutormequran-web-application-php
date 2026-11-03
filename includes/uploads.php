<?php
declare(strict_types=1);
function tmq_storage_root(): string
{
    $path = str_replace('\\', '/', require __DIR__ . '/../config/uploads.php');
    if (!preg_match('~^(?:[A-Za-z]:/|/)~', $path) || str_contains($path, '..')) { throw new RuntimeException('Invalid storage configuration.'); }
    $document = realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: realpath(__DIR__ . '/..');
    $normalized = strtolower(rtrim($path, '/'));
    if ($document) {
        $web = strtolower(rtrim(str_replace('\\','/',$document),'/'));
        if ($normalized === $web || str_starts_with($normalized, $web.'/')) { throw new RuntimeException('Upload storage must be outside the document root.'); }
    }
    if (!is_dir($path) && !@mkdir($path,0700,true) && !is_dir($path)) { throw new RuntimeException('Storage unavailable.'); }
    $actual = realpath($path);
    if ($actual === false) { throw new RuntimeException('Storage unavailable.'); }
    // Resolve symlinks before trusting the configured location.
    if ($document) {
        $resolved = strtolower(str_replace('\\','/',$actual));
        if ($resolved === $web || str_starts_with($resolved,$web.'/')) { throw new RuntimeException('Unsafe storage location.'); }
    }
    return $actual;
}
function tmq_stored_file(string $key): string
{
    if (!preg_match('~\A(profiles|certificates|duitnow)/[a-f0-9]{40}\.(jpg|png|pdf)\z~', $key)) { throw new RuntimeException('Invalid file reference.'); }
    $root = tmq_storage_root();
    $path = $root . '/' . $key;
    if (is_link(dirname($path)) || is_link($path)) { throw new RuntimeException('Invalid file location.'); }
    return $path;
}
function tmq_receive_upload(string $field, string $kind, bool $required = false): ?array
{
    $file = $_FILES[$field] ?? null;
    if (!$file || ($file['error'] ?? null) === UPLOAD_ERR_NO_FILE) {
        if ($required) { throw new DomainException('Choose a file to upload.'); }
        return null;
    }
    if (!is_array($file) || !is_string($file['name'] ?? null) || !is_string($file['tmp_name'] ?? null)
        || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) { throw new DomainException('Upload failed. Maximum file size is 5 MB.'); }
    $original = $file['name'];
    if ($original === '' || strlen($original)>255 || preg_match('~[\\\\/\x00-\x1f]~',$original)) { throw new DomainException('Use a plain filename without folders or control characters.'); }
    $tmp = $file['tmp_name'];
    if (!is_uploaded_file($tmp)) { throw new DomainException('Invalid upload.'); }
    $size = filesize($tmp);
    if (!$size || $size > 5242880) { throw new DomainException('Maximum file size is 5 MB.'); }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
    $extension = strtolower(pathinfo($original,PATHINFO_EXTENSION));
    $types = ['image/jpeg'=>['jpg','jpeg'],'image/png'=>['png']];
    if ($kind === 'certificates') { $types['application/pdf']=['pdf']; }
    if (!isset($types[$mime]) || !in_array($extension,$types[$mime],true)) { throw new DomainException('Choose a valid JPG/PNG image' . ($kind==='certificates'?' or PDF.':'.')); }
    $bytes = file_get_contents($tmp);
    if (preg_match('/<\?(?:php|=)/i',$bytes)) { throw new DomainException('Executable file content is not allowed.'); }
    if ($mime === 'application/pdf') {
        if (!str_starts_with($bytes,'%PDF-') || !str_contains(substr($bytes,-2048),'%%EOF')
            || preg_match('~/\s*(JavaScript|JS|Launch|EmbeddedFile|OpenAction)\b~i',$bytes)) { throw new DomainException('Choose a valid PDF without active scripts or embedded files.'); }
    } else {
        $image = @getimagesize($tmp);
        if (!$image || ($image['mime'] ?? '') !== $mime || $image[0]<1 || $image[1]<1 || $image[0]*$image[1]>20000000) { throw new DomainException('Invalid image or image dimensions too large.'); }
        if ($mime==='image/png' && (!str_starts_with($bytes,"\x89PNG\r\n\x1a\n") || !str_ends_with($bytes,"IEND\xaeB`\x82"))) { throw new DomainException('Invalid PNG content.'); }
        if ($mime==='image/jpeg' && (!str_starts_with($bytes,"\xff\xd8") || !str_ends_with($bytes,"\xff\xd9"))) { throw new DomainException('Invalid JPEG content.'); }
    }
    $key = $kind . '/' . bin2hex(random_bytes(20)) . '.' . ($mime==='image/jpeg'?'jpg':$extension);
    $target = tmq_stored_file($key);
    if (!is_dir(dirname($target)) && !@mkdir(dirname($target),0700) && !is_dir(dirname($target))) { throw new RuntimeException('Upload directory unavailable.'); }
    if (!@move_uploaded_file($tmp,$target)) { throw new RuntimeException('Upload could not be stored.'); }
    return ['file_path'=>$key,'original_filename'=>$original,'mime_type'=>$mime,'file_size_bytes'=>$size];
}
function tmq_discard_upload(?array $upload): void
{
    if ($upload) { $path=tmq_stored_file($upload['file_path']); if (is_file($path) && !@unlink($path)) { error_log('Unable to remove an uncommitted upload.'); } }
}
