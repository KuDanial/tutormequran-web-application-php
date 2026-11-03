"""Phase 3C HTTP, upload and MariaDB tests. Only disposable DB/files are mutated.
Run: python tests/profile_verification.test.py --php C:/xampp/php/php.exe
"""
import argparse
import base64
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
import urllib.request

ROOT = Path(__file__).resolve().parents[1]
sys.dont_write_bytecode = True
spec = importlib.util.spec_from_file_location('auth_tests', ROOT/'tests/authentication.test.py')
auth = importlib.util.module_from_spec(spec)
spec.loader.exec_module(auth)
check = auth.check
class Browser(auth.Browser):
    def request(self,page,data=None):
        payload=None if data is None else auth.urllib.parse.urlencode(data).encode()
        try: response=self.opener.open(self.base+page,payload,timeout=15)
        except urllib.error.HTTPError as error: response=error
        return response.code,response.read().decode('utf-8','replace'),response.headers
PNG = base64.b64decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=')
JPEG = base64.b64decode('/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////2wBDAf//////////////////////////////////////////////////////////////////////////////////////wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAX/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIQAxAAAAF//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABBQJ//8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAwEBPwF//8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAgEBPwF//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQAGPwJ//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPyF//9oADAMBAAIAAwAAABD/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oACAEDAQE/EH//xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oACAECAQE/EH//xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oACAEBAAE/EH//2Q==')
def blank_pdf():
    objects=[b'<< /Type /Catalog /Pages 2 0 R >>',b'<< /Type /Pages /Kids [3 0 R] /Count 1 >>',b'<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << >> >>',b'<< /Length 3 >>\nstream\nq Q\nendstream']
    data=bytearray(b'%PDF-1.4\n');offsets=[0]
    for index,obj in enumerate(objects,1):
        offsets.append(len(data));data.extend(str(index).encode()+b' 0 obj\n'+obj+b'\nendobj\n')
    xref=len(data);data.extend(b'xref\n0 5\n0000000000 65535 f \n')
    for offset in offsets[1:]:data.extend(f'{offset:010d} 00000 n \n'.encode())
    data.extend(f'trailer\n<< /Root 1 0 R /Size 5 >>\nstartxref\n{xref}\n%%EOF\n'.encode())
    return bytes(data)
PDF = blank_pdf()


def upload(browser,page,data,field,filename,content,mime):
    boundary='tmq'+secrets.token_hex(12)
    body=bytearray()
    for key,value in data.items():
        values=value if isinstance(value,list) else [value]
        for item in values:
            body.extend(f'--{boundary}\r\nContent-Disposition: form-data; name="{key}"\r\n\r\n{item}\r\n'.encode())
    body.extend(f'--{boundary}\r\nContent-Disposition: form-data; name="{field}"; filename="{filename}"\r\nContent-Type: {mime}\r\n\r\n'.encode())
    body.extend(content);body.extend(f'\r\n--{boundary}--\r\n'.encode())
    request=urllib.request.Request(browser.base+page,bytes(body),{'Content-Type':'multipart/form-data; boundary='+boundary})
    try: response=browser.opener.open(request,timeout=20)
    except urllib.error.HTTPError as error: response=error
    return response.code,response.read().decode('utf-8','replace'),response.headers


def main():
    parser=argparse.ArgumentParser(description=__doc__);parser.add_argument('--php',default='C:/xampp/php/php.exe');args=parser.parse_args()
    name='tutormequran_auth_validation_'+secrets.token_hex(8)
    password='Profile8-'+secrets.token_hex(12)
    flags=subprocess.CREATE_NO_WINDOW if os.name=='nt' else 0
    created=False;server=None
    with tempfile.TemporaryDirectory(prefix='tmq-profile-tests-') as directory:
        directory=Path(directory).resolve();assert directory.is_relative_to(Path(tempfile.gettempdir()).resolve())
        env=dict(os.environ,TMQ_DB_NAME=name,TMQ_UPLOAD_ROOT=str(directory/'uploads'))
        bridge=directory/'bridge.php';bridge.write_text(auth.BRIDGE,encoding='utf-8')
        def php(action,data=None):
            result=subprocess.run([args.php,str(bridge),str(ROOT),action],env=env,input=json.dumps(data or {}),capture_output=True,text=True,encoding='utf-8',creationflags=flags)
            if result.returncode: raise RuntimeError(result.stderr+result.stdout)
            return json.loads(result.stdout)
        def sql(query,params=None): return php('query',{'sql':query,'params':params or []})
        try:
            php('create');created=True
            with socket.socket() as sock: sock.bind(('127.0.0.1',0));port=sock.getsockname()[1]
            base=f'http://127.0.0.1:{port}/'
            log=open(directory/'server.log','w',encoding='utf-8')
            server=subprocess.Popen([args.php,'-d','display_errors=0','-d','upload_max_filesize=6M','-d','post_max_size=8M','-d','session.save_path='+str(directory),'-S',f'127.0.0.1:{port}','-t',str(ROOT)],env=env,cwd=ROOT,stdout=log,stderr=log,creationflags=flags)
            anon=Browser(base)
            for _ in range(50):
                try: anon.request('index.php');break
                except urllib.error.URLError: time.sleep(.1)
            def account(role,username):
                b=Browser(base);prefix='stud' if role=='student' else 'tutor'
                form={'csrf_token':b.token('register.php'),'accountRoleType':role,'accountUsername':username,'accountPassword':password,'confirmPassword':password,prefix+'FName':username,prefix+'LName':'Test',prefix+'Email':username+'@example.invalid',prefix+'ICNum':'900101-01-9999',prefix+'Phone':'012-1234567',prefix+'Address':'PRIVATE ADDRESS NEVER PUBLIC',prefix+'Eligibility':'on'}
                check(b.request('register.php',form)[0]==303,role+' registration regression')
                login(b,username)
                return b,sql('SELECT user_id FROM users WHERE username=?',[username])[0]['user_id']
            def login(b,username):
                check(b.request('login.php',{'csrf_token':b.token('login.php'),'accountUsername':username,'accountPassword':password})[0]==303,'login '+username)
            student,student_id=account('student','pv_student');other_student,other_student_id=account('student','pv_other_student')
            tutor,tutor_user=account('tutor','pv_tutor');other_tutor,other_tutor_user=account('tutor','pv_other_tutor')
            tutor_id=sql('SELECT tutor_id FROM tutors WHERE user_id=?',[tutor_user])[0]['tutor_id'];other_tutor_id=sql('SELECT tutor_id FROM tutors WHERE user_id=?',[other_tutor_user])[0]['tutor_id']
            result=subprocess.run([args.php,str(ROOT/'scripts/create_admin.php'),'--init-settings'],input=json.dumps({'username':'pv_admin','email':'pv_admin@example.invalid','first_name':'Test','last_name':'Admin','password':password}),capture_output=True,text=True,env=env,creationflags=flags)
            check(result.returncode==0,'Isolated Admin provisioned')
            admin=Browser(base);login(admin,'pv_admin')
            def post(b,page,action,extra=None):
                data={'csrf_token':b.token(page),'profile_action':action};data.update(extra or {});return b.request(page,data)
            def photo(b,page,filename='photo.png',content=PNG,mime='image/png'):
                return upload(b,page,{'csrf_token':b.token(page),'profile_action':'personal'},'profilePicture',filename,content,mime)
            sp='student/student-profile-edit.php';tp='tutor/tutor-profile-edit.php'
            check('pv_student' in student.request('student/student-profile-view.php')[1],'Student own view')
            check(post(student,sp,'personal',{'studFName':'Changed Student','user_id':other_student_id})[0]==303,'Student edit ignores target ID')
            check(sql('SELECT first_name FROM users WHERE user_id=?',[other_student_id])[0]['first_name']=='pv_other_student','Other Student unchanged')
            check('Changed Student' in student.request('student/student-profile-view.php')[1],'Student actual view updated')
            def settings(disable=None,extra=None):
                fields={'editable['+r['role']+']['+r['field_key']+']':'1' for r in sql('SELECT role,field_key FROM profile_field_settings') if (r['role'],r['field_key'])!=disable}
                fields.update(extra or {});return post(admin,'admin/admin-settings.php','settings',fields)
            check(settings(('student','full_name'))[0]==303,'Admin disables approved field')
            check(post(student,sp,'personal',{'studFName':'Blocked'})[0]==422,'Disabled field server rejected')
            check('disabled aria-disabled="true"' in student.request(sp)[1],'Disabled control rendered')
            check(settings(extra={'editable[tutor][unapproved_field]':'1'})[0]==422,'Arbitrary field key rejected')
            check(student.request('admin/admin-settings.php',{'profile_action':'settings'})[0]==403,'Non Admin settings denied')
            check(settings()[0]==303,'Settings restored')
            check(post(student,sp,'personal',{'studLName':'<script>alert(1)</script>'})[0]==303,'Profile text accepts literal data')
            escaped=student.request('student/student-profile-view.php')[1]
            check('&lt;script&gt;alert(1)&lt;/script&gt;' in escaped and '<script>alert(1)</script>' not in escaped,'Stored text safely escaped')
            check(post(student,sp,'personal',{'studEmail':'pv_other_student@example.invalid'})[0]==422,'Duplicate profile email rejected')
            check(photo(student,sp)[0]==303,'PNG profile upload')
            check(photo(student,sp,'photo.jpg',JPEG,'image/jpeg')[0]==303,'JPG profile upload')
            oldphoto=sql('SELECT profile_picture_path FROM users WHERE user_id=?',[student_id])[0]['profile_picture_path']
            check(photo(student,sp,'bad.png',b'<?php echo "attack"; ?>')[0]==422,'Disguised executable rejected')
            check(photo(student,sp,'big.png',PNG+b'x'*5242880)[0]==422,'Oversized photo rejected')
            check(sql('SELECT profile_picture_path FROM users WHERE user_id=?',[student_id])[0]['profile_picture_path']==oldphoto,'Bad upload preserves old photo')
            check(anon.request('profile-file.php?kind=profile&id='+str(student_id))[0]==404,'Private student photo denied')
            check(post(tutor,tp,'personal',{'tutorFName':'Public Teacher','tutor_id':other_tutor_id,'user_id':other_tutor_user})[0]==303,'Tutor own personal edit')
            check(sql('SELECT first_name FROM users WHERE user_id=?',[other_tutor_user])[0]['first_name']=='pv_other_tutor','Other Tutor unchanged')
            check('Public Teacher' in tutor.request('tutor/tutor-account-edit.php')[1],'Tutor own fields displayed')
            check(post(tutor,'tutor/tutor-profile-visibility.php','visibility',{'profileVisibility':'public'})[0]==422,'Incomplete publication denied')
            check('at least one language' in tutor.request('tutor/tutor-profile-ready.php')[1],'Completeness identifies relational requirements')
            teaching={'displayName':'Public Teacher Display','teachingState':'Selangor','teachingLocation':'PUBLIC TEACHING AREA','teachingMode':'both','hourlyRate':'40.25','shortBio':'Patient Quran teaching','languages_present':'1','languages[]':['1','2','1'],'focuses_present':'1','focuses[]':['1','2','1']}
            # URL-encoded list values must be encoded with repeated keys.
            def teaching_post(values):
                data={'csrf_token':tutor.token(tp),'profile_action':'teaching',**values}
                return multipart_fields(tutor,tp,data)
            check(teaching_post(teaching)[0]==303,'Teaching profile transaction')
            check(sql('SELECT COUNT(*) n FROM tutor_languages WHERE tutor_id=?',[tutor_id])[0]['n']==2,'Languages relational/deduplicated')
            check(sql('SELECT COUNT(*) n FROM tutor_lesson_focuses WHERE tutor_id=?',[tutor_id])[0]['n']==2,'Focuses relational/deduplicated')
            check(sql('SELECT hourly_rate FROM tutors WHERE tutor_id=?',[tutor_id])[0]['hourly_rate']=='40.25','DECIMAL rate persisted')
            check(teaching_post(dict(teaching,teachingMode='invalid'))[0]==422,'Invalid teaching mode rejected')
            check(teaching_post(dict(teaching,**{'languages[]':['99999'],'shortBio':'Must roll back'}))[0]==422,'Invalid relationship rejected')
            check(sql('SELECT bio FROM tutors WHERE tutor_id=?',[tutor_id])[0]['bio']=='Patient Quran teaching','Atomic profile rollback')
            check(photo(tutor,'tutor/tutor-account-edit.php')[0]==303,'Tutor photo uploaded')
            check(post(tutor,'tutor/tutor-profile-visibility.php','visibility',{'profileVisibility':'public'})[0]==422,'No approved certificate publication denied')
            certpage='tutor/tutor-certificate-upload.php'
            def certificate(b,purpose='initial',filename='qualification.pdf',content=PDF,mime='application/pdf',parent=None,page=certpage):
                form={'csrf_token':b.token(page),'profile_action':'certificate','submissionPurpose':purpose,'certificateName':'Qualification '+secrets.token_hex(3),'certificateType':'Quran teaching','certificateYear':'2024','qualificationLevel':'ijazah','certificateOrigin':'Selangor','certificateDescription':'Certificate test'}
                if parent:form['replacesCertificate']=str(parent)
                return upload(b,page,form,'certificateFile',filename,content,mime)
            check(certificate(tutor)[0]==303,'Valid PDF certificate')
            first=sql('SELECT * FROM certificates WHERE tutor_id=? ORDER BY certificate_id',[tutor_id])[0]
            check(first['tutor_id']==tutor_id and first['status']=='pending','Submission owned/pending')
            check(certificate(other_tutor,filename='qualification.png',content=PNG,mime='image/png')[0]==303,'PNG certificate accepted')
            other_cert=sql('SELECT certificate_id FROM certificates WHERE tutor_id=?',[other_tutor_id])[0]['certificate_id']
            certurl='profile-file.php?kind=certificate&id='+str(first['certificate_id'])
            check(tutor.request(certurl)[0]==200,'Owner certificate download')
            check(admin.request(certurl)[0]==200,'Admin certificate access')
            check(student.request(certurl)[0]==404 and anon.request(certurl)[0]==404,'Student/public certificate denied')
            check(other_tutor.request(certurl)[0]==404,'Other Tutor certificate denied')
            check(tutor.request('profile-file.php?kind=certificate&id=999999')[0]==404,'Unknown file denied')
            check(anon.request(first['file_path'])[0]==404,'Storage key not publicly served')
            check(first['file_path'] not in tutor.request('tutor/tutor-certificate-view.php')[1],'Physical/storage path not rendered')
            def review(certid,decision,remark='',display=False):
                return post(admin,'admin/manage-certificates-view.php?id='+str(certid),'review',{'decision':decision,'certificateAdminRemark':remark,'makeDisplayed':'1' if display else '0'})
            check(tutor.request('admin/manage-certificates-view.php?id='+str(first['certificate_id']))[0]==403,'Tutor cannot review own certificate')
            check(post(tutor,'tutor/tutor-certificate-view.php','displayed',{'displayed_certificate_id':first['certificate_id']})[0]==422,'Pending certificate cannot be displayed')
            check(review(first['certificate_id'],'approved',display=True)[0]==303,'Admin approves and explicitly selects display')
            saved=sql('SELECT * FROM certificates WHERE certificate_id=?',[first['certificate_id']])[0]
            check(saved['reviewed_by_admin_id'] is not None and saved['reviewed_at'] is not None,'Reviewer and time stored')
            check(review(first['certificate_id'],'rejected','Cannot change approval')[0]==422,'Review transition protected')
            check(post(tutor,'tutor/tutor-profile-visibility.php','visibility',{'profileVisibility':'public'})[0]==303,'Complete approved profile public')
            check(sql('SELECT COUNT(*) n FROM availability_slots')[0]['n']==0,'No availability required')
            listing=anon.request('tutors.php')[1];check('Public Teacher Display' in listing,'Eligible Tutor listed with no slots')
            detail='tutor-detail.php?id='+str(tutor_id);status,body,_=anon.request(detail)
            check(status==200 and 'PUBLIC TEACHING AREA' in body and '40.25' in body,'Actual public detail')
            for secret in ['pv_tutor@example.invalid','900101-01-9999','PRIVATE ADDRESS NEVER PUBLIC',first['file_path']]:check(secret not in body,'Sensitive field hidden')
            check(anon.request('profile-file.php?kind=profile&id='+str(tutor_user))[0]==200,'Eligible public photo delivered')
            check(post(tutor,'tutor/tutor-profile-visibility.php','visibility',{'profileVisibility':'private'})[0]==303,'Set private')
            check('Public Teacher Display' not in anon.request('tutors.php')[1] and anon.request(detail)[0]==404,'Private listing/detail hidden')
            check(post(tutor,'tutor/tutor-profile-visibility.php','visibility',{'profileVisibility':'public'})[0]==303,'Publish again')
            sql("UPDATE users SET account_status='inactive' WHERE user_id=?",[tutor_user])
            check('Public Teacher Display' not in anon.request('tutors.php')[1] and anon.request(detail)[0]==404,'Inactive tutor never public')
            sql("UPDATE users SET account_status='active' WHERE user_id=?",[tutor_user])
            check(certificate(tutor,'higher_qualification',filename='higher.jpg',content=JPEG,mime='image/jpeg')[0]==303,'JPG higher qualification accepted')
            higher=sql('SELECT MAX(certificate_id) id FROM certificates WHERE tutor_id=?',[tutor_id])[0]['id']
            check(sql('SELECT displayed_certificate_id FROM tutors WHERE tutor_id=?',[tutor_id])[0]['displayed_certificate_id']==first['certificate_id'],'Pending higher keeps displayed approval')
            check(review(higher,'rejected')[0]==422,'Rejection reason required')
            check(review(higher,'rejected','Please submit a clearer certificate')[0]==303,'Admin rejection')
            check(sql('SELECT admin_remark FROM certificates WHERE certificate_id=?',[higher])[0]['admin_remark']=='Please submit a clearer certificate','Rejection remark persists')
            check(sql('SELECT displayed_certificate_id FROM tutors WHERE tutor_id=?',[tutor_id])[0]['displayed_certificate_id']==first['certificate_id'],'Rejected higher keeps earlier displayed qualification')
            check(post(tutor,'tutor/tutor-certificate-view.php','displayed',{'displayed_certificate_id':higher})[0]==422,'Rejected qualification cannot be displayed')
            check(review(other_cert,'approved')[0]==303,'Other tutor approved independently')
            check(post(tutor,'tutor/tutor-certificate-view.php','displayed',{'displayed_certificate_id':other_cert})[0]==422,'Foreign approved qualification rejected')
            check(certificate(tutor,'application_correction',parent=higher,page='tutor/tutor-certificate-replace.php')[0]==303,'Rejected certificate corrected with new record')
            correction=sql('SELECT MAX(certificate_id) id FROM certificates WHERE tutor_id=?',[tutor_id])[0]['id']
            check(certificate(tutor,'application_correction',parent=correction,page='tutor/tutor-certificate-replace.php')[0]==303,'Pending correction creates next version')
            check(sql('SELECT status FROM certificates WHERE certificate_id=?',[correction])[0]['status']=='superseded','Replaced pending marked superseded')
            latest=sql('SELECT MAX(certificate_id) id FROM certificates WHERE tutor_id=?',[tutor_id])[0]['id']
            check(review(latest,'approved')[0]==303,'New qualification approved without automatic selection')
            check(sql('SELECT displayed_certificate_id FROM tutors WHERE tutor_id=?',[tutor_id])[0]['displayed_certificate_id']==first['certificate_id'],'Approval does not rank or overwrite selection')
            check(post(tutor,'tutor/tutor-certificate-view.php','displayed',{'displayed_certificate_id':latest})[0]==303,'Tutor explicitly selects owned approved qualification')
            check(sql('SELECT status FROM certificates WHERE certificate_id=?',[first['certificate_id']])[0]['status']=='approved','Earlier approved history retained')
            for filename,content,mime,label in [('bad.exe',PDF,'application/pdf','extension rejected'),('bad.png',b'<?php echo 1; ?>','image/png','disguised executable certificate rejected'),('huge.pdf',PDF+b'x'*5242880,'application/pdf','oversized certificate rejected')]:
                check(certificate(tutor,'higher_qualification',filename,content,mime)[0]==422,label)
            traversed=certificate(tutor,'higher_qualification','../evil.pdf',PDF,'application/pdf')
            check(traversed[0] in (303,422),'Traversal filename rejected or sanitized by PHP')
            if traversed[0]==303:
                record=sql('SELECT original_filename,file_path FROM certificates ORDER BY certificate_id DESC LIMIT 1')[0]
                check('/' not in record['original_filename'] and '..' not in record['file_path'],'Sanitized filename and generated storage key')
            qr='tutor/tutor-payment-qr.php'
            check(upload(tutor,qr,{'csrf_token':tutor.token(qr),'profile_action':'qr'},'duitnowQr','qr.png',PNG,'image/png')[0]==303,'DuitNow QR image stored')
            check(upload(tutor,qr,{'csrf_token':tutor.token(qr),'profile_action':'qr'},'duitnowQr','qr.php',b'<?php ?>','image/png')[0]==422,'QR executable rejected')
            check(anon.request('profile-file.php?kind=qr&id='+str(tutor_id))[0]==404,'QR not public')
            check(sql('SELECT COUNT(*) n FROM payments')[0]['n']==0,'No payment workflow')
            check(post(tutor,tp,'social',{'facebook':'javascript:alert(1)'})[0]==422,'Unsafe social link rejected')
            check(student.request(sp,{'profile_action':'personal','csrf_token':'bad','studFName':'CSRF'})[0]==403,'Profile CSRF')
            check(admin.request('admin/manage-certificates-view.php?id='+str(latest),{'profile_action':'review','csrf_token':'bad','decision':'rejected'})[0]==403,'Review CSRF')
            check(admin.request('admin/admin-settings.php',{'profile_action':'settings','csrf_token':'bad'})[0]==403,'Settings CSRF')
            check('Public Teacher' in admin.request('admin/manage-tutors-view.php?id='+str(tutor_id))[1],'Admin sees real tutor')
            # Remove an eligibility requirement directly to simulate inconsistent legacy data.
            sql('UPDATE tutors SET bio=NULL WHERE tutor_id=?',[tutor_id])
            check(anon.request(detail)[0]==404 and 'Public Teacher Display' not in anon.request('tutors.php')[1],'Public flag never bypasses completeness')
            check('CHECK TABLE' not in admin.request('admin/manage-certificates.php')[1],'No database internals rendered')
            print(f'PASS: {auth.CHECKS} Phase 3C assertions covering profiles, relations, settings, certificates, visibility, files and auth integration.')
        finally:
            if server: server.terminate();server.wait(timeout=15);log.close()
            if created: php('drop');print('CLEANUP: only task-owned database and temporary uploads removed: '+name)


def multipart_fields(browser,page,data):
    # PHP treats the empty upload as UPLOAD_ERR_NO_FILE; all ordinary fields persist.
    return upload(browser,page,data,'unused','','','application/octet-stream')


if __name__=='__main__': main()
