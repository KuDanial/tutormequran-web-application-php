"""Rule-based chatbot tests; disposable database only. Set TMQ_DB_PORT to a disposable MariaDB instance."""
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
    failure_server = None
    failure_log = None
    with tempfile.TemporaryDirectory(prefix='tmq-chatbot-tests-') as temp:
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
            server = subprocess.Popen([PHP,'-d','allow_url_fopen=0','-d','disable_functions=curl_init,curl_exec,fsockopen,pfsockopen,stream_socket_client,exec,shell_exec,system,passthru','-d','display_errors=0','-d','session.save_path='+str(temp),'-S',f'127.0.0.1:{port}','-t',str(ROOT)],env=env,stdout=log,stderr=log,creationflags=flags)
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
            student, user = account('student','bc_student')
            r=subprocess.run([PHP,str(ROOT/'scripts/create_admin.php')],input=json.dumps({'username':'bc_admin','email':'bc_admin@example.invalid','first_name':'Test','last_name':'Admin','password':password}),env=env,capture_output=True,text=True,encoding='utf-8',creationflags=flags)
            check(r.returncode==0,'fixture Admin')
            admin=auth.Browser(base); login(admin,'bc_admin')
            aid=sql('SELECT admin_id FROM admins')[0]['admin_id']
            def fixture(question, answer, phrases, status='active'):
                sql('INSERT INTO faqs (question,answer,status,created_by_admin_id,updated_by_admin_id) VALUES (?,?,?,?,?)',[question,answer,status,aid,aid])
                fid=sql('SELECT MAX(faq_id) AS id FROM faqs')[0]['id']
                for phrase in phrases:sql('INSERT INTO faq_keywords (faq_id,keyword) VALUES (?,?)',[fid,phrase])
                return fid
            partial=fixture('Booking guidance','PARTIAL ANSWER',['book tutor'])
            exact=fixture('How do I book a tutor?','EXACT ANSWER',['how to book tutor'])
            fixture('Shared keyword duplicate','LATER TIE ANSWER',['how to book tutor'])
            inactive=fixture('Inactive secret','INACTIVE SECRET',['private special phrase'],'inactive')
            fixture('Receipt guidance','RECEIPT ANSWER',['upload successful payment receipt'])
            fixture('العربية','UNICODE ANSWER',['تعلم القرآن'])
            xss=fixture('<script>alert(1)</script>','<script>alert(1)</script>\nSecond line',['unsafe content'])
            long=fixture('L'*101,'LONG QUESTION ANSWER',[])
            token=anon.token('chatbot.php')
            def ask(question, browser=anon, csrf=token):
                return browser.request('chatbot.php',{'csrf_token':csrf,'question':question})
            def snapshot():
                return {t:sql('SELECT * FROM `'+t+'`') for row in sql('SHOW TABLES') for t in row.values()}
            def schema():
                return {t:sql('SHOW CREATE TABLE `'+t+'`')[0]['Create Table'] for row in sql('SHOW TABLES') for t in row.values()}
            baseline=snapshot();structure=schema()
            result=ask('how to book tutor')
            check(result[0]==200 and 'EXACT ANSWER' in result[1] and 'How do I book a tutor?' in result[1],'exact active match returns stored question and answer')
            check('PARTIAL ANSWER' not in result[1] and 'LATER TIE ANSWER' not in result[1],'exact outranks earlier partial; lowest ID wins exact tie')
            for text in ['HOW TO BOOK TUTOR','  how\t to\n book\u2003tutor  ']:
                check('EXACT ANSWER' in ask(text)[1],'case and Unicode whitespace normalization')
            check('PARTIAL ANSWER' in ask('Please book tutor for me')[1],'phrase containment')
            check('RECEIPT ANSWER' in ask('payment receipt successful upload')[1],'meaningful token overlap')
            check('UNICODE ANSWER' in ask('  تعلم\u2003القرآن  ')[1],'UTF-8 matching')
            for text in ['private special phrase','Who discovered Neptune?', 'how please help', 'payment astronomy planets', 'book tutorology', 'L'*101, "' OR 1=1 --", '^how.*tutor$']:
                body=ask(text)[1]
                check('could not find a suitable answer' in body and 'INACTIVE SECRET' not in body,'controlled fallback: '+text[:30])
            for invalid in ['', ' \t\n\u2003 ', 'x'*501, '😀'*501, ['not scalar']]:
                payload={'csrf_token':token, 'question[]' if isinstance(invalid,list) else 'question':invalid}
                check(anon.request('chatbot.php',payload)[0]==422,'invalid input rejected')
            check(anon.request('chatbot.php',{'csrf_token':token})[0]==422,'missing question rejected')
            payload=auth.urllib.parse.urlencode({'csrf_token':token}).encode()+b'&question=%FF'
            try:r=anon.opener.open(base+'chatbot.php',payload,timeout=15)
            except urllib.error.HTTPError as e:r=e
            check(r.code==422,'invalid UTF-8 rejected');r.close()
            check(ask('😀'*500)[0]==200,'500 Unicode characters accepted without truncation')
            check(ask('hello',csrf='wrong')[0]==403,'CSRF required')
            try:r=anon.opener.open(auth.urllib.request.Request(base+'chatbot.php',b'',method='PUT'),timeout=15)
            except urllib.error.HTTPError as e:r=e
            check(r.code==405,'unsupported method denied');r.close()
            body=ask('<script>alert(1)</script>')[1]
            check('&lt;script&gt;alert(1)&lt;/script&gt;' in body and '<script>alert(1)</script>' not in body,'user XSS rendered as text')
            body=ask('unsafe content')[1]
            check(body.count('&lt;script&gt;alert(1)&lt;/script&gt;')==2 and '<script>alert(1)</script>' not in body and '<br' in body,'FAQ question/answer XSS escaped; safe line breaks')
            body=ask('unknown-question-sentinel-75921')[1]
            check('href="help.php"' in body and anon.request('help.php')[0]==200,'fallback links to real FAQ/support route')
            check('unknown-question-sentinel-75921' not in anon.request('chatbot.php')[1],'GET does not replay a conversation')
            check(all('unknown-question-sentinel-75921' not in p.read_text(errors='replace') for p in temp.glob('sess_*')),'question not persisted in session files')
            check('EXACT ANSWER' not in anon.request('chatbot.php?question=how+to+book+tutor')[1],'GET query does not submit question')
            for browser in [student,tutor,admin]:
                check('EXACT ANSWER' in ask('how to book tutor',browser,browser.token('chatbot.php'))[1],'same public matcher for authenticated account')
            check(snapshot()==baseline,'all tables unchanged: FAQs, support, notifications, booking/payment/chat and profiles; no conversation persistence')
            check(schema()==structure,'no schema changes')
            # Existing FAQ administration remains authoritative without chatbot caches.
            sql('UPDATE faqs SET answer=? WHERE faq_id=?',['CURRENT ANSWER',exact])
            check('CURRENT ANSWER' in ask('how to book tutor')[1],'updated answer used immediately')
            sql("UPDATE faqs SET status='inactive' WHERE faq_id=?",[exact])
            check('CURRENT ANSWER' not in ask('how to book tutor')[1] and 'LATER TIE ANSWER' in ask('how to book tutor')[1],'deactivation reflected immediately')
            sql('INSERT INTO faq_keywords (faq_id,keyword) VALUES (?,?)',[long,'long question phrase'])
            check('LONG QUESTION ANSWER' in ask('long question phrase')[1],'long FAQ matches only through existing valid keywords')
            # Wrong disposable DB name simulates failure without altering any schema or server.
            with socket.socket() as sock:
                sock.bind(('127.0.0.1',0)); failure_port=sock.getsockname()[1]
            failure_log=open(temp/'failure.log','w')
            failure_server=subprocess.Popen([PHP,'-d','display_errors=0','-d','session.save_path='+str(temp),'-S',f'127.0.0.1:{failure_port}','-t',str(ROOT)],env=dict(env,TMQ_DB_NAME=name+'_missing'),stdout=failure_log,stderr=failure_log,creationflags=flags)
            failure_browser=auth.Browser(f'http://127.0.0.1:{failure_port}/')
            for _ in range(50):
                try:failure_browser.request('chatbot.php');break
                except urllib.error.URLError:time.sleep(.1)
            status,body,_=failure_browser.request('chatbot.php',{'csrf_token':failure_browser.token('chatbot.php'),'question':'hello'})
            check(status==503 and 'temporarily unavailable' in body and not any(x in body for x in ['SQLSTATE','PDOException',name,'Stack trace']),'database failure controlled without diagnostics')
            source=(ROOT/'includes/faq-chatbot.php').read_text()+(ROOT/'chatbot.php').read_text()
            check(not any(x in source for x in ['curl_', 'https://', 'http://', 'file_put_contents', 'INSERT INTO', 'UPDATE faqs', 'DELETE FROM']),'no external API or persistence code; normal server also disables outbound PHP helpers')
            print(f'PASS: {auth.CHECKS} chatbot assertions; matching, fallback, safety, public/account access and read-only behavior.')
        finally:
            if failure_server:failure_server.terminate();failure_server.wait(timeout=15)
            if failure_log:failure_log.close()
            if server:server.terminate();server.wait(timeout=15);log.close()
            if created:php('drop');print('CLEANUP: dropped only task-created '+name+'; temporary files removed.')


if __name__=='__main__':main()
