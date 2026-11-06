"""Booking lifecycle HTTP/MariaDB tests; disposable database only."""
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
    with tempfile.TemporaryDirectory(prefix='tmq-lifecycle-tests-') as temp:
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
            se='student/student-booking-edit.php'; te='tutor/tutor-booking-edit.php'
            sv='student/student-booking-view.php'; tv='tutor/tutor-booking-view.php'
            tokens={student:stoken,student2:stoken2,tutor:tutor.token('tutor/tutor-availability-addNew.php'),tutor2:tutor2.token('tutor/tutor-availability-addNew.php')}
            def page(browser,bid):return (se if browser in [student,student2] else te)+'?id='+str(bid)
            def request(bid,target,mode='online',browser=student,**extra):
                data={'csrf_token':tokens[browser],'change_action':'create','slotId':target,'classType':mode,'reason':'Please change <script>alert(1)</script>',
                      'student_id':student2_id,'tutor_id':tid2,'requested_by_user_id':user2,'user_id':user2,'proposed_expected_amount':'0.01','base_agreement_version':'999'}
                data.update(extra)
                return browser.request(page(browser,bid),data)
            def respond(bid,cid,action='accept',browser=tutor,**extra):
                data={'csrf_token':tokens[browser],'change_action':action,'change_id':cid,'response_note':'Response <b>test</b>','responded_by_user_id':user2}
                data.update(extra)
                return browser.request(page(browser,bid),data)
            def row(bid):return sql('SELECT * FROM bookings WHERE booking_id=?',[bid])[0]
            def change(bid):return sql('SELECT * FROM booking_change_requests WHERE booking_id=? ORDER BY change_request_id DESC',[bid])[0]
            offset=0
            def fixture(hours=1,mode='both'):
                nonlocal offset
                offset+=72
                old=slot(tid,offset,hours,mode); target=slot(tid,offset+6,2)
                bid=create_booking(old)
                token=tokens[tutor]
                check(tutor.request(tv+'?id='+str(bid),{'csrf_token':token,'decision':'accept'})[0]==303,'fixture accepted through management')
                return bid,old,target
            admin_user=sql('SELECT user_id FROM admins WHERE admin_id=?',[aid])[0]['user_id']
            tokens[admin]=admin.token('admin/admin-profile.php')
            roles={student:'student',student2:'student',tutor:'tutor',tutor2:'tutor',admin:'admin'}
            def route(browser,bid,kind):
                role=roles[browser]
                return (f'admin/manage-bookings-{kind}.php' if role=='admin' else f'{role}/{role}-booking-{kind}.php')+'?id='+str(bid)
            def cancel(bid,browser=student,**extra):
                data={'csrf_token':tokens[browser],'lifecycle_action':'cancel','reason':'  Cannot attend <b>today</b> & tomorrow  ',
                      'cancelled_by':'admin','cancelled_by_user_id':admin_user,'student_id':student2_id,'tutor_id':tid2,'user_id':admin_user}
                data.update(extra)
                return browser.request(route(browser,bid,'cancel'),data)
            def complete(bid,browser=student,**extra):
                data={'csrf_token':tokens[browser],'lifecycle_action':'complete','agreement_version':row(bid)['agreement_version'],
                      'student_completed_at':'2000-01-01 00:00:00','tutor_completed_at':'2000-01-01 00:00:00','status':'closed','user_id':admin_user}
                data.update(extra)
                return browser.request(route(browser,bid,'session'),data)
            past_number=0
            def move_past(bid,arranged=True):
                nonlocal past_number
                past_number+=1
                start=(datetime.now(timezone.utc)-timedelta(days=past_number+2)).replace(hour=0,minute=0,second=0,microsecond=0)
                b=row(bid);end=start+timedelta(hours=1)
                args=[start.strftime('%Y-%m-%d %H:%M:%S'),end.strftime('%Y-%m-%d %H:%M:%S')]
                sql('UPDATE availability_slots SET starts_at=?,ends_at=? WHERE slot_id=?',args+[b['slot_id']])
                sql('UPDATE bookings SET starts_at=?,ends_at=?,expected_amount=hourly_rate_snapshot WHERE booking_id=?',args+[bid])
                if arranged:sql("UPDATE bookings SET student_arranged_at=UTC_TIMESTAMP(),tutor_arranged_at=UTC_TIMESTAMP(),meeting_platform='Video',meeting_url='https://example.invalid/room' WHERE booking_id=?",[bid])
            def past_fixture():
                bid,old,target=fixture();move_past(bid);return bid,old,target
            def unchanged_contract(before,after):
                fields=['hourly_rate_snapshot','expected_amount','class_mode','starts_at','ends_at','slot_id','agreement_version','venue_address','meeting_platform','meeting_url','student_arranged_at','tutor_arranged_at']
                return all(before[f]==after[f] for f in fields)

            # Cancellation owns the actor and releases only generated occupancy.
            bid,old,target=fixture();sql("UPDATE bookings SET status='requested' WHERE booking_id=?",[bid]);before=row(bid)
            check(student.request(route(student,bid,'cancel')+'&lifecycle_action=cancel&reason=GET')[0]==200 and row(bid)==before,'GET cancellation never mutates')
            check(cancel(bid,csrf_token='bad')[0]==403 and row(bid)==before,'cancellation CSRF required')
            for invalid in ['', '   ', '<b></b>', 'x'*65536, '\U0001f642'*16384]:
                check(cancel(bid,reason=invalid)[0]==422 and row(bid)==before,'reason required and TEXT byte limit enforced')
            for browser in [student2,tutor2]:
                code,body,_=cancel(bid,browser)
                check(code==404 and 'Learner One' not in body and row(bid)==before,'foreign cancellation ID cannot expose/mutate booking')
            check(cancel(bid,tutor)[0]==409 and 'Reject booking' in tutor.request(tv+'?id='+str(bid))[1],'Tutor requested booking retains Reject')
            slot_before=sql('SELECT * FROM availability_slots WHERE slot_id=?',[old])[0]
            check(cancel(bid)[0]==303,'Student requested cancellation')
            after=row(bid)
            check(after['status']=='cancelled' and after['cancelled_by_user_id']==user and after['cancelled_at'] and after['cancellation_timing']=='early' and after['cancellation_reason']=='Cannot attend today & tomorrow','session actor and plain trimmed cancellation metadata')
            check(unchanged_contract(before,after) and after['occupied_slot_id'] is None and sql('SELECT * FROM availability_slots WHERE slot_id=?',[old])[0]==slot_before,'history/price/slot retained; occupancy released')
            replacement=create_booking(old,browser=student2)
            check(row(replacement)['occupied_slot_id']==old and row(bid)['status']=='cancelled','cancelled future slot reused by real booking creation')
            check(cancel(bid)[0]==409 and row(bid)==after,'duplicate cancellation controlled and immutable')
            check(cancel(replacement,student2)[0]==303,'replacement can cancel')
            for browser,uid in [(student,user),(tutor,tutor_user),(admin,admin_user)]:
                cb,cs,ct=fixture();sql("UPDATE bookings SET student_arranged_at=UTC_TIMESTAMP(),tutor_arranged_at=UTC_TIMESTAMP(),meeting_platform='Video',meeting_url='https://example.invalid/room' WHERE booking_id=?",[cb]);original=row(cb)
                check(cancel(cb,browser)[0]==303,'accepted cancellation '+roles[browser])
                current=row(cb)
                check(current['cancelled_by_user_id']==uid and current['cancellation_timing']=='early' and unchanged_contract(original,current),'actor, early timing and arrangement history '+roles[browser])
                new=create_booking(cs,browser=student2)
                check(row(new)['occupied_slot_id']==cs,'released slot reusable '+roles[browser]);cancel(new,student2)
            for status in ['rejected','cancelled','completed','closed']:
                cb,cs,ct=fixture()
                sql("UPDATE bookings SET status=?,rejection_reason='No lesson',student_completed_at=UTC_TIMESTAMP(),tutor_completed_at=UTC_TIMESTAMP() WHERE booking_id=?",[status,cb]);original=row(cb)
                for browser in [student,tutor,admin]:
                    check(cancel(cb,browser)[0]==409 and row(cb)==original,'terminal cancellation blocked '+roles[browser]+' '+status)
            # Same-day cancellation remains valid after start; timing is based only on date.
            zone=timezone(timedelta(hours=8));today=datetime.now(zone).replace(hour=0,minute=0,second=0,microsecond=0)
            for hour,(browser,uid) in enumerate([(student,user),(tutor,tutor_user),(admin,admin_user)]):
                cb,cs,ct=fixture();start=(today+timedelta(hours=hour)).astimezone(timezone.utc);end=start+timedelta(hours=1)
                times=[start.strftime('%Y-%m-%d %H:%M:%S'),end.strftime('%Y-%m-%d %H:%M:%S')]
                sql('UPDATE availability_slots SET starts_at=?,ends_at=? WHERE slot_id=?',times+[cs]);sql('UPDATE bookings SET starts_at=?,ends_at=? WHERE booking_id=?',times+[cb])
                check(cancel(cb,browser)[0]==303 and row(cb)['cancellation_timing']=='same_day' and row(cb)['cancelled_by_user_id']==uid,'same Malaysia date '+roles[browser])
            cb,cs,ct=past_fixture();original=row(cb)
            check(cancel(cb)[0]==409 and cancel(cb,tutor)[0]==409 and row(cb)==original,'participants cannot cancel after lesson date')
            check(cancel(cb,admin)[0]==303 and row(cb)['cancellation_timing']=='after_lesson_date' and row(cb)['cancelled_by_user_id']==admin_user,'exceptional Admin retrospective cancellation')
            for browser in [student,tutor,anon]:
                check(browser.request('admin/manage-bookings-cancel.php?id='+str(cb),{'csrf_token':tokens.get(browser,''),'lifecycle_action':'cancel','reason':'forged'})[0] in [302,303,403],'Admin endpoint role protection')
            # Deterministic local-midnight boundaries, including UTC dates that differ.
            timing=temp/'timing.php'
            timing.write_text("""<?php require $argv[1].'/includes/auth.php'; require $argv[1].'/includes/booking-cancellations.php';
              $b=['starts_at'=>'2026-11-10 02:00:00'];
              foreach(['2026-11-09 15:59:00','2026-11-09 16:01:00','2026-11-10 15:59:59','2026-11-10 16:00:00'] as $now) {
                $out[]=tmq_cancellation_timing($b,new DateTimeImmutable($now,new DateTimeZone('UTC')));
              } echo json_encode($out);""",encoding='utf-8')
            result=subprocess.run([PHP,str(timing),str(ROOT)],env=env,capture_output=True,text=True,creationflags=flags)
            check(result.returncode==0 and json.loads(result.stdout)==['early','same_day','same_day','after_lesson_date'],'Malaysia midnight classification is calendar-based, not rolling 24 hours')
            cb,cs,ct=fixture();check(request(cb,ct)[0]==303,'pending cancellation fixture');history=change(cb)
            check(cancel(cb,tutor)[0]==303,'counterpart cancellation with pending proposal')
            withdrawn=change(cb)
            check(withdrawn['change_request_id']==history['change_request_id'] and withdrawn['status']=='withdrawn' and withdrawn['responded_at'] and withdrawn['responded_by_user_id'] is None and withdrawn['pending_booking_id'] is None,'pending history withdrawn atomically without false consent')
            original=row(cb)
            check(respond(cb,history['change_request_id'])[0]==409 and row(cb)==original,'old cancelled proposal cannot apply')
            for browser in [student,tutor,admin]:
                code,body,_=browser.request(route(browser,cb,'view'))
                check(code==200 and 'Cancelled' in body and 'Cancelled by' in body and 'Tutor' in body and 'Cannot attend today &amp; tomorrow' in body,'cancelled history displayed and escaped '+roles[browser])
                listing='admin/manage-bookings.php' if browser is admin else roles[browser]+'/'+roles[browser]+'-bookings.php'
                check('Cancelled' in browser.request(listing+'?status=cancelled')[1],'cancelled list '+roles[browser])
            sql("UPDATE bookings SET cancellation_reason='<script>alert(1)</script>' WHERE booking_id=?",[cb])
            body=admin.request(route(admin,cb,'view'))[1]
            check('&lt;script&gt;alert(1)&lt;/script&gt;' in body and '<script>alert(1)</script>' not in body,'legacy cancellation HTML is escaped on output')

            # Completion eligibility, authorization and independent timestamps.
            cb,cs,ct=fixture();sql('UPDATE bookings SET student_arranged_at=UTC_TIMESTAMP(),tutor_arranged_at=UTC_TIMESTAMP() WHERE booking_id=?',[cb]);original=row(cb)
            check(complete(cb)[0]==409 and complete(cb,tutor)[0]==409 and row(cb)==original,'neither participant completes before effective end')
            cb,cs,ct=past_fixture();original=row(cb)
            check(student.request(route(student,cb,'session')+'&lifecycle_action=complete')[0]==200 and row(cb)==original,'GET cannot complete')
            check(complete(cb,csrf_token='bad')[0]==403 and row(cb)==original,'completion CSRF')
            for browser in [student2,tutor2]:
                check(complete(cb,browser)[0]==404 and browser.request(route(browser,cb,'session'))[0]==404 and row(cb)==original,'completion ownership and read scope')
            check(admin.request(route(student,cb,'session'),{'csrf_token':tokens[admin],'lifecycle_action':'complete'})[0]==403,'Admin cannot confirm for participant')
            check(complete(cb,agreement_version=999)[0]==409 and row(cb)==original,'stale completion version rejected')
            for status in ['requested','rejected','cancelled','closed']:
                sql("UPDATE bookings SET status=?,rejection_reason='No lesson',student_completed_at=IF(?='closed',UTC_TIMESTAMP(),NULL),tutor_completed_at=IF(?='closed',UTC_TIMESTAMP(),NULL) WHERE booking_id=?",[status,status,status,cb]);current=row(cb)
                check(complete(cb)[0]==409 and complete(cb,tutor)[0]==409 and row(cb)==current,'completion blocked for '+status)
            sql("UPDATE bookings SET status='accepted',student_completed_at=NULL,tutor_completed_at=NULL WHERE booking_id=?",[cb])
            for student_arranged,tutor_arranged in [(None,None),('2026-01-01 00:00:00',None),(None,'2026-01-01 00:00:00')]:
                sql('UPDATE bookings SET student_arranged_at=?,tutor_arranged_at=? WHERE booking_id=?',[student_arranged,tutor_arranged,cb]);current=row(cb)
                check(complete(cb)[0]==409 and complete(cb,tutor)[0]==409 and row(cb)==current,'both arrangement confirmations required')
            for first,second,first_role,second_role in [(student,tutor,'student','tutor'),(tutor,student,'tutor','student')]:
                cb,cs,ct=past_fixture();original=row(cb)
                check(complete(cb,first)[0]==303,'first completion '+first_role)
                one=row(cb)
                check(one['status']=='accepted' and one[first_role+'_completed_at'] and one[second_role+'_completed_at'] is None and unchanged_contract(original,one),'only acting participant timestamp; accepted unchanged contract')
                check('Awaiting '+second_role.title()+' completion confirmation' in first.request(route(first,cb,'session'))[1],'one-sided derived UI state')
                time.sleep(1.05)
                check(complete(cb,first)[0]==303 and row(cb)==one,'duplicate first confirmation does not rewrite timestamp')
                check(complete(cb,second)[0]==303,'second completion '+second_role)
                two=row(cb)
                check(two['status']=='completed' and two['student_completed_at'] and two['tutor_completed_at'] and two[first_role+'_completed_at']==one[first_role+'_completed_at'] and two['closed_at'] is None,'exact both-confirmations completed transition, never closed')
                check(unchanged_contract(original,two) and two['occupied_slot_id']==cs,'completed agreement, price, arrangement and historical occupancy retained')
                check(complete(cb,first)[0]==303 and complete(cb,second)[0]==303 and row(cb)==two,'completed duplicate confirmation idempotent')
                check(cancel(cb,admin)[0]==409 and row(cb)==two,'completion winner prevents later cancellation')
                for browser in [student,tutor,admin]:
                    body=browser.request(route(browser,cb,'view'))[1]
                    check('Completed' in body and 'Student completion confirmation' in body and 'Tutor completion confirmation' in body and 'payment-upload.php' not in body,'completed detail and no payment action '+roles[browser])
                    listing='admin/manage-bookings.php' if browser is admin else roles[browser]+'/'+roles[browser]+'-bookings.php'
                    check('Completed' in browser.request(listing+'?status=completed')[1],'completed list '+roles[browser])
                for browser in [student,tutor]:
                    check(browser.request(route(browser,cb,'completed'),{'csrf_token':tokens[browser],'lifecycle_action':'complete'})[0]==405,'completed page is read-only; one canonical mutation endpoint')
            cb,cs,ct=past_fixture();check(cancel(cb,admin)[0]==303,'cancellation wins first');original=row(cb)
            check(complete(cb)[0]==409 and complete(cb,tutor)[0]==409 and row(cb)==original,'cancelled winner prevents either completion')
            # Effective schedule after real change acceptance; old end cannot authorize completion.
            cb,cs,ct=fixture();check(request(cb,ct)[0]==303 and respond(cb,change(cb)['change_request_id'])[0]==303,'rescheduled fixture')
            sql('UPDATE bookings SET student_arranged_at=UTC_TIMESTAMP(),tutor_arranged_at=UTC_TIMESTAMP() WHERE booking_id=?',[cb]);original=row(cb)
            check(original['slot_id']==ct and complete(cb)[0]==409 and row(cb)==original,'effective rescheduled future end blocks completion')
            move_past(cb);check(complete(cb)[0]==303 and complete(cb,tutor)[0]==303 and row(cb)['agreement_version']==2,'rescheduled agreement completes only after effective end')

            # Truly independent PDO processes, released together by a start barrier.
            worker=temp/'lifecycle.php'
            worker.write_text("""<?php require $argv[1].'/includes/auth.php'; require $argv[1].'/includes/booking-cancellations.php'; require $argv[1].'/includes/booking-completion.php';
              $p=json_decode(stream_get_contents(STDIN),true);file_put_contents($p['ready'],'ready');while(!file_exists($p['go'])){usleep(1000);}
              try {$actor=['role'=>$p['role'],'user_id'=>$p['user']];
                if($p['action']==='complete'){echo tmq_confirm_completion($actor,$p['id'],$p['version'])?'saved':'unchanged';}
                elseif($p['action']==='cancel'){tmq_cancel_booking($actor,$p['id'],'Concurrent cancellation');echo 'saved';}
                elseif($p['action']==='arrange'){echo tmq_confirm_arrangement($actor,$p['id'],$p['version'])?'saved':'unchanged';}
                else{tmq_respond_booking_change($actor,$p['id'],$p['cid'],'accept');echo 'saved';}
              }catch(DomainException $e){echo 'conflict:'.$e->getMessage();}""",encoding='utf-8')
            def race(bid,cid,actions):
                nonce=secrets.token_hex(4);go=temp/(nonce+'-go');processes=[];version=row(bid)['agreement_version']
                try:
                    for i,(action,role,uid) in enumerate(actions):
                        ready=temp/(nonce+'-'+str(i));p=subprocess.Popen([PHP,str(worker),str(ROOT)],stdin=subprocess.PIPE,stdout=subprocess.PIPE,stderr=subprocess.PIPE,text=True,env=env,creationflags=flags)
                        processes.append((p,ready));p.stdin.write(json.dumps({'id':bid,'version':version,'cid':cid,'action':action,'role':role,'user':uid,'ready':str(ready),'go':str(go)}));p.stdin.close();p.stdin=None
                    deadline=time.monotonic()+15
                    while not all(ready.exists() for p,ready in processes):
                        if time.monotonic()>deadline:raise RuntimeError('Worker barrier timeout')
                        time.sleep(.01)
                    go.write_text('go');results=[]
                    for p,_ in processes:
                        out,err=p.communicate(timeout=20)
                        if p.returncode:raise RuntimeError(out+err)
                        results.append(out)
                    return results
                finally:
                    for p,_ in processes:
                        if p.poll() is None:p.kill();p.wait()
            cb,cs,ct=past_fixture();original=row(cb)
            results=race(cb,0,[('complete','student',user),('complete','tutor',tutor_user)])
            check(results==['saved','saved'] and row(cb)['status']=='completed' and row(cb)['student_completed_at'] and row(cb)['tutor_completed_at'] and unchanged_contract(original,row(cb)),'concurrent participant completion stores both atomically')
            cb,cs,ct=past_fixture()
            results=race(cb,0,[('complete','student',user),('complete','student',user)])
            check(sorted(results)==['saved','unchanged'] and row(cb)['status']=='accepted' and row(cb)['tutor_completed_at'] is None,'duplicate concurrent completion establishes one timestamp')
            for _ in range(3):
                cb,cs,ct=past_fixture();check(complete(cb)[0]==303,'first confirmation race fixture');original=row(cb)
                results=race(cb,0,[('complete','tutor',tutor_user),('cancel','admin',admin_user)])
                current=row(cb)
                check(results.count('saved')==1 and sum(r.startswith('conflict:') for r in results)==1,'completion/cancellation exactly one terminal winner')
                check(current['status'] in ['completed','cancelled'] and current['student_completed_at']==original['student_completed_at'] and unchanged_contract(original,current),'terminal race retains coherent agreement')
                check((current['status']=='completed' and current['occupied_slot_id']==cs and current['tutor_completed_at'] and current['cancelled_at'] is None) or (current['status']=='cancelled' and current['occupied_slot_id'] is None and current['tutor_completed_at'] is None and current['cancelled_by_user_id']==admin_user),'no partial completion/cancellation metadata')
            cb,cs,ct=fixture()
            results=race(cb,0,[('cancel','student',user),('cancel','tutor',tutor_user)])
            check(results.count('saved')==1 and sum(r.startswith('conflict:') for r in results)==1 and row(cb)['status']=='cancelled','Student vs Tutor cancellation one winner')
            # Before 01:00 MYT no same-date, whole-hour lesson can have ended yet.
            # At other times exercise participant cancellation against second completion too.
            if datetime.now(zone).hour>=1:
                ps=slot(tid2,offset+96,1);pb=create_booking(ps,tid2,student2)
                check(tutor2.request(tv+'?id='+str(pb),{'csrf_token':tokens[tutor2],'decision':'accept'})[0]==303,'participant terminal race fixture')
                start=today.astimezone(timezone.utc);end=start+timedelta(hours=1)
                times=[start.strftime('%Y-%m-%d %H:%M:%S'),end.strftime('%Y-%m-%d %H:%M:%S')]
                sql('UPDATE availability_slots SET starts_at=?,ends_at=? WHERE slot_id=?',times+[ps])
                sql('UPDATE bookings SET starts_at=?,ends_at=?,student_arranged_at=UTC_TIMESTAMP(),tutor_arranged_at=UTC_TIMESTAMP() WHERE booking_id=?',times+[pb])
                check(complete(pb,student2)[0]==303,'Student first completion on lesson date')
                results=race(pb,0,[('cancel','student',user2),('complete','tutor',tutor2_user)])
                current=row(pb)
                check(results.count('saved')==1 and sum(r.startswith('conflict:') for r in results)==1 and current['status'] in ['completed','cancelled'],'participant cancellation vs second completion one terminal winner')
                check((current['status']=='cancelled' and current['cancelled_by_user_id']==user2 and current['cancellation_timing']=='same_day' and current['tutor_completed_at'] is None and current['occupied_slot_id'] is None) or (current['status']=='completed' and current['cancelled_at'] is None and current['tutor_completed_at'] and current['occupied_slot_id']==ps),'participant terminal race metadata and occupancy coherent')
            else:
                print('NOTE: same-date participant terminal race has no eligible whole-hour lesson before 01:00 MYT; Admin terminal races still run.')
            cb,cs,ct=fixture();check(request(cb,ct)[0]==303,'cancel/change race fixture')
            results=race(cb,change(cb)['change_request_id'],[('cancel','student',user),('accept','tutor',tutor_user)])
            current=row(cb);history=change(cb)
            check(history['status'] in ['accepted','withdrawn'] and ((current['status']=='cancelled' and current['occupied_slot_id'] is None) or (current['status']=='accepted' and current['slot_id']==ct and current['occupied_slot_id']==ct)),'cancel/change race coherent terminal history and occupancy')
            check(all(r=='saved' or r.startswith('conflict:') for r in results),'cancel/change controlled outcomes')
            cb,cs,ct=fixture();sql("UPDATE bookings SET meeting_platform='Video',meeting_url='https://example.invalid/room' WHERE booking_id=?",[cb])
            results=race(cb,0,[('cancel','student',user),('arrange','tutor',tutor_user)])
            check(row(cb)['status']=='cancelled' and all(r=='saved' or r.startswith('conflict:') for r in results),'cancel/arrangement race coherent')
            cb,cs,ct=fixture();check(request(cb,ct)[0]==303,'material change completion race fixture');cid=change(cb)['change_request_id'];move_past(cb);original=row(cb)
            results=race(cb,cid,[('complete','student',user),('accept','tutor',tutor_user)])
            check(results[0]=='saved' and results[1].startswith('conflict:') and unchanged_contract(original,row(cb)) and change(cb)['status']=='pending','after-end material change cannot corrupt completion agreement')
            cb,cs,ct=fixture();check(request(cb,ct)[0]==303,'future material change race fixture');cid=change(cb)['change_request_id']
            results=race(cb,cid,[('complete','student',user),('accept','tutor',tutor_user)])
            check(results[0].startswith('conflict:') and results[1]=='saved' and row(cb)['student_completed_at'] is None and row(cb)['agreement_version']==2,'future material change applies; completion cannot cross schedule')
            sql('UPDATE bookings SET student_completed_at=UTC_TIMESTAMP() WHERE booking_id=?',[cb]);current=row(cb)
            check(request(cb,cs)[0]==409 and row(cb)==current,'recorded completion blocks changes even if clock or imported schedule appears future')
            current=row(cb)
            check(admin.request('admin/manage-bookings-closed.php?id='+str(cb),{'csrf_token':tokens[admin],'lifecycle_action':'close'})[0]==405 and row(cb)==current,'Admin closure stays deferred and read-only')
            check('No closing action is available' in admin.request('admin/manage-bookings-closed.php?id='+str(cb))[1],'Admin closure displays explicit deferred notice')
            check(sql('SELECT COUNT(*) n FROM payments')[0]['n']==0 and sql('SELECT COUNT(*) n FROM feedback')[0]['n']==0,'no payments, fees, refunds or feedback created')
            check(all(r['Msg_text']=='OK' for r in sql('CHECK TABLE bookings,booking_change_requests,availability_slots')),'temporary lifecycle tables healthy')
            print(f'PASS: {auth.CHECKS} booking lifecycle assertions; cancellation, completion, ownership, CSRF, calendar dates, history, occupancy and races.')
        finally:
            if server:server.terminate();server.wait(timeout=15);log.close()
            if created:php('drop');print('CLEANUP: dropped only task-created '+name)


if __name__=='__main__':main()
