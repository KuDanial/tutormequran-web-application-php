"""Student payment submission HTTP/MariaDB tests; disposable database only."""
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
    with tempfile.TemporaryDirectory(prefix='tmq-payment-tests-') as temp:
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
            # GET never creates the payment or a transfer; explicit POST opens one record.
            bid=fixture();original=row(bid)
            check(student.request(route(bid)+'&payment_action=open')[0]==200 and payment(bid) is None,'eligible GET read-only')
            check(opened(bid)[0]==303 and opened(bid)[0]==303 and len(sql('SELECT * FROM payments WHERE booking_id=?',[bid]))==1,'one payment per completed booking; repeated open safe')
            p=payment(bid)
            check(p['status']=='pending' and p['evidence_revision']==1 and row(bid)==original and row(bid)['expected_amount']=='40.00','expected amount retained on booking; initial pending payment')
            for status in ['requested','accepted','rejected','cancelled','closed']:
                invalid=fixture(status=status)
                check(opened(invalid)[0]==409 and payment(invalid) is None,'cannot open payment for '+status)
            missing=fixture();sql('UPDATE bookings SET status=\'accepted\',student_completed_at=NULL WHERE booking_id=?',[missing])
            check(opened(missing)[0]==409,'missing completion cannot pay')
            foreign=fixture(student2_id)
            check(student.request(route(foreign))[0]==404 and opened(foreign)[0]==404,'foreign booking ownership')
            for browser in [tutor,admin,anon]:
                check(browser.request(route(bid),{'payment_action':'open','csrf_token':token})[0] in [303,403],'payment Student role guard')
            check(student.request(route(bid),{'payment_action':'open','csrf_token':'bad'})[0]==403,'open CSRF')
            check(student.request(route(bid,'view'),{'payment_action':'open','csrf_token':token})[0]==405,'view cannot mutate')
            check('not a finalized paid invoice' in student.request('student/student-invoices.php')[1],'invoice is charge summary, not fake paid invoice')
            # Snapshot QR and recipient are retained after Tutor changes their profile.
            qr='tutor/tutor-payment-qr.php';qr_token=tutor.token(qr)
            check(upload(tutor,qr,{'csrf_token':qr_token,'profile_action':'qr'},'duitnowQr','qr.png',PNG,'image/png')[0]==303,'real protected Tutor QR fixture')
            snap_bid=new_payment();snap=payment(snap_bid)
            sql("UPDATE tutors SET display_name='Changed name',hourly_rate=99.99,duitnow_qr_path=NULL WHERE tutor_id=?",[tid])
            check(opened(snap_bid)[0]==303 and payment(snap_bid)==snap and row(snap_bid)['expected_amount']=='40.00','recipient/QR snapshot and lesson price do not follow profile changes')
            qr_url='student/student-payment-file.php?kind=qr&id='+str(snap['payment_id'])
            check(download(student,qr_url)[0:2]==(200,PNG) and download(student2,qr_url)[0]==404,'owned snapshot QR only')
            # Money normalization is decimal-string based; no binary float or silent rounding.
            files_before=stored()
            for amount in ['0','0.00','-1','1.001','abc','1e2','1,000','NaN','.50','01.00','100000000','99999999.999']:
                check(submit(bid,amount)[0]==422 and not transfers(bid) and stored()==files_before,'invalid money rejected '+amount)
            for date in ['2026-02-30','2026-1-01','0000-01-01']:
                check(submit(bid,transfer_date=date)[0]==422 and not transfers(bid),'invalid bank date rejected')
            check(submit(bid,csrf_token='bad')[0]==403 and not transfers(bid),'receipt CSRF')
            check(submit(bid,browser=student2)[0]==404 and not transfers(bid),'foreign booking upload blocked')
            own2=new_payment(student2_id,student2)
            check(submit(bid,payment_id=payment(own2)['payment_id'])[0]==404 and not transfers(bid),'foreign payment ID blocked')
            for filename,content,mime in [('receipt.pdf',PDF,'application/pdf'),('receipt.jpg',JPEG,'image/jpeg'),('receipt.jpeg',JPEG,'image/jpeg'),('receipt.png',PNG,'image/png')]:
                check(submit(bid,'4',filename=filename,content=content,mime=mime)[0]==303,'receipt format '+filename)
                s=submissions(bid)[-1]
                check(s['attempt_number']==1 and s['amount_paid']=='4.00' and s['uploaded_by_user_id']==user and s['tutor_status']=='awaiting' and s['admin_status']=='pending' and s['file_sha256']==hashlib.sha256(content).hexdigest(),'receipt metadata and server-derived default reviews')
                check((temp/'uploads'/s['file_path']).read_bytes()==content and s['file_path'].startswith('receipts/'),'receipt stored protected, not BLOB')
                url='student/student-payment-file.php?id='+str(s['submission_id']);code,body,headers=download(student,url)
                check(code==200 and body==content and headers['X-Content-Type-Options']=='nosniff' and 'attachment' in headers['Content-Disposition'] and 'no-store' in headers['Cache-Control'],'owned secure download exact evidence')
                check(download(student2,url)[0]==404 and download(tutor,url)[0]==403 and download(admin,url)[0]==403 and download(anon,url)[0]==303,'receipt access scoped to Student owner; reviews deferred')
            check('Duplicate evidence warning' in student.request(route(bid,'view'))[1],'same hash/reference on another genuine claim warned, never verified')
            unchanged=submissions(bid);before=payment(bid);current=unchanged[-1]
            for action in ['transfer','correct']:
                check(student.request(route(bid)+'&payment_action='+action+'&submission_id='+str(current['submission_id']))[0]==200 and submissions(bid)==unchanged and payment(bid)==before,'GET never creates transfer or correction '+action)
            file_count=stored();sub_count=len(submissions(bid));transfer_count=len(transfers(bid));p_before=payment(bid)
            bad_files=[('too-big.pdf',PDF+b'x'*5242880,'application/pdf'),('receipt.php',b'<?php echo 1; ?>','application/pdf'),('receipt.png',PDF,'image/png'),('script.pdf',b'%PDF-1.4\n/JavaScript (x)\n%%EOF','application/pdf'),('fake.png',b'<?php echo 1;?>','image/png'),('broken.png',PNG[:-8],'image/png')]
            for filename,content,mime in bad_files:
                code,body,_=submit(bid,filename=filename,content=content,mime=mime)
                check(code==422 and stored()==file_count and len(submissions(bid))==sub_count and len(transfers(bid))==transfer_count and payment(bid)==p_before,'unsafe upload rollback '+filename)
            max_pdf=b'%PDF-1.4\n'+b' '* (5242880-len(b'%PDF-1.4\n')-len(b'\n%%EOF'))+b'\n%%EOF'
            check(submit(bid,'0.01',filename='limit.pdf',content=max_pdf)[0]==303 and submissions(bid)[-1]['file_size_bytes']==5242880,'exact 5 MB and smallest positive amount accepted')
            check(submit(bid,'99999999.99',transfer_date='')[0]==303 and submissions(bid)[-1]['amount_paid']=='99999999.99' and submissions(bid)[-1]['transfer_date'] is None,'DECIMAL maximum accepted; omitted bank date not inferred')
            check(submit(bid,'0.10',bank_reference='0',payment_note='0')[0]==303 and transfers(bid)[-1]['bank_reference']=='0' and submissions(bid)[-1]['payment_note']=='0','optional textual zero metadata is retained')
            body=student.request(route(bid,'view'))[1]
            check('&lt;script&gt;note&lt;/script&gt;' in body and '<script>note</script>' not in body and 'receipts/' not in body and str(temp) not in body,'Student output escaped; storage paths hidden')
            # Every valid combination in the explicit correction decision matrix.
            for ts in ['awaiting','issue','received']:
                for ads in ['pending','valid','rejected','reupload_required']:
                    cb,old=receipt_fixture();pid=payment(cb)['payment_id']
                    sql("UPDATE payment_submissions SET tutor_status=?,admin_status=?,tutor_remark='PRIVATE TUTOR',admin_remark='PRIVATE ADMIN',reviewed_by_admin_id=?,tutor_reviewed_at=UTC_TIMESTAMP(),admin_reviewed_at=UTC_TIMESTAMP() WHERE submission_id=?",[ts,ads,aid,old['submission_id']])
                    sql("UPDATE payments SET status='reupload_required',admin_remark='PRIVATE AGGREGATE',reviewed_by_admin_id=?,reviewed_at=UTC_TIMESTAMP() WHERE payment_id=?",[aid,pid])
                    old=submissions(cb)[0];before=payment(cb);old_transfer=transfers(cb)[0]
                    allowed=ads in ['rejected','reupload_required'] or (ads=='pending' and ts in ['awaiting','issue'])
                    response=submit(cb,'4.00',current=old)
                    check(response[0]==(303 if allowed else 409),'correction matrix '+ts+' / '+ads)
                    check(submissions(cb)[0]==old and transfers(cb)==[old_transfer],'old evidence/reviews and genuine transfer immutable')
                    if allowed:
                        current=submissions(cb)[-1];after=payment(cb)
                        check(current['transfer_id']==old['transfer_id'] and current['attempt_number']==2 and current['tutor_status']=='awaiting' and current['admin_status']=='pending' and current['reviewed_by_admin_id'] is None and current['admin_remark'] is None,'next version same transfer with fresh review states')
                        check(after['status']=='pending' and after['evidence_revision']==before['evidence_revision']+1 and after['admin_remark'] is None and after['reviewed_at'] is None and after['reviewed_by_admin_id'] is None,'new evidence resets aggregate and increments revision')
                        check(submit(cb,current=old)[0]==409 and len(submissions(cb))==2,'historical/noncurrent reupload denied')
                        view=student.request(route(cb,'view'))[1]
                        check('Current' in view and 'Historical' in view and 'Duplicate evidence warning' in view,'current/history and identical-file correction detectable')
                    else:check(submissions(cb)==[old] and payment(cb)==before,'blocked review state writes nothing')
                    body=student.request(route(cb,'view'))[1]
                    check('PRIVATE TUTOR' not in body and 'PRIVATE ADMIN' not in body and 'PRIVATE AGGREGATE' not in body,'private review notes not exposed')
            # Pure evaluator covers combinations deliberately excluded by DB CHECK constraints.
            matrix=temp/'matrix.php'
            matrix.write_text("""<?php require $argv[1].'/includes/auth.php';require $argv[1].'/includes/payments.php';
              $b=['status'=>'completed','student_completed_at'=>'x','tutor_completed_at'=>'x'];$p=['status'=>'pending'];
              foreach([['unknown','pending'],['awaiting','unknown'],['unknown','rejected']] as [$t,$a]){$r[]=tmq_payment_correction_allowed($b,$p,['submission_id'=>1,'tutor_status'=>$t,'admin_status'=>$a],1);}
              echo json_encode($r);""",encoding='utf-8')
            r=subprocess.run([PHP,str(matrix),str(ROOT)],env=env,capture_output=True,text=True,creationflags=flags)
            check(r.returncode==0 and json.loads(r.stdout)==[False,False,False],'unexpected state combinations safely deny')
            cb,old=receipt_fixture();sql("UPDATE payments SET status='verified',verified_by_admin_id=?,verified_at=UTC_TIMESTAMP() WHERE booking_id=?",[aid,cb]);before=payment(cb);files=stored()
            check(submit(cb,current=old)[0]==409 and submit(cb)[0]==409 and opened(cb)[0]==409 and payment(cb)==before and stored()==files,'verified payment locks corrections/new transfers/opening')
            cb,old=receipt_fixture();sql("UPDATE bookings SET status='closed',closed_at=UTC_TIMESTAMP() WHERE booking_id=?",[cb]);before=submissions(cb)
            check(submit(cb,current=old)[0]==409 and submit(cb)[0]==409 and submissions(cb)==before,'closed booking locks all evidence mutations')
            check(student.request(route(cb,'view'))[0]==200 and 'read-only' in student.request(route(cb))[1],'closed history remains readable')
            cb,old=receipt_fixture();foreign_transfer=submissions(bid)[0]
            check(submit(cb,current=foreign_transfer)[0]==404 and len(submissions(cb))==1,'foreign transfer ID rejected')
            check(submit(cb,current=old,submission_id=foreign_transfer['submission_id'])[0]==409 and len(submissions(cb))==1,'foreign submission ID cannot authorize correction')
            old_transfer=transfers(cb)[0]
            check(submit(cb,'5.5',current=old)[0]==303 and submissions(cb)[0]==old and submissions(cb)[-1]['amount_paid']=='5.50' and transfers(cb)==[old_transfer] and row(cb)['expected_amount']=='40.00','permitted corrected amount belongs only to next version of same transfer')
            # RM40 / RM4 / RM36: versions are not new money, valid evidence cannot be edited.
            money,first=receipt_fixture();check(submit(money,'4',current=first)[0]==303,'voluntary RM4 receipt correction')
            latest=submissions(money)[-1]
            check(len(transfers(money))==1 and latest['amount_paid']=='4.00' and row(money)['expected_amount']=='40.00','RM4 reupload is one transfer, never RM8')
            sql("UPDATE payment_submissions SET tutor_status='received',admin_status='valid' WHERE submission_id=?",[latest['submission_id']]);sql("UPDATE payments SET status='underpaid' WHERE booking_id=?",[money]);valid=submissions(money)[-1]
            check(submit(money,'36',current=latest)[0]==409 and submissions(money)[-1]==valid,'valid RM4 cannot be edited into missing RM36')
            check(submit(money,'36',bank_reference='Another bank transfer',filename='topup.png',content=PNG,mime='image/png')[0]==303,'genuine RM36 creates new transfer')
            current=sql('SELECT s.amount_paid FROM payment_submissions s JOIN payment_transfers t ON t.transfer_id=s.transfer_id JOIN payments p ON p.payment_id=t.payment_id WHERE p.booking_id=? AND s.attempt_number=(SELECT MAX(x.attempt_number) FROM payment_submissions x WHERE x.transfer_id=s.transfer_id) ORDER BY t.transfer_id',[money])
            check([x['amount_paid'] for x in current]==['4.00','36.00'] and len(transfers(money))==2 and submissions(money)[1]==valid,'RM4 + RM36 represented once each; valid earlier transfer unchanged')
            check(payment(money)['status']=='pending' and payment(money)['verified_at'] is None and row(money)['status']=='completed' and row(money)['closed_at'] is None and row(money)['expected_amount']=='40.00','Student does not verify totals/close/reprice')
            # Second independent PHP HTTP process; separate login sessions avoid PHP session serialization.
            with socket.socket() as sock:sock.bind(('127.0.0.1',0));port2=sock.getsockname()[1]
            other_log=open(temp/'server2.log','w')
            other_server=subprocess.Popen([PHP,'-d','upload_max_filesize=6M','-d','post_max_size=8M','-d','display_errors=0','-d','session.save_path='+str(temp),'-S',f'127.0.0.1:{port2}','-t',str(ROOT)],env=env,stdout=other_log,stderr=other_log,creationflags=flags)
            parallel=auth.Browser(f'http://127.0.0.1:{port2}/')
            for _ in range(50):
                try:parallel.request('index.php');break
                except urllib.error.URLError:time.sleep(.1)
            login(parallel,'bc_student');tokens[parallel]=parallel.token('student/student-profile-edit.php')
            import threading
            def race(actions):
                barrier=threading.Barrier(len(actions))
                def run(action):barrier.wait();return action()
                with concurrent.futures.ThreadPoolExecutor(max_workers=len(actions)) as pool:return list(pool.map(run,actions))
            cb=fixture();results=race([lambda:opened(cb),lambda:opened(cb,parallel)])
            check([r[0] for r in results]==[303,303] and len(sql('SELECT payment_id FROM payments WHERE booking_id=?',[cb]))==1,'concurrent opening produces one payment')
            before=payment(cb)
            results=race([lambda:submit(cb,'4'),lambda:submit(cb,'36',browser=parallel)])
            check([r[0] for r in results]==[303,303] and len(transfers(cb))==2 and len(submissions(cb))==2 and payment(cb)['evidence_revision']==before['evidence_revision']+2,'simultaneous genuine transfers commit coherent independent evidence')
            current=submissions(cb)[0];before=payment(cb);files=stored()
            results=race([lambda:submit(cb,'4',current=current),lambda:submit(cb,'4',browser=parallel,current=current)])
            check(sorted(r[0] for r in results)==[303,409] and len(submissions(cb))==3 and len(transfers(cb))==2 and payment(cb)['evidence_revision']==before['evidence_revision']+1 and len(stored())==len(files)+1,'concurrent corrections allocate one next attempt; stale loser writes no file')
            current=submissions(cb)[-1];before=payment(cb);old=submissions(cb)
            results=race([lambda:submit(cb,'4',current=current),lambda:submit(cb,'10',browser=parallel,bank_reference='Real topup')])
            check([r[0] for r in results]==[303,303] and len(transfers(cb))==3 and len(submissions(cb))==5 and payment(cb)['evidence_revision']==before['evidence_revision']+2,'top-up/correction race keeps transfer identities separate')
            check(submissions(cb)[:3]==old and len([x for x in submissions(cb) if x['transfer_id']==current['transfer_id']])==3,'concurrent history retained; no duplicate attempt numbers')
            # File storage failure cannot create financial records.
            failure=new_payment();before=payment(failure);receipts=temp/'uploads'/'receipts';saved=temp/'saved-receipts'
            receipts.rename(saved)
            try:
                receipts.write_text('block directory')
                code,body,_=submit(failure)
                check(code==503 and not transfers(failure) and not submissions(failure) and payment(failure)==before and 'SQLSTATE' not in body,'file storage failure leaves no partial payment evidence')
            finally:
                receipts.unlink();saved.rename(receipts)
            # A temporary restricted DB account forces a real INSERT failure after file storage.
            # No table/schema change, and no privileges/accounts touched on the real DB instance.
            restricted='tmq_payment_'+secrets.token_hex(5);restricted_password=secrets.token_hex(18)
            sql("CREATE USER '"+restricted+"'@'127.0.0.1' IDENTIFIED BY '"+restricted_password+"'")
            try:
                sql('GRANT SELECT ON `'+name+"`.* TO '"+restricted+"'@'127.0.0.1'")
                sql('GRANT INSERT ON `'+name+"`.payment_transfers TO '"+restricted+"'@'127.0.0.1'")
                sql('GRANT UPDATE ON `'+name+"`.payments TO '"+restricted+"'@'127.0.0.1'")
                other_server.terminate();other_server.wait(timeout=15);other_log.close();other_server=None
                restricted_env=dict(env,TMQ_DB_USER=restricted,TMQ_DB_PASSWORD=restricted_password)
                other_log=open(temp/'restricted-server.log','w')
                other_server=subprocess.Popen([PHP,'-d','display_errors=0','-d','session.save_path='+str(temp),'-S',f'127.0.0.1:{port2}','-t',str(ROOT)],env=restricted_env,stdout=other_log,stderr=other_log,creationflags=flags)
                for _ in range(50):
                    try:parallel.request(route(failure));break
                    except urllib.error.URLError:time.sleep(.1)
                files=stored();before=payment(failure)
                code,body,_=submit(failure,browser=parallel)
                check(code==503 and 'SQLSTATE' not in body and 'PDOException' not in body and not transfers(failure) and not submissions(failure) and payment(failure)==before and stored()==files,'DB failure rolls back transfer/receipt and removes only newly stored orphan file')
            finally:
                if other_server:other_server.terminate();other_server.wait(timeout=15);other_log.close();other_server=None
                sql("DROP USER '"+restricted+"'@'127.0.0.1'")
            # Existing review UI has no backend mutations; test posted attempts against real evidence.
            before=(payment(money),submissions(money),row(money))
            for browser,path in [(tutor,'tutor/tutor-payment-confirm.php'),(tutor,'tutor/tutor-payment-issue.php'),(admin,'admin/manage-payments-view.php'),(admin,'admin/manage-payments-confirmed.php')]:
                browser.request(path+'?id='+str(payment(money)['payment_id']),{'payment_action':'verify','decision':'received','status':'verified'})
                check((payment(money),submissions(money),row(money))==before,'deferred review page cannot mutate database '+path)
            for suffix in ['review','rejected','verified']:
                code,body,_=student.request(route(money,suffix))
                check(code==200 and 'Payment and receipt history' in body and 'payment verified and booking closed' not in body,'legacy Student route shows actual read-only state '+suffix)
            check(all(r['Msg_text']=='OK' for r in sql('CHECK TABLE payments,payment_transfers,payment_submissions,bookings')),'disposable payment tables healthy')
            print(f'PASS: {auth.CHECKS} payment submission assertions; uploads, ownership, correction matrix, versions, top-ups, duplicate evidence, concurrency and rollback.')
        finally:
            if other_server:other_server.terminate();other_server.wait(timeout=15);other_log.close()
            if server:server.terminate();server.wait(timeout=15);log.close()
            if created:php('drop');print('CLEANUP: dropped only task-created '+name+'; temporary receipt files removed with test directory.')


if __name__=='__main__':main()
