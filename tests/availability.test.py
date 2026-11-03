"""Focused availability HTTP/MariaDB tests; disposable database only."""
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
    password = 'Availability8-' + secrets.token_hex(12)
    created = False
    server = None
    with tempfile.TemporaryDirectory(prefix='tmq-availability-tests-') as temp:
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
            tutor, user = account('tutor','av_tutor')
            other, other_user = account('tutor','av_other')
            student, _ = account('student','av_student')
            r=subprocess.run([PHP,str(ROOT/'scripts/create_admin.php')],input=json.dumps({'username':'av_admin','email':'av_admin@example.invalid','first_name':'Test','last_name':'Admin','password':password}),env=env,capture_output=True,text=True,creationflags=flags)
            check(r.returncode==0,'fixture Admin')
            admin=auth.Browser(base); login(admin,'av_admin')
            tid=sql('SELECT tutor_id FROM tutors WHERE user_id=?',[user])[0]['tutor_id']
            other_tid=sql('SELECT tutor_id FROM tutors WHERE user_id=?',[other_user])[0]['tutor_id']
            add='tutor/tutor-availability-addNew.php'
            listing='tutor/tutor-availability.php'
            day=(datetime.now(timezone(timedelta(hours=8)))+timedelta(days=7)).strftime('%Y-%m-%d')
            data={'availabilityDate':day,'startTime':'09:00','durationMinutes':'120','classType':'online','availabilityStatus':'available'}
            token=tutor.token(add)
            def save(values, page=add, browser=tutor):
                return browser.request(page,{'csrf_token':token if browser is tutor else browser.token(add),**values})
            check('No availability slots' in tutor.request(listing)[1],'empty state')
            check(save(dict(data,tutor_id=other_tid))[0]==303,'create while Private; forged owner ignored')
            row=sql('SELECT * FROM availability_slots')[0]; sid=row['slot_id']
            check(row['tutor_id']==tid and row['starts_at']==day+' 01:00:00' and row['ends_at']==day+' 03:00:00','UTC persistence and authenticated owner')
            view=f'tutor/tutor-availability-view.php?slot={sid}'
            edit=f'tutor/tutor-availability-edit.php?slot={sid}'
            check(tutor.request(view)[0]==200 and '9:00 AM' in tutor.request(view)[1],'own view MYT')
            check('9:00 AM' in tutor.request(listing)[1] and f'?slot={sid}' in tutor.request(listing)[1],'own list')
            check('No availability slots' in other.request(listing)[1],'list excludes other Tutor')
            check(other.request(view)[0]==404 and other.request(edit)[0]==404,'foreign view/edit denied')
            check(save(data,edit,other)[0]==404,'foreign POST denied')
            check(tutor.request('tutor/tutor-availability-view.php?slot=bad')[0]==404,'invalid identifier')
            check(save(dict(data,startTime='12:00'),edit)[0]==303,'edit own excludes itself')
            check(save(dict(data,startTime='12:00',durationMinutes='60'),edit)[0]==303,'edit same start excludes current row')
            invalid=[({'availabilityDate':'2000-01-01'},'past'),({'availabilityDate':'2030-02-30'},'invalid date'),({'durationMinutes':'0'},'end equals start'),({'durationMinutes':'-60'},'end before start'),({'durationMinutes':'30'},'under hour'),({'durationMinutes':'1500'},'over 24 hours'),({'startTime':'09:30'},'non-hour start'),({'endTime':'08:00'},'tampered end'),({'availabilityStatus':'booked'},'invalid status'),({'classType':'invalid'},'invalid mode'),({'startTime':'12:00'},'overlap')]
            for patch,label in invalid: check(save(dict(data,**patch))[0]==422,label+' rejected')
            check(save(dict(data,startTime='13:00',durationMinutes='60'))[0]==303,'adjacent accepted')
            check(save(dict(data,startTime='13:00'),edit)[0]==422,'edit overlap rejected')
            check(save(dict(data,startTime='12:00'),browser=other)[0]==303,'different Tutor overlapping interval allowed')
            check(save(dict(data,startTime='12:00',durationMinutes='60',availabilityStatus='unavailable'),edit)[0]==303,'mark unavailable')
            check(sql('SELECT availability_status FROM availability_slots WHERE slot_id=?',[sid])[0]['availability_status']=='unavailable','retained same record')
            check(save(dict(data,startTime='12:00'))[0]==422,'unavailable interval still prevents overlap')
            check(save(dict(data,startTime='12:00',durationMinutes='60'),edit)[0]==303,'restore available')
            check(tutor.request(add,dict(data,csrf_token='invalid'))[0]==403,'CSRF failure')
            for browser,label in [(anon,'anonymous'),(student,'Student'),(admin,'Admin')]:
                expected=303 if browser is anon else 403
                check(browser.request(add,dict(data,csrf_token=token))[0]==expected,label+' cannot mutate')
            check(save(dict(data,startTime='23:00',durationMinutes='120'))[0]==303,'cross-midnight accepted')
            overnight=sql('SELECT * FROM availability_slots WHERE tutor_id=? AND starts_at=?',[tid,day+' 15:00:00'])[0]
            check(overnight['ends_at']==day+' 17:00:00' and '(+1 day)' in tutor.request('tutor/tutor-availability-view.php?slot='+str(overnight['slot_id']))[1],'overnight UTC/MYT display')
            later=(datetime.strptime(day,'%Y-%m-%d')+timedelta(days=2)).strftime('%Y-%m-%d')
            check(save(dict(data,availabilityDate=later,durationMinutes='1440'))[0]==303,'24-hour interval accepted')
            # Independent PDO processes exercise the parent lock, not the serial HTTP test server.
            worker=temp/'concurrent.php'
            worker.write_text("<?php require $argv[1].'/includes/auth.php'; require $argv[1].'/includes/availability.php'; $p=json_decode(stream_get_contents(STDIN),true); try { tmq_save_slot(['role'=>'tutor','user_id'=>$p['user']],null,$p['data']); echo 'saved'; } catch (DomainException $e) { echo 'conflict'; }",encoding='utf-8')
            concurrent_day=(datetime.strptime(day,'%Y-%m-%d')+timedelta(days=4)).strftime('%Y-%m-%d')
            def concurrent_save(index):
                r=subprocess.run([PHP,str(worker),str(ROOT)],input=json.dumps({'user':user,'data':dict(data,availabilityDate=concurrent_day,startTime='09:00' if index==0 else '10:00')}),capture_output=True,text=True,env=env,creationflags=flags)
                if r.returncode: raise RuntimeError(r.stderr)
                return r.stdout
            with concurrent.futures.ThreadPoolExecutor(max_workers=2) as pool: results=list(pool.map(concurrent_save,range(2)))
            check(sorted(results)==['conflict','saved'],'concurrent overlapping creates serialize')
            check(sql('CHECK TABLE availability_slots')[0]['Msg_text']=='OK','availability table healthy')
            print(f'PASS: {auth.CHECKS} focused availability assertions; UTC/MYT, ownership, validation, overlap/concurrency and retained slots.')
        finally:
            if server: server.terminate(); server.wait(timeout=15); log.close()
            if created: php('drop'); print('CLEANUP: dropped only task-created '+name)


if __name__ == '__main__': main()
