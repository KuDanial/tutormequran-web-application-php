"""Booking coordination HTTP/MariaDB tests; disposable database only."""
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
    with tempfile.TemporaryDirectory(prefix='tmq-coordination-tests-') as temp:
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
            sc='student/student-booking-coordinate.php';tc='tutor/tutor-booking-coordinate.php'
            def coordinate(browser,bid):return (sc if browser in [student,student2] else tc)+'?id='+str(bid)
            def propose(bid,browser=student,**values):
                data={'csrf_token':tokens[browser],'change_action':'propose_arrangement','agreement_version':row(bid)['agreement_version'],
                      'venue_address':'  UiTM Library <script>test</script>  ','meeting_platform':'Community Video','meeting_url':'https://example.invalid/meeting?a=1&b=2',
                      'reason':'Arrange <b>lesson</b>','student_id':student2_id,'tutor_id':tid2,'user_id':user2,'requested_by_user_id':user2,'slotId':'999999','classType':'forged'}
                data.update(values)
                return browser.request(coordinate(browser,bid),data)
            def confirm(bid,browser=student,**values):
                data={'csrf_token':tokens[browser],'change_action':'confirm_arrangement','agreement_version':row(bid)['agreement_version'],
                      'user_id':user2,'student_id':student2_id,'tutor_id':tid2,'student_arranged_at':'2000-01-01 00:00:00','tutor_arranged_at':'2000-01-01 00:00:00'}
                data.update(values)
                return browser.request(coordinate(browser,bid),data)
            def decide(bid,action='accept',browser=tutor,**values):
                data={'csrf_token':tokens[browser],'change_action':action,'change_id':change(bid)['change_request_id'],'response_note':'Reviewed <b>proposal</b>'}
                data.update(values)
                return browser.request(coordinate(browser,bid),data)
            def physical_fixture():
                bid,old,target=fixture()
                sql("UPDATE bookings SET class_mode='physical' WHERE booking_id=?",[bid])
                return bid,old,target
            bid,old,target=physical_fixture();before=row(bid)
            for browser in [student,tutor]:
                status,body,_=browser.request(coordinate(browser,bid))
                check(status==200 and 'Current arrangement' in body and 'Not yet agreed' in body and 'name="venue_address"' in body,'owned physical coordination page')
                check('name="meeting_url"' not in body and 'data-booking-chat' not in body and 'booking-preview.js' not in body,'no irrelevant physical inputs or demo chat')
            for browser in [student2,tutor2]:
                check(browser.request(coordinate(browser,bid))[0]==404 and propose(bid,browser=browser)[0]==404 and confirm(bid,browser=browser)[0]==404,'unrelated coordinate/propose/confirm denied')
            check(admin.request(sc+'?id='+str(bid))[0]==403 and admin.request(tc+'?id='+str(bid),{'change_action':'confirm_arrangement'})[0]==403,'Admin participant endpoints denied')
            check(anon.request(sc+'?id='+str(bid))[0]==303,'anonymous coordination denied')
            for action in ['propose_arrangement','confirm_arrangement']:
                check(student.request(coordinate(student,bid)+'&change_action='+action+'&agreement_version=1')[0]==200 and row(bid)==before,'GET cannot mutate '+action)
            check(not sql('SELECT * FROM booking_change_requests'),'GET creates no proposal')
            check(propose(bid,csrf_token='bad')[0]==403 and confirm(bid,csrf_token='bad')[0]==403,'proposal and confirmation CSRF')
            check(confirm(bid)[0]==422 and row(bid)==before,'physical confirmation requires agreed venue')
            for venue in ['', '   ', 'x'*501]:check(propose(bid,venue_address=venue)[0]==422,'physical venue required and length bounded')
            check(propose(bid)[0]==303,'Student initial physical proposal')
            pc=change(bid)
            check(row(bid)==before and pc['requested_by_user_id']==user and pc['requested_slot_id']==old and pc['requested_mode']=='physical','pending proposal preserves agreement and ignores forged identity/slot/mode')
            check(pc['requested_venue_address']=='UiTM Library <script>test</script>' and pc['requested_meeting_platform'] is None and pc['requested_meeting_url'] is None,'trim venue and ignore irrelevant online input')
            check(propose(bid,browser=tutor)[0]==409 and request(bid,target)[0]==409,'one pending arrangement blocks arrangement and schedule')
            check(confirm(bid)[0]==422,'unaccepted proposal cannot satisfy completeness')
            check(decide(bid,browser=student)[0]==403 and decide(bid,csrf_token='bad')[0]==403,'requester cannot accept and accept requires CSRF')
            body=tutor.request(coordinate(tutor,bid))[1]
            check('Proposed' in body and 'UiTM Library &lt;script&gt;' in body and 'Arrange &lt;b&gt;lesson&lt;/b&gt;' in body and 'Send arrangement proposal' not in body,'pending comparison escaped and new form hidden')
            check(decide(bid)[0]==303,'Tutor accepts Student venue proposal')
            accepted=row(bid)
            check(accepted['venue_address']==pc['requested_venue_address'] and accepted['agreement_version']==2 and accepted['student_arranged_at'] is None and accepted['tutor_arranged_at'] is None,'initial acceptance increments once and confirms nobody')
            check(all(accepted[k]==before[k] for k in ['booking_id','slot_id','occupied_slot_id','starts_at','ends_at','class_mode','status','hourly_rate_snapshot','expected_amount','decision_at']),'arrangement-only preserves booking schedule occupancy and price')
            check(change(bid)['status']=='accepted' and change(bid)['responded_by_user_id']==tutor_user,'acceptance history')
            check(propose(bid)[0]==422,'same effective arrangement no-op rejected')
            check(confirm(bid,agreement_version='1')[0]==409 and row(bid)==accepted,'stale initial confirmation rejected without mutation')
            check(confirm(bid)[0]==303,'Student confirms current agreement')
            single=row(bid)
            check(single['student_arranged_at'] is not None and single['tutor_arranged_at'] is None and single['agreement_version']==2,'Student sets only own confirmation without version change')
            check('Awaiting Tutor confirmation' in student.request(coordinate(student,bid))[1] and 'Arrangement confirmed' not in student.request(coordinate(student,bid))[1],'one confirmation not complete')
            time.sleep(1.1)
            check(confirm(bid)[0]==303 and row(bid)==single,'repeat confirmation is idempotent including timestamp')
            check(confirm(bid,browser=tutor)[0]==303,'Tutor separately confirms')
            complete=row(bid)
            check(complete['student_arranged_at']==single['student_arranged_at'] and complete['tutor_arranged_at'] is not None and complete['status']=='accepted' and complete['agreement_version']==2,'both confirmed; status and version unchanged')
            check('Arrangement confirmed' in student.request(sv+'?id='+str(bid))[1] and 'Review arrangement' in tutor.request(tv+'?id='+str(bid))[1],'derived completion on booking details')
            check(admin.request('admin/manage-bookings-view.php?id='+str(bid),{'change_action':'confirm_arrangement'})[0]==405 and row(bid)==complete,'Admin remains read-only')
            # Reject/withdraw leave even existing confirmations untouched.
            check(propose(bid,browser=tutor,venue_address='Masjid UiTM Machang')[0]==303,'Tutor venue-only proposal after both confirmed')
            check(decide(bid,'reject',browser=tutor)[0]==403 and decide(bid,'reject',browser=student)[0]==303,'only counterpart rejects')
            check(row(bid)==complete and change(bid)['status']=='rejected','reject preserves agreement confirmations and history')
            check(propose(bid,browser=tutor,venue_address='Masjid UiTM Machang')[0]==303,'proposal after rejected history')
            check(decide(bid,'withdraw',browser=student)[0]==403 and decide(bid,'withdraw',browser=tutor)[0]==303,'only requester withdraws')
            check(row(bid)==complete and change(bid)['status']=='withdrawn','withdraw preserves agreement confirmations and history')
            check(propose(bid,browser=tutor,venue_address='Masjid UiTM Machang')[0]==303 and decide(bid,browser=student)[0]==303,'counterpart accepts changed physical venue')
            revised=row(bid)
            check(revised['venue_address']=='Masjid UiTM Machang' and revised['agreement_version']==3 and revised['student_arranged_at'] is None and revised['tutor_arranged_at'] is None,'material venue change resets both once')
            check(confirm(bid,agreement_version=2)[0]==409 and row(bid)==revised,'old confirmation form cannot reconfirm new agreement')
            check(propose(bid,agreement_version=2)[0]==409,'stale arrangement proposal form rejected')
            check(confirm(bid,browser=tutor)[0]==303 and row(bid)['student_arranged_at'] is None,'Tutor first sets only Tutor confirmation')
            check('Awaiting Student confirmation' in tutor.request(coordinate(tutor,bid))[1] and confirm(bid)[0]==303,'both reconfirm new version independently')
            # Initial online details, validation without a provider whitelist, and URL-only edits.
            ob,oslot,ot=fixture();ob_before=row(ob)
            body=student.request(coordinate(student,ob))[1]
            check('name="meeting_platform"' in body and 'name="meeting_url"' in body and 'name="venue_address"' not in body,'online form relevant inputs only')
            check(confirm(ob)[0]==422 and row(ob)==ob_before,'online confirmation requires agreed platform and URL')
            for values in [{'meeting_platform':''},{'meeting_platform':' '*2},{'meeting_platform':'x'*81},{'meeting_url':''},{'meeting_url':'javascript:alert(1)'},{'meeting_url':'ftp://example.invalid/a'},{'meeting_url':'not a URL'},{'meeting_url':'https://example.invalid/'+'x'*2048}]:
                check(propose(ob,**values)[0]==422,'invalid online proposal '+next(iter(values)))
            check(propose(ob,browser=tutor,meeting_platform='Private community conference')[0]==303,'Tutor online proposal accepts arbitrary external platform')
            check(row(ob)==ob_before and change(ob)['requested_venue_address'] is None,'online pending unchanged and irrelevant venue ignored')
            check(decide(ob,browser=student)[0]==303,'Student agrees online details')
            online=row(ob)
            check(online['meeting_platform']=='Private community conference' and online['meeting_url']=='https://example.invalid/meeting?a=1&b=2' and online['venue_address'] is None and online['agreement_version']==2 and online['student_arranged_at'] is None,'online agreement fields and no auto confirmation')
            check(propose(ob,meeting_platform='Private community conference')[0]==422,'exact online no-op rejected')
            check(propose(ob,meeting_platform='Private community conference',meeting_url='http://example.invalid/new')[0]==303,'Student URL-only proposal meaningful')
            check(decide(ob)[0]==303 and row(ob)['meeting_url']=='http://example.invalid/new' and row(ob)['agreement_version']==3,'URL-only change accepted; HTTP supported')
            online=row(ob)
            for action in ['reject','withdraw']:
                check(propose(ob,meeting_url='https://example.invalid/other')[0]==303,'online decision fixture')
                check(decide(ob,action,browser=tutor if action=='reject' else student)[0]==303 and row(ob)==online,'online '+action+' leaves agreement unchanged')
            # Acceptance validates stored proposal fields again before mutating either record.
            check(propose(ob,meeting_url='https://example.invalid/valid')[0]==303,'valid online proposal for revalidation')
            sql("UPDATE booking_change_requests SET requested_meeting_url='javascript:alert(1)' WHERE change_request_id=?",[change(ob)['change_request_id']])
            check(decide(ob)[0]==422 and row(ob)==online and change(ob)['status']=='pending','invalid persisted proposed URL rolls back safely')
            check(decide(ob,'withdraw',browser=student)[0]==303,'withdraw malformed pending fixture')
            # Combined change is one transaction/version; schedule-only legacy behavior is covered by 3E-1.
            cb,cs,ct=physical_fixture();initial=row(cb)
            sql("UPDATE bookings SET venue_address='Old venue',student_arranged_at=UTC_TIMESTAMP(),tutor_arranged_at=UTC_TIMESTAMP() WHERE booking_id=?",[cb])
            sql("UPDATE tutors SET hourly_rate='99.99' WHERE tutor_id=?",[tid])
            check(request(cb,ct,'online',include_arrangement='1',meeting_platform='Study Rooms',meeting_url='https://example.invalid/combined',venue_address='Ignore')[0]==303,'combined schedule mode online arrangement proposal')
            combined=change(cb);check(decide(cb)[0]==303,'combined proposal accepted')
            current=row(cb)
            check(current['slot_id']==ct and current['class_mode']=='online' and current['meeting_platform']=='Study Rooms' and current['meeting_url']=='https://example.invalid/combined' and current['venue_address'] is None,'combined schedule mode and details applied')
            check(current['agreement_version']==2 and current['student_arranged_at'] is None and current['tutor_arranged_at'] is None and current['hourly_rate_snapshot']==initial['hourly_rate_snapshot'] and current['expected_amount']==combined['proposed_expected_amount'],'combined single reset/version and original-rate duration price')
            check(combined['original_venue_address']=='Old venue' and combined['requested_venue_address'] is None,'combined original arrangement retained')
            check(request(cb,ct,'physical',include_arrangement='1',venue_address='New physical venue')[0]==303 and decide(cb)[0]==303,'combined mode and physical venue accepted')
            check(row(cb)['venue_address']=='New physical venue' and row(cb)['meeting_platform'] is None and row(cb)['meeting_url'] is None,'physical effective fields clear online agreement')
            # Combined proposals retain target/overlap protection at acceptance.
            rb,rs,rt=fixture();rb_before=row(rb)
            check(request(rb,rt,include_arrangement='1',meeting_platform='Test',meeting_url='https://example.invalid/race')[0]==303,'combined target race request')
            competitor=create_booking(rt,browser=student2);competitor_before=row(competitor)
            check(decide(rb)[0]==409 and row(rb)==rb_before and row(competitor)==competitor_before,'combined target taken cannot overwrite either booking')
            xb,xs,xt=fixture();xb_before=row(xb)
            check(request(xb,xt,include_arrangement='1',meeting_platform='Test',meeting_url='https://example.invalid/overlap')[0]==303,'combined overlap request')
            crossing=slot(tid2,offset+7,2);create_booking(crossing,tid2)
            check(decide(xb)[0]==409 and row(xb)==xb_before,'combined Student overlap still protected')
            # Past/ineligible state and incomplete persisted agreements cannot confirm.
            eb,es,et=physical_fixture()
            sql("UPDATE bookings SET venue_address='Fixture venue' WHERE booking_id=?",[eb])
            for state in ['requested','rejected','cancelled','completed','closed']:
                sql("UPDATE bookings SET status=?,rejection_reason='Fixture',student_completed_at=UTC_TIMESTAMP(),tutor_completed_at=UTC_TIMESTAMP() WHERE booking_id=?",[state,eb])
                original=row(eb)
                check(propose(eb)[0]==409 and confirm(eb)[0]==409 and row(eb)==original,'ineligible state rejects coordination and confirmation '+state)
                check('Send arrangement proposal' not in student.request(coordinate(student,eb))[1],'ineligible state has no proposal form')
            sql("UPDATE bookings SET status='accepted' WHERE booking_id=?",[eb])
            past_start=(epoch-timedelta(days=12)).strftime('%Y-%m-%d %H:%M:%S');past_end=(epoch-timedelta(days=12)+timedelta(hours=1)).strftime('%Y-%m-%d %H:%M:%S')
            sql('UPDATE availability_slots SET starts_at=?,ends_at=? WHERE slot_id=?',[past_start,past_end,es]);sql('UPDATE bookings SET starts_at=?,ends_at=? WHERE booking_id=?',[past_start,past_end,eb])
            check(propose(eb)[0]==409 and confirm(eb)[0]==409,'past booking cannot coordinate or confirm')
            ib,ist,it=fixture()
            for platform,url in [(None,'https://example.invalid/room'),('Platform',None),('Platform','ftp://example.invalid/room')]:
                sql('UPDATE bookings SET meeting_platform=?,meeting_url=? WHERE booking_id=?',[platform,url,ib])
                check(confirm(ib)[0]==422 and row(ib)['student_arranged_at'] is None,'persisted incomplete/invalid online agreement cannot confirm')
            # Independent workers synchronize before calling the shared helpers.
            worker=temp/'coordinate.php'
            worker.write_text("""<?php require $argv[1].'/includes/auth.php'; require $argv[1].'/includes/booking-changes.php';
                $p=json_decode(stream_get_contents(STDIN),true); file_put_contents($p['ready'],'ready');
                while(!file_exists($p['go'])) { usleep(1000); }
                try { $actor=['role'=>$p['role'],'user_id'=>$p['user']];
                  if($p['action']==='confirm') { echo tmq_confirm_arrangement($actor,$p['id'],$p['version'])?'saved':'unchanged'; }
                  else { tmq_respond_booking_change($actor,$p['id'],$p['cid'],$p['action']); echo 'saved'; }
                } catch (DomainException $e) { echo $e->getMessage(); }""",encoding='utf-8')
            def race(bid,version,cid,actions):
                nonce=secrets.token_hex(4);go=temp/(nonce+'-go');processes=[]
                try:
                    for i,(action,role,uid) in enumerate(actions):
                        ready=temp/(nonce+'-'+str(i));p=subprocess.Popen([PHP,str(worker),str(ROOT)],stdin=subprocess.PIPE,stdout=subprocess.PIPE,stderr=subprocess.PIPE,text=True,env=env,creationflags=flags)
                        processes.append((p,ready))
                        p.stdin.write(json.dumps({'id':bid,'version':version,'cid':cid,'action':action,'role':role,'user':uid,'ready':str(ready),'go':str(go)}));p.stdin.close();p.stdin=None
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
            ab,asl,at=physical_fixture();check(propose(ab)[0]==303 and decide(ab)[0]==303,'concurrent confirmation agreement fixture')
            results=race(ab,2,0,[('confirm','student',user),('confirm','student',user)])
            check(sorted(results)==['saved','unchanged'] and row(ab)['tutor_arranged_at'] is None,'concurrent same participant confirms once idempotently')
            check(propose(ab,venue_address='Concurrent venue')[0]==303 and decide(ab)[0]==303,'new agreement clears prior confirmation')
            results=race(ab,3,0,[('confirm','student',user),('confirm','tutor',tutor_user)])
            check(results==['saved','saved'] and row(ab)['student_arranged_at'] is not None and row(ab)['tutor_arranged_at'] is not None and row(ab)['agreement_version']==3,'concurrent Student/Tutor both confirm current version')
            for actions in [('accept','reject'),('withdraw','accept'),('withdraw','reject')]:
                check(propose(ab,venue_address='Venue '+secrets.token_hex(3))[0]==303,'arrangement response race fixture');original=row(ab);cid=change(ab)['change_request_id']
                results=race(ab,original['agreement_version'],cid,[(a,'student' if a=='withdraw' else 'tutor',user if a=='withdraw' else tutor_user) for a in actions])
                check(results.count('saved')==1 and sum('already been updated' in result for result in results)==1,'arrangement response race one controlled winner')
                current=row(ab)
                if change(ab)['status']=='accepted':check(current['agreement_version']==original['agreement_version']+1 and current['student_arranged_at'] is None and current['tutor_arranged_at'] is None,'accepted race resets atomically')
                else:check(current==original,'nonaccepted race preserves agreement and confirmations')
            check(propose(ab,venue_address='Final venue')[0]==303 and decide(ab)[0]==303,'fresh unconfirmed version for confirmation/acceptance race')
            version=row(ab)['agreement_version'];check(propose(ab,venue_address='Racing venue')[0]==303,'racing proposal')
            results=race(ab,version,change(ab)['change_request_id'],[('confirm','student',user),('accept','tutor',tutor_user)])
            check(results[1]=='saved' and (results[0]=='saved' or 'agreement has changed' in results[0]),'confirmation/acceptance serialized with controlled stale result')
            current=row(ab)
            check(current['agreement_version']==version+1 and current['student_arranged_at'] is None and current['tutor_arranged_at'] is None and current['venue_address']=='Racing venue','old confirmation never survives accepted change')
            check(confirm(ab,agreement_version=version)[0]==409 and row(ab)==current,'late old form cannot confirm new version')
            check(sql('SELECT COUNT(*) n FROM payments')[0]['n']==0,'no later workflows created')
            check(all(r['Msg_text']=='OK' for r in sql('CHECK TABLE bookings,booking_change_requests,availability_slots')),'temporary booking tables healthy')
            print(f'PASS: {auth.CHECKS} booking coordination assertions; arrangement proposals, counterpart consent, confirmations, stale versions, combined changes and concurrent transitions.')
        finally:
            if server: server.terminate(); server.wait(timeout=15); log.close()
            if created: php('drop'); print('CLEANUP: dropped only task-created '+name)


if __name__ == '__main__': main()
