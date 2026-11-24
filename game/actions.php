<?php
/**
 * Live Quiz — host and player actions (called by api.php).
 * Kept apart from the router so they can be exercised without HTTP.
 */

/* ═════════════════════════════ Host ═════════════════════════════ */

function host_questions_summary(): array {
    $rows = db()->fetchAll("SELECT id, q_order, q_type, question, time_limit FROM live_quiz_questions ORDER BY q_order, id");
    return array_map(fn($r) => [
        'order'      => (int) $r['q_order'],
        'type'       => $r['q_type'],
        'type_label' => LQ_TYPES[$r['q_type']] ?? $r['q_type'],
        'question'   => mb_strimwidth($r['question'], 0, 140, '…'),
        'time_limit' => (int) $r['time_limit'],
    ], $rows);
}

function host_import(string $text): array {
    $s = lq_session();
    if ($s && $s['phase'] !== 'lobby') return ['ok' => false, 'errors' => ['A game is running. End it first before loading new questions.']];

    $parsed = lq_parse_paste($text);
    if ($parsed['errors']) return ['ok' => false, 'errors' => $parsed['errors']];
    if (count($parsed['rows']) > LQ_MAX_QUESTION) return ['ok' => false, 'errors' => ['At most ' . LQ_MAX_QUESTION . ' questions per game (got ' . count($parsed['rows']) . ').']];

    $clean = []; $errors = [];
    foreach ($parsed['rows'] as $i => $row) {
        [$c, $e] = lq_validate_question($row, $i + 1);
        $clean[] = $c;
        array_push($errors, ...$e);
    }
    if ($errors) return ['ok' => false, 'errors' => $errors];

    $con = db()->getConnection();
    $con->begin_transaction();
    $ok = db()->execute("DELETE FROM live_quiz_questions");
    foreach ($clean as $c) {
        $ok = $ok && db()->execute(
            "INSERT INTO live_quiz_questions (q_order, q_type, scenario, question, options, answer, rationale, time_limit) VALUES (?,?,?,?,?,?,?,?)",
            [$c['q_order'], $c['q_type'], $c['scenario'], $c['question'],
             json_encode($c['options'], JSON_UNESCAPED_UNICODE), json_encode($c['answer'], JSON_UNESCAPED_UNICODE),
             $c['rationale'], $c['time_limit']]
        );
    }
    if (!$ok) { $con->rollback(); return ['ok' => false, 'errors' => ['Saving the questions failed. Nothing was changed.']]; }
    $con->commit();

    if ($s) db()->execute("UPDATE live_quiz_session SET q_total = ? WHERE id = 1", [count($clean)]);
    return ['ok' => true, 'count' => count($clean), 'questions' => host_questions_summary()];
}

function host_create(string $title): array {
    $total = (int) (db()->fetchOne("SELECT COUNT(*) AS c FROM live_quiz_questions")['c'] ?? 0);
    if ($total === 0) return ['ok' => false, 'error' => 'Load questions first.'];
    $title = trim(strip_tags($title)) ?: 'NCLEX Amplified Live Quiz';
    $title = mb_substr($title, 0, 150);

    lq_wipe_game();
    $pin = (string) random_int(100000, 999999);
    db()->execute(
        "INSERT INTO live_quiz_session (id, pin, title, phase, q_index, q_total, phase_start, q_ends) VALUES (1, ?, ?, 'lobby', -1, ?, ?, 0)",
        [$pin, $title, $total, lq_now_ms()]
    );
    return ['ok' => true, 'pin' => $pin];
}

/** Lobby/leaderboard/reveal → next question, or → podium after the last one. */
function host_next(): array {
    $s = lq_session();
    if (!$s || !in_array($s['phase'], ['lobby', 'reveal', 'leaderboard'], true)) return ['ok' => false, 'error' => 'Not ready for the next question.'];

    $next = (int) $s['q_index'] + 1;
    if ($next >= (int) $s['q_total']) {
        db()->execute("UPDATE live_quiz_session SET phase = 'podium', phase_start = ? WHERE id = 1", [lq_now_ms()]);
        return ['ok' => true, 'phase' => 'podium'];
    }
    if ($s['phase'] === 'lobby') {
        $c = (int) (db()->fetchOne("SELECT COUNT(*) AS c FROM live_quiz_players")['c'] ?? 0);
        if ($c === 0) return ['ok' => false, 'error' => 'Wait for at least one player to join.'];
    }
    $q = lq_question_at($next);
    if (!$q) return ['ok' => false, 'error' => 'Question not found.'];

    // Snapshot ranks so the leaderboard can show who moved up after this question
    $players = db()->fetchAll("SELECT id FROM live_quiz_players ORDER BY " . LQ_RANK_ORDER);
    foreach ($players as $pos => $p) {
        db()->execute("UPDATE live_quiz_players SET prev_rank = ? WHERE id = ?", [$pos + 1, (int) $p['id']]);
    }

    // The clock starts after a short "Get ready 3-2-1" intro on every screen
    $start = lq_now_ms() + LQ_INTRO_MS;
    db()->execute(
        "UPDATE live_quiz_session SET phase = 'question', q_index = ?, phase_start = ?, q_ends = ? WHERE id = 1",
        [$next, $start, $start + (int) $q['time_limit'] * 1000]
    );
    return ['ok' => true, 'phase' => 'question'];
}

/** question → reveal. Idempotent: only the first caller does the streak bookkeeping. */
function host_reveal(bool $force): array {
    $s = lq_session();
    if (!$s || $s['phase'] !== 'question') return ['ok' => false, 'error' => 'No question is open.'];
    $con = db()->getConnection();
    db()->execute("UPDATE live_quiz_session SET phase = 'reveal', phase_start = ? WHERE id = 1 AND phase = 'question' AND q_index = ?", [lq_now_ms(), (int) $s['q_index']]);
    if ($con->affected_rows === 1) {
        $qi = (int) $s['q_index'];
        // Anyone without a fully-correct answer on this question loses their streak
        db()->execute(
            "UPDATE live_quiz_players SET streak = 0 WHERE id NOT IN (SELECT player_id FROM live_quiz_answers WHERE q_index = ? AND fraction >= 1)",
            [$qi]
        );
        // No answer counts as using the whole time (score tie-breaker)
        $q = lq_question_at($qi);
        db()->execute(
            "UPDATE live_quiz_players SET time_ms = time_ms + ? WHERE id NOT IN (SELECT player_id FROM live_quiz_answers WHERE q_index = ?)",
            [(int) ($q['time_limit'] ?? 30) * 1000, $qi]
        );
    }
    return ['ok' => true, 'phase' => 'reveal'];
}

function host_set_phase(string $from, string $to): array {
    db()->execute("UPDATE live_quiz_session SET phase = ?, phase_start = ? WHERE id = 1 AND phase = ?", [$to, lq_now_ms(), $from]);
    return ['ok' => true, 'phase' => $to];
}

function host_kick(int $id): array {
    db()->execute("DELETE FROM live_quiz_answers WHERE player_id = ?", [$id]);
    db()->execute("DELETE FROM live_quiz_players WHERE id = ?", [$id]);
    return ['ok' => true];
}

function host_state(): array {
    $now = lq_now_ms();
    $s = lq_session();
    $out = ['ok' => true, 'now' => $now, 'v' => LQ_VERSION];
    if (!$s) {
        $out['phase'] = 'setup';
        $out['questions'] = host_questions_summary();
        return $out;
    }

    $qi = (int) $s['q_index'];
    $playerCount = (int) (db()->fetchOne("SELECT COUNT(*) AS c FROM live_quiz_players")['c'] ?? 0);

    // Close the question when time is up (plus grace) or everyone has answered
    if ($s['phase'] === 'question') {
        $answered = (int) (db()->fetchOne("SELECT COUNT(*) AS c FROM live_quiz_answers WHERE q_index = ?", [$qi])['c'] ?? 0);
        if ($now >= (int) $s['q_ends'] + LQ_GRACE_MS || ($playerCount > 0 && $answered >= $playerCount)) {
            host_reveal(false);
            $s = lq_session();
        }
    }

    $out += [
        'phase'   => $s['phase'],
        'pin'     => $s['pin'],
        'title'   => $s['title'],
        'q_index' => $qi,
        'q_total' => (int) $s['q_total'],
        'q_ends'  => (int) $s['q_ends'],
        'phase_start' => (int) $s['phase_start'],
        'players' => $playerCount,
    ];

    switch ($s['phase']) {
        case 'lobby':
            $out['player_list'] = array_map(fn($r) => ['id' => (int) $r['id'], 'name' => $r['name'], 'avatar' => (int) $r['avatar']],
                db()->fetchAll("SELECT id, name, avatar FROM live_quiz_players ORDER BY id"));
            break;

        case 'question':
        case 'reveal':
            $q = lq_question_at($qi);
            $out['question'] = lq_public_question($q, $qi, (int) $s['q_total']);
            $answers = db()->fetchAll("SELECT answer, fraction FROM live_quiz_answers WHERE q_index = ?", [$qi]);
            $out['answered'] = count($answers);
            if ($s['phase'] === 'reveal') {
                $out['answer']    = $q['answer'];
                $out['rationale'] = $q['rationale'];
                $out['stats']     = reveal_stats($q, $answers, $playerCount);
            }
            break;

        case 'leaderboard':
            $out['leaderboard'] = leaderboard_rows(5, $qi);
            break;

        case 'podium':
            $out['leaderboard'] = leaderboard_rows(10, $qi);
            break;
    }
    return $out;
}

function reveal_stats(array $q, array $answers, int $playerCount): array {
    $full = 0; $partial = 0; $wrong = 0; $counts = [];
    foreach ($answers as $a) {
        $f = (float) $a['fraction'];
        if ($f >= 1) $full++; elseif ($f > 0) $partial++; else $wrong++;
        $given = json_decode($a['answer'], true);
        switch ($q['q_type']) {
            case 'mc':        if (is_string($given)) $counts[$given] = ($counts[$given] ?? 0) + 1; break;
            case 'sata':
            case 'highlight': foreach ((array) $given as $k) $counts[$k] = ($counts[$k] ?? 0) + 1; break;
        }
    }
    return [
        'full'       => $full,
        'partial'    => $partial,
        'wrong'      => $wrong,
        'no_answer'  => max(0, $playerCount - count($answers)),
        'counts'     => $counts,
    ];
}

function leaderboard_rows(int $limit, int $qi): array {
    $rows = db()->fetchAll(
        "SELECT p.id, p.name, p.avatar, p.score, p.streak, p.prev_rank, COALESCE(a.points, 0) AS last_points
           FROM live_quiz_players p
           LEFT JOIN live_quiz_answers a ON a.player_id = p.id AND a.q_index = ?
          ORDER BY p.score DESC, p.time_ms ASC, p.id ASC
          LIMIT " . (int) $limit,
        [$qi]
    );
    foreach ($rows as $pos => &$r) {
        $r['rank'] = $pos + 1;
        foreach (['id', 'avatar', 'score', 'streak', 'prev_rank', 'last_points'] as $k) $r[$k] = (int) $r[$k];
    }
    return $rows;
}


/* ═════════════════════════════ Player ═════════════════════════════ */

function player_join(string $pin, string $name, int $avatar = 0): array {
    $s = lq_session();
    $pin = preg_replace('/\D/', '', $pin);
    if (!$s || $pin !== $s['pin']) return ['ok' => false, 'error' => 'That PIN doesn\'t match a running game. Check the screen and try again.'];
    if (in_array($s['phase'], ['podium'], true)) return ['ok' => false, 'error' => 'This game has already finished.'];

    $name = trim(preg_replace('/\s+/u', ' ', strip_tags($name)));
    if (!preg_match('/^[\p{L}\p{N} ._\'-]{2,20}$/u', $name)) {
        return ['ok' => false, 'error' => 'Use 2–20 letters or numbers for your name.'];
    }
    $count = (int) (db()->fetchOne("SELECT COUNT(*) AS c FROM live_quiz_players")['c'] ?? 0);
    if ($count >= 300) return ['ok' => false, 'error' => 'The game is full.'];

    $token = bin2hex(random_bytes(16));
    // Late joiners start just behind the current question so rank snapshots still work
    $avatar = ($avatar >= 1 && $avatar <= 6) ? $avatar : 0;
    if (!db()->execute("INSERT INTO live_quiz_players (name, token, avatar) VALUES (?, ?, ?)", [$name, $token, $avatar])) {
        return ['ok' => false, 'error' => 'Someone already took that name. Try another one.'];
    }
    return ['ok' => true, 'token' => $token, 'name' => $name, 'avatar' => $avatar];
}

function player_state(string $token): array {
    $now = lq_now_ms();
    $s = lq_session();
    if (!$s) return ['ok' => true, 'now' => $now, 'v' => LQ_VERSION, 'phase' => 'none'];

    $p = $token !== '' ? db()->fetchOne("SELECT id, name, avatar, score, streak, prev_rank, time_ms FROM live_quiz_players WHERE token = ?", [$token]) : null;
    if (!$p) return ['ok' => true, 'now' => $now, 'v' => LQ_VERSION, 'phase' => 'join', 'pin_known' => true];

    $qi = (int) $s['q_index'];
    $score = (int) $p['score'];
    $out = [
        'ok'      => true,
        'now'     => $now,
        'v'       => LQ_VERSION,
        'phase'   => $s['phase'],
        'title'   => $s['title'],
        'name'    => $p['name'],
        'avatar'  => (int) $p['avatar'],
        'score'   => $score,
        'streak'  => (int) $p['streak'],
        'q_index' => $qi,
        'q_total' => (int) $s['q_total'],
        'phase_start' => (int) $s['phase_start'],
    ];

    if (in_array($s['phase'], ['question', 'reveal', 'leaderboard', 'podium'], true)) {
        $out['rank'] = lq_rank_of($p);
        $out['players'] = (int) (db()->fetchOne("SELECT COUNT(*) AS c FROM live_quiz_players")['c'] ?? 0);
    }

    if ($s['phase'] === 'question' || $s['phase'] === 'reveal') {
        $q = lq_question_at($qi);
        $mine = db()->fetchOne("SELECT answer, fraction, points FROM live_quiz_answers WHERE player_id = ? AND q_index = ?", [(int) $p['id'], $qi]);
        $out['answered'] = (bool) $mine;
        if ($s['phase'] === 'question') {
            // Don't give away whether the locked answer was right before the reveal
            if ($mine) {
                $out['score'] = $score - (int) $mine['points'];
                $out['mine']  = json_decode($mine['answer'], true);   // their own picks, to keep them on screen
                $out['answered_count'] = (int) (db()->fetchOne("SELECT COUNT(*) AS c FROM live_quiz_answers WHERE q_index = ?", [$qi])['c'] ?? 0);
            }
            $out['question'] = lq_public_question($q, $qi, (int) $s['q_total']);
            $out['q_ends'] = (int) $s['q_ends'];
        } else {
            $out['result'] = [
                'answered' => (bool) $mine,
                'fraction' => $mine ? (float) $mine['fraction'] : 0,
                'points'   => $mine ? (int) $mine['points'] : 0,
            ];
            $out['question']  = lq_public_question($q, $qi, (int) $s['q_total']);
            $out['answer']    = $q['answer'];
            $out['mine']      = $mine ? json_decode($mine['answer'], true) : null;
            $out['rationale'] = $q['rationale'];
        }
    }
    if ($s['phase'] === 'leaderboard' && $qi + 1 < (int) $s['q_total']) {
        $out['prev_rank'] = (int) $p['prev_rank'];
        $out['top'] = leaderboard_rows(5, $qi);
    }
    // Podium: phones get only their own place (no winners list), by design.
    return $out;
}

function player_answer(string $token, int $qi, $answer): array {
    $now = lq_now_ms();
    $s = lq_session();
    if (!$s || $s['phase'] !== 'question' || (int) $s['q_index'] !== $qi) return ['ok' => false, 'error' => 'This question is already closed.'];
    if ($now > (int) $s['q_ends'] + LQ_GRACE_MS) return ['ok' => false, 'error' => 'Time\'s up!'];
    if ($now < (int) $s['phase_start'] - 500) return ['ok' => false, 'error' => 'Get ready — the question hasn\'t started yet.'];

    $p = db()->fetchOne("SELECT id, streak FROM live_quiz_players WHERE token = ?", [$token]);
    if (!$p) return ['ok' => false, 'error' => 'You are no longer in this game.'];

    $q = lq_question_at($qi);
    if (is_string($answer) && $q['q_type'] !== 'mc') $answer = json_decode($answer, true);
    $given = lq_clean_answer($q['q_type'], $q['options'], $answer);
    $fraction = round(lq_score($q['q_type'], $q['answer'], $given), 3);
    $elapsed = max(0, $now - (int) $s['phase_start']);
    $points = lq_points($fraction, $elapsed, (int) $q['time_limit']);
    $streak = $fraction >= 1 ? (int) $p['streak'] + 1 : 0;
    $points += $fraction >= 1 ? lq_streak_bonus($streak) : 0;

    $saved = db()->execute(
        "INSERT INTO live_quiz_answers (player_id, q_index, answer, fraction, points, ms) VALUES (?,?,?,?,?,?)",
        [(int) $p['id'], $qi, json_encode($given, JSON_UNESCAPED_UNICODE), $fraction, $points, $elapsed]
    );
    if (!$saved) return ['ok' => false, 'error' => 'You already answered this question.'];

    db()->execute("UPDATE live_quiz_players SET score = score + ?, streak = ?, time_ms = time_ms + ? WHERE id = ?", [$points, $streak, $elapsed, (int) $p['id']]);
    return ['ok' => true];
}
