let csYear     = new Date().getFullYear();
let csMonth    = new Date().getMonth() + 1;
let csSelected = null;
let csData     = {};
let editingId  = null;
let editCoachId = null;

const MONTH_NAMES = [
    'January','February','March','April','May','June',
    'July','August','September','October','November','December'
];

const STATUS_CLASS = {
    'On Leave'     : 'cc-onleave',
    'Sick'         : 'cc-sick',
    'Off Day'      : 'cc-offday',
    'Custom Hours' : 'cc-custom',
};

const STATUS_BADGE_STYLE = {
    'On Leave'     : 'background:#fef3c7;color:#92400e;',
    'Sick'         : 'background:#fee2e2;color:#991b1b;',
    'Off Day'      : 'background:#f1f5f9;color:#475569;',
    'Custom Hours' : 'background:#ede9fe;color:#5b21b6;',
};

/* ── Month load ──────────────────────────────── */
function loadMonth() {
    const filterCoach = document.getElementById('cs-filter-coach').value;

    fetch(`${AJAX_URL}?action=month_overview&year=${csYear}&month=${csMonth}`)
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;

            if (filterCoach !== '0') {
                const filtered = {};
                Object.keys(data.data).forEach(date => {
                    const rows = data.data[date].filter(r => String(r.coach_id) === filterCoach);
                    if (rows.length) filtered[date] = rows;
                });
                csData = filtered;
            } else {
                csData = data.data;
            }

            renderCalendar();
        });
}

function changeMonth(dir) {
    csMonth += dir;
    if (csMonth > 12) { csMonth = 1;  csYear++; }
    if (csMonth < 1)  { csMonth = 12; csYear--; }
    loadMonth();
}

/* ── Calendar render ─────────────────────────── */
function renderCalendar() {
    document.getElementById('cs-month-label').textContent = `${MONTH_NAMES[csMonth - 1]} ${csYear}`;

    const firstDay    = new Date(csYear, csMonth - 1, 1).getDay();
    const daysInMonth = new Date(csYear, csMonth, 0).getDate();
    const todayStr    = new Date().toISOString().slice(0, 10);

    let html = '';

    for (let i = 0; i < firstDay; i++) {
        html += '<div class="cs-day-cell empty"></div>';
    }

    for (let d = 1; d <= daysInMonth; d++) {
        const dateStr  = `${csYear}-${String(csMonth).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
        const records  = csData[dateStr] || [];
        const isToday  = dateStr === todayStr;

        let cellClass = 'cs-day-cell';
        if (isToday) cellClass += ' today';

        const uniqueCoaches = {};
        records.forEach(r => {
            if (!uniqueCoaches[r.coach_id]) uniqueCoaches[r.coach_id] = r;
        });

        const coachList = Object.values(uniqueCoaches);
        const shown     = coachList.slice(0, 3);
        const extra     = coachList.length - shown.length;

        const chipsHtml = shown.map(r => {
            const img = r.profile_img
                ? `../../Pictures/Admin_Module/coaches/${r.profile_img}`
                : '../../Pictures/Admin_Module/coaches/default.png';
            const sc  = STATUS_CLASS[r.status] || 'cc-offday';
            return `<div class="cs-coach-chip ${sc}">
                        <img src="${img}" onerror="this.src='../../Pictures/Admin_Module/coaches/default.png'" alt="">
                        ${r.coach_name.split(' ')[0]}
                    </div>`;
        }).join('');

        const moreHtml = extra > 0 ? `<div class="cs-more-tag">+${extra} more</div>` : '';

        html += `<div class="${cellClass}" onclick="openDayDetail('${dateStr}')">
                    <div class="cs-day-num">${d}</div>
                    ${chipsHtml}
                    ${moreHtml}
                 </div>`;
    }

    document.getElementById('cs-cal-grid').innerHTML = html;
}

/* ── Day Detail Modal ────────────────────────── */
function openDayDetail(dateStr) {
    csSelected  = dateStr;
    editingId   = null;
    editCoachId = null;

    const d = new Date(dateStr + 'T00:00:00');
    document.getElementById('dd-date-label').textContent =
        d.toLocaleDateString('en-MY', { weekday:'long', day:'numeric', month:'long', year:'numeric' });

    const dayData  = csData[dateStr] || [];
    const unique   = {};
    dayData.forEach(r => { unique[r.coach_id] = true; });
    const cnt = Object.keys(unique).length;

    document.getElementById('dd-header-sub').textContent =
        cnt > 0 ? `${cnt} coach${cnt > 1 ? 'es' : ''} unavailable` : 'All coaches available';

    showViewPanel();
    document.getElementById('dayDetailModal').style.display = 'flex';
    loadDayDetail(dateStr);
}

function closeDayDetail() {
    document.getElementById('dayDetailModal').style.display = 'none';
    csSelected  = null;
    editingId   = null;
    editCoachId = null;
}

document.getElementById('dayDetailModal').addEventListener('click', function (e) {
    if (e.target === this) closeDayDetail();
});

function showViewPanel() {
    document.getElementById('dd-view-panel').style.display = 'block';
    document.getElementById('dd-edit-panel').style.display = 'none';
    document.getElementById('dd-add-panel').style.display  = 'none';
    editingId   = null;
    editCoachId = null;
}

function showAddPanel() {
    document.getElementById('dd-view-panel').style.display = 'none';
    document.getElementById('dd-edit-panel').style.display = 'none';
    document.getElementById('dd-add-panel').style.display  = 'block';

    document.getElementById('dd-coach-search').value               = '';
    document.getElementById('dd-coach-id').value                   = '';
    document.getElementById('dd-add-status').value                 = 'On Leave';
    document.getElementById('dd-add-reason').value                 = '';
    document.getElementById('dd-add-start').value                  = '';
    document.getElementById('dd-add-end').value                    = '';
    document.getElementById('dd-add-custom-hours').style.display   = 'none';
    document.getElementById('dd-add-conflict-warn').style.display  = 'none';

    const list = document.getElementById('dd-coach-list');
    list.querySelectorAll('.cs-search-item').forEach(el => {
        el.style.display = 'block';
        el.classList.remove('selected');
    });
    list.classList.remove('open');
}

function showEditPanel(id, coachId, coachName, status, startTime, endTime, reason) {
    editingId   = id;
    editCoachId = coachId;

    document.getElementById('dd-view-panel').style.display = 'none';
    document.getElementById('dd-add-panel').style.display  = 'none';
    document.getElementById('dd-edit-panel').style.display = 'block';

    document.getElementById('dd-edit-banner-label').textContent = `Editing: ${coachName}`;
    document.getElementById('dd-edit-status').value  = status;
    document.getElementById('dd-edit-reason').value  = reason || '';
    document.getElementById('dd-edit-start').value   = startTime ? startTime.slice(0, 5) : '';
    document.getElementById('dd-edit-end').value     = endTime   ? endTime.slice(0, 5)   : '';
    document.getElementById('dd-edit-conflict-warn').style.display = 'none';

    toggleDdCustomHours();
}

function loadDayDetail(dateStr) {
    fetch(`${AJAX_URL}?action=day_detail&date=${dateStr}`)
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;
            renderDayAvails(data.avails);
        });
}

function renderDayAvails(avails) {
    const list = document.getElementById('dd-avail-list');

    if (avails.length === 0) {
        list.innerHTML = '<div class="dd-no-items">No unavailabilities set for this day.</div>';
        return;
    }

    list.innerHTML = avails.map(a => {
        const img = a.profile_img
            ? `../../Pictures/Admin_Module/coaches/${a.profile_img}`
            : '../../Pictures/Admin_Module/coaches/default.png';

        const label = a.start_time
            ? `${a.status} · ${a.start_time.slice(0,5)}–${a.end_time.slice(0,5)}`
            : a.status;

        const bs     = STATUS_BADGE_STYLE[a.status] || '';
        const reason = a.reason ? `<div class="dd-avail-reason">${a.reason}</div>` : '';
        const st     = a.start_time || '';
        const et     = a.end_time   || '';
        const rsn    = (a.reason    || '').replace(/\\/g,'\\\\').replace(/'/g,"\\'");
        const nm     = a.coach_name.replace(/\\/g,'\\\\').replace(/'/g,"\\'");

        return `<div class="dd-avail-row">
                    <img class="dd-coach-avatar" src="${img}"
                         onerror="this.src='../../Pictures/Admin_Module/coaches/default.png'" alt="">
                    <div class="dd-avail-info">
                        <div class="dd-avail-name">${a.coach_name}</div>
                        <span class="dd-avail-badge" style="${bs}">${label}</span>
                        ${reason}
                    </div>
                    <div class="dd-avail-actions">
                        <button class="dd-btn-edit"
                            onclick="showEditPanel(${a.id},${a.coach_id},'${nm}','${a.status}','${st}','${et}','${rsn}')">
                            <i class="fas fa-pen"></i> Edit
                        </button>
                        <button class="dd-btn-del" onclick="deleteDdRecord(${a.id})">
                            <i class="fas fa-xmark"></i>
                        </button>
                    </div>
                </div>`;
    }).join('');
}

/* ── Coach search for Add panel ──────────────── */
document.getElementById('dd-coach-search').addEventListener('focus', function () {
    document.getElementById('dd-coach-list').classList.add('open');
});

document.addEventListener('click', function (e) {
    const wrap = document.querySelector('.cs-search-wrap');
    if (wrap && !wrap.contains(e.target)) {
        document.getElementById('dd-coach-list').classList.remove('open');
    }
});

function filterCoachSearch() {
    const q    = document.getElementById('dd-coach-search').value.toLowerCase();
    const list = document.getElementById('dd-coach-list');
    let   any  = false;

    list.querySelectorAll('.cs-search-item').forEach(el => {
        const match = el.textContent.toLowerCase().includes(q);
        el.style.display = match ? 'block' : 'none';
        if (match) any = true;
    });

    list.classList.toggle('open', any || q === '');
}

function selectCoach(el) {
    document.getElementById('dd-coach-id').value     = el.dataset.id;
    document.getElementById('dd-coach-search').value = el.textContent.trim();
    document.getElementById('dd-coach-list').classList.remove('open');

    el.closest('.cs-search-list').querySelectorAll('.cs-search-item')
        .forEach(i => i.classList.remove('selected'));
    el.classList.add('selected');
}

/* ── Custom hours toggles ────────────────────── */
function toggleDdCustomHours() {
    const val = document.getElementById('dd-edit-status').value;
    document.getElementById('dd-edit-custom-hours').style.display =
        val === 'Custom Hours' ? 'block' : 'none';
}

function toggleAddCustomHours() {
    const val = document.getElementById('dd-add-status').value;
    document.getElementById('dd-add-custom-hours').style.display =
        val === 'Custom Hours' ? 'block' : 'none';
}

/* ── Save edit ───────────────────────────────── */
function saveEdit() {
    if (!editingId || !editCoachId || !csSelected) return;

    const status    = document.getElementById('dd-edit-status').value;
    const reason    = document.getElementById('dd-edit-reason').value.trim();
    const startTime = document.getElementById('dd-edit-start').value;
    const endTime   = document.getElementById('dd-edit-end').value;

    if (status === 'Custom Hours' && (!startTime || !endTime)) {
        alert('Please set the unavailable time range.');
        return;
    }

    /* delete existing, then re-create with new values */
    const delBody = new FormData();
    delBody.append('action', 'delete');
    delBody.append('id',     editingId);

    fetch(AJAX_URL, { method: 'POST', body: delBody })
        .then(() => {
            const body = new FormData();
            body.append('action',   'save');
            body.append('coach_id', editCoachId);
            body.append('date',     csSelected);
            body.append('status',   status);
            body.append('reason',   reason);
            if (status === 'Custom Hours') {
                body.append('start_time', startTime);
                body.append('end_time',   endTime);
            }
            return fetch(AJAX_URL, { method: 'POST', body });
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success) { alert(data.message || 'Save failed'); return; }

            if (data.conflict) {
                document.getElementById('dd-edit-conflict-msg').textContent =
                    `Warning: ${data.conflict_count} booking(s) exist for this coach on this day.`;
                document.getElementById('dd-edit-conflict-warn').style.display = 'flex';
            }

            loadMonth();
            loadDayDetail(csSelected);
            showViewPanel();
        });
}

/* ── Save new ────────────────────────────────── */
function saveDayDetail() {
    if (!csSelected) return;

    const coachId   = document.getElementById('dd-coach-id').value;
    const status    = document.getElementById('dd-add-status').value;
    const reason    = document.getElementById('dd-add-reason').value.trim();
    const startTime = document.getElementById('dd-add-start').value;
    const endTime   = document.getElementById('dd-add-end').value;

    if (!coachId) { alert('Please select a coach.'); return; }

    if (status === 'Custom Hours' && (!startTime || !endTime)) {
        alert('Please set the unavailable time range.');
        return;
    }

    const body = new FormData();
    body.append('action',   'save');
    body.append('coach_id', coachId);
    body.append('date',     csSelected);
    body.append('status',   status);
    body.append('reason',   reason);
    if (status === 'Custom Hours') {
        body.append('start_time', startTime);
        body.append('end_time',   endTime);
    }

    fetch(AJAX_URL, { method: 'POST', body })
        .then(r => r.json())
        .then(data => {
            if (!data.success) { alert(data.message || 'Save failed'); return; }

            if (data.conflict) {
                document.getElementById('dd-add-conflict-msg').textContent =
                    `Warning: ${data.conflict_count} booking(s) exist for this coach on this day.`;
                document.getElementById('dd-add-conflict-warn').style.display = 'flex';
            }

            loadMonth();
            loadDayDetail(csSelected);
            showViewPanel();
        });
}

/* ── Delete ──────────────────────────────────── */
function deleteDdRecord(id) {
    if (!confirm('Remove this schedule entry?')) return;

    const body = new FormData();
    body.append('action', 'delete');
    body.append('id',     id);

    fetch(AJAX_URL, { method: 'POST', body })
        .then(r => r.json())
        .then(data => {
            if (!data.success) { alert(data.message || 'Delete failed'); return; }
            loadMonth();
            loadDayDetail(csSelected);
        });
}

/* ── History Modal ───────────────────────────── */
function openHistoryModal() {
    document.getElementById('historyModal').style.display = 'flex';
    loadHistory();
}

function closeHistoryModal() {
    document.getElementById('historyModal').style.display = 'none';
}

document.getElementById('historyModal').addEventListener('click', function (e) {
    if (e.target === this) closeHistoryModal();
});

function loadHistory() {
    const coachId = document.getElementById('hist-coach-filter').value;
    const from    = document.getElementById('hist-from').value;
    const to      = document.getElementById('hist-to').value;

    fetch(`${AJAX_URL}?action=history&coach_id=${coachId}&from=${from}&to=${to}`)
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;
            renderHistory(data.logs);
        });
}

function renderHistory(logs) {
    const list = document.getElementById('hist-list');

    if (logs.length === 0) {
        list.innerHTML = '<div class="dd-no-items">No records found.</div>';
        return;
    }

    const ACTION_ICON = { 'created':'fa-plus', 'updated':'fa-pen', 'deleted':'fa-trash' };

    list.innerHTML = logs.map(l => {
        const icon   = ACTION_ICON[l.action] || 'fa-circle';
        const status = l.action === 'deleted' ? l.old_status : l.new_status;
        const date   = new Date(l.date + 'T00:00:00').toLocaleDateString('en-MY', { day:'2-digit', month:'short', year:'numeric' });
        const time   = new Date(l.created_at).toLocaleString('en-MY', { day:'2-digit', month:'short', hour:'2-digit', minute:'2-digit' });

        let detail = `${l.coach_name} — ${date}: `;
        if (l.action === 'created') detail += `set to <strong>${status}</strong>`;
        if (l.action === 'updated') detail += `changed from <strong>${l.old_status}</strong> → <strong>${l.new_status}</strong>`;
        if (l.action === 'deleted') detail += `removed <strong>${status}</strong>`;

        const by = l.changed_by_name ? ` by ${l.changed_by_name} (${l.changed_by_role})` : '';

        return `<div class="hist-log-item">
                    <div class="hist-icon ${l.action}"><i class="fas ${icon}"></i></div>
                    <div class="hist-body">
                        <div class="hist-meta">${detail}</div>
                        <div class="hist-sub">${by}${l.reason ? ' · ' + l.reason : ''}</div>
                    </div>
                    <div class="hist-time">${time}</div>
                </div>`;
    }).join('');
}

/* ── Init ────────────────────────────────────── */
loadMonth();
