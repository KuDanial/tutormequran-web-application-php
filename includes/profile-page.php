<?php
require_once __DIR__ . '/certificates.php';
if(!isset($authUser)){http_response_code(404);exit;}
$profilePage=basename($_SERVER['SCRIPT_FILENAME']);
$profileMessage='';$profileSuccess=false;$profile=null;$certificate=null;$certificateHistory=[];
try {
    $actor=$authUser;
    if(($_SERVER['REQUEST_METHOD']??'GET')==='POST' && empty($_POST) && (int)($_SERVER['CONTENT_LENGTH']??0)>0){http_response_code(413);throw new DomainException('The request is too large. Maximum upload size is 5 MB.');}
    if($actor['role']==='admin'){
        $targetId=filter_var(tmq_input($_GET,'id'),FILTER_VALIDATE_INT,['options'=>['min_range'=>1]])?:0;
        if($profilePage==='manage-certificates-view.php' && $targetId){$r=tmq_profile_rows('SELECT * FROM certificates WHERE certificate_id=?',[$targetId]);if($r){$certificate=$r[0];$profile=tmq_profile_tutor((int)$certificate['tutor_id']);}}
        elseif(in_array($profilePage,['manage-tutors-view.php','manage-tutors-edit.php'],true)&&$targetId){$profile=tmq_profile_tutor($targetId);}
    }elseif($actor['role']==='tutor'){$profile=tmq_own_tutor((int)$actor['user_id']);}
    else{$profile=tmq_profile_user((int)$actor['user_id']);$profile['student_id']=tmq_profile_rows('SELECT student_id FROM students WHERE user_id=?',[$actor['user_id']])[0]['student_id'];}
    $action=tmq_input($_POST,'profile_action');
    if(($_SERVER['REQUEST_METHOD']??'GET')==='POST' && $action!==''){
        tmq_check_csrf();
        $allowed=match($profilePage){
            'student-profile-edit.php','tutor-account-edit.php','tutor-account-verified.php'=>['personal'],
            'tutor-profile-edit.php'=>['personal','teaching','social','visibility'],
            'tutor-profile-setup.php'=>['teaching','additional','social'],
            'manage-tutors-edit.php'=>['personal','teaching','social'],
            'tutor-profile-ready.php','tutor-profile-visibility.php'=>['visibility'],
            'tutor-certificate-upload.php','tutor-certificate-replace.php'=>['certificate'],
            'tutor-certificate-view.php','tutor-certificate-verified.php'=>['displayed'],
            'manage-certificates-view.php'=>['review'], 'admin-settings.php'=>['settings'],
            'tutor-payment-qr.php'=>['qr'],default=>[],
        };
        if(!in_array($action,$allowed,true)){throw new DomainException('Action unavailable on this page.');}
        if(in_array($action,['personal','teaching','additional','social'],true)){if(!$profile){throw new DomainException('Select a profile first.');}tmq_save_profile($actor,(int)$profile['user_id'],$_POST,$action);}
        elseif($action==='visibility'){tmq_save_visibility($actor,tmq_input($_POST,'profileVisibility'));}
        elseif($action==='certificate'){tmq_submit_certificate($actor,$_POST);}
        elseif($action==='displayed'){tmq_choose_certificate($actor,(int)tmq_input($_POST,'displayed_certificate_id'));}
        elseif($action==='review'){if(!$certificate){throw new DomainException('Select a certificate first.');}tmq_review_certificate($actor,(int)$certificate['certificate_id'],$_POST);}
        elseif($action==='settings'){tmq_save_settings($actor,$_POST);}
        elseif($action==='qr'){tmq_save_qr($actor);}
        $_SESSION['profile_saved']=true;
        $folder=$actor['role']==='admin'?'admin/':($actor['role']==='student'?'student/':'tutor/');
        tmq_redirect($folder.$profilePage.($actor['role']==='admin'&&$targetId?'?id='.$targetId:''));
    }
    if($profile && isset($profile['tutor_id'])){
        $certificateHistory=tmq_certificates((int)$profile['tutor_id']);
        if($certificate){$certificate=current(array_filter($certificateHistory,fn($c)=>(int)$c['certificate_id']===(int)$certificate['certificate_id']));}
        else{
            $requested=filter_var(tmq_input($_GET,'certificate_id'),FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
            $certificate=isset($_GET['certificate_id'])?($requested?current(array_filter($certificateHistory,fn($c)=>(int)$c['certificate_id']===$requested)):null):($certificateHistory[0]??null);
            if(isset($_GET['certificate_id'])&&!$certificate){http_response_code(404);}
        }
    }
    if(!empty($_SESSION['profile_saved'])){unset($_SESSION['profile_saved']);$profileSuccess=true;$profileMessage='Changes saved.';}
}catch(DomainException $e){if(!in_array(http_response_code(),[403,413],true)){http_response_code(422);}$profileMessage=$e->getMessage();}
catch(Throwable $e){http_response_code(503);$profileMessage='This update is temporarily unavailable. Please try again later.';}
$editable=$profile?tmq_profile_settings($profile['role']):[];
$missing=isset($profile['tutor_id'])?tmq_profile_missing($profile):[];
$applicationApproved=(bool)array_filter($certificateHistory,fn($c)=>$c['status']==='approved');
$applicationStatus=$applicationApproved?'approved':($certificateHistory[0]['status']??'No certificate submitted yet');
require_once __DIR__ . '/profile-view-helpers.php';
