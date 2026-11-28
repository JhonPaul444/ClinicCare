(() => {
    'use strict';

    const page = document.querySelector('[data-clinic-page]');
    const passwordToggle = document.querySelector('[data-password-toggle]');
    passwordToggle?.addEventListener('click',(event)=>{
        const button=event.currentTarget; const input=document.getElementById(button.getAttribute('aria-controls'));
        if(!input)return; const visible=input.type==='password'; input.type=visible?'text':'password'; button.setAttribute('aria-pressed',String(visible)); button.setAttribute('aria-label',visible?'Hide password':'Show password');
    });
    const profileDropdown=document.querySelector('[data-profile-dropdown]');
    const profileToggle=profileDropdown?.querySelector('[data-profile-toggle]');
    const profileMenu=profileDropdown?.querySelector('[data-profile-menu]');
    if(profileDropdown&&profileToggle&&profileMenu){
        const menuItems=()=>[...profileMenu.querySelectorAll('a[href],button:not([disabled]),[tabindex]:not([tabindex="-1"])')];
        const isOpen=()=>!profileMenu.hidden;
        const setProfileMenuOpen=(open,restoreFocus=false)=>{
            profileMenu.hidden=!open;
            profileMenu.classList.toggle('show',open);
            profileToggle.setAttribute('aria-expanded',String(open));
            if(!open&&restoreFocus)profileToggle.focus();
        };
        profileToggle.addEventListener('click',()=>setProfileMenuOpen(!isOpen()));
        profileToggle.addEventListener('keydown',(event)=>{
            if(!['ArrowDown','ArrowUp'].includes(event.key))return;
            event.preventDefault();
            setProfileMenuOpen(true);
            const items=menuItems();
            (event.key==='ArrowUp'?items[items.length-1]:items[0])?.focus();
        });
        profileMenu.addEventListener('keydown',(event)=>{
            if(event.key==='Escape'){
                event.preventDefault();
                setProfileMenuOpen(false,true);
                return;
            }
            const items=menuItems();
            const current=items.indexOf(document.activeElement);
            if(!items.length||current<0)return;
            let next=current;
            if(event.key==='ArrowDown')next=(current+1)%items.length;
            else if(event.key==='ArrowUp')next=(current-1+items.length)%items.length;
            else if(event.key==='Home')next=0;
            else if(event.key==='End')next=items.length-1;
            else return;
            event.preventDefault();
            items[next].focus();
        });
        document.addEventListener('pointerdown',(event)=>{
            if(isOpen()&&!profileDropdown.contains(event.target))setProfileMenuOpen(false);
        });
        document.addEventListener('focusin',(event)=>{
            if(isOpen()&&!profileDropdown.contains(event.target))setProfileMenuOpen(false);
        });
        document.addEventListener('keydown',(event)=>{
            if(event.key==='Escape'&&isOpen())setProfileMenuOpen(false,true);
        });
    }
    if (!page) return;

    const pageType = page.dataset.clinicPage;
    const role = page.dataset.dashboardRole || page.dataset.appRole || '';
    const apiBase = page.dataset.apiBase || '../..';
    const apiUrl = (resource, params = {}) => {
        const url = new URL(`${apiBase}/api/index.php`, window.location.href);
        url.searchParams.set('resource', resource);
        Object.entries(params).forEach(([key, value]) => {
            if (value !== '' && value !== null && value !== undefined) url.searchParams.set(key, String(value));
        });
        return url;
    };

    let csrfToken = '';
    let records = [];
    let editingRecord = null;
    let formEntityType = pageType;
    let searchTimer = null;
    const localToday = () => {
        const value = new Date();
        return `${value.getFullYear()}-${String(value.getMonth()+1).padStart(2,'0')}-${String(value.getDate()).padStart(2,'0')}`;
    };

    const feedback = page.querySelector('[data-feedback]');
    const form = page.querySelector('[data-record-form]');
    const countLabel = page.querySelector('[data-record-count]');
    const tbody = page.querySelector('[data-table-body]');
    const thead = page.querySelector('[data-table-head]');
    const searchInput = page.querySelector('[data-record-search]');
    const globalSearch = document.querySelector('[data-global-search]');
    const statusFilter = page.querySelector('[data-record-filter]');
    const dateFilter = page.querySelector('[data-date-filter]');
    let appointmentQuickFilter=pageType==='appointments'?new URLSearchParams(location.search).get('filter'):'';
    const appointmentFilterNotice=page.querySelector('[data-appointment-filter-notice]');
    const clearAppointmentQuickFilter=()=>{
        if(!appointmentQuickFilter)return;
        appointmentQuickFilter='';
        const url=new URL(location.href);url.searchParams.delete('filter');
        history.replaceState(null,'',`${url.pathname}${url.search}${url.hash}`);
        appointmentFilterNotice?.classList.add('d-none');
    };
    if(pageType==='appointments'&&appointmentQuickFilter==='today'){
        if(dateFilter)dateFilter.value=localToday();
        if(appointmentFilterNotice){
            appointmentFilterNotice.replaceChildren(document.createTextNode('Showing appointments dated today. '));
            const clear=document.createElement('a');clear.href=location.pathname;clear.textContent='View all appointments';appointmentFilterNotice.append(clear);appointmentFilterNotice.classList.remove('d-none');
        }
    }else if(pageType==='appointments'&&appointmentQuickFilter==='upcoming'){
        if(statusFilter)statusFilter.value='Scheduled';
        if(appointmentFilterNotice){
            appointmentFilterNotice.replaceChildren(document.createTextNode('Showing scheduled appointments dated today or later. '));
            const clear=document.createElement('a');clear.href=location.pathname;clear.textContent='View all appointments';appointmentFilterNotice.append(clear);appointmentFilterNotice.classList.remove('d-none');
        }
    }

    const configs = {
        patients: {
            resource: 'patients', title: 'patient', count: 'patients',
            columns: [['full_name', 'Patient'], ['patient_id', 'ID'], ['birth_date', 'Birth date'], ['gender', 'Gender'], ['contact_number', 'Contact'], ['address', 'Address'], ['status', 'Status']],
            fields: [
                {name:'full_name',label:'Full name',required:true,max:120},
                {name:'birth_date',label:'Birth date',type:'date',required:true},
                {name:'gender',label:'Gender',type:'select',placeholder:'Select Gender',options:['Male','Female','Prefer not to say'],required:true,max:30,help:'Choose the option provided by the patient.'},
                {name:'contact_number',label:'Contact number',required:true,max:30},
                {name:'address',label:'Address',required:true,max:255},
            ],
        },
        appointments: {
            resource: 'appointments', title: 'appointment', count: 'appointments',
            columns: [['appointment_id','ID'],['patient_name','Patient'],['doctor_name','Doctor'],['appointment_date','Date'],['appointment_time','Time'],['duration_minutes','Minutes'],['reason','Reason'],['status','Status']],
            fields: [
                {name:'patient_id',label:'Patient',type:'select',source:'patients',value:'patient_id',text:'full_name',required:true},
                {name:'doctor_id',label:'Doctor',type:'select',source:'doctors',value:'doctor_id',text:'full_name',required:true},
                {name:'appointment_date',label:'Date',type:'date',required:true},
                {name:'appointment_time',label:'Time',type:'time',required:true},
                {name:'duration_minutes',label:'Duration (minutes)',type:'number',min:1,max:720,default:'30',required:true},
                {name:'reason',label:'Reason',type:'textarea',max:500,required:true},
            ],
        },
        users: {
            resource: 'users', title: 'user', count: 'users',
            columns: [['full_name','Name'],['username','Username'],['role','Role'],['status','Status']],
            fields: [
                {name:'full_name',label:'Full name',required:true,max:120},
                {name:'username',label:'Username',required:true,max:50,createOnly:true},
                {name:'role',label:'Role',type:'select',options:['Admin','Receptionist','Doctor'],required:true},
                {name:'status',label:'Status',type:'select',options:['Active','Inactive'],editOnly:true},
                {name:'password',label:'Password',type:'password',required:true,createOnly:true,min:12,help:'Use at least 12 characters. On edit, leave blank to keep the current password.'},
                {name:'password',label:'New password (optional)',type:'password',editOnly:true,min:12},
            ],
        },
        doctors: {
            resource: 'doctors', title: 'doctor profile', count: 'doctor profiles',
            columns: [['full_name','Doctor'],['username','Username'],['specialization_name','Specialization'],['license_number','License'],['status','Profile status'],['account_status','Account status']],
            fields: [
                {name:'user_id',label:'Doctor account',type:'select',source:'users',value:'user_id',text:'full_name',filterRole:'Doctor',required:true,createOnly:true},
                {name:'specialization_id',label:'Specialization',type:'select',source:'specializations',value:'specialization_id',text:'specialization_name',required:true},
                {name:'license_number',label:'License number',max:50},
            ],
        },
        specializations: {
            resource: 'specializations', title: 'specialization', count: 'specializations',
            columns: [['specialization_name','Specialization'],['description','Description'],['status','Status']],
            fields: [
                {name:'specialization_name',label:'Name',required:true,max:100},
                {name:'description',label:'Description',type:'textarea',max:255},
                {name:'status',label:'Status',type:'select',options:['Active','Inactive'],editOnly:true},
            ],
        },
        schedules: {
            resource: 'schedules', title: 'weekly availability', count: 'doctors',
            columns: [['doctor_name','Doctor'],['weekly_availability','Weekly availability']],
            fields: [],
        },
        'medical-records': {
            resource: 'medical-records', title: 'medical record', count: 'medical records',
            columns: [['consultation_date','Date'],['patient_name','Patient'],['diagnosis','Diagnosis'],['treatment','Treatment'],['notes','Notes']],
            fields: [
                {name:'appointment_id',label:'Appointment',type:'select',source:'appointments',value:'appointment_id',text:'appointment_label',required:true},
                {name:'diagnosis',label:'Diagnosis',type:'textarea',required:true},
                {name:'treatment',label:'Treatment',type:'textarea',required:true},
                {name:'notes',label:'Notes',type:'textarea'},
                {name:'consultation_date',label:'Consultation date',type:'date',required:true},
            ],
        },
    };

    const showFeedback = (message, kind = 'success') => {
        if (!feedback) return;
        feedback.textContent = message;
        feedback.className = `alert alert-${kind === 'error' ? 'danger' : kind === 'warning' ? 'warning' : 'success'}`;
        feedback.classList.remove('d-none');
    };

    const request = async (resource, options = {}) => {
        const method = (options.method || 'GET').toUpperCase();
        const headers = {Accept:'application/json'};
        let body;
        if (options.body !== undefined) {
            headers['Content-Type'] = 'application/json';
            body = JSON.stringify(options.body);
        }
        if (!['GET','HEAD'].includes(method)) {
            if (!csrfToken) {
                const tokenResponse = await fetch(apiUrl('auth/csrf'), {credentials:'same-origin',headers:{Accept:'application/json'}});
                const tokenPayload = await tokenResponse.json();
                if (!tokenResponse.ok || !tokenPayload.csrf_token) throw new Error(tokenPayload.error || 'Could not start a secure session.');
                csrfToken = tokenPayload.csrf_token;
            }
            headers['X-CSRF-Token'] = csrfToken;
        }
        const response = await fetch(apiUrl(resource, options.params || {}), {method,headers,body,credentials:'same-origin',cache:'no-store'});
        const payload = await response.json().catch(() => ({}));
        if (response.status === 401) {
            window.location.assign(`${apiBase}/login.php`);
            throw new Error('Your session expired. Please sign in again.');
        }
        if (!response.ok) throw new Error(payload.error || `Request failed (${response.status}).`);
        if (payload.csrf_token) csrfToken = payload.csrf_token;
        return payload;
    };

    const asArray = (payload) => Array.isArray(payload?.data) ? payload.data : [];
    const formatAppointmentTime = (value) => {
        const [hour,minute]=String(value||'').split(':').map(Number);
        if(!Number.isInteger(hour)||!Number.isInteger(minute))return String(value||'');
        return new Date(2000,0,1,hour,minute).toLocaleTimeString(undefined,{hour:'numeric',minute:'2-digit'});
    };
    const labelForAppointment = (row) => `#${row.appointment_id} - ${row.patient_name || 'Patient'} / ${row.appointment_date} ${formatAppointmentTime(row.appointment_time)} (${row.status})`;
    const loadOptions = async (field) => {
        if (!field.source) return (field.options || []).map((item) => Array.isArray(item) ? item : [item, item]);
        const payload = await request(field.source);
        let rows = asArray(payload);
        if (field.source === 'users' && field.filterRole) rows = rows.filter((row) => row.role === field.filterRole && row.status === 'Active');
        if (field.source === 'doctors') rows = rows.filter((row) => row.status === 'Active' && row.account_status === 'Active');
        if (field.source === 'patients') rows = rows.filter((row) => row.status === 'Active');
        if (field.source === 'specializations') rows = rows.filter((row) => row.status === 'Active');
        if (field.source === 'appointments') {
            rows = rows.filter((row) => ['Scheduled','Completed'].includes(row.status));
            if (formEntityType === 'medical-records') {
                const existing = new Set(asArray(await request('medical-records')).map((record) => Number(record.appointment_id)));
                rows = rows.filter((row) => !existing.has(Number(row.appointment_id)));
            }
        }
        return rows.map((row) => {
            if (field.source === 'appointments') return [row[field.value], labelForAppointment(row)];
            return [row[field.value], row[field.text]];
        });
    };

    const actionButton = (label, action, id, kind = 'outline-secondary') => {
        const button = document.createElement('button');
        button.type = 'button'; button.className = `btn btn-sm btn-${kind} me-1 mb-1`;
        button.dataset.rowAction = action; button.dataset.recordId = String(id); button.textContent = label;
        return button;
    };

    const statusPill = (value) => {
        const span = document.createElement('span');
        span.className = `status-pill status-${String(value || '').toLowerCase().replace(/[^a-z0-9]+/g,'-')}`;
        span.textContent = String(value ?? '');
        return span;
    };

    const rowActions = (row) => {
        const cell = document.createElement('td');
        cell.className = 'record-actions';
        const idKey = ({patients:'patient_id',appointments:'appointment_id',users:'user_id',doctors:'doctor_id',specializations:'specialization_id',schedules:'schedule_id','medical-records':'record_id'})[pageType];
        const id = row[idKey];
        if (['patients','appointments','medical-records'].includes(pageType)) cell.append(actionButton('Details','details',id));
        if (['patients','appointments','users','doctors','specializations','schedules'].includes(pageType) && !(pageType === 'appointments' && role === 'Doctor')) cell.append(actionButton('Edit','edit',id));
        if (pageType === 'appointments' && role === 'Doctor') {
            if (row.status === 'Scheduled') cell.append(actionButton('No-show','no-show',id,'outline-danger'));
            const appointmentStart = new Date(`${row.appointment_date}T${String(row.appointment_time || '00:00:00').slice(0,8)}`);
            if (['Scheduled','Completed'].includes(row.status) && !row.has_medical_record && appointmentStart <= new Date()) cell.append(actionButton('Record consult','record',id,'primary'));
        } else if (pageType === 'appointments' && ['Admin','Receptionist'].includes(role)) {
            if (row.status === 'Scheduled') {
                cell.append(actionButton('Cancel','cancel',id,'outline-danger'));
                cell.append(actionButton('No-show','no-show',id));
            }
        } else if (['patients','doctors','schedules'].includes(pageType)) {
            if (row.status === 'Active') cell.append(actionButton('Deactivate','deactivate',id,'outline-danger'));
            else cell.append(actionButton('Activate','activate',id,'outline-success'));
        }
        return cell;
    };

    const dayName = (value) => ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'][Number(value) - 1] || value;
    const renderScheduleGroups = (doctors) => {
        if(!thead||!tbody)return;
        thead.replaceChildren();
        ['Doctor','Weekly availability','Actions'].forEach((label)=>{const th=document.createElement('th');th.scope='col';th.textContent=label;thead.append(th);});
        tbody.replaceChildren();
        if(!doctors.length){const tr=document.createElement('tr');tr.className='empty-table-row';const td=document.createElement('td');td.colSpan=3;td.textContent='No doctor profiles found.';tr.append(td);tbody.append(tr);return;}
        doctors.forEach((doctor)=>{
            const tr=document.createElement('tr');tr.className='weekly-doctor-row';tr.dataset.recordRow='';
            const searchable=[doctor.full_name, ...(doctor.schedules||[]).flatMap((item)=>[dayName(item.available_day),item.start_time,item.end_time,item.status])].join(' ').toLocaleLowerCase();
            tr.dataset.searchText=searchable;
            const doctorCell=document.createElement('td');doctorCell.className='weekly-doctor-cell';
            const doctorName=document.createElement('strong');doctorName.className='weekly-doctor-name';doctorName.textContent=doctor.full_name||`Doctor ${doctor.doctor_id}`;doctorCell.append(doctorName);
            const doctorState=document.createElement('small');doctorState.textContent=doctor.status!=='Active'||doctor.account_status!=='Active'?'Doctor account inactive':'';doctorCell.append(doctorState);
            const scheduleCell=document.createElement('td');scheduleCell.className='weekly-availability-cell';
            const activeSchedules=(doctor.schedules||[]).filter((item)=>item.status==='Active');
            const weeklyState=document.createElement('div');weeklyState.className='weekly-overall-state';
            weeklyState.append(statusPill(doctor.status!=='Active'||doctor.account_status!=='Active'?'Doctor inactive':activeSchedules.length?'Schedule active':'No active days'));
            scheduleCell.append(weeklyState);
            const week=document.createElement('div');week.className='weekly-availability-list';
            for(let day=1;day<=7;day++){
                const dayRow=document.createElement('div');dayRow.className='weekly-availability-day';
                const dayLabel=document.createElement('strong');dayLabel.textContent=dayName(day);dayRow.append(dayLabel);
                const ranges=document.createElement('div');ranges.className='weekly-availability-ranges';
                const daySchedules=(doctor.schedules||[]).filter((item)=>Number(item.available_day)===day).sort((a,b)=>String(a.start_time).localeCompare(String(b.start_time)));
                if(!daySchedules.length){const unavailable=document.createElement('span');unavailable.className='weekly-unavailable';unavailable.textContent='Unavailable';ranges.append(unavailable);}
                daySchedules.forEach((schedule)=>{
                    const range=document.createElement('div');range.className=`weekly-range ${schedule.status==='Active'?'is-active':'is-inactive'}`;
                    const hours=document.createElement('span');hours.className='weekly-range-hours';hours.textContent=`${formatAppointmentTime(schedule.start_time)}–${formatAppointmentTime(schedule.end_time)}`;
                    const state=document.createElement('span');state.className='weekly-range-state';state.textContent=schedule.status==='Active'?'Active':'Inactive';
                    const toggle=actionButton(schedule.status==='Active'?'Deactivate':'Activate',schedule.status==='Active'?'deactivate':'activate',schedule.schedule_id,schedule.status==='Active'?'outline-danger':'outline-success');toggle.classList.remove('me-1','mb-1');range.append(hours,state,toggle);ranges.append(range);
                });
                dayRow.append(ranges);week.append(dayRow);
            }
            scheduleCell.append(week);
            const actions=document.createElement('td');actions.className='record-actions weekly-doctor-actions';
            actions.append(actionButton('Edit Weekly Schedule','edit',doctor.doctor_id,'outline-primary'));
            tr.append(doctorCell,scheduleCell,actions);tbody.append(tr);
        });
    };
    const renderTable = (rows, cfg) => {
        if (!thead || !tbody) return;
        if(pageType==='schedules'){renderScheduleGroups(rows);return;}
        thead.replaceChildren();
        cfg.columns.forEach(([,label]) => { const th=document.createElement('th'); th.scope='col'; th.textContent=label; thead.append(th); });
        const canEdit = ['patients','appointments','users','doctors','specializations','schedules','medical-records'].includes(pageType);
        if (canEdit) { const th=document.createElement('th'); th.scope='col'; th.textContent='Actions'; thead.append(th); }
        tbody.replaceChildren();
        if (!rows.length) {
            const tr=document.createElement('tr'); tr.className='empty-table-row'; const td=document.createElement('td'); td.colSpan=cfg.columns.length+(canEdit?1:0); td.textContent='No records found.'; tr.append(td); tbody.append(tr); return;
        }
        rows.forEach((row) => {
            const tr=document.createElement('tr'); tr.dataset.recordRow='';
            tr.dataset.searchText=Object.values(row).join(' ').toLocaleLowerCase();
            cfg.columns.forEach(([key]) => {
                const td=document.createElement('td'); let value=row[key] ?? '';
                if (key === 'available_day') value=dayName(value);
                if (key === 'appointment_time' || key === 'start_time' || key === 'end_time') value=formatAppointmentTime(value);
                if (key === 'consultation_date') value=String(value).replace('T',' ');
                if (key === 'status' || key === 'account_status') td.append(statusPill(value)); else td.textContent=String(value);
                tr.append(td);
            });
            if (canEdit) tr.append(rowActions(row));
            tbody.append(tr);
        });
    };

    let currentReportPayload = null;
    const reportDateInputs = {from:page.querySelector('[data-report-from]'),to:page.querySelector('[data-report-to]')};
    const reportDateText = (value) => {
        const [year,month,day]=String(value||'').split('-').map(Number);
        return year&&month&&day?new Date(year,month-1,day).toLocaleDateString(undefined,{year:'numeric',month:'short',day:'numeric'}):String(value||'');
    };
    const reportEscape = (value) => String(value??'').replace(/[&<>"']/g,(char)=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[char]));
    const reportCsvCell = (value) => {
        let text=String(value??'');
        if(/^[\t\r ]*[=+\-@]/.test(text))text=`'${text}`;
        return `"${text.replaceAll('"','""')}"`;
    };
    const reportSetPresetActive = (preset) => page.querySelectorAll('[data-report-preset]').forEach((button)=>{
        const active=button.dataset.reportPreset===preset;
        button.classList.toggle('active',active);button.setAttribute('aria-pressed',String(active));
    });
    const reportSetRange = (preset) => {
        const now=new Date(),end=new Date(now.getFullYear(),now.getMonth(),now.getDate());
        let start=new Date(end);
        if(preset==='week'){const day=(end.getDay()+6)%7;start.setDate(end.getDate()-day);end.setDate(start.getDate()+6);}
        else if(preset==='month'){start.setDate(1);end.setMonth(end.getMonth()+1,0);}
        const toIso=(date)=>`${date.getFullYear()}-${String(date.getMonth()+1).padStart(2,'0')}-${String(date.getDate()).padStart(2,'0')}`;
        reportDateInputs.from.value=toIso(start);reportDateInputs.to.value=toIso(end);reportSetPresetActive(preset);
    };
    const drawReportChart = (points) => {
        const host=page.querySelector('[data-report-chart]'),empty=page.querySelector('[data-report-chart-empty]');
        if(!host||!empty)return;
        host.replaceChildren();
        if(!points?.length){host.classList.add('d-none');empty.classList.remove('d-none');return;}
        host.classList.remove('d-none');empty.classList.add('d-none');
        const ns='http://www.w3.org/2000/svg',svg=document.createElementNS(ns,'svg');
        svg.setAttribute('viewBox','0 0 760 270');svg.setAttribute('role','img');svg.setAttribute('aria-label','Appointment counts over time');
        const max=Math.max(1,...points.map((item)=>Number(item.total)||0)),left=56,right=24,top=24,bottom=54,width=760-left-right,height=270-top-bottom;
        const add=(name,attrs,textValue)=>{const el=document.createElementNS(ns,name);Object.entries(attrs).forEach(([key,value])=>el.setAttribute(key,String(value)));if(textValue!==undefined)el.textContent=textValue;svg.append(el);return el;};
        for(let tick=0;tick<=4;tick++){const y=top+height-(height*tick/4),value=Math.round(max*tick/4);add('line',{x1:left,y1:y,x2:760-right,y2:y,class:'report-chart-gridline'});add('text',{x:left-12,y:y+4,'text-anchor':'end',class:'report-chart-axis'},String(value));}
        const step=points.length>1?width/(points.length-1):0;
        const coords=points.map((item,index)=>({x:points.length===1?left+width/2:left+index*step,y:top+height-(Number(item.total)||0)/max*height,item}));
        if(coords.length>1)add('polyline',{points:coords.map(({x,y})=>`${x},${y}`).join(' '),class:'report-chart-line'});
        coords.forEach(({x,y,item},index)=>{
            add('circle',{cx:x,cy:y,r:5,class:'report-chart-point'});
            add('text',{x,y:y-11,'text-anchor':'middle',class:'report-chart-value'},String(Number(item.total)||0));
            const label=String(item.label||'');
            const display=label.length>11?label.slice(5):label;
            if(points.length<=12||index===0||index===points.length-1||index%Math.ceil(points.length/10)===0)add('text',{x,y:top+height+26,'text-anchor':'middle',class:'report-chart-axis'},display);
        });
        host.append(svg);
    };
    const renderReport = (payload) => {
        currentReportPayload=payload;
        const summary=payload.summary||{};
        page.querySelector('[data-report-stat="appointments"]').textContent=String(summary.total??0);
        page.querySelector('[data-report-stat="patients"]').textContent=String(payload.patients_total??0);
        page.querySelector('[data-report-stat="pending"]').textContent=String(summary.pending??0);
        page.querySelector('[data-report-stat="completed"]').textContent=String(summary.completed??0);
        page.querySelector('[data-report-period]').textContent=`${reportDateText(payload.from)} – ${reportDateText(payload.to)}`;
        drawReportChart(payload.trend||[]);
        const body=page.querySelector('[data-report-doctors]');body.replaceChildren();
        if(!(Number(summary.total)||0)){
            const tr=document.createElement('tr');tr.className='empty-table-row';const td=document.createElement('td');td.colSpan=5;td.textContent='No appointments match this date range.';tr.append(td);body.append(tr);return;
        }
        (payload.by_doctor||[]).filter((row)=>Number(row.total)>0).forEach((row)=>{
            const tr=document.createElement('tr');
            [row.doctor_name,row.specialization,row.total,row.pending,row.completed].forEach((value)=>{const td=document.createElement('td');td.textContent=String(value??0);tr.append(td);});
            body.append(tr);
        });
        if(!body.children.length){const tr=document.createElement('tr');tr.className='empty-table-row';const td=document.createElement('td');td.colSpan=5;td.textContent='No appointments match this date range.';tr.append(td);body.append(tr);}
    };
    const loadReports = async () => {
        const from=reportDateInputs.from?.value,to=reportDateInputs.to?.value;
        if(!from||!to){showFeedback('Choose both a start date and an end date.','error');return;}
        if(from>to){showFeedback('The start date must be on or before the end date.','error');return;}
        try{
            const granularity=page.querySelector('[data-report-granularity]')?.value||'daily';
            const payload=await request('reports',{params:{from,to,granularity}});renderReport(payload);
            const feedback=page.querySelector('[data-feedback]');feedback?.classList.add('d-none');
        }catch(error){showFeedback(error.message,'error');}
    };
    const exportReportCsv = () => {
        if(!currentReportPayload)return;
        const p=currentReportPayload,rows=[['ClinicCare report','Date range',`${p.from} to ${p.to}`],[],['Summary','Value'],['Total appointments',p.summary?.total??0],['Total patients',p.patients_total??0],['Pending appointments',p.summary?.pending??0],['Completed appointments',p.summary?.completed??0],[],['Appointment trends',p.granularity],['Period','Appointments'],...(p.trend||[]).map((row)=>[row.label,row.total]),[],['Doctor name','Specialization','Total appointments','Pending appointments','Completed appointments'],...(p.by_doctor||[]).filter((row)=>Number(row.total)>0).map((row)=>[row.doctor_name,row.specialization,row.total,row.pending,row.completed])];
        const csv=rows.map((row)=>row.map(reportCsvCell).join(',')).join('\r\n');
        const url=URL.createObjectURL(new Blob(['\uFEFF',csv],{type:'text/csv;charset=utf-8'}));
        const link=document.createElement('a');link.href=url;link.download=`cliniccare-report-${p.from}-to-${p.to}.csv`;link.click();setTimeout(()=>URL.revokeObjectURL(url),1000);
    };
    const exportReportPdf = () => {
        if(!currentReportPayload)return;
        const p=currentReportPayload,w=window.open('','_blank');
        if(!w){showFeedback('Allow pop-ups for ClinicCare to open the printable report.','warning');return;}
        w.opener=null;
        const doctors=(p.by_doctor||[]).filter((row)=>Number(row.total)>0).map((row)=>`<tr><td>${reportEscape(row.doctor_name)}</td><td>${reportEscape(row.specialization)}</td><td>${Number(row.total)||0}</td><td>${Number(row.pending)||0}</td><td>${Number(row.completed)||0}</td></tr>`).join('');
        const trends=(p.trend||[]).map((row)=>`<tr><td>${reportEscape(row.label)}</td><td>${Number(row.total)||0}</td></tr>`).join('');
        w.document.write(`<!doctype html><html><head><meta charset="utf-8"><title>ClinicCare report</title><style>body{font:14px Arial,sans-serif;color:#243247;margin:36px}h1{color:#2855bd;margin-bottom:4px}h2{margin-top:28px;color:#334258;font-size:18px}.muted{color:#738493}table{width:100%;border-collapse:collapse;margin-top:12px}th,td{text-align:left;border-bottom:1px solid #e4ebf0;padding:9px}th{background:#f3f6fc;color:#536276} .stats{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-top:24px}.stat{border:1px solid #e4ebf0;border-radius:8px;padding:14px}.stat strong{display:block;font-size:22px;margin-top:8px}@media print{body{margin:15mm}}</style></head><body><h1>ClinicCare</h1><div class="muted">Clinic appointments and performance report · ${reportEscape(p.from)} to ${reportEscape(p.to)}</div><div class="stats"><div class="stat">Total appointments<strong>${Number(p.summary?.total)||0}</strong></div><div class="stat">Total patients<strong>${Number(p.patients_total)||0}</strong></div><div class="stat">Pending appointments<strong>${Number(p.summary?.pending)||0}</strong></div><div class="stat">Completed appointments<strong>${Number(p.summary?.completed)||0}</strong></div></div><h2>Appointment trends (${reportEscape(p.granularity)})</h2><table><thead><tr><th>Period</th><th>Appointments</th></tr></thead><tbody>${trends||'<tr><td colspan="2">No appointments match this date range.</td></tr>'}</tbody></table><h2>Appointments by doctor</h2><table><thead><tr><th>Doctor Name</th><th>Specialization</th><th>Total</th><th>Pending</th><th>Completed</th></tr></thead><tbody>${doctors||'<tr><td colspan="5">No appointments match this date range.</td></tr>'}</tbody></table><script>window.addEventListener('load',()=>window.print());</script></body></html>`);w.document.close();
    };

    const loadManagement = async () => {
        const cfg = configs[pageType];
        if (!cfg) return;
        if (countLabel) countLabel.textContent='Loading records…';
        try {
            const params={};
            if (['patients','appointments'].includes(pageType) && searchInput?.value.trim()) params.q=searchInput.value.trim();
            if (pageType === 'appointments' && statusFilter?.value) params.status=statusFilter.value;
            if (pageType === 'appointments' && appointmentQuickFilter==='today') params.today='1';
            else if (pageType === 'appointments' && appointmentQuickFilter==='upcoming') params.upcoming='1';
            else if (pageType === 'appointments' && dateFilter?.value) params.date=dateFilter.value;
            const payload = await request(cfg.resource,{params});
            records=asArray(payload);
            if (pageType === 'schedules') {
                const scheduleRows=records;
                const doctors=asArray(await request('doctors'));
                records=doctors.map((doctor)=>({...doctor,schedules:scheduleRows.filter((row)=>Number(row.doctor_id)===Number(doctor.doctor_id))}));
            }
            if (pageType === 'appointments' && role === 'Doctor') {
                const existingRecords=new Set(asArray(await request('medical-records')).map((record)=>Number(record.appointment_id)));
                records=records.map((row)=>({...row,has_medical_record:existingRecords.has(Number(row.appointment_id))}));
            }
            if (searchInput?.value.trim() && !['patients','appointments'].includes(pageType)) {
                const query=searchInput.value.trim().toLocaleLowerCase();
                records=records.filter((row)=>pageType==='schedules'
                    ? [row.full_name,row.specialization_name,...(row.schedules||[]).flatMap((item)=>[dayName(item.available_day),item.start_time,item.end_time,item.status])].join(' ').toLocaleLowerCase().includes(query)
                    : Object.values(row).join(' ').toLocaleLowerCase().includes(query));
            }
            renderTable(records,cfg);
            if (countLabel) countLabel.textContent=`${records.length} ${cfg.count}`;
        } catch (error) {
            if (countLabel) countLabel.textContent='Could not load records';
            showFeedback(error.message,'error');
            if (tbody) { tbody.replaceChildren(); const tr=document.createElement('tr'); const td=document.createElement('td'); td.colSpan=(cfg.columns?.length||1)+1; td.textContent=error.message; tr.append(td); tbody.append(tr); }
        }
    };

    const buildWeeklyScheduleEditor = async (scheduleRow = null) => {
        const title=document.createElement('h2');title.className='h5 mb-3';title.textContent=scheduleRow?'Edit weekly availability':'Set weekly availability';form.append(title);
        const intro=document.createElement('p');intro.className='text-secondary small mb-3';intro.textContent='Choose available days and set one or more working time ranges for each day. Times must stay within 7:00 AM–7:00 PM.';form.append(intro);
        const doctorField=document.createElement('div');doctorField.className='mb-4';
        const doctorLabel=document.createElement('label');doctorLabel.className='form-label';doctorLabel.htmlFor='weekly-doctor';doctorLabel.textContent='Doctor';
        const doctorSelect=document.createElement('select');doctorSelect.className='form-select';doctorSelect.id='weekly-doctor';doctorSelect.required=true;
        const doctorPlaceholder=document.createElement('option');doctorPlaceholder.value='';doctorPlaceholder.textContent='Select a doctor';doctorSelect.append(doctorPlaceholder);
        const doctors=asArray(await request('doctors'));
        doctors.forEach((doctor)=>{const option=document.createElement('option');option.value=String(doctor.doctor_id);option.textContent=`${doctor.full_name}${doctor.status==='Active'&&doctor.account_status==='Active'?'':' (Inactive)'}`;doctorSelect.append(option);});
        doctorField.append(doctorLabel,doctorSelect);form.append(doctorField);
        const dayList=document.createElement('div');dayList.className='weekly-schedule-grid';form.append(dayList);
        const weekdayNames=['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
        const dayCards=new Map();
        const formatScheduleTime=(value)=>{
            if(!value)return 'Choose a time';
            const [hour,minute]=value.split(':').map(Number);
            return new Date(2000,0,1,hour,minute).toLocaleTimeString('en-US',{hour:'numeric',minute:'2-digit'});
        };
        const makeRange=(card,dayName,start='07:00',end='19:00')=>{
            const range=document.createElement('div');range.className='weekly-time-range';range.dataset.timeRange='';
            const startLabel=document.createElement('label');startLabel.className='weekly-time-field';startLabel.textContent='Start time (AM/PM)';
            const startInput=document.createElement('input');startInput.type='time';startInput.lang='en-US';startInput.className='form-control form-control-sm';startInput.value=String(start).slice(0,5);startInput.min='07:00';startInput.max='19:00';startInput.step='60';startInput.dataset.startTime='';startInput.setAttribute('aria-label',`${dayName} start time`);const startPreview=document.createElement('small');startPreview.className='weekly-time-preview';startPreview.textContent=formatScheduleTime(startInput.value);startInput.addEventListener('input',()=>{startPreview.textContent=formatScheduleTime(startInput.value);});startLabel.append(startInput,startPreview);
            const endLabel=document.createElement('label');endLabel.className='weekly-time-field';endLabel.textContent='End time (AM/PM)';
            const endInput=document.createElement('input');endInput.type='time';endInput.lang='en-US';endInput.className='form-control form-control-sm';endInput.value=String(end).slice(0,5);endInput.min='07:00';endInput.max='19:00';endInput.step='60';endInput.dataset.endTime='';endInput.setAttribute('aria-label',`${dayName} end time`);const endPreview=document.createElement('small');endPreview.className='weekly-time-preview';endPreview.textContent=formatScheduleTime(endInput.value);endInput.addEventListener('input',()=>{endPreview.textContent=formatScheduleTime(endInput.value);});endLabel.append(endInput,endPreview);
            const remove=document.createElement('button');remove.type='button';remove.className='btn btn-sm btn-outline-secondary weekly-remove-range';remove.textContent='Remove range';remove.setAttribute('aria-label',`Remove ${dayName} time range`);
            remove.addEventListener('click',()=>{range.remove();updateDay(card);});range.append(startLabel,endLabel,remove);card.querySelector('[data-weekly-ranges]').append(range);updateDay(card);return range;
        };
        const updateDay=(card)=>{
            const enabled=card.querySelector('[data-day-enabled]').checked;
            card.classList.toggle('is-unavailable',!enabled);
            card.querySelector('[data-day-state]').textContent=enabled?'Available':'Unavailable';
            card.querySelectorAll('input[data-start-time],input[data-end-time]').forEach((input)=>{input.disabled=!enabled;input.required=enabled;});
            card.querySelector('[data-add-range]').disabled=!enabled;
        };
        weekdayNames.forEach((name,index)=>{
            const card=document.createElement('section');card.className='weekly-day-card is-unavailable';card.dataset.weeklyDay=String(index+1);
            const header=document.createElement('div');header.className='weekly-day-header';
            const heading=document.createElement('h3');heading.textContent=name;
            const switchLabel=document.createElement('label');switchLabel.className='weekly-day-toggle';
            const toggle=document.createElement('input');toggle.type='checkbox';toggle.className='form-check-input';toggle.dataset.dayEnabled='';toggle.setAttribute('aria-label',`${name} available`);
            const state=document.createElement('span');state.dataset.dayState='';state.textContent='Unavailable';switchLabel.append(toggle,state);header.append(heading,switchLabel);
            const ranges=document.createElement('div');ranges.className='weekly-day-ranges';ranges.dataset.weeklyRanges='';
            const add=document.createElement('button');add.type='button';add.className='btn btn-sm btn-outline-secondary';add.dataset.addRange='';add.textContent='＋ Add time range';
            add.addEventListener('click',()=>makeRange(card,name));toggle.addEventListener('change',()=>updateDay(card));
            card.append(header,ranges,add);dayList.append(card);dayCards.set(index+1,card);updateDay(card);
        });
        const feedbackRow=document.createElement('div');feedbackRow.className='d-flex flex-wrap gap-2 mt-3';
        const save=document.createElement('button');save.type='submit';save.className='btn btn-primary';save.textContent='Save weekly availability';
        const close=document.createElement('button');close.type='button';close.className='btn btn-outline-secondary';close.textContent='Close';close.addEventListener('click',()=>form.classList.add('d-none'));feedbackRow.append(save,close);form.append(feedbackRow);
        const populate=async(doctorId)=>{
            for(const [day,card] of dayCards){card.querySelector('[data-weekly-ranges]').replaceChildren();card.querySelector('[data-day-enabled]').checked=false;updateDay(card);}
            if(!doctorId)return;
            save.disabled=true;
            try{
                const rows=asArray(await request('schedules',{params:{doctor_id:doctorId}}));
                const byDay=new Map();rows.forEach((item)=>{const day=Number(item.available_day);if(!byDay.has(day))byDay.set(day,[]);byDay.get(day).push(item);});
                byDay.forEach((items,day)=>{
                    const card=dayCards.get(day);if(!card)return;
                    const active=items.filter((item)=>item.status==='Active');card.querySelector('[data-day-enabled]').checked=active.length>0;
                    const ranges=active;
                    ranges.forEach((item)=>makeRange(card,weekdayNames[day-1],item.start_time,item.end_time));
                    if(!ranges.length)makeRange(card,weekdayNames[day-1]);
                    updateDay(card);
                });
            }catch(error){showFeedback(error.message,'error');}
            finally{save.disabled=false;}
        };
        doctorSelect.addEventListener('change',()=>populate(doctorSelect.value));
        if(scheduleRow){doctorSelect.value=String(scheduleRow.doctor_id);doctorSelect.disabled=true;await populate(scheduleRow.doctor_id);}
        else if(doctors.length===1){doctorSelect.value=String(doctors[0].doctor_id);await populate(doctorSelect.value);}
    };

    const makeField = async (field, data, mode) => {
        const wrapper=document.createElement('div'); wrapper.className='col-12 col-md-6 mb-3';
        const id=`field-${field.name}-${Math.random().toString(36).slice(2)}`;
        const label=document.createElement('label'); label.className='form-label'; label.htmlFor=id; label.textContent=field.label; wrapper.append(label);
        let input;
        if (field.type === 'textarea') { input=document.createElement('textarea'); input.rows=3; }
        else if (field.type === 'select') {
            input=document.createElement('select'); input.className='form-select';
            const placeholder=document.createElement('option'); placeholder.value=''; placeholder.textContent=field.placeholder || 'Select an option'; input.append(placeholder);
            const options=await loadOptions(field);
            options.forEach(([value,text])=>{ const option=document.createElement('option'); option.value=String(value); option.textContent=String(text); input.append(option); });
            if(mode==='edit'&&field.name==='gender'&&data?.gender&&!options.some(([optionValue])=>String(optionValue)===String(data.gender))){
                const legacy=document.createElement('option');legacy.value=String(data.gender);legacy.textContent=`Existing value: ${data.gender} (keep current)`;input.append(legacy);
            }
        } else { input=document.createElement('input'); input.type=field.type||'text'; }
        input.id=id; input.name=field.name; if (field.type !== 'select') input.classList.add('form-control');
        if (field.required && !(mode==='edit' && field.createOnly)) input.required=true;
        if (field.max) input.maxLength=field.max;
        if (field.min) input.min=String(field.min);
        if (field.max && field.type==='number') input.max=String(field.max);
        if (field.type==='password' && mode==='edit' && field.name==='password') input.required=false;
        let value=data?.[field.name];
        if (field.name==='available_day' && value !== undefined) value=String(value);
        if (field.name==='user_id' && value !== undefined) value=String(value);
        if (field.name==='specialization_id' && value !== undefined) value=String(value);
        if (field.name==='doctor_id' && value !== undefined) value=String(value);
        if (field.name==='patient_id' && value !== undefined) value=String(value);
        if (field.name==='appointment_id' && value !== undefined) value=String(value);
        if (field.name==='consultation_date' && !value) value=localToday();
        if (field.name==='appointment_date' && !value) value=localToday();
        if (field.name==='duration_minutes' && !value) value='30';
        if (value !== undefined && value !== null) input.value=(field.type==='time' ? String(value).slice(0,5) : String(value));
        if(field.name==='appointment_time'){input.min='07:00';input.max='18:30';input.step='60';}
        if (mode==='edit' && field.createOnly) { input.disabled=true; input.required=false; }
        if (field.help) { const help=document.createElement('small'); help.className='form-text text-secondary'; help.textContent=field.help; wrapper.append(input,help); }
        else wrapper.append(input);
        return wrapper;
    };

    const attachAppointmentScheduleGuidance = (submitButton) => {
        const doctorInput=form.querySelector('[name="doctor_id"]');
        const dateInput=form.querySelector('[name="appointment_date"]');
        const timeInput=form.querySelector('[name="appointment_time"]');
        const durationInput=form.querySelector('[name="duration_minutes"]');
        if(!doctorInput||!dateInput||!timeInput||!durationInput)return;

        const guidance=document.createElement('div');
        guidance.className='alert alert-info appointment-schedule-guidance mb-3';
        guidance.setAttribute('role','status');
        guidance.setAttribute('aria-live','polite');
        guidance.textContent='Choose a doctor to view their active working days and hours.';
        form.insertBefore(guidance,submitButton.parentElement);

        const weekdayNames=['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
        let activeSchedules=[];
        let requestVersion=0;
        const minutesOf=(value)=>{
            const parts=String(value||'').split(':').map(Number);
            return (parts[0]||0)*60+(parts[1]||0);
        };
        const displayTime=(minutes)=>{
            const hour=Math.floor(minutes/60)%24;
            const minute=minutes%60;
            return new Date(2000,0,1,hour,minute).toLocaleTimeString(undefined,{hour:'numeric',minute:'2-digit'});
        };
        const dayNumber=(date)=>{
            const jsDay=new Date(`${date}T12:00:00`).getDay();
            return jsDay===0?7:jsDay;
        };
        const activeDaySummary=()=>weekdayNames.map((name,index)=>{
            const rows=activeSchedules.filter((item)=>Number(item.available_day)===index+1);
            if(!rows.length)return `${name}: no active hours`;
            return `${name}: ${rows.map((item)=>`${displayTime(minutesOf(item.start_time))}–${displayTime(minutesOf(item.end_time))}`).join(', ')}`;
        }).join(' · ');
        const showGuidance=(message,kind='info')=>{
            guidance.className=`alert alert-${kind} appointment-schedule-guidance mb-3`;
            guidance.textContent=message;
        };
        const validateSelection=()=>{
            dateInput.setCustomValidity('');
            timeInput.setCustomValidity('');
            const doctorId=doctorInput.value;
            const selectedDate=dateInput.value;
            const requestedDuration=Number(durationInput.value||30);
            const latestStart=19*60-requestedDuration;
            timeInput.min='07:00';
            timeInput.max=Number.isInteger(requestedDuration)&&requestedDuration>0&&latestStart>=7*60
                ? `${String(Math.floor(latestStart/60)).padStart(2,'0')}:${String(latestStart%60).padStart(2,'0')}`
                : '07:00';
            if(!doctorId){showGuidance('Choose a doctor to view their active working days and hours.');return;}
            const weekly=activeDaySummary();
            if(!selectedDate){showGuidance(`Active working hours: ${weekly}. Choose an appointment date to check that day.`);return;}
            const selectedDay=dayNumber(selectedDate);
            const dayName=weekdayNames[selectedDay-1];
            const daySchedules=activeSchedules.filter((item)=>Number(item.available_day)===selectedDay);
            if(!daySchedules.length){
                timeInput.min='07:00';timeInput.max='18:30';
                const message=`No active schedule exists for this doctor on ${dayName}, ${selectedDate}. Choose another date or doctor.`;
                dateInput.setCustomValidity(message);
                showGuidance(`${message} Active working days: ${weekly}.`,'warning');
                return;
            }
            const dayStart=Math.max(7*60,Math.min(...daySchedules.map((item)=>minutesOf(item.start_time))));
            const dayEnd=Math.min(19*60,Math.max(...daySchedules.map((item)=>minutesOf(item.end_time))));
            const dayLatest=dayEnd-requestedDuration;
            timeInput.min=`${String(Math.floor(dayStart/60)).padStart(2,'0')}:${String(dayStart%60).padStart(2,'0')}`;
            timeInput.max=`${String(Math.floor(Math.max(dayStart,dayLatest)/60)).padStart(2,'0')}:${String(Math.max(dayStart,dayLatest)%60).padStart(2,'0')}`;
            if(!timeInput.value||!durationInput.value){
                const ranges=daySchedules.map((item)=>`${displayTime(minutesOf(item.start_time))}–${displayTime(minutesOf(item.end_time))}`).join(', ');
                showGuidance(`Active hours for ${dayName}: ${ranges}. Enter a start time and duration to check coverage.`);
                return;
            }
            const start=minutesOf(timeInput.value);
            const duration=Number(durationInput.value);
            const end=start+duration;
            if(!Number.isInteger(duration)||duration<1){showGuidance(`Active working hours: ${weekly}. Enter a valid duration.`,'warning');return;}
            if(start<7*60){
                const message='Appointments cannot start before 7:00 AM. Choose a later time.';
                timeInput.setCustomValidity(message);showGuidance(message,'warning');return;
            }
            if(end>19*60){
                const message=`This appointment ends at ${displayTime(end)}. It must finish by 7:00 PM; choose an earlier time or shorter duration.`;
                timeInput.setCustomValidity(message);showGuidance(message,'warning');return;
            }
            if(end>1440){
                const message='The appointment would continue past midnight. Choose an earlier start time or shorter duration.';
                timeInput.setCustomValidity(message);showGuidance(message,'warning');return;
            }
            const fits=daySchedules.some((item)=>minutesOf(item.start_time)<=start&&minutesOf(item.end_time)>=end);
            if(!fits){
                const endLabel=displayTime(end);
                const ranges=daySchedules.map((item)=>`${displayTime(minutesOf(item.start_time))}–${displayTime(minutesOf(item.end_time))}`).join(', ');
                const message=`This appointment ends at ${endLabel} and does not fit within one active ${dayName} schedule (${ranges}). Choose an earlier time or shorter duration.`;
                timeInput.setCustomValidity(message);showGuidance(message,'warning');return;
            }
            showGuidance(`This appointment fits an active ${dayName} schedule. Active working hours: ${weekly}.`,'success');
        };
        const loadSchedules=async()=>{
            const currentVersion=++requestVersion;
            doctorInput.setCustomValidity('');dateInput.setCustomValidity('');timeInput.setCustomValidity('');
            if(!doctorInput.value){activeSchedules=[];submitButton.disabled=false;validateSelection();return;}
            submitButton.disabled=true;
            showGuidance('Loading this doctor’s active working hours…');
            try{
                const payload=await request('schedules',{params:{doctor_id:doctorInput.value}});
                if(currentVersion!==requestVersion)return;
                activeSchedules=asArray(payload).filter((item)=>String(item.status).toLowerCase()==='active');
                validateSelection();
            }catch(error){
                if(currentVersion===requestVersion){activeSchedules=[];showGuidance(`Could not load this doctor’s active schedule. ${error.message}`,'warning');}
            }finally{
                if(currentVersion===requestVersion)submitButton.disabled=false;
            }
        };
        doctorInput.addEventListener('change',loadSchedules);
        dateInput.addEventListener('change',loadSchedules);
        timeInput.addEventListener('input',validateSelection);
        durationInput.addEventListener('input',validateSelection);
        loadSchedules();
    };

    const openForm = async (row = null, preset = {}) => {
        formEntityType=preset.formType || pageType;
        const cfg=configs[formEntityType];
        if (!cfg || !form) return;
        editingRecord=row;
        form.replaceChildren(); form.classList.remove('d-none');
        if(formEntityType==='schedules'){
            try{await buildWeeklyScheduleEditor(row);}catch(error){showFeedback(error.message,'error');}
            form.scrollIntoView({behavior:'smooth',block:'start'});return;
        }
        const title=document.createElement('h2'); title.className='h5 mb-3'; title.textContent=`${row?'Edit':'Add'} ${cfg.title}`; form.append(title);
        const grid=document.createElement('div'); grid.className='row'; form.append(grid);
        try {
            const fields=cfg.fields.filter((field)=>!(field.createOnly&&row)&&!(field.editOnly&&!row));
            for (const field of fields) {
                let data=row;
                if (field.name==='appointment_id' && preset.appointment_id) data={...row,appointment_id:preset.appointment_id};
                grid.append(await makeField(field,data,row?'edit':'create'));
            }
        } catch (error) { showFeedback(error.message,'error'); }
        const controls=document.createElement('div'); controls.className='d-flex gap-2'; form.append(controls);
        const save=document.createElement('button'); save.type='submit'; save.className='btn btn-primary'; save.textContent=row?'Save changes':'Save'; controls.append(save);
        const cancel=document.createElement('button'); cancel.type='button'; cancel.className='btn btn-outline-secondary'; cancel.textContent='Close'; cancel.addEventListener('click',()=>{form.classList.add('d-none');}); controls.append(cancel);
        if(['Admin','Receptionist'].includes(role)&&formEntityType==='appointments')attachAppointmentScheduleGuidance(save);
        form.scrollIntoView({behavior:'smooth',block:'start'});
    };

    const collectPayload = () => {
        const payload={};
        new FormData(form).forEach((value,key)=>{
            if (form.querySelector(`[name="${CSS.escape(key)}"]`)?.disabled || value==='') return;
            const numeric=['patient_id','doctor_id','user_id','specialization_id','available_day','duration_minutes','appointment_id'];
            payload[key]=numeric.includes(key)?Number(value):String(value);
        });
        if (formEntityType==='users' && editingRecord && !payload.password) delete payload.password;
        return payload;
    };

    const saveRecord = async (event) => {
        event.preventDefault();
        if (!form.reportValidity()) return;
        const cfg=configs[formEntityType];
        if(formEntityType==='schedules'){
            const days=[...form.querySelectorAll('[data-weekly-day]')].map((card)=>({
                available_day:Number(card.dataset.weeklyDay),enabled:card.querySelector('[data-day-enabled]').checked,
                ranges:[...card.querySelectorAll('[data-time-range]')].map((range)=>({start_time:range.querySelector('[data-start-time]').value,end_time:range.querySelector('[data-end-time]').value}))
            }));
            for(const day of days)if(day.enabled){
                if(!day.ranges.length){showFeedback('Add at least one time range for each available day.','error');return;}
                day.ranges.sort((a,b)=>a.start_time.localeCompare(b.start_time));
                for(let i=0;i<day.ranges.length;i++){
                    const range=day.ranges[i];
                    if(range.end_time<=range.start_time){showFeedback('Each end time must be later than its start time.','error');return;}
                    if(i>0&&range.start_time<day.ranges[i-1].end_time){showFeedback('Time ranges on the same day cannot overlap. Adjust or remove a range.','error');return;}
                }
            }
            try{const result=await request('schedules/weekly',{method:'PUT',body:{doctor_id:Number(form.querySelector('#weekly-doctor').value),days}});form.classList.add('d-none');showFeedback(result.message||'Weekly availability saved.');await loadManagement();}
            catch(error){showFeedback(error.message,'error');}
            return;
        }
        const payload=collectPayload();
        let resource=cfg.resource; let method='POST';
        if (editingRecord) {
            method='PUT';
            const idKey=({patients:'patient_id',appointments:'appointment_id',users:'user_id',doctors:'doctor_id',specializations:'specialization_id',schedules:'schedule_id'})[formEntityType];
            if (formEntityType==='schedules') {
                payload.schedule_id=Number(editingRecord.schedule_id);
                payload.doctor_id=Number(editingRecord.doctor_id);
            }
            else resource+=`/${editingRecord[idKey]}`;
        }
        try {
            const result=await request(resource,{method,body:payload});
            form.classList.add('d-none');
            showFeedback(result.message || 'Saved successfully.');
            await loadManagement();
        } catch (error) { showFeedback(error.message,'error'); }
    };

    const updateStatus = async (row, status) => {
        const route=pageType==='patients'?'patients':pageType==='doctors'?'doctors':pageType==='schedules'?'schedules':'appointments';
        const idKey=({patients:'patient_id',doctors:'doctor_id',schedules:'schedule_id',appointments:'appointment_id'})[pageType];
        try {
            const result=await request(`${route}/${row[idKey]}`,{method:'PATCH',body:{status}});
            showFeedback(result.message || 'Status updated.'); await loadManagement();
        } catch(error) { showFeedback(error.message,'error'); }
    };

    const openDetails = async (row) => {
        const idKey=({patients:'patient_id',appointments:'appointment_id','medical-records':'record_id'})[pageType];
        try {
            const detail=await request(`${configs[pageType].resource}/${row[idKey]}`);
            const data=detail.data || row;
            const dialog=document.createElement('dialog'); dialog.className='clinic-detail-dialog';
            const shell=document.createElement('div'); shell.className='detail-dialog-shell';
            const header=document.createElement('header'); header.className='detail-dialog-header';
            const headingGroup=document.createElement('div');
            const eyebrow=document.createElement('span'); eyebrow.className='detail-eyebrow'; eyebrow.textContent=pageType==='appointments'?'CLINIC VISIT':'CLINIC RECORD';
            const detailTitle=configs[pageType].title;
            const heading=document.createElement('h2'); heading.textContent=`${detailTitle.charAt(0).toUpperCase()}${detailTitle.slice(1)} details`;
            headingGroup.append(eyebrow,heading);
            const subheading=document.createElement('p');
            if(pageType==='appointments')subheading.textContent=`Appointment #${data.appointment_id ?? row.appointment_id} · ${data.patient_name ?? 'Patient'}`;
            else subheading.textContent=`Record #${data[idKey] ?? row[idKey]}`;
            headingGroup.append(subheading); header.append(headingGroup);
            if(pageType==='appointments'&&data.status)header.append(statusPill(data.status));
            const close=document.createElement('button'); close.type='button'; close.className='detail-close'; close.setAttribute('aria-label','Close appointment details'); close.textContent='×';
            close.addEventListener('click',()=>dialog.close()); header.append(close); shell.append(header);

            const content=document.createElement('div'); content.className='detail-dialog-content';
            const appendSection=(title,fields)=>{
                const section=document.createElement('section'); section.className='detail-section';
                const sectionTitle=document.createElement('h3'); sectionTitle.textContent=title; section.append(sectionTitle);
                const grid=document.createElement('div'); grid.className='detail-grid';
                fields.forEach(([label,value,wide=false])=>{
                    const item=document.createElement('div'); item.className=`detail-item${wide?' detail-item-wide':''}`;
                    const name=document.createElement('span'); name.textContent=label;
                    const detailValue=document.createElement('strong'); detailValue.textContent=String(value ?? '—');
                    item.append(name,detailValue); grid.append(item);
                });
                section.append(grid); content.append(section);
            };
            const formatDate=(value)=>{
                if(!value)return '—';
                const [year,month,day]=String(value).slice(0,10).split('-').map(Number);
                return new Date(year,month-1,day).toLocaleDateString(undefined,{year:'numeric',month:'long',day:'numeric'});
            };
            const formatTime=(value)=>{
                if(!value)return '—';
                const [hour,minute]=String(value).split(':').map(Number);
                return new Date(2000,0,1,hour,minute).toLocaleTimeString(undefined,{hour:'numeric',minute:'2-digit'});
            };
            if(pageType==='appointments'){
                appendSection('Appointment',[
                    ['Date',formatDate(data.appointment_date)],
                    ['Time',formatTime(data.appointment_time)],
                    ['Duration',`${data.duration_minutes ?? '—'} minutes`],
                    ['Reason',data.reason || '—',true],
                ]);
                appendSection('Patient',[
                    ['Name',data.patient_name],
                    ['Patient ID',data.patient_id],
                    ['Birth date',formatDate(data.birth_date)],
                    ['Gender',data.gender],
                    ['Contact number',data.contact_number],
                ]);
                appendSection('Doctor',[
                    ['Doctor',data.doctor_name],
                    ['Doctor ID',data.doctor_id],
                    ['Created by user ID',data.created_by],
                ]);
            }else{
                const fields=Object.entries(data).filter(([key])=>!/^\d+$/.test(key)).map(([key,value])=>[
                    key.replaceAll('_',' ').replace(/\b\w/g,(letter)=>letter.toUpperCase()),value,
                ]);
                appendSection(`${configs[pageType].title} information`,fields);
            }
            shell.append(content); dialog.append(shell);
            dialog.addEventListener('click',(event)=>{if(event.target===dialog)dialog.close();});
            dialog.addEventListener('close',()=>dialog.remove()); document.body.append(dialog); dialog.showModal(); close.focus();
        } catch(error) { showFeedback(error.message,'error'); }
    };

    const onTableClick = async (event) => {
        const button=event.target.closest('[data-row-action]');
        if(!button) return;
        const id=Number(button.dataset.recordId);
        const idKey=({patients:'patient_id',appointments:'appointment_id',users:'user_id',doctors:'doctor_id',specializations:'specialization_id',schedules:button.dataset.rowAction==='edit'?'doctor_id':'schedule_id','medical-records':'record_id'})[pageType];
        const row=pageType==='schedules'&&button.dataset.rowAction!=='edit'
            ? records.flatMap((doctor)=>doctor.schedules||[]).find((item)=>Number(item.schedule_id)===id)
            : records.find((item)=>Number(item[idKey])===id);
        if(!row) return;
        switch(button.dataset.rowAction) {
            case 'details': await openDetails(row); break;
            case 'edit': await openForm(row); break;
            case 'cancel': await updateStatus(row,'Cancelled'); break;
            case 'no-show': await updateStatus(row,'No-show'); break;
            case 'activate': await updateStatus(row,'Active'); break;
            case 'deactivate': await updateStatus(row,'Inactive'); break;
            case 'record': await openForm(null,{appointment_id:row.appointment_id,formType:'medical-records'}); break;
        }
    };

    const loadDashboard = async () => {
        try {
            const summary=(await request('dashboard')).data || {};
            const appointmentPayload=await request('appointments');
            const appointments=asArray(appointmentPayload);
            const today=localToday();
            const stats={};
            if (role==='Admin') Object.assign(stats,summary);
            else if (role==='Receptionist') {
                stats.patients_total=summary.patients_total || 0;
                stats.appointments_today=summary.appointments_today || 0;
                stats.doctors_active=summary.doctors_active || 0;
                stats.appointments_upcoming=summary.appointments_upcoming || 0;
                page.querySelector('[data-stat="doctors_active"]')?.closest('.metric-card')?.querySelector('[data-stat-label]')?.replaceChildren('Doctors with appointments');
            } else {
                stats.appointments_today=summary.appointments_today || 0;
                stats.appointments_upcoming=summary.appointments_upcoming || 0;
                stats.patients_total=appointments.length;
                stats.doctors_active=0;
            }
            page.querySelectorAll('[data-stat]').forEach((node)=>{
                const key=node.dataset.stat; const card=node.closest('.metric-card');
                if (stats[key] === undefined || (role==='Doctor' && ['patients_total','doctors_active'].includes(key))) { if(card) card.hidden=true; return; }
                if(card) card.hidden=false;
                node.textContent=String(stats[key]);
            });
            const recent=page.querySelector('[data-recent-appointments]');
            if(recent){
                recent.replaceChildren();
                const upcoming=appointments.filter((item)=>item.status==='Scheduled'&&item.appointment_date>=today).slice(0,6);
                upcoming.forEach((item)=>{
                    const tr=document.createElement('tr');
                    [item.patient_name,item.doctor_name,`${item.appointment_date} ${formatAppointmentTime(item.appointment_time)}`].forEach((value)=>{const td=document.createElement('td');td.textContent=String(value||'');tr.append(td);});
                    const td=document.createElement('td');td.append(statusPill(item.status));tr.append(td);recent.append(tr);
                });
                if(!upcoming.length){const tr=document.createElement('tr');const td=document.createElement('td');td.colSpan=4;td.className='text-center';td.textContent='No upcoming appointments.';tr.append(td);recent.append(tr);}
            }
        } catch(error){ showFeedback(error.message,'error'); }
    };

    if (form) form.addEventListener('submit',saveRecord);
    page.querySelector('[data-open-form]')?.addEventListener('click',()=>openForm());
    page.querySelector('[data-refresh]')?.addEventListener('click',()=>loadManagement());
    page.querySelector('[data-search-submit]')?.addEventListener('click',()=>loadManagement());
    page.querySelectorAll('[data-report-preset]').forEach((button)=>button.addEventListener('click',()=>{
        const preset=button.dataset.reportPreset;
        if(preset==='custom'){reportSetPresetActive('custom');return;}
        reportSetRange(preset);loadReports();
    }));
    page.querySelector('[data-report-apply]')?.addEventListener('click',()=>{reportSetPresetActive('custom');loadReports();});
    page.querySelector('[data-report-reset]')?.addEventListener('click',()=>{reportSetRange('today');loadReports();});
    page.querySelector('[data-report-granularity]')?.addEventListener('change',()=>loadReports());
    page.querySelector('[data-report-csv]')?.addEventListener('click',exportReportCsv);
    page.querySelector('[data-report-pdf]')?.addEventListener('click',exportReportPdf);
    page.querySelector('[data-export-table]')?.addEventListener('click',()=>{
        const output=pageType==='schedules'
            ? [['Doctor','Available day','Start time','End time','Status'],...records.flatMap((doctor)=>(doctor.schedules||[]).map((schedule)=>[doctor.full_name,dayName(schedule.available_day),formatAppointmentTime(schedule.start_time),formatAppointmentTime(schedule.end_time),schedule.status]))]
            : [configs[pageType].columns.map(([,label])=>label),...records.map((row)=>configs[pageType].columns.map(([key])=>row[key]??''))];
        const csvCell=(value)=>{let text=String(value??'');if(/^[\t\r ]*[=+\-@]/.test(text))text=`'${text}`;return `"${text.replaceAll('"','""')}"`;};
        const csv=output.map((row)=>row.map(csvCell).join(',')).join('\r\n');
        const url=URL.createObjectURL(new Blob([csv],{type:'text/csv;charset=utf-8'}));
        const link=document.createElement('a');link.href=url;link.download=`cliniccare-${pageType}.csv`;link.click();
        setTimeout(()=>URL.revokeObjectURL(url),1000);
    });
    page.querySelector('[data-table-region]')?.addEventListener('click',onTableClick);
    searchInput?.addEventListener('input',()=>{clearTimeout(searchTimer);searchTimer=setTimeout(()=>loadManagement(),300);});
    if (globalSearch) {
        globalSearch.addEventListener('input',()=>{
            if (searchInput) { searchInput.value=globalSearch.value; clearTimeout(searchTimer); searchTimer=setTimeout(()=>loadManagement(),300); }
            else {
                const query=globalSearch.value.trim().toLocaleLowerCase();
                document.querySelectorAll('.sidebar-link').forEach((link)=>{link.hidden=Boolean(query)&&!link.textContent.toLocaleLowerCase().includes(query);});
            }
        });
        searchInput?.addEventListener('input',()=>{if(globalSearch)globalSearch.value=searchInput.value;});
    }
    statusFilter?.addEventListener('change',()=>{clearAppointmentQuickFilter();loadManagement();});
    dateFilter?.addEventListener('change',()=>{clearAppointmentQuickFilter();loadManagement();});
    if (pageType==='dashboard') loadDashboard();
    else if(pageType==='reports'){reportSetRange('today');loadReports();}
    else loadManagement();
    if(new URLSearchParams(location.search).get('new')==='1') page.querySelector('[data-open-form]')?.click();

    document.querySelector('[data-sidebar-toggle]')?.addEventListener('click',(event)=>{
        const button=event.currentTarget; const open=document.body.classList.toggle('sidebar-open'); button.setAttribute('aria-expanded',String(open));
    });
    document.addEventListener('pointerdown',(event)=>{if(!document.body.classList.contains('sidebar-open'))return;if(event.target.closest('#admin-sidebar')||event.target.closest('[data-sidebar-toggle]'))return;document.body.classList.remove('sidebar-open');});
    document.addEventListener('keydown',(event)=>{if(event.key==='Escape')document.body.classList.remove('sidebar-open');});
})();
