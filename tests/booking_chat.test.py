"""Booking-linked private chat HTTP/MariaDB tests; disposable database only."""
import concurrent.futures
import hashlib
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
check = auth.check
PHP = 'C:/xampp/php/php.exe'
spec = importlib.util.spec_from_file_location('profile_tests', ROOT/'tests/profile_verification.test.py')
profiles = importlib.util.module_from_spec(spec)
spec.loader.exec_module(profiles)


def main():
    name = 'tutormequran_auth_validation_' + secrets.token_hex(8)
    env = dict(os.environ, TMQ_DB_NAME=name)
    flags = subprocess.CREATE_NO_WINDOW if os.name == 'nt' else 0
    password = 'Booking8-' + secrets.token_hex(12)
    created = False
    server = None
    with tempfile.TemporaryDirectory(prefix='tmq-chat-tests-') as temp:
        temp = Path(temp)
        env['TMQ_UPLOAD_ROOT']=str(temp/'uploads')
        other_server=None; other_log=None
        bridge = temp/'bridge.php'
        bridge.write_text(auth.BRIDGE, encoding='utf-8')
        def php(action, data=None):
            r = subprocess.run([PHP,str(bridge),str(ROOT),action],env=env,input=json.dumps(data or {}),capture_output=True,text=True,creationflags=flags)
            if r.returncode: raise RuntimeError(r.stdout+r.stderr)
            return json.loads(r.stdout)
        def sql(query, params=None): return php('query',{'sql':query,'params':params or []})
        try:
            php('create'); created = True
            with socket.socket() as sock:
                sock.bind(('127.0.0.1',0)); port=sock.getsockname()[1]
            log = open(temp/'server.log','w')
            server = subprocess.Popen([PHP,'-d','upload_max_filesize=11M','-d','post_max_size=56M','-d','display_errors=0','-d','session.save_path='+str(temp),'-S',f'127.0.0.1:{port}','-t',str(ROOT)],env=env,stdout=log,stderr=log,creationflags=flags)
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
            r=subprocess.run([PHP,str(ROOT/'scripts/create_admin.php')],input=json.dumps({'username':'bc_admin','email':'bc_admin@example.invalid','first_name':'Test','last_name':'Admin','password':password}),env=env,capture_output=True,text=True,creationflags=flags)
            check(r.returncode==0,'fixture Admin')
            admin=auth.Browser(base); login(admin,'bc_admin')
            aid=sql('SELECT admin_id FROM admins')[0]['admin_id']
            tids=[]
            for uid in (tutor_user,tutor2_user):
                tid=sql('SELECT tutor_id FROM tutors WHERE user_id=?',[uid])[0]['tutor_id']; tids.append(tid)
                sql("UPDATE users SET profile_picture_path='profiles/test.jpg' WHERE user_id=?",[uid])
                sql("INSERT INTO certificates (tutor_id,submission_purpose,certificate_name,qualification_level,certificate_year,file_path,original_filename,mime_type,file_size_bytes,status,reviewed_by_admin_id,reviewed_at) VALUES (?,'initial','Test qualification','ijazah',2024,'certificates/test.pdf','test.pdf','application/pdf',100,'approved',?,UTC_TIMESTAMP())",[tid,aid])
                cid=sql('SELECT certificate_id FROM certificates WHERE tutor_id=?',[tid])[0]['certificate_id']
                sql("UPDATE tutors SET displayed_certificate_id=?,profile_visibility='public',teaching_state='Selangor',teaching_area='Test area',teaching_mode='both',hourly_rate='40.25',bio='Test teaching' WHERE tutor_id=?",[cid,tid])
                sql('INSERT INTO tutor_languages VALUES (?,1)',[tid]);sql('INSERT INTO tutor_lesson_focuses VALUES (?,1)',[tid])
            tid,tid2=tids
            student_id=sql('SELECT student_id FROM students WHERE user_id=?',[user])[0]['student_id']
            student2_id=sql('SELECT student_id FROM students WHERE user_id=?',[user2])[0]['student_id']
            upload=profiles.upload;PNG=profiles.PNG;JPEG=profiles.JPEG;PDF=profiles.PDF
            token=student.token('student/student-profile-edit.php');token2=student2.token('student/student-profile-edit.php')
            tokens={student:token,student2:token2}
            fixture_number=0
            def fixture(owner=student_id,status='completed'):
                nonlocal fixture_number
                fixture_number+=1
                start=(datetime.now(timezone.utc)-timedelta(days=fixture_number+3)).replace(hour=0,minute=0,second=0,microsecond=0);end=start+timedelta(hours=1)
                times=[start.strftime('%Y-%m-%d %H:%M:%S'),end.strftime('%Y-%m-%d %H:%M:%S')]
                sql("INSERT INTO availability_slots (tutor_id,starts_at,ends_at,mode) VALUES (?,?,?,'online')",[tid]+times)
                slot_id=sql('SELECT slot_id FROM availability_slots WHERE tutor_id=? AND starts_at=?',[tid,times[0]])[0]['slot_id']
                sql("INSERT INTO bookings (student_id,tutor_id,slot_id,starts_at,ends_at,class_mode,hourly_rate_snapshot,expected_amount,status,rejection_reason,student_arranged_at,tutor_arranged_at,student_completed_at,tutor_completed_at) VALUES (?,?,?,?,?,'online',40,40,?,'Fixture',UTC_TIMESTAMP(),UTC_TIMESTAMP(),UTC_TIMESTAMP(),UTC_TIMESTAMP())",[owner,tid,slot_id]+times+[status])
                return sql('SELECT booking_id FROM bookings WHERE slot_id=?',[slot_id])[0]['booking_id']
            def row(bid):return sql('SELECT * FROM bookings WHERE booking_id=?',[bid])[0]
            import io, zipfile, struct, re
            roles={student:'student',student2:'student',tutor:'tutor',tutor2:'tutor',admin:'student',anon:'student'}
            tokens[tutor]=tutor.token('tutor/tutor-account-edit.php')
            tokens[tutor2]=tutor2.token('tutor/tutor-account-edit.php')
            tokens[admin]=admin.token('admin/admin-profile.php');tokens[anon]='invalid'
            def route(bid,browser=student):return roles[browser]+'/'+roles[browser]+'-chat.php?id='+str(bid)
            def messages(bid):return sql('SELECT * FROM messages WHERE booking_id=? ORDER BY sent_at,message_id',[bid])
            def attachments(bid):return sql('SELECT a.* FROM message_attachments a JOIN messages m ON m.message_id=a.message_id WHERE m.booking_id=? ORDER BY attachment_id',[bid])
            def send(bid,browser=student,**extra):
                data={'csrf_token':tokens[browser],'chat_action':'send','message_type':'text','body':'Assalamualaikum','sender_user_id':user2,'user_id':user2,'role':'admin'};data.update(extra)
                return browser.request(route(bid,browser),data)
            def filesend(bid,files,browser=student,**extra):
                boundary='tmq'+secrets.token_hex(12);body=bytearray()
                data={'csrf_token':tokens[browser],'chat_action':'send','message_type':'attachment'};data.update(extra)
                for key,value in data.items():body.extend(f'--{boundary}\r\nContent-Disposition: form-data; name="{key}"\r\n\r\n{value}\r\n'.encode())
                for filename,content,mime in files:
                    body.extend(f'--{boundary}\r\nContent-Disposition: form-data; name="attachments[]"; filename="{filename}"\r\nContent-Type: {mime}\r\n\r\n'.encode());body.extend(content);body.extend(b'\r\n')
                body.extend(f'--{boundary}--\r\n'.encode())
                request=auth.urllib.request.Request(browser.base+route(bid,browser),bytes(body),{'Content-Type':'multipart/form-data; boundary='+boundary})
                try:r=browser.opener.open(request,timeout=30)
                except urllib.error.HTTPError as e:r=e
                return r.code,r.read().decode('utf-8','replace'),r.headers
            def stored():return sorted((str(p.relative_to(temp/'uploads')),hashlib.sha256(p.read_bytes()).hexdigest()) for p in (temp/'uploads').rglob('*') if p.is_file())
            def download(browser,aid):
                path=roles[browser]+'/'+roles[browser]+'-chat-file.php?id='+str(aid)
                try:r=browser.opener.open(browser.base+path,timeout=15)
                except urllib.error.HTTPError as e:r=e
                return r.code,r.read(),r.headers
            bid=fixture(status='requested')
            for browser in [student,tutor]:check(browser.request(route(bid,browser))[0]==200,'own requested chat '+roles[browser])
            for browser in [student2,tutor2,admin,anon]:
                expected=404 if browser in [student2,tutor2] else 403 if browser==admin else 303
                check(browser.request(route(bid,browser))[0]==expected and send(bid,browser)[0]==expected,'private read/send role and relationship boundary')
            for bad in ['9999999','0','abc','TMQ-'+str(bid)]:check(student.request(route(bad))[0]==404,'booking ID is scoped and validated')
            check(student.request('student/student-chat.php?tutor_id='+str(tid))[0]==200 and send('',body='No booking')[0]==404,'no general direct messaging')
            check(send(bid,csrf_token='bad')[0]==403 and not messages(bid),'CSRF prevents mutation')
            for status in ['requested','accepted','completed']:
                b=fixture(status=status);before=row(b)
                for browser in [student,tutor]:check(send(b,browser)[0]==303,'writable '+status+' '+roles[browser])
                check([m['sender_user_id'] for m in messages(b)]==[user,tutor_user] and row(b)==before,'session sender and unchanged booking')
            for status in ['rejected','cancelled','closed']:
                b=fixture(status='requested');send(b,body='historical '+status);sql('UPDATE bookings SET status=? WHERE booking_id=?',[status,b]);before=messages(b)
                for browser in [student,tutor]:
                    code,body,_=browser.request(route(b,browser))
                    check(code==200 and 'historical '+status in body and 'read-only' in body and 'name="chat_action"' not in body,'terminal history '+status)
                    check(send(b,browser)[0]==409 and messages(b)==before,'terminal send denied '+status)
                    detail=browser.request(roles[browser]+'/'+roles[browser]+'-booking-view.php?id='+str(b))[1]
                    check(roles[browser]+'-chat.php?id='+str(b) in detail,'terminal navigation '+status)
            for text in ['', ' \r\n\t', '\u2003', 'x'*2001, 'é'*2001, '\0bad']:
                before=messages(bid);check(send(bid,body=text)[0]==422 and messages(bid)==before,'invalid text rejected '+repr(text[:20]))
            for text in ['  Salam\nBaris kedua  ','السلام عليكم','é'*2000,'<script>alert(1)</script>']:
                check(send(bid,body=text)[0]==303 and messages(bid)[-1]['body']==text.strip(),'plain UTF-8 text retained')
            invalid={'csrf_token':token,'chat_action':'send','message_type':'text','body':'bad'}
            payload=auth.urllib.parse.urlencode(invalid).encode().replace(b'body=bad',b'body=%FF')
            try:r=student.opener.open(student.base+route(bid),payload)
            except urllib.error.HTTPError as e:r=e
            check(r.code==422,'invalid UTF-8 rejected');r.read()
            body=student.request(route(bid))[1]
            check('&lt;script&gt;alert(1)&lt;/script&gt;' in body and '<script>alert(1)</script>' not in body and 'Salam\nBaris kedua' in body,'stored XSS escaped; line breaks retained')
            before=messages(bid)
            for action in ['edit','delete','unsend']:check(send(bid,chat_action=action,message_id=before[0]['message_id'])[0]==422 and messages(bid)==before,'immutable '+action)
            sql("INSERT INTO payments (booking_id,recipient_name_snapshot) VALUES (?,'Test tutor')",[bid])
            side_before=(row(bid),sql('SELECT * FROM availability_slots'),sql('SELECT * FROM payments'))
            for location in [dict(location_label='<script>venue</script>'),dict(latitude='3.123456',longitude='101.123456'),dict(latitude='-90',longitude='+180',location_label='Boundary')]:
                check(send(bid,message_type='location',body='',**location)[0]==303,'valid location or label-only')
            for location in [dict(latitude='90.000001',longitude='0'),dict(latitude='0',longitude='-180.000001'),dict(latitude='3'),dict(longitude='3'),dict(latitude='1e1',longitude='3'),dict(latitude='NaN',longitude='3'),dict(latitude='1.0000001',longitude='3'),dict(location_label='x'*501),dict(location_label='\u2003'),{}]:
                before=messages(bid);check(send(bid,message_type='location',body='',**location)[0]==422 and messages(bid)==before,'invalid location rejected')
            check(side_before==(row(bid),sql('SELECT * FROM availability_slots'),sql('SELECT * FROM payments')),'location leaves arrangement, availability, payment and evidence revision intact')
            check('&lt;script&gt;venue&lt;/script&gt;' in tutor.request(route(bid,tutor))[1],'location label escaped')
            def office(ext,unsafe=False):
                main,kind={'docx':('word/document.xml','wordprocessingml.document'),'xlsx':('xl/workbook.xml','spreadsheetml.sheet'),'pptx':('ppt/presentation.xml','presentationml.presentation')}[ext]
                output=io.BytesIO()
                with zipfile.ZipFile(output,'w',zipfile.ZIP_DEFLATED) as z:
                    z.writestr('[Content_Types].xml',f'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Override PartName="/{main}" ContentType="application/vnd.openxmlformats-officedocument.{kind}.main+xml"/></Types>')
                    z.writestr(main,'<document/>')
                    if unsafe:z.writestr('word/vbaProject.bin',b'unsafe macro')
                return output.getvalue()
            def box(kind,data):return struct.pack('>I',len(data)+8)+kind+data
            mp4=box(b'ftyp',b'isom\0\0\0\0isommp42')+box(b'moov',b'')+box(b'mdat',b'\0'*32)
            webm=b'\x1a\x45\xdf\xa3\x9f\x42\x86\x81\x01\x42\xf7\x81\x01\x42\xf2\x81\x04\x42\xf3\x81\x08\x42\x82\x84webm\x42\x87\x81\x02\x42\x85\x81\x02\x18\x53\x80\x67\xff\x16\x54\xae\x6b\x80\x1f\x43\xb6\x75\x80'
            allowed=[('a.pdf',PDF,'application/pdf'),('a.txt',b'Salam\nText file','text/plain'),('a.jpg',JPEG,'image/jpeg'),('a.png',PNG,'image/png'),('a.mp4',mp4,'video/mp4'),('a.webm',webm,'video/webm')]+[(f'a.{ext}',office(ext),'application/octet-stream') for ext in ['docx','xlsx','pptx']]
            for file in allowed:
                result=filesend(bid,[file]);check(result[0]==303,'allowed attachment '+file[0]+': '+str(result[0]))
                a=attachments(bid)[-1];path=temp/'uploads'/a['file_path']
                check(path.read_bytes()==file[1] and not path.is_relative_to(ROOT) and a['file_size_bytes']==len(file[1]),'protected metadata and file storage')
                for browser in [student,tutor]:
                    code,content,headers=download(browser,a['attachment_id'])
                    check(code==200 and content==file[1] and headers['X-Content-Type-Options']=='nosniff' and 'attachment' in headers['Content-Disposition'] and 'no-store' in headers['Cache-Control'],'participant secure download')
                check(a['file_path'] not in student.request(route(bid))[1] and str(temp) not in student.request(route(bid))[1],'raw storage path hidden')
            for browser in [student2,tutor2,admin,anon]:check(download(browser,a['attachment_id'])[0] in [404,403,303],'foreign or Admin attachment denied')
            check(filesend(bid,[allowed[0],allowed[1]])[0]==303 and len([a for a in attachments(bid) if a['message_id']==messages(bid)[-1]['message_id']])==2,'multiple attachments on one message')
            terminal_file=fixture(status='requested');filesend(terminal_file,[allowed[0]])
            terminal_aid=attachments(terminal_file)[0]['attachment_id']
            for status in ['rejected','cancelled','closed']:
                sql('UPDATE bookings SET status=? WHERE booking_id=?',[status,terminal_file])
                check(download(student,terminal_aid)[0]==200 and download(tutor,terminal_aid)[0]==200,'terminal attachments remain participant-readable '+status)
            max_txt=b'Salam\n  '*1310720
            result=filesend(bid,[('limit.txt',max_txt,'text/plain')])
            check(result[0]==303,'exactly 10 MB accepted: '+str(result[0]))
            bad=[('big.txt',max_txt+b'a','text/plain'),('a.php',b'<?php echo 1;?>','image/png'),('a.txt',b'<html>bad</html>','text/plain'),('a.html',b'<html>bad</html>','text/plain'),('a.svg',b'<svg/>','image/png'),('a.exe',b'MZ'+b'\0'*100,'text/plain'),('a.png',PDF,'image/png'),('a.zip',office('docx'),'application/zip'),('a.docx',office('docx',True),'application/zip'),('a.docx',office('xlsx'),'application/zip'),('a.mp4',b'fake','video/mp4'),('a.webm',b'fake','video/webm')]
            before=(messages(bid),attachments(bid),stored())
            for file in bad:check(filesend(bid,[file])[0]==422 and (messages(bid),attachments(bid),stored())==before,'unsafe or spoofed attachment rejected '+file[0])
            for ext in ['js','bat','cmd','ps1','rar','docm','xlsm','pptm']:
                check(filesend(bid,[('blocked.'+ext,b'unsafe content','text/plain')])[0]==422 and (messages(bid),attachments(bid),stored())==before,'disallowed extension '+ext)
            check(filesend(bid,[allowed[0],bad[1]])[0]==422 and (messages(bid),attachments(bid),stored())==before,'partial multi-file validation removes only staged files')
            check(filesend(bid,[allowed[0]]*6)[0]==422 and (messages(bid),attachments(bid),stored())==before,'finite attachment count')
            # Bounded deterministic pagination with deliberately equal timestamps.
            history_bid=fixture()
            for n in range(103):sql("INSERT INTO messages (booking_id,sender_user_id,message_type,body,sent_at) VALUES (?,?,'text',?,'2026-01-01 00:00:00')",[history_bid,user,'history-'+str(n)])
            code,body,_=student.request(route(history_bid));ids=[int(x) for x in re.findall('data-message-id="([0-9]+)"',body)]
            check(code==200 and len(ids)==100 and ids==sorted(ids) and 'Older messages' in body,'latest 100 chronological with ID tie breaker')
            older=student.request(route(history_bid)+'&before='+str(ids[0]))[1]
            check(len(re.findall('data-message-id=',older))==3 and 'history-0' in older,'older history navigation')
            check(student.request(route(history_bid)+'&before='+str(messages(bid)[0]['message_id']))[0]==404,'foreign cursor denied')
            check(re.findall('data-message-id="([0-9]+)"',tutor.request(route(history_bid,tutor))[1])==[str(i) for i in ids],'both participants see identical chronological history')
            check('history-100' not in student.request(route(bid))[1],'foreign conversation not mixed')
            # Independent PHP processes and sessions, sharing the disposable DB only.
            with socket.socket() as sock:sock.bind(('127.0.0.1',0));port2=sock.getsockname()[1]
            other_log=open(temp/'other.log','w')
            def start_other(environment):
                return subprocess.Popen([PHP,'-d','upload_max_filesize=11M','-d','post_max_size=56M','-d','display_errors=0','-d','session.save_path='+str(temp),'-S',f'127.0.0.1:{port2}','-t',str(ROOT)],env=environment,stdout=other_log,stderr=other_log,creationflags=flags)
            other_server=start_other(env);parallel=auth.Browser(f'http://127.0.0.1:{port2}/');parallel_tutor=auth.Browser(parallel.base)
            for _ in range(50):
                try:parallel.request('index.php');break
                except urllib.error.URLError:time.sleep(.1)
            login(parallel,'bc_student');login(parallel_tutor,'bc_tutor')
            roles[parallel]='student';roles[parallel_tutor]='tutor';tokens[parallel]=parallel.token('student/student-profile-edit.php');tokens[parallel_tutor]=parallel_tutor.token('tutor/tutor-account-edit.php')
            def race(actions):
                with concurrent.futures.ThreadPoolExecutor(max_workers=2) as pool:return list(pool.map(lambda action:action(),actions))
            concurrent_bid=fixture(status='accepted');snapshot=(row(concurrent_bid),sql('SELECT * FROM availability_slots'),sql('SELECT * FROM payments'))
            for browser in [parallel,parallel_tutor]:
                results=race([lambda:send(concurrent_bid),lambda:send(concurrent_bid,browser)])
                check([r[0] for r in results]==[303,303],'simultaneous text sends coherent')
            results=race([lambda:filesend(concurrent_bid,[allowed[0],allowed[1]]),lambda:send(concurrent_bid,parallel_tutor)])
            check([r[0] for r in results]==[303,303] and len(messages(concurrent_bid))==6 and len(attachments(concurrent_bid))==2,'attachment/text send atomic concurrency')
            check(snapshot==(row(concurrent_bid),sql('SELECT * FROM availability_slots'),sql('SELECT * FROM payments')),'chat has no booking/payment side effects')
            # Hold the booking update uncommitted until the HTTP send is waiting on its lock.
            locker=temp/'terminal-lock.php'
            locker.write_text("<?php require $argv[1].'/includes/db.php'; $db=tmq_db(); $db->beginTransaction(); $q=$db->prepare('UPDATE bookings SET status=? WHERE booking_id=?'); $q->execute([$argv[3],$argv[2]]); echo 'locked'.PHP_EOL; flush(); fgets(STDIN); $db->commit();",encoding='utf-8')
            for terminal in ['cancelled','rejected','closed']:
                b=fixture(status='requested');check(student.request(route(b))[0]==200,'stale writable form fixture')
                lock=subprocess.Popen([PHP,str(locker),str(ROOT),str(b),terminal],env=env,stdin=subprocess.PIPE,stdout=subprocess.PIPE,stderr=subprocess.PIPE,text=True,creationflags=flags)
                try:
                    check(lock.stdout.readline().strip()=='locked','terminal transaction holds booking row')
                    with concurrent.futures.ThreadPoolExecutor(max_workers=1) as pool:
                        future=pool.submit(filesend,b,[allowed[0]])
                        # Wait for actual InnoDB lock contention, not just a scheduling delay.
                        waiting=False
                        for _ in range(60):
                            if sql('SELECT * FROM information_schema.INNODB_LOCK_WAITS'):waiting=True;break
                            time.sleep(.05)
                        check(waiting,'send waits for terminal booking lock')
                        lock.communicate('\n',timeout=10);result=future.result(timeout=15)
                    check(result[0]==409 and not messages(b) and not attachments(b),'terminal wins before send '+terminal)
                finally:
                    if lock.poll() is None:lock.communicate('\n',timeout=10)
            # Real failure on metadata INSERT after the message row/file have been staged.
            restricted='tmq_chat_'+secrets.token_hex(5);secret=secrets.token_hex(18)
            sql("CREATE USER '"+restricted+"'@'127.0.0.1' IDENTIFIED BY '"+secret+"'")
            try:
                sql('GRANT SELECT ON `'+name+"`.* TO '"+restricted+"'@'127.0.0.1'")
                sql('GRANT INSERT ON `'+name+"`.messages TO '"+restricted+"'@'127.0.0.1'")
                other_server.terminate();other_server.wait(timeout=15);other_server=start_other(dict(env,TMQ_DB_USER=restricted,TMQ_DB_PASSWORD=secret))
                for _ in range(50):
                    try:parallel.request(route(bid));break
                    except urllib.error.URLError:time.sleep(.1)
                before=(messages(bid),attachments(bid),stored())
                code,body,_=filesend(bid,[allowed[0],allowed[1]],parallel)
                check(code==503 and 'SQLSTATE' not in body and (messages(bid),attachments(bid),stored())==before,'DB failure rolls back message and cleans only new files')
            finally:
                if other_server:other_server.terminate();other_server.wait(timeout=15);other_server=None
                sql("DROP USER '"+restricted+"'@'127.0.0.1'")
            sql("UPDATE users SET account_status='inactive' WHERE user_id=?",[user])
            check(student.request(route(bid))[0]==303 and send(bid)[0]==303 and download(student,a['attachment_id'])[0]==303,'inactive account denied read/send/download')
            check(all(m['read_at'] is None for m in messages(bid)),'GET history never mutates read receipts')
            check(all(r['Msg_text']=='OK' for r in sql('CHECK TABLE messages,message_attachments,bookings')),'chat tables healthy after races and rollback')
            print(f'PASS: {auth.CHECKS} booking chat assertions; privacy, messages, uploads, location, history, concurrency and rollback.')
        finally:
            if other_server:other_server.terminate();other_server.wait(timeout=15)
            if other_log:other_log.close()
            if server:server.terminate();server.wait(timeout=15);log.close()
            if created:php('drop');print('CLEANUP: dropped only task-created '+name+'; temporary chat files removed with test directory.')


if __name__=='__main__':main()
