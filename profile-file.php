<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/profiles.php';
try {
    $kind=tmq_input($_GET,'kind');$id=filter_var(tmq_input($_GET,'id'),FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
    if(!$id||!in_array($kind,['certificate','profile','qr'],true)){http_response_code(404);exit('File unavailable.');}
    $actor=tmq_current_user();$key=null;$mime=null;
    if($kind==='certificate'){
        $rows=tmq_profile_rows('SELECT c.file_path,c.mime_type,t.user_id FROM certificates c JOIN tutors t ON t.tutor_id=c.tutor_id WHERE c.certificate_id=?',[$id]);
        if($rows&&$actor&&($actor['role']==='admin'||($actor['role']==='tutor'&&(int)$rows[0]['user_id']===(int)$actor['user_id']))){$key=$rows[0]['file_path'];$mime=$rows[0]['mime_type'];}
    }elseif($kind==='qr'){
        $rows=tmq_profile_rows('SELECT user_id,duitnow_qr_path FROM tutors WHERE tutor_id=?',[$id]);
        if($rows&&$actor&&($actor['role']==='admin'||(int)$rows[0]['user_id']===(int)$actor['user_id'])){$key=$rows[0]['duitnow_qr_path'];}
    }else{
        $rows=tmq_profile_rows('SELECT user_id,profile_picture_path FROM users WHERE user_id=?',[$id]);
        if($rows){
            $allowed=$actor&&($actor['role']==='admin'||(int)$actor['user_id']===$id);
            $tutors=tmq_profile_rows('SELECT tutor_id FROM tutors WHERE user_id=?',[$id]);
            if(!$allowed&&$tutors){$allowed=tmq_public_tutor(tmq_profile_tutor((int)$tutors[0]['tutor_id']));}
            if($allowed){$key=$rows[0]['profile_picture_path'];}
        }
    }
    if(!$key){http_response_code(404);exit('File unavailable.');}
    $file=tmq_stored_file($key);if(!is_file($file)){http_response_code(404);exit('File unavailable.');}
    $mime=$mime?:((new finfo(FILEINFO_MIME_TYPE))->file($file));
    if(!in_array($mime,$kind==='certificate'?['image/jpeg','image/png','application/pdf']:['image/jpeg','image/png'],true)){http_response_code(404);exit('File unavailable.');}
    $stream=@fopen($file,'rb');if(!$stream){throw new RuntimeException('File unavailable.');}
    header('Content-Type: '.$mime);header('X-Content-Type-Options: nosniff');header("Content-Security-Policy: default-src 'none'; sandbox");header('Cache-Control: private, no-store');
    header('Content-Disposition: '.($kind==='certificate'?'attachment':'inline').'; filename="'.$kind.'-'.$id.'.'.pathinfo($key,PATHINFO_EXTENSION).'"');
    header('Content-Length: '.filesize($file));session_write_close();fpassthru($stream);fclose($stream);
}catch(Throwable $e){http_response_code(503);echo 'File temporarily unavailable.';}
