"""Run every integration suite against a new, disposable loopback MariaDB.

The JSON ledger and logs are kept in the printed temporary runtime directory.
Never reuses an application database/server. Remove that exact directory only
after the ledger confirms normal shutdown and process exit.
"""
import argparse
from datetime import datetime, timezone
import json
import os
from pathlib import Path
import re
import socket
import subprocess
import sys
import tempfile
import time

ROOT = Path(__file__).resolve().parents[1]
MYSQL = Path('C:/xampp/mysql/bin')
FLAGS = subprocess.CREATE_NO_WINDOW if os.name == 'nt' else 0


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--suite', action='append', choices=[p.name for p in (ROOT/'tests').glob('*.test.py')],
                        help='Focused rerun only; omit for the complete set, subdirectory auth and JavaScript.')
    args = parser.parse_args()
    runtime = Path(tempfile.mkdtemp(prefix='tmq-final-integration-')).resolve()
    with socket.socket() as sock:
        sock.bind(('127.0.0.1', 0))
        port = sock.getsockname()[1]
    env = dict(os.environ, TMQ_DB_HOST='127.0.0.1', TMQ_DB_PORT=str(port),
               TMQ_DB_USER='root', TMQ_DB_PASSWORD='', PYTHONDONTWRITEBYTECODE='1')
    ledger = {'runtime': str(runtime), 'port': port, 'pid': None, 'suites': [], 'exited': False,
              'started_utc': datetime.now(timezone.utc).isoformat(), 'complete_set': not args.suite}
    def save():
        (runtime/'results.json').write_text(json.dumps(ledger, indent=2), encoding='utf-8')
    def run(args, **kwargs):
        return subprocess.run(args, cwd=ROOT, env=env, capture_output=True,
                              text=True, encoding='utf-8', creationflags=FLAGS, **kwargs)
    print('RUNTIME: '+str(runtime), flush=True)
    save()
    database = None
    try:
        result = run([str(MYSQL/'mysql_install_db.exe'), '--datadir='+str(runtime/'data'), '--port='+str(port)], timeout=60)
        (runtime/'install.log').write_text(result.stdout+result.stderr, encoding='utf-8')
        if result.returncode:
            raise RuntimeError('Disposable initialization failed; see install.log')
        with (runtime/'console.log').open('w') as log:
            database = subprocess.Popen([str(MYSQL/'mysqld.exe'), '--no-defaults',
                '--basedir=C:/xampp/mysql', '--datadir='+str(runtime/'data'), '--port='+str(port),
                '--bind-address=127.0.0.1', '--innodb-buffer-pool-size=32M',
                '--log-error='+str(runtime/'database.log'), '--pid-file='+str(runtime/'database.pid')],
                stdout=log, stderr=log, creationflags=FLAGS)
            ledger['pid'] = database.pid
            save()
            for _ in range(100):
                if database.poll() is not None:
                    raise RuntimeError('Disposable database exited during startup')
                ping = run([str(MYSQL/'mysqladmin.exe'), '--no-defaults', '--host=127.0.0.1',
                            '--port='+str(port), '--user=root', 'ping'], timeout=5)
                if ping.returncode == 0:
                    break
                time.sleep(.1)
            else:
                raise RuntimeError('Disposable database unavailable')
            print(f'DATABASE: PID {database.pid}, loopback port {port}', flush=True)
            suites = [ROOT/'tests'/name for name in args.suite] if args.suite else sorted((ROOT/'tests').glob('*.test.py'))
            commands = [(p.name, [sys.executable, '-X', 'utf8', str(p)]) for p in suites]
            if not args.suite:
                commands += [('authentication-subdirectory', [sys.executable, '-X', 'utf8', 'tests/authentication.test.py', '--subdirectory'])]
                commands += [('javascript', ['node', '--test', '--test-reporter=tap'] + [str(p) for p in sorted((ROOT/'tests').glob('*.test.cjs'))])]
            for label, command in commands:
                print('RUN: '+label, flush=True)
                result = run(command)
                output = result.stdout+result.stderr
                (runtime/(label+'.log')).write_text(output, encoding='utf-8')
                match = re.search(r'PASS: (\d+)', output)
                skips = [line for line in output.splitlines() if re.search(r'skip|no eligible whole-hour', line, re.I)]
                entry = {'suite': label, 'exit_code': result.returncode,
                         'passed': int(match[1]) if match else None,
                         'failed': 0 if result.returncode == 0 else None,
                         'skipped': 5 if label=='booking_lifecycle.test.py' and 'no eligible whole-hour' in output else 0,
                         'skip_messages': skips}
                if label=='javascript':
                    for field, pattern in [('passed',r'# pass (\d+)'),('failed',r'# fail (\d+)'),('skipped',r'# skipped (\d+)')]:
                        found = re.search(pattern, output)
                        entry[field] = int(found[1]) if found else None
                ledger['suites'].append(entry)
                save()
                print(output, flush=True)
            inventory = run([str(MYSQL/'mysql.exe'), '--no-defaults', '--host=127.0.0.1',
                '--port='+str(port), '--user=root', '--batch', '-e',
                'SHOW DATABASES; SELECT User,Host FROM mysql.user;'])
            (runtime/'remaining-resources.log').write_text(inventory.stdout+inventory.stderr, encoding='utf-8')
            ledger['inventory_exit_code'] = inventory.returncode
    finally:
        if database is not None and database.poll() is None:
            shutdown = run([str(MYSQL/'mysqladmin.exe'), '--no-defaults', '--host=127.0.0.1',
                '--port='+str(port), '--user=root', 'shutdown'], timeout=30)
            ledger['shutdown_exit_code'] = shutdown.returncode
            try:
                database.wait(timeout=30)
            except subprocess.TimeoutExpired:
                print('RETAINED: process has not exited; do not remove runtime', flush=True)
        ledger['exited'] = database is not None and database.poll() is not None
        log = runtime/'database.log'
        ledger['shutdown_complete'] = log.exists() and 'Shutdown complete' in log.read_text(errors='replace')
        ledger['finished_utc'] = datetime.now(timezone.utc).isoformat()
        save()
        print('RESULTS: '+str(runtime/'results.json'), flush=True)
    return int(any(s['exit_code'] for s in ledger['suites']) or not ledger['exited'])


if __name__ == '__main__':
    sys.exit(main())
