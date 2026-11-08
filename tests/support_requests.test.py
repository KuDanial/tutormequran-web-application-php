"""Support request integration tests; disposable database and uploads only."""
import concurrent.futures
import importlib.util
import json
import os
from pathlib import Path
import secrets
import socket
import subprocess
import sys
import tempfile
import time
import urllib.error
from datetime import datetime, timedelta, timezone

ROOT = Path(__file__).resolve().parents[1]
sys.dont_write_bytecode = True
spec = importlib.util.spec_from_file_location('auth_tests', ROOT/'tests/authentication.test.py')
auth = importlib.util.module_from_spec(spec)
spec.loader.exec_module(auth)
profile_spec = importlib.util.spec_from_file_location('profile_tests', ROOT/'tests/profile_verification.test.py')
profile = importlib.util.module_from_spec(profile_spec)
profile_spec.loader.exec_module(profile)
import re
import copy
import struct
import zlib
check = auth.check
PHP = 'C:/xampp/php/php.exe'


def main():
    name = 'tutormequran_auth_validation_' + secrets.token_hex(8)
    env = dict(os.environ, TMQ_DB_NAME=name)
    flags = subprocess.CREATE_NO_WINDOW if os.name == 'nt' else 0
    password = 'Booking8-' + secrets.token_hex(12)
    created = False
    server = None
    with tempfile.TemporaryDirectory(prefix='tmq-support-tests-') as temp:
        temp = Path(temp)
        env['TMQ_UPLOAD_ROOT']=str(temp/'uploads')
        other_server=None; other_log=None
        bridge = temp/'bridge.php'
        bridge.write_text(auth.BRIDGE, encoding='utf-8')
        def php(action, data=None):
            r = subprocess.run([PHP,str(bridge),str(ROOT),action],env=env,input=json.dumps(data or {}),capture_output=True,text=True,encoding='utf-8',creationflags=flags)
            if r.returncode: raise RuntimeError(r.stdout+r.stderr)
            return json.loads(r.stdout)
        def sql(query, params=None): return php('query',{'sql':query,'params':params or []})
        try:
            php('create'); created = True
            with socket.socket() as sock:
                sock.bind(('127.0.0.1',0)); port=sock.getsockname()[1]
            log = open(temp/'server.log','w')
            server = subprocess.Popen([PHP,'-d','upload_max_filesize=6M','-d','post_max_size=8M','-d','display_errors=0','-d','session.save_path='+str(temp),'-S',f'127.0.0.1:{port}','-t',str(ROOT)],env=env,stdout=log,stderr=log,creationflags=flags)
            base = f'http://127.0.0.1:{port}/'
            anon = auth.Browser(base)
            for _ in range(50):
                try: anon.request('index.php'); break
                except urllib.error.URLError: time.sleep(.1)
            def login(browser, username):
                check(browser.request('login.php',{'csrf_token':browser.token('login.php'),'accountUsername':username,'accountPassword':password})[0]==303,'fixture login')
            def account(role, username):
                browser = auth.Browser(base)
                prefix = 'stud' if role=='student' else 'tutor'
                data = {'csrf_token':browser.token('register.php'),'accountRoleType':role,'accountUsername':username,'accountPassword':password,'confirmPassword':password,prefix+'FName':'Availability',prefix+'LName':'Test',prefix+'ICNum':'900101-01-1234',prefix+'Phone':'0123456789',prefix+'Email':username+'@example.invalid',prefix+'Address':'Test address',prefix+'Eligibility':'on',role+'Gender':'Male',role+'DateOfBirth':'1990-01-01'}
                check(browser.request('register.php',data)[0]==303,'fixture registration')
                login(browser,username)
                return browser,sql('SELECT user_id FROM users WHERE username=?',[username])[0]['user_id']
            tutor, tutor_user = account('tutor','bc_tutor')
            tutor2, tutor2_user = account('tutor','bc_tutor2')
            student, user = account('student','bc_student')
            student2, user2 = account('student','bc_student2')
            r=subprocess.run([PHP,str(ROOT/'scripts/create_admin.php')],input=json.dumps({'username':'bc_admin','email':'bc_admin@example.invalid','first_name':'Test','last_name':'Admin','password':password}),env=env,capture_output=True,text=True,encoding='utf-8',creationflags=flags)
            check(r.returncode==0,'fixture Admin')
            admin=auth.Browser(base); login(admin,'bc_admin')
            aid=sql('SELECT admin_id FROM admins')[0]['admin_id']
            tids=[]
            for uid in (tutor_user,tutor2_user):
                tid=sql('SELECT tutor_id FROM tutors WHERE user_id=?',[uid])[0]['tutor_id']; tids.append(tid)
            tid,tid2=tids
            student_id=sql('SELECT student_id FROM students WHERE user_id=?',[user])[0]['student_id']
            student2_id=sql('SELECT student_id FROM students WHERE user_id=?',[user2])[0]['student_id']
            tokens={student:student.token('student/student-profile-edit.php'),student2:student2.token('student/student-profile-edit.php'),
                    tutor:tutor.token('tutor/tutor-profile-edit.php'),tutor2:tutor2.token('tutor/tutor-profile-edit.php'),admin:admin.token('admin/admin-profile.php')}
            number=0
            def fixture(owner=student_id,status='completed',student_done=True,tutor_done=True,tutor_id=tid):
                nonlocal number
                number+=1
                start=(datetime.now(timezone.utc)-timedelta(days=number+3)).replace(hour=0,minute=0,second=0,microsecond=0)
                times=[start.strftime('%Y-%m-%d %H:%M:%S'),(start+timedelta(hours=1)).strftime('%Y-%m-%d %H:%M:%S')]
                sql("INSERT INTO availability_slots (tutor_id,starts_at,ends_at,mode) VALUES (?,?,?,'online')",[tutor_id]+times)
                slot=sql('SELECT slot_id FROM availability_slots WHERE tutor_id=? AND starts_at=?',[tutor_id,times[0]])[0]['slot_id']
                sql("INSERT INTO bookings (student_id,tutor_id,slot_id,starts_at,ends_at,class_mode,hourly_rate_snapshot,expected_amount,status,rejection_reason,student_arranged_at,tutor_arranged_at,student_completed_at,tutor_completed_at) VALUES (?,?,?,?,?,'online',40,40,?,'Fixture',UTC_TIMESTAMP(),UTC_TIMESTAMP(),?,?)",
                    [owner,tutor_id,slot]+times+[status,times[1] if student_done else None,times[1] if tutor_done else None])
                return sql('SELECT booking_id FROM bookings WHERE slot_id=?',[slot])[0]['booking_id']
            def route(role='student'): return role+'/'+role+'-support-request.php'
            def form(browser=student, role='student'):
                status, body, _ = browser.request(route(role))
                if status != 200: raise AssertionError((status,body))
                return {'csrf_token':tokens[browser], 'support_action':'create',
                        'support_form':re.search(r'name="support_form" value="([a-f0-9]+)"',body)[1],
                        'category':'other','description':'Ordinary support request'}
            def submit(browser=student, route_role='student', **extra):
                data=form(browser,route_role); data.update(extra)
                return browser.request(route(route_role),data)
            def records(): return sql('SELECT * FROM support_requests ORDER BY request_id')
            def files(): return sorted(str(p.relative_to(temp/'uploads')) for p in (temp/'uploads').rglob('*') if p.is_file())
            def snapshot():
                tables=sql('SHOW TABLES')
                return {table:sql('SELECT * FROM `'+table+'`') for row in tables for table in row.values() if table!='support_requests'}
            owned=fixture(); foreign=fixture(owner=student2_id,tutor_id=tid2)
            baseline=snapshot()
            check(anon.request(route())[0]==303 and anon.request(route(),{})[0]==303,'guest intake excluded')
            check(admin.request(route())[0]==403 and admin.request(route(),{})[0]==403,'Admin cannot submit as Student')
            check(tutor.request(route())[0]==403 and student.request(route('tutor'))[0]==403,'role guards enforced')
            check(student.request(route()+'?support_action=create&description=get')[0]==200 and not records(),'GET cannot create')
            try:r=student.opener.open(auth.urllib.request.Request(base+route(),b'',method='PUT'),timeout=15)
            except urllib.error.HTTPError as e:r=e
            check(r.code==405 and not records(),'other HTTP methods cannot create');r.close()
            check(submit(csrf_token='bad')[0]==403 and not records(),'CSRF required')
            check(student.request(route(),{'support_action':'create'})[0]==403,'missing CSRF rejected')
            for field,value in [('description',''),('description',' \r\n\t '),('description','x'*65536),('description','😀'*16384),('category','unknown'),('category','a'*25),('category',''),('booking','../1'),('booking','4294967296'),('booking','1e0')]:
                check(submit(**{field:value})[0]==422 and not records(),'invalid field '+field+': '+repr(value[:30]))
            for field in ['category','description','booking']:
                data=form(); data.pop(field,None); data[field+'[]']='bad'
                check(student.request(route(),data)[0]==422,'array rejected: '+field)
            for field in ['category','description']:
                data=form(); data.pop(field)
                check(student.request(route(),data)[0]==422,'required field: '+field)
            data=form(); data.pop('description')
            payload=auth.urllib.parse.urlencode(data).encode()+b'&description=%FF'
            try:r=student.opener.open(base+route(),payload,timeout=15)
            except urllib.error.HTTPError as e:r=e
            check(r.code==422,'invalid UTF-8 rejected');r.close()
            check(submit(booking=str(foreign),user_id=user2,owner=user2,role='admin')[0]==404,'forged owner cannot authorize foreign booking')
            raw='  العربية 😀\n<script>alert(1)</script>  '
            result=submit(category=' billing ',description=raw,booking=str(owned),user_id=user2,requester_user_id=user2,student_id=student2_id,tutor_id=tid2,role='admin',owner=user2,created_by=user2,status='resolved',resolution_note='forged',handled_by_admin_id=aid,created_at='2000-01-01',redirect='https://example.invalid')
            record=records()[0]; rid=record['request_id']
            check(result[0]==303 and result[2]['Location'].endswith('/'+route()+'?id='+str(rid)),'successful submission and fixed redirect')
            check(record['requester_user_id']==user and record['booking_id']==owned and record['category']=='billing' and record['description']==raw.strip(),'session ownership, owned booking, trim and UTF-8 preserved')
            check(record['status']=='open' and record['handled_by_admin_id'] is None and record['resolution_note'] is None and record['resolved_at'] is None and record['created_at'][:4]!='2000','status and timestamps server-derived')
            page=route()+'?id='+str(rid)
            body=student.request(page)[1]
            check('&lt;script&gt;alert(1)&lt;/script&gt;' in body and '<script>alert(1)</script>' not in body and 'العربية 😀' in body and '>Open<' in body,'own persisted history escapes stored XSS and shows actual status')
            check(student2.request(page)[0]==404 and student2.request(page+'&user_id='+str(user))[0]==404,'foreign Student detail denied including forged owner')
            check(tutor.request(route('tutor')+'?id='+str(rid))[0]==404,'booking counterpart cannot read request')
            check('alert(1)' not in student2.request(route())[1],'foreign request omitted from history')
            for action in ['resolve','update','delete']:
                check(submit(support_action=action,request_id=rid,status='resolved')[0]==422 and records()==[record],'no user mutation: '+action)
            check(submit(support_action='update',status='invented')[0]==422,'arbitrary status action rejected')
            sql("UPDATE support_requests SET status='resolved',resolution_note='<script>resolution</script>',handled_by_admin_id=?,resolved_at=UTC_TIMESTAMP() WHERE request_id=?",[aid,rid])
            body=student.request(page)[1]
            check('>Resolved<' in body and '&lt;script&gt;resolution&lt;/script&gt;' in body and '<script>resolution</script>' not in body,'approved resolved record and escaped resolution display')
            check(submit(description='a'*65535)[0]==303 and len(records()[-1]['description'])==65535,'actual TEXT byte boundary accepted without truncation')
            check(submit(tutor,'tutor',category='attendance',booking=str(owned))[0]==303 and records()[-1]['requester_user_id']==tutor_user,'Tutor can submit with own booking')
            check(submit(tutor,'tutor',booking=str(foreign))[0]==404,'Tutor foreign booking denied')
            for mime,filename,content in [('image/jpeg','screen.jpeg',profile.JPEG),('image/png','screen.png',profile.PNG)]:
                result=profile.upload(student,route(),form(),'photo',filename,content,'application/octet-stream')
                record=records()[-1]; key=record['photo_path']; attachment='support-file.php?id='+str(record['request_id'])
                check(result[0]==303 and re.fullmatch(r'support/[a-f0-9]{40}\.(jpg|png)',key) is not None and (temp/'uploads'/key).read_bytes()==content,'content-validated randomized '+mime+' upload')
                check(not (ROOT/key).exists() and key not in student.request(route())[1] and str(temp) not in student.request(route())[1],'protected storage key and filesystem path not exposed')
                for browser in [student,admin]:
                    response=browser.opener.open(base+attachment,timeout=15)
                    check(response.status==200 and response.read()==content and response.headers['Content-Type']==mime and response.headers['X-Content-Type-Options']=='nosniff' and 'attachment;' in response.headers['Content-Disposition'],'owner/Admin protected download')
                    response.close()
                for browser in [student2,tutor,tutor2]:
                    check(browser.request(attachment)[0]==404,'foreign attachment denied')
                check(anon.request(attachment)[0]==303,'anonymous attachment denied')
                check(student.request(attachment,{})[0]==405,'download GET only')
                check(anon.request(key)[0]==404,'raw URL unavailable')
            padding=b'Note\0'+b'a'*(5242880-len(profile.PNG)-17)
            chunk=b'tEXt'+padding
            exact_png=profile.PNG[:-12]+struct.pack('>I',len(padding))+chunk+struct.pack('>I',zlib.crc32(chunk))+profile.PNG[-12:]
            check(len(exact_png)==5242880 and profile.upload(student,route(),form(),'photo','maximum.png',exact_png,'image/png')[0]==303,'valid image at exact 5 MB boundary accepted')
            before=records(); before_files=files()
            for filename,content,mime in [('large.png',profile.PNG+b'x'*5242880,'image/png'),('fake.jpg',b'<html>web</html>','image/jpeg'),('mismatch.jpg',profile.PNG,'image/jpeg'),('evil.php',profile.PNG,'image/png'),('../escape.png',profile.PNG,'image/png'),('polyglot.png',profile.PNG[:-8]+b'<?php echo 1;?>'+profile.PNG[-8:],'image/png'),('web.png',profile.PNG[:-8]+b'<script>alert(1)</script>'+profile.PNG[-8:],'image/png'),('bad.png',profile.PNG[:-8],'image/png')]:
                check(profile.upload(student,route(),form(),'photo',filename,content,mime)[0]==422 and records()==before and files()==before_files,'unsafe upload rejected with no partial state: '+filename)
            check(profile.upload(student,route(),dict(form(),booking=str(foreign)),'photo','test.png',profile.PNG,'image/png')[0]==404 and files()==before_files,'ownership failure cleans staged upload')
            sql("CREATE TRIGGER support_test_failure BEFORE INSERT ON support_requests FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Private database diagnostic'")
            try:
                status,body,_=profile.upload(student,route(),form(),'photo','test.png',profile.PNG,'image/png')
                check(status==503 and 'SQLSTATE' not in body and 'Private database diagnostic' not in body and files()==before_files and records()==before,'database failure rolls back and removes staged file')
            finally:sql('DROP TRIGGER support_test_failure')
            support_dir=temp/'uploads'/'support'; moved=temp/'uploads'/'support-saved'
            support_dir.rename(moved);support_dir.write_text('block upload directory')
            try:
                status,body,_=profile.upload(student,route(),form(),'photo','test.png',profile.PNG,'image/png')
                check(status==503 and records()==before and str(temp) not in body,'storage failure creates no DB record and no exposed path')
            finally:support_dir.unlink();moved.rename(support_dir)
            # Independent HTTP workers share the same authenticated session/form.
            with socket.socket() as sock:sock.bind(('127.0.0.1',0));other_port=sock.getsockname()[1]
            other_log=open(temp/'other-server.log','w')
            other_server=subprocess.Popen([PHP,'-d','display_errors=0','-d','session.save_path='+str(temp),'-S',f'127.0.0.1:{other_port}','-t',str(ROOT)],env=env,stdout=other_log,stderr=other_log,creationflags=flags)
            second=auth.Browser(f'http://127.0.0.1:{other_port}/')
            for cookie in student.cookies:second.cookies.set_cookie(copy.copy(cookie))
            for _ in range(50):
                try:second.request('index.php');break
                except urllib.error.URLError:time.sleep(.1)
            data=form();count=len(records())
            sql('CREATE TRIGGER support_test_wait BEFORE INSERT ON support_requests FOR EACH ROW SET @support_wait=SLEEP(1)')
            try:
                with concurrent.futures.ThreadPoolExecutor(max_workers=2) as pool:
                    futures=[pool.submit(browser.request,route(),data) for browser in [student,second]]
                    results=[f.result(timeout=20) for f in futures]
                check(sorted(r[0] for r in results)==[303,409] and len(records())==count+1,'simultaneous duplicate form POST creates one intact request')
            finally:sql('DROP TRIGGER support_test_wait')
            check(student.request(route(),data)[0]==409 and len(records())==count+1,'stale form replay controlled')
            sql("UPDATE users SET account_status='inactive' WHERE user_id=?",[user2])
            check(student2.request(route())[0]==303 and student2.request(route(),data)[0]==303,'inactive account cannot view or submit')
            old_updated=next(u['updated_at'] for u in baseline['users'] if u['user_id']==user2)
            sql("UPDATE users SET account_status='active',updated_at=? WHERE user_id=?",[old_updated,user2])
            check(snapshot()==baseline,'support changes no other table: bookings, availability, payments, evidence, certificates, feedback, chat or notifications')
            # A competing owner change wins before the INSERT authorization read.
            race_booking=fixture();ready=temp/'ownership-ready'
            lock_script=temp/'ownership-race.php'
            lock_script.write_text("<?php require $argv[1].'/includes/db.php'; $db=tmq_db(); $db->beginTransaction(); $q=$db->prepare('UPDATE bookings SET student_id=? WHERE booking_id=?'); $q->execute([$argv[2],$argv[3]]); file_put_contents($argv[4],'ready'); usleep(1500000); $db->commit();",encoding='utf-8')
            data=dict(form(),booking=str(race_booking));count=len(records());before_files=files()
            locker=subprocess.Popen([PHP,str(lock_script),str(ROOT),str(student2_id),str(race_booking),str(ready)],env=env,stdout=subprocess.PIPE,stderr=subprocess.PIPE,creationflags=flags)
            try:
                for _ in range(100):
                    if ready.exists():break
                    time.sleep(.01)
                if not ready.exists():raise AssertionError('ownership race fixture did not acquire row')
                result=profile.upload(student,route(),data,'photo','race.png',profile.PNG,'image/png')
                locker.communicate(timeout=10)
                check(locker.returncode==0 and result[0]==404 and len(records())==count and files()==before_files,'concurrent ownership change cannot authorize request and staged photo is cleaned')
            finally:
                if locker.poll() is None:locker.terminate();locker.wait(timeout=10)
            for i in range(55):sql("INSERT INTO support_requests (requester_user_id,category,description,created_at) VALUES (?,'other',?,'2030-01-01 00:00:00')",[user,'history '+str(i)])
            expected=[r['request_id'] for r in sql('SELECT request_id FROM support_requests WHERE requester_user_id=? ORDER BY created_at DESC,request_id DESC',[user])]
            bodies=[student.request(route()+'?page='+str(p))[1] for p in [1,2]]
            found=[[int(i) for i in re.findall(r'data-request-id="(\d+)"',body)] for body in bodies]
            check(found[0]==expected[:50] and found[1]==expected[50:] and 'Older requests' in bodies[0],'bounded 50-row deterministic history and pagination')
            check(student.request(route()+'?page[]=1')[0]==422,'array pagination rejected')
            check(student.request(page+'&page=999')[0]==200,'detail lookup independent of pagination')
            print(f'PASS: {auth.CHECKS} support assertions; authorization, validation, history, uploads, concurrency and side effects.')
        finally:
            if other_server:other_server.terminate();other_server.wait(timeout=15)
            if other_log:other_log.close()
            if server:server.terminate();server.wait(timeout=15);log.close()
            if created:php('drop');print('CLEANUP: dropped only task-created '+name+'; temporary files removed.')


if __name__=='__main__':main()
