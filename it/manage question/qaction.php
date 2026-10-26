<?php
session_start();
include('../../config.php');

$action = $_POST['action'] ?? '';
$type   = $_POST['type']   ?? '';
$id     = (int)($_POST['id'] ?? 0);

/* ── Table map ── */
$tableMap = [
    'traditional' => 'mcq',
    'sata'        => 'sata',
    'mpr'         => 'mpr',
    'mmr'         => 'mmr',
    'btq'         => 'btq',
    'dragndrop'   => 'dragndrop',
    'dropdown'    => 'dropdown_questions',
    'highlight'   => 'highlight',
];

if (!array_key_exists($type, $tableMap) || !in_array($action, ['add','edit','delete'])) {
    flash(false, 'Invalid request.'); redirect($type); exit;
}

$table = $tableMap[$type];

/* ── Helpers ── */
function flash(bool $ok, string $msg) {
    $_SESSION['qflash'] = ['ok'=>$ok,'msg'=>$msg];
}
function redirect(string $type) {
    header('Location: list.php?type=' . urlencode($type));
    exit;
}
function post(string $k): string {
    return trim($_POST[$k] ?? '');
}
function postJson(string $k): string {
    $v = trim($_POST[$k] ?? '');
    // Validate it's JSON; if not, return empty array
    json_decode($v);
    return json_last_error() === JSON_ERROR_NONE ? $v : '[]';
}

/* ── Image upload helper ── */
function handleImageUpload(string $field, ?string $existing = null): ?string {
    if (empty($_FILES[$field]['name'])) return $existing; // keep existing
    $ext   = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
    $allow = ['jpg','jpeg','png','gif','webp'];
    if (!in_array($ext, $allow)) return $existing;
    $dir  = '../../uploads/questions/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $name = uniqid('q_') . '.' . $ext;
    if (move_uploaded_file($_FILES[$field]['tmp_name'], $dir . $name)) {
        return 'uploads/questions/' . $name;
    }
    return $existing;
}

/* ════════════════════════════════════════════════
   DELETE
   ════════════════════════════════════════════════ */
if ($action === 'delete') {
    if (!$id) { flash(false,'Invalid ID.'); redirect($type); }
    $stmt = $con->prepare("DELETE FROM `$table` WHERE id = ?");
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) flash(true,  'Question deleted.');
    else                  flash(false, 'Delete failed: ' . $con->error);
    redirect($type);
}

/* ════════════════════════════════════════════════
   Common fields (all types)
   ════════════════════════════════════════════════ */
$question         = post('question');
$rationale        = post('rationale');
$topic            = post('topic');
$system           = post('system');
$cnc              = post('cnc');
$dlevel           = post('dlevel');
$concept          = post('concept');
$narcan           = post('narcan');
$furtherinfo      = post('furtherinfo');
$difficulty_logit = post('difficulty_logit') !== '' ? (float)post('difficulty_logit') : 0.0;

if (!$question || !$rationale) {
    flash(false, 'Question and Rationale are required.');
    redirect($type);
}

/* ── Fetch existing image for edit ── */
$existingImage = null;
if ($action === 'edit' && $id) {
    $r = mysqli_query($con, "SELECT image FROM `$table` WHERE id=$id LIMIT 1");
    if ($r && $row = mysqli_fetch_assoc($r)) $existingImage = $row['image'];
}
$image = handleImageUpload('image', $existingImage);

/* ════════════════════════════════════════════════
   BUILD type-specific payload
   ════════════════════════════════════════════════ */
$extra_cols   = '';   // additional column names for INSERT/UPDATE
$extra_vals   = '';   // additional value placeholders
$extra_binds  = '';   // bind type string
$extra_params = [];   // params array

switch ($type) {

  /* ── Traditional MCQ ── */
  case 'traditional': {
    $choices = json_encode([
        post('choiceA'), post('choiceB'), post('choiceC'), post('choiceD')
    ]);
    $correct = post('correct'); // A/B/C/D
    if (!$correct) { flash(false,'Please select the correct answer.'); redirect($type); }
    $extra_cols   = ', choices, correct';
    $extra_vals   = ', ?, ?';
    $extra_binds  = 'ss';
    $extra_params = [$choices, $correct];
    break;
  }

  /* ── SATA ── */
  case 'sata': {
    $items_json   = postJson('items_json');
    $correct_json = postJson('correct_json');
    $extra_cols   = ', items, correct';
    $extra_vals   = ', ?, ?';
    $extra_binds  = 'ss';
    $extra_params = [$items_json, $correct_json];
    break;
  }

  /* ── MPR ── */
  case 'mpr': {
    $items   = post('items');   // newline-separated text
    $correct = post('correct'); // comma-sep indices
    $extra_cols   = ', items, correct';
    $extra_vals   = ', ?, ?';
    $extra_binds  = 'ss';
    $extra_params = [$items, $correct];
    break;
  }

  /* ── MMR ── */
  case 'mmr': {
    $columns_json  = postJson('columns_json');
    $rows_json     = postJson('rows_json');
    $correct_json  = postJson('correct_json');
    $nurses_notes  = post('nurses_notes');
    $vital_signs   = post('vital_signs');
    $diagnostics   = post('diagnostics');
    $extra_cols   = ', `columns`, `rows`, correct, nurses_notes, vital_signs, diagnostics';
    $extra_vals   = ', ?, ?, ?, ?, ?, ?';
    $extra_binds  = 'ssssss';
    $extra_params = [$columns_json, $rows_json, $correct_json, $nurses_notes, $vital_signs, $diagnostics];
    break;
  }

  /* ── BTQ ── */
  case 'btq': {
    $actionToTake_json        = postJson('actionToTake_json');
    $potentialConditions_json = postJson('potentialConditions_json');
    $parameterToMonitor_json  = postJson('parameterToMonitor_json');
    $nursesNotes  = post('nursesNotes');
    $vitalSigns   = post('vitalSigns');
    $diagnostics  = post('diagnostics');
    $extra_cols   = ', actionToTake, potentialConditions, parameterToMonitor, nursesNotes, vitalSigns, diagnostics';
    $extra_vals   = ', ?, ?, ?, ?, ?, ?';
    $extra_binds  = 'ssssss';
    $extra_params = [$actionToTake_json, $potentialConditions_json, $parameterToMonitor_json, $nursesNotes, $vitalSigns, $diagnostics];
    break;
  }

  /* ── Drag & Drop ── */
  case 'dragndrop': {
    $items_json   = postJson('items_json');
    $correct_json = postJson('correct_json');
    $extra_cols   = ', items, correct';
    $extra_vals   = ', ?, ?';
    $extra_binds  = 'ss';
    $extra_params = [$items_json, $correct_json];
    break;
  }

  /* ── Dropdown Cloze ── */
  case 'dropdown': {
    $options       = post('options');
    $correct_words = post('correct_words');
    $extra_cols   = ', options, correct_words';
    $extra_vals   = ', ?, ?';
    $extra_binds  = 'ss';
    $extra_params = [$options, $correct_words];
    break;
  }

  /* ── Highlight ── */
  case 'highlight': {
    $passage       = post('passage');
    $options       = post('options');
    $correct_words = post('correct_words');
    $maxHighlights = (int)post('maxHighlights') ?: 2;
    $extra_cols   = ', passage, options, correct_words, maxHighlights';
    $extra_vals   = ', ?, ?, ?, ?';
    $extra_binds  = 'sssi';
    $extra_params = [$passage, $options, $correct_words, $maxHighlights];
    break;
  }

}

/* ════════════════════════════════════════════════
   ADD / EDIT
   ════════════════════════════════════════════════ */
// question(s), rationale(s), topic(s), system(s), cnc(s), dlevel(s), concept(s), difficulty_logit(d), narcan(s), furtherinfo(s), image(s)
$bind_types  = 'sssssssdsss' . $extra_binds;
$base_params = [$question, $rationale, $topic, $system, $cnc, $dlevel, $concept, $difficulty_logit, $narcan, $furtherinfo, $image];
$all_params  = array_merge($base_params, $extra_params);

if ($action === 'add') {
    $sql = "INSERT INTO `$table`
            (question, rationale, topic, `system`, cnc, dlevel, concept, difficulty_logit, narcan, furtherinfo, image
             $extra_cols)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
             $extra_vals)";
    $stmt = $con->prepare($sql);
    if (!$stmt) { flash(false, 'Prepare failed: ' . $con->error); redirect($type); }
    $stmt->bind_param($bind_types, ...$all_params);
    if ($stmt->execute()) flash(true,  'Question added successfully!');
    else                  flash(false, 'Add failed: ' . $stmt->error);

} elseif ($action === 'edit') {
    if (!$id) { flash(false,'Invalid ID.'); redirect($type); }

    $set_base  = 'question=?, rationale=?, topic=?, `system`=?, cnc=?, dlevel=?, concept=?, difficulty_logit=?, narcan=?, furtherinfo=?, image=?';
    $set_extra = '';
    if ($extra_cols) {
        // Build SET clause from extra_cols string
        $col_list = array_map('trim', explode(',', $extra_cols));
        $col_list = array_filter($col_list);
        $set_parts = array_map(fn($c) => "$c=?", $col_list);
        $set_extra = ', ' . implode(', ', $set_parts);
    }
    $sql = "UPDATE `$table` SET $set_base $set_extra WHERE id=?";
    $all_params_edit = array_merge($base_params, $extra_params, [$id]);
    $bind_types_edit = $bind_types . 'i';
    $stmt = $con->prepare($sql);
    if (!$stmt) { flash(false, 'Prepare failed: ' . $con->error); redirect($type); }
    $stmt->bind_param($bind_types_edit, ...$all_params_edit);
    if ($stmt->execute()) flash(true,  'Question updated successfully!');
    else                  flash(false, 'Update failed: ' . $stmt->error);
}

redirect($type);
