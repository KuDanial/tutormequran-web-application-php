"""Booking management HTTP/MariaDB tests; disposable database only."""
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
    with tempfile.TemporaryDirectory(prefix='tmq-management-tests-') as temp:
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
            for uid,fixture_name in [(user,'Learner One'),(user2,'Learner Two'),(tutor_user,'Teacher One'),(tutor2_user,'Teacher Two')]:
                sql('UPDATE users SET first_name=?,last_name=?,address=?,phone=? WHERE user_id=?',[fixture_name,'Test','PRIVATE ADDRESS','PRIVATE PHONE',uid])
            create='student/student-booking-addNew.php'
            stoken=student.token(create);stoken2=student2.token(create)
            def create_booking(sid,tutor_id=tid,browser=student):
                response=browser.request(create,{'csrf_token':stoken if browser is student else stoken2,'tutor':str(tutor_id),'slotId':str(sid),'classType':'online','bookingNotes':'Practice <script>alert(1)</script>'})
                check(response[0]==303,'booking fixture uses real creation')
                return sql('SELECT booking_id FROM bookings WHERE slot_id=? ORDER BY booking_id DESC',[sid])[0]['booking_id']
            first=slot(tid,0); second=slot(tid,4); foreign=slot(tid2,8)
            bid=create_booking(first);reject_id=create_booking(second,browser=student2);foreign_id=create_booking(foreign,tid2,browser=student2)
            sl='student/student-bookings.php';sv='student/student-booking-view.php'
            tl='tutor/tutor-bookings.php';tv='tutor/tutor-booking-view.php'
            al='admin/manage-bookings.php';av='admin/manage-bookings-view.php'
            def view(page,bid):return page+'?id='+str(bid)
            token=tutor.token(view(tv,bid));other_token=tutor2.token(view(tv,foreign_id))
            def decision(bid,action='accept',reason='',browser=tutor,csrf=None,page=tv):
                return browser.request(view(page,bid),{'csrf_token':csrf if csrf is not None else token if browser is tutor else other_token,'decision':action,'reason':reason,'tutor_id':tid2,'student_id':student2_id})
            def row(bid):return sql('SELECT * FROM bookings WHERE booking_id=?',[bid])[0]
            own=student.request(sl+'?student_id='+str(student2_id))[1]
            check(f'{sv.split("/")[-1]}?id={bid}' in own and f'{sv.split("/")[-1]}?id={reject_id}' not in own,'Student list isolated despite forged owner')
            own=tutor.request(tl+'?tutor_id='+str(tid2))[1]
            check(f'{tv.split("/")[-1]}?id={bid}' in own and f'{tv.split("/")[-1]}?id={reject_id}' in own and f'{tv.split("/")[-1]}?id={foreign_id}' not in own,'Tutor list isolated')
            check(student2.request(view(sv,bid))[0]==404 and tutor2.request(view(tv,bid))[0]==404,'foreign detail denied')
            check(student2.request(view(sv,999999))[0]==404 and tutor.request(tv+'?id=bad')[0]==404,'missing and malformed IDs controlled')
            student_body=student.request(view(sv,bid))[1]; tutor_body=tutor.request(view(tv,bid))[1]
            check('Teacher One' in student_body and 'Learner One' in tutor_body and 'Requested' in student_body,'correct counterpart and requested status')
            check('RM 40.25' in student_body and 'RM 80.50' in student_body and '8:00 AM MYT' in student_body,'price snapshots and Malaysia display')
            check('PRIVATE ADDRESS' not in tutor_body+student_body and 'PRIVATE PHONE' not in tutor_body+student_body and '&lt;script&gt;' in tutor_body,'privacy and escaped notes')
            check('value="accept"' in tutor_body and 'value="reject"' in tutor_body,'requested actions available')
            original=row(bid);original_slot=sql('SELECT * FROM availability_slots WHERE slot_id=?',[first])[0]
            check(tutor.request(view(tv,bid)+'&decision=accept')[0]==200 and row(bid)==original,'GET does not accept')
            for action in ['accept','reject']:
                check(decision(bid,action,'Reason',csrf='bad')[0]==403 and row(bid)==original,'decision CSRF enforced')
                check(decision(bid,action,'Reason',browser=tutor2)[0]==404,'foreign Tutor action denied')
                for browser in [student,admin,anon]:
                    check(decision(bid,action,'Reason',browser=browser)[0]==(303 if browser is anon else 403),'non-Tutor action denied')
            check(decision(bid,'cancel')[0]==422 and row(bid)==original,'unsupported action no mutation')
            check(decision(bid)[0]==303,'own Tutor accepts')
            accepted=row(bid)
            check(accepted['status']=='accepted' and accepted['decision_at'] is not None and accepted['occupied_slot_id']==first,'accepted decision and occupation')
            check(all(accepted[k]==original[k] for k in original if k not in ['status','decision_at','updated_at']),'accept changes only decision metadata')
            check(sql('SELECT * FROM availability_slots WHERE slot_id=?',[first])[0]==original_slot,'accepted slot unchanged')
            check('Accepted' in student.request(view(sv,bid))[1] and 'value="accept"' not in tutor.request(view(tv,bid))[1],'accepted detail and no actions')
            check(decision(bid)[0]==409 and decision(bid,'reject','Too late')[0]==409,'accepted stale decisions rejected')
            check(row(bid)==accepted,'stale attempts leave accepted record untouched')
            check('no longer available' in student2.request(create,{'csrf_token':stoken2,'tutor':str(tid),'slotId':str(first),'classType':'online'})[1],'accepted slot remains unavailable for creation')
            before=row(reject_id);before_slot=sql('SELECT * FROM availability_slots WHERE slot_id=?',[second])[0]
            check(decision(reject_id,'reject','')[0]==422 and decision(reject_id,'reject','x'*601)[0]==422 and row(reject_id)==before,'required bounded rejection reason')
            check(decision(reject_id,'reject','Cannot teach <script>alert(1)</script>')[0]==303,'own Tutor rejects')
            rejected=row(reject_id)
            check(rejected['status']=='rejected' and rejected['occupied_slot_id'] is None and rejected['decision_at'] is not None and rejected['rejection_reason']=='Cannot teach <script>alert(1)</script>','rejected metadata and released occupancy')
            check(sql('SELECT * FROM availability_slots WHERE slot_id=?',[second])[0]==before_slot,'rejected slot unchanged')
            check('Rejected' in student2.request(view(sv,reject_id))[1] and '&lt;script&gt;' in student2.request(view(sv,reject_id))[1],'Student sees rejection and escaped reason')
            check(f'?id={reject_id}' in tutor.request(tl+'?status=rejected')[1] and f'?id={bid}' not in tutor.request(tl+'?status=rejected')[1],'filtered rejection history')
            check(f'?id={reject_id}' in student2.request(sl+'?status=rejected')[1],'Student rejection history')
            check('Rejected' in tutor.request(view('tutor/tutor-booking-rejected.php',reject_id))[1] and tutor2.request(view('tutor/tutor-booking-rejected.php',reject_id))[0]==404,'rejection page real and owned')
            check(decision(reject_id)[0]==409 and decision(reject_id,'reject','Again')[0]==409,'rejected stale decisions denied')
            reused=create_booking(second,browser=student)
            check(reused!=reject_id and row(reused)['occupied_slot_id']==second and row(reject_id)==rejected,'creation reuses released slot and retains old history')
            check(admin.request(al)[0]==200 and all(f'?id={i}' in admin.request(al)[1] for i in [bid,reject_id,foreign_id]),'Admin lists all bookings')
            check('Teacher One' in admin.request(view(av,bid))[1] and 'Learner One' in admin.request(view(av,bid))[1],'Admin real detail')
            for browser in [student,tutor]:check(browser.request(al)[0]==403 and browser.request(view(av,bid))[0]==403,'non-Admin read denied')
            for page in [al,view(av,bid)]:check(admin.request(page,{'decision':'reject','reason':'Forged'})[0]==405,'Admin pages read-only')
            check(student.request(view(sv,bid),{'decision':'reject'})[0]==405,'Student detail read-only')
            check(tutor.request(tl,{'decision':'accept','id':foreign_id})[0]==405,'Tutor list read-only')
            check(row(bid)==accepted,'read-only attempts do not mutate')
            for body in [student.request(view(sv,bid))[1],tutor.request(view(tv,bid))[1],admin.request(view(av,bid))[1]]:
                check('payment-upload.php' not in body and 'bookings-closed.php' not in body ,'deferred payment/closing links remain removed')
            # A requested lesson in the past cannot be accepted (fixture adjustment only).
            past_slot=slot(tid,12);past_id=create_booking(past_slot)
            past_start=(epoch-timedelta(days=10)).strftime('%Y-%m-%d %H:%M:%S');past_end=(epoch-timedelta(days=10)+timedelta(hours=2)).strftime('%Y-%m-%d %H:%M:%S')
            sql('UPDATE availability_slots SET starts_at=?,ends_at=? WHERE slot_id=?',[past_start,past_end,past_slot]);sql('UPDATE bookings SET starts_at=?,ends_at=? WHERE booking_id=?',[past_start,past_end,past_id])
            check(decision(past_id)[0]==409 and row(past_id)['status']=='requested','past acceptance denied')
            # Independent PDO processes; no false concurrency from the serial web server.
            worker=temp/'decide.php'
            worker.write_text("<?php require $argv[1].'/includes/auth.php'; require $argv[1].'/includes/bookings.php'; $p=json_decode(stream_get_contents(STDIN),true); try { tmq_decide_booking(['role'=>'tutor','user_id'=>$p['user']],$p['id'],$p['action'],'Cannot teach'); echo 'saved'; } catch (DomainException $e) { echo $e->getMessage(); }",encoding='utf-8')
            def race_action(args):
                bid,action=args
                r=subprocess.run([PHP,str(worker),str(ROOT)],input=json.dumps({'user':tutor_user,'id':bid,'action':action}),capture_output=True,text=True,env=env,creationflags=flags)
                if r.returncode:raise RuntimeError(r.stderr)
                return r.stdout
            for offset,actions in [(24,['accept','accept']),(30,['accept','reject'])]:
                sid=slot(tid,offset);race_id=create_booking(sid)
                with concurrent.futures.ThreadPoolExecutor(max_workers=2) as pool:results=list(pool.map(race_action,[(race_id,a) for a in actions]))
                check(results.count('saved')==1 and sum('already been updated' in r for r in results)==1,'concurrent decisions one winner/controlled stale loser')
                result=row(race_id)
                check(result['status'] in ['accepted','rejected'] and result['decision_at'] is not None and (result['occupied_slot_id']==sid if result['status']=='accepted' else result['occupied_slot_id'] is None and result['rejection_reason']=='Cannot teach'),'atomic race outcome')
            check(sql('SELECT COUNT(*) n FROM payments')[0]['n']==0 and sql('SELECT COUNT(*) n FROM booking_change_requests')[0]['n']==0,'no deferred workflow records')
            check(sql('CHECK TABLE bookings')[0]['Msg_text']=='OK','temporary booking table healthy')
            print(f'PASS: {auth.CHECKS} booking management assertions; ownership, decisions, history, slot reuse, read-only Admin and concurrency.')
        finally:
            if server: server.terminate(); server.wait(timeout=15); log.close()
            if created: php('drop'); print('CLEANUP: dropped only task-created '+name)


if __name__ == '__main__': main()
