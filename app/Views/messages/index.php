<?= $this->extend('layouts/main') ?>

<?= $this->section('head') ?>
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
            var unread  = c.unread_count || 0;
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

            var unreadBadge = unread > 0
                ? '<span class="chat-unread-badge">' + (unread > 99 ? '99+' : unread) + '</span>'
                : '';

            return '<a class="chat-card' + (unread > 0 ? ' chat-card--unread' : '') + '" href="' + _base + 'messages/' + h(c.id) + '">' +
                '<div class="chat-avatar"><span class="material-symbols-outlined">forum</span></div>' +
                '<div class="chat-info">' +
                    '<div class="chat-title">' + title + (unread > 0 ? ' <span class="chat-title-dot"></span>' : '') + '</div>' +
                    '<div class="chat-preview' + (unread > 0 ? ' chat-preview--unread' : '') + '">' + preview + '</div>' +
                '</div>' +
                '<div class="chat-right">' +
                    '<div class="chat-time' + (unread > 0 ? ' chat-time--unread' : '') + '">' + h(time) + '</div>' +
                    unreadBadge +
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
