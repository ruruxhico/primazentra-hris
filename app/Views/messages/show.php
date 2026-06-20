<?= $this->extend('layouts/main') ?>

<?= $this->section('head') ?>
<style>
.msg-show-wrap {
    display:flex; flex-direction:column;
    height:calc(100vh - 120px); min-height:400px;
}

.msg-chat-hdr {
    display:flex; align-items:center; gap:12px;
    padding-bottom:12px; border-bottom:1px solid #eee; flex-shrink:0;
}
.msg-back-btn {
    background:none; border:none; cursor:pointer; color:#b8960c;
    display:flex; align-items:center; padding:4px;
    border-radius:6px; transition:background .1s;
}
.msg-back-btn:hover { background:#fdf8e1; }
.msg-back-btn .material-symbols-outlined { font-size:22px; }
.msg-chat-title { font-size:1rem; font-weight:700; color:#1a1a1a; }
.msg-chat-members { font-size:.75rem; color:#aaa; margin-top:2px; }

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

.msg-sender-label { font-size:.7rem; color:#aaa; margin-bottom:3px; padding:0 4px; }

.msg-bubble {
    padding:9px 14px; border-radius:16px; font-size:.88rem;
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

.msg-time { font-size:.68rem; color:#bbb; margin-top:3px; padding:0 4px; }
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
.msg-compose-area { flex-shrink:0; border-top:1px solid #eee; padding-top:10px; }

.msg-attach-preview {
    display:none; align-items:center; gap:10px;
    background:#fdf8e1; border:1px solid #e8d97a; border-radius:8px;
    padding:8px 12px; margin-bottom:8px; font-size:.83rem;
}
.msg-attach-preview.visible { display:flex; }
.msg-attach-preview img { max-height:60px; border-radius:6px; }
.msg-attach-preview .att-name { flex:1; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; color:#555; }
.msg-remove-att {
    background:none; border:none; cursor:pointer; color:#c00;
    display:flex; align-items:center; padding:2px;
}
.msg-remove-att .material-symbols-outlined { font-size:18px; }

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
    padding:10px 12px; font-size:.88rem; outline:none;
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
            <div class="msg-chat-members" id="chatMembers"></div>
        </div>
    </div>

    <div class="msg-thread" id="msgThread">
        <div id="threadLoading">Loading messages…</div>
    </div>

    <div class="msg-compose-area">
        <div class="msg-attach-preview" id="attachPreview">
            <span id="attThumb"></span>
            <span class="att-name" id="attName"></span>
            <button class="msg-remove-att" id="removeAtt" aria-label="Remove">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="msg-compose">
            <input type="file" id="fileInput" style="display:none" accept="image/*,application/pdf,.doc,.docx,.xls,.xlsx,.txt,.zip">
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
    var _selectedFile = null;

    var thread     = document.getElementById('msgThread');
    var input      = document.getElementById('msgInput');
    var sendBtn    = document.getElementById('sendBtn');
    var fileInput  = document.getElementById('fileInput');
    var attachBtn  = document.getElementById('attachBtn');
    var preview    = document.getElementById('attachPreview');
    var attThumb   = document.getElementById('attThumb');
    var attName    = document.getElementById('attName');
    var removeAtt  = document.getElementById('removeAtt');

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
            var names = Object.values(chat.member_names || {});
            document.getElementById('chatMembers').textContent =
                names.length ? names.join(', ') : '';
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

    function setSelectedFile(f) {
        _selectedFile = f;
        attName.textContent = f.name;
        attThumb.innerHTML = '';
        if (f.type.startsWith('image/')) {
            var reader = new FileReader();
            reader.onload = function (e) {
                var img = document.createElement('img');
                img.src = e.target.result;
                attThumb.appendChild(img);
            };
            reader.readAsDataURL(f);
        } else {
            attThumb.innerHTML = '<span class="material-symbols-outlined" style="font-size:28px;color:#b8960c">description</span>';
        }
        preview.classList.add('visible');
    }

    attachBtn.addEventListener('click', function () { fileInput.click(); });

    fileInput.addEventListener('change', function () {
        if (fileInput.files[0]) setSelectedFile(fileInput.files[0]);
    });

    // Paste image directly into the compose box
    input.addEventListener('paste', function (e) {
        var items = e.clipboardData && e.clipboardData.items;
        if (!items) return;
        for (var i = 0; i < items.length; i++) {
            if (items[i].kind === 'file' && items[i].type.startsWith('image/')) {
                e.preventDefault();
                var blob = items[i].getAsFile();
                var ext  = items[i].type.split('/')[1] || 'png';
                setSelectedFile(new File([blob], 'pasted-image-' + Date.now() + '.' + ext, { type: items[i].type }));
                break;
            }
        }
    });

    removeAtt.addEventListener('click', function () {
        _selectedFile = null;
        fileInput.value = '';
        preview.classList.remove('visible');
        attThumb.innerHTML = '';
        attName.textContent = '';
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

    function send() {
        var text = input.value.trim();
        if (!text && !_selectedFile) return;

        sendBtn.disabled = true;

        var fetchOpts;
        if (_selectedFile) {
            // File upload — must use multipart FormData
            var fd = new FormData();
            fd.append('text', text);
            fd.append('file', _selectedFile);
            fetchOpts = {
                method: 'POST', credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: fd,
            };
        } else {
            // Text only — use JSON to avoid multipart parsing issues
            fetchOpts = {
                method: 'POST', credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ text: text }),
            };
        }

        fetch(_base + 'messages/' + _chatId + '/send', fetchOpts)
        .then(function (r) { return r.json(); })
        .then(function (d) {
            if (d.success) {
                input.value = '';
                input.style.height = '';
                removeAtt.click();
                _atBot = true;
                load(false);
            } else {
                console.error('sendMessage failed:', d);
                showToast(d.error || 'Failed to send.', 'error');
            }
        })
        .catch(function (err) {
            console.error('sendMessage error:', err);
            showToast('Network error. Please try again.', 'error');
        })
        .finally(function () { sendBtn.disabled = false; });
    }

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
