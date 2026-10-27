<?php

// Operational cutover: back up first, rehearse on copies, then move tables without copying rows.
require __DIR__.'/../vendor/autoload.php';

use Illuminate\Database\ConfigurationUrlParser;
use Symfony\Component\Process\Process;

$root = dirname(__DIR__);
$env = Dotenv\Dotenv::createArrayBacked($root)->safeLoad();
$cache = $root.'/bootstrap/cache/config.php';
$config = is_file($cache) ? require $cache : null;
$directory = $argv[2] ?? '';
$mode = $argv[1] ?? '';
if (!in_array($mode, ['backup', 'restore-copy', 'move', 'verify'], true) || !is_dir($directory)) {
    throw new RuntimeException('Usage: php scripts/separate-databases.php backup|restore-copy|move|verify PRIVATE_BACKUP_DIRECTORY [--copy]');
}
$directory = realpath($directory);
$privateRoot = realpath($root.'/storage/app/private/backups');
if ($privateRoot === false || !str_starts_with(str_replace('\\', '/', $directory).'/', str_replace('\\', '/', $privateRoot).'/')) {
    throw new RuntimeException('Use a directory under storage/app/private/backups.');
}
$base = [
    'driver'=>'mysql', 'host'=>$env['DB_HOST']??'127.0.0.1', 'port'=>$env['DB_PORT']??3306,
    'username'=>$env['DB_USERNAME']??'root', 'password'=>$env['DB_PASSWORD']??'',
    'database'=>$env['DB_DATABASE']??'orgchain', 'url'=>$env['DB_URL']??null,
];
$settings = (new ConfigurationUrlParser())->parseConfiguration($config['database']['connections']['mysql']??$base);
$pdo = new PDO('mysql:host='.$settings['host'].';port='.$settings['port'].';charset=utf8mb4', $settings['username'], $settings['password'], [
    PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
    PDO::ATTR_STRINGIFY_FETCHES=>true, PDO::ATTR_EMULATE_PREPARES=>false,
]);
function ident(string $value): string { return '`'.str_replace('`', '``', $value).'`'; }
function tableName(string $schema, string $table): string { return ident($schema).'.'.ident($table); }
function rowsHash(PDO $pdo, string $schema, string $table, array $columns, array $primary, ?int $maxId = null): array {
    $sql = 'SELECT '.implode(',',array_map('ident',$columns)).' FROM '.tableName($schema,$table);
    if ($maxId !== null) $sql .= ' WHERE `id` <= '.(int)$maxId;
    $sql .= ' ORDER BY '.implode(',',array_map('ident',$primary));
    $statement = $pdo->query($sql); $hash = hash_init('sha256'); $count = 0;
    while ($row = $statement->fetch(PDO::FETCH_NUM)) { hash_update($hash, serialize($row)); ++$count; }
    return ['rows'=>$count,'sha256'=>hash_final($hash)];
}
function snapshot(PDO $pdo, string $schema): array {
    $q = $pdo->quote($schema);
    $tables = $pdo->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=$q AND TABLE_TYPE='BASE TABLE' ORDER BY TABLE_NAME")->fetchAll(PDO::FETCH_COLUMN);
    $unsupported = (int)$pdo->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=$q AND TABLE_TYPE<>'BASE TABLE'")->fetchColumn()
        +(int)$pdo->query("SELECT COUNT(*) FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA=$q")->fetchColumn();
    if ($unsupported) throw new RuntimeException('Review views/triggers before moving schema '.$schema);
    $result=[];
    foreach ($tables as $table) {
        $columns=$pdo->query('SHOW COLUMNS FROM '.tableName($schema,$table))->fetchAll(PDO::FETCH_COLUMN);
        $primary=$pdo->query('SHOW INDEX FROM '.tableName($schema,$table)." WHERE Key_name='PRIMARY'")->fetchAll();
        usort($primary,fn($a,$b)=>(int)$a['Seq_in_index']<=>(int)$b['Seq_in_index']);
        $primary=array_column($primary,'Column_name');
        if (!$primary) throw new RuntimeException('No deterministic primary key on '.$schema.'.'.$table);
        $create=$pdo->query('SHOW CREATE TABLE '.tableName($schema,$table))->fetch(PDO::FETCH_NUM)[1];
        $result[$table]=array_merge(['columns'=>$columns,'primary'=>$primary,'create'=>$create],rowsHash($pdo,$schema,$table,$columns,$primary));
        if ($table==='migrations') $result[$table]['maxId']=(int)$pdo->query('SELECT MAX(id) FROM '.tableName($schema,$table))->fetchColumn();
    }
    return $result;
}
function writeJson(string $path, array $value): void {
    if (file_put_contents($path,json_encode($value,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR))===false) throw new RuntimeException('Cannot write '.$path);
}
function plan(array $manifest, array $schemas): array {
    $voting=['admin_users','elections','positions','candidates','voters','votes','vote_receipts','audit_logs','security_events','rate_limit_hits'];
    $oldCore=['analytics_reports','approvals','archived_reports','audit_trails','proposals','reports','student_organizations'];
    $oldOffice=['students','community_posts','community_comments','community_likes'];
    $pairs=[];
    foreach ($manifest['snapshots']['core'] as $table=>$record) {
        if (in_array($table,$oldCore,true)) $pairs[]=['fromSchema'=>$schemas['core'],'from'=>$table,'toSchema'=>$schemas['core'],'to'=>'legacy_'.$table,'source'=>'core'];
    }
    foreach ($manifest['snapshots']['voting'] as $table=>$record) {
        if (in_array($table,$voting,true)) continue;
        $pairs[]=['fromSchema'=>$schemas['voting'],'from'=>$table,'toSchema'=>$schemas['core'],'to'=>(in_array($table,$oldOffice,true)?'legacy_':'').$table,'source'=>'voting'];
    }
    return $pairs;
}
function verifyRows(PDO $pdo,array $manifest,array $schemas,bool $moved): array {
    $mapping=[];
    if ($moved) foreach (plan($manifest,$schemas) as $pair) $mapping[$pair['source'].'|'.$pair['from']]=[$pair['toSchema'],$pair['to']];
    $checked=0; $different=[];
    foreach ($manifest['snapshots'] as $role=>$tables) foreach ($tables as $table=>$before) {
        [$schema,$target]=$mapping[$role.'|'.$table]??[$schemas[$role],$table];
        $actual=rowsHash($pdo,$schema,$target,$before['columns'],$before['primary'],$moved&&$table==='migrations'?($before['maxId']??null):null);
        if ($actual['rows']!==$before['rows'] || $actual['sha256']!==$before['sha256']) $different[]=$schema.'.'.$target;
        ++$checked;
    }
    if ($different) throw new RuntimeException('Preserved-row verification failed: '.implode(', ',$different));
    return ['tablesChecked'=>$checked,'originalRowsUnchanged'=>true];
}
function verifyRelationships(PDO $pdo, array $schemas): array {
    $quoted=implode(',',array_map(fn($schema)=>$pdo->quote($schema),array_values($schemas)));
    $keys=$pdo->query("SELECT TABLE_SCHEMA AS childSchema,TABLE_NAME AS childTable,CONSTRAINT_NAME AS name,COLUMN_NAME AS childColumn,REFERENCED_TABLE_SCHEMA AS parentSchema,REFERENCED_TABLE_NAME AS parentTable,REFERENCED_COLUMN_NAME AS parentColumn FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA IN ($quoted) AND REFERENCED_TABLE_NAME IS NOT NULL ORDER BY TABLE_SCHEMA,TABLE_NAME,CONSTRAINT_NAME,ORDINAL_POSITION")->fetchAll();
    $groups=[];
    foreach ($keys as $key) {
        if (!in_array($key['parentSchema'],$schemas,true)) throw new RuntimeException('A foreign key still points outside the selected database pair.');
        $groups[$key['childSchema'].'.'.$key['childTable'].'.'.$key['name']][]=$key;
    }
    foreach ($groups as $keys) {
        $first=$keys[0]; $present=[]; $matches=[];
        foreach ($keys as $key) {
            $present[]='child.'.ident($key['childColumn']).' IS NOT NULL';
            $matches[]='parent.'.ident($key['parentColumn']).'=child.'.ident($key['childColumn']);
        }
        $sql='SELECT COUNT(*) FROM '.tableName($first['childSchema'],$first['childTable']).' child WHERE '.implode(' AND ',$present)
            .' AND NOT EXISTS (SELECT 1 FROM '.tableName($first['parentSchema'],$first['parentTable']).' parent WHERE '.implode(' AND ',$matches).')';
        if ((int)$pdo->query($sql)->fetchColumn()) throw new RuntimeException('Orphaned rows at '.$first['childSchema'].'.'.$first['childTable']);
    }
    return ['foreignKeysChecked'=>count($groups),'foreignKeyOrphans'=>0];
}
function runClient(array $settings,string $binary,array $arguments,?string $inputFile=null): void {
    $path=PHP_OS_FAMILY==='Windows'?'C:/laragon/bin/mysql/mysql-8.4.3-winx64/bin/'.$binary.'.exe':$binary;
    $process=new Process(array_merge([$path,'--host='.$settings['host'],'--port='.$settings['port'],'--user='.$settings['username']],$arguments),null,['MYSQL_PWD'=>$settings['password']]);
    if ($inputFile!==null) $process->setInput(fopen($inputFile,'rb'));
    $process->setTimeout(180); $process->mustRun();
}
$manifestPath=$directory.'/manifest.json';
if ($mode==='backup') {
    if (is_file($manifestPath)) throw new RuntimeException('Preserving an existing backup manifest.');
    $oldCore=(new ConfigurationUrlParser())->parseConfiguration($config['database']['connections']['orgchain']??array_merge($base,['database'=>$env['DB_ORGCHAIN_DATABASE']??'orgchain']));
    if ($settings['database']===$oldCore['database']) throw new RuntimeException('Backup mode expects the original separated student/core and mixed voting schema.');
    $schemas=['voting'=>$settings['database'],'core'=>$oldCore['database']];
    $manifest=['createdAt'=>gmdate('c'),'schemas'=>$schemas,'server'=>['host'=>$settings['host'],'port'=>$settings['port']],'snapshots'=>[],'dumps'=>[]];
    foreach ($schemas as $role=>$schema) {
        $file=$directory.'/'.$role.'.sql';
        $manifest['snapshots'][$role]=snapshot($pdo,$schema);
        runClient($settings,'mysqldump',['--single-transaction','--no-tablespaces','--hex-blob','--routines','--triggers','--events','--set-gtid-purged=OFF','--column-statistics=0','--result-file='.$file,$schema]);
        if (filesize($file)<1000) throw new RuntimeException('Unexpectedly small backup for '.$schema);
        $manifest['dumps'][$role]=['file'=>basename($file),'bytes'=>filesize($file),'sha256'=>hash_file('sha256',$file)];
    }
    $copySuffix=strtolower(substr(hash('sha256',$directory),0,10));
    $manifest['copySchemas']=['core'=>'orgchain_splitcheck_'.$copySuffix.'_core','voting'=>'orgchain_splitcheck_'.$copySuffix.'_voting'];
    $manifest['moves']=plan($manifest,$schemas);
    $manifest['reverseSql']='RENAME TABLE '.implode(', ',array_map(fn($p)=>tableName($p['toSchema'],$p['to']).' TO '.tableName($p['fromSchema'],$p['from']),array_reverse($manifest['moves']))).';';
    writeJson($manifestPath,$manifest);
    echo json_encode(['backup'=>$directory,'schemas'=>$schemas,'tables'=>array_map('count',$manifest['snapshots']),'dumps'=>$manifest['dumps'],'moves'=>count($manifest['moves'])],JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR).PHP_EOL;
    exit;
}
$manifest=json_decode(file_get_contents($manifestPath),true,512,JSON_THROW_ON_ERROR);
foreach ($manifest['dumps'] as $dump) if (!hash_equals($dump['sha256'],hash_file('sha256',$directory.'/'.$dump['file']))) throw new RuntimeException('Backup checksum mismatch.');
$copy=in_array('--copy',$argv,true);
$schemas=$copy?$manifest['copySchemas']:$manifest['schemas'];
if ($mode==='restore-copy') {
    foreach ($manifest['copySchemas'] as $role=>$schema) {
        if (!preg_match('/^orgchain_splitcheck_[a-f0-9]{10}_(core|voting)$/',$schema)) throw new RuntimeException('Unsafe disposable schema name.');
        $pdo->exec('CREATE DATABASE '.ident($schema).' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        runClient($settings,'mysql',['--binary-mode',$schema],$directory.'/'.$manifest['dumps'][$role]['file']);
    }
    $result=verifyRows($pdo,$manifest,$manifest['copySchemas'],false);
    writeJson($directory.'/restore-proof.json',$result+['schemas'=>$manifest['copySchemas']]);
    echo json_encode($result+['schemas'=>$manifest['copySchemas']],JSON_THROW_ON_ERROR).PHP_EOL;
    exit;
}
if ($mode==='move') {
    if (!$copy && !is_file($root.'/storage/framework/down')) throw new RuntimeException('Enter maintenance mode before the live cutover.');
    if (!$copy && !is_file($directory.'/restore-proof.json')) throw new RuntimeException('Restore and verify the backups on disposable copies first.');
    verifyRows($pdo,$manifest,$schemas,false);
    $pairs=plan($manifest,$schemas); $sources=[];
    foreach ($pairs as $pair) $sources[$pair['fromSchema'].'.'.$pair['from']]=true;
    foreach ($pairs as $pair) {
        $statement=$pdo->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=? AND TABLE_NAME=?');
        $statement->execute([$pair['toSchema'],$pair['to']]);
        if ($statement->fetchColumn() && !isset($sources[$pair['toSchema'].'.'.$pair['to']])) throw new RuntimeException('Target table already exists: '.$pair['toSchema'].'.'.$pair['to']);
    }
    $sql='RENAME TABLE '.implode(', ',array_map(fn($p)=>tableName($p['fromSchema'],$p['from']).' TO '.tableName($p['toSchema'],$p['to']),$pairs));
    $pdo->exec($sql);
    $result=verifyRows($pdo,$manifest,$schemas,true);
    writeJson($directory.'/'.($copy?'copy-move-proof':'live-move-proof').'.json',$result+['moves'=>count($pairs),'schemas'=>$schemas]);
    echo json_encode($result+['moves'=>count($pairs),'schemas'=>$schemas],JSON_THROW_ON_ERROR).PHP_EOL;
    exit;
}
$result=verifyRows($pdo,$manifest,$schemas,true);
$result+=verifyRelationships($pdo,$schemas);
$schemaTables=[];
foreach ($schemas as $role=>$schema) {
    $statement=$pdo->prepare('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=? ORDER BY TABLE_NAME');
    $statement->execute([$schema]); $schemaTables[$role]=$statement->fetchAll(PDO::FETCH_COLUMN);
}
$votingTables=['admin_users','elections','positions','candidates','voters','votes','vote_receipts','audit_logs','security_events','rate_limit_hits'];
if (array_diff($schemaTables['voting'],$votingTables) || array_intersect($schemaTables['core'],$votingTables)) throw new RuntimeException('Core/voting table ownership is not separated.');
writeJson($directory.'/'.($copy?'copy-final-proof':'live-final-proof').'.json',$result+['tables'=>$schemaTables]);
echo json_encode($result+['tables'=>$schemaTables],JSON_THROW_ON_ERROR).PHP_EOL;
