"""Persisted notification integration tests; disposable database and uploads only."""
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
    with tempfile.TemporaryDirectory(prefix='tmq-notifications-tests-') as temp:
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
            upload=profiles.upload;PDF=profiles.PDF
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
            import re
            actors={student:user,student2:user2,tutor:tutor_user,tutor2:tutor2_user}
            roles={student:'student',student2:'student',tutor:'tutor',tutor2:'tutor',admin:'admin'}
            def notices(bid=None,cert=None):
                where=' WHERE booking_id=?' if bid else ' WHERE certificate_id=?' if cert else ''
                return sql('SELECT * FROM notifications'+where+' ORDER BY notification_id',[bid or cert] if where else [])
            def event(bid,kind,recipients):
                rows=[n for n in notices(bid) if n['type']==kind]
                check(sorted(n['recipient_user_id'] for n in rows)==sorted(recipients),kind+' exact recipients/count')
                check(all(n['booking_id']==bid and n['certificate_id'] is None for n in rows),kind+' booking relation')
                return rows
            def center(browser):return roles[browser]+'/'+roles[browser]+'-notifications.php'
            def mark(browser,action='read',nid=None,**extra):
                data={'csrf_token':tokens[browser],'notification_action':action,'notification_id':nid or '',
                      'recipient_user_id':user2,'type':'forged','title':'FORGED','message':'FORGED','read_at':'2000-01-01','redirect':'https://evil.invalid'}
                data.update(extra);return browser.request(center(browser),data)
            def bookview(bid,browser=tutor):return roles[browser]+'/'+roles[browser]+'-booking-view.php?id='+str(bid)
            future_number=0
            def future(status='requested'):
                nonlocal future_number
                future_number+=1
                start=(datetime.now(timezone.utc)+timedelta(days=future_number+5)).replace(hour=0,minute=0,second=0,microsecond=0)
                times=[start.strftime('%Y-%m-%d %H:%M:%S'),(start+timedelta(hours=1)).strftime('%Y-%m-%d %H:%M:%S')]
                sql("INSERT INTO availability_slots (tutor_id,starts_at,ends_at,mode) VALUES (?,?,?,'both')",[tid]+times)
                sid=sql('SELECT slot_id FROM availability_slots WHERE tutor_id=? AND starts_at=?',[tid,times[0]])[0]['slot_id']
                check(student.request('student/student-booking-addNew.php',{'csrf_token':token,'tutor':tid,'slotId':sid,'classType':'online',
                      'recipient_user_id':user2,'type':'forged','title':'FORGED','message':'FORGED','booking_id':999999,'certificate_id':999999})[0]==303,'successful booking creation')
                bid=sql('SELECT booking_id FROM bookings WHERE slot_id=?',[sid])[0]['booking_id']
                if status=='accepted':check(booking_decision(bid)[0]==303,'accept fixture')
                return bid
            def booking_decision(bid,decision='accept',browser=tutor):
                return browser.request(bookview(bid,browser),{'csrf_token':tokens[browser],'decision':decision,'reason':'Private reason'})
            def change(bid,browser=student,action='create',**extra):
                data={'csrf_token':tokens[browser],'change_action':action,'reason':'Private change reason','classType':'physical','slotId':row(bid)['slot_id']}
                data.update(extra)
                return browser.request(roles[browser]+'/'+roles[browser]+'-booking-edit.php?id='+str(bid),data)
            def lifecycle(bid,browser,action):
                path=(roles[browser]+'-booking-' if roles[browser]!='admin' else 'manage-bookings-')+('cancel' if action=='cancel' else 'session')+'.php?id='+str(bid)
                return browser.request(roles[browser]+'/'+path,{'csrf_token':tokens[browser],'lifecycle_action':action,'reason':'Private cancellation reason','agreement_version':row(bid)['agreement_version']})
            def chat(bid,browser=student,**extra):
                data={'csrf_token':tokens[browser],'chat_action':'send','message_type':'text','body':'PRIVATE CHAT BODY',
                      'recipient_user_id':user2,'type':'forged','title':'FORGED','message':'FORGED','booking_id':999999}
                data.update(extra)
                return browser.request(roles[browser]+'/'+roles[browser]+'-chat.php?id='+str(bid),data)
            def cert_fixture():
                sql("INSERT INTO certificates (tutor_id,submission_purpose,certificate_name,certificate_year,file_path,original_filename,mime_type,file_size_bytes) VALUES (?,'higher_qualification','Private qualification',2024,'certificates/private-test.pdf','PRIVATE CERT.pdf','application/pdf',100)",[tid])
                return sql('SELECT MAX(certificate_id) id FROM certificates')[0]['id']
            def cert_review(cid,decision='approved',browser=admin):
                return browser.request('admin/manage-certificates-view.php?id='+str(cid),{'csrf_token':tokens[browser],'profile_action':'review','decision':decision,'certificateAdminRemark':'PRIVATE ADMIN CERT REMARK'})

            # Internal helper contract; test-only bridge outside document root.
            foundation=temp/'notification-helper.php'
            foundation.write_text('''<?php
require $argv[1].'/includes/notifications.php';
$a=json_decode(stream_get_contents(STDIN),true);$db=tmq_db();
try { if (!($a['no_transaction']??false)) $db->beginTransaction();
tmq_notification_insert($db,$a['recipient'],$a['type'],$a['booking']??null,$a['certificate']??null);
$db->commit();echo json_encode(['ok'=>true]);
} catch(Throwable $e) { if($db->inTransaction())$db->rollBack();echo json_encode(['ok'=>false]); }
''',encoding='utf-8')
            def helper(**data):
                r=subprocess.run([PHP,str(foundation),str(ROOT)],input=json.dumps(data),env=env,capture_output=True,text=True,creationflags=flags)
                if r.returncode:raise RuntimeError(r.stdout+r.stderr)
                return json.loads(r.stdout)['ok']
            b=fixture();c=cert_fixture()
            check(helper(recipient=user,type='booking_accepted',booking=b,title='FORGED',message='FORGED'),'helper allows approved event')
            n=notices(b)[0]
            check(n['title']=='Booking accepted' and n['message']=='Your Tutor accepted your booking.' and n['read_at'] is None,'fixed server text and initially unread')
            check(sql('SELECT ABS(TIMESTAMPDIFF(SECOND,created_at,UTC_TIMESTAMP())) age FROM notifications WHERE notification_id=?',[n['notification_id']])[0]['age']<10,'server UTC creation')
            check(helper(recipient=tutor_user,type='certificate_approved',certificate=c) and notices(cert=c)[0]['booking_id'] is None,'certificate FK only')
            for values in [dict(recipient=0),dict(type='evil'),dict(certificate=c),dict(booking=None),dict(no_transaction=True)]:
                data=dict(recipient=user,type='booking_accepted',booking=b);data.update(values);before=notices()
                check(not helper(**data) and notices()==before,'helper rejects invalid event/relation/transaction '+str(values))

            # Every successful domain transition; stale/no-op mutations add nothing.
            b=future();event(b,'booking_requested',[tutor_user])
            check(booking_decision(b)[0]==303,'booking accepted');event(b,'booking_accepted',[user])
            before=notices();check(booking_decision(b)[0]==409 and notices()==before,'stale acceptance creates no duplicate')
            rejected=future();check(booking_decision(rejected,'reject')[0]==303,'booking rejected');event(rejected,'booking_rejected',[user])
            for action in ['accept','reject','withdraw']:
                b=future('accepted');check(change(b)[0]==303,'Student proposes change')
                event(b,'booking_change_requested',[tutor_user])
                cid=sql('SELECT change_request_id FROM booking_change_requests WHERE booking_id=?',[b])[0]['change_request_id']
                responder=student if action=='withdraw' else tutor
                check(change(b,responder,action,change_id=cid)[0]==303,'change response '+action)
                kind={'accept':'accepted','reject':'rejected','withdraw':'withdrawn'}[action]
                event(b,'booking_change_'+kind,[tutor_user if action=='withdraw' else user])
                before=notices();check(change(b,responder,action,change_id=cid)[0]==409 and notices()==before,'stale change response')
            b=future('accepted');check(change(b,tutor)[0]==303,'Tutor proposes change');event(b,'booking_change_requested',[user])
            # Arrangement proposals use the same approved change-request event.
            b=future('accepted')
            check(student.request('student/student-booking-coordinate.php?id='+str(b),{'csrf_token':token,'change_action':'propose_arrangement',
                  'agreement_version':1,'meeting_platform':'Meet','meeting_url':'https://meet.example.invalid/private','reason':'Private arrangement'})[0]==303,'arrangement change request')
            event(b,'booking_change_requested',[tutor_user])
            for browser,recipients in [(student,[tutor_user]),(tutor,[user]),(admin,[user,tutor_user])]:
                b=future('accepted');check(lifecycle(b,browser,'cancel')[0]==303,'cancellation '+roles[browser]);event(b,'booking_cancelled',recipients)
            for first,second in [(student,tutor),(tutor,student)]:
                b=fixture(status='accepted');sql('UPDATE bookings SET student_completed_at=NULL,tutor_completed_at=NULL WHERE booking_id=?',[b])
                check(lifecycle(b,first,'complete')[0]==303 and row(b)['status']=='accepted','first completion confirmation')
                event(b,'booking_completion_needed',[actors[second]])
                before=notices();check(lifecycle(b,first,'complete')[0]==303 and notices()==before,'repeat first completion is idempotent')
                check(lifecycle(b,second,'complete')[0]==303 and row(b)['status']=='completed','second completion confirmation')
                event(b,'booking_completed',[actors[first]])

            b=fixture(status='requested')
            for browser in [student,tutor]:check(chat(b,browser)[0]==303,'chat send '+roles[browser])
            event(b,'chat_message',[user,tutor_user])
            check(chat(b,message_type='location',location_label='PRIVATE LOCATION',latitude='3.123456',longitude='101.123456')[0]==303,'location message')
            check(upload(student,'student/student-chat.php?id='+str(b),{'csrf_token':token,'chat_action':'send','message_type':'attachment'},'attachments[]','PRIVATE ATTACHMENT.pdf',PDF,'application/pdf')[0]==303,'attachment message')
            private=['PRIVATE CHAT BODY','PRIVATE LOCATION','3.123456','101.123456','PRIVATE ATTACHMENT.pdf']
            check(all(x not in json.dumps(notices(b)) for x in private),'chat content, filename and coordinates omitted')
            before=notices();check(chat(b,body='')[0]==422 and notices()==before,'failed chat creates no notice')

            b=new_payment();check(submit(b,'4')[0]==303,'first evidence');event(b,'payment_submitted',[tutor_user])
            receipt=submissions(b)[-1];check(decide(b,receipt,'issue',tutor,tutor_issue_type='not_received')[0]==303,'Tutor issue');event(b,'payment_tutor_issue',[user])
            check(submit(b,'4',current=receipt)[0]==303,'corrected receipt');event(b,'payment_submitted',[tutor_user,tutor_user])
            receipt=submissions(b)[-1];check(decide(b,receipt,'received')[0]==303,'Tutor received')
            n=event(b,'payment_tutor_received',[user])[0];check('verified' not in n['message'].lower(),'Tutor received does not claim verification')
            check(decide(b,receipt,'valid',admin)[0]==303,'Admin valid');n=event(b,'payment_admin_valid',[user])[0]
            check('verified' not in n['message'].lower() and 'still required' in n['message'],'evidence acceptance distinct from verification')
            check(submit(b,'36')[0]==303,'genuine top-up');event(b,'payment_submitted',[tutor_user]*3)
            accepted(b,submissions(b)[-1]);check(verify(b)[0]==303,'final verification');event(b,'payment_verified',[user,tutor_user])
            before=notices();check(verify(b)[0]==409 and notices()==before,'repeat verification creates no duplicates')
            for decision,kind in [('rejected','payment_admin_rejected'),('reupload_required','payment_reupload_required')]:
                b,receipt=receipt_fixture();check(decide(b,receipt,decision,admin)[0]==303,'Admin '+decision)
                n=event(b,kind,[user])[0]
                if decision=='reupload_required':check('same transfer' in n['message'] and 'another payment' not in n['message'],'same-transfer correction wording')
                before=notices();check(verify(b)[0]==409 and notices()==before,'unsatisfied final verification creates no notice')
            for decision in ['approved','rejected']:
                c=cert_fixture();check(cert_review(c,decision)[0]==303,'certificate '+decision)
                ns=notices(cert=c)
                check(len(ns)==1 and ns[0]['type']=='certificate_'+decision and ns[0]['recipient_user_id']==tutor_user and ns[0]['booking_id'] is None,'certificate exact recipient/type/relation')
                before=notices();cert_review(c,decision);check(notices()==before,'stale certificate review creates no duplicate')
            serialized=json.dumps(notices())
            check(all(x not in serialized for x in ['FORGED','Private review','Bank <reference>','receipts/','certificates/','PRIVATE ADMIN CERT REMARK','PRIVATE CERT.pdf']), 'server content excludes submitted text and private payment/certificate data')
            check(not sql("SELECT n.notification_id FROM notifications n JOIN users u ON u.user_id=n.recipient_user_id WHERE u.role='admin'"),'no Admin operational notification spam')

            # Concurrent workers run independent PHP processes with independent sessions.
            def start_server(server_env):
                with socket.socket() as sock:sock.bind(('127.0.0.1',0));p=sock.getsockname()[1]
                f=open(temp/('server-'+str(p)+'.log'),'w')
                proc=subprocess.Popen([PHP,'-d','display_errors=0','-d','session.save_path='+str(temp),'-S',f'127.0.0.1:{p}','-t',str(ROOT)],env=server_env,stdout=f,stderr=f,creationflags=flags)
                url=f'http://127.0.0.1:{p}/'
                for _ in range(50):
                    try:auth.Browser(url).request('index.php');break
                    except urllib.error.URLError:time.sleep(.1)
                return proc,f,url
            other_server,other_log,other_base=start_server(env)
            def peer(original,username,url=other_base):
                browser=auth.Browser(url);login(browser,username)
                roles[browser]=roles[original];actors[browser]=actors.get(original)
                tokens[browser]=browser.token('admin/admin-profile.php' if original==admin else center(browser))
                if roles[browser]=='tutor':tutor_browsers.append(browser)
                return browser
            s_peer=peer(student,'bc_student');t_peer=peer(tutor,'bc_tutor');a_peer=peer(admin,'bc_admin')
            def race(a,z):
                with concurrent.futures.ThreadPoolExecutor(2) as pool:
                    jobs=[pool.submit(a),pool.submit(z)];return [j.result()[0] for j in jobs]
            b=future();check(sorted(race(lambda:booking_decision(b),lambda:booking_decision(b,'reject',t_peer)))==[303,409],'competing booking decisions')
            check(len(notices(b))==2,'only winning booking decision adds notification')
            b=future('accepted');check(change(b)[0]==303,'race proposal')
            cid=sql('SELECT change_request_id FROM booking_change_requests WHERE booking_id=?',[b])[0]['change_request_id']
            check(sorted(race(lambda:change(b,tutor,'accept',change_id=cid),lambda:change(b,t_peer,'reject',change_id=cid)))==[303,409],'competing change responses')
            check(len(notices(b))==4,'only winning change response adds notification')
            b=fixture(status='accepted');sql('UPDATE bookings SET student_completed_at=NULL,tutor_completed_at=NULL WHERE booking_id=?',[b])
            check(race(lambda:lifecycle(b,student,'complete'),lambda:lifecycle(b,t_peer,'complete'))==[303,303],'concurrent completion')
            check(sorted(n['type'] for n in notices(b))==['booking_completed','booking_completion_needed'],'one notice for each completion transition')
            b=fixture(status='requested');check(race(lambda:chat(b),lambda:chat(b,t_peer))==[303,303],'concurrent genuine chat sends')
            event(b,'chat_message',[user,tutor_user])
            b=fixture(status='accepted');sql('UPDATE bookings SET tutor_completed_at=NULL WHERE booking_id=?',[b])
            check(sorted(race(lambda:lifecycle(b,tutor,'complete'),lambda:lifecycle(b,a_peer,'cancel')))==[303,409],'final completion competes with exceptional cancellation')
            if row(b)['status']=='completed':event(b,'booking_completed',[user])
            else:event(b,'booking_cancelled',[user,tutor_user])
            check(len(notices(b))==(1 if row(b)['status']=='completed' else 2),'losing lifecycle transition creates no notice')
            b,receipt=receipt_fixture()
            check(sorted(race(lambda:submit(b,'4',current=receipt),lambda:submit(b,'4',s_peer,current=receipt)))==[303,409],'competing receipt corrections')
            event(b,'payment_submitted',[tutor_user,tutor_user])
            check(race(lambda:submit(b,'2'),lambda:submit(b,'3',s_peer))==[303,303],'concurrent genuine additional transfers')
            event(b,'payment_submitted',[tutor_user]*4)
            b,receipt=receipt_fixture();check(sorted(race(lambda:decide(b,receipt,'received'),lambda:decide(b,receipt,'issue',t_peer,tutor_issue_type='not_received')))==[303,409],'competing Tutor evidence decisions')
            check(len(notices(b))==2,'only winning Tutor review adds notification')
            b=new_payment();check(submit(b,'40')[0]==303,'exact payment race fixture');receipt=submissions(b)[-1]
            check(decide(b,receipt,'received')[0]==303,'receive race evidence')
            check(sorted(race(lambda:decide(b,receipt,'valid',admin),lambda:decide(b,receipt,'rejected',a_peer)))==[303,409],'competing Admin evidence decisions')
            check(len(notices(b))==3,'only winning Admin review adds notification')
            b=new_payment();check(submit(b,'40')[0]==303,'exact final race fixture');accepted(b,submissions(b)[-1])
            check(sorted(race(lambda:verify(b),lambda:verify(b,a_peer)))==[303,409],'competing final verifications')
            event(b,'payment_verified',[user,tutor_user])
            c=cert_fixture();race(lambda:cert_review(c),lambda:cert_review(c,'rejected',a_peer))
            check(len(notices(cert=c))==1,'certificate review race has one winner notification')

            # Actual denied INSERT, with all preceding domain writes permitted.
            restricted='tmq_notice_'+secrets.token_hex(6);fault_server=None;fault_log=None
            try:
                sql("CREATE USER '"+restricted+"'@'127.0.0.1' IDENTIFIED BY 'temporary-test-only'")
                sql('GRANT SELECT ON `'+name+"`.* TO '"+restricted+"'@'127.0.0.1'")
                tables=sql('SELECT TABLE_NAME t FROM information_schema.TABLES WHERE TABLE_SCHEMA=?',[name])
                for table in tables:
                    privileges='UPDATE' if table['t']=='notifications' else 'INSERT,UPDATE'
                    sql('GRANT '+privileges+' ON `'+name+'`.`'+table['t']+"` TO '"+restricted+"'@'127.0.0.1'")
                fault_env=dict(env,TMQ_DB_USER=restricted,TMQ_DB_PASSWORD='temporary-test-only')
                fault_server,fault_log,fault_base=start_server(fault_env)
                fs=peer(student,'bc_student',fault_base);ft=peer(tutor,'bc_tutor',fault_base);fa=peer(admin,'bc_admin',fault_base)
                b=future();before=row(b);ns=notices()
                response=booking_decision(b,browser=ft)
                check(response[0]==503 and row(b)==before and notices()==ns,'notification INSERT failure rolls back booking acceptance')
                check('SQLSTATE' not in response[1] and restricted not in response[1],'controlled failure without SQL diagnostics')
                b=fixture(status='requested');check(chat(b,fs)[0]==503 and not sql('SELECT * FROM messages WHERE booking_id=?',[b]) and not notices(b),'notification failure rolls back chat INSERT')
                b,receipt=receipt_fixture();before=payment(b);ns=notices()
                check(decide(b,receipt,'received',ft)[0]==503 and payment(b)==before and submissions(b)[0]==receipt and notices()==ns,'notification failure rolls back evidence review and revision')
                check(decide(b,receipt,'received')[0]==303,'receive after fault');before=payment(b);sr=submissions(b);ns=notices()
                check(decide(b,receipt,'valid',fa)[0]==503 and payment(b)==before and submissions(b)==sr and notices()==ns,'notification failure rolls back Admin evidence review')
                b=new_payment();check(submit(b,'40')[0]==303,'final rollback fixture');accepted(b,submissions(b)[-1]);before=payment(b);br=row(b);ns=notices()
                check(verify(b,fa)[0]==503 and payment(b)==before and row(b)==br and notices()==ns,'notification failure rolls back final verification and booking closure')
                c=cert_fixture();before=sql('SELECT * FROM certificates WHERE certificate_id=?',[c])
                check(cert_review(c,browser=fa)[0]==503 and sql('SELECT * FROM certificates WHERE certificate_id=?',[c])==before and not notices(cert=c),'notification failure rolls back certificate review')
            finally:
                if fault_server:fault_server.terminate();fault_server.wait(timeout=15)
                if fault_log:fault_log.close()
                sql("DROP USER IF EXISTS '"+restricted+"'@'127.0.0.1'")

            # Generate enough genuine events for both centers to have a second page.
            b=fixture(status='requested')
            for sender,recipient in [(student,tutor_user),(tutor,user)]:
                count=sql('SELECT COUNT(*) n FROM notifications WHERE recipient_user_id=?',[recipient])[0]['n']
                responses=[chat(b,sender)[0] for _ in range(max(0,55-count))]
                check(all(code==303 for code in responses),'genuine chat events populate pagination fixture')
            # Listing is read-only, bounded, deterministic, recipient scoped and escaped.
            for browser in [student,tutor]:
                expected=sql('SELECT notification_id FROM notifications WHERE recipient_user_id=? ORDER BY created_at DESC,notification_id DESC LIMIT 50',[actors[browser]])
                before=notices();domains={table:sql('SELECT * FROM '+table) for table in ['bookings','availability_slots','booking_change_requests','payments','payment_transfers','payment_submissions','certificates','messages']}
                code,body,_=browser.request(center(browser)+'?notification_action=read_all&recipient_user_id='+str(user2))
                ids=[int(n) for n in re.findall(r'data-notification-id="(\d+)"',body)]
                check(code==200 and ids==[n['notification_id'] for n in expected],'own notifications in deterministic newest-first bounded order')
                check(notices()==before and domains=={table:sql('SELECT * FROM '+table) for table in domains},'GET has no domain or read-state side effects')
                count=sql('SELECT COUNT(*) n FROM notifications WHERE recipient_user_id=? AND read_at IS NULL',[actors[browser]])[0]['n']
                check(f'{count} unread' in body and 'Unread' in body,'recipient-scoped unread count and state')
                if count>50:
                    ids2=[int(n) for n in re.findall(r'data-notification-id="(\d+)"',browser.request(center(browser)+'?page=2')[1])]
                    expected2=sql('SELECT notification_id FROM notifications WHERE recipient_user_id=? ORDER BY created_at DESC,notification_id DESC LIMIT 50 OFFSET 50',[actors[browser]])
                    check(ids2==[n['notification_id'] for n in expected2] and not set(ids)&set(ids2),'second page and no overlap')
            # Both accounts have enough history to actually exercise pagination.
            check(len(sql('SELECT notification_id FROM notifications WHERE recipient_user_id=?',[user]))>50,'pagination fixture contains over 50 real Student events')
            own=sql('SELECT notification_id FROM notifications WHERE recipient_user_id=? LIMIT 1',[user])[0]['notification_id']
            foreign=sql('SELECT notification_id FROM notifications WHERE recipient_user_id=? LIMIT 1',[tutor_user])[0]['notification_id']
            before=notices();student.request(center(student)+'?notification_action=read&notification_id='+str(own))
            check(notices()==before,'mark-one requires POST, GET action ignored')
            before=notices();check(mark(student,nid=own,csrf_token='bad')[0]==403 and notices()==before,'mark-one CSRF required')
            check(mark(student,'read_all',csrf_token='bad')[0]==403 and notices()==before,'mark-all CSRF required')
            check(mark(student,nid=foreign)[0]==303 and notices()==before,'foreign notification ID cannot be read')
            response=mark(student,nid=own)
            check(response[0]==303 and response[2]['Location'].endswith('/student/student-notifications.php'),'fixed redirect ignores submitted URL')
            after=notices();check(next(n for n in after if n['notification_id']==own)['read_at'] is not None,'owned notice marked read')
            check(mark(student,nid=own)[0]==303 and notices()==after,'repeat mark read is idempotent')
            foreign_before=[n for n in notices() if n['recipient_user_id']!=user]
            check(mark(student,'read_all')[0]==303 and not sql('SELECT notification_id FROM notifications WHERE recipient_user_id=? AND read_at IS NULL',[user]),'mark-all reads own records')
            check([n for n in notices() if n['recipient_user_id']!=user]==foreign_before,'mark-all preserves everyone else')
            check('0 unread' in student.request(center(student))[1] and '>Read<' in student.request(center(student))[1],'read state and zero unread shown')
            check(anon.request(center(student))[0]==303 and anon.request(center(tutor))[0]==303,'guest centers denied')
            sql("UPDATE users SET account_status='inactive' WHERE user_id=?",[user2])
            check(student2.request(center(student2))[0]==303 and mark(student2,'read_all')[0]==303,'inactive account denied')
            check(tutor.request(center(student))[0]==403 and admin.request(center(tutor))[0]==403,'role guards preserved')

            # Safe destinations: inspect actual links from authorized, newly created events.
            b=future('accepted');chat(b);chat(b,tutor)
            for browser in [student,tutor]:
                body=browser.request(center(browser))[1];role=roles[browser]
                check(f'{role}-booking-view.php?id={b}' in body and f'{role}-chat.php?id={b}' in body,'role-correct booking/chat links')
            b=new_payment();submit(b,'40');accepted(b,submissions(b)[-1]);verify(b)
            for browser in [student,tutor]:check(f'{roles[browser]}-payment-view.php?id={b}' in browser.request(center(browser))[1],'role-correct payment link')
            c=cert_fixture();cert_review(c)
            check(f'tutor-certificate-view.php?certificate_id={c}' in tutor.request(center(tutor))[1],'qualification link')
            # Corrupt legacy data cannot grant relation access; only disposable records.
            foreign_booking=fixture(owner=student2_id)
            sql("INSERT INTO notifications (recipient_user_id,type,title,message,booking_id) VALUES (?,'chat_message','<script>PRIVATE XSS</script>','<img src=x onerror=alert(1)>',?)",[user,foreign_booking])
            body=student.request(center(student))[1]
            check('&lt;script&gt;PRIVATE XSS&lt;/script&gt;' in body and '<script>PRIVATE XSS</script>' not in body and '&lt;img' in body,'stored title/message escaped')
            check('student-chat.php?id='+str(foreign_booking) not in body and student.request('student/student-chat.php?id='+str(foreign_booking))[0]==404,'notice ownership cannot authorize foreign booking')
            sql("INSERT INTO notifications (recipient_user_id,type,title,message,certificate_id) VALUES (?,'certificate_approved','Foreign certificate','No link',?)",[tutor2_user,c])
            check('tutor-certificate-view.php?certificate_id='+str(c) not in tutor2.request(center(tutor2))[1] and tutor2.request('tutor/tutor-certificate-view.php?certificate_id='+str(c))[0]==404,'notice ownership cannot authorize foreign certificate')
            sql("INSERT INTO notifications (recipient_user_id,type,title,message,booking_id) VALUES (?,'https://evil.invalid','Unknown type','No arbitrary link',?)",[user,b])
            check('https://evil.invalid' not in student.request(center(student))[1],'unknown stored type cannot create arbitrary URL')
            print(f'PASS: {auth.CHECKS} notification assertions; event matrix, recipient privacy, centers, CSRF, links, concurrency and rollback.')
        finally:
            if other_server:other_server.terminate();other_server.wait(timeout=15)
            if other_log:other_log.close()
            if server:server.terminate();server.wait(timeout=15);log.close()
            if created:php('drop');print('CLEANUP: dropped only task-created '+name+'; temporary uploads removed.')


if __name__=='__main__':main()
