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

    const configs = {
        patients: {
            resource: 'patients', title: 'patient', count: 'patients',
            columns: [['full_name', 'Patient'], ['patient_id', 'ID'], ['birth_date', 'Birth date'], ['gender', 'Gender'], ['contact_number', 'Contact'], ['address', 'Address'], ['status', 'Status']],
            fields: [
                {name:'full_name',label:'Full name',required:true,max:120},
                {name:'birth_date',label:'Birth date',type:'date',required:true},
                {name:'gender',label:'Gender',required:true,max:30,help:'Enter the value approved by the clinic.'},
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
            resource: 'schedules', title: 'schedule', count: 'schedules',
            columns: [['doctor_name','Doctor'],['day_name','Available day'],['start_time','Start'],['end_time','End'],['status','Status']],
            fields: [
                {name:'doctor_id',label:'Doctor',type:'select',source:'doctors',value:'doctor_id',text:'full_name',required:true,createOnly:true},
                {name:'available_day',label:'Available day',type:'select',options:[['1','Monday'],['2','Tuesday'],['3','Wednesday'],['4','Thursday'],['5','Friday'],['6','Saturday'],['7','Sunday']],required:true},
                {name:'start_time',label:'Start time',type:'time',required:true},
                {name:'end_time',label:'End time',type:'time',required:true},
                {name:'status',label:'Status',type:'select',options:['Active','Inactive']},
            ],
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
        reports: {
            resource: 'reports', title: 'report', count: 'report rows',
            columns: [['report','Report'],['summary','Summary'],['period','Period']],
            fields: [],
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
    const labelForAppointment = (row) => `#${row.appointment_id} - ${row.patient_name || 'Patient'} / ${row.appointment_date} ${String(row.appointment_time || '').slice(0,5)} (${row.status})`;
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
    const renderTable = (rows, cfg) => {
        if (!thead || !tbody) return;
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
                if (key === 'appointment_time' || key === 'start_time' || key === 'end_time') value=String(value).slice(0,5);
                if (key === 'consultation_date') value=String(value).replace('T',' ');
                if (key === 'status' || key === 'account_status') td.append(statusPill(value)); else td.textContent=String(value);
                tr.append(td);
            });
            if (canEdit) tr.append(rowActions(row));
            tbody.append(tr);
        });
        if (pageType === 'reports' && role === 'Receptionist' && Number(records.find((row) => row.report === 'All appointments')?.summary ?? 0) === 0) {
            const tr=document.createElement('tr'); tr.className='empty-table-row report-empty-state';
            const td=document.createElement('td'); td.colSpan=cfg.columns.length; td.textContent='No appointments match this date range.';
            tr.append(td); tbody.append(tr);
        }
    };

    const reportRows = (payload) => {
        const rows = [];
        (payload.by_status || []).forEach((row) => rows.push({report:`Appointments - ${row.status}`,summary:row.total,period:`${payload.from} to ${payload.to}`}));
        (payload.by_doctor || []).forEach((row) => rows.push({report:`${row.doctor_name}`,summary:`${row.total} appointments`,period:`${payload.from} to ${payload.to}`}));
        rows.unshift({report:'All patients',summary:payload.patients_total ?? 0,period:'Current total'});
        rows.unshift({report:'All appointments',summary:payload.appointments_total ?? 0,period:`${payload.from} to ${payload.to}`} );
        return rows;
    };

    const loadManagement = async () => {
        const cfg = configs[pageType];
        if (!cfg) return;
        if (countLabel) countLabel.textContent='Loading records…';
        try {
            let payload;
            if (pageType === 'reports') {
                const from = page.querySelector('[data-report-from]')?.value;
                const to = page.querySelector('[data-report-to]')?.value;
                payload = await request('reports',{params:{from,to}});
                records = reportRows(payload);
                cfg.columns=[['report','Report'],['summary','Summary'],['period','Period']];
            } else {
                const params={};
                if (['patients','appointments'].includes(pageType) && searchInput?.value.trim()) params.q=searchInput.value.trim();
                if (pageType === 'appointments' && statusFilter?.value) params.status=statusFilter.value;
                if (pageType === 'appointments' && dateFilter?.value) params.date=dateFilter.value;
                payload = await request(cfg.resource,{params});
                records=asArray(payload);
            }
            if (pageType === 'schedules') {
                const doctors=asArray(await request('doctors'));
                const names=new Map(doctors.map((doctor)=>[Number(doctor.doctor_id),doctor.full_name]));
                records=records.map((row)=>({...row,doctor_name:names.get(Number(row.doctor_id)) || `Doctor ${row.doctor_id}`}));
            }
            if (pageType === 'appointments' && role === 'Doctor') {
                const existingRecords=new Set(asArray(await request('medical-records')).map((record)=>Number(record.appointment_id)));
                records=records.map((row)=>({...row,has_medical_record:existingRecords.has(Number(row.appointment_id))}));
            }
            if (searchInput?.value.trim() && !['patients','appointments'].includes(pageType)) {
                const query=searchInput.value.trim().toLocaleLowerCase();
                records=records.filter((row)=>Object.values(row).join(' ').toLocaleLowerCase().includes(query));
            }
            renderTable(records,cfg);
            if (countLabel) countLabel.textContent=`${records.length} ${cfg.count}`;
        } catch (error) {
            if (countLabel) countLabel.textContent='Could not load records';
            showFeedback(error.message,'error');
            if (tbody) { tbody.replaceChildren(); const tr=document.createElement('tr'); const td=document.createElement('td'); td.colSpan=(cfg.columns?.length||1)+1; td.textContent=error.message; tr.append(td); tbody.append(tr); }
        }
    };

    const makeField = async (field, data, mode) => {
        const wrapper=document.createElement('div'); wrapper.className='col-12 col-md-6 mb-3';
        const id=`field-${field.name}-${Math.random().toString(36).slice(2)}`;
        const label=document.createElement('label'); label.className='form-label'; label.htmlFor=id; label.textContent=field.label; wrapper.append(label);
        let input;
        if (field.type === 'textarea') { input=document.createElement('textarea'); input.rows=3; }
        else if (field.type === 'select') {
            input=document.createElement('select'); input.className='form-select';
            const placeholder=document.createElement('option'); placeholder.value=''; placeholder.textContent='Select an option'; input.append(placeholder);
            const options=await loadOptions(field);
            options.forEach(([value,text])=>{ const option=document.createElement('option'); option.value=String(value); option.textContent=String(text); input.append(option); });
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
        if (mode==='edit' && field.createOnly) { input.disabled=true; input.required=false; }
        if (field.help) { const help=document.createElement('small'); help.className='form-text text-secondary'; help.textContent=field.help; wrapper.append(input,help); }
        else wrapper.append(input);
        return wrapper;
    };

    const openForm = async (row = null, preset = {}) => {
        formEntityType=preset.formType || pageType;
        const cfg=configs[formEntityType];
        if (!cfg || !form) return;
        editingRecord=row;
        form.replaceChildren(); form.classList.remove('d-none');
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
        const idKey=({patients:'patient_id',appointments:'appointment_id',users:'user_id',doctors:'doctor_id',specializations:'specialization_id',schedules:'schedule_id','medical-records':'record_id'})[pageType];
        const row=records.find((item)=>Number(item[idKey])===id);
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
                    [item.patient_name,item.doctor_name,`${item.appointment_date} ${String(item.appointment_time||'').slice(0,5)}`].forEach((value)=>{const td=document.createElement('td');td.textContent=String(value||'');tr.append(td);});
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
    page.querySelector('[data-report-refresh]')?.addEventListener('click',()=>loadManagement());
    page.querySelector('[data-export-table]')?.addEventListener('click',()=>{
        const output=[configs[pageType].columns.map(([,label])=>label),...records.map((row)=>configs[pageType].columns.map(([key])=>row[key]??''))];
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
    statusFilter?.addEventListener('change',()=>loadManagement());
    dateFilter?.addEventListener('change',()=>loadManagement());
    if (pageType==='dashboard') loadDashboard();
    else loadManagement();
    if(new URLSearchParams(location.search).get('new')==='1') page.querySelector('[data-open-form]')?.click();

    document.querySelector('[data-sidebar-toggle]')?.addEventListener('click',(event)=>{
        const button=event.currentTarget; const open=document.body.classList.toggle('sidebar-open'); button.setAttribute('aria-expanded',String(open));
    });
    document.addEventListener('pointerdown',(event)=>{if(!document.body.classList.contains('sidebar-open'))return;if(event.target.closest('#admin-sidebar')||event.target.closest('[data-sidebar-toggle]'))return;document.body.classList.remove('sidebar-open');});
    document.addEventListener('keydown',(event)=>{if(event.key==='Escape')document.body.classList.remove('sidebar-open');});
})();
