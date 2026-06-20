<?= $this->extend('layouts/main') ?>

<?= $this->section('head') ?>
<style>
.notif-page-hdr {
    display:flex; align-items:center; justify-content:space-between;
    margin-bottom:1.2rem; flex-wrap:wrap; gap:.5rem;
}
.notif-page-hdr h1 { font-size:1.4rem; font-weight:700; margin:0; }
.notif-mark-all-btn {
    background:none; border:1.5px solid #b8960c; color:#b8960c;
    padding:6px 16px; border-radius:20px; font-size:.83rem;
    font-weight:600; cursor:pointer; transition:background .15s, color .15s;
}
.notif-mark-all-btn:hover { background:#b8960c; color:#fff; }
.notif-mark-all-btn:disabled { opacity:.45; cursor:default; }

.notif-section-label {
    font-size:.72rem; font-weight:700; letter-spacing:.06em;
    text-transform:uppercase; color:#999; padding:0 0 6px;
    margin:1.2rem 0 .3rem;
}
.notif-section-label:first-child { margin-top:0; }

.notif-item {
    display:flex; align-items:flex-start; gap:14px;
    padding:14px 18px; border-radius:10px; cursor:pointer;
    transition:background .12s; margin-bottom:4px;
    background:#fff; border:1px solid #eee;
    text-decoration:none; color:inherit;
}
.notif-item:hover { background:#fdf8e1; border-color:#e8d97a; }
.notif-item.unread { background:#fffcec; border-color:#e8d97a; }
.notif-item.unread:hover { background:#fdf3c0; }

.notif-type-icon {
    width:40px; height:40px; border-radius:50%; flex-shrink:0;
    display:flex; align-items:center; justify-content:center;
    background:#f5f0dc; color:#b8960c;
}
.notif-type-icon.type-emergency   { background:#fde8e8; color:#c62828; }
.notif-type-icon.type-approved    { background:#e8f5e9; color:#2e7d32; }
.notif-type-icon.type-message     { background:#e3f2fd; color:#1565c0; }
.notif-type-icon.type-approval    { background:#fff3e0; color:#e65100; }
.notif-type-icon .material-symbols-outlined { font-size:20px; }

.notif-body-wrap { flex:1; min-width:0; }
.notif-title     { font-weight:600; font-size:.9rem; color:#1a1a1a; line-height:1.3; }
.notif-body-text { font-size:.82rem; color:#555; margin-top:3px; line-height:1.4; }
.notif-meta      { display:flex; align-items:center; gap:8px; margin-top:5px; }
.notif-time      { font-size:.75rem; color:#aaa; }
.notif-type-pill {
    font-size:.68rem; font-weight:600; letter-spacing:.04em;
    padding:2px 8px; border-radius:10px;
    background:#f0ecd8; color:#b8960c;
}
.notif-type-pill.type-emergency { background:#fde8e8; color:#c62828; }
.notif-type-pill.type-approved  { background:#e8f5e9; color:#2e7d32; }
.notif-type-pill.type-message   { background:#e3f2fd; color:#1565c0; }
.notif-type-pill.type-approval  { background:#fff3e0; color:#e65100; }

.notif-unread-dot {
    width:9px; height:9px; border-radius:50%;
    background:#b8960c; flex-shrink:0; margin-top:6px;
}
.notif-empty-state {
    text-align:center; padding:60px 20px; color:#aaa;
}
.notif-empty-state .material-symbols-outlined { font-size:48px; display:block; margin-bottom:12px; opacity:.4; }
.notif-empty-state p { font-size:.9rem; }
#notifLoading { text-align:center; padding:40px; color:#aaa; font-size:.9rem; }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="notif-page-hdr">
    <h1>Notifications</h1>
    <button class="notif-mark-all-btn" id="pageMarkAll" disabled>Mark all as read</button>
</div>

<div id="notifLoading">Loading notifications…</div>
<div id="notifContent" style="display:none"></div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
    var _base = '<?= base_url() ?>';
    var _data = [];

    var TYPE_ICON  = {
        messageSent:       { icon:'chat_bubble',          cls:'type-message',   label:'Message'    },
        toolRequested:     { icon:'build',                cls:'',               label:'Request'    },
        secondaryApproval: { icon:'approval',             cls:'type-approval',  label:'Approval'   },
        requestApproved:   { icon:'check_circle',         cls:'type-approved',  label:'Approved'   },
        projectAssigned:   { icon:'folder_special',       cls:'',               label:'Project'    },
        emergencyCreated:  { icon:'warning',              cls:'type-emergency', label:'Emergency'  },
        progressReported:  { icon:'assignment_turned_in', cls:'',               label:'Progress'   },
    };

    function h(s) {
        var d = document.createElement('div');
        d.appendChild(document.createTextNode(String(s || '')));
        return d.innerHTML;
    }

    function relTime(iso) {
        if (!iso) return '';
        var diff = Math.floor((Date.now() - new Date(iso.replace(' ','T') + '+08:00')) / 1000);
        if (diff <  60)  return 'Just now';
        if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
        if (diff < 86400)return Math.floor(diff / 3600) + 'h ago';
        if (diff < 604800)return Math.floor(diff / 86400) + 'd ago';
        return new Date(iso.replace(' ','T') + '+08:00')
            .toLocaleDateString('en-US', { month:'short', day:'numeric', year:'numeric' });
    }

    function navUrl(n) {
        var m = n.metadata || {};
        switch (n.type) {
            case 'toolRequested':
            case 'secondaryApproval':
            case 'requestApproved':
                return (m.request_id || m.requestId) ? _base + 'requests/' + (m.request_id || m.requestId) : _base + 'requests';
            case 'projectAssigned':
                return (m.project_id || m.projectId) ? _base + 'projects/show/' + (m.project_id || m.projectId) : _base + 'projects';
            case 'emergencyCreated':
            case 'progressReported':
                return (m.project_id || m.projectId) ? _base + 'projects/show/' + (m.project_id || m.projectId) : _base + 'projects';
            case 'messageSent':
                return (m.chat_id || m.chatId) ? _base + 'messages/' + (m.chat_id || m.chatId) : _base + 'messages';
            default:
                return '';
        }
    }

    function markRead(id, cb) {
        fetch(_base + 'notifications/mark-read', {
            method: 'POST', credentials: 'same-origin',
            headers: { 'Content-Type':'application/json', 'X-Requested-With':'XMLHttpRequest' },
            body: JSON.stringify({ id: id }),
        }).then(cb || function(){}).catch(function(){});
    }

    function render() {
        var unread = _data.filter(function(n){ return !n.isRead; });
        var read   = _data.filter(function(n){ return  n.isRead; });

        var markAllBtn = document.getElementById('pageMarkAll');
        markAllBtn.disabled = unread.length === 0;

        if (!_data.length) {
            document.getElementById('notifContent').innerHTML =
                '<div class="notif-empty-state">' +
                '<span class="material-symbols-outlined">notifications_off</span>' +
                '<p>You have no notifications yet.</p></div>';
            return;
        }

        var html = '';
        if (unread.length) {
            html += '<div class="notif-section-label">Unread (' + unread.length + ')</div>';
            html += renderItems(unread);
        }
        if (read.length) {
            html += '<div class="notif-section-label">' + (unread.length ? 'Earlier' : 'All Notifications') + '</div>';
            html += renderItems(read);
        }
        document.getElementById('notifContent').innerHTML = html;

        document.querySelectorAll('.notif-item').forEach(function (el) {
            el.addEventListener('click', function () {
                var id     = el.dataset.id;
                var isRead = el.dataset.read === '1';
                var url    = el.dataset.url;

                if (!isRead) {
                    el.classList.remove('unread');
                    el.dataset.read = '1';
                    var dot = el.querySelector('.notif-unread-dot');
                    if (dot) dot.remove();
                    // update local data
                    var item = _data.find(function(n){ return n.id === id; });
                    if (item) item.isRead = true;
                    var remaining = _data.filter(function(n){ return !n.isRead; }).length;
                    document.getElementById('pageMarkAll').disabled = remaining === 0;
                    markRead(id, function() {
                        if (url) window.location.href = url;
                    });
                } else {
                    if (url) window.location.href = url;
                }
            });
        });
    }

    function renderItems(items) {
        return items.map(function (n) {
            var t    = TYPE_ICON[n.type] || { icon:'notifications', cls:'', label:n.type };
            var url  = navUrl(n);
            return '<div class="notif-item' + (n.isRead ? '' : ' unread') + '" ' +
                   'data-id="'   + h(n.id)        + '" ' +
                   'data-read="' + (n.isRead?'1':'0') + '" ' +
                   'data-url="'  + h(url)          + '">' +
              '<div class="notif-type-icon ' + t.cls + '">' +
                '<span class="material-symbols-outlined">' + t.icon + '</span>' +
              '</div>' +
              '<div class="notif-body-wrap">' +
                '<div class="notif-title">'     + h(n.title) + '</div>' +
                '<div class="notif-body-text">' + h(n.body)  + '</div>' +
                '<div class="notif-meta">' +
                  '<span class="notif-time">' + relTime(n.createdAt) + '</span>' +
                  '<span class="notif-type-pill ' + t.cls + '">' + t.label + '</span>' +
                  (url ? '' : '<span class="notif-time" style="font-style:italic">No link</span>') +
                '</div>' +
              '</div>' +
              (n.isRead ? '' : '<div class="notif-unread-dot"></div>') +
            '</div>';
        }).join('');
    }

    function load() {
        fetch(_base + 'notifications/json', { credentials:'same-origin' })
            .then(function(r){ return r.json(); })
            .then(function(d){
                _data = d.notifications || [];
                document.getElementById('notifLoading').style.display = 'none';
                document.getElementById('notifContent').style.display = '';
                render();
            })
            .catch(function(){
                document.getElementById('notifLoading').textContent = 'Failed to load notifications.';
            });
    }

    document.getElementById('pageMarkAll').addEventListener('click', function () {
        this.disabled = true;
        _data.forEach(function(n){ n.isRead = true; });
        render();
        fetch(_base + 'notifications/mark-all-read', {
            method:'POST', credentials:'same-origin',
            headers:{ 'Content-Type':'application/json', 'X-Requested-With':'XMLHttpRequest' },
            body:'{}',
        }).catch(function(){});
    });

    load();
}());
</script>
<?= $this->endSection() ?>
