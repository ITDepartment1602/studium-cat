<?php
/**
 * question_preview.php — Opens the real student question pages with a question that is not
 * in the bank yet (an import paste, an approval queue row or an unsaved manual form).
 *
 * The admin side stores the exact row a save would write, and the student page swaps it in
 * for its own database row, so the preview is the page students get and not a copy of it.
 * Nothing is written to a question table, and only an admin session can trigger the swap.
 */

/** Student page folder under student/dashboard/ngn/ for each admin question type. */
const QUESTION_PREVIEW_FOLDERS = [
    'traditional' => 'traditional',
    'sata'        => 'sata',
    'mpr'         => 'mpr',
    'mmr'         => 'mmr',
    'btq'         => 'bowtie',
    'dragndrop'   => 'dragndrop',
    'dropdown'    => 'dropdown',
    'highlight'   => 'highlight',
];

/**
 * Holds a candidate row for the next preview and returns the student page URL, relative to
 * admin/manage question/. The id of -1 matches no question, and gets past the pages that
 * refuse to load without an id.
 */
function storeQuestionPreview(string $type, array $row): string {
    $_SESSION['question_preview'][$type] = $row + ['id' => 0];
    return '../../student/dashboard/ngn/' . QUESTION_PREVIEW_FOLDERS[$type] . '/index.php?id=-1&preview=1';
}

/** The row a student page should show instead of its own, or null on a normal page load. */
function questionPreviewRow(string $type): ?array {
    if (!isset($_GET['preview'])) return null;
    if (!isset($_SESSION['admin_id']) && !isset($_SESSION['admin_login'])) return null;
    return $_SESSION['question_preview'][$type] ?? null;
}
