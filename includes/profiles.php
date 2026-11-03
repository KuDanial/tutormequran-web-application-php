<?php
declare(strict_types=1);
require_once __DIR__ . '/uploads.php';

const TMQ_PROFILE_FIELDS = [
 'student'=>['profile_picture','full_name','phone','address','gender','date_of_birth','password'],
 'tutor'=>['profile_picture','full_name','phone','gender','date_of_birth','address','teaching_mode','teaching_state','teaching_location','languages','lesson_focus','hourly_rate','bio','social_networks','password'],
];
const TMQ_STATES = ['Johor','Kedah','Kelantan','Melaka','Negeri Sembilan','Pahang','Penang','Perak','Perlis','Sabah','Sarawak','Selangor','Terengganu','Kuala Lumpur','Labuan','Putrajaya'];
function tmq_profile_rows(string $sql, array $params=[]): array { $q=tmq_db()->prepare($sql);$q->execute($params);return $q->fetchAll(); }
function tmq_profile_run(string $sql, array $params=[]): void { $q=tmq_db()->prepare($sql);$q->execute($params); }
function tmq_profile_user(int $id): array
{
    $rows=tmq_profile_rows('SELECT user_id,role,username,email,first_name,last_name,ic_number,phone,gender,date_of_birth,personal_state,address,profile_picture_path,account_status FROM users WHERE user_id=?',[$id]);
    if (!$rows) { throw new DomainException('Account unavailable.'); } return $rows[0];
}
function tmq_profile_tutor(int $id): array
{
    $rows=tmq_profile_rows('SELECT t.*, c.certificate_name, c.qualification_level, c.status AS certificate_status, c.tutor_id AS certificate_owner FROM tutors t LEFT JOIN certificates c ON c.certificate_id=t.displayed_certificate_id WHERE t.tutor_id=?',[$id]);
    if (!$rows) { throw new DomainException('Tutor unavailable.'); }
    $t=$rows[0];$t=array_merge(tmq_profile_user((int)$t['user_id']),$t);
    $t['languages']=tmq_profile_rows('SELECT l.language_id AS id,l.name FROM languages l JOIN tutor_languages x ON x.language_id=l.language_id WHERE x.tutor_id=? ORDER BY l.language_id',[$id]);
    $t['focuses']=tmq_profile_rows('SELECT l.focus_id AS id,l.name FROM lesson_focuses l JOIN tutor_lesson_focuses x ON x.focus_id=l.focus_id WHERE x.tutor_id=? ORDER BY l.focus_id',[$id]);
    return $t;
}
function tmq_own_tutor(int $userId): array
{
    $r=tmq_profile_rows('SELECT tutor_id FROM tutors WHERE user_id=?',[$userId]);
    if (!$r) { throw new DomainException('Tutor account unavailable.'); } return tmq_profile_tutor((int)$r[0]['tutor_id']);
}
function tmq_profile_missing(array $t): array
{
    $missing=[];
    if ($t['account_status']!=='active') { $missing[]='active account'; }
    if (!$t['displayed_certificate_id'] || $t['certificate_status']!=='approved' || (int)$t['certificate_owner']!==(int)$t['tutor_id']) { $missing[]='approved displayed qualification'; }
    foreach (['profile_picture_path'=>'profile picture','teaching_state'=>'teaching state','teaching_area'=>'teaching location','bio'=>'biography'] as $key=>$label) { if (!trim((string)$t[$key])) { $missing[]=$label; } }
    if (!in_array($t['teaching_mode'],['online','physical','both'],true)) { $missing[]='teaching mode'; }
    if ($t['hourly_rate']===null || (float)$t['hourly_rate']<=0) { $missing[]='hourly rate'; }
    if (!$t['languages']) { $missing[]='at least one language'; }
    if (!$t['focuses']) { $missing[]='at least one lesson focus'; }
    return $missing;
}
function tmq_public_tutor(array $t): bool { return $t['profile_visibility']==='public' && tmq_profile_missing($t)===[]; }
function tmq_profile_settings(string $role, bool $lock=false): array
{
    return array_column(tmq_profile_rows('SELECT field_key,is_editable FROM profile_field_settings WHERE role=?'.($lock?' FOR UPDATE':''),[$role]),'is_editable','field_key');
}
function tmq_profile_allowed(array $settings,string $key): bool { return (int)($settings[$key]??0)===1; }
function tmq_profile_text(string $value,int $max,bool $required=false): string
{
    $value=trim($value);
    if (($required && $value==='') || !mb_check_encoding($value,'UTF-8') || mb_strlen($value)>$max || str_contains($value,"\0")) { throw new DomainException('A required value is missing or too long.'); }return $value;
}
function tmq_profile_update_sql(string $table,string $key,int $id,array $changes): void
{
    if (!$changes) { return; }
    $columns=implode(', ',array_map(fn($c)=>"`$c`=?",array_keys($changes)));
    tmq_profile_run("UPDATE `$table` SET $columns WHERE `$key`=?",[...array_values($changes),$id]);
}
function tmq_save_profile(array $actor,int $userId,array $post,string $action): void
{
    if ($actor['role']!=='admin' && (int)$actor['user_id']!==$userId) { throw new DomainException('Profile not available.'); }
    $db=tmq_db();$upload=null;
    try {
        $db->beginTransaction();tmq_profile_rows('SELECT user_id FROM users WHERE user_id=? FOR UPDATE',[$userId]);
        $u=tmq_profile_user($userId);$t=null;
        if ($u['role']==='tutor') { tmq_profile_rows('SELECT tutor_id FROM tutors WHERE user_id=? FOR UPDATE',[$userId]);$t=tmq_own_tutor($userId); }
        $settings=tmq_profile_settings($u['role'],true);
        $admin=$actor['role']==='admin';$users=[];$tutors=[];
        $prefix=$u['role']==='student'?'stud':'tutor';
        $personal=[$prefix.'FName'=>['first_name','full_name',100],$prefix.'LName'=>['last_name','full_name',100],$prefix.'Phone'=>['phone','phone',32],$prefix.'Email'=>['email',null,254],'gender'=>['gender','gender',24],'dateOfBirth'=>['date_of_birth','date_of_birth',10],'state'=>['personal_state','address',40],$prefix.'Address'=>['address','address',5000]];
        $teaching=['displayName'=>['display_name',null,150],'teachingState'=>['teaching_state','teaching_state',40],'teachingLocation'=>['teaching_area','teaching_location',255],'teachingMode'=>['teaching_mode','teaching_mode',16],'hourlyRate'=>['hourly_rate','hourly_rate',12],'shortBio'=>['bio','bio',10000]];
        $social=[];foreach(['facebook','instagram','linkedin','twitter','tiktok'] as $key) {$social[$key]=[$key.'_url','social_networks',2048];}
        $mapping=match($action){'personal'=>$personal,'teaching'=>$teaching,'additional'=>array_intersect_key($personal,array_flip(['gender','dateOfBirth']))+array_intersect_key($teaching,array_flip(['teachingLocation'])),'social'=>$social,default=>[]};
        if ($action!=='personal' && !$t) { throw new DomainException('Tutor profile required.'); }
        if (isset($post['qualification']) && tmq_input($post,'qualification')!==($t['certificate_name']??'')) { throw new DomainException('Qualification changes require an approved certificate.'); }
        foreach($mapping as $name=>[$column,$setting,$max]) {
            if (!array_key_exists($name,$post)) { continue; }
            $value=tmq_profile_text(tmq_input($post,$name),$max,in_array($column,['first_name','last_name','email'],true));
            if ($column==='gender') {$value=strtolower(str_replace(' ','_',$value));if(!in_array($value,['','male','female','prefer_not_to_say'],true)){throw new DomainException('Invalid gender.');}}
            if ($column==='date_of_birth' && $value!=='') {$d=DateTimeImmutable::createFromFormat('!Y-m-d',$value);if(!$d||$d->format('Y-m-d')!==$value||$d>new DateTimeImmutable('-18 years')){throw new DomainException('Enter a valid adult date of birth.');}}
            if (in_array($column,['personal_state','teaching_state'],true) && $value!=='' && !in_array($value,TMQ_STATES,true)) {throw new DomainException('Select a Malaysian state or territory.');}
            if ($column==='email') {$value=strtolower($value);if(!filter_var($value,FILTER_VALIDATE_EMAIL)){throw new DomainException('Enter a valid email.');}}
            if ($column==='teaching_mode') {$value=strtolower($value);if(!in_array($value,['online','physical','both'],true)){throw new DomainException('Select Online, Physical or Both.');}}
            if ($column==='hourly_rate' && $value!=='') {if(!preg_match('/\A(?:0|[1-9][0-9]{0,7})(?:\.[0-9]{1,2})?\z/',$value)||(float)$value<=0){throw new DomainException('Enter a positive hourly rate with up to two decimals.');}$value=number_format((float)$value,2,'.','');}
            if (str_ends_with($column,'_url') && $value!=='' && (!filter_var($value,FILTER_VALIDATE_URL)||!in_array(strtolower(parse_url($value,PHP_URL_SCHEME)??''),['https','http'],true))) {throw new DomainException('Social links must be valid HTTP/HTTPS URLs.');}
            $isUser=array_key_exists($column,$u);$old=$isUser?$u[$column]:($t[$column]??null);
            if (!$admin && $setting && !tmq_profile_allowed($settings,$setting) && (string)$old!==$value) {throw new DomainException('This field is disabled by Admin: '.$setting.'.');}
            if ($isUser) {$users[$column]=$value===''?null:$value;}else{$tutors[$column]=$value===''?null:$value;}
        }
        if ($admin && $action==='personal' && isset($post['accountStatus'])) {$status=strtolower(tmq_input($post,'accountStatus'));if(!in_array($status,['active','inactive'],true)){throw new DomainException('Invalid account status.');}$users['account_status']=$status;}
        if (in_array($action,['personal','additional'],true) && isset($_FILES['profilePicture']) && ($_FILES['profilePicture']['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE) {
            if (!$admin && !tmq_profile_allowed($settings,'profile_picture')) {throw new DomainException('Profile picture changes are disabled by Admin.');}
            $upload=tmq_receive_upload('profilePicture','profiles');$users['profile_picture_path']=$upload['file_path'];
        }
        tmq_profile_update_sql('users','user_id',$userId,$users);
        if ($t) {
            tmq_profile_update_sql('tutors','tutor_id',(int)$t['tutor_id'],$tutors);
            foreach(['languages'=>['languages','language_id','tutor_languages','languages'],'focuses'=>['lesson_focuses','focus_id','tutor_lesson_focuses','lesson_focus']] as $name=>[$table,$key,$join,$setting]) {
                if (!in_array($action,['teaching','additional'],true) || !isset($post[$name.'_present'])) {continue;}
                $values=$post[$name]??[];
                if(!is_array($values)||count($values)>100){throw new DomainException('Invalid teaching selections.');}
                $ids=[];foreach($values as $id){if(!is_string($id)||!ctype_digit($id)||!(int)$id){throw new DomainException('Invalid teaching selection.');}$ids[]=(int)$id;}
                $ids=array_values(array_unique($ids));sort($ids);
                $old=array_map('intval',array_column($t[$name],'id'));sort($old);
                if(!$admin&&!tmq_profile_allowed($settings,$setting)&&$old!==$ids){throw new DomainException('This field is disabled by Admin: '.$setting.'.');}
                $canonical=array_map('intval',array_column(tmq_profile_rows("SELECT `$key` FROM `$table`"),$key));
                if(array_diff($ids,$canonical)){throw new DomainException('Choose an existing language or lesson focus.');}
                tmq_profile_run("DELETE FROM `$join` WHERE tutor_id=?",[$t['tutor_id']]);
                foreach($ids as $id){tmq_profile_run("INSERT INTO `$join` (tutor_id,`$key`) VALUES (?,?)",[$t['tutor_id'],$id]);}
            }
            $saved=tmq_profile_tutor((int)$t['tutor_id']);
            if ($saved['profile_visibility']==='public' && $saved['account_status']==='active' && tmq_profile_missing($saved)) {throw new DomainException('Make the profile Private before removing required public information.');}
        }
        $db->commit();
        // Retain replaced files: historic QR snapshots may still reference them.
    } catch(Throwable $e) {if($db->inTransaction()){$db->rollBack();}tmq_discard_upload($upload);if($e instanceof PDOException && (int)($e->errorInfo[1]??0)===1062){throw new DomainException('That email is already registered.');}throw $e;}
}
function tmq_save_visibility(array $actor,string $visibility): void
{
    $visibility=strtolower($visibility);if(!in_array($visibility,['draft','private','public'],true)){throw new DomainException('Invalid visibility.');}
    $db=tmq_db();try{$db->beginTransaction();tmq_profile_rows('SELECT user_id FROM users WHERE user_id=? FOR UPDATE',[$actor['user_id']]);tmq_profile_rows('SELECT tutor_id FROM tutors WHERE user_id=? FOR UPDATE',[$actor['user_id']]);$t=tmq_own_tutor((int)$actor['user_id']);
        if($visibility==='public' && ($missing=tmq_profile_missing($t))){throw new DomainException('Before publishing, complete: '.implode(', ',$missing).'.');}
        tmq_profile_run('UPDATE tutors SET profile_visibility=? WHERE tutor_id=?',[$visibility,$t['tutor_id']]);$db->commit();
    }catch(Throwable $e){if($db->inTransaction()){$db->rollBack();}throw $e;}
}
function tmq_save_qr(array $actor): void
{
    $db=tmq_db();$file=null;try{$db->beginTransaction();tmq_profile_rows('SELECT tutor_id FROM tutors WHERE user_id=? FOR UPDATE',[$actor['user_id']]);$t=tmq_own_tutor((int)$actor['user_id']);$file=tmq_receive_upload('duitnowQr','duitnow',true);tmq_profile_run('UPDATE tutors SET duitnow_qr_path=?, duitnow_qr_updated_at=UTC_TIMESTAMP() WHERE tutor_id=?',[$file['file_path'],$t['tutor_id']]);$db->commit();}catch(Throwable $e){if($db->inTransaction()){$db->rollBack();}tmq_discard_upload($file);throw $e;}
}
function tmq_save_settings(array $actor,array $post): void
{
    if($actor['role']!=='admin'){throw new DomainException('Admin access required.');}
    $submitted=$post['editable']??[];if(!is_array($submitted)){throw new DomainException('Invalid settings.');}
    foreach($submitted as $role=>$fields){if(!isset(TMQ_PROFILE_FIELDS[$role])||!is_array($fields)||array_diff(array_keys($fields),TMQ_PROFILE_FIELDS[$role])){throw new DomainException('Unapproved editable-field key.');}foreach($fields as $v){if($v!=='1'){throw new DomainException('Invalid editable-field value.');}}}
    $admin=tmq_profile_rows('SELECT admin_id FROM admins WHERE user_id=?',[$actor['user_id']])[0]['admin_id'];
    $db=tmq_db();try{$db->beginTransaction();foreach(TMQ_PROFILE_FIELDS as $role=>$fields){foreach($fields as $key){tmq_profile_run('INSERT INTO profile_field_settings (role,field_key,is_editable,updated_by_admin_id) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE is_editable=VALUES(is_editable),updated_by_admin_id=VALUES(updated_by_admin_id)',[$role,$key,isset($submitted[$role][$key])?1:0,$admin]);}}$db->commit();}catch(Throwable $e){if($db->inTransaction()){$db->rollBack();}throw $e;}
}
function tmq_photo_url(array $user): string {return !empty($user['profile_picture_path'])?tmq_url('profile-file.php?kind=profile&id='.(int)$user['user_id']):tmq_url('assets/img/default-avatar-profile.png');}
