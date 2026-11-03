"""Static Phase 3A schema/specification checks; does not connect to a database.

Run: python -X utf8 scripts/validate_database_schema.py
This is not a MariaDB SQL compiler or a substitute for a future test installation.
"""
import json
from pathlib import Path
import re

ROOT = Path(__file__).resolve().parents[1]


def identifiers(value):
    return re.findall(r'`([a-z_][a-z0-9_]*)`', value)


def balanced_sql(sql):
    """Check quoted strings/identifiers, parentheses and statement termination."""
    quote = None
    depth = 0
    index = 0
    clean = re.sub(r'(?m)^--.*$', '', sql).strip()
    while index < len(clean):
        char = clean[index]
        if quote:
            if char == quote:
                if index + 1 < len(clean) and clean[index + 1] == quote:
                    index += 1
                else:
                    quote = None
        elif char in "'`":
            quote = char
        elif char == '(':
            depth += 1
        elif char == ')':
            depth -= 1
            assert depth >= 0, 'Unmatched SQL closing parenthesis'
        elif char == ';':
            assert depth == 0, 'Statement ends inside parentheses'
        index += 1
    assert quote is None and depth == 0, 'Unclosed SQL string/parenthesis'
    assert clean.endswith(';'), 'Missing final statement terminator'


def main():
    sql = (ROOT / 'database/tutormequran.sql').read_text(encoding='utf-8')
    seed = (ROOT / 'database/seed-profile-field-settings.sql').read_text(encoding='utf-8')
    spec = json.loads((ROOT / 'docs/database-design-summary.json').read_text(encoding='utf-8'))
    design = (ROOT / 'docs/database-design-proposal.md').read_text(encoding='utf-8')
    balanced_sql(sql)
    balanced_sql(seed)
    statements = re.sub(r'(?m)^--.*$', '', sql)
    assert not re.search(r'\b(?:DROP|TRUNCATE|DELETE|REPLACE)\b', statements.replace('ON DELETE RESTRICT', ''))
    assert 'CREATE DATABASE `tutormequran` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;' in sql
    assert 'USE `tutormequran`;' in sql
    assert 'FOREIGN_KEY_CHECKS' not in sql

    parts = re.findall(r'CREATE TABLE `([a-z_]+)` \(\n(.*?)\n\) (ENGINE=.*?);', sql, re.S)
    tables = {name: body for name, body, _ in parts}
    expected = {table['name']: table for table in spec['tables']}
    assert len(parts) == len(tables) == len(expected) == 23
    assert tables.keys() == expected.keys(), 'SQL/spec table names differ'
    for name, _, options in parts:
        assert options == 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC', name

    sections = dict(re.findall(r'^### 3\.\d+ `([^`]+)`\n(.*?)(?=^### 3\.|^## 4\.)', design, re.M | re.S))
    column_types, keys, auto_ids = {}, {}, {}
    type_pattern = r'(?:INT UNSIGNED|SMALLINT UNSIGNED|TINYINT(?:\(\d+\))?(?: UNSIGNED)?|VARCHAR\(\d+\)|CHAR\(\d+\)|TEXT|DATETIME|DATE|DECIMAL\(\d+,\d+\))'
    for name, body in tables.items():
        declarations = dict(re.findall(r'^  `([a-z_][a-z0-9_]*)` (.*?)(?:,)?$', body, re.M))
        approved = {}
        for line in sections[name].splitlines():
            if not line.startswith('| ') or line.startswith(('| Column', '| ---')):
                continue
            col, datatype, _, nullable, _ = [v.strip() for v in line.strip('|').split('|')]
            approved[col] = ('INT UNSIGNED' if datatype.startswith('ID') else datatype, nullable)
        assert declarations.keys() == approved.keys(), (name, 'column set differs')
        column_types[name] = {}
        auto_ids[name] = []
        for col, declaration in declarations.items():
            match = re.match(type_pattern + r'(?=\s|$)', declaration)
            assert match, (name, col, 'unrecognized SQL type')
            datatype = match.group()
            assert datatype == approved[col][0], (name, col, datatype, approved[col])
            column_types[name][col] = datatype
            if 'GENERATED ALWAYS AS' in declaration:
                assert approved[col][1] == 'Yes' and declaration.endswith('PERSISTENT')
            else:
                required = approved[col][1] == 'No'
                assert (' NOT NULL' in declaration) == required, (name, col, 'nullability')
            if 'AUTO_INCREMENT' in declaration:
                auto_ids[name].append(col)
        pk = identifiers(re.search(r'PRIMARY KEY \(([^)]+)\)', body)[1])
        unique = [identifiers(value) for value in re.findall(r'UNIQUE KEY `[^`]+` \(([^)]+)\)', body)]
        assert pk == expected[name]['primary_key']
        assert unique == expected[name]['unique'], (name, 'UNIQUE differs')
        keys[name] = [pk] + unique
        assert auto_ids[name] == (pk if len(pk) == 1 else []), (name, 'AUTO_INCREMENT')

    # Collect both CREATE TABLE FKs and the circular relation added afterward.
    fk_pattern = r'CONSTRAINT `([^`]+)` FOREIGN KEY \(([^)]+)\) REFERENCES `([^`]+)` \(([^)]+)\) ON DELETE RESTRICT ON UPDATE RESTRICT'
    foreign = {name: re.findall(fk_pattern, body) for name, body in tables.items()}
    alters = re.findall(r'ALTER TABLE `([^`]+)` ADD (.*?);', sql)
    assert len(alters) == 1 and alters[0][0] == 'tutors'
    assert sql.index('ALTER TABLE `tutors`') > sql.index('CREATE TABLE `certificates`')
    assert sql.index('CREATE TABLE `tutors`') < sql.index('CREATE TABLE `certificates`')
    foreign['tutors'].extend(re.findall(fk_pattern, alters[0][1]))
    for name, fks in foreign.items():
        actual = {}
        indexes = keys[name] + [identifiers(value) for value in re.findall(r'(?m)^  KEY `[^`]+` \(([^)]+)\)', tables[name])]
        for _, local, parent, remote in fks:
            local, remote = identifiers(local), identifiers(remote)
            assert parent in tables and len(local) == len(remote)
            assert remote in keys[parent], (name, parent, 'FK target not PK/UNIQUE')
            assert any(key[:len(local)] == local for key in indexes), (name, local, 'missing FK index')
            for left, right in zip(local, remote):
                assert column_types[name][left] == column_types[parent][right], (name, left, 'FK type mismatch')
            actual[','.join(local)] = parent + '.' + ','.join(remote)
        assert actual == expected[name]['foreign_keys'], (name, 'FK differs')
    for name, fks in foreign.items():
        for _, _, parent, _ in fks:
            if parent == name or (name == 'tutors' and parent == 'certificates'):
                continue
            assert sql.index('CREATE TABLE `' + parent + '`') < sql.index('CREATE TABLE `' + name + '`')

    status_columns = {
        'users': {'role': 'user_role', 'account_status': 'account_status'},
        'tutors': {'teaching_mode': 'teaching_and_slot_mode', 'profile_visibility': 'profile_visibility'},
        'certificates': {'status': 'certificate_status', 'submission_purpose': 'certificate_purpose', 'qualification_level': 'qualification_level'},
        'availability_slots': {'mode': 'teaching_and_slot_mode', 'availability_status': 'availability_status_stored'},
        'bookings': {'class_mode': 'booking_class_mode', 'status': 'booking_status', 'cancellation_timing': 'cancellation_timing'},
        'booking_change_requests': {'original_mode': 'booking_class_mode', 'requested_mode': 'booking_class_mode', 'status': 'booking_change_status'},
        'payments': {'status': 'payment_status'},
        'payment_submissions': {'tutor_status': 'payment_submission_tutor_status', 'tutor_issue_type': 'payment_issue_type', 'admin_status': 'payment_submission_admin_status'},
        'messages': {'message_type': 'message_type'}, 'faqs': {'status': 'faq_status'},
        'support_requests': {'category': 'support_category', 'status': 'support_status'},
    }
    checked_statuses = 0
    for name, mapping in status_columns.items():
        for col, source in mapping.items():
            pattern = r'CONSTRAINT `ck_' + name + '_' + col + r'` CHECK \(`' + col + r'` IN \(([^)]+)\)\)'
            match = re.search(pattern, tables[name])
            assert match and re.findall(r"'([^']+)'", match[1]) == spec['statuses'][source], (name, col)
            assert re.search(r'`' + col + r'` VARCHAR\(\d+\) COLLATE utf8mb4_bin', tables[name])
            checked_statuses += 1

    assert "GENERATED ALWAYS AS (CASE WHEN `status` IN ('requested', 'accepted', 'completed', 'closed') THEN `slot_id` ELSE NULL END) PERSISTENT" in tables['bookings']
    assert "GENERATED ALWAYS AS (CASE WHEN `status` = 'pending' THEN `booking_id` ELSE NULL END) PERSISTENT" in tables['booking_change_requests']
    assert ['ic_number'] not in keys['users']
    for name, types in column_types.items():
        for col, datatype in types.items():
            if 'amount' in col or col in ('hourly_rate', 'hourly_rate_snapshot'):
                assert datatype == 'DECIMAL(10,2)', (name, col)
    assert 'CHECK (`rating` BETWEEN 1 AND 5)' in tables['feedback']
    assert 'CHECK (`attempt_number` >= 1)' in tables['payment_submissions']
    names = re.findall(r'CONSTRAINT `([^`]+)`', sql)
    assert len(names) == len(set(names)) and all(len(name) <= 64 for name in names)

    inserts = re.findall(r'INSERT INTO `([^`]+)`', sql)
    assert inserts == ['languages', 'lesson_focuses'], 'Base seed contains non-reference data'
    assert re.search(r"INSERT INTO `languages` .*?VALUES \('Bahasa Melayu'\), \('English'\), \('Arabic'\);", sql)
    assert re.search(r"INSERT INTO `lesson_focuses` .*?VALUES \('Tajweed'\), \('Quran Reading'\), \('Iqra'\), \('Hafazan'\), \('Talaqqi'\);", sql)
    settings = {
        'student': ['profile_picture', 'full_name', 'phone', 'address', 'gender', 'date_of_birth', 'password'],
        'tutor': ['profile_picture', 'full_name', 'phone', 'gender', 'date_of_birth', 'address', 'teaching_mode', 'teaching_state', 'teaching_location', 'languages', 'lesson_focus', 'hourly_rate', 'bio', 'social_networks', 'password'],
    }
    rows = re.findall(r"\('(student|tutor)', '([^']+)', 1, @profile_settings_admin_id, UTC_TIMESTAMP\(\)\)", seed)
    assert len(rows) == 22 and len(set(rows)) == 22
    for role, fields in settings.items():
        assert [key for kind, key in rows if kind == role] == fields
        allowed = re.search(r"`role` = '" + role + r"' AND `field_key` IN \(([^)]+)\)", tables['profile_field_settings'])
        assert allowed and re.findall(r"'([^']+)'", allowed[1]) == fields
    assert re.findall(r'INSERT INTO `([^`]+)`', seed) == ['profile_field_settings']
    assert 'STRICT_TRANS_TABLES' in seed and '@profile_settings_admin_id' in seed

    print(f'PASS: 23 tables, {sum(map(len, column_types.values()))} columns, {sum(map(len, foreign.values()))} FKs, exact PK/UNIQUE/type/nullability parity.')
    print(f'PASS: {checked_statuses} stored-status mappings, circular FK order, InnoDB/utf8mb4, DECIMAL money, generated UNIQUE rules.')
    print('PASS: 3 languages + 5 focuses; deferred 7 student + 15 tutor settings; no fake domain records or destructive statements.')
    print('STATIC VALIDATION ONLY: MariaDB runtime syntax/install/constraint execution is not tested by this script.')


if __name__ == '__main__':
    main()
