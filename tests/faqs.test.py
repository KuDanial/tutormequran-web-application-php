"""FAQ integration tests; disposable database only. Set TMQ_DB_PORT to a disposable MariaDB instance."""
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
import re
check = auth.check
PHP = 'C:/xampp/php/php.exe'


def main():
    name = 'tutormequran_auth_validation_' + secrets.token_hex(8)
    env = dict(os.environ, TMQ_DB_NAME=name)
    flags = subprocess.CREATE_NO_WINDOW if os.name == 'nt' else 0
    password = 'Booking8-' + secrets.token_hex(12)
    created = False
    server = None
    with tempfile.TemporaryDirectory(prefix='tmq-faq-tests-') as temp:
        temp = Path(temp)
        env['TMQ_UPLOAD_ROOT']=str(temp/'uploads')
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
            owned=fixture(); foreign=fixture(owner=student2_id,tutor_id=tid2)
            sql("INSERT INTO support_requests (requester_user_id,category,description) VALUES (?,'other','untouched support')",[user])
            sql("INSERT INTO notifications (recipient_user_id,type,title,message) VALUES (?,'booking_requested','Untouched','Untouched')",[user])
            def snapshot():
                return {t:sql('SELECT * FROM `'+t+'`') for row in sql('SHOW TABLES') for t in row.values() if t not in ['faqs','faq_keywords']}
            baseline=snapshot()
            add='admin/manage-faq-addNew.php'
            listing='admin/manage-faq.php'
            def edit(i):return 'admin/manage-faq-edit.php?id='+str(i)
            def view(i):return 'admin/manage-faq-view.php?id='+str(i)
            token=admin.token(add)
            def data(**extra):
                result={'csrf_token':token,'faq_action':'save','questionKeyword':'How to Book?', 'answer':'Book a lesson.', 'faqStatus':'active'}
                result.update(extra); return result
            def records():return sql('SELECT * FROM faqs ORDER BY faq_id')
            def keywords(i):return [r['keyword'] for r in sql('SELECT keyword FROM faq_keywords WHERE faq_id=? ORDER BY keyword',[i])]
            check([r['Field'] for r in sql('SHOW COLUMNS FROM faqs')]==['faq_id','question','answer','status','created_by_admin_id','updated_by_admin_id','created_at','updated_at'],'actual FAQ schema inspected')
            check([r['Field'] for r in sql('SHOW COLUMNS FROM faq_keywords')]==['faq_id','keyword'],'actual keyword schema inspected')
            check('PRIMARY KEY (`faq_id`,`keyword`)' in sql('SHOW CREATE TABLE faq_keywords')[0]['Create Table'],'actual composite PK inspected')
            check(anon.request('help.php')[0]==200 and 'No FAQs are available.' in anon.request('help.php')[1],'public empty state has no demo records')
            check(admin.request(listing)[0]==200,'Admin list access')
            for browser,label,expected in [(anon,'guest',303),(student,'Student',403),(tutor,'Tutor',403)]:
                for route in [listing,add,edit(1),view(1)]:
                    check(browser.request(route)[0]==expected,label+' FAQ access denied')
                    check(browser.request(route,data(role='admin',user_id=aid,csrf_token=tokens.get(browser,'')))[0]==expected,label+' FAQ mutation denied')
            check(not records(),'unauthorized attempts have no FAQ writes')
            check(admin.request(add+'?faq_action=save&questionKeyword=GET')[0]==200 and not records(),'GET does not mutate')
            for route in [add,listing,'help.php']:
                try:r=admin.opener.open(auth.urllib.request.Request(base+route,b'',method='PUT'),timeout=15)
                except urllib.error.HTTPError as e:r=e
                check(r.code==405,'PUT denied '+route);r.close()
            check(admin.request(listing,data())[0]==405 and anon.request('help.php',data())[0]==405,'read-only endpoints reject POST')
            for csrf in ['', 'wrong']:
                check(admin.request(add,data(csrf_token=csrf))[0]==403,'CSRF required')
            for key,value in [('questionKeyword',''),('questionKeyword',' \t\n '),('answer',''),('answer','\u2003'),('questionKeyword','x'*501),('answer','x'*65536),('answer','😀'*16384),('faqStatus','Active'),('faqStatus','deleted')]:
                check(admin.request(add,data(**{key:value}))[0]==422,'validation '+key+' '+repr(value[:10]))
            for key in ['questionKeyword','answer','faqStatus']:
                d=data();del d[key]
                check(admin.request(add,d)[0]==422,'missing '+key)
                d[key+'[]']='bad'
                check(admin.request(add,d)[0]==422,'array '+key)
                del d[key+'[]']
                payload=auth.urllib.parse.urlencode(d).encode()+b'&'+key.encode()+b'=%FF'
                try:r=admin.opener.open(base+add,payload,timeout=15)
                except urllib.error.HTTPError as e:r=e
                check(r.code==422,'invalid UTF-8 '+key);r.close()
            check(not records(),'invalid inputs never persist')
            raw='  العربية 😀 <script>alert(1)</script>  '
            result=admin.request(add,data(questionKeyword=raw,answer=raw,created_by_admin_id=999,updated_by_admin_id=999,role='student'))
            first=records()[0];fid=first['faq_id']
            check(result[0]==303 and result[2]['Location'].endswith(view(fid)),'Admin create and redirect')
            check(first['question']==raw.strip() and first['answer']==raw.strip(),'UTF-8 and whitespace preserved correctly')
            check(first['created_by_admin_id']==aid and first['updated_by_admin_id']==aid,'author and editor session-derived')
            for browser,route in [(anon,'help.php'),(student,'student/student-faq.php'),(tutor,'tutor/tutor-faq.php'),(admin,listing),(admin,view(fid)),(admin,edit(fid))]:
                status,body,_=browser.request(route)
                check(status==200 and '&lt;script&gt;alert(1)&lt;/script&gt;' in body and '<script>alert(1)</script>' not in body,'escaped stored XSS '+route)
            check(admin.request(add,data(questionKeyword='  HOW \t  to Book?  '))[0]==303,'keyword creation')
            second=records()[-1];sid=second['faq_id']
            check(keywords(sid)==['how to book?'],'keyword lowercase and whitespace normalization')
            sql('INSERT INTO faq_keywords (faq_id,keyword) VALUES (?,?)',[sid,'other phrase'])
            check(admin.request(edit(sid),data())[0]==303 and keywords(sid)==['how to book?','other phrase'],'repeat save duplicate protection and preserved other phrases')
            sql('INSERT INTO faq_keywords (faq_id,keyword) VALUES (?,?)',[sid,'café'])
            check(admin.request(edit(sid),data(questionKeyword='CAFE'))[0]==303 and keywords(sid)==['café','other phrase'],'collation-equivalent duplicate protection')
            before=sql('SELECT * FROM faqs WHERE faq_id=?',[fid]);before_kw=keywords(fid)
            check(admin.request(edit(sid),data(questionKeyword='Updated phrase',faq_id=fid,keyword_faq_id=fid,keyword='forged',faqStatus='inactive'))[0]==303,'Admin edit and deactivate')
            check(sql('SELECT * FROM faqs WHERE faq_id=?',[fid])==before and keywords(fid)==before_kw,'foreign submitted FAQ and keyword IDs cannot redirect writes')
            check(keywords(sid)==['other phrase','updated phrase'],'only target old derived keyword replaced')
            check('Updated phrase' not in anon.request('help.php')[1] and 'Updated phrase' in admin.request(listing)[1],'inactive public hidden and Admin visible')
            for route in [view(sid),edit(sid)]:check(admin.request(route)[0]==200,'Admin inactive access')
            check(admin.request(edit(sid),data(questionKeyword='Updated phrase'))[0]==303 and 'Updated phrase' in anon.request('help.php')[1],'activation makes FAQ public')
            before=records();before_kw=keywords(sid)
            check(admin.request(edit(sid)+'&faqStatus=inactive&faq_action=delete')[0]==200 and records()==before and keywords(sid)==before_kw,'GET edit cannot mutate')
            check(admin.request(edit(sid),data(faq_action='delete'))[0]==422 and records()==before,'no hard-delete action')
            check(admin.request(view(sid),data())[0]==405,'view read-only')
            for suffix,expected in [('999999',404),('0',422),('1e0',422),('4294967296',422)]:
                check(admin.request(edit(suffix),data())[0]==expected,'invalid or missing target '+suffix)
            check(admin.request('admin/manage-faq-edit.php?id[]=1',data())[0]==422,'array ID denied')
            for route in ['admin/manage-faq-edit.php','admin/manage-faq-view.php']:
                check(admin.request(route)[0]==404,'missing FAQ ID')
            for length in [100,101,500]:
                check(admin.request(add,data(questionKeyword='😀'*length,answer='a'*65535))[0]==303,'question/TEXT boundary '+str(length))
                row=records()[-1]
                check(row['question']=='😀'*length and len(row['answer'])==65535,'no truncation '+str(length))
                check(keywords(row['faq_id'])==(['😀'*100] if length==100 else []),'keyword boundary without truncation '+str(length))
            check(admin.request(add,data(questionKeyword='  ÉCOLE \n العربية  '))[0]==303 and keywords(records()[-1]['faq_id'])==['école العربية'],'Unicode keyword normalization')
            for i in range(55):sql("INSERT INTO faqs (question,answer,status,created_by_admin_id,updated_by_admin_id) VALUES (?,?,'active',?,?)",['Page '+str(i),'Answer',aid,aid])
            for browser,route,public in [(anon,'help.php',True),(admin,listing,False)]:
                expected=[r['faq_id'] for r in sql('SELECT faq_id FROM faqs '+("WHERE status='active' " if public else '')+'ORDER BY faq_id')]
                body=browser.request(route)[1]; found=[int(x) for x in re.findall(r'data-faq-id="(\d+)"',body)]
                check(found==expected[:50] and 'More FAQs' in body,'bounded deterministic listing '+route)
                body=browser.request(route+'?after='+str(found[-1]))[1]
                check([int(x) for x in re.findall(r'data-faq-id="(\d+)"',body)]==expected[50:],'cursor continuation '+route)
                check(browser.request(route+'?after[]=1')[0]==422,'invalid cursor '+route)
            check(snapshot()==baseline,'no domain or notification side effects: all other table snapshots unchanged')
            before=records()
            sql("UPDATE users SET account_status='inactive' WHERE user_id=(SELECT user_id FROM admins WHERE admin_id=?)",[aid])
            check(admin.request(add,data())[0]==303 and records()==before,'inactive Admin mutation denied')
            print(f'PASS: {auth.CHECKS} FAQ assertions; public visibility, Admin CRUD, keywords, validation, security and side effects.')
        finally:
            if server:server.terminate();server.wait(timeout=15);log.close()
            if created:php('drop');print('CLEANUP: dropped only task-created '+name+'; temporary files removed.')


if __name__=='__main__':main()
