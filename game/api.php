<?php
/**
 * Live Quiz API — one endpoint, ?a=<action>.
 *
 * Player (no login, identified by a random token kept in localStorage):
 *   join    POST pin, name          → token
 *   state   GET  token              → what the phone should show now (polled ~1s)
 *   answer  POST token, q, answer   → locks the answer in
 *
 * Host (admin/IT login + X-CSRF-Token from host.php):
 *   host_state (GET, polled ~1s), import, create, next, reveal, leaderboard, kick, end
 *
 * Polling actions skip config.php's schema checks (STUDIUM_SKIP_SCHEMA) so ~100
 * phones polling every second stay cheap on shared hosting.
 */

$action = $_GET['a'] ?? '';
if (in_array($action, ['state', 'answer', 'host_state'], true)) {
    define('STUDIUM_SKIP_SCHEMA', true);
}
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/lib.php';
require_once __DIR__ . '/actions.php';

$isPost = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
$in = $_POST;
if ($isPost && stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
    $in = json_decode(file_get_contents('php://input'), true) ?: [];
}

/* ───────────────────────────── Host actions ───────────────────────────── */

if (strpos($action, 'host_') === 0 || in_array($action, ['import', 'create', 'next', 'reveal', 'leaderboard', 'kick', 'end'], true)) {
    if (!lq_is_host()) lq_json_out(['ok' => false, 'error' => 'Please log in to the admin panel first.'], 401);
    // A host key arrives in a custom header, which another site cannot make a browser
    // send, so it needs no CSRF token (and the iframe may not keep a session cookie).
    if ($isPost && !lq_host_key_ok()) {
        $sent = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (empty($_SESSION['lq_csrf']) || !hash_equals($_SESSION['lq_csrf'], $sent)) {
            lq_json_out(['ok' => false, 'error' => 'Your session expired. Reload the page.'], 403);
        }
    }
    session_write_close(); // don't hold the session lock while polling

    switch ($action) {
        case 'host_state':  lq_json_out(host_state());
        case 'import':      lq_json_out(host_import((string) ($in['text'] ?? '')));
        case 'create':      lq_json_out(host_create((string) ($in['title'] ?? '')));
        case 'next':        lq_json_out(host_next());
        case 'reveal':      lq_json_out(host_reveal(true));
        case 'leaderboard':
            $ls = lq_session();
            // After the last question there is no leaderboard: go straight to the winners.
            if ($ls && (int) $ls['q_index'] + 1 >= (int) $ls['q_total']) lq_json_out(host_next());
            lq_json_out(host_set_phase('reveal', 'leaderboard'));
        case 'kick':        lq_json_out(host_kick((int) ($in['id'] ?? 0)));
        case 'end':         lq_wipe_game(); lq_json_out(['ok' => true]);
    }
}

/* ───────────────────────────── Player actions ───────────────────────────── */

session_write_close();
switch ($action) {
    case 'join':   if (!$isPost) break; lq_json_out(player_join((string) ($in['pin'] ?? ''), (string) ($in['name'] ?? ''), (int) ($in['avatar'] ?? 0)));
    case 'state':  lq_json_out(player_state((string) ($_GET['token'] ?? '')));
    case 'answer': if (!$isPost) break; lq_json_out(player_answer((string) ($in['token'] ?? ''), (int) ($in['q'] ?? -1), $in['answer'] ?? null));
}
lq_json_out(['ok' => false, 'error' => 'Unknown action.'], 400);
