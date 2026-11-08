"""Phase 3B HTTP/PDO integration checks using an exclusively owned temporary DB.

Run: python tests/authentication.test.py --php C:/xampp/php/php.exe
Requires the configured MariaDB server; never imports into or drops the real DB.
"""
import argparse
import http.cookiejar
import json
import os
from pathlib import Path
import re
import secrets
import socket
import subprocess
import tempfile
import time
import urllib.error
import urllib.parse
import urllib.request

ROOT = Path(__file__).resolve().parents[1]
CHECKS = 0


def check(condition, label):
    global CHECKS
    if not condition:
        raise AssertionError(label)
    CHECKS += 1


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, *args):
        return None


class Browser:
    def __init__(self, base):
        self.base = base
        self.cookies = http.cookiejar.CookieJar()
        self.opener = urllib.request.build_opener(NoRedirect(), urllib.request.HTTPCookieProcessor(self.cookies))

    def request(self, page, data=None):
        payload = None if data is None else urllib.parse.urlencode(data).encode()
        try:
            response = self.opener.open(self.base + page, payload, timeout=15)
        except urllib.error.HTTPError as error:
            response = error
        return response.code, response.read().decode('utf-8'), response.headers

    def token(self, page):
        status, body, headers = self.request(page)
        check(status == 200, 'form loads: ' + page)
        match = re.search(r'name="csrf_token" value="([a-f0-9]{64})"', body)
        check(match is not None, 'CSRF token rendered: ' + page)
        return match[1]

    def sid(self):
        return next((cookie.value for cookie in self.cookies if cookie.name == 'TMQSESSID'), None)


BRIDGE = r'''<?php
declare(strict_types=1);
$root=$argv[1]; $action=$argv[2]; $name=getenv('TMQ_DB_NAME');
if (!preg_match('/\Atutormequran_auth_validation_[a-f0-9]{16}\z/',$name)) { throw new RuntimeException('Unsafe test database name'); }
$cfg=require $root.'/config/database.php';
$db=new PDO('mysql:host='.$cfg['host'].';port='.$cfg['port'].';charset=utf8mb4',$cfg['username'],$cfg['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
if ($action==='create') {
 $q=$db->prepare('SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME=?');$q->execute([$name]);
 if($q->fetchColumn()) { throw new RuntimeException('Name already exists; refusing to touch it'); }
 $sql=file_get_contents($root.'/database/tutormequran.sql');
 $sql=str_replace(['CREATE DATABASE `tutormequran`','USE `tutormequran`;'],['CREATE DATABASE `'.$name.'`','USE `'.$name.'`;'],$sql);
 $sql=preg_replace('/^--[^\r\n]*$/m','',$sql);
 foreach(preg_split('/;\s*(?:\r?\n|$)/',$sql) as $stmt) { if(trim($stmt)!=='') $db->exec($stmt); }
 echo json_encode(['created'=>$name]); exit;
}
if($action==='drop') { $db->exec('DROP DATABASE `'.$name.'`'); echo json_encode(['dropped'=>$name]);exit; }
require $root.'/includes/db.php'; $db=tmq_db();
$input=json_decode(stream_get_contents(STDIN),true,512,JSON_THROW_ON_ERROR);
$q=$db->prepare($input['sql']);$q->execute($input['params']??[]);
echo json_encode($q->columnCount() ? $q->fetchAll() : ['affected'=>$q->rowCount()]);
'''


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--php', default='C:/xampp/php/php.exe')
    parser.add_argument('--subdirectory', action='store_true', help='Also exercise installation under a URL subdirectory')
    args = parser.parse_args()
    name = 'tutormequran_auth_validation_' + secrets.token_hex(8)
    env = dict(os.environ, TMQ_DB_NAME=name)
    created = False
    server = None
    flags = subprocess.CREATE_NO_WINDOW if os.name == 'nt' else 0
    password = 'Validation8-' + secrets.token_hex(12)
    new_password = 'Changed9-' + secrets.token_hex(12)
    with tempfile.TemporaryDirectory(prefix='tmq-auth-tests-') as directory:
        directory = Path(directory).resolve()
        assert directory.is_relative_to(Path(tempfile.gettempdir()).resolve())
        bridge = directory / 'bridge.php'
        bridge.write_text(BRIDGE, encoding='utf-8')

        def php(action, data=None):
            result = subprocess.run([args.php, str(bridge), str(ROOT), action], env=env, input=json.dumps(data or {}), capture_output=True, text=True, encoding='utf-8', creationflags=flags)
            if result.returncode:
                raise RuntimeError('Test bridge failed: ' + result.stderr + result.stdout)
            return json.loads(result.stdout)

        def sql(query, params=None):
            return php('query', {'sql': query, 'params': params or []})

        def registration(role, username):
            prefix = 'stud' if role == 'student' else 'tutor'
            return {'accountRoleType': role, 'accountUsername': username, 'accountPassword': password,
                    'confirmPassword': password, prefix+'FName': 'Development', prefix+'LName': 'Test',
                    prefix+'ICNum': '900101-01-1234', prefix+'Phone': '012-3456789',
                    prefix+'Email': username+'@example.invalid', prefix+'Address': 'Test address, Malaysia',
                    prefix+'Eligibility': 'on', role+'Gender': 'Male', role+'DateOfBirth': '1990-01-01'}

        try:
            php('create'); created = True
            with socket.socket() as sock:
                sock.bind(('127.0.0.1', 0)); port = sock.getsockname()[1]
            prefix = '/' + ROOT.name if args.subdirectory else ''
            base = f'http://127.0.0.1:{port}{prefix}/'
            log = open(directory / 'server.log', 'w', encoding='utf-8')
            server = subprocess.Popen([args.php, '-d', 'display_errors=0', '-d', 'session.save_path='+str(directory), '-S', f'127.0.0.1:{port}', '-t', str(ROOT.parent if args.subdirectory else ROOT)], cwd=ROOT, env=env, stdout=log, stderr=log, creationflags=flags)
            anon = Browser(base)
            for _ in range(50):
                try:
                    anon.request('index.php'); break
                except urllib.error.URLError:
                    time.sleep(.1)
            else:
                raise RuntimeError('Test HTTP server did not start')
            protected = [p.relative_to(ROOT).as_posix() for folder in ['student','tutor','admin','report'] for p in (ROOT/folder).glob('*.php')]
            for page in protected:
                status, _, headers = anon.request(page)
                check(status == 303 and headers.get('Location') == prefix+'/login.php', 'anonymous denied: '+page)
            for page in ['index.php','login.php','register.php','tutors.php','tutor-detail.php','why-us.php','help.php','chatbot.php']:
                # Phase 3C detail now requires an eligible persisted tutor ID.
                check(anon.request(page)[0] == (404 if page == 'tutor-detail.php' else 200), 'public accessible: '+page)
            check(anon.request('scripts/create_admin.php')[0] == 404, 'CLI provisioning refuses HTTP')

            registration_browser = Browser(base)
            form = registration('student','tmq_test_student')
            form['csrf_token'] = registration_browser.token('register.php')
            check(registration_browser.request('register.php',dict(form, csrf_token='bad'))[0] == 403, 'registration CSRF')
            for patch, label in [({'accountRoleType':'admin'},'public Admin rejected'), ({'accountUsername':'bad username'},'username format'), ({'studEmail':'bad'},'email format'), ({'confirmPassword':'different'},'password confirmation'), ({'accountPassword':'short','confirmPassword':'short'},'password length'), ({'studentGender':'invalid'},'gender'), ({'studentDateOfBirth':'1990-02-30'},'invalid date'), ({'studentDateOfBirth':'2020-01-01'},'underage'), ({'studEligibility':''},'eligibility')]:
                check(registration_browser.request('register.php',dict(form,**patch))[0] == 422, label)
            check(registration_browser.request('register.php',form)[0] == 303, 'Student registration')
            for patch,label in [({'studEmail':'unique@example.invalid'},'duplicate username'), ({'accountUsername':'tmq_other'},'duplicate email')]:
                status,body,_ = registration_browser.request('register.php',dict(form,**patch))
                check(status == 422 and 'already registered' in body, label)
            tutorform = registration('tutor','tmq_test_tutor'); tutorform['csrf_token']=registration_browser.token('register.php')
            check(registration_browser.request('register.php',tutorform)[0] == 303, 'Tutor registration without certificate')
            users = sql('SELECT user_id,username,role,password_hash FROM users ORDER BY user_id')
            check(len(users) == 2 and all(u['password_hash'] != password and u['password_hash'].startswith('$2y$') for u in users),'password hashes stored')
            studentid = users[0]['user_id']; tutorid = users[1]['user_id']
            check(len(sql('SELECT * FROM students WHERE user_id=?',[studentid])) == 1,'Student subtype')
            tutors = sql('SELECT * FROM tutors WHERE user_id=?',[tutorid])
            check(len(tutors)==1 and tutors[0]['profile_visibility']=='draft' and tutors[0]['displayed_certificate_id'] is None,'Tutor subtype draft/no approval')
            check(sql('SELECT COUNT(*) n FROM certificates')[0]['n']==0,'No certificate workflow')
            sql("CREATE TRIGGER test_subtype_failure BEFORE INSERT ON students FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Injected subtype failure'")
            rollbackform=registration('student','tmq_rollback');rollbackform['csrf_token']=registration_browser.token('register.php')
            check(registration_browser.request('register.php',rollbackform)[0]==503,'Subtype failure handled')
            check(sql("SELECT COUNT(*) n FROM users WHERE username='tmq_rollback'")[0]['n']==0,'No orphan user after subtype failure')
            sql('DROP TRIGGER test_subtype_failure')
            admin_data={'username':'tmq_test_admin','email':'tmq_test_admin@example.invalid','first_name':'Development','last_name':'Admin','password':password}
            admincmd=[args.php,str(ROOT/'scripts/create_admin.php'),'--init-settings']
            result=subprocess.run(admincmd,input=json.dumps(admin_data),capture_output=True,text=True,env=env,creationflags=flags)
            check(result.returncode==0 and password not in result.stdout+result.stderr,'CLI Admin provisioning')
            result=subprocess.run(admincmd,input=json.dumps(admin_data),capture_output=True,text=True,env=env,creationflags=flags)
            check(result.returncode!=0,'Duplicate CLI Admin rejected')
            check(sql('SELECT COUNT(*) n FROM admins')[0]['n']==1,'Exactly one Admin subtype')
            check(sql('SELECT COUNT(*) n FROM profile_field_settings')[0]['n']==22,'Real test Admin initializes 22 settings')

            def login(browser, username, secret=password, hint='admin'):
                token=browser.token('login.php')
                return browser.request('login.php',{'csrf_token':token,'accountUsername':username,'accountPassword':secret,'accountRoleType':hint})

            student=Browser(base); token=student.token('login.php'); before=student.sid()
            check(student.request('login.php',{'csrf_token':'bad','accountUsername':'tmq_test_student','accountPassword':password})[0]==403,'Login CSRF')
            check(login(student,'tmq_test_student','wrong-password9')[0]==422,'Wrong password rejected')
            status,_,headers=login(student,'tmq_test_student')
            check(status==303 and headers['Location']==prefix+'/student/student-dashboard.php','Student login ignores forged role')
            check(student.sid()!=before,'Session ID regenerated')
            check('HttpOnly' in headers.get('Set-Cookie','') and 'SameSite=Lax' in headers.get('Set-Cookie',''),'Session cookie flags')
            tutor=Browser(base);status,_,headers=login(tutor,'tmq_test_tutor')
            check(status==303 and headers['Location']==prefix+'/tutor/tutor-dashboard.php','Tutor login to incomplete account')
            check('certificate not submitted' in tutor.request('tutor/tutor-dashboard.php')[1],'No fake certificate approval')
            admin=Browser(base);status,_,headers=login(admin,'tmq_test_admin',hint='student')
            check(status==303 and headers['Location']==prefix+'/admin/admin-dashboard.php','Admin login derives DB role')
            for page in protected:
                expected='admin' if page.startswith(('admin/','report/')) else page.split('/')[0]
                for role,browser in [('student',student),('tutor',tutor),('admin',admin)]:
                    status,body,_=browser.request(page)
                    # The protected receipt stream requires an owned file ID; a bare URL is 404.
                    own_status=404 if page in ['student/student-payment-file.php','tutor/tutor-payment-file.php','admin/manage-payments-file.php','student/student-chat-file.php','tutor/tutor-chat-file.php'] else 200
                    check(status==(own_status if role==expected else 403),'role access '+role+' '+page)
                    check('Fatal error' not in body and 'Warning:' not in body,'clean response '+page)
            sql("UPDATE users SET account_status='inactive' WHERE user_id=?",[tutorid])
            check(tutor.request('tutor/tutor-dashboard.php')[0]==303,'Deactivation invalidates current session')
            check(login(Browser(base),'tmq_test_tutor')[0]==422,'Inactive login denied')
            sql("UPDATE users SET account_status='active' WHERE user_id=?",[tutorid])
            tutor=Browser(base);login(tutor,'tmq_test_tutor')

            second_student=Browser(base);login(second_student,'tmq_test_student')
            for role,browser,page in [('student',student,'student/student-profile-edit.php'),('tutor',tutor,'tutor/tutor-account-edit.php'),('admin',admin,'admin/admin-profile.php')]:
                token=browser.token(page)
                change={'csrf_token':token,'currentPassword':password,'newPassword':new_password,'confirmNewPassword':new_password,'user_id':'99999'}
                check(browser.request(page,dict(change,csrf_token='bad'))[0]==403,role+' password CSRF')
                check(browser.request(page,dict(change,currentPassword='wrong-password9'))[0]==422,role+' wrong current password')
                if role=='student':
                    sql("UPDATE profile_field_settings SET is_editable=0 WHERE role='student' AND field_key='password'")
                    check(browser.request(page,change)[0]==422,'Password setting respected')
                    sql("UPDATE profile_field_settings SET is_editable=1 WHERE role='student' AND field_key='password'")
                check(browser.request(page,change)[0]==303,role+' password changed')
                check('Password updated' in browser.request(page)[1],role+' success message')
                check(login(Browser(base),'tmq_test_'+role,password)[0]==422,role+' old password denied')
                check(login(Browser(base),'tmq_test_'+role,new_password)[0]==303,role+' new password works')
            check(second_student.request('student/student-dashboard.php')[0]==303,'Other session invalidated after password change')
            token=student.token('student/student-dashboard.php')
            check(student.request('logout.php')[0]==405,'GET logout denied')
            check(student.request('logout.php',{'csrf_token':'bad'})[0]==403,'Logout CSRF')
            check(student.request('student/student-dashboard.php')[0]==200,'Failed logout preserves login')
            check(student.request('logout.php',{'csrf_token':token})[0]==303,'POST logout succeeds')
            check(student.sid() is None,'Logout clears cookie')
            check(student.request('student/student-dashboard.php')[0]==303,'Logout destroys authentication')
            for session in directory.glob('sess_*'):
                content=session.read_text(errors='replace')
                check(password not in content and new_password not in content and 'password_hash' not in content,'No secret in session')
            check(sql('CHECK TABLE users, students, tutors, admins')[0]['Msg_text']=='OK','Account tables check OK')
            print(f'PASS: {CHECKS} assertions; all requested auth flows; {len(protected)} protected pages across all roles.')
        finally:
            if server:
                server.terminate();server.wait(timeout=15);log.close()
            if created:
                php('drop')
                print('CLEANUP: dropped only task-created '+name+'; all test accounts removed.')


if __name__ == '__main__':
    main()
