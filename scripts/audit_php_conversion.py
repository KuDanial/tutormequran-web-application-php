"""Lint/render Phase 1 pages and compare them with the retained HTML references.

Run: python scripts/audit_php_conversion.py --php C:/xampp/php/php.exe
Uses only Python's standard library and the existing frontend document audit.
"""
import argparse
from collections import Counter
from html.parser import HTMLParser
from pathlib import Path
import re
import shutil
import subprocess
from urllib.parse import unquote, urlsplit, urljoin
from urllib.request import urlopen

from audit_frontend import Document, ROOT


class Markup(HTMLParser):
    """Compare elements, attributes and content, ignoring formatting/comments."""
    def __init__(self, source):
        super().__init__(convert_charrefs=True)
        self.events = []
        self.feed(source)

    def handle_starttag(self, tag, attrs):
        self.events.append(('start', tag, sorted(attrs)))

    def handle_startendtag(self, tag, attrs):
        self.handle_starttag(tag, attrs)

    def handle_endtag(self, tag):
        self.events.append(('end', tag))

    def handle_data(self, data):
        text = re.sub(r'\s+', ' ', data).strip()
        if text:
            self.events.append(('text', text))


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--php', default=shutil.which('php') or 'C:/xampp/php/php.exe')
    parser.add_argument('--base-url', help='Optional running PHP server URL, including the project subdirectory')
    args = parser.parse_args()
    failures = []
    sources = sorted(ROOT.glob('*.html'))
    for folder in ['student', 'tutor', 'admin', 'report']:
        sources += sorted((ROOT / folder).glob('*.html'))
    php_files = sorted(ROOT.rglob('*.php'))
    for path in php_files:
        result = subprocess.run([args.php, '-l', str(path)], capture_output=True, text=True, encoding='utf-8')
        if result.returncode:
            failures.append(f'PHP lint: {path.relative_to(ROOT)}: {result.stdout}{result.stderr}')
    documents = {}
    http_assets = set()
    existing_issues = Counter()
    for source in sources:
        path = source.with_suffix('.php')
        if not path.is_file():
            failures.append(f'Missing PHP equivalent: {source.relative_to(ROOT)}')
            continue
        result = subprocess.run([args.php, '-d', 'display_errors=stderr', '-d', 'error_reporting=-1', str(path)],
                                cwd=ROOT, capture_output=True, text=True, encoding='utf-8')
        if result.returncode or result.stderr:
            failures.append(f'Render: {path.relative_to(ROOT)}: {result.stderr}')
            continue
        expected = source.read_text(encoding='utf-8-sig').replace('.html', '.php')
        if args.base_url:
            page_url = urljoin(args.base_url.rstrip('/') + '/', path.relative_to(ROOT).as_posix())
            with urlopen(page_url, timeout=10) as response:
                served = response.read().decode('utf-8')
                if response.status != 200 or served != result.stdout:
                    failures.append(f'HTTP render differs: {page_url}')
        actual_events = Markup(result.stdout).events
        expected_events = Markup(expected).events
        if actual_events != expected_events:
            for i, (actual, old) in enumerate(zip(actual_events, expected_events)):
                if actual != old:
                    failures.append(f'Markup differs: {path.relative_to(ROOT)} event {i}: {actual!r} != {old!r}')
                    break
            else:
                failures.append(f'Markup event count differs: {path.relative_to(ROOT)}')
        doc = Document(path)
        doc.feed(result.stdout)
        doc.finish()
        baseline = Document(path)
        baseline.feed(expected)
        baseline.finish()
        prior = Counter((item['code'], item['detail']) for item in baseline.issues)
        after = Counter((item['code'], item['detail']) for item in doc.issues)
        existing_issues.update(item['code'] for item in baseline.issues)
        for item in (after - prior):
            failures.append(f'New frontend issue: {path.relative_to(ROOT)}: {item}')
        documents[path.resolve()] = doc
        for node in doc.nodes:
            for attr in ['href', 'src', 'action', 'formaction', 'poster']:
                target = node['attrs'].get(attr, '')
                url = urlsplit(target)
                if not target or url.scheme or url.netloc:
                    continue
                if url.path.endswith('.html'):
                    failures.append(f'HTML route: {path.relative_to(ROOT)}: {target}')
                dest = path.parent / unquote(url.path) if url.path else path
                if not dest.exists():
                    failures.append(f'Missing link/asset: {path.relative_to(ROOT)}: {target}')
                if args.base_url and attr in ['src', 'href'] and url.path and dest.is_file() and dest.suffix != '.php':
                    http_assets.add(urljoin(page_url, target))
    for path, doc in documents.items():
        for node in doc.nodes:
            target = node['attrs'].get('href', '')
            url = urlsplit(target)
            if url.scheme or url.netloc or not url.fragment:
                continue
            other = documents.get((path.parent / unquote(url.path)).resolve() if url.path else path)
            if other and not any(n['attrs'].get('id') == unquote(url.fragment) for n in other.nodes):
                failures.append(f'Missing anchor: {path.relative_to(ROOT)}: {target}')
    for path in php_files + list((ROOT / 'assets/js').glob('*.js')):
        if '.html' in path.read_text(encoding='utf-8'):
            failures.append(f'Old extension in active source: {path.relative_to(ROOT)}')
    # JS route constants and interpolated availability edit/view links.
    for path in (ROOT / 'assets/js').glob('*.js'):
        text = path.read_text(encoding='utf-8')
        routes = re.findall(r'[\w/-]+\.php', text)
        if 'tutor-availability-${action.toLowerCase()}.php' in text:
            routes.extend(['tutor-availability-view.php', 'tutor-availability-edit.php'])
        for route in routes:
            candidates = [ROOT / route] + [ROOT / folder / route for folder in ['student', 'tutor', 'admin', 'report']]
            if not any(p.is_file() for p in candidates):
                failures.append(f'Missing JS route: {path.relative_to(ROOT)}: {route}')
    if len(list((ROOT / 'report').glob('*.php'))) != 1:
        failures.append('Expected exactly one report PHP page')
    for url in sorted(http_assets):
        with urlopen(url, timeout=10) as response:
            if response.status != 200:
                failures.append(f'HTTP asset failed: {url}')
    print(f'PHP lint: {len(php_files)} files; rendered pages: {len(documents)}/{len(sources)}')
    print(f'Existing HTML audit findings (unchanged): {dict(existing_issues)}')
    if args.base_url:
        print(f'HTTP checks: {len(documents)} pages and {len(http_assets)} assets under {args.base_url}')
    if failures:
        print('\n'.join(failures))
        raise SystemExit(1)
    print('PASS: markup parity, includes, assets, routes, anchors, forms and frontend audit regression checks.')


if __name__ == '__main__':
    main()
