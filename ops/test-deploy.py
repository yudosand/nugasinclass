"""Exercise deployment/rollback on disposable directories with fake PHP and HTTP."""
import hashlib,io,json,os,pathlib,subprocess,tarfile,tempfile

source=pathlib.Path(__file__).with_name('deploy.sh').read_text()
paths=source.split('PATHS=(',1)[1].split(')',1)[0].split()
directories={'app','config','routes','database/migrations','database/seeders','database/factories','resources/views','resources/css','resources/js','public/assets','vendor','bootstrap/cache'}
commit='a'*40
for failure in ['', 'config:cache', 'http', 'digest']:
    with tempfile.TemporaryDirectory(prefix='nugasin-deploy-test-') as folder:
        root=pathlib.Path(folder);app=root/'app';state=root/'state';stage=root/'release';tools=root/'bin'
        for p in [app,state/'incoming',stage,tools,app/'storage/framework',app/'resources/exams']:
            p.mkdir(parents=True,exist_ok=True)
        for path in paths:
            for base,text in [(app,'old'),(stage,'new')]:
                target=base/path
                if path in directories:target=target/'fixture.txt'
                target.parent.mkdir(parents=True,exist_ok=True);target.write_text(text)
        for path in ['.env','resources/exams/manifest.json','resources/exams/questions.json','storage/customer.txt']:
            (app/path).write_text('preserve')
        (stage/'release.json').write_text(json.dumps({'commit':commit}))
        (stage/'verify-release.php').write_text('fixture')
        (stage/'public/assets/release.json').write_text(json.dumps({'commit':commit}))
        archive=state/'incoming'/f'{commit}.tar.gz'
        with tarfile.open(archive,'w:gz') as tar:tar.add(stage,arcname='.')
        digest=hashlib.sha256(archive.read_bytes()).hexdigest()
        php=tools/'php'
        php.write_text('''#!/usr/bin/env bash
set -e
if [[ "${2:-}" == "down" ]]; then touch "$TEST_APP/storage/framework/down"; fi
if [[ "${2:-}" == "up" ]]; then rm -f "$TEST_APP/storage/framework/down"; fi
if [[ -n "$FAILURE" && "${2:-}" == "$FAILURE" ]]; then exit 1; fi
''')
        curl=tools/'curl'
        curl.write_text('''#!/usr/bin/env bash
if [[ "$FAILURE" == "http" ]]; then exit 22; fi
for arg in "$@"; do if [[ "$arg" == *release.json* ]]; then printf '{"commit":"%s"}' "$TEST_COMMIT"; fi; done
''')
        php.chmod(0o700);curl.chmod(0o700)
        script=root/'deploy.sh'
        script.write_text(source.replace('APP=/home/nugasinclass/htdocs/nugasinclass.web.id',f'APP={app}').replace('STATE=/home/nugasindeploy/.nugasin-deploy',f'STATE={state}').replace('PHP=/usr/bin/php8.5',f'PHP={php}'))
        env=os.environ|{'PATH':str(tools)+':'+os.environ['PATH'],'TEST_APP':str(app),'TEST_COMMIT':commit,'FAILURE':failure}
        result=subprocess.run(['bash',str(script),commit,'0'*64 if failure=='digest' else digest],env=env,capture_output=True,text=True)
        assert (result.returncode==0)==(not failure),(failure,result.stdout,result.stderr)
        for path in paths:
            target=app/path
            if path in directories:target=target/'fixture.txt'
            assert target.read_text()==('old' if failure else 'new'),(failure,path)
        for path in ['.env','resources/exams/manifest.json','resources/exams/questions.json','storage/customer.txt']:
            assert (app/path).read_text()=='preserve',(failure,path)
        assert not (app/'storage/framework/down').exists()
        print('PASS deployment sandbox: '+(failure or 'success'))
