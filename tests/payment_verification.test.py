"""Payment review and final verification HTTP/MariaDB tests; disposable database only."""
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
    with tempfile.TemporaryDirectory(prefix='tmq-verification-tests-') as temp:
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
            def payment(bid):return (sql('SELECT * FROM payments WHERE booking_id=?',[bid]) or [None])[0]
            def submissions(bid):return sql('SELECT s.* FROM payment_submissions s JOIN payment_transfers t ON t.transfer_id=s.transfer_id JOIN payments p ON p.payment_id=t.payment_id WHERE p.booking_id=? ORDER BY s.submission_id',[bid])
            def transfers(bid):return sql('SELECT t.* FROM payment_transfers t JOIN payments p ON p.payment_id=t.payment_id WHERE p.booking_id=? ORDER BY t.transfer_id',[bid])
            def route(bid,kind='upload'):return 'student/student-payment-'+kind+'.php?id='+str(bid)
            def opened(bid,browser=student):return browser.request(route(bid),{'csrf_token':tokens[browser],'payment_action':'open','student_id':student2_id,'user_id':user2})
            def submit(bid,amount='4',browser=student,current=None,filename='receipt.pdf',content=PDF,mime='application/pdf',**extra):
                data={'csrf_token':tokens[browser],'payment_action':'correct' if current else 'transfer','payment_id':payment(bid)['payment_id'],'amount_paid':amount,
                      'transfer_date':'2026-10-01','payment_note':'Receipt <script>note</script>','bank_reference':'Bank <reference>',
                      'student_id':student2_id,'user_id':user2,'uploaded_by_user_id':user2,'status':'verified','tutor_status':'received','admin_status':'valid','expected_amount':'999.99'}
                if current:data.update(transfer_id=current['transfer_id'],submission_id=current['submission_id'])
                data.update(extra)
                return upload(browser,route(bid),data,'receipt',filename,content,mime)
            def new_payment(owner=student_id,browser=student):
                bid=fixture(owner);check(opened(bid,browser)[0]==303,'eligible payment fixture');return bid
            def receipt_fixture():
                bid=new_payment();check(submit(bid)[0]==303,'first evidence fixture');return bid,submissions(bid)[0]
            def stored():return sorted(str(p.relative_to(temp/'uploads')) for p in (temp/'uploads').rglob('*') if p.is_file())
            def download(browser,path):
                try:r=browser.opener.open(browser.base+path,timeout=15)
                except urllib.error.HTTPError as e:r=e
                return r.code,r.read(),r.headers
            tokens[tutor]=tutor.token('tutor/tutor-profile-edit.php')
            tokens[tutor2]=tutor2.token('tutor/tutor-profile-edit.php')
            tokens[admin]=admin.token('admin/admin-profile.php')
            def review_route(bid,browser):
                role='tutor' if browser in tutor_browsers else 'admin'
                return (role+'/tutor-payment-view.php' if role=='tutor' else role+'/manage-payments-view.php')+'?id='+str(bid)
            tutor_browsers=[tutor,tutor2]
            def decide(bid,receipt,decision,browser=tutor,revision=None,**extra):
                data={'csrf_token':tokens[browser],'payment_action':'tutor_review' if browser in tutor_browsers else 'admin_review',
                      'submission_id':receipt['submission_id'],'decision':decision,'evidence_revision':str(payment(bid)['evidence_revision'] if revision is None else revision),
                      'remark':'Private review <b>reason</b>','admin_id':'999999','tutor_id':tid2,'status':'verified','valid_total':'40.00'}
                data.update(extra)
                return browser.request(review_route(bid,browser),data)
            def verify(bid,browser=admin,revision=None,**extra):
                data={'csrf_token':tokens[browser],'payment_action':'verify','evidence_revision':str(payment(bid)['evidence_revision'] if revision is None else revision),
                      'valid_total':'40.00','expected_amount':'40.00','admin_id':'999999','reviewer_id':'999999'}
                data.update(extra)
                return browser.request(review_route(bid,browser),data)
            def accepted(bid,receipt):
                check(decide(bid,receipt,'received')[0]==303,'Tutor receives fixture transfer')
                check(decide(bid,receipt,'valid',admin)[0]==303,'Admin accepts fixture evidence')
            def totals(bid):
                result=subprocess.run([PHP,str(calculator),str(ROOT),str(bid)],env=env,capture_output=True,text=True,creationflags=flags)
                if result.returncode:raise RuntimeError(result.stdout+result.stderr)
                return json.loads(result.stdout)
            calculator=temp/'calculate.php'
            calculator.write_text('''<?php
require $argv[1].'/includes/db.php';require $argv[1].'/includes/payment-verification.php';
$b=tmq_profile_rows('SELECT * FROM bookings WHERE booking_id=?',[$argv[2]])[0];
$p=tmq_profile_rows('SELECT * FROM payments WHERE booking_id=?',[$argv[2]])[0];
echo json_encode(tmq_payment_settlement($b,$p,tmq_payment_current(tmq_db(),(int)$p['payment_id'])));
''',encoding='utf-8')
            # Protected current/history receipts and relationship checks.
            bid,receipt=receipt_fixture()
            for browser,path in [(tutor,'tutor/tutor-payment-file.php'),(admin,'admin/manage-payments-file.php'),(student,'student/student-payment-file.php')]:
                code,body,headers=download(browser,path+'?id='+str(receipt['submission_id']))
                check(code==200 and body==PDF and headers['Content-Type']=='application/pdf','authorized protected receipt '+path)
                check('no-store' in headers['Cache-Control'] and headers['X-Content-Type-Options']=='nosniff' and 'sandbox' in headers['Content-Security-Policy'] and 'attachment' in headers['Content-Disposition'],'protected receipt headers '+path)
                check(browser.request(path+'?id='+str(receipt['submission_id']),{})[0]==405,'file GET only '+path)
            check(download(tutor2,'tutor/tutor-payment-file.php?id='+str(receipt['submission_id']))[0]==404,'foreign Tutor receipt denied')
            check(download(student2,'student/student-payment-file.php?id='+str(receipt['submission_id']))[0]==404,'foreign Student receipt denied')
            for browser in [student,tutor,tutor2,anon]:
                check(browser.request('admin/manage-payments-view.php?id='+str(bid),{'payment_action':'verify'})[0] in [303,403],'Admin endpoint role/session protection')
            check(tutor2.request(review_route(bid,tutor2))[0]==404 and decide(bid,receipt,'received',tutor2)[0]==404,'foreign Tutor booking read/write denied')
            check('receipts/' not in tutor.request(review_route(bid,tutor))[1] and 'receipts/' not in admin.request(review_route(bid,admin))[1],'review HTML does not expose storage paths')
            check(verify(bid,tutor)[0]==403 and decide(bid,receipt,'valid',payment_action='admin_review')[0]==403,'Tutor cannot perform Admin evidence or final actions')
            before=payment(bid)
            check(decide(bid,receipt,'received',csrf_token='bad')[0]==403 and payment(bid)==before,'review requires CSRF')
            check(decide(bid,receipt,'received',revision='')[0]==409 and payment(bid)==before,'review requires evidence revision')
            check(totals(bid)['valid_total']=='0.00','awaiting pending contributes zero')
            check(decide(bid,receipt,'valid',admin)[0]==409,'Admin valid cannot precede Tutor received')
            check(decide(bid,receipt,'received')[0]==303,'current awaiting becomes received')
            after=payment(bid);current=submissions(bid)[-1]
            check(after['evidence_revision']==before['evidence_revision']+1 and after['status']=='pending' and current['tutor_reviewed_at'] and current['admin_status']=='pending' and row(bid)['status']=='completed','Tutor increments revision once without Admin acceptance, verification or closure')
            check(after['reviewed_by_admin_id'] is None and after['reviewed_at'] is None and after['admin_remark'] is None,'Tutor review clears aggregate metadata')
            check(totals(bid)['valid_total']=='0.00','received pending contributes zero')
            check(decide(bid,receipt,'issue',tutor_issue_type='not_received')[0]==409 and submissions(bid)[-1]==current,'received cannot be rewritten to issue')
            check(decide(bid,receipt,'valid',admin,revision=before['evidence_revision'])[0]==409,'Tutor review invalidates stale Admin form')
            check(decide(bid,receipt,'valid',admin)[0]==303,'Admin valid after received')
            check(payment(bid)['evidence_revision']==after['evidence_revision']+1 and payment(bid)['status']=='underpaid' and payment(bid)['reviewed_by_admin_id']==aid,'Admin decision increments once and records aggregate reviewer')
            check(submissions(bid)[-1]['reviewed_by_admin_id']==aid and submissions(bid)[-1]['admin_reviewed_at'],'Admin identity from session and review timestamp')
            check(totals(bid)['valid_total']=='4.00' and totals(bid)['outstanding']=='36.00' and not totals(bid)['ready'],'RM4 valid total and RM36 outstanding')
            check('Outstanding: RM 36.00' in student.request(route(bid,'view'))[1] and 'Private review' not in student.request(route(bid,'view'))[1],'Student sees underpayment without private remarks')
            frozen=submissions(bid)[-1];before=payment(bid)
            check(decide(bid,receipt,'rejected',admin)[0]==409 and submissions(bid)[-1]==frozen and payment(bid)==before,'Admin valid is immutable')
            check(verify(bid)[0]==409,'underpaid cannot final verify')
            # Genuine top-up uses a distinct transfer; final acceptance is still not final verification.
            check(submit(bid,'36')[0]==303,'genuine RM36 top-up')
            topup=submissions(bid)[-1]
            check(topup['transfer_id']!=receipt['transfer_id'] and totals(bid)['valid_total']=='4.00' and payment(bid)['status']=='pending','unreviewed top-up counts zero and resets aggregate pending')
            accepted(bid,topup)
            check(totals(bid)['valid_total']=='40.00' and totals(bid)['outstanding']=='0.00' and totals(bid)['ready'] and payment(bid)['status']=='pending' and row(bid)['status']=='completed','RM4 plus RM36 ready but not automatically verified')
            before=(payment(bid),row(bid),submissions(bid))
            check(admin.request(review_route(bid,admin)+'&payment_action=verify&evidence_revision='+str(payment(bid)['evidence_revision']))[0]==200 and before==(payment(bid),row(bid),submissions(bid)),'GET cannot verify')
            check(verify(bid,csrf_token='bad')[0]==403 and verify(bid,revision='')[0]==409,'final requires CSRF and revision')
            check(verify(bid,revision=payment(bid)['evidence_revision']-1)[0]==409,'stale final form rejected')
            check(verify(bid)[0]==303,'explicit exact-total final verification')
            finalp=payment(bid);finalb=row(bid)
            check(finalp['status']=='verified' and finalp['verified_by_admin_id']==aid and finalp['verified_at'] and finalb['status']=='closed' and finalb['closed_at']==finalp['verified_at'],'payment verified and booking closed with same timestamp and authenticated Admin')
            check(finalb['slot_id']==before[1]['slot_id'] and finalb['occupied_slot_id']==finalb['slot_id'] and finalb['student_completed_at']==before[1]['student_completed_at'] and finalb['tutor_completed_at']==before[1]['tutor_completed_at'],'historical slot, occupancy and completions retained')
            check(verify(bid)[0]==409 and decide(bid,topup,'rejected',admin)[0]==409 and decide(bid,topup,'issue',tutor_issue_type='not_received')[0]==409,'final state blocks repeated settlement and evidence changes')
            check(submit(bid)[0]==409 and submit(bid,current=topup)[0]==409 and finalp==payment(bid) and finalb==row(bid),'final state blocks Student evidence and preserves state')
            check('read-only' in student.request(route(bid,'view'))[1] and 'name="payment_action"' not in admin.request(review_route(bid,admin))[1] and 'name="payment_action"' not in tutor.request(review_route(bid,tutor))[1],'verified UI read-only for all roles')
            for path in ['admin/manage-payments-confirmed.php','admin/manage-bookings-closed.php','admin/manage-payments.php','tutor/tutor-payments.php']:
                browser=tutor if path.startswith('tutor') else admin
                check('Booking #'+str(bid) in browser.request(path)[1],'real payment/closed list '+path)
            check(admin.request('admin/manage-bookings-closed.php',{'csrf_token':tokens[admin],'payment_action':'verify'})[0]==405,'closed history has no closing mutation')
            # All issue enums; correction is a version of the same transfer.
            for issue in ['not_received','amount_mismatch','reference_mismatch']:
                cb,old=receipt_fixture();p=payment(cb)
                check(decide(cb,old,'issue',tutor_issue_type=issue)[0]==303,'Tutor issue '+issue)
                check(submissions(cb)[-1]['tutor_issue_type']==issue and payment(cb)['evidence_revision']==p['evidence_revision']+1 and totals(cb)['valid_total']=='0.00' and payment(cb)['status']=='pending','issue stored, revision changed, zero valid contribution')
                check(decide(cb,old,'received')[0]==409 and decide(cb,old,'valid',admin)[0]==409,'issue immutable and Admin valid prohibited')
                check(submit(cb,current=old)[0]==303,'Tutor issue permits Student same-transfer correction')
                latest=submissions(cb)[-1]
                check(latest['transfer_id']==old['transfer_id'] and latest['attempt_number']==2 and latest['tutor_status']=='awaiting' and latest['admin_status']=='pending' and len(transfers(cb))==1,'correction starts both reviews and preserves transfer identity')
                check(decide(cb,old,'received')[0]==409 and decide(cb,old,'rejected',admin)[0]==409,'historical receipts cannot be reviewed')
                for browser,path in [(admin,'admin/manage-payments-file.php'),(tutor,'tutor/tutor-payment-file.php')]:
                    check(download(browser,path+'?id='+str(old['submission_id']))[0]==200,'protected historical receipt remains accessible')
            cb,old=receipt_fixture();before=payment(cb)
            for issue in ['', 'incorrect', 'received']:
                check(decide(cb,old,'issue',tutor_issue_type=issue)[0]==422 and payment(cb)==before,'missing/invalid issue type denied')
            # Admin negatives can precede Tutor acknowledgement, and are one-way decisions.
            for tutor_status in ['awaiting','issue','received']:
                for decision in ['rejected','reupload_required']:
                    cb,old=receipt_fixture()
                    if tutor_status!='awaiting':check(decide(cb,old,tutor_status,tutor_issue_type='not_received')[0]==303,'negative-decision Tutor fixture')
                    before=payment(cb)
                    check(decide(cb,old,decision,admin,remark='  ')[0]==422 and payment(cb)==before,'negative Admin decision requires reason')
                    check(decide(cb,old,decision,admin)[0]==303,'Admin '+decision+' with Tutor '+tutor_status)
                    frozen=submissions(cb)[-1];after=payment(cb)
                    check(after['status']==decision and after['evidence_revision']==before['evidence_revision']+1 and totals(cb)['valid_total']=='0.00','negative decision aggregate, revision and zero contribution')
                    check(decide(cb,old,'valid',admin)[0]==409 and decide(cb,old,'received')[0]==409 and submissions(cb)[-1]==frozen,'negative Admin decisions freeze that version for both reviewers')
                    check(submit(cb,current=old)[0]==303 and submissions(cb)[0]==frozen and len(transfers(cb))==1,'negative decision correction retains historical review')
            # Highest attempt is canonical even for superseded previously valid evidence.
            cb,old=receipt_fixture();accepted(cb,old)
            sql('INSERT INTO payment_submissions (transfer_id,attempt_number,uploaded_by_user_id,amount_paid,file_path,original_filename,mime_type,file_size_bytes,file_sha256) SELECT transfer_id,2,uploaded_by_user_id,amount_paid,file_path,original_filename,mime_type,file_size_bytes,file_sha256 FROM payment_submissions WHERE submission_id=?',[old['submission_id']])
            check(totals(cb)['valid_total']=='0.00','superseded valid historical version immediately stops counting')
            latest=submissions(cb)[-1];accepted(cb,latest)
            check(totals(cb)['valid_total']=='4.00' and len(submissions(cb))==2 and len(transfers(cb))==1,'highest attempt counts exactly once, never RM8')
            # Aggregate precedence, unresolved blockers, then rejected plus valid exact settlement.
            cb,first=receipt_fixture();decide(cb,first,'rejected',admin)
            submit(cb,'40');second=submissions(cb)[-1]
            check(payment(cb)['status']=='pending' and not totals(cb)['ready'],'pending precedes rejected')
            check(decide(cb,second,'issue',tutor_issue_type='not_received')[0]==303 and totals(cb)['status']=='pending','Tutor issue/Admin pending is unresolved')
            check(decide(cb,second,'reupload_required',admin)[0]==303 and payment(cb)['status']=='reupload_required' and verify(cb)[0]==409,'reupload required precedes rejected and blocks final')
            submit(cb,'40',current=second);replacement=submissions(cb)[-1];accepted(cb,replacement)
            check(payment(cb)['status']=='rejected' and totals(cb)['ready'] and totals(cb)['valid_total']=='40.00','resolved rejected claim excluded while valid transfer settles exact fee')
            check(verify(cb)[0]==303 and submissions(cb)[0]['admin_status']=='rejected','final verification independent of pending aggregate and preserves rejected evidence')
            cb,first=receipt_fixture();decide(cb,first,'reupload_required',admin);submit(cb,'40');second=submissions(cb)[-1]
            decide(cb,second,'received');decide(cb,second,'valid',admin)
            check(payment(cb)['status']=='reupload_required' and totals(cb)['valid_total']=='40.00' and verify(cb)[0]==409,'reupload still blocks despite exact valid funds elsewhere')
            cb,first=receipt_fixture();submit(cb,'40');second=submissions(cb)[-1];accepted(cb,second)
            check(payment(cb)['status']=='pending' and totals(cb)['valid_total']=='40.00' and verify(cb)[0]==409,'pending claim blocks despite exact valid funds elsewhere')
            # Exact sen arithmetic at both boundaries; fake total never authorizes settlement.
            for amount in ['39.99','40.00','40.01','50.00']:
                cb=new_payment();submit(cb,amount);s=submissions(cb)[-1];accepted(cb,s)
                state=totals(cb);before=(payment(cb),row(cb));result=verify(cb)
                check(state['valid_total']==amount and result[0]==(303 if amount=='40.00' else 409),'exact total boundary '+amount)
                if amount!='40.00':check((payment(cb),row(cb))==before,'failed exact verification leaves both records unchanged '+amount)
                if amount in ['40.01','50.00']:check(state['status']=='pending' and 'manual' in state['message'],'overpayment stays nonfinal/manual '+amount)
            cb=new_payment()
            for amount in ['0.10','0.20','39.70']:
                submit(cb,amount);accepted(cb,submissions(cb)[-1])
            check(totals(cb)['valid_total']=='40.00' and verify(cb)[0]==303,'decimal tenths sum exactly without binary floating point')
            # Inconsistent valid chains and completion/finality preconditions fail safely.
            for tutor_status in ['awaiting','issue']:
                cb=new_payment();submit(cb,'40');s=submissions(cb)[-1]
                sql("UPDATE payment_submissions SET admin_status='valid',tutor_status=? WHERE submission_id=?",[tutor_status,s['submission_id']])
                check(totals(cb)['valid_total']=='0.00' and verify(cb)[0]==409 and decide(cb,s,'received')[0]==409,'inconsistent valid chain safely blocked '+tutor_status)
            for state in ['requested','accepted','rejected','cancelled','closed']:
                cb=new_payment();submit(cb,'40');accepted(cb,submissions(cb)[-1])
                sql('UPDATE bookings SET status=? WHERE booking_id=?',[state,cb]);before=payment(cb)
                check(verify(cb)[0]==409 and payment(cb)==before,'final requires completed booking, rejects '+state)
            for missing in ['student_completed_at','tutor_completed_at']:
                cb=new_payment();submit(cb,'40');accepted(cb,submissions(cb)[-1])
                # Approved CHECK forbids a completed booking with a missing confirmation.
                sql('UPDATE bookings SET status=\'accepted\','+missing+'=NULL WHERE booking_id=?',[cb])
                check(verify(cb)[0]==409,'missing completion denied '+missing)
            cb=new_payment()
            check(verify(cb)[0]==409,'no transfers cannot settle')
            sql('INSERT INTO payment_transfers (payment_id) VALUES (?)',[payment(cb)['payment_id']])
            check(verify(cb)[0]==409,'unexpected empty transfer blocks settlement')
            # Active-account enforcement runs on each HTTP request.
            cb,old=receipt_fixture();sql("UPDATE users SET account_status='inactive' WHERE user_id=?",[tutor_user])
            check(decide(cb,old,'received')[0] in [303,403] and submissions(cb)[-1]['tutor_status']=='awaiting','inactive Tutor cannot acknowledge')
            sql("UPDATE users SET account_status='active' WHERE user_id=?",[tutor_user]);login(tutor,'bc_tutor');tokens[tutor]=tutor.token('tutor/tutor-profile-edit.php')
            admin_user=sql('SELECT user_id FROM admins WHERE admin_id=?',[aid])[0]['user_id']
            sql("UPDATE users SET account_status='inactive' WHERE user_id=?",[admin_user])
            check(decide(cb,old,'rejected',admin)[0] in [303,403] and submissions(cb)[-1]['admin_status']=='pending','inactive Admin cannot review')
            sql("UPDATE users SET account_status='active' WHERE user_id=?",[admin_user]);login(admin,'bc_admin');tokens[admin]=admin.token('admin/admin-profile.php')
            # Two real PHP HTTP workers and independent authenticated sessions.
            with socket.socket() as sock:sock.bind(('127.0.0.1',0));port2=sock.getsockname()[1]
            other_log=open(temp/'server2.log','w')
            other_server=subprocess.Popen([PHP,'-d','display_errors=0','-d','session.save_path='+str(temp),'-S',f'127.0.0.1:{port2}','-t',str(ROOT)],env=env,stdout=other_log,stderr=other_log,creationflags=flags)
            for _ in range(50):
                try:auth.Browser(f'http://127.0.0.1:{port2}/').request('index.php');break
                except urllib.error.URLError:time.sleep(.1)
            parallel_tutor=auth.Browser(f'http://127.0.0.1:{port2}/');login(parallel_tutor,'bc_tutor');tutor_browsers.append(parallel_tutor);tokens[parallel_tutor]=parallel_tutor.token('tutor/tutor-profile-edit.php')
            parallel_admin=auth.Browser(f'http://127.0.0.1:{port2}/');login(parallel_admin,'bc_admin');tokens[parallel_admin]=parallel_admin.token('admin/admin-profile.php')
            parallel_student=auth.Browser(f'http://127.0.0.1:{port2}/');login(parallel_student,'bc_student');tokens[parallel_student]=parallel_student.token('student/student-profile-edit.php')
            import threading
            def race(actions):
                barrier=threading.Barrier(len(actions))
                def run(action):barrier.wait();return action()
                with concurrent.futures.ThreadPoolExecutor(max_workers=len(actions)) as pool:return list(pool.map(run,actions))
            cb,old=receipt_fixture();rev=payment(cb)['evidence_revision']
            results=race([lambda:decide(cb,old,'received',revision=rev),lambda:decide(cb,old,'issue',parallel_tutor,rev,tutor_issue_type='not_received')])
            check(sorted(r[0] for r in results)==[303,409] and payment(cb)['evidence_revision']==rev+1,'concurrent Tutor received vs issue has one winner')
            cb,old=receipt_fixture();rev=payment(cb)['evidence_revision']
            results=race([lambda:decide(cb,old,'received',revision=rev),lambda:submit(cb,current=old,browser=parallel_student)])
            check(sorted(r[0] for r in results)==[303,409] and payment(cb)['evidence_revision']==rev+1,'Tutor received vs Student correction serialized')
            cb,old=receipt_fixture();rev=payment(cb)['evidence_revision']
            results=race([lambda:decide(cb,old,'received',revision=rev),lambda:decide(cb,old,'rejected',parallel_admin,rev)])
            check(sorted(r[0] for r in results)==[303,409] and payment(cb)['evidence_revision']==rev+1,'Tutor vs Admin negative review serialized')
            cb,old=receipt_fixture();rev=payment(cb)['evidence_revision']
            results=race([lambda:decide(cb,old,'reupload_required',admin,rev),lambda:submit(cb,current=old,browser=parallel_student)])
            check(results[1][0]==303 and results[0][0] in [303,409] and submissions(cb)[-1]['attempt_number']==2 and submissions(cb)[-1]['admin_status']=='pending' and payment(cb)['evidence_revision']==rev+sum(r[0]==303 for r in results),'Admin negative review vs correction preserves historical decision and new pending evidence')
            cb,old=receipt_fixture();decide(cb,old,'received');rev=payment(cb)['evidence_revision']
            results=race([lambda:decide(cb,old,'valid',admin,rev),lambda:submit(cb,current=old,browser=parallel_student)])
            check(results[0][0]==303 and results[1][0]==409 and payment(cb)['evidence_revision']==rev+1,'Admin valid vs correction cannot supersede acknowledged evidence')
            cb,old=receipt_fixture();decide(cb,old,'received');rev=payment(cb)['evidence_revision']
            results=race([lambda:decide(cb,old,'valid',admin,rev),lambda:decide(cb,old,'rejected',parallel_admin,rev)])
            check(sorted(r[0] for r in results)==[303,409] and payment(cb)['evidence_revision']==rev+1,'two Admin evidence decisions have one winner')
            cb,old=receipt_fixture();decide(cb,old,'received');rev=payment(cb)['evidence_revision']
            results=race([lambda:decide(cb,old,'valid',admin,rev),lambda:submit(cb,'36',browser=parallel_student)])
            check(results[1][0]==303 and results[0][0] in [303,409] and payment(cb)['evidence_revision']==rev+sum(r[0]==303 for r in results) and len(transfers(cb))==2 and payment(cb)['status']=='pending','Admin review vs top-up yields coherent revision and pending aggregate')
            cb=new_payment();submit(cb,'40');s=submissions(cb)[-1];decide(cb,s,'received');rev=payment(cb)['evidence_revision']
            results=race([lambda:decide(cb,s,'valid',admin,rev),lambda:verify(cb,parallel_admin,rev)])
            check(results[0][0]==303 and results[1][0]==409 and payment(cb)['status']=='pending' and row(cb)['status']=='completed','Admin decision invalidates concurrent final form; no stale approval')
            rev=payment(cb)['evidence_revision'];results=race([lambda:verify(cb,admin,rev),lambda:verify(cb,parallel_admin,rev)])
            check(sorted(r[0] for r in results)==[303,409] and payment(cb)['status']=='verified' and row(cb)['status']=='closed','two final requests settle exactly once')
            cb=new_payment();submit(cb,'40');accepted(cb,submissions(cb)[-1]);rev=payment(cb)['evidence_revision']
            results=race([lambda:verify(cb,admin,rev),lambda:submit(cb,'1',browser=parallel_student)])
            check(sorted(r[0] for r in results)==[303,409] and (payment(cb)['status']=='verified')==(row(cb)['status']=='closed'),'final vs new transfer has one coherent winner')
            if results[1][0]==303:check(verify(cb,revision=rev)[0]==409,'new transfer invalidates old final revision')
            cb,old=receipt_fixture();decide(cb,old,'rejected',admin);submit(cb,'40');accepted(cb,submissions(cb)[-1]);rev=payment(cb)['evidence_revision']
            results=race([lambda:verify(cb,admin,rev),lambda:submit(cb,current=old,browser=parallel_student)])
            check(sorted(r[0] for r in results)==[303,409] and (payment(cb)['status']=='verified')==(row(cb)['status']=='closed'),'final vs reupload has one coherent winner')
            if results[1][0]==303:check(verify(cb,revision=rev)[0]==409,'reupload invalidates old final revision')
            # Force a genuine second-write failure with a disposable restricted DB account.
            # It can update payments but cannot update bookings; the first update must roll back.
            cb=new_payment();submit(cb,'40');accepted(cb,submissions(cb)[-1]);before=(payment(cb),row(cb))
            restricted='tmq_verify_'+secrets.token_hex(5);restricted_password=secrets.token_hex(18)
            sql("CREATE USER '"+restricted+"'@'127.0.0.1' IDENTIFIED BY '"+restricted_password+"'")
            try:
                sql('GRANT SELECT ON `'+name+"`.* TO '"+restricted+"'@'127.0.0.1'")
                sql('GRANT UPDATE ON `'+name+"`.payments TO '"+restricted+"'@'127.0.0.1'")
                other_server.terminate();other_server.wait(timeout=15);other_log.close();other_server=None
                other_log=open(temp/'restricted.log','w')
                other_server=subprocess.Popen([PHP,'-d','display_errors=0','-d','session.save_path='+str(temp),'-S',f'127.0.0.1:{port2}','-t',str(ROOT)],env=dict(env,TMQ_DB_USER=restricted,TMQ_DB_PASSWORD=restricted_password),stdout=other_log,stderr=other_log,creationflags=flags)
                for _ in range(50):
                    try:parallel_admin.request(review_route(cb,parallel_admin));break
                    except urllib.error.URLError:time.sleep(.1)
                code,body,_=verify(cb,parallel_admin)
                check(code==503 and 'SQLSTATE' not in body and 'PDOException' not in body and (payment(cb),row(cb))==before,'failure closing booking rolls back payment verification atomically without SQL diagnostics')
            finally:
                if other_server:other_server.terminate();other_server.wait(timeout=15);other_log.close();other_server=None
                sql("DROP USER '"+restricted+"'@'127.0.0.1'")
            check(all(r['Msg_text']=='OK' for r in sql('CHECK TABLE payments,payment_transfers,payment_submissions,bookings')),'payment tables healthy after races and rollback')
            print(f'PASS: {auth.CHECKS} payment verification assertions; protected receipts, reviews, exact totals, aggregate precedence, stale forms, atomic closure and HTTP concurrency.')
        finally:
            if other_server:other_server.terminate();other_server.wait(timeout=15);other_log.close()
            if server:server.terminate();server.wait(timeout=15);log.close()
            if created:php('drop');print('CLEANUP: dropped only task-created '+name+'; temporary receipt files removed with test directory.')


if __name__=='__main__':main()
