"""Cross-module HTTP journey and rendered navigation audit on a disposable DB.

Run through scripts/run_final_regression.py, or set TMQ_DB_PORT to an isolated
MariaDB. The only clock shortcut moves this test's agreed lesson into the past;
completion, feedback, payment evidence and closure still use actual HTTP forms.
"""
from datetime import datetime, timedelta, timezone
from html.parser import HTMLParser
import importlib.util
import json
import os
from pathlib import Path
import re
import secrets
import socket
import subprocess
import sys
import tempfile
import time
import urllib.error
from urllib.parse import urljoin, urlsplit, unquote

ROOT = Path(__file__).resolve().parents[1]
sys.dont_write_bytecode = True


def module(name, file):
    spec = importlib.util.spec_from_file_location(name, ROOT/'tests'/file)
    result = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(result)
    return result


auth = module('auth_helpers', 'authentication.test.py')
profiles = module('profile_helpers', 'profile_verification.test.py')
check = auth.check
PHP = 'C:/xampp/php/php.exe'


class Links(HTMLParser):
    def __init__(self, body):
        super().__init__()
        self.urls, self.ids, self.forms = [], set(), []
        self.feed(body)

    def handle_starttag(self, tag, attrs):
        attrs = dict(attrs)
        if 'id' in attrs:
            self.ids.add(attrs['id'])
        if tag in ('a', 'link', 'script', 'img', 'form'):
            for key in ('href', 'src', 'action'):
                if attrs.get(key):
                    self.urls.append((tag, attrs[key]))
        if tag == 'form':
            self.forms.append(attrs)


def main():
    name = 'tutormequran_auth_validation_' + secrets.token_hex(8)
    env = dict(os.environ, TMQ_DB_NAME=name)
    flags = subprocess.CREATE_NO_WINDOW if os.name == 'nt' else 0
    password = 'Integration8-' + secrets.token_hex(12)
    created, server, restricted = False, None, None
    with tempfile.TemporaryDirectory(prefix='tmq-final-http-') as directory:
        temp = Path(directory)
        env['TMQ_UPLOAD_ROOT'] = str(temp/'uploads')
        bridge = temp/'bridge.php'
        bridge.write_text(auth.BRIDGE, encoding='utf-8')

        def run(args, **kwargs):
            return subprocess.run(args, env=env, capture_output=True, text=True,
                                  encoding='utf-8', creationflags=flags, **kwargs)

        def php(action, data=None):
            r = run([PHP, str(bridge), str(ROOT), action], input=json.dumps(data or {}))
            if r.returncode:
                raise RuntimeError(r.stdout+r.stderr)
            return json.loads(r.stdout)

        def sql(query, params=None):
            return php('query', {'sql': query, 'params': params or []})

        try:
            php('create'); created = True
            with socket.socket() as sock:
                sock.bind(('127.0.0.1', 0)); port = sock.getsockname()[1]
            log = open(temp/'server.log', 'w', encoding='utf-8')
            server = subprocess.Popen([PHP, '-d', 'display_errors=1', '-d', 'error_reporting=-1',
                '-d', 'session.save_path='+str(temp), '-S', f'127.0.0.1:{port}', '-t', str(ROOT)],
                env=env, stdout=log, stderr=log, creationflags=flags)
            base = f'http://127.0.0.1:{port}/'
            guest = auth.Browser(base)
            for _ in range(50):
                try:
                    guest.request('index.php'); break
                except urllib.error.URLError:
                    time.sleep(.1)
            hashed = run([PHP, '-r', 'echo password_hash(trim(stream_get_contents(STDIN)), PASSWORD_DEFAULT);'], input=password).stdout
            for uid, role in [(1, 'admin'), (2, 'student'), (3, 'tutor'), (4, 'student')]:
                sql('INSERT INTO users (user_id,role,username,email,password_hash,first_name,last_name,ic_number) VALUES (?,?,?,?,?,?,?,?)',
                    [uid, role, 'final'+str(uid), 'private'+str(uid)+'@example.invalid', hashed, 'Actual <name>', role, 'PRIVATE_IC'])
                sql('INSERT INTO '+{'admin':'admins', 'student':'students', 'tutor':'tutors'}[role]+' (user_id) VALUES (?)', [uid])

            def login(uid):
                browser = auth.Browser(base)
                check(browser.request('login.php', {'csrf_token':browser.token('login.php'),
                    'accountUsername':'final'+str(uid), 'accountPassword':password})[0] == 303, 'login')
                return browser

            admin, student, tutor, foreign = [login(i) for i in (1, 2, 3, 4)]
            tokens = {browser:browser.token(page) for browser, page in [(admin,'admin/admin-profile.php'),
                (student,'student/student-dashboard.php'), (tutor,'tutor/tutor-dashboard.php'),
                (foreign,'student/student-dashboard.php')]}

            def post(browser, page, **values):
                return browser.request(page, dict(csrf_token=tokens[browser], **values))

            # Direct access must not invent verification or public visibility.
            body = tutor.request('tutor/tutor-dashboard-verified.php')[1]
            check('Not approved' in body and 'Not listed publicly' in body, 'unapproved tutor never sees simulated verified/public state')
            check('certificate not submitted' in tutor.request('tutor/tutor-dashboard.php')[1], 'draft dashboard handles NULL application date without diagnostics')
            sql("UPDATE users SET profile_picture_path=? WHERE user_id=3", ['profiles/'+'a'*40+'.png'])
            (temp/'uploads'/'profiles').mkdir(parents=True)
            (temp/'uploads'/'profiles'/('a'*40+'.png')).write_bytes(profiles.PNG)
            sql("INSERT INTO certificates (tutor_id,submission_purpose,certificate_name,qualification_level,certificate_year,file_path,original_filename,mime_type,file_size_bytes,status,reviewed_by_admin_id,reviewed_at) VALUES (1,'initial','Qualification','ijazah',2024,?,'certificate.pdf','application/pdf',100,'approved',1,UTC_TIMESTAMP())", ['certificates/'+'b'*40+'.pdf'])
            sql("UPDATE tutors SET displayed_certificate_id=1,profile_visibility='public',teaching_state='Selangor',teaching_area='Test area',teaching_mode='both',hourly_rate=20,bio='Quran lessons' WHERE tutor_id=1")
            sql('INSERT INTO tutor_languages VALUES (1,1)'); sql('INSERT INTO tutor_lesson_focuses VALUES (1,1)')
            check('1 matching profiles' in guest.request('tutors.php')[1], 'public listing count comes from eligible filtered records')
            check('0 matching profiles' in guest.request('tutors.php?searchTutor=nonexistent')[1], 'empty filtered count is truthful')
            home = Links(guest.request('index.php')[1])
            check(any(f.get('action')=='tutors.php' and f.get('method')=='get' for f in home.forms), 'homepage search submits existing live GET filters')
            check('sample data' not in guest.request('index.php')[1], 'no fake platform counts')
            day = (datetime.now(timezone.utc)+timedelta(days=10)).strftime('%Y-%m-%d')
            for start, duration in [('10:00', '60'), ('14:00', '120')]:
                check(post(tutor, 'tutor/tutor-availability-addNew.php', availabilityDate=day,
                    startTime=start, durationMinutes=duration, classType='both', availabilityStatus='available')[0]==303,
                    'Tutor creates future availability through HTTP')
            create = 'student/student-booking-addNew.php'
            check(post(student, create, tutor='1', slotId='1', classType='online', bookingNotes='Private lesson notes')[0]==303, 'eligible Student books slot')
            bid = sql('SELECT booking_id FROM bookings')[0]['booking_id']
            suffix = '?id='+str(bid)

            def booking(): return sql('SELECT * FROM bookings WHERE booking_id=?', [bid])[0]
            def payment(): return sql('SELECT * FROM payments WHERE booking_id=?', [bid])[0]
            check(booking()['occupied_slot_id']==1 and booking()['status']=='requested', 'request reserves immediately')
            check(post(foreign, create, tutor='1', slotId='1', classType='online')[0] in (409,422), 'double booking denied')
            check(post(tutor, 'tutor/tutor-booking-view.php'+suffix, decision='accept')[0]==303, 'Tutor accepts')
            check(post(student, 'student/student-booking-coordinate.php'+suffix, change_action='propose_arrangement',
                agreement_version=str(booking()['agreement_version']), meeting_platform='Video',
                meeting_url='https://example.invalid/lesson', reason='Agree meeting details')[0]==303, 'arrangement proposal')
            change_id = sql('SELECT change_request_id FROM booking_change_requests')[0]['change_request_id']
            check(post(tutor, 'tutor/tutor-booking-coordinate.php'+suffix, change_action='accept', change_id=str(change_id))[0]==303, 'counterpart agreement')

            def confirm():
                for browser, role in [(student,'student'), (tutor,'tutor')]:
                    check(post(browser, role+'/'+role+'-booking-coordinate.php'+suffix,
                        change_action='confirm_arrangement', agreement_version=str(booking()['agreement_version']))[0]==303,
                        role+' confirms arrangement')
                check(booking()['status']=='accepted', 'arrangement never completes lesson')
            confirm()
            sql('UPDATE tutors SET hourly_rate=99 WHERE tutor_id=1')
            old_version = booking()['agreement_version']
            check(post(student, 'student/student-booking-edit.php'+suffix, change_action='create', slotId='2', classType='online', reason='Longer lesson')[0]==303, 'reschedule requested')
            check(booking()['slot_id']==1 and not sql('SELECT booking_id FROM bookings WHERE occupied_slot_id=2'), 'pending reschedule holds no target')
            change_id = sql('SELECT MAX(change_request_id) id FROM booking_change_requests')[0]['id']
            check(post(tutor, 'tutor/tutor-booking-edit.php'+suffix, change_action='accept', change_id=str(change_id))[0]==303, 'reschedule accepted')
            row = booking()
            check(row['slot_id']==2 and row['occupied_slot_id']==2 and str(row['hourly_rate_snapshot'])=='20.00' and str(row['expected_amount'])=='40.00', 'occupancy transfers and original rate prices new duration')
            check(row['agreement_version']==old_version+1 and row['student_arranged_at'] is None and row['tutor_arranged_at'] is None, 'reschedule increments version and resets both confirmations')
            confirm()
            check(post(student, 'student/student-chat.php'+suffix, chat_action='send', message_type='text', body='Private message')[0]==303, 'accepted chat writable')
            # The test clock advances only by changing its disposable schedule fixture.
            start = (datetime.now(timezone.utc)-timedelta(days=2)).replace(hour=0,minute=0,second=0,microsecond=0)
            times = [start.strftime('%Y-%m-%d %H:%M:%S'), (start+timedelta(hours=2)).strftime('%Y-%m-%d %H:%M:%S')]
            sql('UPDATE availability_slots SET starts_at=?,ends_at=? WHERE slot_id=2', times)
            sql('UPDATE bookings SET starts_at=?,ends_at=? WHERE booking_id=?', times+[bid])
            for browser, role in [(student,'student'), (tutor,'tutor')]:
                check(post(browser, role+'/'+role+'-booking-session.php'+suffix, lifecycle_action='complete',
                    agreement_version=str(booking()['agreement_version']))[0]==303, role+' confirms lesson completion')
            check(booking()['status']=='completed', 'second completion derives completed')
            check(post(student, 'student/student-feedback-addNew.php'+suffix, feedback_action='submit', rating='5', comment='Useful <script>review</script>')[0]==303, 'feedback before any payment')
            public = guest.request('tutor-detail.php?id=1')[1]
            check('Useful &lt;script&gt;review&lt;/script&gt;' in public and 'private2@example.invalid' not in public, 'public review escaped without Student private identity')
            upload_page = 'student/student-payment-upload.php'+suffix
            check(post(student, upload_page, payment_action='open')[0]==303, 'completed payment opened')
            for amount in ['4', '36']:
                check(profiles.upload(student, upload_page, {'csrf_token':tokens[student], 'payment_action':'transfer',
                    'payment_id':payment()['payment_id'], 'amount_paid':amount}, 'receipt', 'receipt.pdf', profiles.PDF, 'application/pdf')[0]==303, 'genuine transfer '+amount)
                sid = sql('SELECT MAX(submission_id) id FROM payment_submissions')[0]['id']
                for browser, role, action, decision in [(tutor,'tutor','tutor_review','received'), (admin,'admin','admin_review','valid')]:
                    route = 'tutor/tutor-payment-view.php' if role=='tutor' else 'admin/manage-payments-view.php'
                    check(post(browser, route+suffix, payment_action=action, submission_id=str(sid), decision=decision,
                        evidence_revision=str(payment()['evidence_revision']))[0]==303, role+' reviews current evidence')
                if amount=='4': check(payment()['status']=='underpaid', 'RM4 is underpaid against RM40')
            check(post(admin, 'admin/manage-payments-view.php'+suffix, payment_action='verify', evidence_revision=str(payment()['evidence_revision']))[0]==303, 'exact RM4 + RM36 final verification')
            check(payment()['status']=='verified' and booking()['status']=='closed', 'payment and booking close together')
            check(post(student, 'student/student-chat.php'+suffix, chat_action='send', message_type='text', body='Blocked')[0]==409, 'closed chat immutable')
            check(post(student, 'student/student-feedback-addNew.php'+suffix, feedback_action='submit', rating='1')[0]==409, 'closed feedback remains unique and immutable')
            check(admin.request('student/student-chat.php'+suffix)[0]==403, 'Admin never acquires participant chat access')
            notice_count = sql('SELECT COUNT(*) n FROM notifications')[0]['n']
            support_body = student.request('student/student-support-request.php')[1]
            form_token = re.search(r'name="support_form" value="([a-f0-9]+)"',support_body)[1]
            check(post(student, 'student/student-support-request.php', support_action='create', support_form=form_token,
                category='other', description='Question about my lesson', booking=str(bid))[0]==303, 'support accepts owned historical booking')
            check(foreign.request('student/student-support-request.php?id=1')[0]==404, 'support foreign history denied')
            faq_data = {'faq_action':'save', 'questionKeyword':'Lesson payment guide', 'answer':'Current answer', 'faqStatus':'active'}
            check(post(admin, 'admin/manage-faq-addNew.php', **faq_data)[0]==303, 'Admin creates FAQ')
            chatbot_token = guest.token('chatbot.php')
            def ask(): return guest.request('chatbot.php', {'csrf_token':chatbot_token, 'question':'Lesson payment guide'})[1]
            check('Current answer' in ask(), 'chatbot uses current active FAQ')
            check(post(admin, 'admin/manage-faq-edit.php?id=1', **dict(faq_data, answer='Changed answer'))[0]==303 and 'Changed answer' in ask(), 'Admin edit immediately reaches chatbot')
            check(post(admin, 'admin/manage-faq-edit.php?id=1', **dict(faq_data, faqStatus='inactive'))[0]==303 and 'could not find a suitable answer' in ask(), 'deactivation immediately removes chatbot answer')
            report = admin.request('report/report-dashboard.php')[1]
            check('Verified' in report or 'verified' in report, 'report sees just-verified payment')
            export = admin.request('report/report-dashboard.php?export=csv')
            check(export[0]==200 and 'verified' in export[1], 'CSV sees current transactional status')
            check(sql('SELECT COUNT(*) n FROM notifications')[0]['n']==notice_count, 'support/FAQ/chatbot/report create no new notification events')

            # Every live page: correct role, actual context, diagnostic-free output,
            # local links/assets and same-page fragments. No requests to external URLs.
            pages = [(guest,p.name) for p in ROOT.glob('*.php') if p.name not in ('logout.php','profile-file.php','support-file.php')]
            for role, browser in [('student',student),('tutor',tutor),('admin',admin),('report',admin)]:
                for path in (ROOT/role).glob('*.php'):
                    if path.stem.endswith('-file'):
                        continue
                    page = role+'/'+path.name
                    if 'booking-' in path.name or 'payment-' in path.name or path.name in ('student-chat.php','tutor-chat.php','student-feedback-addNew.php'):
                        page += suffix
                    elif path.name in ('manage-faq-view.php','manage-faq-edit.php','manage-tutors-view.php','manage-tutors-edit.php','manage-certificates-view.php'):
                        page += '?id=1'
                    elif path.name in ('tutor-availability-view.php','tutor-availability-edit.php'):
                        page += '?slot=1'
                    pages.append((browser,page))
            rendered = {}
            for browser, page in pages:
                if page=='tutor-detail.php': page+='?id=1'
                status, body, _ = browser.request(page)
                check(status==200, 'HTTP smoke '+page+': '+str(status))
                check(not re.search(r'Fatal error|Warning:|Deprecated:|SQLSTATE|Stack trace|password_hash',body), 'controlled output '+page)
                if browser is guest or page.startswith('report/'):
                    check('PRIVATE_IC' not in body and 'private2@example.invalid' not in body, 'public/export private identity exclusion '+page)
                check(not re.search(r'TMQ-1024|sample data|sample directory|Ustaz Farhan|Ahmad Hakimi|Nur Amirah|data-delete-row|data-progress-source="session"',body), 'no live prototype state '+page)
                document = Links(body)
                rendered[urlsplit(base+page).path] = document
                for tag, value in document.urls:
                    url = urlsplit(urljoin(base+page,value))
                    if url.netloc!=urlsplit(base).netloc or url.scheme not in ('http','https'):
                        continue
                    target = ROOT/unquote(url.path).lstrip('/')
                    check(target.is_file() and target.suffix!='.html', 'live local route/asset '+page+' -> '+value)
                    if url.fragment and url.path==urlsplit(base+page).path:
                        check(unquote(url.fragment) in document.ids, 'same-page anchor '+page+' -> '+value)
            missing = []
            for path, document in rendered.items():
                for tag, value in document.urls:
                    url = urlsplit(urljoin(base.rstrip('/')+path,value))
                    if url.netloc==urlsplit(base).netloc and url.fragment and url.path in rendered:
                        if unquote(url.fragment) not in rendered[url.path].ids:
                            missing.append(path+' -> '+value)
            check(not missing, 'cross-page anchors: '+repr(missing))
            for page in ['admin/manage-students.php','admin/manage-students-view.php','admin/manage-students-edit.php','admin/manage-bookings-edit.php']:
                check(post(admin,page,action='delete')[0]==405, 'retired prototype route cannot mutate '+page)
            check('Actual &lt;name&gt; admin' in admin.request('admin/admin-profile.php')[1], 'Admin profile uses escaped authenticated identity')
            # Auth still succeeds while a later profile/settings read fails. This
            # must stay controlled even when PHP display_errors is enabled.
            restricted = 'final_reader_'+secrets.token_hex(4)
            sql("CREATE USER '"+restricted+"'@'127.0.0.1' IDENTIFIED BY ''")
            for table in ['users','students','tutors','certificates','languages','tutor_languages','lesson_focuses','tutor_lesson_focuses']:
                sql('GRANT SELECT ON `'+name+'`.'+table+" TO '"+restricted+"'@'127.0.0.1'")
            server.terminate(); server.wait(timeout=15)
            server = subprocess.Popen([PHP, '-d','display_errors=1','-d','error_reporting=-1',
                '-d','session.save_path='+str(temp),'-S',f'127.0.0.1:{port}','-t',str(ROOT)],
                env=dict(env,TMQ_DB_USER=restricted),stdout=log,stderr=log,creationflags=flags)
            for _ in range(50):
                try: guest.request('index.php'); break
                except urllib.error.URLError: time.sleep(.1)
            for browser, page in [(student,'student/student-profile-view.php'),
                                  (tutor,'tutor/tutor-profile-edit.php'),(admin,'admin/admin-settings.php')]:
                status, body, _ = browser.request(page)
                check(status==503 and not re.search(r'SQLSTATE|Stack trace|Fatal error|Warning:|xampp|SELECT command',body),
                      'profile/settings database failure controlled with display_errors on: '+page)
            sql('REVOKE SELECT ON `'+name+"`.certificates FROM '"+restricted+"'@'127.0.0.1'")
            for browser, page in [(admin,'admin/manage-certificates.php'), (admin,'admin/manage-tutors.php'),
                                  (tutor,'tutor/tutor-bookings.php')]:
                status, body, _ = browser.request(page)
                check(status==503 and not re.search(r'SQLSTATE|Stack trace|Fatal error|Warning:|xampp|SELECT command',body),
                      'profile list/header database failure controlled: '+page)
            print(f'HTTP SMOKE: {len(rendered)} live pages; internal routes/assets and anchors audited.')
            print(f'PASS: {auth.CHECKS} final integration assertions; end-to-end workflow, live navigation, prototype removal and HTTP smoke.')
        finally:
            if server:
                server.terminate(); server.wait(timeout=15); log.close()
            if restricted:
                sql("DROP USER '"+restricted+"'@'127.0.0.1'")
            if created:
                php('drop')
                print('CLEANUP: dropped only task-created '+name)


if __name__ == '__main__':
    main()
