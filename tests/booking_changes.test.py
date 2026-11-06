"""Booking change HTTP/MariaDB tests; disposable database only."""
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
    with tempfile.TemporaryDirectory(prefix='tmq-change-tests-') as temp:
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
            bid,old,target=fixture()
            before=row(bid);old_row=sql('SELECT * FROM availability_slots WHERE slot_id=?',[old])[0]
            check(student.request(page(student,bid)+'&change_action=create&slotId='+str(target))[0]==200 and not sql('SELECT * FROM booking_change_requests'),'GET cannot create')
            check(request(bid,target,csrf_token='bad')[0]==403 and not sql('SELECT * FROM booking_change_requests'),'create CSRF')
            for browser in [student2,tutor2]:
                check(request(bid,target,browser=browser)[0]==404 and browser.request(page(browser,bid))[0]==404,'unrelated participant denied create and view')
            check(request(bid,old)[0]==422,'no-op rejected')
            foreign=slot(tid2,10);past=slot(tid,-24*10);unavailable=slot(tid,12,status='unavailable')
            for invalid in [foreign,past,unavailable,999999]:check(request(bid,invalid)[0]==409,'invalid target rejected')
            check(request(bid,target,mode='both')[0]==422,'invalid actual mode')
            check(request(bid,target,reason='')[0]==422,'reason required')
            check(request(bid,target)[0]==303,'Student creates request')
            c=change(bid);cid=c['change_request_id']
            check(c['requested_by_user_id']==user and c['status']=='pending' and c['base_agreement_version']==1 and c['is_admin_correction']==0,'session identity and server values')
            check(c['original_slot_id']==old and c['requested_slot_id']==target and c['original_expected_amount']=='40.25' and c['proposed_expected_amount']=='80.50','original and proposed snapshot')
            check(row(bid)==before and not sql('SELECT booking_id FROM bookings WHERE occupied_slot_id=?',[target]),'pending leaves booking unchanged and target unheld')
            check(request(bid,target,browser=tutor)[0]==409 and len(sql('SELECT * FROM booking_change_requests WHERE booking_id=?',[bid]))==1,'one pending controlled')
            for browser,detail in [(student,sv),(tutor,tv)]:
                for path in [page(browser,bid),detail+'?id='+str(bid)]:
                    body=browser.request(path)[1]
                    check('Current at request' in body and 'Proposed' in body and 'RM 40.25' in body and 'RM 80.50' in body and 'Pending' in body and '&lt;script&gt;' in body,'current/proposed detail and escaped history')
            for browser,detail in [(student2,sv),(tutor2,tv)]:check(browser.request(detail+'?id='+str(bid))[0]==404,'foreign detail denied')
            for action in ['accept','reject']:
                check(respond(bid,cid,action,browser=student)[0]==403,'requester cannot respond')
                check(respond(bid,cid,action,csrf_token='bad')[0]==403,'response CSRF')
                check(respond(bid,cid,action,browser=tutor2)[0]==404,'foreign responder denied')
            check(respond(bid,cid,'withdraw')[0]==403,'counterpart cannot withdraw')
            check(tutor.request(page(tutor,bid)+'&change_action=accept&change_id='+str(cid))[0]==200 and row(bid)==before,'GET cannot accept')
            check(admin.request('admin/manage-bookings-view.php?id='+str(bid),{'change_action':'accept','change_id':cid})[0]==405,'Admin read-only')
            check(admin.request(te+'?id='+str(bid))[0]==403 and anon.request(se+'?id='+str(bid))[0]==303,'role and anonymous guards')
            sql("UPDATE tutors SET hourly_rate='50.00' WHERE tutor_id=?",[tid])
            sql('UPDATE bookings SET student_arranged_at=UTC_TIMESTAMP(),tutor_arranged_at=UTC_TIMESTAMP() WHERE booking_id=?',[bid])
            check(respond(bid,cid)[0]==303,'counterpart accepts schedule change')
            after=row(bid);accepted=change(bid)
            check(after['booking_id']==bid and after['slot_id']==target and after['occupied_slot_id']==target and after['status']=='accepted' and after['decision_at']==before['decision_at'],'same booking and Tutor acceptance preserved')
            check(after['hourly_rate_snapshot']=='40.25' and after['expected_amount']=='80.50','duration price uses immutable original rate despite current 50.00')
            check(after['agreement_version']==2 and after['student_arranged_at'] is None and after['tutor_arranged_at'] is None,'agreement version and confirmation reset')
            check(accepted['status']=='accepted' and accepted['responded_by_user_id']==tutor_user and accepted['responded_at'] is not None and accepted['pending_booking_id'] is None,'accepted metadata')
            check(sql('SELECT * FROM availability_slots WHERE slot_id=?',[old])[0]==old_row and not sql('SELECT booking_id FROM bookings WHERE occupied_slot_id=?',[old]),'old slot intact and released')
            reused=create_booking(old,browser=student2)
            check(row(reused)['occupied_slot_id']==old,'old slot reusable after reschedule')
            check(respond(bid,cid)[0]==409 and respond(bid,cid,'reject')[0]==409 and respond(bid,cid,'withdraw',browser=student)[0]==409,'stale responses controlled')
            check(row(bid)==after,'stale responses no mutation')
            # Tutor request; mode-only is explicitly independent of original slot mode.
            mb,ms,mt=fixture(mode='online'); m_before=row(mb)
            sql("UPDATE bookings SET meeting_platform='Existing platform',meeting_url='https://example.invalid/private',student_arranged_at=UTC_TIMESTAMP(),tutor_arranged_at=UTC_TIMESTAMP() WHERE booking_id=?",[mb])
            check(request(mb,ms,'physical',browser=tutor)[0]==303,'Tutor creates mode-only request on online slot')
            mc=change(mb);check(mc['requested_by_user_id']==tutor_user and mc['original_meeting_platform']=='Existing platform','Tutor identity and arrangement history')
            check(respond(mb,mc['change_request_id'],browser=student)[0]==303,'Student accepts Tutor request')
            mr=row(mb)
            check(mr['slot_id']==ms and mr['class_mode']=='physical' and mr['expected_amount']==m_before['expected_amount'] and mr['hourly_rate_snapshot']==m_before['hourly_rate_snapshot'],'mode-only identity interval price preserved')
            check(mr['meeting_platform'] is None and mr['meeting_url'] is None and mr['student_arranged_at'] is None and sql('SELECT mode FROM availability_slots WHERE slot_id=?',[ms])[0]['mode']=='online','clear inapplicable arrangement; preserve slot mode')
            check(request(mb,ms,'online')[0]==303 and respond(mb,change(mb)['change_request_id'])[0]==303,'reverse physical to online supported')
            # Rejection/withdrawal retain the agreement and history; new requests allowed afterward.
            rb,rs,rt=fixture(); unchanged=row(rb)
            check(request(rb,rt,browser=tutor)[0]==303,'Tutor schedule request')
            rc=change(rb)['change_request_id']
            check(respond(rb,rc,'reject',browser=tutor)[0]==403 and respond(rb,rc,'reject',browser=student)[0]==303,'only Student rejects Tutor request')
            check(row(rb)==unchanged and change(rb)['status']=='rejected' and change(rb)['responded_by_user_id']==user,'rejection history booking unchanged')
            check(request(rb,rt)[0]==303,'request after rejection')
            rc=change(rb)['change_request_id']
            check(respond(rb,rc,'withdraw',browser=student,csrf_token='bad')[0]==403,'withdrawal CSRF')
            check(respond(rb,rc,'withdraw',browser=student)[0]==303,'requester withdrawal')
            check(row(rb)==unchanged and change(rb)['status']=='withdrawn' and change(rb)['responded_at'] is not None and change(rb)['responded_by_user_id'] is None,'withdraw history metadata and unchanged booking')
            check(len(sql('SELECT * FROM booking_change_requests WHERE booking_id=?',[rb]))==2,'rejected and withdrawn history retained')
            check(request(rb,rt,'physical',browser=tutor)[0]==303,'combined schedule and mode proposal')
            rc=change(rb)['change_request_id']
            sql("UPDATE users SET account_status='inactive' WHERE user_id=?",[tutor_user])
            check(respond(rb,rc,browser=student)[0]==409 and row(rb)==unchanged,'active Student cannot accept inactive Tutor proposal')
            check(respond(rb,rc,'reject',browser=student)[0]==303 and row(rb)==unchanged,'rejection still closes pending request with inactive counterpart')
            sql("UPDATE users SET account_status='active' WHERE user_id=?",[tutor_user])
            check(request(rb,rt,'physical',browser=tutor)[0]==303 and respond(rb,change(rb)['change_request_id'],browser=student)[0]==303,'combined schedule and mode acceptance')
            check(row(rb)['slot_id']==rt and row(rb)['class_mode']=='physical' and row(rb)['expected_amount']==change(rb)['proposed_expected_amount'],'combined accepted agreement matches proposal')
            # Target remains bookable by another Student during pending request.
            tb,ts,tt=fixture();original=row(tb)
            check(request(tb,tt)[0]==303,'target race request')
            competing=create_booking(tt,browser=student2); competing_before=row(competing)
            result=respond(tb,change(tb)['change_request_id'])
            check(result[0]==409 and 'proposed slot is no longer available' in result[1] and 'SQLSTATE' not in result[1],'taken target controlled acceptance failure')
            check(row(tb)==original and row(competing)==competing_before and change(tb)['status']=='pending','failed acceptance preserves both bookings and pending proposal')
            # Overlap acquired after request; boundary adjacency remains legal.
            ob,oslot,ot=fixture();check(request(ob,ot)[0]==303,'overlap request')
            crossing=slot(tid2,offset+7,2);cross_id=create_booking(crossing,tid2)
            ob_before=row(ob)
            check(respond(ob,change(ob)['change_request_id'])[0]==409 and row(ob)==ob_before,'Student overlap at acceptance prevented')
            sql("UPDATE bookings SET status='rejected',rejection_reason='Fixture releases conflict' WHERE booking_id=?",[cross_id])
            adjacent=slot(tid2,offset+8,2);create_booking(adjacent,tid2)
            check(respond(ob,change(ob)['change_request_id'])[0]==303,'adjacent Student lesson allowed')
            # Acceptance revalidates capability, account status, availability and agreement version.
            vb,vs,vt=fixture();check(request(vb,vt,'physical')[0]==303,'revalidation request')
            vc=change(vb)['change_request_id'];vb_before=row(vb)
            for uid in [user,tutor_user]:
                sql("UPDATE users SET account_status='inactive' WHERE user_id=?",[uid])
                # Call with the other active participant: accept requires both accounts active.
                if uid==user:check(respond(vb,vc)[0]==409 and row(vb)==vb_before,'inactive Student acceptance blocked')
                else:
                    # The HTTP guard also blocks an inactive acting Tutor and logs them out.
                    check(respond(vb,vc)[0]==303 and row(vb)==vb_before,'inactive Tutor auth blocks action')
                sql("UPDATE users SET account_status='active' WHERE user_id=?",[uid])
                if uid==tutor_user:login(tutor,'bc_tutor');tokens[tutor]=tutor.token('tutor/tutor-availability-addNew.php')
            sql("UPDATE tutors SET teaching_mode='online' WHERE tutor_id=?",[tid])
            check(respond(vb,vc)[0]==422 and row(vb)==vb_before,'Tutor mode revalidated')
            sql("UPDATE tutors SET teaching_mode='both' WHERE tutor_id=?",[tid])
            sql("UPDATE availability_slots SET mode='online' WHERE slot_id=?",[vt])
            check(respond(vb,vc)[0]==422 and row(vb)==vb_before,'schedule target mode revalidated')
            sql("UPDATE availability_slots SET mode='both',availability_status='unavailable' WHERE slot_id=?",[vt])
            check(respond(vb,vc)[0]==409 and row(vb)==vb_before,'target status revalidated')
            sql("UPDATE availability_slots SET availability_status='available' WHERE slot_id=?",[vt])
            sql('UPDATE bookings SET agreement_version=agreement_version+1 WHERE booking_id=?',[vb])
            check(respond(vb,vc)[0]==409 and change(vb)['status']=='pending','stale agreement version blocked')
            # Every ineligible lifecycle state; past original and past proposed acceptance.
            eb,es,et=fixture()
            for state in ['requested','rejected','cancelled','completed','closed']:
                sql("UPDATE bookings SET status=?,rejection_reason='Fixture',student_completed_at=UTC_TIMESTAMP(),tutor_completed_at=UTC_TIMESTAMP() WHERE booking_id=?",[state,eb])
                check(request(eb,et)[0]==409,'ineligible '+state)
            sql("UPDATE bookings SET status='accepted',student_completed_at=NULL,tutor_completed_at=NULL WHERE booking_id=?",[eb])
            check(request(eb,et)[0]==303,'future original request')
            past_start=(epoch-timedelta(days=12)).strftime('%Y-%m-%d %H:%M:%S');past_end=(epoch-timedelta(days=12)+timedelta(hours=1)).strftime('%Y-%m-%d %H:%M:%S')
            sql('UPDATE availability_slots SET starts_at=?,ends_at=? WHERE slot_id=?',[past_start,past_end,es])
            sql('UPDATE bookings SET starts_at=?,ends_at=? WHERE booking_id=?',[past_start,past_end,eb])
            check(respond(eb,change(eb)['change_request_id'])[0]==409,'past original acceptance rejected')
            check(respond(eb,change(eb)['change_request_id'],'withdraw',browser=student)[0]==303 and request(eb,et)[0]==409,'past original create rejected; cleanup withdrawal allowed')
            pb,ps,pt=fixture();check(request(pb,pt)[0]==303,'future proposed request')
            sql('UPDATE availability_slots SET starts_at=?,ends_at=? WHERE slot_id=?',[(epoch-timedelta(days=11)).strftime('%Y-%m-%d %H:%M:%S'),(epoch-timedelta(days=11)+timedelta(hours=2)).strftime('%Y-%m-%d %H:%M:%S'),pt])
            check(respond(pb,change(pb)['change_request_id'])[0]==409,'past proposed acceptance rejected')
            # Foreign request ID must not be usable with an owned booking URL.
            check(respond(pb,cid)[0]==404,'request ID bound to booking')
            # Independent PHP/PDO workers start together after a barrier file, not serial HTTP.
            worker=temp/'changes.php'
            worker.write_text("""<?php require $argv[1].'/includes/auth.php'; require $argv[1].'/includes/booking-changes.php';
                $p=json_decode(stream_get_contents(STDIN),true); file_put_contents($p['ready'],'ready');
                while (!file_exists($p['go'])) { usleep(1000); }
                try { $actor=['role'=>$p['role'],'user_id'=>$p['user']];
                  if($p['action']==='create') { tmq_create_booking_change($actor,$p['id'],['slotId'=>(string)$p['target'],'classType'=>'online','reason'=>'Race']); }
                  else { tmq_respond_booking_change($actor,$p['id'],$p['cid'],$p['action'],'Race'); }
                  echo 'saved';
                } catch (DomainException $e) { echo $e->getMessage(); }""",encoding='utf-8')
            def race(bid,target,cid,actions):
                nonce=secrets.token_hex(4);go=temp/(nonce+'-go');processes=[]
                for i,(action,role,uid) in enumerate(actions):
                    ready=temp/(nonce+'-'+str(i));p=subprocess.Popen([PHP,str(worker),str(ROOT)],stdin=subprocess.PIPE,stdout=subprocess.PIPE,stderr=subprocess.PIPE,text=True,env=env,creationflags=flags)
                    p.stdin.write(json.dumps({'id':bid,'target':target,'cid':cid,'action':action,'role':role,'user':uid,'ready':str(ready),'go':str(go)}));p.stdin.close();p.stdin=None
                    processes.append((p,ready))
                try:
                    deadline=time.monotonic()+15
                    while not all(ready.exists() for p,ready in processes):
                        if time.monotonic()>deadline:raise RuntimeError('Worker barrier timeout')
                        time.sleep(.01)
                    go.write_text('go');results=[]
                    for p,_ in processes:
                        out,err=p.communicate(timeout=20)
                        if p.returncode:raise RuntimeError(err)
                        results.append(out)
                    return results
                finally:
                    for p,_ in processes:
                        if p.poll() is None:p.kill();p.wait()
            cb,cs,ct=fixture();cb_before=row(cb)
            results=race(cb,ct,0,[('create','student',user),('create','tutor',tutor_user)])
            check(results.count('saved')==1 and sum('pending change request' in r for r in results)==1,'concurrent pending creation one winner controlled loser')
            check(row(cb)==cb_before and len(sql('SELECT * FROM booking_change_requests WHERE booking_id=?',[cb]))==1,'concurrent creation no booking mutation')
            for actions in [('accept','accept'),('accept','reject'),('reject','reject'),('withdraw','accept'),('withdraw','reject')]:
                cb,cs,ct=fixture();check(request(cb,ct)[0]==303,'race request fixture');cc=change(cb);original=row(cb)
                results=race(cb,ct,cc['change_request_id'],[(a,'student' if a=='withdraw' else 'tutor',user if a=='withdraw' else tutor_user) for a in actions])
                check(results.count('saved')==1 and sum('already been updated' in r for r in results)==1,'race '+ '/'.join(actions)+' exactly one transition')
                final=row(cb);history=change(cb)
                check(history['status'] in ['accepted','rejected','withdrawn'] and history['responded_at'] is not None,'race decision metadata')
                if history['status']=='accepted':
                    check(final['slot_id']==ct and final['agreement_version']==2 and final['hourly_rate_snapshot']==original['hourly_rate_snapshot'] and final['expected_amount']==cc['proposed_expected_amount'],'atomic accepted race agreement')
                else:check(final==original,'nonaccepted race booking unchanged')
            check(sql('SELECT COUNT(*) n FROM payments')[0]['n']==0,'no later workflow records')
            check(sql('CHECK TABLE bookings,booking_change_requests,availability_slots')[-1]['Msg_text']=='OK','temporary tables healthy')
            print(f'PASS: {auth.CHECKS} booking change assertions; creation, history, acceptance, rejection, withdrawal, repricing, ownership and concurrent transitions.')
        finally:
            if server: server.terminate(); server.wait(timeout=15); log.close()
            if created: php('drop'); print('CLEANUP: dropped only task-created '+name)


if __name__ == '__main__': main()
