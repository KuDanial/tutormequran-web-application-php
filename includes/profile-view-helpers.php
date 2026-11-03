<?php
function tmq_profile_value(string $name): string
{
    global $profile;
    $map=['studFName'=>'first_name','studLName'=>'last_name','studPhone'=>'phone','studEmail'=>'email','studAddress'=>'address','tutorFName'=>'first_name','tutorLName'=>'last_name','tutorPhone'=>'phone','tutorEmail'=>'email','tutorAddress'=>'address','gender'=>'gender','dateOfBirth'=>'date_of_birth','state'=>'personal_state','displayName'=>'display_name','qualification'=>'certificate_name','teachingState'=>'teaching_state','teachingLocation'=>'teaching_area','teachingMode'=>'teaching_mode','hourlyRate'=>'hourly_rate','shortBio'=>'bio','profileVisibility'=>'profile_visibility','accountStatus'=>'account_status'];
    foreach(['facebook','instagram','linkedin','twitter','tiktok'] as $key){$map[$key]=$key.'_url';}
    return (string)($profile[$map[$name]??$name]??'');
}
function tmq_profile_disabled(string $name): string
{
    global $editable,$actor;
    $map=['studFName'=>'full_name','studLName'=>'full_name','tutorFName'=>'full_name','tutorLName'=>'full_name','studPhone'=>'phone','tutorPhone'=>'phone','studAddress'=>'address','tutorAddress'=>'address','state'=>'address','dateOfBirth'=>'date_of_birth','profilePicture'=>'profile_picture','teachingState'=>'teaching_state','teachingLocation'=>'teaching_location','teachingMode'=>'teaching_mode','hourlyRate'=>'hourly_rate','shortBio'=>'bio','languages'=>'languages','focuses'=>'lesson_focus','gender'=>'gender'];
    foreach(['facebook','instagram','linkedin','twitter','tiktok'] as $key){$map[$key]='social_networks';}
    return $actor['role']!=='admin' && isset($map[$name]) && !tmq_profile_allowed($editable,$map[$name]) ? ' disabled aria-disabled="true"' : '';
}
function tmq_profile_options(string $name,array $values): string
{
    $selected=tmq_profile_value($name);$html='<option value="">Select an option</option>';
    foreach($values as $key=>$label){$value=is_int($key)?$label:$key;$html.='<option value="'.tmq_escape($value).'"'.($selected===$value?' selected':'').'>'.tmq_escape($label).'</option>';}
    return $html;
}
function tmq_profile_choices(string $name): void
{
    global $profile;
    [$table,$key]=$name==='languages'?['languages','language_id']:['lesson_focuses','focus_id'];
    $disabled=tmq_profile_disabled($name);$selected=array_map('intval',array_column($profile[$name]??[],'id'));
    if(!$disabled){echo '<input type="hidden" name="'.$name.'_present" value="1">';}
    foreach(tmq_profile_rows("SELECT `$key` AS id,name FROM `$table` ORDER BY `$key`") as $row){echo '<label class="lesson-tag"><input type="checkbox" name="'.$name.'[]" value="'.(int)$row['id'].'"'.(in_array((int)$row['id'],$selected,true)?' checked':'').$disabled.'> '.tmq_escape($row['name']).'</label>';}
}
function tmq_profile_detail(string $label): string
{
    global $profile,$missing;
    $p=$profile??[];$mapping=['First Name'=>'first_name','Last Name'=>'last_name','IC Number'=>'ic_number','Phone'=>'phone','Email'=>'email','Address'=>'address','Username'=>'username','Gender'=>'gender','Date of Birth'=>'date_of_birth','State'=>'personal_state','Address / Area'=>'address','Teaching Location / Area'=>'teaching_area','Teaching Mode'=>'teaching_mode','About'=>'bio','Biography'=>'bio','Qualification'=>'certificate_name','Display Name'=>'display_name','Hourly Rate'=>'hourly_rate','Visibility'=>'profile_visibility','Profile Visibility'=>'profile_visibility','Current Visibility'=>'profile_visibility'];
    if(isset($mapping[$label])){return (string)($p[$mapping[$label]]??'Not provided');}
    return match($label){
        'Student ID'=>'ST-'.($p['student_id']??''),'Tutor ID'=>'TU-'.($p['tutor_id']??''),'Account ID'=>'ACC-'.($p['user_id']??''),'Role'=>ucfirst($p['role']??''),
        'Teaching Location'=>trim(($p['teaching_area']??'').', '.($p['teaching_state']??''),', '),
        'Language Available','Language'=>implode(', ',array_column($p['languages']??[],'name'))?:'Not selected',
        'Lesson Focus'=>implode(' · ',array_column($p['focuses']??[],'name'))?:'Not selected',
        'Certificate','Verification','Tutor Status'=>($p['certificate_status']??'No displayed qualification'),
        'Next Step'=>$missing?'Complete: '.implode(', ',$missing):'Choose your profile visibility',
        'Available Slot'=>'Availability is not required to publish.',
        'Profile Picture'=>!empty($p['profile_picture_path'])?'Uploaded':'Not uploaded',
        'Optional Social Networks'=>implode(' · ',array_filter(array_map(fn($k)=>$p[$k.'_url']??'', ['facebook','instagram','linkedin','twitter','tiktok'])))?:'Not provided',default=>'Not provided',
    };
}
function tmq_profile_notice(): void
{
    global $profileMessage,$profileSuccess;
    if($profileMessage!==''){echo '<aside class="flow-notice '.($profileSuccess?'info':'warm').'" role="status"><p>'.tmq_escape($profileMessage).'</p></aside>';}
}
