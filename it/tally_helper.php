<?php
date_default_timezone_set('Asia/Manila');

function tally_file_path() {
    return __DIR__ . '/tally_' . date('Y-m-d') . '.json';
}

function tally_read() {
    $f = tally_file_path();
    if (!file_exists($f)) return null;
    $raw = file_get_contents($f);
    return ($raw !== false && $raw !== '') ? json_decode($raw, true) : null;
}

function tally_create($morningCount) {
    $data = [
        'date'               => date('Y-m-d'),
        'morning_count'      => (int)$morningCount,
        'added_1month'       => 0,
        'added_2months'      => 0,
        'added_3months'      => 0,
        'added_6months'      => 0,
        'added_12months'     => 0,
        'added_1month_free'  => 0,
        'added_2months_free' => 0,
        'deleted_today'      => 0,
    ];
    file_put_contents(tally_file_path(), json_encode($data), LOCK_EX);
    return $data;
}

// Atomically increment multiple keys in one file open/close cycle
function tally_increment(array $increments) {
    $f = tally_file_path();
    if (!file_exists($f)) return;

    $fp = fopen($f, 'r+');
    if (!$fp) return;

    flock($fp, LOCK_EX);

    $raw = '';
    while (!feof($fp)) $raw .= fread($fp, 8192);
    $data = ($raw !== '') ? json_decode($raw, true) : null;

    if (is_array($data)) {
        foreach ($increments as $key => $amount) {
            if (!isset($data[$key])) $data[$key] = 0;
            $data[$key] += (int)$amount;
        }
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($data));
        fflush($fp);
    }

    flock($fp, LOCK_UN);
    fclose($fp);
}
