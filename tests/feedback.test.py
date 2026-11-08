"""Booking feedback integration tests; disposable database and uploads only."""
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
    with tempfile.TemporaryDirectory(prefix='tmq-feedback-tests-') as temp:
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
                sql("UPDATE users SET profile_picture_path='profiles/test.jpg' WHERE user_id=?",[uid])
                sql("INSERT INTO certificates (tutor_id,submission_purpose,certificate_name,qualification_level,certificate_year,file_path,original_filename,mime_type,file_size_bytes,status,reviewed_by_admin_id,reviewed_at) VALUES (?,'initial','Test qualification','ijazah',2024,'certificates/test.pdf','test.pdf','application/pdf',100,'approved',?,UTC_TIMESTAMP())",[tid,aid])
                cid=sql('SELECT certificate_id FROM certificates WHERE tutor_id=?',[tid])[0]['certificate_id']
                sql("UPDATE tutors SET displayed_certificate_id=?,profile_visibility='public',teaching_state='Selangor',teaching_area='Test area',teaching_mode='both',hourly_rate='40.25',bio='Test teaching' WHERE tutor_id=?",[cid,tid])
                sql('INSERT INTO tutor_languages VALUES (?,1)',[tid]);sql('INSERT INTO tutor_lesson_focuses VALUES (?,1)',[tid])
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
            def route(bid):return 'student/student-feedback-addNew.php?id='+str(bid)
            def history(bid,role='student'):return role+'/'+role+'-feedbacks.php?id='+str(bid)
            def saved(bid):return sql('SELECT * FROM feedback WHERE booking_id=?',[bid])
            def submit(bid,browser=student,**extra):
                data={'csrf_token':tokens.get(browser,''),'feedback_action':'submit','rating':'5','comment':'Ordinary review'}
                data.update(extra);return browser.request(route(bid),data)
            def snapshot():
                return {table:sql('SELECT * FROM '+table) for table in ['bookings','availability_slots','booking_change_requests','payments','payment_transfers','payment_submissions','messages','notifications']}
            b=fixture()
            check(student.request(route(b))[0]==200 and 'Submit feedback' in student.request(route(b))[1],'own eligible booking form')
            foreign=fixture(owner=student2_id)
            check(student.request(route(foreign))[0]==404 and submit(foreign)[0]==404,'foreign booking GET/POST denied')
            check(student.request(route(4294967295))[0]==404,'missing booking shares not-found response')
            check(anon.request(route(b))[0]==303 and submit(b,anon)[0]==303,'unauthenticated denied')
            check(tutor.request(route(b))[0]==403 and submit(b,tutor)[0]==403,'Tutor cannot author feedback')
            check(admin.request(route(b))[0]==403 and submit(b,admin)[0]==403,'Admin cannot author feedback')
            check(submit(b,csrf_token='bad')[0]==403 and not saved(b),'CSRF required')
            check(student.request(route(b)+'&feedback_action=submit&rating=5&comment=GET')[0]==200 and not saved(b),'GET cannot submit')
            check(student.request('student/student-feedbacks.php',{'csrf_token':tokens[student],'feedback_action':'submit'})[0]==405,'history is read-only')
            check(tutor.request('tutor/tutor-feedbacks.php',{'csrf_token':tokens[tutor],'feedback_action':'submit'})[0]==405,'Tutor history is read-only')
            for status,sd,td,label in [('accepted',False,False,'neither completion'),('accepted',True,False,'Student completion only'),('accepted',False,True,'Tutor completion only'),('requested',True,True,'requested'),('rejected',False,False,'rejected'),('cancelled',False,False,'cancelled'),('rejected',True,True,'rejected even with legacy timestamps'),('cancelled',True,True,'cancelled even with legacy timestamps')]:
                target=fixture(status=status,student_done=sd,tutor_done=td)
                before=snapshot()
                check('Submit feedback' not in student.request(route(target))[1] and submit(target)[0]==409 and not saved(target),label+' ineligible even after scheduled end')
                check(snapshot()==before,label+' causes no writes')
            for status in ['accepted','completed','closed']:
                target=fixture(status=status)
                before=snapshot()
                check(submit(target)[0]==303 and len(saved(target))==1,status+' both confirmations eligible without payment')
                check(snapshot()==before,status+' feedback has no domain side effects')
            for value in ['0','6','-1','2.5','1e0','abc','',' 5','5 ','01']:
                check(submit(b,rating=value)[0]==422 and not saved(b),'invalid rating rejected: '+repr(value))
            check(submit(b,**{'rating[]':'5','rating':''})[0]==422,'array rating rejected')
            missing={'csrf_token':tokens[student],'feedback_action':'submit','comment':'Missing rating'}
            check(student.request(route(b),missing)[0]==422 and not saved(b),'missing rating rejected')
            check(submit(b,comment='x'*65536)[0]==422 and not saved(b),'TEXT byte overflow rejected')
            check(submit(b,comment='😀'*16384)[0]==422 and not saved(b),'multibyte overflow rejected without truncation')
            array_comment={'csrf_token':tokens[student],'feedback_action':'submit','rating':'5','comment[]':'bad'}
            check(student.request(route(b),array_comment)[0]==422 and not saved(b),'array comment rejected')
            import urllib.request
            payload=('csrf_token='+tokens[student]+'&feedback_action=submit&rating=5&comment=%FF').encode('ascii')
            try:response=student.opener.open(base+route(b),payload,timeout=15)
            except urllib.error.HTTPError as error:response=error
            check(response.code==422 and not saved(b),'invalid UTF-8 rejected');response.close()
            raw='  Terima kasih — القرآن 😀\n<script>alert("stored")</script><img src=x onerror=alert(1)> & ordinary text  '
            before=snapshot()
            result=submit(b,rating='1',comment=raw,student_id=student2_id,tutor_id=tid2,user_id=user2,reviewer_id=user2,reviewee_id=tutor2_user,booking_id=foreign,role='admin',created_at='2000-01-01')
            check(result[0]==303 and result[2]['Location'].endswith('/student/student-feedbacks.php?id='+str(b)),'minimum rating accepted with fixed redirect')
            record=saved(b)[0]
            check(record['rating']==1 and record['comment']==raw.strip() and record['booking_id']==b and record['created_at'][:4]!='2000','UTF-8 preserved, trimmed, identity and timestamp server-derived')
            check(snapshot()==before,'feedback changes no booking, payment, availability, chat or notification data')
            check(not saved(foreign),'forged identity cannot assign foreign booking')
            check(submit(foreign,student_id=student_id,tutor_id=tid,user_id=user)[0]==404 and not saved(foreign),'fake participant IDs cannot authorize')
            escaped='&lt;script&gt;alert(&quot;stored&quot;)&lt;/script&gt;'
            for browser,page in [(student,history(b)),(student,route(b)),(tutor,history(b,'tutor'))]:
                status,body,_=browser.request(page)
                check(status==200 and escaped in body and '<script>alert("stored")</script>' not in body and 'القرآن 😀' in body and 'read-only' in body and 'name="rating"' not in body,'real immutable escaped feedback display: '+page)
            check(student2.request(history(b))[0]==404 and tutor2.request(history(b,'tutor'))[0]==404,'foreign history denied for both roles')
            for role,browser in [('student',student2),('tutor',tutor2)]:
                check('stored' not in browser.request(role+'/'+role+'-feedbacks.php')[1],'foreign feedback omitted from '+role+' history')
            check(submit(b,rating='5',comment='replacement')[0]==409 and saved(b)==[record],'repeated/stale POST cannot replace or duplicate')
            check('name="rating"' not in student.request(route(b)+'&page=2')[1] and escaped in student.request(route(b)+'&page=2')[1],'pagination cannot hide submitted feedback and reopen a form')
            for action in ['edit','delete','replace']:
                check(submit(b,feedback_action=action,feedback_id=record['feedback_id'])[0]==422 and saved(b)==[record],action+' unavailable')
            try:sql('INSERT INTO feedback (booking_id,rating) VALUES (?,5)',[b])
            except RuntimeError:duplicate_blocked=True
            else:duplicate_blocked=False
            check(duplicate_blocked and saved(b)==[record],'actual database unique key enforces one feedback per booking')
            maximum=fixture()
            check(submit(maximum,rating='5',comment='a'*65535)[0]==303 and len(saved(maximum)[0]['comment'])==65535,'rating maximum and exact TEXT byte limit accepted')
            blank=fixture()
            check(submit(blank,comment=' \r\n\t ')[0]==303 and saved(blank)[0]['comment'] is None,'optional whitespace comment stored NULL')
            omitted=fixture()
            check(student.request(route(omitted),{'csrf_token':tokens[student],'feedback_action':'submit','rating':'3'})[0]==303 and saved(omitted)[0]['comment'] is None,'optional missing comment accepted')
            pending=fixture()
            for page in ['student/student-bookings.php','student/student-booking-view.php?id='+str(pending),'student/student-booking-completed.php?id='+str(pending)]:
                check('student-feedback-addNew.php?id='+str(pending) in student.request(page)[1],'eligible booking UI link: '+page)
            check('student-feedback-addNew.php?id='+str(pending) in student.request('student/student-feedback-addNew.php')[1],'eligible chooser uses real bookings')
            sql("INSERT INTO payments (booking_id,recipient_name_snapshot,evidence_revision) VALUES (?,'Test recipient',7)",[pending])
            before=snapshot()
            check(submit(pending)[0]==303 and len(saved(pending))==1,'completed booking accepts feedback with an existing unverified payment')
            check(snapshot()==before,'existing payment status and evidence revision remain unchanged')
            for role,browser in [('student',student),('tutor',tutor)]:
                body=browser.request(role+'/'+role+'-booking-view.php?id='+str(b))[1]
                check(role+'-feedbacks.php?id='+str(b) in body and 'student-feedback-addNew.php?id='+str(b) not in body,'recorded booking feedback navigation for '+role)
            # Public profile uses eligible records only, without participant/booking details.
            public='tutor-detail.php?id='+str(tid)
            status,body,_=anon.request(public)
            expected=sql("SELECT AVG(f.rating) avg,COUNT(*) n FROM feedback f JOIN bookings b ON b.booking_id=f.booking_id WHERE b.tutor_id=? AND b.student_completed_at IS NOT NULL AND b.tutor_completed_at IS NOT NULL AND b.status IN ('accepted','completed','closed')",[tid])[0]
            check(status==200 and f"Average rating: {float(expected['avg']):.2f} / 5" in body and str(expected['n'])+' review(s)' in body,'real public aggregate')
            check(escaped in body and '<script>alert("stored")</script>' not in body and 'bc_student@example.invalid' not in body and 'Booking #' not in body,'public escaped reviews omit private identity/booking data')
            check('No feedback yet.' in anon.request('tutor-detail.php?id='+str(tid2))[1],'empty public rating has no fake aggregate')
            invalid=fixture(status='cancelled')
            sql("INSERT INTO feedback (booking_id,rating,comment) VALUES (?,1,'INELIGIBLE LEGACY REVIEW')",[invalid])
            check('INELIGIBLE LEGACY REVIEW' not in anon.request(public)[1] and f"Average rating: {float(expected['avg']):.2f} / 5" in anon.request(public)[1],'ineligible legacy feedback excluded from public reviews and aggregate')
            sql("UPDATE tutors SET profile_visibility='private' WHERE tutor_id=?",[tid])
            check(anon.request(public)[0]==404 and escaped not in anon.request(public)[1],'private Tutor profile does not expose reviews')
            sql("UPDATE tutors SET profile_visibility='public' WHERE tutor_id=?",[tid])
            sql("UPDATE users SET account_status='inactive' WHERE user_id=?",[user2])
            check(student2.request(route(foreign))[0]==303 and submit(foreign,student2)[0]==303 and not saved(foreign),'inactive Student denied')
            sql("UPDATE users SET account_status='inactive' WHERE user_id=?",[tutor2_user])
            check(tutor2.request('tutor/tutor-feedbacks.php')[0]==303,'inactive Tutor denied')

            # Independent HTTP workers and sessions avoid PHP session serialization.
            with socket.socket() as sock:sock.bind(('127.0.0.1',0));peer_port=sock.getsockname()[1]
            other_log=open(temp/'peer.log','w')
            other_server=subprocess.Popen([PHP,'-d','display_errors=0','-d','session.save_path='+str(temp),'-S',f'127.0.0.1:{peer_port}','-t',str(ROOT)],env=env,stdout=other_log,stderr=other_log,creationflags=flags)
            peer_base=f'http://127.0.0.1:{peer_port}/'
            for _ in range(50):
                try:auth.Browser(peer_base).request('index.php');break
                except urllib.error.URLError:time.sleep(.1)
            peer=auth.Browser(peer_base);login(peer,'bc_student');tokens[peer]=peer.token('student/student-profile-edit.php')
            admin_peer=auth.Browser(peer_base);login(admin_peer,'bc_admin');tokens[admin_peer]=admin_peer.token('admin/admin-profile.php')
            # Hold the row while both requests arrive, then release. This also tests
            # revalidation after a booking becomes ineligible while a form is stale.
            locker=temp/'lock.php'
            locker.write_text('''<?php
require $argv[1].'/includes/db.php';$db=tmq_db();$db->beginTransaction();
$q=$db->prepare('SELECT booking_id FROM bookings WHERE booking_id=? FOR UPDATE');$q->execute([$argv[2]]);
if(($argv[3]??'')==='cancel'){$q=$db->prepare("UPDATE bookings SET status='cancelled' WHERE booking_id=?");$q->execute([$argv[2]]);}
echo "ready\\n";flush();fgets(STDIN);$db->commit();
''',encoding='utf-8')
            def race(bid,a,z=None,cancel=False):
                holder=subprocess.Popen([PHP,str(locker),str(ROOT),str(bid),'cancel' if cancel else ''],env=env,stdin=subprocess.PIPE,stdout=subprocess.PIPE,stderr=subprocess.PIPE,text=True,creationflags=flags)
                try:
                    if holder.stdout.readline().strip()!='ready':raise RuntimeError('lock holder failed')
                    with concurrent.futures.ThreadPoolExecutor(2) as pool:
                        jobs=[pool.submit(a)]+([pool.submit(z)] if z else [])
                        # Wait for the application row-lock requests, bounded to 5s.
                        expected_waiters=2 if z else 1
                        for _ in range(50):
                            waits=sql('SELECT COUNT(DISTINCT w.requesting_trx_id) n FROM information_schema.INNODB_LOCK_WAITS w JOIN information_schema.INNODB_TRX t ON t.trx_id=w.requesting_trx_id JOIN information_schema.PROCESSLIST p ON p.ID=t.trx_mysql_thread_id WHERE p.DB=?',[name])[0]['n']
                            if waits>=expected_waiters:break
                            time.sleep(.1)
                        holder.communicate('\n',timeout=10)
                        results=[job.result() for job in jobs]
                    check(waits>=expected_waiters,'concurrent requests reached database lock wait')
                    return results
                finally:
                    if holder.poll() is None:holder.communicate('\n',timeout=10)
            race_booking=fixture()
            before=snapshot()
            results=race(race_booking,lambda:submit(race_booking,comment='first'),lambda:submit(race_booking,peer,comment='second'))
            check(sorted(r[0] for r in results)==[303,409] and len(saved(race_booking))==1,'concurrent duplicate has exactly one winner')
            check(snapshot()==before,'duplicate race has no unrelated side effects')
            stale=fixture(status='accepted')
            result=race(stale,lambda:submit(stale),cancel=True)[0]
            check(result[0]==409 and not saved(stale) and 'name="rating"' not in result[1],'stale form rechecks current ineligible booking after waiting and removes action')
            # Ready-to-verify disposable payment; real Admin endpoint runs concurrently.
            payment_booking=fixture()
            sql("INSERT INTO payments (booking_id,recipient_name_snapshot) VALUES (?,'Test tutor')",[payment_booking])
            pid=sql('SELECT payment_id FROM payments WHERE booking_id=?',[payment_booking])[0]['payment_id']
            sql('INSERT INTO payment_transfers (payment_id) VALUES (?)',[pid])
            transfer=sql('SELECT transfer_id FROM payment_transfers WHERE payment_id=?',[pid])[0]['transfer_id']
            sql("INSERT INTO payment_submissions (transfer_id,attempt_number,uploaded_by_user_id,amount_paid,file_path,original_filename,mime_type,file_size_bytes,file_sha256,tutor_status,admin_status,reviewed_by_admin_id) VALUES (?,1,?,40,'receipts/test.pdf','test.pdf','application/pdf',100,?,'received','valid',?)",[transfer,user,'a'*64,aid])
            original=sql('SELECT * FROM bookings WHERE booking_id=?',[payment_booking])[0]
            evidence=sql('SELECT * FROM payment_submissions WHERE transfer_id=?',[transfer])
            def verify():return admin_peer.request('admin/manage-payments-view.php?id='+str(payment_booking),{'csrf_token':tokens[admin_peer],'payment_action':'verify','evidence_revision':'1'})
            results=race(payment_booking,lambda:submit(payment_booking),verify)
            check([r[0] for r in results]==[303,303] and len(saved(payment_booking))==1,'feedback and payment verification both succeed concurrently')
            current=sql('SELECT * FROM bookings WHERE booking_id=?',[payment_booking])[0]
            check(current['status']=='closed' and all(current[k]==v for k,v in original.items() if k not in ['status','closed_at','updated_at']),'only verification closure changes booking')
            payment=sql('SELECT * FROM payments WHERE payment_id=?',[pid])[0]
            check(payment['status']=='verified' and payment['evidence_revision']==1 and sql('SELECT * FROM payment_submissions WHERE transfer_id=?',[transfer])==evidence,'verification preserved evidence and revision during feedback race')
            notices=sql('SELECT * FROM notifications WHERE booking_id=?',[payment_booking])
            check(len(notices)==2 and all(n['type']=='payment_verified' for n in notices),'only approved payment notifications, no feedback event')
            before=snapshot()
            check(submit(payment_booking)[0]==409 and snapshot()==before,'duplicate feedback preserves verified payment, closure and existing notifications')
            check('SQLSTATE' not in ''.join(r[1] for r in results),'no SQL diagnostics exposed')
            failed_booking=fixture()
            before=snapshot()
            sql("CREATE TRIGGER feedback_test_failure BEFORE INSERT ON feedback FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Private database diagnostic'")
            try:
                status,body,_=submit(failed_booking)
                check(status==503 and 'SQLSTATE' not in body and 'Private database diagnostic' not in body and not saved(failed_booking) and snapshot()==before,'database failure rolls back and returns controlled error without diagnostics')
            finally:sql('DROP TRIGGER feedback_test_failure')
            print(f'PASS: {auth.CHECKS} feedback assertions; authorization, validation, completion, immutability, public display, concurrency and side effects.')
        finally:
            if other_server:other_server.terminate();other_server.wait(timeout=15)
            if other_log:other_log.close()
            if server:server.terminate();server.wait(timeout=15);log.close()
            if created:php('drop');print('CLEANUP: dropped only task-created '+name+'; temporary files removed.')


if __name__=='__main__':main()
