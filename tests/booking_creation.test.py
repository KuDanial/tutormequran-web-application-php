"""Booking creation HTTP/MariaDB tests; disposable database only."""
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
check = auth.check
PHP = 'C:/xampp/php/php.exe'


def main():
    name = 'tutormequran_auth_validation_' + secrets.token_hex(8)
    env = dict(os.environ, TMQ_DB_NAME=name)
    flags = subprocess.CREATE_NO_WINDOW if os.name == 'nt' else 0
    password = 'Booking8-' + secrets.token_hex(12)
    created = False
    server = None
    with tempfile.TemporaryDirectory(prefix='tmq-booking-tests-') as temp:
        temp = Path(temp)
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
            server = subprocess.Popen([PHP,'-d','display_errors=0','-d','session.save_path='+str(temp),'-S',f'127.0.0.1:{port}','-t',str(ROOT)],env=env,stdout=log,stderr=log,creationflags=flags)
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
            epoch=(datetime.now(timezone.utc)+timedelta(days=7)).replace(hour=0,minute=0,second=0,microsecond=0)
            def slot(tid,offset,hours=2,mode='both',status='available'):
                start=epoch+timedelta(hours=offset); end=start+timedelta(hours=hours)
                sql('INSERT INTO availability_slots (tutor_id,starts_at,ends_at,mode,availability_status) VALUES (?,?,?,?,?)',[tid,start.strftime('%Y-%m-%d %H:%M:%S'),end.strftime('%Y-%m-%d %H:%M:%S'),mode,status])
                return sql('SELECT slot_id FROM availability_slots WHERE tutor_id=? AND starts_at=?',[tid,start.strftime('%Y-%m-%d %H:%M:%S')])[0]['slot_id']
            first=slot(tid,0); overlap=slot(tid2,1); adjacent=slot(tid2,2)
            free=slot(tid,10); unavailable=slot(tid,20,status='unavailable'); past=slot(tid,-240)
            online=slot(tid,30,mode='online'); physical=slot(tid,40,mode='physical')
            page='student/student-booking-addNew.php'
            token=student.token(page);token2=student2.token(page)
            def book(sid, tutor_id=tid, browser=student, **extra):
                return browser.request(page,{'csrf_token':token if browser is student else token2,'tutor':str(tutor_id),'slotId':str(sid),'classType':'online',**extra})
            def count():return sql('SELECT COUNT(*) n FROM bookings')[0]['n']
            body=student.request(page+'?tutor='+str(tid))[1]
            check(f'name="slotId" value="{first}"' in body and f'name="slotId" value="{unavailable}"' not in body and f'name="slotId" value="{past}"' not in body,'real available future selection only')
            check('8:00 AM' in body and 'RM 80.50' in body,'Malaysia display and exact price preview')
            check(book(first,student_id=student2_id,user_id=user2,hourly_rate_snapshot='1',expected_amount='1')[0]==303,'create ignoring forged identity and price')
            row=sql('SELECT * FROM bookings')[0]
            check(row['student_id']==student_id and row['tutor_id']==tid and row['slot_id']==first,'session identity and references')
            check(row['status']=='requested' and row['occupied_slot_id']==first,'requested immediately occupies')
            check(row['hourly_rate_snapshot']=='40.25' and row['expected_amount']=='80.50','exact snapshot and duration price')
            check('Booking #'+str(row['booking_id'])+' created' in student.request(page+'?tutor='+str(tid))[1],'truthful success after redirect')
            check(f'name="slotId" value="{first}"' not in student2.request(page+'?tutor='+str(tid))[1],'occupied slot no longer offered')
            sql("UPDATE tutors SET hourly_rate='50.75' WHERE tutor_id=?",[tid])
            check(sql('SELECT hourly_rate_snapshot,expected_amount FROM bookings')[0]=={'hourly_rate_snapshot':'40.25','expected_amount':'80.50'},'snapshot survives rate change')
            def rejected(sid,tutor_id=tid,**extra):
                before=count();code,body,_=book(sid,tutor_id,**extra)
                check(code==422 and count()==before and 'SQLSTATE' not in body,'invalid booking rejected atomically')
            rejected(first)
            rejected(overlap,tid2)
            # Accepted also blocks overlap, without implementing acceptance actions.
            sql("UPDATE bookings SET status='accepted' WHERE booking_id=?",[row['booking_id']]);rejected(overlap,tid2)
            sql("UPDATE bookings SET status='requested' WHERE booking_id=?",[row['booking_id']])
            check(book(adjacent,tid2)[0]==303,'adjacent Student booking accepted')
            check(book(overlap,tid2,browser=student2)[0]==303,'different Student may book overlapping interval')
            rejected(unavailable);rejected(past);rejected(free,tid2)
            rejected(online,classType='physical');rejected(physical,classType='online')
            rejected(free,classType='both');rejected('1 OR 1=1');rejected('0');rejected(free,'bad');rejected(free,999999)
            sql("UPDATE tutors SET profile_visibility='private' WHERE tutor_id=?",[tid]);rejected(free)
            sql("UPDATE tutors SET profile_visibility='public',bio='' WHERE tutor_id=?",[tid]);rejected(free)
            sql("UPDATE tutors SET bio='Test teaching',displayed_certificate_id=NULL WHERE tutor_id=?",[tid]);rejected(free)
            cid=sql('SELECT certificate_id FROM certificates WHERE tutor_id=?',[tid])[0]['certificate_id']
            sql("UPDATE tutors SET displayed_certificate_id=?,teaching_mode='online' WHERE tutor_id=?",[cid,tid]);rejected(free,classType='physical')
            sql("UPDATE tutors SET teaching_mode='both' WHERE tutor_id=?",[tid])
            sql("UPDATE users SET account_status='inactive' WHERE user_id=?",[tutor_user]);rejected(free)
            sql("UPDATE users SET account_status='active' WHERE user_id=?",[tutor_user])
            check(book(free,classType='physical')[0]==303,'both-mode slot accepts physical')
            check(book(online)[0]==303 and book(physical,classType='physical')[0]==303,'single-mode slots accept supported mode')
            sql("UPDATE tutors SET hourly_rate='99999999.99' WHERE tutor_id=?",[tid])
            rejected(slot(tid,50))
            check(student.request(page+'?tutor='+str(tid))[0]==200,'out-of-range amounts do not break selection page')
            sql("UPDATE tutors SET hourly_rate='50.75' WHERE tutor_id=?",[tid])
            check(book(free,csrf_token='invalid')[0]==403,'CSRF rejected')
            for browser,label in [(anon,'anonymous'),(tutor,'Tutor'),(admin,'Admin')]:
                check(browser.request(page,{'csrf_token':token,'tutor':tid,'slotId':free,'classType':'online'})[0]==(303 if browser is anon else 403),label+' denied')
            # Real independent connections; HTTP's development server is serial.
            worker=temp/'concurrent.php'
            worker.write_text("<?php require $argv[1].'/includes/auth.php'; require $argv[1].'/includes/booking-creation.php'; $p=json_decode(stream_get_contents(STDIN),true); try { tmq_create_booking(['role'=>'student','user_id'=>$p['user']],$p['data']); echo 'saved'; } catch (DomainException $e) { echo $e->getMessage(); }",encoding='utf-8')
            def attempt(args):
                uid,tutor_id,sid=args
                r=subprocess.run([PHP,str(worker),str(ROOT)],input=json.dumps({'user':uid,'data':{'tutor':str(tutor_id),'slotId':str(sid),'classType':'online'}}),capture_output=True,text=True,env=env,creationflags=flags)
                if r.returncode:raise RuntimeError(r.stderr)
                return r.stdout
            race=slot(tid,60)
            with concurrent.futures.ThreadPoolExecutor(max_workers=2) as pool:results=list(pool.map(attempt,[(user,tid,race),(user2,tid,race)]))
            check(results.count('saved')==1 and any('slot is no longer available' in x for x in results),'same-slot race controlled loser')
            check(sql('SELECT COUNT(*) n FROM bookings WHERE slot_id=?',[race])[0]['n']==1,'exactly one race booking')
            cross1=slot(tid,70);cross2=slot(tid2,71)
            with concurrent.futures.ThreadPoolExecutor(max_workers=2) as pool:results=list(pool.map(attempt,[(user,tid,cross1),(user,tid2,cross2)]))
            check(results.count('saved')==1 and any('overlaps one of your active bookings' in x for x in results),'same-Student cross-Tutor concurrency')
            sql("UPDATE users SET account_status='inactive' WHERE user_id=?",[user2])
            check('Student account unavailable' in attempt((user2,tid,free)),'active Student rechecked inside transaction')
            check(sql('SELECT COUNT(*) n FROM payments')[0]['n']==0,'no payment records created')
            check(sql('CHECK TABLE bookings')[0]['Msg_text']=='OK','temporary bookings healthy')
            print(f'PASS: {auth.CHECKS} booking creation assertions; real slots, reservation, pricing, validation and concurrent races.')
        finally:
            if server: server.terminate(); server.wait(timeout=15); log.close()
            if created: php('drop'); print('CLEANUP: dropped only task-created '+name)


if __name__ == '__main__': main()
