<?php
declare(strict_types=1);
require_once __DIR__ . '/profiles.php';
function tmq_certificates(int $tutorId): array
{
    return tmq_profile_rows('SELECT c.*, CONCAT(u.first_name,\' \',u.last_name) AS reviewer FROM certificates c LEFT JOIN admins a ON a.admin_id=c.reviewed_by_admin_id LEFT JOIN users u ON u.user_id=a.user_id WHERE c.tutor_id=? ORDER BY c.certificate_id DESC',[$tutorId]);
}
function tmq_submit_certificate(array $actor,array $post): void
{
    $purpose=tmq_input($post,'submissionPurpose');
    if(!in_array($purpose,['initial','application_correction','higher_qualification'],true)){throw new DomainException('Select a valid submission purpose.');}
    $name=tmq_profile_text(tmq_input($post,'certificateName'),255,true);
    $year=tmq_input($post,'certificateYear');
    if(!preg_match('/\A[0-9]{4}\z/',$year)||(int)$year<1900||(int)$year>(int)date('Y')){throw new DomainException('Enter a valid certificate year up to the current year.');}
    $level=tmq_input($post,'qualificationLevel');if(!in_array($level,['','ijazah','advanced_ijazah','diploma','bachelor','master','doctorate'],true)){throw new DomainException('Select a valid qualification level.');}
    $origin=tmq_profile_text(tmq_input($post,'certificateOrigin'),80);
    $origin=str_replace('Federal Territory of ','',$origin);
    if($origin==='Others (Outside Malaysia)'){$origin='outside_malaysia';}
    if($origin!=='' && !in_array($origin,[...TMQ_STATES,'outside_malaysia'],true)){throw new DomainException('Select a valid issuing state or origin.');}
    $type=tmq_profile_text(tmq_input($post,'certificateType'),150);
    $description=tmq_profile_text(tmq_input($post,'certificateDescription'),10000);
    $parent=filter_var(tmq_input($post,'replacesCertificate'),FILTER_VALIDATE_INT,['options'=>['min_range'=>1]])?:null;
    $db=tmq_db();$file=null;
    try{$db->beginTransaction();tmq_profile_rows('SELECT user_id FROM users WHERE user_id=? FOR UPDATE',[$actor['user_id']]);tmq_profile_rows('SELECT tutor_id FROM tutors WHERE user_id=? FOR UPDATE',[$actor['user_id']]);$t=tmq_own_tutor((int)$actor['user_id']);$history=tmq_certificates((int)$t['tutor_id']);
        if($purpose==='initial' && $history){throw new DomainException('Use Application correction or Higher qualification for an additional submission.');}
        if($purpose==='higher_qualification' && !array_filter($history,fn($c)=>$c['status']==='approved')){throw new DomainException('Submit your initial qualification or correct the existing application first.');}
        if($purpose==='application_correction'){
            $old=$parent?tmq_profile_rows('SELECT * FROM certificates WHERE certificate_id=? AND tutor_id=? FOR UPDATE',[$parent,$t['tutor_id']]):[];
            if(!$old||!in_array($old[0]['status'],['pending','rejected'],true)){throw new DomainException('Choose your pending or rejected submission to correct. Approved certificates remain in history.');}
            if(tmq_profile_rows('SELECT certificate_id FROM certificates WHERE replaces_certificate_id=?',[$parent])){throw new DomainException('This submission already has a correction. Correct its latest version instead.');}
        } elseif($parent){throw new DomainException('Only an application correction can reference an earlier pending/rejected attempt.');}
        $file=tmq_receive_upload('certificateFile','certificates',true);
        tmq_profile_run('INSERT INTO certificates (tutor_id,replaces_certificate_id,submission_purpose,certificate_name,certificate_type,qualification_level,certificate_year,origin,description,file_path,original_filename,mime_type,file_size_bytes) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)',[$t['tutor_id'],$parent,$purpose,$name,$type?:null,$level?:null,(int)$year,$origin?:null,$description?:null,$file['file_path'],$file['original_filename'],$file['mime_type'],$file['file_size_bytes']]);
        if($parent){tmq_profile_run("UPDATE certificates SET status='superseded' WHERE certificate_id=? AND status='pending'",[$parent]);}
        $db->commit();
    }catch(Throwable $e){if($db->inTransaction()){$db->rollBack();}tmq_discard_upload($file);throw $e;}
}
function tmq_review_certificate(array $actor,int $id,array $post): void
{
    if($actor['role']!=='admin'){throw new DomainException('Admin review required.');}
    $decision=tmq_input($post,'decision');if(!in_array($decision,['approved','rejected'],true)){throw new DomainException('Choose Approve or Reject.');}
    $remark=tmq_profile_text(tmq_input($post,'certificateAdminRemark'),10000,$decision==='rejected');
    $lookup=tmq_profile_rows('SELECT t.tutor_id,t.user_id FROM certificates c JOIN tutors t ON t.tutor_id=c.tutor_id WHERE c.certificate_id=?',[$id]);if(!$lookup){throw new DomainException('Certificate not found.');}
    $db=tmq_db();try{$db->beginTransaction();tmq_profile_rows('SELECT user_id FROM users WHERE user_id=? FOR UPDATE',[$lookup[0]['user_id']]);tmq_profile_rows('SELECT tutor_id FROM tutors WHERE tutor_id=? FOR UPDATE',[$lookup[0]['tutor_id']]);$rows=tmq_profile_rows('SELECT * FROM certificates WHERE certificate_id=? FOR UPDATE',[$id]);
        if($rows[0]['status']!=='pending'){throw new DomainException('This submission has already been reviewed or replaced.');}
        $admin=tmq_profile_rows('SELECT admin_id FROM admins WHERE user_id=?',[$actor['user_id']])[0]['admin_id'];
        tmq_profile_run('UPDATE certificates SET status=?,reviewed_by_admin_id=?,reviewed_at=UTC_TIMESTAMP(),admin_remark=? WHERE certificate_id=?',[$decision,$admin,$remark?:null,$id]);
        if($decision==='approved' && tmq_input($post,'makeDisplayed')==='1'){tmq_profile_run('UPDATE tutors SET displayed_certificate_id=? WHERE tutor_id=?',[$id,$lookup[0]['tutor_id']]);}
        $db->commit();
    }catch(Throwable $e){if($db->inTransaction()){$db->rollBack();}throw $e;}
}
function tmq_choose_certificate(array $actor,int $id): void
{
    $db=tmq_db();try{$db->beginTransaction();tmq_profile_rows('SELECT user_id FROM users WHERE user_id=? FOR UPDATE',[$actor['user_id']]);tmq_profile_rows('SELECT tutor_id FROM tutors WHERE user_id=? FOR UPDATE',[$actor['user_id']]);$t=tmq_own_tutor((int)$actor['user_id']);
        $cert=tmq_profile_rows("SELECT certificate_id FROM certificates WHERE certificate_id=? AND tutor_id=? AND status='approved' FOR UPDATE",[$id,$t['tutor_id']]);
        if(!$cert){throw new DomainException('Select an approved certificate belonging to your account.');}
        tmq_profile_run('UPDATE tutors SET displayed_certificate_id=? WHERE tutor_id=?',[$id,$t['tutor_id']]);$db->commit();
    }catch(Throwable $e){if($db->inTransaction()){$db->rollBack();}throw $e;}
}
