<?= $this->extend('layouts/main') ?>

<?= $this->section('head') ?>
<style>
.msg-page-hdr {
    display:flex; align-items:center; justify-content:space-between;
    margin-bottom:1.2rem;
}
.msg-page-hdr h1 { font-size:1.4rem; font-weight:700; margin:0; }

.chat-list { display:flex; flex-direction:column; gap:8px; }

.chat-card {
    display:flex; align-items:center; gap:14px;
    background:#fff; border:1px solid #e2ddd4; border-radius:10px;
    padding:14px 18px; cursor:pointer; transition:background .12s, border-color .12s, box-shadow .12s;
    text-decoration:none; color:inherit; position:relative;
    box-shadow: 0 1px 5px rgba(0,0,0,0.07);
}
.chat-card:hover { background:#fdf8e1; border-color:#e8d97a; }

.chat-avatar {
    width:44px; height:44px; border-radius:50%; flex-shrink:0;
    background:#f0ead8; color:#b8960c;
    display:flex; align-items:center; justify-content:center;
}
.chat-avatar .material-symbols-outlined { font-size:22px; }

.chat-info { flex:1; min-width:0; }
.chat-title {
    font-weight:700; font-size:1rem; color:#1a1a1a;
    white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
}
.chat-preview {
    font-size:.9rem; color:#777; margin-top:2px;
    white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
}
.chat-right {
    flex-shrink:0; display:flex; align-items:center; gap:8px;
}
.chat-time { font-size:.82rem; color:#888; white-space:nowrap; }

/* Info button + popover */
.chat-info-btn {
    flex-shrink:0; background:none; border:none; cursor:pointer;
    color:#b8960c; padding:2px; line-height:1; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    transition:background .12s;
}
.chat-info-btn:hover { background:#f0ead8; }
.chat-info-btn .material-symbols-outlined { font-size:16px; font-variation-settings:'FILL' 1; }

.chat-members-pop {
    display:none; position:absolute; right:14px; top:42px;
    background:#fff; border:1px solid #e8d97a; border-radius:10px;
    padding:10px 14px; min-width:180px; max-width:260px;
    box-shadow:0 4px 16px rgba(0,0,0,.12); z-index:100;
    font-size:.88rem; color:#333;
}
.chat-members-pop.open { display:block; }
.chat-members-pop-title {
    font-weight:700; font-size:.82rem; color:#b8960c;
    text-transform:uppercase; letter-spacing:.04em; margin-bottom:6px;
}
.chat-members-pop ul {
    margin:0; padding:0 0 0 14px;
}
.chat-members-pop li { margin-bottom:3px; }

.msg-empty {
    text-align:center; padding:60px 20px; color:#888;
}
.msg-empty .material-symbols-outlined { font-size:48px; display:block; margin-bottom:12px; opacity:.4; }
.msg-empty p { font-size:.9rem; margin-bottom:4px; }
.msg-empty a { color:#b8960c; font-weight:600; text-decoration:none; }
.msg-empty a:hover { text-decoration:underline; }

#chatLoading { text-align:center; padding:40px; color:#aaa; font-size:.9rem; }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="msg-page-hdr">
    <h1>Messages</h1>
</div>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger mb-3"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<div id="chatLoading">Loading…</div>
<div id="chatList" class="chat-list" style="display:none"></div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
    var _base  = '<?= base_url() ?>';
    var _myUid = '<?= esc(session()->get('userId') ?? '') ?>';

    function h(s) {
        var d = document.createElement('div');
        d.appendChild(document.createTextNode(String(s || '')));
        return d.innerHTML;
    }

    function relTime(iso) {
        if (!iso) return '';
        var d = new Date(iso.replace(' ', 'T') + '+08:00');
        var diff = Math.floor((Date.now() - d) / 1000);
        if (diff < 60)     return 'Just now';
        if (diff < 3600)   return Math.floor(diff / 60) + 'm ago';
        if (diff < 86400)  return Math.floor(diff / 3600) + 'h ago';
        if (diff < 604800) return Math.floor(diff / 86400) + 'd ago';
        return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    }

    function renderChats(chats) {
        var el = document.getElementById('chatList');
        if (!chats.length) {
            el.innerHTML =
                '<div class="msg-empty">' +
                '<span class="material-symbols-outlined">chat_bubble_outline</span>' +
                '<p>No project chats yet.</p>' +
                '<p>Open a <a href="' + _base + 'projects">project</a> and click <strong>Chat</strong> to start one.</p>' +
                '</div>';
            return;
        }

        el.innerHTML = chats.map(function (c, idx) {
            var lm      = c.last_message;
            var preview = lm && (lm.text || lm.sender_name)
                ? h((lm.sender_name ? lm.sender_name + ': ' : '') + (lm.text || ''))
                : '<em style="color:#bbb">No messages yet</em>';
            var time    = lm ? relTime(lm.sent_at) : relTime(c.created_at);
            var title   = h(c.project_name || 'Project Chat');
            var popId   = 'mem-pop-' + idx;

            // Build member list from member_names (object keyed by uid, or array)
            var names = c.member_names;
            var memberItems = '';
            if (names && typeof names === 'object') {
                var vals = Array.isArray(names) ? names : Object.values(names);
                memberItems = vals.filter(Boolean).map(function (n) {
                    return '<li>' + h(n) + '</li>';
                }).join('');
            }
            var membersHtml = memberItems
                ? '<div class="chat-members-pop-title">Members</div><ul>' + memberItems + '</ul>'
                : '<span style="color:#aaa">No member info</span>';

            return '<a class="chat-card" href="' + _base + 'messages/' + h(c.id) + '">' +
                '<div class="chat-avatar"><span class="material-symbols-outlined">forum</span></div>' +
                '<div class="chat-info">' +
                    '<div class="chat-title">' + title + '</div>' +
                    '<div class="chat-preview">' + preview + '</div>' +
                '</div>' +
                '<div class="chat-right">' +
                    '<div class="chat-time">' + h(time) + '</div>' +
                    '<button class="chat-info-btn" data-pop="' + popId + '" title="View members" onclick="event.preventDefault();togglePop(this)">' +
                        '<span class="material-symbols-outlined">info</span>' +
                    '</button>' +
                '</div>' +
                '<div class="chat-members-pop" id="' + popId + '">' + membersHtml + '</div>' +
            '</a>';
        }).join('');
    }

    function load() {
        fetch(_base + 'messages/json', { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                document.getElementById('chatLoading').style.display = 'none';
                document.getElementById('chatList').style.display = '';
                renderChats(d.chats || []);
            })
            .catch(function () {
                document.getElementById('chatLoading').textContent = 'Failed to load messages.';
            });
    }

    load();
    setInterval(load, 15000);
}());

function togglePop(btn) {
    var popId = btn.getAttribute('data-pop');
    var pop   = document.getElementById(popId);
    if (!pop) return;
    var isOpen = pop.classList.contains('open');
    // Close all open popovers first
    document.querySelectorAll('.chat-members-pop.open').forEach(function (el) {
        el.classList.remove('open');
    });
    if (!isOpen) pop.classList.add('open');
}
// Close popovers on outside click
document.addEventListener('click', function (e) {
    if (!e.target.closest('.chat-info-btn') && !e.target.closest('.chat-members-pop')) {
        document.querySelectorAll('.chat-members-pop.open').forEach(function (el) {
            el.classList.remove('open');
        });
    }
});
</script>
<?= $this->endSection() ?>
