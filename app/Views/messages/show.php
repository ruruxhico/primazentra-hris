<?= $this->extend('layouts/main') ?>

<?= $this->section('head') ?>
<style>
.msg-show-wrap {
    display:flex; flex-direction:column;
    height:calc(100vh - 120px); min-height:400px;
    background:#fff; border:1px solid #e2ddd4; border-radius:14px;
    box-shadow:0 2px 10px rgba(0,0,0,0.08);
    padding:1.25rem 1.5rem 0;
}

.msg-chat-hdr {
    display:flex; align-items:center; gap:12px;
    padding-bottom:12px; border-bottom:1px solid #eee; flex-shrink:0;
    position:relative;
}
.msg-back-btn {
    background:none; border:none; cursor:pointer; color:#b8960c;
    display:flex; align-items:center; padding:4px;
    border-radius:6px; transition:background .1s;
}
.msg-back-btn:hover { background:#fdf8e1; }
.msg-back-btn .material-symbols-outlined { font-size:22px; }
.msg-chat-title { font-size:1.1rem; font-weight:700; color:#1a1a1a; }

.msg-hdr-info-btn {
    flex-shrink:0; background:none; border:none; cursor:pointer;
    color:#b8960c; padding:4px; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    transition:background .12s;
}
.msg-hdr-info-btn:hover { background:#f0ead8; }
.msg-hdr-info-btn .material-symbols-outlined { font-size:20px; font-variation-settings:'FILL' 1; }

.msg-hdr-pop {
    display:none; position:absolute; right:0; top:calc(100% + 8px);
    background:#fff; border:1px solid #e8d97a; border-radius:10px;
    padding:10px 14px; min-width:180px; max-width:260px;
    box-shadow:0 4px 16px rgba(0,0,0,.12); z-index:200;
    font-size:.88rem; color:#333;
}
.msg-hdr-pop.open { display:block; }
.msg-hdr-pop-title {
    font-weight:700; font-size:.82rem; color:#b8960c;
    text-transform:uppercase; letter-spacing:.04em; margin-bottom:6px;
}
.msg-hdr-pop ul { margin:0; padding:0 0 0 14px; }
.msg-hdr-pop li { margin-bottom:3px; }

.msg-thread {
    flex:1; overflow-y:auto; padding:16px 4px;
    display:flex; flex-direction:column; gap:10px;
}

.msg-bubble-row { display:flex; align-items:flex-end; gap:8px; }
.msg-bubble-row.mine { flex-direction:row-reverse; }

.msg-avatar {
    width:30px; height:30px; border-radius:50%; flex-shrink:0;
    background:#e8e8e8; color:#888; font-size:12px; font-weight:700;
    display:flex; align-items:center; justify-content:center;
    text-transform:uppercase;
}
.msg-bubble-row.mine .msg-avatar { background:#f0ead8; color:#b8960c; }

.msg-bubble-wrap { max-width:72%; display:flex; flex-direction:column; }
.msg-bubble-row.mine .msg-bubble-wrap { align-items:flex-end; }

.msg-sender-label { font-size:.78rem; color:#aaa; margin-bottom:3px; padding:0 4px; }

.msg-bubble {
    padding:9px 14px; border-radius:16px; font-size:.95rem;
    line-height:1.45; word-break:break-word;
    background:#f0f0f0; color:#222; border-bottom-left-radius:4px;
}
.msg-bubble-row.mine .msg-bubble {
    background:#b8960c; color:#fff;
    border-bottom-right-radius:4px; border-bottom-left-radius:16px;
}

.msg-bubble img {
    max-width:240px; max-height:240px; border-radius:10px;
    display:block; cursor:pointer; margin-bottom:4px;
}
.msg-file-link {
    display:flex; align-items:center; gap:8px;
    font-size:.85rem; text-decoration:none;
    color:inherit;
}
.msg-file-link .material-symbols-outlined { font-size:20px; }
.msg-bubble-row.mine .msg-file-link { color:#fff; }

.msg-time { font-size:.75rem; color:#bbb; margin-top:3px; padding:0 4px; }
.msg-bubble-row.mine .msg-time { text-align:right; }

.msg-date-div {
    text-align:center; font-size:.72rem; color:#bbb; margin:6px 0;
    display:flex; align-items:center; gap:8px;
}
.msg-date-div::before, .msg-date-div::after {
    content:''; flex:1; height:1px; background:#eee;
}

.msg-empty-thread {
    flex:1; display:flex; align-items:center; justify-content:center;
    flex-direction:column; gap:8px; color:#ccc;
}
.msg-empty-thread .material-symbols-outlined { font-size:44px; opacity:.4; }
.msg-empty-thread p { font-size:.85rem; }

#threadLoading { text-align:center; padding:40px; color:#aaa; font-size:.9rem; }

/* Compose */
.msg-compose-area { flex-shrink:0; border-top:1px solid #eee; padding-top:10px; padding-bottom:14px; }

/* Multi-file chip row */
.msg-chips-row {
    display:flex; flex-wrap:wrap; gap:6px; padding-bottom:8px;
}
.msg-att-chip {
    display:flex; align-items:center; gap:5px;
    background:#fdf8e1; border:1px solid #e8d97a; border-radius:20px;
    padding:4px 8px 4px 5px; font-size:.77rem; max-width:210px;
}
.att-chip-img {
    width:30px; height:30px; border-radius:5px; object-fit:cover; flex-shrink:0;
}
.att-chip-icon { font-size:20px; color:#b8960c; flex-shrink:0; }
.att-chip-name {
    flex:1; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; color:#555; max-width:130px;
}
.att-chip-rm {
    background:none; border:none; cursor:pointer; color:#c00;
    font-size:17px; line-height:1; padding:0 2px; flex-shrink:0;
}

.msg-compose {
    display:flex; align-items:flex-end; gap:8px;
}
.msg-attach-btn {
    background:none; border:1.5px solid #ddd; color:#888;
    border-radius:50%; width:40px; height:40px; flex-shrink:0;
    display:flex; align-items:center; justify-content:center;
    cursor:pointer; transition:border-color .15s, color .15s;
}
.msg-attach-btn:hover { border-color:#b8960c; color:#b8960c; }
.msg-attach-btn .material-symbols-outlined { font-size:20px; }

.msg-compose textarea {
    flex:1; resize:none; border:1.5px solid #ddd; border-radius:10px;
    padding:10px 12px; font-size:.95rem; outline:none;
    line-height:1.4; max-height:120px; overflow-y:auto;
    transition:border-color .15s; font-family:inherit;
}
.msg-compose textarea:focus { border-color:#b8960c; }

.msg-send-btn {
    background:#b8960c; color:#fff; border:none;
    border-radius:50%; width:40px; height:40px; flex-shrink:0;
    cursor:pointer; display:flex; align-items:center; justify-content:center;
    transition:background .15s;
}
.msg-send-btn:hover { background:#9a7c0a; }
.msg-send-btn:disabled { opacity:.5; cursor:default; }
.msg-send-btn .material-symbols-outlined { font-size:20px; }

/* Lightbox */
#imgLightbox {
    display:none; position:fixed; inset:0; z-index:2000;
    background:rgba(0,0,0,.85); align-items:center; justify-content:center;
    cursor:zoom-out;
}
#imgLightbox.open { display:flex; }
#imgLightbox img { max-width:90vw; max-height:90vh; border-radius:8px; }

/* Toast */
#pzToastWrap {
    position:fixed; bottom:24px; right:24px; z-index:3000;
    display:flex; flex-direction:column; gap:8px;
    pointer-events:none; max-width:320px;
}
.pz-toast {
    padding:11px 16px; border-radius:9px; font-size:.85rem; font-weight:500;
    line-height:1.4; box-shadow:0 4px 18px rgba(0,0,0,.18);
    pointer-events:all; opacity:0; transform:translateY(8px);
    transition:opacity .22s, transform .22s;
    display:flex; align-items:flex-start; gap:10px;
}
.pz-toast.show { opacity:1; transform:translateY(0); }
.pz-toast.type-error   { background:#c62828; color:#fff; }
.pz-toast.type-success { background:#2e7d32; color:#fff; }
.pz-toast.type-info    { background:#1565c0; color:#fff; }
.pz-toast .pz-toast-icon { font-size:18px; flex-shrink:0; }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="msg-show-wrap">
    <div class="msg-chat-hdr">
        <button class="msg-back-btn" onclick="history.back()">
            <span class="material-symbols-outlined">arrow_back</span>
        </button>
        <div style="flex:1; min-width:0">
            <div class="msg-chat-title" id="chatTitle">Loading…</div>
        </div>
        <button class="msg-hdr-info-btn" id="hdrInfoBtn" title="View members">
            <span class="material-symbols-outlined">info</span>
        </button>
        <div class="msg-hdr-pop" id="hdrPop">
            <div class="msg-hdr-pop-title">Members</div>
            <ul id="hdrMemberList"></ul>
        </div>
    </div>

    <div class="msg-thread" id="msgThread">
        <div id="threadLoading">Loading messages…</div>
    </div>

    <div class="msg-compose-area">
        <div class="msg-chips-row" id="attachChips"></div>
        <div class="msg-compose">
            <input type="file" id="fileInput" style="display:none" multiple
                   accept="image/*,application/pdf,.doc,.docx,.xls,.xlsx,.txt,.zip">
            <button class="msg-attach-btn" id="attachBtn" aria-label="Attach file">
                <span class="material-symbols-outlined">attach_file</span>
            </button>
            <textarea id="msgInput" rows="1" placeholder="Type a message… (Enter to send, Shift+Enter for newline)" maxlength="2000"></textarea>
            <button class="msg-send-btn" id="sendBtn" aria-label="Send">
                <span class="material-symbols-outlined">send</span>
            </button>
        </div>
    </div>
</div>

<div id="imgLightbox"><img id="lbImg" src="" alt=""></div>
<div id="pzToastWrap"></div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
    var _base   = '<?= base_url() ?>';
    var _chatId = '<?= esc($chatId) ?>';
    var _myUid  = '<?= esc(session()->get('userId') ?? '') ?>';
    var _known  = {};
    var _atBot  = true;
    var _selectedFiles = [];   // array of File objects

    var thread    = document.getElementById('msgThread');
    var input     = document.getElementById('msgInput');
    var sendBtn   = document.getElementById('sendBtn');
    var fileInput = document.getElementById('fileInput');
    var attachBtn = document.getElementById('attachBtn');
    var chipsRow  = document.getElementById('attachChips');

    function h(s) {
        var d = document.createElement('div');
        d.appendChild(document.createTextNode(String(s || '')));
        return d.innerHTML;
    }

    function fmtTime(iso) {
        if (!iso) return '';
        var d = new Date(iso.replace(' ', 'T') + '+08:00');
        return d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
    }

    function fmtDate(iso) {
        if (!iso) return '';
        var d     = new Date(iso.replace(' ', 'T') + '+08:00');
        var today = new Date();
        var yest  = new Date(today); yest.setDate(yest.getDate() - 1);
        if (d.toDateString() === today.toDateString()) return 'Today';
        if (d.toDateString() === yest.toDateString())  return 'Yesterday';
        return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }

    function initials(name) {
        return (name || '?').trim().split(/\s+/).map(function (w) { return w[0]; }).slice(0, 2).join('').toUpperCase();
    }

    thread.addEventListener('scroll', function () {
        _atBot = thread.scrollHeight - thread.scrollTop - thread.clientHeight < 60;
    });

    function scrollToBottom(force) {
        if (force || _atBot) thread.scrollTop = thread.scrollHeight;
    }

    function buildBubbleContent(msg) {
        if (msg.type === 'image' && msg.file_url) {
            var img = '<img src="' + h(msg.file_url) + '" alt="' + h(msg.file_name || 'Image') + '" data-lightbox>';
            return img + (msg.text ? '<div style="margin-top:4px">' + h(msg.text) + '</div>' : '');
        }
        if (msg.type === 'file' && msg.file_url) {
            var link = '<a class="msg-file-link" href="' + h(msg.file_url) + '" target="_blank" rel="noopener">' +
                '<span class="material-symbols-outlined">description</span>' +
                h(msg.file_name || 'Download') + '</a>';
            return link + (msg.text ? '<div style="margin-top:4px">' + h(msg.text) + '</div>' : '');
        }
        return h(msg.text || '');
    }

    function renderMessages(messages, chat, initial) {
        var el = document.getElementById('threadLoading');
        if (el) el.style.display = 'none';

        if (initial) {
            thread.innerHTML = '';
            _known = {};
        }

        var lastDate = '';
        var frag = document.createDocumentFragment();
        var hasNew = false;

        messages.forEach(function (msg) {
            if (_known[msg.id]) return;
            _known[msg.id] = true;
            hasNew = true;

            var dateLabel = fmtDate(msg.sent_at);
            if (dateLabel !== lastDate) {
                lastDate = dateLabel;
                var div = document.createElement('div');
                div.className = 'msg-date-div';
                div.textContent = dateLabel;
                frag.appendChild(div);
            }

            var isMine = msg.sender_uid === _myUid;
            var row = document.createElement('div');
            row.className = 'msg-bubble-row' + (isMine ? ' mine' : '');

            var av = document.createElement('div');
            av.className = 'msg-avatar';
            av.textContent = initials(msg.sender_name);

            var wrap = document.createElement('div');
            wrap.className = 'msg-bubble-wrap';

            if (!isMine) {
                var lbl = document.createElement('div');
                lbl.className = 'msg-sender-label';
                lbl.textContent = msg.sender_name;
                wrap.appendChild(lbl);
            }

            var bubble = document.createElement('div');
            bubble.className = 'msg-bubble';
            bubble.innerHTML = buildBubbleContent(msg);
            wrap.appendChild(bubble);

            var tm = document.createElement('div');
            tm.className = 'msg-time';
            tm.textContent = fmtTime(msg.sent_at);
            wrap.appendChild(tm);

            row.appendChild(av);
            row.appendChild(wrap);
            frag.appendChild(row);
        });

        if (messages.length === 0 && initial) {
            var emp = document.createElement('div');
            emp.className = 'msg-empty-thread';
            emp.innerHTML = '<span class="material-symbols-outlined">chat_bubble_outline</span><p>No messages yet. Say hello!</p>';
            frag.appendChild(emp);
        }

        if (hasNew || initial) {
            thread.appendChild(frag);
            scrollToBottom(initial);

            // Bind lightbox
            thread.querySelectorAll('[data-lightbox]').forEach(function (img) {
                if (img._lbBound) return;
                img._lbBound = true;
                img.addEventListener('click', function () {
                    document.getElementById('lbImg').src = img.src;
                    document.getElementById('imgLightbox').classList.add('open');
                });
            });
        }

        if (initial && chat) {
            document.getElementById('chatTitle').textContent = chat.project_name || 'Project Chat';
            var names = Object.values(chat.member_names || {}).filter(Boolean);
            var list  = document.getElementById('hdrMemberList');
            if (list) {
                list.innerHTML = names.length
                    ? names.map(function (n) { return '<li>' + h(n) + '</li>'; }).join('')
                    : '<li style="color:#aaa">No member info</li>';
            }
        }
    }

    function load(initial) {
        fetch(_base + 'messages/' + _chatId + '/json', { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (d) { renderMessages(d.messages || [], d.chat || null, initial); })
            .catch(function () {
                if (initial) {
                    var el = document.getElementById('threadLoading');
                    if (el) el.textContent = 'Failed to load messages.';
                }
            });
    }

    load(true);
    setInterval(function () { load(false); }, 5000);

    // Lightbox close
    document.getElementById('imgLightbox').addEventListener('click', function () {
        this.classList.remove('open');
    });

    // ── File attach ────────────────────────────────────────────────────────

    function renderChips() {
        chipsRow.innerHTML = '';
        _selectedFiles.forEach(function (f, idx) {
            var chip = document.createElement('div');
            chip.className = 'msg-att-chip';

            if (f.type.startsWith('image/')) {
                var reader = new FileReader();
                (function (el) {
                    reader.onload = function (e) {
                        var img = document.createElement('img');
                        img.className = 'att-chip-img';
                        img.src = e.target.result;
                        el.insertBefore(img, el.firstChild);
                    };
                })(chip);
                reader.readAsDataURL(f);
            } else {
                var icon = document.createElement('span');
                icon.className = 'material-symbols-outlined att-chip-icon';
                icon.textContent = 'description';
                chip.appendChild(icon);
            }

            var name = document.createElement('span');
            name.className = 'att-chip-name';
            name.textContent = f.name;
            chip.appendChild(name);

            var rm = document.createElement('button');
            rm.className = 'att-chip-rm';
            rm.type = 'button';
            rm.textContent = '×';
            rm.setAttribute('aria-label', 'Remove');
            rm.dataset.idx = idx;
            rm.addEventListener('click', function () {
                _selectedFiles.splice(parseInt(this.dataset.idx), 1);
                if (_selectedFiles.length === 0) fileInput.value = '';
                renderChips();
            });
            chip.appendChild(rm);

            chipsRow.appendChild(chip);
        });
    }

    function addFile(f) {
        _selectedFiles.push(f);
        renderChips();
    }

    attachBtn.addEventListener('click', function () { fileInput.click(); });

    fileInput.addEventListener('change', function () {
        for (var i = 0; i < fileInput.files.length; i++) {
            addFile(fileInput.files[i]);
        }
        fileInput.value = ''; // reset so same file can be re-added
    });

    // Paste image from clipboard into the compose box
    input.addEventListener('paste', function (e) {
        var items = e.clipboardData && e.clipboardData.items;
        if (!items) return;
        for (var i = 0; i < items.length; i++) {
            if (items[i].kind === 'file' && items[i].type.startsWith('image/')) {
                e.preventDefault();
                var blob = items[i].getAsFile();
                var ext  = items[i].type.split('/')[1] || 'png';
                addFile(new File([blob], 'pasted-image-' + Date.now() + '.' + ext, { type: items[i].type }));
                break;
            }
        }
    });

    // ── Toast ──────────────────────────────────────────────────────────────

    var TOAST_ICONS = { error:'error', success:'check_circle', info:'info' };

    function showToast(msg, type) {
        type = type || 'info';
        var wrap  = document.getElementById('pzToastWrap');
        var toast = document.createElement('div');
        toast.className = 'pz-toast type-' + type;
        toast.innerHTML =
            '<span class="material-symbols-outlined pz-toast-icon">' + (TOAST_ICONS[type] || 'info') + '</span>' +
            '<span>' + String(msg).replace(/</g, '&lt;') + '</span>';
        wrap.appendChild(toast);
        requestAnimationFrame(function () { toast.classList.add('show'); });
        setTimeout(function () {
            toast.classList.remove('show');
            setTimeout(function () { if (toast.parentNode) toast.parentNode.removeChild(toast); }, 250);
        }, 4000);
    }

    // ── Send ───────────────────────────────────────────────────────────────

    function doSend(file, text) {
        var opts;
        if (file) {
            var fd = new FormData();
            fd.append('file', file);
            if (text) fd.append('text', text);
            opts = {
                method: 'POST', credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: fd,
            };
        } else {
            opts = {
                method: 'POST', credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ text: text }),
            };
        }
        return fetch(_base + 'messages/' + _chatId + '/send', opts).then(function (r) {
            var status = r.status;
            if (!r.ok && status !== 400 && status !== 403 && status !== 500) {
                throw new Error('HTTP ' + status);
            }
            return r.text().then(function (raw) {
                try {
                    var d = JSON.parse(raw);
                    if (!d.success && !d.error) {
                        console.warn('[pz-msg] send failed, status=' + status + ', body:', raw);
                        d.error = 'Server error (HTTP ' + status + ')';
                    }
                    return d;
                } catch (e) {
                    console.error('[pz-msg] non-JSON response, status=' + status + ':', raw.substring(0, 300));
                    throw new Error('Non-JSON response (HTTP ' + status + ')');
                }
            });
        });
    }

    async function send() {
        var text = input.value.trim();
        if (!text && _selectedFiles.length === 0) return;

        sendBtn.disabled = true;
        var failed = false;
        var files  = _selectedFiles.slice(); // snapshot

        try {
            if (files.length === 0) {
                // Text-only: one JSON send
                var d = await doSend(null, text);
                if (!d.success) { showToast(d.error || 'Failed to send.', 'error'); failed = true; }
            } else {
                // Text first (separate bubble), then each file
                if (text) {
                    var d = await doSend(null, text);
                    if (!d.success) { showToast(d.error || 'Failed to send.', 'error'); failed = true; }
                }
                for (var i = 0; i < files.length; i++) {
                    console.log('[pz-msg] sending file:', files[i].name, files[i].size, files[i].type);
                    var d = await doSend(files[i], '');
                    console.log('[pz-msg] file send result:', d);
                    if (!d.success) {
                        showToast(d.error || 'Failed to send attachment.', 'error');
                        failed = true;
                        break;
                    }
                }
            }
        } catch (err) {
            console.error('[pz-msg] sendMessage error:', err);
            showToast('Error: ' + err.message, 'error');
            failed = true;
        }

        if (!failed) {
            input.value = '';
            input.style.height = '';
            _selectedFiles = [];
            renderChips();
        }
        _atBot = true;
        load(false);
        sendBtn.disabled = false;
    }

    // Header info button
    document.getElementById('hdrInfoBtn').addEventListener('click', function () {
        document.getElementById('hdrPop').classList.toggle('open');
    });
    document.addEventListener('click', function (e) {
        if (!e.target.closest('#hdrInfoBtn') && !e.target.closest('#hdrPop')) {
            document.getElementById('hdrPop').classList.remove('open');
        }
    });

    sendBtn.addEventListener('click', send);
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); send(); }
    });
    input.addEventListener('input', function () {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 120) + 'px';
    });
}());
</script>
<?= $this->endSection() ?>
