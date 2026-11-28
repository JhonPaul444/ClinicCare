<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
date_default_timezone_set(getenv('CLINIC_TIMEZONE') ?: 'Asia/Singapore');

try {
    $pdo = cliniccare_pdo();
    $pdo->exec('SET time_zone = ' . $pdo->quote((new DateTimeImmutable())->format('P')));
    $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    $path = trim((string)($_GET['resource'] ?? ''), '/');
    $parts = $path === '' ? [] : explode('/', $path);
    $id = isset($parts[1]) && ctype_digit($parts[1]) ? (int)$parts[1] : null;
    $input = in_array($method, ['POST','PUT','PATCH','DELETE'], true) ? cliniccare_input() : [];

    if (($parts[0] ?? '') === 'auth' && ($parts[1] ?? '') === 'login' && $method === 'POST') {
        cliniccare_verify_csrf();
        $name = cliniccare_required($input, 'username', 'Username');
        $pass = (string)($input['password'] ?? '');
        if ($pass === '') cliniccare_json(['error' => 'Password is required.'], 422);
        if (!cliniccare_login($name, $pass)) cliniccare_json(['error'=>'Username or password is incorrect, or this account is inactive.'], 401);
        cliniccare_json(['user'=>cliniccare_user(), 'csrf_token'=>cliniccare_csrf_token()]);
    }
    if (($parts[0] ?? '') === 'auth' && ($parts[1] ?? '') === 'csrf' && $method === 'GET') {
        cliniccare_json(['csrf_token'=>cliniccare_csrf_token()]);
    }
    if (($parts[0] ?? '') === 'auth' && ($parts[1] ?? '') === 'logout' && $method === 'POST') {
        cliniccare_require_auth(); cliniccare_verify_csrf(); cliniccare_logout(); cliniccare_json(['message'=>'Signed out.']);
    }
    $user = cliniccare_require_auth();
    if (in_array($method, ['POST','PUT','PATCH','DELETE'], true)) cliniccare_verify_csrf();

    $admin = ['Admin']; $adminReception = ['Admin','Receptionist'];

    // PATIENTS: receptionists and administrators can administer demographics.
    if (($parts[0] ?? '') === 'patients') {
        cliniccare_require_auth($adminReception);
        if ($method === 'GET') {
            if ($id) {
                $s=$pdo->prepare('SELECT patient_id,full_name,birth_date,gender,contact_number,address,date_registered,status,created_at,updated_at FROM patients WHERE patient_id=?'); $s->execute([$id]);
                $row=$s->fetch(); if(!$row) cliniccare_json(['error'=>'Patient not found.'],404); cliniccare_json(['data'=>$row]);
            }
            $q=trim((string)($_GET['q']??'')); $status=(string)($_GET['status']??'');
            $sql='SELECT patient_id,full_name,birth_date,gender,contact_number,address,date_registered,status FROM patients WHERE 1=1'; $args=[];
            if($q!==''){ $sql.=' AND (full_name LIKE ? OR contact_number LIKE ?)'; $args[]='%'.$q.'%'; $args[]='%'.$q.'%'; }
            if($status!==''){ $sql.=' AND status=?'; $args[]=cliniccare_status($status,['Active','Inactive']); }
            $sql.=' ORDER BY full_name LIMIT 250'; $s=$pdo->prepare($sql);$s->execute($args);cliniccare_json(['data'=>$s->fetchAll()]);
        }
        if ($method==='POST') {
            $name=cliniccare_required($input,'full_name','Full name'); $birth=cliniccare_required($input,'birth_date','Birth date'); cliniccare_not_future_date($birth,'Birth date');
            $gender=cliniccare_required($input,'gender','Gender'); if(!cliniccare_gender_is_allowed($gender))cliniccare_json(['error'=>'Select Male, Female, or Prefer not to say for gender.'],422); $contact=cliniccare_required($input,'contact_number','Contact number'); $address=cliniccare_required($input,'address','Address');
            foreach([['full_name',$name,'Full name'],['gender',$gender,'Gender'],['contact_number',$contact,'Contact number'],['address',$address,'Address']] as [$col,$val,$label]) cliniccare_length($pdo,'patients',$col,$val,$label);
            $s=$pdo->prepare("INSERT INTO patients(full_name,birth_date,gender,contact_number,address,date_registered,status) VALUES(?,?,?,?,?,CURRENT_DATE,'Active')");$s->execute([$name,$birth,$gender,$contact,$address]);cliniccare_json(['message'=>'Patient registered.','patient_id'=>(int)$pdo->lastInsertId()],201);
        }
        if ($id && $method==='PUT') {
            $name=cliniccare_required($input,'full_name','Full name');$birth=cliniccare_required($input,'birth_date','Birth date');cliniccare_not_future_date($birth,'Birth date');$gender=cliniccare_required($input,'gender','Gender');$contact=cliniccare_required($input,'contact_number','Contact number');$address=cliniccare_required($input,'address','Address');
            if(!cliniccare_gender_is_allowed($gender)){$existingGender=$pdo->prepare('SELECT gender FROM patients WHERE patient_id=?');$existingGender->execute([$id]);$storedGender=$existingGender->fetchColumn();if($storedGender===false)cliniccare_json(['error'=>'Patient not found.'],404);if((string)$storedGender!==$gender)cliniccare_json(['error'=>'Choose Male, Female, or Prefer not to say. To preserve the patient’s existing value, leave it unchanged.'],422);}
            foreach([['full_name',$name,'Full name'],['gender',$gender,'Gender'],['contact_number',$contact,'Contact number'],['address',$address,'Address']] as [$col,$val,$label]) cliniccare_length($pdo,'patients',$col,$val,$label);
            $s=$pdo->prepare('UPDATE patients SET full_name=?,birth_date=?,gender=?,contact_number=?,address=?,updated_at=CURRENT_TIMESTAMP WHERE patient_id=?');$s->execute([$name,$birth,$gender,$contact,$address,$id]);if(!$s->rowCount()){$x=$pdo->prepare('SELECT patient_id FROM patients WHERE patient_id=?');$x->execute([$id]);if(!$x->fetch())cliniccare_json(['error'=>'Patient not found.'],404);}cliniccare_json(['message'=>'Patient updated.']);
        }
        if ($id && $method==='PATCH') { $status=cliniccare_status((string)($input['status']??''),['Active','Inactive']);$s=$pdo->prepare('UPDATE patients SET status=?,updated_at=CURRENT_TIMESTAMP WHERE patient_id=?');$s->execute([$status,$id]);if(!$s->rowCount())cliniccare_json(['error'=>'Patient not found or unchanged.'],404);cliniccare_json(['message'=>'Patient status updated.']); }
    }

    // USERS: admin-only account administration; passwords are always hashed.
    if (($parts[0]??'')==='users') {
        cliniccare_require_auth($admin);
        if($method==='GET'){$sql='SELECT user_id,full_name,username,role,status,created_at,updated_at FROM users';if($id){$s=$pdo->prepare($sql.' WHERE user_id=?');$s->execute([$id]);$row=$s->fetch();if(!$row)cliniccare_json(['error'=>'Account not found.'],404);cliniccare_json(['data'=>$row]);}$s=$pdo->query($sql.' ORDER BY full_name');cliniccare_json(['data'=>$s->fetchAll()]);}
        if($method==='POST'){$name=cliniccare_required($input,'full_name','Full name');$username=cliniccare_required($input,'username','Username');$password=(string)($input['password']??'');if($password==='')cliniccare_json(['error'=>'Password is required.'],422);$role=cliniccare_status((string)($input['role']??''),['Admin','Receptionist','Doctor'],'Role');if(strlen($password)<12)cliniccare_json(['error'=>'Password must be at least 12 characters.'],422);cliniccare_length($pdo,'users','full_name',$name,'Full name');cliniccare_length($pdo,'users','username',$username,'Username');$s=$pdo->prepare("INSERT INTO users(full_name,username,password_hash,role,status) VALUES(?,?,?,?,'Active')");$s->execute([$name,$username,password_hash($password,PASSWORD_DEFAULT),$role]);cliniccare_json(['message'=>'Account created.','user_id'=>(int)$pdo->lastInsertId()],201);}
        if($id&&$method==='PUT'){$exists=$pdo->prepare('SELECT user_id FROM users WHERE user_id=?');$exists->execute([$id]);if(!$exists->fetch())cliniccare_json(['error'=>'Account not found.'],404);$name=cliniccare_required($input,'full_name','Full name');$role=cliniccare_status((string)($input['role']??''),['Admin','Receptionist','Doctor'],'Role');$status=cliniccare_status((string)($input['status']??''),['Active','Inactive']);cliniccare_length($pdo,'users','full_name',$name,'Full name');if($role!=='Doctor'){$linked=$pdo->prepare('SELECT doctor_id FROM doctors WHERE user_id=?');$linked->execute([$id]);if($linked->fetch())cliniccare_json(['error'=>'A user linked to a doctor profile must keep the Doctor role. Deactivate the profile instead.'],409);}$password=(string)($input['password']??'');if($password!==''&&strlen($password)<12)cliniccare_json(['error'=>'Password must be at least 12 characters.'],422);if($password!==''){$s=$pdo->prepare('UPDATE users SET full_name=?,role=?,status=?,password_hash=?,updated_at=CURRENT_TIMESTAMP WHERE user_id=?');$s->execute([$name,$role,$status,password_hash($password,PASSWORD_DEFAULT),$id]);}else{$s=$pdo->prepare('UPDATE users SET full_name=?,role=?,status=?,updated_at=CURRENT_TIMESTAMP WHERE user_id=?');$s->execute([$name,$role,$status,$id]);}cliniccare_json(['message'=>'Account updated.']);}
    }

    // SPECIALIZATIONS.
    if(($parts[0]??'')==='specializations'){
        cliniccare_require_auth($admin);
        if($method==='GET'){$s=$pdo->query('SELECT specialization_id,specialization_name,description,status FROM specializations ORDER BY specialization_name');cliniccare_json(['data'=>$s->fetchAll()]);}
        if($method==='POST'||($id&&$method==='PUT')){$name=cliniccare_required($input,'specialization_name','Specialization name');$desc=trim((string)($input['description']??''));$status=cliniccare_status((string)($input['status']??'Active'),['Active','Inactive']);cliniccare_length($pdo,'specializations','specialization_name',$name,'Specialization name');cliniccare_length($pdo,'specializations','description',$desc,'Description',false);if($method==='POST'){$s=$pdo->prepare('INSERT INTO specializations(specialization_name,description,status) VALUES(?,?,?)');$s->execute([$name,$desc===''?null:$desc,$status]);cliniccare_json(['message'=>'Specialization created.','specialization_id'=>(int)$pdo->lastInsertId()],201);}$s=$pdo->prepare('UPDATE specializations SET specialization_name=?,description=?,status=? WHERE specialization_id=?');$s->execute([$name,$desc===''?null:$desc,$status,$id]);cliniccare_json(['message'=>'Specialization updated.']);}
    }

    // DOCTORS: profile records are retained and deactivated, never deleted.
    if(($parts[0]??'')==='doctors'){
        if($method==='GET'){
            cliniccare_require_auth(['Admin','Receptionist']);
            $sql='SELECT d.doctor_id,d.user_id,u.full_name,u.username,u.status AS account_status,d.specialization_id,s.specialization_name,d.license_number,d.status FROM doctors d JOIN users u ON u.user_id=d.user_id LEFT JOIN specializations s ON s.specialization_id=d.specialization_id';
            if($id){$s=$pdo->prepare($sql.' WHERE d.doctor_id=?');$s->execute([$id]);$r=$s->fetch();if(!$r)cliniccare_json(['error'=>'Doctor not found.'],404);cliniccare_json(['data'=>$r]);}
            cliniccare_json(['data'=>$pdo->query($sql.' ORDER BY u.full_name')->fetchAll()]);
        }
        cliniccare_require_auth($admin);
        if($method==='POST'){$uid=cliniccare_id($input,'user_id','User');$sid=cliniccare_id($input,'specialization_id','Specialization');$license=trim((string)($input['license_number']??''));cliniccare_length($pdo,'doctors','license_number',$license,'License number',false);$doctorId=cliniccare_transaction($pdo,function()use($pdo,$uid,$sid,$license){$s=$pdo->prepare("SELECT user_id FROM users WHERE user_id=? AND role='Doctor' AND status='Active' FOR UPDATE");$s->execute([$uid]);if(!$s->fetch())cliniccare_json(['error'=>'Select an active user with the Doctor role.'],422);$s=$pdo->prepare('SELECT doctor_id FROM doctors WHERE user_id=?');$s->execute([$uid]);if($s->fetch())cliniccare_json(['error'=>'This user already has a doctor profile.'],409);$s=$pdo->prepare("SELECT specialization_id FROM specializations WHERE specialization_id=? AND status='Active'");$s->execute([$sid]);if(!$s->fetch())cliniccare_json(['error'=>'Select an active specialization.'],422);$s=$pdo->prepare('INSERT INTO doctors(user_id,specialization_id,license_number,status) VALUES(?,?,?,\'Active\')');$s->execute([$uid,$sid,$license===''?null:$license]);return (int)$pdo->lastInsertId();});cliniccare_json(['message'=>'Doctor profile created.','doctor_id'=>$doctorId],201);}
        if($id&&($method==='PUT'||$method==='PATCH')){
            if($method==='PATCH'){
                $status=cliniccare_status((string)($input['status']??''),['Active','Inactive']);
                cliniccare_transaction($pdo,function()use($pdo,$id,$status){
                    $s=$pdo->prepare('SELECT doctor_id FROM doctors WHERE doctor_id=? FOR UPDATE');$s->execute([$id]);
                    if(!$s->fetch())cliniccare_json(['error'=>'Doctor not found.'],404);
                    $s=$pdo->prepare('UPDATE doctors SET status=?,updated_at=CURRENT_TIMESTAMP WHERE doctor_id=?');$s->execute([$status,$id]);
                });
                cliniccare_json(['message'=>'Doctor status updated.']);
            }
            $s=$pdo->prepare('SELECT doctor_id FROM doctors WHERE doctor_id=?');$s->execute([$id]);if(!$s->fetch())cliniccare_json(['error'=>'Doctor not found.'],404);
            $sid=cliniccare_id($input,'specialization_id','Specialization');$license=trim((string)($input['license_number']??''));cliniccare_length($pdo,'doctors','license_number',$license,'License number',false);$s=$pdo->prepare("SELECT specialization_id FROM specializations WHERE specialization_id=? AND status='Active'");$s->execute([$sid]);if(!$s->fetch())cliniccare_json(['error'=>'Select an active specialization.'],422);$s=$pdo->prepare('UPDATE doctors SET specialization_id=?,license_number=?,updated_at=CURRENT_TIMESTAMP WHERE doctor_id=?');$s->execute([$sid,$license===''?null:$license,$id]);cliniccare_json(['message'=>'Doctor profile updated.']);
        }
    }

    // SCHEDULES: doctor row lock serializes schedule edits and bookings.
    if(($parts[0]??'')==='schedules'){
        if($method==='GET'){
            $scheduleViewer=cliniccare_require_auth(['Admin','Receptionist']);
            $doctorId=isset($_GET['doctor_id'])?(int)$_GET['doctor_id']:null;
            if($scheduleViewer['role']==='Receptionist'){
                if(!$doctorId)cliniccare_json(['error'=>'Select a doctor to view active schedule availability.'],400);
                $s=$pdo->prepare("SELECT schedule_id,doctor_id,available_day,start_time,end_time,status FROM doctor_schedules WHERE doctor_id=? AND status='Active' ORDER BY available_day,start_time");
                $s->execute([$doctorId]);
            }elseif($doctorId){
                $s=$pdo->prepare('SELECT schedule_id,doctor_id,available_day,start_time,end_time,status FROM doctor_schedules WHERE doctor_id=? ORDER BY available_day,start_time');
                $s->execute([$doctorId]);
            }else{
                $s=$pdo->query('SELECT schedule_id,doctor_id,available_day,start_time,end_time,status FROM doctor_schedules ORDER BY doctor_id,available_day,start_time');
            }
            cliniccare_json(['data'=>$s->fetchAll()]);
        }
        cliniccare_require_auth($admin);
        if(($parts[1]??'')==='weekly'&&$method==='PUT'){
            $doctorId=cliniccare_id($input,'doctor_id','Doctor');$submittedDays=$input['days']??null;
            if(!is_array($submittedDays)||count($submittedDays)!==7)cliniccare_json(['error'=>'Submit availability for all seven days of the week.'],422);
            $days=[];
            foreach($submittedDays as $item){
                if(!is_array($item))cliniccare_json(['error'=>'A weekly schedule day is invalid.'],422);
                $day=filter_var($item['available_day']??null,FILTER_VALIDATE_INT);
                if($day===false||$day<1||$day>7||isset($days[$day]))cliniccare_json(['error'=>'Each weekday must appear exactly once.'],422);
                if(!is_bool($item['enabled']??null))cliniccare_json(['error'=>'Choose whether each weekday is available.'],422);
                $ranges=$item['ranges']??null;if(!is_array($ranges))cliniccare_json(['error'=>'Working hours must be supplied as time ranges.'],422);
                $normalizedRanges=[];
                if($item['enabled']){
                    if(count($ranges)<1)cliniccare_json(['error'=>'Add at least one working time range for every available day.'],422);
                    foreach($ranges as $range){
                        if(!is_array($range))cliniccare_json(['error'=>'A working time range is invalid.'],422);
                        $start=cliniccare_time((string)($range['start_time']??''),'Start time');$end=cliniccare_time((string)($range['end_time']??''),'End time');
                        if($start<'07:00:00'||$end>'19:00:00')cliniccare_json(['error'=>'Doctor schedules must be within clinic hours, 7:00 AM to 7:00 PM.'],422);
                        if($end<=$start)cliniccare_json(['error'=>'Each end time must be later than its start time.'],422);
                        $normalizedRanges[]=['start_time'=>$start,'end_time'=>$end];
                    }
                    usort($normalizedRanges,static fn($a,$b)=>strcmp($a['start_time'],$b['start_time']));
                    for($i=1;$i<count($normalizedRanges);$i++)if($normalizedRanges[$i]['start_time']<$normalizedRanges[$i-1]['end_time'])cliniccare_json(['error'=>'Working time ranges on the same day cannot overlap or duplicate one another.'],422);
                }
                $days[$day]=['enabled'=>$item['enabled'],'ranges'=>$normalizedRanges];
            }
            if(count($days)!==7)cliniccare_json(['error'=>'Each weekday must appear exactly once.'],422);
            ksort($days);
            cliniccare_transaction($pdo,function()use($pdo,$doctorId,$days){
                $doctor=$pdo->prepare('SELECT d.doctor_id,d.status,u.status AS account_status FROM doctors d JOIN users u ON u.user_id=d.user_id WHERE d.doctor_id=? FOR UPDATE');$doctor->execute([$doctorId]);$profile=$doctor->fetch();
                if(!$profile)cliniccare_json(['error'=>'Doctor not found.'],404);
                if(strcasecmp((string)$profile['status'],'Active')!==0||strcasecmp((string)$profile['account_status'],'Active')!==0)cliniccare_json(['error'=>'Only doctors with an active profile and account can be scheduled.'],422);
                $scheduleQuery=$pdo->prepare('SELECT schedule_id,available_day,start_time,end_time,status FROM doctor_schedules WHERE doctor_id=? ORDER BY available_day,schedule_id FOR UPDATE');$scheduleQuery->execute([$doctorId]);$existing=$scheduleQuery->fetchAll();$byDay=[];
                foreach($existing as $schedule)$byDay[(int)$schedule['available_day']][]=$schedule;
                $appointments=$pdo->prepare("SELECT appointment_date,appointment_time,duration_minutes,WEEKDAY(appointment_date)+1 AS available_day FROM appointments WHERE doctor_id=? AND status IN ('Scheduled','Completed','No-show') AND appointment_date>=CURRENT_DATE ORDER BY appointment_date,appointment_time");$appointments->execute([$doctorId]);
                foreach($appointments->fetchAll() as $appointment){
                    $day=(int)$appointment['available_day'];$proposed=$days[$day];$start=(string)$appointment['appointment_time'];$endSeconds=strtotime($start)+(int)$appointment['duration_minutes']*60;$end=date('H:i:s',$endSeconds);
                    $covered=$proposed['enabled']&&$endSeconds<86400;
                    if($covered){$covered=false;foreach($proposed['ranges'] as $range)if($range['start_time']<=$start&&$range['end_time']>=$end){$covered=true;break;}}
                    if(!$covered)cliniccare_json(['error'=>"Cannot save this weekly schedule: an upcoming appointment on {$appointment['appointment_date']} would fall outside the doctor's new hours. Reschedule that appointment first."],409);
                }
                foreach($days as $day=>$config){
                    $availableRanges=$config['enabled']?$config['ranges']:[];$rows=$byDay[$day]??[];$used=[];
                    foreach($availableRanges as $range){
                        $match=null;
                        foreach($rows as $index=>$row)if(!isset($used[$index])&&(string)$row['start_time']===$range['start_time']&&(string)$row['end_time']===$range['end_time']){$match=$index;break;}
                        if($match===null)foreach($rows as $index=>$row)if(!isset($used[$index])){$match=$index;break;}
                        if($match===null){$insert=$pdo->prepare("INSERT INTO doctor_schedules(doctor_id,available_day,start_time,end_time,status) VALUES(?,?,?,?,'Active')");$insert->execute([$doctorId,$day,$range['start_time'],$range['end_time']]);}
                        else{$used[$match]=true;$update=$pdo->prepare("UPDATE doctor_schedules SET available_day=?,start_time=?,end_time=?,status='Active' WHERE schedule_id=? AND doctor_id=?");$update->execute([$day,$range['start_time'],$range['end_time'],$rows[$match]['schedule_id'],$doctorId]);}
                    }
                    foreach($rows as $index=>$row)if(!isset($used[$index])&&(string)$row['status']!=='Inactive'){$deactivate=$pdo->prepare("UPDATE doctor_schedules SET status='Inactive' WHERE schedule_id=? AND doctor_id=?");$deactivate->execute([$row['schedule_id'],$doctorId]);}
                }
            });
            cliniccare_json(['message'=>'Weekly availability saved. Existing schedule records were retained.']);
        }
        if(in_array($method,['POST','PUT'],true)){$scheduleId=$method==='PUT'?cliniccare_id($input,'schedule_id','Schedule'):null;$doctorId=cliniccare_id($input,'doctor_id','Doctor');$day=filter_var($input['available_day']??null,FILTER_VALIDATE_INT);$start=cliniccare_time((string)($input['start_time']??''),'Start time');$end=cliniccare_time((string)($input['end_time']??''),'End time');$status=cliniccare_status((string)($input['status']??'Active'),['Active','Inactive']);if($day===false||$day<1||$day>7)cliniccare_json(['error'=>'Available day must be from 1 (Monday) through 7 (Sunday).'],422);if($end<=$start)cliniccare_json(['error'=>'End time must be later than start time.'],422);if($start<'07:00:00'||$end>'19:00:00')cliniccare_json(['error'=>'Doctor schedules must be within clinic hours, 7:00 AM to 7:00 PM.'],422);
            cliniccare_transaction($pdo,function()use($pdo,$doctorId,$day,$start,$end,$status,$scheduleId,$method){$lock=$pdo->prepare('SELECT doctor_id FROM doctors WHERE doctor_id=? FOR UPDATE');$lock->execute([$doctorId]);if(!$lock->fetch())cliniccare_json(['error'=>'Doctor not found.'],404);$skip=$scheduleId??0;$s=$pdo->prepare('SELECT schedule_id FROM doctor_schedules WHERE doctor_id=? AND available_day=? AND status=\'Active\' AND ?<end_time AND ?>start_time AND schedule_id<>?');$s->execute([$doctorId,$day,$start,$end,$skip]);if($status==='Active'&&$s->fetch())cliniccare_json(['error'=>'This schedule overlaps another active schedule.'],409);
                if($method==='PUT'){$check=$pdo->prepare('SELECT schedule_id FROM doctor_schedules WHERE schedule_id=? AND doctor_id=?');$check->execute([$scheduleId,$doctorId]);if(!$check->fetch())cliniccare_json(['error'=>'Schedule not found for this doctor.'],404);}
                // Do not deactivate or shorten availability beneath an existing booked appointment.
                if($status!=='Active'||$method==='PUT'){$affectedDays=[$day];if($method==='PUT'){$oldSchedule=$pdo->prepare('SELECT available_day FROM doctor_schedules WHERE schedule_id=? AND doctor_id=?');$oldSchedule->execute([$scheduleId,$doctorId]);$oldDay=$oldSchedule->fetchColumn();if($oldDay===false)cliniccare_json(['error'=>'Schedule not found for this doctor.'],404);$affectedDays[]=(int)$oldDay;}$affectedDays=array_values(array_unique($affectedDays));$q=$pdo->prepare("SELECT a.appointment_time,a.duration_minutes,WEEKDAY(a.appointment_date)+1 AS appointment_day FROM appointments a WHERE a.doctor_id=? AND a.status IN ('Scheduled','Completed','No-show') AND a.appointment_date>=CURRENT_DATE AND WEEKDAY(a.appointment_date)+1=?");foreach($affectedDays as $appointmentDay){$q->execute([$doctorId,$appointmentDay]);foreach($q->fetchAll() as $a){$as=(string)$a['appointment_time'];$endSeconds=strtotime($as)+(int)$a['duration_minutes']*60;$ae=date('H:i:s',$endSeconds);$covered=$endSeconds<86400&&$as!=='';$cover=$pdo->prepare("SELECT schedule_id FROM doctor_schedules WHERE doctor_id=? AND available_day=? AND status='Active' AND schedule_id<>? AND start_time<=? AND end_time>=? LIMIT 1");$cover->execute([$doctorId,$appointmentDay,$skip,$as,$ae]);$candidateCovers=($method==='PUT'&&$status==='Active'&&(int)$day===(int)$appointmentDay&&$start<=$as&&$end>=$ae);if(!$covered||(!$cover->fetch()&&!$candidateCovers))cliniccare_json(['error'=>'This change would leave an existing appointment outside the doctor schedule.'],409);}}}
                if($method==='POST'){$s=$pdo->prepare('INSERT INTO doctor_schedules(doctor_id,available_day,start_time,end_time,status) VALUES(?,?,?,?,?)');$s->execute([$doctorId,$day,$start,$end,$status]);return (int)$pdo->lastInsertId();}$s=$pdo->prepare('UPDATE doctor_schedules SET available_day=?,start_time=?,end_time=?,status=? WHERE schedule_id=? AND doctor_id=?');$s->execute([$day,$start,$end,$status,$scheduleId,$doctorId]);return $scheduleId;});cliniccare_json(['message'=>'Schedule saved.']);}
        if($method==='PATCH'&&$id){$status=cliniccare_status((string)($input['status']??''),['Active','Inactive']);$s=$pdo->prepare('SELECT doctor_id,available_day,start_time,end_time FROM doctor_schedules WHERE schedule_id=?');$s->execute([$id]);$old=$s->fetch();if(!$old)cliniccare_json(['error'=>'Schedule not found.'],404);$doctorId=(int)$old['doctor_id'];cliniccare_transaction($pdo,function()use($pdo,$id,$doctorId,$old,$status){$s=$pdo->prepare('SELECT doctor_id FROM doctors WHERE doctor_id=? FOR UPDATE');$s->execute([$doctorId]);if(!$s->fetch())cliniccare_json(['error'=>'Doctor not found.'],404);if($status==='Active'){$s=$pdo->prepare("SELECT schedule_id FROM doctor_schedules WHERE doctor_id=? AND available_day=? AND status='Active' AND ?<end_time AND ?>start_time AND schedule_id<>?");$s->execute([$doctorId,(int)$old['available_day'],$old['start_time'],$old['end_time'],$id]);if($s->fetch())cliniccare_json(['error'=>'This schedule overlaps another active schedule.'],409);}if($status==='Inactive'){$q=$pdo->prepare("SELECT appointment_time,duration_minutes FROM appointments WHERE doctor_id=? AND status IN ('Scheduled','Completed','No-show') AND WEEKDAY(appointment_date)+1=? AND appointment_date>=CURRENT_DATE");$q->execute([$doctorId,(int)$old['available_day']]);foreach($q->fetchAll() as $a){$end=date('H:i:s',strtotime((string)$a['appointment_time'])+(int)$a['duration_minutes']*60);$c=$pdo->prepare("SELECT schedule_id FROM doctor_schedules WHERE doctor_id=? AND available_day=? AND status='Active' AND schedule_id<>? AND start_time<=? AND end_time>=? LIMIT 1");$c->execute([$doctorId,(int)$old['available_day'],$id,$a['appointment_time'],$end]);if(!$c->fetch())cliniccare_json(['error'=>'This change would leave an existing appointment outside the doctor schedule.'],409);}}$s=$pdo->prepare('UPDATE doctor_schedules SET status=? WHERE schedule_id=?');$s->execute([$status,$id]);});cliniccare_json(['message'=>'Schedule status updated.']);}
    }

    // APPOINTMENTS: every booking/reschedule locks doctor row(s) before validation.
    if(($parts[0]??'')==='appointments'){
        if($method==='GET'){
            if($user['role']==='Doctor'){$doc=cliniccare_require_active_doctor($pdo,$user);$where=' WHERE a.doctor_id=?';$args=[(int)$doc['doctor_id']];}
            elseif(in_array($user['role'],['Admin','Receptionist'],true)){$where=' WHERE 1=1';$args=[];}
            else cliniccare_json(['error'=>'Not authorized.'],403);
            if($id){$where.=' AND a.appointment_id=?';$args[]=$id;}
            if(isset($_GET['date'])&&$_GET['date']!==''){$d=cliniccare_date((string)$_GET['date'],'Date');$where.=' AND a.appointment_date=?';$args[]=$d->format('Y-m-d');}
            if(isset($_GET['status'])&&$_GET['status']!==''){$where.=' AND a.status=?';$args[]=cliniccare_status((string)$_GET['status'],['Scheduled','Completed','Cancelled','No-show']);}
            if(isset($_GET['doctor_id'])&&in_array($user['role'],$admin,true)){$where.=' AND a.doctor_id=?';$args[]=(int)$_GET['doctor_id'];}
            if(isset($_GET['q'])&&trim((string)$_GET['q'])!==''){$where.=' AND (p.full_name LIKE ? OR u.full_name LIKE ?)';$args[]='%'.trim((string)$_GET['q']).'%';$args[]='%'.trim((string)$_GET['q']).'%';}
            $s=$pdo->prepare('SELECT a.appointment_id,a.patient_id,p.full_name AS patient_name,p.birth_date,p.gender,p.contact_number,a.doctor_id,u.full_name AS doctor_name,a.appointment_date,a.appointment_time,a.duration_minutes,a.reason,a.status,a.created_by FROM appointments a JOIN patients p ON p.patient_id=a.patient_id JOIN doctors d ON d.doctor_id=a.doctor_id JOIN users u ON u.user_id=d.user_id'.$where.' ORDER BY a.appointment_id ASC LIMIT 500');$s->execute($args);$rows=$s->fetchAll();if($id&&!$rows)cliniccare_json(['error'=>'Appointment not found.'],404);cliniccare_json(['data'=>$id?$rows[0]:$rows]);
        }
        if($method==='POST'){
            cliniccare_require_auth($adminReception);$patientId=cliniccare_id($input,'patient_id','Patient');$doctorId=cliniccare_id($input,'doctor_id','Doctor');$date=cliniccare_date(cliniccare_required($input,'appointment_date','Appointment date'),'Appointment date',false)->format('Y-m-d');$time=cliniccare_time(cliniccare_required($input,'appointment_time','Appointment time'),'Appointment time');$reason=cliniccare_required($input,'reason','Reason');$duration=filter_var($input['duration_minutes']??30,FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>720]]);if($duration===false)cliniccare_json(['error'=>'Duration must be between 1 and 720 minutes.'],422);cliniccare_length($pdo,'appointments','reason',$reason,'Reason');$start=strtotime($date.' '.$time);$end=$start+$duration*60;if(date('Y-m-d',$start)!==date('Y-m-d',$end))cliniccare_json(['error'=>'An appointment cannot extend beyond midnight.'],422);$hoursError=cliniccare_appointment_operating_hours_error($time,date('H:i:s',$end));if($hoursError!==null)cliniccare_json(['error'=>$hoursError],422);if($start<time())cliniccare_json(['error'=>'Appointment date and time cannot be in the past.'],422);
            $newStartSeconds=((int)substr($time,0,2))*3600+((int)substr($time,3,2))*60+(int)substr($time,6,2);$newEndSeconds=$newStartSeconds+$duration*60;
            $newId=cliniccare_transaction($pdo,function()use($pdo,$doctorId,$patientId,$date,$time,$duration,$reason,$user,$start,$end,$newStartSeconds,$newEndSeconds){$d=$pdo->prepare('SELECT d.doctor_id,d.status,u.status AS user_status FROM doctors d JOIN users u ON u.user_id=d.user_id WHERE d.doctor_id=? FOR UPDATE');$d->execute([$doctorId]);$doc=$d->fetch();if(!$doc||strcasecmp((string)$doc['status'],'Active')!==0||strcasecmp((string)$doc['user_status'],'Active')!==0)cliniccare_json(['error'=>'The selected doctor or their linked account is inactive. Choose an active doctor or ask an Admin to reactivate the account.'],422);$p=$pdo->prepare('SELECT status FROM patients WHERE patient_id=? FOR UPDATE');$p->execute([$patientId]);if(strcasecmp((string)$p->fetchColumn(),'Active')!==0)cliniccare_json(['error'=>'The selected patient is inactive or unavailable. Choose an active patient.'],422);$day=(int)(new DateTimeImmutable($date))->format('N');$endTime=date('H:i:s',$end);$s=$pdo->prepare("SELECT start_time,end_time FROM doctor_schedules WHERE doctor_id=? AND available_day=? AND status='Active' ORDER BY start_time,end_time");$s->execute([$doctorId,$day]);$scheduleError=cliniccare_appointment_schedule_error($s->fetchAll(),$date,$time,$endTime);if($scheduleError!==null)cliniccare_json(['error'=>$scheduleError],422);$s=$pdo->prepare("SELECT appointment_id FROM appointments WHERE doctor_id=? AND appointment_date=? AND status IN ('Scheduled','Completed','No-show') AND TIME_TO_SEC(appointment_time) < ? AND TIME_TO_SEC(appointment_time)+duration_minutes*60 > ? LIMIT 1");$s->execute([$doctorId,$date,$newEndSeconds,$newStartSeconds]);if($s->fetch())cliniccare_json(['error'=>'This time conflicts with another appointment for the selected doctor. Choose a different time.'],409);$s=$pdo->prepare("INSERT INTO appointments(patient_id,doctor_id,appointment_date,appointment_time,duration_minutes,reason,status,created_by) VALUES(?,?,?,?,?,?,'Scheduled',?)");$s->execute([$patientId,$doctorId,$date,$time,$duration,$reason,cliniccare_audit_actor($user)]);return (int)$pdo->lastInsertId();});cliniccare_json(['message'=>'Appointment scheduled.','appointment_id'=>$newId],201);
        }
        if($id&&$method==='PUT'){
            cliniccare_require_auth($adminReception);$initial=$pdo->prepare('SELECT doctor_id FROM appointments WHERE appointment_id=?');$initial->execute([$id]);$old=$initial->fetch();if(!$old)cliniccare_json(['error'=>'Appointment not found.'],404);$patientId=cliniccare_id($input,'patient_id','Patient');$doctorId=cliniccare_id($input,'doctor_id','Doctor');$date=cliniccare_date(cliniccare_required($input,'appointment_date','Appointment date'),'Appointment date',false)->format('Y-m-d');$time=cliniccare_time(cliniccare_required($input,'appointment_time','Appointment time'),'Appointment time');$reason=cliniccare_required($input,'reason','Reason');$duration=filter_var($input['duration_minutes']??30,FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>720]]);if($duration===false)cliniccare_json(['error'=>'Duration must be between 1 and 720 minutes.'],422);cliniccare_length($pdo,'appointments','reason',$reason,'Reason');$start=strtotime($date.' '.$time);$end=$start+$duration*60;if(date('Y-m-d',$end)!==$date)cliniccare_json(['error'=>'An appointment cannot extend beyond midnight.'],422);$hoursError=cliniccare_appointment_operating_hours_error($time,date('H:i:s',$end));if($hoursError!==null)cliniccare_json(['error'=>$hoursError],422);if($start<time())cliniccare_json(['error'=>'Appointment date and time cannot be in the past.'],422);$oldDoctor=(int)$old['doctor_id'];
            $newStartSeconds=((int)substr($time,0,2))*3600+((int)substr($time,3,2))*60+(int)substr($time,6,2);$newEndSeconds=$newStartSeconds+$duration*60;
            cliniccare_transaction($pdo,function()use($pdo,$oldDoctor,$doctorId,$patientId,$date,$time,$duration,$reason,$user,$id,$start,$end,$newStartSeconds,$newEndSeconds){$ids=array_values(array_unique([$oldDoctor,$doctorId]));sort($ids,SORT_NUMERIC);$locked=[];foreach($ids as $x){$s=$pdo->prepare('SELECT d.doctor_id,d.status,u.status AS user_status FROM doctors d JOIN users u ON u.user_id=d.user_id WHERE d.doctor_id=? FOR UPDATE');$s->execute([$x]);$locked[$x]=$s->fetch();} $doc=$locked[$doctorId]??null;if(!$doc||strcasecmp((string)$doc['status'],'Active')!==0||strcasecmp((string)$doc['user_status'],'Active')!==0)cliniccare_json(['error'=>'The selected doctor or their linked account is inactive. Choose an active doctor or ask an Admin to reactivate the account.'],422);$a=$pdo->prepare('SELECT appointment_id,doctor_id,status FROM appointments WHERE appointment_id=? FOR UPDATE');$a->execute([$id]);$appointment=$a->fetch();if(!$appointment)cliniccare_json(['error'=>'Appointment not found.'],404);if((int)$appointment['doctor_id']!==$oldDoctor)cliniccare_json(['error'=>'Appointment changed during rescheduling. Reload and try again.'],409);if($appointment['status']!=='Scheduled')cliniccare_json(['error'=>'Only scheduled appointments can be rescheduled.'],409);$p=$pdo->prepare('SELECT status FROM patients WHERE patient_id=? FOR UPDATE');$p->execute([$patientId]);if(strcasecmp((string)$p->fetchColumn(),'Active')!==0)cliniccare_json(['error'=>'The selected patient is inactive or unavailable. Choose an active patient.'],422);$day=(int)(new DateTimeImmutable($date))->format('N');$s=$pdo->prepare("SELECT start_time,end_time FROM doctor_schedules WHERE doctor_id=? AND available_day=? AND status='Active' ORDER BY start_time,end_time");$s->execute([$doctorId,$day]);$scheduleError=cliniccare_appointment_schedule_error($s->fetchAll(),$date,$time,date('H:i:s',$end));if($scheduleError!==null)cliniccare_json(['error'=>$scheduleError],422);$s=$pdo->prepare("SELECT appointment_id FROM appointments WHERE doctor_id=? AND appointment_date=? AND appointment_id<>? AND status IN ('Scheduled','Completed','No-show') AND TIME_TO_SEC(appointment_time) < ? AND TIME_TO_SEC(appointment_time)+duration_minutes*60 > ? LIMIT 1");$s->execute([$doctorId,$date,$id,$newEndSeconds,$newStartSeconds]);if($s->fetch())cliniccare_json(['error'=>'This time conflicts with another appointment for the selected doctor. Choose a different time.'],409);$s=$pdo->prepare("UPDATE appointments SET patient_id=?,doctor_id=?,appointment_date=?,appointment_time=?,duration_minutes=?,reason=?,updated_at=CURRENT_TIMESTAMP WHERE appointment_id=?");$s->execute([$patientId,$doctorId,$date,$time,$duration,$reason,$id]);});cliniccare_json(['message'=>'Appointment rescheduled.']);
        }
        if($id&&$method==='PATCH'){
            $status=cliniccare_status((string)($input['status']??''),['Cancelled','No-show']);
            $old=$pdo->prepare('SELECT appointment_id,doctor_id,status FROM appointments WHERE appointment_id=?');
            $old->execute([$id]);$appointment=$old->fetch();
            if(!$appointment)cliniccare_json(['error'=>'Appointment not found.'],404);
            if($user['role']==='Doctor'){
                $doctor=cliniccare_require_active_doctor($pdo,$user);
                if((int)$doctor['doctor_id']!==(int)$appointment['doctor_id'])cliniccare_json(['error'=>'You can only update your own appointments.'],403);
                if($status!=='No-show')cliniccare_json(['error'=>'Doctors may mark their assigned appointments No-show.'],403);
            }elseif(!in_array($user['role'],$adminReception,true))cliniccare_json(['error'=>'Not authorized.'],403);
            if($appointment['status']!=='Scheduled')cliniccare_json(['error'=>'Only scheduled appointments can be cancelled or marked No-show.'],409);
            $s=$pdo->prepare('UPDATE appointments SET status=?,updated_at=CURRENT_TIMESTAMP WHERE appointment_id=?');
            $s->execute([$status,$id]);
            cliniccare_json(['message'=>'Appointment status updated.']);
        }
    }

    // MEDICAL RECORDS: doctors may create one record for their own completed consults.
    if(($parts[0]??'')==='medical-records'){
        if($method==='GET'){
            if(!in_array($user['role'],['Admin','Doctor'],true))cliniccare_json(['error'=>'Medical records are restricted to administrators and doctors.'],403);
            if($user['role']==='Admin'){$sql='SELECT m.record_id,m.appointment_id,m.diagnosis,m.treatment,m.notes,m.consultation_date,m.created_by,a.patient_id,p.full_name AS patient_name,a.doctor_id FROM medical_records m JOIN appointments a ON a.appointment_id=m.appointment_id JOIN patients p ON p.patient_id=a.patient_id';$args=[];if($id){$sql.=' WHERE m.record_id=?';$args[]=$id;}}
            else{$doc=cliniccare_require_active_doctor($pdo,$user);$sql='SELECT m.record_id,m.appointment_id,m.diagnosis,m.treatment,m.notes,m.consultation_date,m.created_by,a.patient_id,p.full_name AS patient_name,a.doctor_id FROM medical_records m JOIN appointments a ON a.appointment_id=m.appointment_id JOIN patients p ON p.patient_id=a.patient_id WHERE (a.doctor_id=? OR EXISTS(SELECT 1 FROM appointments ax WHERE ax.patient_id=a.patient_id AND ax.doctor_id=? AND ax.status IN (\'Scheduled\',\'Completed\')))';$args=[(int)$doc['doctor_id'],(int)$doc['doctor_id']];if($id){$sql.=' AND m.record_id=?';$args[]=$id;}}
            if(isset($_GET['patient_id'])){$patientId=cliniccare_id($_GET,'patient_id','Patient');$sql.=$user['role']==='Admin'&&!$id?' WHERE a.patient_id=?':' AND a.patient_id=?';$args[]=$patientId;}
            $s=$pdo->prepare($sql.($id?'':' ORDER BY m.consultation_date DESC LIMIT 250'));$s->execute($args);$rows=$s->fetchAll();if($id&&!$rows)cliniccare_json(['error'=>'Medical record not found or not authorized.'],404);cliniccare_json(['data'=>$id?$rows[0]:$rows]);
        }
        if($method==='POST'){
            $doctor=cliniccare_require_active_doctor($pdo,$user);$appointmentId=cliniccare_id($input,'appointment_id','Appointment');$diagnosis=cliniccare_required($input,'diagnosis','Diagnosis');$treatment=cliniccare_required($input,'treatment','Treatment');$notes=trim((string)($input['notes']??''));$date=cliniccare_not_future_date(cliniccare_required($input,'consultation_date','Consultation date'),'Consultation date')->format('Y-m-d');foreach([['diagnosis',$diagnosis,'Diagnosis'],['treatment',$treatment,'Treatment'],['notes',$notes,'Notes']] as [$col,$val,$label])cliniccare_length($pdo,'medical_records',$col,$val,$label,$col!=='notes');
            cliniccare_transaction($pdo,function()use($pdo,$doctor,$appointmentId,$diagnosis,$treatment,$notes,$date,$user){$active=$pdo->prepare("SELECT d.doctor_id FROM doctors d JOIN users u ON u.user_id=d.user_id WHERE d.doctor_id=? AND d.user_id=? AND d.status='Active' AND u.status='Active' FOR UPDATE");$active->execute([(int)$doctor['doctor_id'],(int)$user['user_id']]);if(!$active->fetch())cliniccare_json(['error'=>'An active doctor profile and account are required.'],403);$s=$pdo->prepare('SELECT appointment_id,status,appointment_date,appointment_time FROM appointments WHERE appointment_id=? AND doctor_id=? FOR UPDATE');$s->execute([$appointmentId,(int)$doctor['doctor_id']]);$a=$s->fetch();if(!$a)cliniccare_json(['error'=>'Appointment is not assigned to your doctor profile.'],403);if((string)$date<(string)$a['appointment_date']||new DateTimeImmutable((string)$a['appointment_date'].' '.(string)$a['appointment_time'])>new DateTimeImmutable())cliniccare_json(['error'=>'Consultation date cannot precede the appointment or be in the future.'],422);if(!in_array($a['status'],['Scheduled','Completed'],true))cliniccare_json(['error'=>'A record can only be created for an active consultation.'],409);$exists=$pdo->prepare('SELECT record_id FROM medical_records WHERE appointment_id=?');$exists->execute([$appointmentId]);if($exists->fetch())cliniccare_json(['error'=>'A medical record already exists for this appointment.'],409);$s=$pdo->prepare("INSERT INTO medical_records(appointment_id,diagnosis,treatment,notes,consultation_date,created_by) VALUES(?,?,?,?,?,?)");$s->execute([$appointmentId,$diagnosis,$treatment,$notes===''?null:$notes,$date,cliniccare_audit_actor($user)]);$s=$pdo->prepare("UPDATE appointments SET status='Completed',updated_at=CURRENT_TIMESTAMP WHERE appointment_id=?");$s->execute([$appointmentId]);});cliniccare_json(['message'=>'Consultation recorded and appointment marked Completed.'],201);
        }
    }

    // Role-specific dashboard summaries; appointment details remain available through /appointments.
    if (($parts[0] ?? '') === 'dashboard' && $method === 'GET') {
        if ($user['role'] === 'Admin') {
            $summary = [
                'patients_total' => (int)$pdo->query('SELECT COUNT(*) FROM patients')->fetchColumn(),
                'patients_active' => (int)$pdo->query("SELECT COUNT(*) FROM patients WHERE status='Active'")->fetchColumn(),
                'doctors_total' => (int)$pdo->query('SELECT COUNT(*) FROM doctors')->fetchColumn(),
                'doctors_active' => (int)$pdo->query("SELECT COUNT(*) FROM doctors d JOIN users u ON u.user_id=d.user_id WHERE d.status='Active' AND u.status='Active'")->fetchColumn(),
                'appointments_today' => (int)$pdo->query('SELECT COUNT(*) FROM appointments WHERE appointment_date=CURRENT_DATE')->fetchColumn(),
                'appointments_upcoming' => (int)$pdo->query("SELECT COUNT(*) FROM appointments WHERE appointment_date>=CURRENT_DATE AND status='Scheduled'")->fetchColumn(),
            ];
            cliniccare_json(['data' => $summary]);
        }
        if ($user['role'] === 'Receptionist') {
            $s = $pdo->prepare('SELECT status,COUNT(*) AS total FROM appointments WHERE appointment_date=CURRENT_DATE GROUP BY status ORDER BY status');
            $s->execute();
            $byStatus = $s->fetchAll();
            $summary = [
                'patients_total' => (int)$pdo->query('SELECT COUNT(*) FROM patients')->fetchColumn(),
                'doctors_active' => (int)$pdo->query("SELECT COUNT(*) FROM doctors d JOIN users u ON u.user_id=d.user_id WHERE d.status='Active' AND u.status='Active'")->fetchColumn(),
                'appointments_today' => array_sum(array_map(static fn($r) => (int)$r['total'], $byStatus)),
                'appointments_upcoming' => (int)$pdo->query("SELECT COUNT(*) FROM appointments WHERE appointment_date>=CURRENT_DATE AND status='Scheduled'")->fetchColumn(),
                'by_status' => $byStatus,
            ];
            cliniccare_json(['data' => $summary]);
        }
        if ($user['role'] === 'Doctor') {
            $doctor = cliniccare_require_active_doctor($pdo, $user);
            $s = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id=? AND appointment_date=CURRENT_DATE AND status='Scheduled'");
            $s->execute([(int)$doctor['doctor_id']]);
            $today = (int)$s->fetchColumn();
            $s = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id=? AND appointment_date>=CURRENT_DATE AND status='Scheduled'");
            $s->execute([(int)$doctor['doctor_id']]);
            cliniccare_json(['data' => ['appointments_today' => $today, 'appointments_upcoming' => (int)$s->fetchColumn()]]);
        }
        cliniccare_json(['error' => 'Not authorized.'], 403);
    }

    // Role-limited appointment aggregates; this response contains no patient-level or clinical data.
    if(($parts[0]??'')==='reports'){
        cliniccare_require_auth(['Admin','Receptionist']);
        $from=isset($_GET['from'])?cliniccare_date((string)$_GET['from'],'Start date')->format('Y-m-d'):date('Y-m-d');
        $to=isset($_GET['to'])?cliniccare_date((string)$_GET['to'],'End date')->format('Y-m-d'):date('Y-m-d');
        if($to<$from)cliniccare_json(['error'=>'End date must be on or after start date.'],422);
        $granularity=(string)($_GET['granularity']??'daily');
        if(!in_array($granularity,['daily','weekly','monthly'],true))cliniccare_json(['error'=>'Choose a daily, weekly, or monthly trend view.'],422);
        $s=$pdo->prepare("SELECT status,COUNT(*) AS total FROM appointments WHERE appointment_date BETWEEN ? AND ? GROUP BY status ORDER BY status");
        $s->execute([$from,$to]);$counts=$s->fetchAll();
        $summary=['total'=>0,'pending'=>0,'completed'=>0];
        foreach($counts as $row){$n=(int)$row['total'];$summary['total']+=$n;if($row['status']==='Scheduled')$summary['pending']=$n;if($row['status']==='Completed')$summary['completed']=$n;}
        $trendSql=match($granularity){
            'weekly'=>"SELECT DATE_FORMAT(DATE_SUB(appointment_date, INTERVAL WEEKDAY(appointment_date) DAY),'%Y-%m-%d') AS label,COUNT(*) AS total FROM appointments WHERE appointment_date BETWEEN ? AND ? GROUP BY YEARWEEK(appointment_date,3),DATE_SUB(appointment_date, INTERVAL WEEKDAY(appointment_date) DAY) ORDER BY DATE_SUB(appointment_date, INTERVAL WEEKDAY(appointment_date) DAY)",
            'monthly'=>"SELECT DATE_FORMAT(appointment_date,'%Y-%m') AS label,COUNT(*) AS total FROM appointments WHERE appointment_date BETWEEN ? AND ? GROUP BY YEAR(appointment_date),MONTH(appointment_date) ORDER BY YEAR(appointment_date),MONTH(appointment_date)",
            default=>"SELECT DATE_FORMAT(appointment_date,'%Y-%m-%d') AS label,COUNT(*) AS total FROM appointments WHERE appointment_date BETWEEN ? AND ? GROUP BY appointment_date ORDER BY appointment_date"
        };
        $s=$pdo->prepare($trendSql);$s->execute([$from,$to]);$trend=$s->fetchAll();
        $doctorSql="SELECT u.full_name AS doctor_name,sp.specialization_name AS specialization,COUNT(a.appointment_id) AS total,SUM(CASE WHEN a.status='Scheduled' THEN 1 ELSE 0 END) AS pending,SUM(CASE WHEN a.status='Completed' THEN 1 ELSE 0 END) AS completed FROM doctors d JOIN users u ON u.user_id=d.user_id JOIN specializations sp ON sp.specialization_id=d.specialization_id LEFT JOIN appointments a ON a.doctor_id=d.doctor_id AND a.appointment_date BETWEEN ? AND ? GROUP BY d.doctor_id,u.full_name,sp.specialization_name ORDER BY u.full_name";
        $s=$pdo->prepare($doctorSql);$s->execute([$from,$to]);$byDoctor=$s->fetchAll();
        cliniccare_json(['from'=>$from,'to'=>$to,'granularity'=>$granularity,'by_status'=>$counts,'by_doctor'=>$byDoctor,'trend'=>$trend,'patients_total'=>(int)$pdo->query('SELECT COUNT(*) FROM patients')->fetchColumn(),'summary'=>$summary]);
    }

    cliniccare_json(['error'=>'Endpoint not found.'],404);
} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        error_log('[ClinicCare] ' . $e->getMessage());
        cliniccare_json(['error' => 'A duplicate value or related record prevents this change.'], 409);
    }
    cliniccare_error_handler($e);
} catch (Throwable $e) { cliniccare_error_handler($e); }
