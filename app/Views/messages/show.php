<?= $this->extend('layouts/main') ?>

<?= $this->section('head') ?>
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
    var _known    = {};
    var _atBot    = true;
    var _lastDate = '';
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
            _lastDate = '';
        }

        var frag = document.createDocumentFragment();
        var hasNew = false;

        messages.forEach(function (msg) {
            if (_known[msg.id]) return;
            _known[msg.id] = true;
            hasNew = true;

            var dateLabel = fmtDate(msg.sent_at);
            if (dateLabel !== _lastDate) {
                _lastDate = dateLabel;
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
