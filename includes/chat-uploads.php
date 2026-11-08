<?php
declare(strict_types=1);
require_once __DIR__.'/uploads.php';

// Browser MIME is never used. Office containers are inspected, not accepted as arbitrary ZIPs.
function tmq_chat_file_type(string $path, string $extension): string
{
    $mime=(new finfo(FILEINFO_MIME_TYPE))->file($path);
    $bytes=file_get_contents($path);
    if ($bytes===false || preg_match('/<\?(?:php|=)/i',$bytes)) { throw new DomainException('Executable content is not allowed.'); }
    // libmagic can classify large plain-text files as octet-stream. Inspect a bounded
    // prefix for MIME, then validate EVERY byte as UTF-8/plain text below.
    if ($extension==='txt' && $mime==='application/octet-stream') { $mime=(new finfo(FILEINFO_MIME_TYPE))->buffer(mb_strcut($bytes,0,1024,'UTF-8')); }
    $types=['pdf'=>'application/pdf','txt'=>'text/plain','jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','mp4'=>'video/mp4','webm'=>'video/webm'];
    $office=['docx'=>['word/document.xml','application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        'xlsx'=>['xl/workbook.xml','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        'pptx'=>['ppt/presentation.xml','application/vnd.openxmlformats-officedocument.presentationml.presentation']];
    if (isset($office[$extension])) {
        if (!in_array($mime,['application/zip',$office[$extension][1]],true) || !str_starts_with($bytes,"PK\x03\x04")) { throw new DomainException('Invalid Office document.'); }
        try {
            $zip=new PharData($path,0,null,Phar::ZIP); $total=0; $count=0;
            foreach (new RecursiveIteratorIterator($zip) as $entry) {
                $name=$entry->getPathname(); $total+=$entry->getSize(); $count++;
                if ($count>2000 || $total>52428800 || preg_match('~(?:vbaProject|activeX|embeddings|\.\./|\.(?:exe|dll|js|php|html|svg|bin)$)~i',$name)) { throw new DomainException('Unsafe Office document content.'); }
            }
            if (!isset($zip['[Content_Types].xml'],$zip[$office[$extension][0]])) { throw new DomainException('Office document type does not match its extension.'); }
            $xml=$zip['[Content_Types].xml']->getContent();
            $main=$zip[$office[$extension][0]]->getContent();
            if (preg_match('/macroEnabled|vbaProject|<!DOCTYPE|<!ENTITY/i',$xml.$main)) { throw new DomainException('Active Office content is not allowed.'); }
            $doc=new DOMDocument();
            if (!@$doc->loadXML($xml,LIBXML_NONET) || !str_contains($xml,$office[$extension][1].'.main+xml') || !@$doc->loadXML($main,LIBXML_NONET)) { throw new DomainException('Invalid Office document structure.'); }
            return $office[$extension][1];
        } catch (DomainException $e) { throw $e; }
        catch (Throwable $e) { throw new DomainException('Invalid Office document.'); }
    }
    if (!isset($types[$extension]) || $mime!==$types[$extension]) { throw new DomainException('File content and extension must match an allowed format.'); }
    if ($extension==='txt') {
        if (!mb_check_encoding($bytes,'UTF-8') || preg_match('/[\x00-\x08\x0b\x0c\x0e-\x1f]/',$bytes)
            || preg_match('~<\s*(?:!doctype|html|script|svg|iframe)\b~i',$bytes)) { throw new DomainException('Choose a plain UTF-8 text document.'); }
    } elseif ($extension==='pdf') {
        if (!str_starts_with($bytes,'%PDF-') || !str_contains(substr($bytes,-2048),'%%EOF') || preg_match('~/\s*(JavaScript|JS|Launch|EmbeddedFile|OpenAction)\b~i',$bytes)) { throw new DomainException('Choose a PDF without active content.'); }
    } elseif (in_array($extension,['jpg','jpeg','png'],true)) {
        $info=@getimagesize($path);
        if (!$info || $info['mime']!==$mime || $info[0]*$info[1]>20000000
            || ($extension==='png' && (!str_starts_with($bytes,"\x89PNG\r\n\x1a\n") || !str_ends_with($bytes,"IEND\xaeB`\x82")))
            || ($mime==='image/jpeg' && (!str_starts_with($bytes,"\xff\xd8") || !str_ends_with($bytes,"\xff\xd9")))) { throw new DomainException('Invalid image.'); }
    } elseif ($extension==='mp4') {
        $offset=0; $boxes=[]; $length=strlen($bytes);
        while ($offset+8<=$length) {
            $size=unpack('N',substr($bytes,$offset,4))[1]; $type=substr($bytes,$offset+4,4);
            if ($size===0) { $size=$length-$offset; }
            if ($size<8 || $size>$length-$offset) { throw new DomainException('Invalid MP4 container.'); }
            $boxes[]=$type; $offset+=$size;
        }
        if ($offset!==$length || ($boxes[0]??'')!=='ftyp' || !in_array('moov',$boxes,true) || !in_array('mdat',$boxes,true)) { throw new DomainException('Invalid MP4 video.'); }
    } elseif (!str_starts_with($bytes,"\x1a\x45\xdf\xa3") || !str_contains(substr($bytes,0,256),'webm') || !str_contains($bytes,"\x16\x54\xae\x6b") || !str_contains($bytes,"\x1f\x43\xb6\x75")) {
        throw new DomainException('Invalid WebM video.');
    }
    return $mime;
}

function tmq_chat_receive_files(): array
{
    $files=$_FILES['attachments']??null; $stored=[];
    if (!$files || !is_array($files['name']??null) || count($files['name'])>5) { throw new DomainException('Choose between one and five attachments.'); }
    try {
        foreach ($files['name'] as $i=>$name) {
            $tmp=$files['tmp_name'][$i]??null;
            if (!is_string($name) || !mb_check_encoding($name,'UTF-8') || $name==='' || strlen($name)>255 || preg_match('~[\\\\/\x00-\x1f\x7f]~',$name)
                || ($files['error'][$i]??null)!==UPLOAD_ERR_OK || !is_string($tmp) || !is_uploaded_file($tmp)) { throw new DomainException('Upload failed. Use plain filenames and files up to 10 MB each.'); }
            $size=filesize($tmp);
            if (!$size || $size>10485760) { throw new DomainException('Each attachment must be between 1 byte and 10 MB.'); }
            $ext=strtolower(pathinfo($name,PATHINFO_EXTENSION)); $mime=tmq_chat_file_type($tmp,$ext);
            $key='chat/'.bin2hex(random_bytes(20)).'.'.($ext==='jpeg'?'jpg':$ext); $target=tmq_stored_file($key);
            if (!is_dir(dirname($target)) && !@mkdir(dirname($target),0700) && !is_dir(dirname($target))) { throw new RuntimeException('Storage unavailable.'); }
            if (!@move_uploaded_file($tmp,$target)) { throw new RuntimeException('Upload failed.'); }
            $stored[]=['file_path'=>$key,'original_filename'=>$name,'mime_type'=>$mime,'file_size_bytes'=>$size];
        }
        return $stored;
    } catch (Throwable $e) { foreach ($stored as $file) { tmq_discard_upload($file); } throw $e; }
}
