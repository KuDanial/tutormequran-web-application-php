<?php
require_once __DIR__ . '/profiles.php';
require_once __DIR__ . '/feedback.php';
$publicTutors=[];$publicTutor=null;$publicError='';
try {
    if(basename($_SERVER['SCRIPT_FILENAME'])==='tutor-detail.php'){
        $id=filter_var(tmq_input($_GET,'id'),FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
        if($id && tmq_profile_rows('SELECT tutor_id FROM tutors WHERE tutor_id=?',[$id])){$candidate=tmq_profile_tutor($id);if(tmq_public_tutor($candidate)){$publicTutor=$candidate;$publicFeedback=tmq_feedback_public((int)$id);}}
        if(!$publicTutor){http_response_code(404);$publicError='This tutor profile is unavailable.';}
    }else{
        $where=["t.profile_visibility='public'","u.account_status='active'"];$params=[];
        $search=trim(tmq_input($_GET,'searchTutor'));if($search!==''){$where[]="(t.display_name LIKE ? OR CONCAT(u.first_name,' ',u.last_name) LIKE ? OR t.bio LIKE ?)";$params=[...$params,...array_fill(0,3,'%'.mb_substr($search,0,150).'%')];}
        $mode=strtolower(tmq_input($_GET,'filterMode'));if(in_array($mode,['online','physical'],true)){$where[]="t.teaching_mode IN (?, 'both')";$params[]=$mode;}
        $state=tmq_input($_GET,'filterState');if(in_array($state,TMQ_STATES,true)){$where[]='t.teaching_state=?';$params[]=$state;}
        $budget=tmq_input($_GET,'filterRate');$ranges=['below30'=>'t.hourly_rate<30','30to50'=>'t.hourly_rate BETWEEN 30 AND 50','above50'=>'t.hourly_rate>50'];if(isset($ranges[$budget])){$where[]=$ranges[$budget];}
        foreach(tmq_profile_rows('SELECT t.tutor_id FROM tutors t JOIN users u ON u.user_id=t.user_id WHERE '.implode(' AND ',$where).' ORDER BY t.tutor_id',$params) as $row){$candidate=tmq_profile_tutor((int)$row['tutor_id']);if(tmq_public_tutor($candidate)){$publicTutors[]=$candidate;}}
    }
}catch(Throwable $e){$publicTutor=null;http_response_code(503);$publicError='Tutor profiles are temporarily unavailable.';}
function tmq_public_name(array $t): string {return $t['display_name']?:trim($t['first_name'].' '.$t['last_name']);}
