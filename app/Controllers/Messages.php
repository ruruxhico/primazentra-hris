<?php

namespace App\Controllers;

use App\Libraries\FirestoreService;
use App\Models\Model_Users;
use App\Services\ChatService;
use App\Services\NotificationService;
use Google\Cloud\Core\Timestamp;
use Google\Cloud\Firestore\FieldValue;

class Messages extends BaseController
{
    private \Google\Cloud\Firestore\FirestoreClient $db;

    public function __construct()
    {
        $this->db = (FirestoreService::getInstance())->getDb();
    }

    // GET /messages
    public function index()
    {
        if (!session()->get('isLoggedIn')) return redirect()->to('auth/login');

        $uid = (string)(session()->get('userId') ?? '');
        if ($uid) {
            try {
                (new \App\Services\NotificationService())->markTypeAsRead($uid, 'messageSent');
            } catch (\Throwable $e) {
                log_message('error', 'Messages::index markTypeAsRead: ' . $e->getMessage());
            }
        }

        return view('messages/index', [
            'pageTitle' => 'Messages',
            'role'      => session()->get('role') ?? '',
        ]);
    }

    // GET /messages/{chatId}
    public function show(string $chatId)
    {
        if (!session()->get('isLoggedIn')) return redirect()->to('auth/login');

        $uid = (string)(session()->get('userId') ?? '');

        try {
            $chatRef = $this->db->collection('chats')->document($chatId);
            $snap    = $chatRef->snapshot();
            if (!$snap->exists()) {
                return redirect()->to('messages')->with('error', 'Chat not found.');
            }
            if (!in_array($uid, (array)($snap->data()['members'] ?? []), true)) {
                return redirect()->to('messages')->with('error', 'You are not a member of this chat.');
            }
            // Reset this user's unread count for the chat
            $chatRef->update([['path' => 'unread_counts.' . $uid, 'value' => 0]]);
        } catch (\Throwable $e) {
            log_message('error', 'Messages::show - ' . $e->getMessage());
            return redirect()->to('messages')->with('error', 'Failed to load chat.');
        }

        return view('messages/show', [
            'pageTitle' => 'Messages',
            'chatId'    => $chatId,
            'role'      => session()->get('role') ?? '',
        ]);
    }

    // GET /messages/project/{projectId} — find or create project chat, redirect to show
    public function projectChat(string $projectId)
    {
        if (!session()->get('isLoggedIn')) return redirect()->to('auth/login');

        try {
            $chatId = $this->createOrGetProjectChat($projectId);
        } catch (\Throwable $e) {
            log_message('error', 'Messages::projectChat - ' . $e->getMessage());
            return redirect()->to('messages')->with('error', 'Could not open project chat.');
        }

        return redirect()->to('messages/' . $chatId);
    }

    // GET /messages/unread-count — lightweight badge endpoint
    public function unreadCount()
    {
        if (!session()->get('isLoggedIn')) {
            return $this->response->setJSON(['count' => 0]);
        }
        $uid = (string)(session()->get('userId') ?? '');
        if (!$uid) return $this->response->setJSON(['count' => 0]);

        try {
            $docs  = $this->db->collection('chats')
                ->where('members', 'array-contains', $uid)
                ->documents();
            $total = 0;
            foreach ($docs as $doc) {
                if (!$doc->exists()) continue;
                $counts = $doc->data()['unread_counts'] ?? [];
                $total += (int)($counts[$uid] ?? 0);
            }
            return $this->response->setJSON(['count' => $total]);
        } catch (\Throwable $e) {
            log_message('error', 'Messages::unreadCount - ' . $e->getMessage());
            return $this->response->setJSON(['count' => 0]);
        }
    }

    // GET /messages/json
    public function chatsJson()
    {
        if (!session()->get('isLoggedIn')) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Unauthorized']);
        }

        $uid = (string)(session()->get('userId') ?? '');
        if (!$uid) return $this->response->setStatusCode(401)->setJSON(['error' => 'Unauthorized']);

        try {
            $docs = $this->db->collection('chats')
                ->where('members', 'array-contains', $uid)
                ->documents();

            $chats   = [];
            $allUids = [];

            foreach ($docs as $doc) {
                if (!$doc->exists()) continue;
                $d       = $doc->data();
                $members = array_values((array)($d['members'] ?? []));
                foreach ($members as $mUid) {
                    $allUids[(string)$mUid] = true;
                }
                $unreadCounts = (array)($d['unread_counts'] ?? []);
                $chats[] = [
                    'id'           => $doc->id(),
                    'project_id'   => $d['project_id']   ?? null,
                    'project_name' => $d['project_name'] ?? null,
                    'members'      => $members,
                    'last_message' => $this->serializeLastMessage($d['last_message'] ?? null),
                    'created_at'   => $this->tsToIso($d['created_at'] ?? null),
                    'unread_count' => (int)($unreadCounts[$uid] ?? 0),
                ];
            }

            // Resolve real names live from Firestore users collection so stale
            // member_names (which old chats stored as emails) are always correct.
            $nameMap = [];
            foreach (array_keys($allUids) as $mUid) {
                $snap = $this->db->collection('users')->document($mUid)->snapshot();
                $nameMap[$mUid] = $snap->exists()
                    ? $this->buildFullName($snap->data())
                    : (string)$mUid;
            }

            foreach ($chats as &$chat) {
                $resolved = [];
                foreach ($chat['members'] as $mUid) {
                    $resolved[$mUid] = $nameMap[(string)$mUid] ?? (string)$mUid;
                }
                $chat['member_names'] = $resolved;
            }
            unset($chat);

            usort($chats, function ($a, $b) {
                $ta = $a['last_message']['sent_at'] ?? $a['created_at'] ?? '';
                $tb = $b['last_message']['sent_at'] ?? $b['created_at'] ?? '';
                return strcmp($tb, $ta);
            });

            return $this->response->setJSON(['chats' => $chats]);
        } catch (\Throwable $e) {
            log_message('error', 'Messages::chatsJson - ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['error' => 'Server error']);
        }
    }

    // GET /messages/{chatId}/json
    public function messagesJson(string $chatId)
    {
        if (!session()->get('isLoggedIn')) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Unauthorized']);
        }

        $uid = (string)(session()->get('userId') ?? '');

        try {
            $chatSnap = $this->db->collection('chats')->document($chatId)->snapshot();
            if (!$chatSnap->exists()) {
                return $this->response->setStatusCode(404)->setJSON(['error' => 'Not found']);
            }
            $chatData = $chatSnap->data();
            if (!in_array($uid, (array)($chatData['members'] ?? []), true)) {
                return $this->response->setStatusCode(403)->setJSON(['error' => 'Forbidden']);
            }

            $msgDocs = $this->db->collection('chats')->document($chatId)
                ->collection('messages')
                ->orderBy('sent_at')
                ->limit(200)
                ->documents();

            $messages = [];
            foreach ($msgDocs as $doc) {
                if (!$doc->exists()) continue;
                $d = $doc->data();
                $messages[] = [
                    'id'          => $doc->id(),
                    'type'        => (string)($d['type']        ?? 'text'),
                    'text'        => isset($d['text'])     ? (string)$d['text']     : null,
                    'file_url'    => isset($d['file_url']) ? (string)$d['file_url'] : null,
                    'file_name'   => isset($d['file_name'])? (string)$d['file_name']: null,
                    'sender_uid'  => (string)($d['sender_uid']  ?? $d['sender_id']  ?? ''),
                    'sender_name' => (string)($d['sender_name'] ?? ''),
                    'sent_at'     => $this->tsToIso($d['sent_at'] ?? null),
                ];
            }

            // Resolve real names from Firestore users so stale stored member_names are corrected
            $memberIds    = array_values((array)($chatData['members'] ?? []));
            $resolvedNames = [];
            foreach ($memberIds as $mUid) {
                $snap = $this->db->collection('users')->document((string)$mUid)->snapshot();
                $resolvedNames[(string)$mUid] = $snap->exists()
                    ? $this->buildFullName($snap->data())
                    : (string)$mUid;
            }

            return $this->response->setJSON([
                'messages' => $messages,
                'chat' => [
                    'id'           => $chatSnap->id(),
                    'project_id'   => $chatData['project_id']   ?? null,
                    'project_name' => $chatData['project_name'] ?? null,
                    'member_names' => $resolvedNames,
                ],
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Messages::messagesJson - ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['error' => 'Server error']);
        }
    }

    // POST /messages/{chatId}/send
    // Accepts multipart (with file) or JSON (text only). JS sends one file per request.
    public function sendMessage(string $chatId)
    {
        if (!session()->get('isLoggedIn')) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Unauthorized']);
        }

        try {
            $uid      = (string)(session()->get('userId')   ?? '');
            $userName = (string)(session()->get('userName') ?? '');

            // Support both JSON body (text-only) and multipart form (file upload).
            // Only call getJSON() for application/json requests: calling it on multipart bodies
            // runs json_decode() which sets json_last_error() = JSON_ERROR_SYNTAX and can
            // cause CI4's JSONFormatter to throw FormatException when encoding the response.
            $isJsonRequest = str_contains(
                $this->request->getHeaderLine('Content-Type'),
                'application/json'
            );
            $jsonBody = $isJsonRequest ? ($this->request->getJSON(true) ?: []) : [];
            $text     = trim((string)($this->request->getPost('text') ?? $jsonBody['text'] ?? ''));

            // Single file upload — JS always sends exactly one file per request via 'file' field
            $uploadedFile = $this->request->getFile('file');
            $hasFile = $uploadedFile instanceof \CodeIgniter\HTTP\Files\UploadedFile
                    && $uploadedFile->isValid()
                    && !$uploadedFile->hasMoved();

            if (ENVIRONMENT === 'development' && $uploadedFile && !$hasFile) {
                log_message('debug', 'Messages::sendMessage - file invalid, error=' . $uploadedFile->getError() . ' name=' . $uploadedFile->getName());
            }

            if ($text === '' && !$hasFile) {
                return $this->response->setStatusCode(400)->setJSON(['error' => 'Empty message']);
            }
            $chatRef  = $this->db->collection('chats')->document($chatId);
            $chatSnap = $chatRef->snapshot();
            if (!$chatSnap->exists()) {
                return $this->response->setStatusCode(404)->setJSON(['error' => 'Chat not found']);
            }

            $chatData = $chatSnap->data();
            $members  = (array)($chatData['members'] ?? []);
            if (!in_array($uid, $members, true)) {
                return $this->response->setStatusCode(403)->setJSON(['error' => 'Forbidden']);
            }

            $now    = new Timestamp(new \DateTime());
            $msgRef = $chatRef->collection('messages')->newDocument();
            $msgId  = $msgRef->id();

            $msgData = [
                'sender_uid'  => $uid,
                'sender_name' => $userName,
                'sent_at'     => $now,
                'text'        => $text ?: null,
                'file_url'    => null,
                'file_name'   => null,
                'type'        => 'text',
            ];

            if ($hasFile) {
                $info = $this->uploadFileLocally($uploadedFile, $chatId, $msgId);
                $msgData['type']      = $info['type'];
                $msgData['file_name'] = $info['file_name'];
                $msgData['file_url']  = $info['file_url'];
                if (!$text) $msgData['text'] = null;
            }

            $msgRef->set($msgData);

            $preview = $msgData['type'] === 'text'
                ? mb_strimwidth($text, 0, 80, '…')
                : ($msgData['file_name'] ?? '[File]');

            // Build the batch update: last_message + increment unread count for each recipient
            $updates = [['path' => 'last_message', 'value' => [
                'text'        => $preview,
                'sender_name' => $userName,
                'sent_at'     => $now,
            ]]];
            foreach ($members as $mUid) {
                if ((string)$mUid !== $uid) {
                    $updates[] = ['path' => 'unread_counts.' . $mUid, 'value' => FieldValue::increment(1)];
                }
            }
            $chatRef->update($updates);

            $this->notifyMembers($members, $uid, $chatId, $userName, $preview, $chatData);

            return $this->response->setJSON(['success' => true, 'id' => $msgId]);
        } catch (\Throwable $e) {
            log_message('error', 'Messages::sendMessage - ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            $detail = ENVIRONMENT === 'development' ? $e->getMessage() : 'Server error';
            return $this->response->setStatusCode(500)->setJSON(['error' => $detail]);
        }
    }

    // ── Private helpers ────────────────────────────────────────────────────

    /** Find or create the project group chat. Delegates to ChatService. */
    private function createOrGetProjectChat(string $projectId): string
    {
        return (new ChatService())->createOrGetProjectChat($projectId);
    }

    /** Build display name from Firestore user data using first_name / last_name (schema-correct). */
    private function buildFullName(array $u): string
    {
        $fn = trim((string)($u['first_name'] ?? ''));
        $ln = trim((string)($u['last_name']  ?? ''));
        $full = trim("$fn $ln");
        return $full ?: trim((string)($u['email'] ?? '')) ?: 'Unknown';
    }

    /** Normalize a Firestore DocumentReference, path string, or bare UID to just the UID. */
    private function extractUid(mixed $value): string
    {
        if ($value === null) return '';
        if ($value instanceof \Google\Cloud\Firestore\DocumentReference) return $value->id();
        if (is_string($value)) {
            $last = ltrim(strrchr($value, '/'), '/');
            return $last ?: $value;
        }
        return '';
    }

    private function uploadFileLocally(\CodeIgniter\HTTP\Files\UploadedFile $file, string $chatId, string $msgId): array
    {
        // getClientName() returns the browser-supplied filename; getName() is the same before move().
        $originalName = (string)($file->getClientName() ?: $file->getName() ?: '');
        if ($originalName === '') $originalName = 'upload_' . $msgId;

        $ext      = pathinfo($originalName, PATHINFO_EXTENSION);
        $safeName = $msgId . ($ext ? '.' . preg_replace('/[^a-zA-Z0-9]/', '', $ext) : '');

        $mimeType = (string)$file->getClientMimeType();
        if (!$mimeType) $mimeType = (string)$file->getMimeType();
        $type = str_starts_with($mimeType, 'image/') ? 'image' : 'file';

        // Ensure the destination directory exists (Windows-safe)
        $dir = rtrim(FCPATH, '/\\')
             . DIRECTORY_SEPARATOR . 'uploads'
             . DIRECTORY_SEPARATOR . 'messages'
             . DIRECTORY_SEPARATOR . $chatId
             . DIRECTORY_SEPARATOR;

        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true) && !is_dir($dir)) {
                throw new \RuntimeException("Cannot create upload directory: {$dir}");
            }
        }

        $file->move($dir, $safeName);

        return [
            'type'      => $type,
            'file_name' => $originalName,
            'file_url'  => base_url('uploads/messages/' . $chatId . '/' . $safeName),
        ];
    }

    private function notifyMembers(
        array  $members,
        string $senderUid,
        string $chatId,
        string $senderName,
        string $preview,
        array  $_chatData
    ): void {
        try {
            $svc   = new NotificationService();
            $title = 'New Message';
            $body  = "{$senderName}: {$preview}";

            foreach ($members as $uid) {
                if ((string)$uid === $senderUid) continue;
                $svc->send((string)$uid, 'messageSent', $title, $body, ['chat_id' => $chatId]);
            }
        } catch (\Throwable $e) {
            log_message('error', 'Messages::notifyMembers - ' . $e->getMessage());
        }
    }

    private function serializeLastMessage(mixed $lm): ?array
    {
        if (!$lm || !is_array($lm)) return null;
        return [
            'text'        => (string)($lm['text']        ?? ''),
            'sender_name' => (string)($lm['sender_name'] ?? ''),
            'sent_at'     => $this->tsToIso($lm['sent_at'] ?? null),
        ];
    }

    private function tsToIso(mixed $value): ?string
    {
        if ($value === null) return null;
        if ($value instanceof Timestamp) {
            try {
                return \DateTimeImmutable::createFromInterface($value->get())
                    ->setTimezone(new \DateTimeZone('Asia/Manila'))
                    ->format('Y-m-d H:i:s');
            } catch (\Throwable) { return null; }
        }
        return is_string($value) ? $value : null;
    }
}
