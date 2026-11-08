"""Focused reports checks. Starts and removes its own disposable MariaDB instance.

Run: python -X utf8 tests/reports.test.py (XAMPP PHP/MariaDB required).
Imports only HTTP/SQL helpers from authentication tests; never runs that suite.
"""
import csv
import html
import importlib.util
import io
import json
import os
from pathlib import Path
import re
import secrets
import shutil
import socket
import subprocess
import sys
import tempfile
import time
import urllib.error

ROOT = Path(__file__).resolve().parents[1]
sys.dont_write_bytecode = True
spec = importlib.util.spec_from_file_location('auth_helpers', ROOT/'tests/authentication.test.py')
auth = importlib.util.module_from_spec(spec)
spec.loader.exec_module(auth)
check = auth.check
PHP = 'C:/xampp/php/php.exe'
MYSQL = Path('C:/xampp/mysql/bin')
FLAGS = subprocess.CREATE_NO_WINDOW if os.name == 'nt' else 0
PAGE = 'report/report-dashboard.php'


def port():
    with socket.socket() as sock:
        sock.bind(('127.0.0.1', 0))
        return sock.getsockname()[1]


def table(body, identifier):
    tbody = re.search(r'id="'+identifier+r'".*?<tbody>(.*?)</tbody>', body, re.S)[1]
    return [[html.unescape(cell) for cell in re.findall(r'<td>(.*?)</td>', row, re.S)]
            for row in re.findall(r'<tr>(.*?)</tr>', tbody, re.S) if 'colspan=' not in row]


def main():
    temp = Path(tempfile.mkdtemp(prefix='tmq-reports-')).resolve()
    check(temp.parent == Path(tempfile.gettempdir()).resolve(), 'owned temporary directory')
    dbport, httpport = port(), port()
    name = 'tutormequran_auth_validation_' + secrets.token_hex(8)
    env = dict(os.environ, TMQ_DB_HOST='127.0.0.1', TMQ_DB_PORT=str(dbport),
               TMQ_DB_NAME=name, TMQ_DB_USER='root', TMQ_DB_PASSWORD='')
    database = server = None
    created = False
    logs = []
    bridge = temp/'bridge.php'
    bridge.write_text(auth.BRIDGE, encoding='utf-8')

    def run(args, **kwargs):
        return subprocess.run(args, env=env, capture_output=True, text=True,
                              encoding='utf-8', creationflags=FLAGS, **kwargs)

    def php(action, data=None):
        result = run([PHP, str(bridge), str(ROOT), action], input=json.dumps(data or {}))
        if result.returncode:
            raise RuntimeError(result.stdout + result.stderr)
        return json.loads(result.stdout)

    def sql(query, params=None):
        return php('query', {'sql': query, 'params': params or []})

    try:
        result = run([str(MYSQL/'mysql_install_db.exe'), '--datadir='+str(temp/'data'), '--port='+str(dbport)], timeout=60)
        if result.returncode:
            raise RuntimeError(result.stdout + result.stderr)
        db_log = open(temp/'database-console.log', 'w'); logs.append(db_log)
        database = subprocess.Popen([str(MYSQL/'mysqld.exe'), '--no-defaults',
            '--basedir=C:/xampp/mysql', '--datadir='+str(temp/'data'), '--port='+str(dbport),
            '--bind-address=127.0.0.1', '--innodb-buffer-pool-size=32M',
            '--log-error='+str(temp/'database.log'), '--pid-file='+str(temp/'database.pid')],
            stdout=db_log, stderr=db_log, creationflags=FLAGS)
        for _ in range(100):
            if database.poll() is not None:
                raise RuntimeError((temp/'database.log').read_text(errors='replace'))
            try:
                with socket.create_connection(('127.0.0.1', dbport), .2): break
            except OSError: time.sleep(.1)
        else: raise RuntimeError('Disposable MariaDB did not start')
        php('create'); created = True
        password = 'Reports8-' + secrets.token_hex(10)
        hashed = run([PHP, '-r', 'echo password_hash(trim(stream_get_contents(STDIN)), PASSWORD_DEFAULT);'], input=password).stdout
        for uid, role in enumerate(['admin', 'student'] + ['tutor']*6, 1):
            sql('INSERT INTO users (user_id,role,username,email,password_hash,first_name,last_name,ic_number) VALUES (?,?,?,?,?,?,?,?)',
                [uid, role, 'reports'+str(uid), 'PRIVATE_EMAIL_'+str(uid)+'@example.invalid', hashed, 'Tutor', str(uid), 'PRIVATE_IC'])
            target = {'admin': 'admins', 'student': 'students', 'tutor': 'tutors'}[role]
            sql('INSERT INTO '+target+' (user_id) VALUES (?)', [uid])
        names = ['<script>alert(1)</script>, "Tutor"\nName', '=SUM(1,2)', '+SUM(1,2)', '-SUM(1,2)', '@SUM(1,2)', '\t=SUM(1,2)']
        for tid, label in enumerate(names, 1):
            sql('UPDATE tutors SET display_name=? WHERE tutor_id=?', [label, tid])
        # Historical private/inactive Tutor still belongs in the comparison.
        sql("UPDATE users SET account_status='inactive' WHERE user_id=3")
        dates = ['2026-09-30 15:59:59', '2026-09-30 16:00:00', '2026-10-31 15:59:59',
                 '2026-10-31 16:00:00', '2026-10-15 00:00:00', '2026-10-16 00:00:00']
        statuses = ['requested', 'accepted', 'rejected', 'completed', 'cancelled', 'closed']
        payment_dates = [dates[1], dates[3], dates[2], dates[4], dates[0], dates[5]]
        payment_statuses = ['pending', 'pending', 'underpaid', 'rejected', 'reupload_required', 'verified']
        bid = 0
        for tid, count in enumerate([6, 6, 1, 1, 1, 1], 1):
            for i in range(count):
                bid += 1
                start, end = f'2026-12-{bid:02d} 01:00:00', f'2026-12-{bid:02d} 02:00:00'
                sql('INSERT INTO availability_slots (slot_id,tutor_id,starts_at,ends_at,mode) VALUES (?,?,?,?,?)', [bid,tid,start,end,'online'])
                sql('INSERT INTO bookings (booking_id,student_id,tutor_id,slot_id,starts_at,ends_at,class_mode,hourly_rate_snapshot,expected_amount,status,rejection_reason,student_completed_at,tutor_completed_at,created_at,notes) VALUES (?,1,?,?,?,?,?,10,10,?,?,?,?,?,?)',
                    [bid,tid,bid,start,end,'online',statuses[i] if tid==1 else 'completed', 'PRIVATE_REASON',end,end,dates[i] if tid==1 else '2026-11-02 00:00:00','PRIVATE_NOTES'])
                if tid==2:
                    sql('INSERT INTO payments (booking_id,status,recipient_name_snapshot,qr_path_snapshot,admin_remark,created_at) VALUES (?,?,?,?,?,?)',
                        [bid,payment_statuses[i],'PRIVATE_RECIPIENT','PRIVATE_QR_PATH','PRIVATE_REMARK',payment_dates[i]])
        web_log = open(temp/'http.log', 'w'); logs.append(web_log)
        server = subprocess.Popen([PHP, '-d', 'display_errors=0', '-d', 'session.save_path='+str(temp),
            '-S', f'127.0.0.1:{httpport}', '-t', str(ROOT)], env=env,
            stdout=web_log, stderr=web_log, creationflags=FLAGS)
        base = f'http://127.0.0.1:{httpport}/'
        guest = auth.Browser(base)
        for _ in range(50):
            try: guest.request(PAGE); break
            except urllib.error.URLError: time.sleep(.1)

        def login(uid):
            browser = auth.Browser(base)
            check(browser.request('login.php', {'csrf_token':browser.token('login.php'),
                'accountUsername':'reports'+str(uid),'accountPassword':password})[0]==303, 'fixture login')
            return browser

        admin, student, tutor = login(1), login(2), login(4)
        for suffix in ['', '?export=csv']:
            check(guest.request(PAGE+suffix)[0]==303, 'guest denied '+suffix)
            for browser in [student, tutor]:
                check(browser.request(PAGE+suffix)[0]==403, 'non-Admin denied '+suffix)
        sql("UPDATE users SET account_status='inactive' WHERE user_id=1")
        for suffix in ['', '?export=csv']:
            check(admin.request(PAGE+suffix)[0]==303, 'inactive Admin denied '+suffix)
        sql("UPDATE users SET account_status='active' WHERE user_id=1")
        admin = login(1)
        tables = [next(iter(row.values())) for row in sql('SHOW TABLES')]
        def snapshot(): return {t:sql('SELECT * FROM `'+t+'`') for t in tables}
        def schema(): return {t:sql('SHOW CREATE TABLE `'+t+'`')[0]['Create Table'] for t in tables}
        baseline, structure = snapshot(), schema()
        expected_all = [[str(i), label, str(6 if i<=2 else 1)] for i,label in enumerate(names,1)]
        expected_payments = [[s,str(2 if s=='pending' else 1),'33.33%' if s=='pending' else '16.67%'] for s in sorted(set(payment_statuses))]
        for month, expected, composition in [('',expected_all,expected_payments),
                ('2026-10', [['1',names[0],'4']], [[s,'1','25.00%'] for s in ['pending','rejected','underpaid','verified']]),
                ('2024-02', [], [])]:
            suffix = '?month='+month
            status, body, headers = admin.request(PAGE+suffix)
            check(status==200, 'active Admin allowed')
            check(table(body,'booking-comparison')==expected, 'real counts, all statuses, grouping, historical Tutors, deterministic ties, month boundaries')
            check(table(body,'payment-composition')==composition, 'authoritative statuses, counts, percentages and independent payment dates')
            check('no-store' in headers['Cache-Control'], 'private response not cached')
            check('PRIVATE_' not in body and '<script>alert(1)</script>' not in body, 'HTML private fields excluded and XSS escaped')
            if not month:
                check('&lt;script&gt;alert(1)&lt;/script&gt;' in body, 'stored XSS escaped')
                check(admin.request(PAGE)[1]==body, 'deterministic repeated dashboard')
            if not expected:
                check('No bookings in this period.' in body and 'No payments in this period.' in body and '>0</strong>' in body, 'zero data safe')
            check(snapshot()==baseline, 'view does not mutate any domain table')
            status, content, headers = admin.request(PAGE+suffix+'&export=csv')
            check(status==200 and headers['Content-Type']=='text/csv; charset=UTF-8', 'CSV download')
            check(headers['Content-Disposition']=='attachment; filename="tutormequran-reports-'+(month or 'all-time')+'.csv"', 'deterministic safe filename')
            check(headers['X-Content-Type-Options']=='nosniff' and 'no-store' in headers['Cache-Control'], 'safe export headers')
            rows = list(csv.reader(io.StringIO(content, newline='')))
            want = [['Total Bookings', month or 'All time', tid,
                     "'"+label if re.match(r'[=+@\-\t]',label) else label, count, ''] for tid,label,count in expected]
            want += [['Payment Status Composition', month or 'All time', '', s,n,p[:-1]] for s,n,p in composition]
            check(len(rows[0])==6 and rows[1:]==want, 'valid CSV; same displayed definitions/filter; escaping including commas, quotes, newlines; formula protection')
            check(not any(re.match(r'[=+@\-\t\r\n]',cell) for row in rows for cell in row), 'all exported cells formula safe')
            check('PRIVATE_' not in content, 'no private data exported')
            check(snapshot()==baseline, 'export does not mutate any domain table')
        for query in ['month=2026-13','month=2026-00','month=2026-1','month=0000-01','month=2026-10-01','month[]=2026-10',
                      'month=%202026-10','month=2026-10%0A','month=%27%20OR%201=1','month=%3Cscript%3E','export[]=csv','export=pdf']:
            for extra in ['', '&export=csv'] if query.startswith('month') else ['']:
                status, body, _ = admin.request(PAGE+'?'+query+extra)
                check(status==422 and '<script>' not in body and 'SQLSTATE' not in body, 'controlled invalid filter/export')
        check(admin.request(PAGE,{})[0]==405, 'GET only')
        check(schema()==structure, 'view/export leave schema unchanged')
        # Deny only report SELECT permissions to exercise query errors after successful auth.
        sql("CREATE USER 'report_reader'@'127.0.0.1' IDENTIFIED BY ''")
        sql('GRANT SELECT ON `'+name+"`.users TO 'report_reader'@'127.0.0.1'")
        server.terminate(); server.wait(timeout=15)
        server = subprocess.Popen([PHP, '-d','display_errors=0','-d','session.save_path='+str(temp),
            '-S',f'127.0.0.1:{httpport}','-t',str(ROOT)], env=dict(env,TMQ_DB_USER='report_reader'),
            stdout=web_log, stderr=web_log, creationflags=FLAGS)
        for _ in range(50):
            try: admin.request(PAGE); break
            except urllib.error.URLError: time.sleep(.1)
        for suffix in ['', '?export=csv']:
            status,body,headers = admin.request(PAGE+suffix)
            check(status==503 and body=='Reports are temporarily unavailable. Please try again later.' and 'Content-Disposition' not in headers, 'report DB error controlled without SQL diagnostics or partial CSV')
        check(snapshot()==baseline and schema()==structure, 'error paths also have no domain/schema mutations')
        print(f'PASS: {auth.CHECKS} focused report assertions.')
    finally:
        if server:
            server.terminate(); server.wait(timeout=15)
        if created: php('drop')
        if database and database.poll() is None:
            run([str(MYSQL/'mysqladmin.exe'), '--no-defaults','--host=127.0.0.1','--port='+str(dbport),'--user=root','shutdown'], timeout=20)
            database.wait(timeout=30)
        for log in logs: log.close()
        if database is None or database.poll() is not None:
            assert temp.parent==Path(tempfile.gettempdir()).resolve() and temp.name.startswith('tmq-reports-')
            shutil.rmtree(temp)
            print('CLEANUP: removed only this test database and stopped disposable runtime.')
        else:
            print('Retained test runtime:',temp)


if __name__ == '__main__': main()
