<?php
session_start();
include '../../../config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$examTaken = isset($_GET['examTaken']) ? mysqli_real_escape_string($con, $_GET['examTaken']) : die("No exam selected!");

// 1. Fetch Student Info
$user_query = mysqli_query($con, "SELECT studentnumber, fullname FROM login WHERE id = '$user_id'");
$user_data = mysqli_fetch_assoc($user_query);
$display_student_no = $user_data['studentnumber'] ?? 'N/A';
$display_fullname = $user_data['fullname'] ?? 'Student';

// 2. Fetch Results and Calculate Stats
$stats_query = mysqli_query($con, "SELECT isCorrect, score, topic FROM exam_results WHERE student_id='$user_id' AND examTaken='$examTaken'");
$stats = ['total' => 0, 'correct' => 0, 'wrong' => 0, 'total_score' => 0, 'topics' => []];

while ($s_row = mysqli_fetch_assoc($stats_query)) {
    $stats['total']++;
    $score = floatval($s_row['score']);
    $stats['total_score'] += $score;
    
    // NGN standard: fully correct if score is 1.00
    if ($score >= 1.00) $stats['correct']++;
    else $stats['wrong']++;

    $topic = $s_row['topic'] ?: 'General';
    if (!isset($stats['topics'][$topic])) {
        $stats['topics'][$topic] = ['score' => 0, 'total' => 0];
    }
    $stats['topics'][$topic]['total']++;
    $stats['topics'][$topic]['score'] += $score;
}

$overall_percent = ($stats['total'] > 0) ? round(($stats['total_score'] / $stats['total']) * 100) : 0;

// 3. Fetch the Main Results List
$results_query = mysqli_query($con, "SELECT * FROM exam_results WHERE student_id='$user_id' AND examTaken='$examTaken' ORDER BY question_number ASC");

function getTypeColor($type) {
    $colors = [
        'highlight' => '#f59e0b',
        'bowtie' => '#8b5cf6',
        'mmr' => '#06b6d4',
        'mpr' => '#10b981',
        'dragndrop' => '#ec4899',
        'dropdown' => '#6366f1',
        'sata' => '#14b8a6',
        'column' => '#f97316',
        'traditional' => '#64748b'
    ];
    return $colors[$type] ?? '#94a3b8';
}

function table_exists($con, $table) {
    $safe = mysqli_real_escape_string($con, $table);
    $res = mysqli_query($con, "SHOW TABLES LIKE '$safe'");
    return $res && mysqli_num_rows($res) > 0;
}

function resolveQuestionTable($con, $questionType) {
    $type = strtolower(trim((string) $questionType));
    $candidatesByType = [
        'bowtie' => ['btq'],
        'btq' => ['btq'],
        'dropdown' => ['dropdown', 'dropdown_questions'],
        'highlight' => ['highlight'],
        'mmr' => ['mmr'],
        'mpr' => ['mpr'],
        'dragndrop' => ['dragndrop'],
        'sata' => ['sata'],
        'column' => ['column'],
        'traditional' => ['traditional'],
    ];

    $candidates = $candidatesByType[$type] ?? [$type];
    foreach ($candidates as $table) {
        if (table_exists($con, $table)) return $table;
    }
    return null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Grid — Attempt #<?php echo htmlspecialchars($examTaken); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.5.0/chart.umd.min.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; color: #0f172a; }
        .data-grid-container { max-height: 70vh; overflow-y: auto; border-radius: 12px; }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        .type-pill { 
            font-size: 10px; font-weight: 800; text-transform: uppercase; 
            padding: 2px 8px; border-radius: 100px; display: inline-flex; 
            align-items: center; border: 1px solid rgba(0,0,0,0.1);
        }
        #viewModal { display: none; }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="pb-10">

    <header class="bg-[#0f172a] text-white py-8 px-6 shadow-xl mb-8 relative overflow-hidden">
        <div class="max-w-[1600px] mx-auto flex justify-between items-center relative z-10">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight">Practice Exam Review</h1>
                <div class="flex items-center gap-4 mt-1 opacity-70 text-sm font-medium">
                    <span>Attempt ID: #<?php echo htmlspecialchars($examTaken); ?></span>
                    <span class="w-1 h-1 rounded-full bg-white/30"></span>
                    <span>Student: <?php echo htmlspecialchars($display_fullname); ?></span>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <div class="bg-white/10 px-4 py-2 rounded-xl backdrop-blur-md border border-white/10">
                    <span class="text-xs font-bold opacity-60 uppercase tracking-widest block mb-1">Student No</span>
                    <span class="text-lg font-mono font-bold"><?php echo htmlspecialchars($display_student_no); ?></span>
                </div>
                <a href="result.php" class="bg-blue-600 hover:bg-blue-700 p-3 rounded-xl transition-all shadow-lg shadow-blue-900/40">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </a>
            </div>
        </div>
        <div class="absolute right-0 top-0 w-64 h-64 bg-blue-600/10 rounded-full blur-3xl -mr-32 -mt-32"></div>
    </header>

    <main class="max-w-[1600px] mx-auto px-6">
        
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <!-- Score Card -->
            <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-200 flex flex-col items-center justify-center text-center">
                <div class="relative w-28 h-28 mb-4 text-center">
                    <canvas id="scoreChart"></canvas>
                    <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                        <span class="text-2xl font-black text-slate-800 leading-none"><?php echo $overall_percent; ?>%</span>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">Mastery</span>
                    </div>
                </div>
                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-1">Total Score</p>
                <h2 class="text-xl font-bold text-slate-900 leading-none"><?php echo round($stats['total_score'], 1); ?> <span class="text-sm font-semibold text-slate-300">/ <?php echo $stats['total']; ?></span></h2>
            </div>

            <!-- Stats Mini Cards -->
            <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-200 flex flex-col justify-between">
                <div>
                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-6">Execution Breakdown</p>
                    <div class="flex items-center gap-6 mb-8">
                        <div>
                            <span class="block text-4xl font-extrabold text-green-500 leading-none"><?php echo $stats['correct']; ?></span>
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1 block">Perfect</span>
                        </div>
                        <div class="w-px h-10 bg-slate-100"></div>
                        <div>
                            <span class="block text-4xl font-extrabold text-amber-500 leading-none"><?php echo $stats['total'] - $stats['correct']; ?></span>
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1 block">Partial</span>
                        </div>
                    </div>
                </div>
                <div>
                  <div class="h-4 w-full bg-slate-100 rounded-full overflow-hidden flex shadow-inner">
                      <div style="width: <?php echo ($stats['total'] > 0 ? ($stats['correct']/$stats['total'])*100 : 0); ?>%" class="bg-gradient-to-r from-green-400 to-green-500"></div>
                      <div style="width: <?php echo ($stats['total'] > 0 ? (($stats['total']-$stats['correct'])/$stats['total'])*100 : 0); ?>%" class="bg-gradient-to-r from-amber-400 to-amber-500 border-l border-white/20"></div>
                  </div>
                  <p class="text-[10px] font-bold text-slate-400 mt-3 text-center uppercase tracking-tighter">Consistency Distribution</p>
                </div>
            </div>

            <!-- Topic Performance (Text Grid) -->
            <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-200 md:col-span-2">
                <div class="flex justify-between items-center mb-6">
                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-widest">Mastery by Category</p>
                    <span class="text-[10px] font-bold text-blue-600 bg-blue-50 px-2.5 py-1 rounded-lg uppercase"><?php echo count($stats['topics']); ?> Analyzed</span>
                </div>
                <div class="grid grid-cols-2 gap-4 max-h-[160px] overflow-y-auto custom-scrollbar pr-2">
                    <?php 
                    foreach($stats['topics'] as $topic => $tstats): 
                        $pct = ($tstats['total'] > 0) ? round(($tstats['score'] / $tstats['total']) * 100) : 0;
                        $colorClass = $pct >= 80 ? 'text-green-600 bg-green-50' : ($pct >= 50 ? 'text-amber-600 bg-amber-50' : 'text-red-600 bg-red-50');
                    ?>
                    <div class="flex items-center justify-between p-4 rounded-2xl bg-slate-50 border border-slate-100/50">
                        <span class="text-[12px] font-bold text-slate-600 truncate max-w-[70%]" title="<?php echo htmlspecialchars($topic); ?>"><?php echo htmlspecialchars($topic); ?></span>
                        <span class="text-xs font-black px-2 py-1 rounded-lg <?php echo $colorClass; ?>"><?php echo $pct; ?>%</span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="data-grid-container custom-scrollbar overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[1200px]">
                    <thead class="bg-slate-50 sticky top-0 z-10">
                        <tr class="text-[11px] uppercase tracking-widest text-slate-500 font-bold border-b border-slate-200">
                            <th class="p-4 w-12 text-center">#</th>
                            <th class="p-4 w-32">NGN Metrics</th>
                            <th class="p-4 w-1/3">Question Detail</th>
                            <th class="p-4">Topic / Category</th>
                            <th class="p-4">Your Answer</th>
                            <th class="p-4">Omitted/Initial</th>
                            <th class="p-4">Correct Key</th>
                            <th class="p-4">Analysis / Rationale</th>
                            <th class="p-4 text-center w-24">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-[13px]">
                        <?php
if (mysqli_num_rows($results_query) > 0):
    while ($row = mysqli_fetch_assoc($results_query)):
        $score = floatval($row['score']);
        $isPerfect = $score >= 1.00;
        $scorePercent = round($score * 100);

        $uAnsRaw = json_decode($row['user_answer'], true);
        $cAnsRaw = json_decode($row['correct_answer'], true);
        $initialAnsRaw = isset($row['initial_answer']) && !empty($row['initial_answer']) ? json_decode($row['initial_answer'], true) : null;
        $changesData = isset($row['changes']) && !empty($row['changes']) ? json_decode($row['changes'], true) : null;

        $formatAns = function ($val) use (&$formatAns) {
            if (is_array($val)) {
                $keys = array_keys($val);
                $isAssoc = !empty($keys) && ($keys !== range(0, count($val) - 1));
                $parts = [];
                foreach ($val as $k => $v) {
                    $item = is_array($v) ? $formatAns($v) : htmlspecialchars((string)$v);
                    $parts[] = $isAssoc ? "<strong>$k</strong>: $item" : $item;
                }
                return implode("<br>", $parts);
            }
            if (is_bool($val)) return $val ? 'True' : 'False';
            return htmlspecialchars((string)($val ?: 'N/A'));
        };

        $uAnsDisplay = $formatAns($uAnsRaw);
        $cAnsDisplay = $formatAns($cAnsRaw);

        // Fetch question content dynamically
        $qTypeTable = resolveQuestionTable($con, $row['question_type']);
        $rawUid = $row['question_uid'];
        if (strpos($rawUid, '-') !== false) {
            $parts = explode('-', $rawUid);
            $actualId = end($parts);
        } else {
            $actualId = $rawUid;
        }

        $displayQuestion = "Question content placeholder (ID: $actualId)";
        $displayRationale = isset($row['rationale']) ? $row['rationale'] : "No rationale provided.";
        $displayCNC = isset($row['cnc']) ? $row['cnc'] : "N/A";

        if (!empty($qTypeTable) && !empty($actualId)) {
            $safeTable = mysqli_real_escape_string($con, $qTypeTable);
            $safeId = mysqli_real_escape_string($con, $actualId);
            $q_lookup = mysqli_query($con, "SELECT * FROM `$safeTable` WHERE id = '$safeId' LIMIT 1");
            if ($q_lookup && $q_data = mysqli_fetch_assoc($q_lookup)) {
                $displayQuestion = $q_data['question'] ?? ($q_data['passage'] ?? $displayQuestion);
                if (empty(trim((string) $displayRationale)) || $displayRationale === "No rationale provided." || $displayRationale === "No rationale available.") {
                    $displayRationale = $q_data['rationale'] ?? $displayRationale;
                }
                $displayCNC = $q_data['cnc'] ?? $displayCNC;
            }
        }

        $typeColor = getTypeColor($row['question_type']);
?>
                        <tr class="hover:bg-slate-50 transition-colors group">
                            <td class="p-4 text-center font-bold text-slate-300"><?php echo $row['question_number']; ?></td>
                            <td class="p-4">
                                <div class="flex flex-col gap-1.5">
                                    <span class="type-pill" style="color: <?php echo $typeColor; ?>; background: <?php echo $typeColor; ?>10; border-color: <?php echo $typeColor; ?>20;">
                                        <?php echo htmlspecialchars($row['question_type']); ?>
                                    </span>
                                    <div class="flex items-center gap-2">
                                        <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
                                            <div class="h-full <?php echo $score >= 0.75 ? 'bg-green-500' : ($score >= 0.5 ? 'bg-amber-500' : 'bg-red-500'); ?>" 
                                                 style="width: <?php echo $scorePercent; ?>%"></div>
                                        </div>
                                        <span class="text-[10px] font-extrabold <?php echo $score >= 0.75 ? 'text-green-600' : ($score >= 0.5 ? 'text-amber-600' : 'text-red-600'); ?>">
                                            <?php echo $scorePercent; ?>%
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td class="p-4">
                                <div class="leading-relaxed text-slate-800 font-semibold line-clamp-3" title="<?php echo htmlspecialchars($displayQuestion); ?>">
                                    <?php echo htmlspecialchars($displayQuestion); ?>
                                </div>
                            </td>
                            <td class="p-4">
                                <span class="block font-bold text-[#0f172a]"><?php echo htmlspecialchars($row['topic'] ?: 'General'); ?></span>
                                <span class="block text-[10px] text-slate-400 font-medium uppercase mt-1">CNC: <?php echo htmlspecialchars($displayCNC); ?></span>
                            </td>
                            <td class="p-4">
                                <div class="p-2.5 rounded-xl border <?php echo $isPerfect ? 'bg-green-50 border-green-100 text-green-800' : 'bg-slate-50 border-slate-100 text-slate-600'; ?> font-semibold text-[12px] break-words">
                                    <?php echo $uAnsDisplay; ?>
                                </div>
                            </td>
                            <td class="p-4">
                                <?php if ($initialAnsRaw && $changesData && $changesData['changed']): ?>
                                    <div class="p-2.5 rounded-xl border border-amber-200 bg-amber-50 text-amber-800 font-semibold text-[12px] break-words" style="text-decoration: line-through; opacity: 0.8;">
                                        <div class="text-[10px] font-bold uppercase text-amber-600 mb-1">Initial Answer:</div>
                                        <?php echo $formatAns($initialAnsRaw); ?>
                                    </div>
                                <?php else: ?>
                                    <div class="p-2.5 rounded-xl border border-slate-200 bg-slate-100 text-slate-500 font-semibold text-[12px] text-center">
                                        —
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="p-4">
                                <div class="p-2.5 rounded-xl bg-blue-50 border border-blue-100 text-blue-800 font-bold text-[12px] break-words">
                                    <?php echo $cAnsDisplay; ?>
                                </div>
                            </td>
                            <td class="p-4">
                                <div class="text-slate-500 text-[12px] leading-relaxed italic" title="<?php echo htmlspecialchars($displayRationale); ?>">
                                    <?php echo nl2br($displayRationale); ?>
                                </div>
                            </td>
                            <td class="p-4 text-center">
                                <button class="btn-view inline-flex items-center justify-center w-10 h-10 rounded-xl bg-slate-100 text-slate-600 hover:bg-blue-600 hover:text-white transition-all shadow-sm"
                                        onclick='viewQuestion(<?php echo json_encode([
                                            "type" => $row["question_type"],
                                            "id" => $actualId,
                                            "answer" => $uAnsRaw,
                                            "correct_answer" => $cAnsRaw,
                                            "initial_answer" => $initialAnsRaw,
                                            "changes" => $changesData,
                                            "score" => $score,
                                            "earned_points" => $row["earned_points"] ?? 0,
                                            "max_points" => $row["max_points"] ?? 0,
                                            "rationale" => $displayRationale
                                        ]); ?>)'>
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </button>
                            </td>
                        </tr>
                        <?php
    endwhile;
else:
?>
                        <tr>
                            <td colspan="7" class="p-20 text-center">
                                <div class="flex flex-col items-center gap-3 text-slate-400">
                                    <svg class="w-12 h-12 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    <span class="font-bold uppercase tracking-widest text-xs">No records found for this attempt</span>
                                </div>
                            </td>
                        </tr>
                        <?php
endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <div id="viewModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-6">
        <div class="bg-white w-full max-w-5xl h-[85vh] rounded-2xl shadow-2xl overflow-hidden flex flex-col animate-in fade-in zoom-in duration-200">
            <div class="p-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <div class="flex items-center gap-3">
                    <span class="w-10 h-10 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </span>
                    <h3 class="font-extrabold text-slate-800">Question Review</h3>
                </div>
                <button onclick="closeModal()" class="w-10 h-10 flex items-center justify-center rounded-xl bg-slate-200 text-slate-500 hover:bg-red-500 hover:text-white transition-all">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="flex-1 overflow-hidden relative">
                <iframe id="reviewFrame" src="" class="w-full h-full border-none"></iframe>
            </div>
        </div>
    </div>

    <script>
    let currentPayload = null;
    function viewQuestion(payload) {
        currentPayload = payload;
        const modal = document.getElementById('viewModal');
        const frame = document.getElementById('reviewFrame');
        modal.style.display = 'flex';
        frame.src = `${payload.type}/index.php?id=${payload.id}&mode=review&t=${Date.now()}`;
        frame.onload = function() {
            frame.contentWindow.postMessage({
                type: 'prefill',
                answer: currentPayload.answer,
                correct_answer: currentPayload.correct_answer,
                initial_answer: currentPayload.initial_answer,
                changes: currentPayload.changes,
                score: currentPayload.score,
                earned_points: currentPayload.earned_points,
                max_points: currentPayload.max_points,
                rationale: currentPayload.rationale,
                showRationale: true,
                isReview: true
            }, "*");
        };
    }
    function closeModal() { document.getElementById('viewModal').style.display = 'none'; document.getElementById('reviewFrame').src = ''; }

    new Chart(document.getElementById('scoreChart'), {
        type: 'doughnut',
        data: {
            datasets: [{
                data: [<?php echo $overall_percent; ?>, <?php echo max(0, 100 - $overall_percent); ?>],
                backgroundColor: ['#3b82f6', '#f1f5f9'],
                borderWidth: 0, cutout: '85%', borderRadius: 10
            }]
        },
        options: { plugins: { legend: { display: false }, tooltip: { enabled: false } } }
    });
    </script>
</body>
</html>