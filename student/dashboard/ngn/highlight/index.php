<?php
require_once '../../../../config.php';
// session_start handled by config.php

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id === 0) die("No ID provided.");

$stmt = mysqli_prepare($con, "SELECT * FROM highlight WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$data) die("Question not found.");

$topic = $data['topic'] ?? '';
$question = $data['question'] ?? '';
$passage = $data['passage'] ?? '';
$options = array_filter(array_map('trim', explode(',', $data['options'] ?? '')));
$highlightable_flags = array_map('intval', array_filter(array_map('trim', explode(',', $data['highlightable'] ?? ''))));
$correct_phrases = array_filter(array_map('trim', explode(',', $data['correct_words'] ?? '')));
$rationale = $data['rationale'] ?? '';
$systemTxt = $data['system'] ?? '';
$cnc = $data['cnc'] ?? '';
$dlevel = $data['dlevel'] ?? '';
$maxHighlights = (int)($data['maxHighlights'] ?? 4);

// Build passage HTML with spans
$option_map = [];
foreach ($options as $i => $opt) if ($opt !== '') $option_map[$i] = $opt;
uasort($option_map, function ($a, $b) { return mb_strlen($b) <=> mb_strlen($a); });

$passage_work = $passage;
$placeholders = [];
foreach ($option_map as $idx => $phrase) {
    if (trim($phrase) === '') continue;
    $token = "~~OPTION_{$idx}_" . md5($phrase) . "~~";
    
    // Create a regex that allows any sequence of spaces/newlines between words in the phrase
    $words = preg_split('/\s+/', trim($phrase));
    $escapedWords = array_map(function($w){ return preg_quote($w, '/'); }, $words);
    $fuzzyRegex = implode('\s+', $escapedWords);
    
    $passage_work = preg_replace_callback("/($fuzzyRegex)/iu", function ($m) use ($idx, $token, &$placeholders) {
        $placeholders[] = ['token' => $token, 'index' => $idx, 'text' => $m[0]];
        return $token;
    }, $passage_work);
}

$token_to_span = [];
foreach ($placeholders as $ph) {
    $idx = $ph['index'];
    $display = htmlspecialchars($ph['text']);
    $is_highlightable = (isset($highlightable_flags[$idx]) && intval($highlightable_flags[$idx]) === 1);
    $cls = 'inline-token' . ($is_highlightable ? ' hint-highlightable' : '');
    $token_to_span[$ph['token']] = "<span class=\"{$cls}\" data-index=\"{$idx}\">{$display}</span>";
}
$passage_html = strtr($passage_work, $token_to_span);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Highlight Question</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
:root {
  --primary: #0a1628;
  --accent: #3b82f6;
  --highlight: #fef08a;
  --highlight-hover: #fde68a;
  --success: #10b981;
  --danger: #ef4444;
  --bg: #f8fafc;
  --surface: #ffffff;
  --border: #e2e8f0;
  --text: #0f172a;
}

html, body { height: 100%; margin: 0; padding: 0; overflow: hidden; }
body {
  font-family: 'Inter', sans-serif;
  color: var(--text);
  background: white;
  display: flex;
  justify-content: center;
  align-items: flex-start;
  overflow-y: auto;
  padding: clamp(16px, 5vh, 48px) 16px;
}

.card {
  background: var(--surface);
  border-radius: 20px;
  padding: clamp(20px, 4vw, 40px);
  width: 100%;
  max-width: 900px;
  box-shadow: 0 10px 30px -5px rgba(0,0,0,0.05);
  border: 1px solid var(--border);
  margin-bottom: 24px;
}

/* RESPONSIVE */
@media (max-width: 640px) {
  .card { border-radius: 0; padding: 16px; }
  .question-text { font-size: 16px; line-height: 1.5; }
  .passage-box { padding: 16px; font-size: 14px; line-height: 1.6; }
  .btn { padding: 10px 24px; font-size: 13px; }
}

.instruction {
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    color: var(--accent);
    letter-spacing: 1.5px;
    margin-bottom: 12px;
    display: block;
}

.question-text {
  font-size: 18px;
  font-weight: 700;
  line-height: 1.6;
  margin-bottom: 24px;
}

.passage-box {
  background: #fdfdfd;
  border: 1px solid var(--border);
  padding: 24px;
  border-radius: 12px;
  line-height: 1.8;
  font-size: 16px;
  color: #334155;
  white-space: pre-wrap;
  margin-bottom: 24px;
}

.inline-token {
  padding: 2px 2px;
  border-radius: 4px;
  cursor: pointer;
  transition: all 0.2s;
  border-bottom: 1.5px solid transparent;
}

.hint-highlightable:hover {
  background: #f1f5f9;
  border-bottom-color: var(--accent);
}

.inline-token.highlighted {
  background: var(--highlight);
  border-bottom-color: #f59e0b;
  font-weight: 600;
}

.inline-token.correct-reveal { background: #dcfce7 !important; border-bottom-color: var(--success); }
.inline-token.wrong-reveal { background: #fee2e2 !important; border-bottom-color: var(--danger); }
.inline-token.omitted-reveal { background: #fef3c7 !important; border-bottom-color: #f59e0b; text-decoration: line-through; opacity: 0.75; }

.actions { display: flex; gap: 12px; margin-top: 24px; }
.btn {
  padding: 12px 32px;
  border-radius: 10px;
  font-weight: 700;
  font-size: 14px;
  cursor: pointer;
  border: none;
  transition: all 0.2s;
}
.btn-primary { background: var(--primary); color: white; }
.btn-outline { background: transparent; border: 2px solid var(--border); color: #64748b; }

#result {
  margin-top: 24px;
  padding: 24px;
  background: #f8fafc;
  border-radius: 12px;
  border-left: 4px solid var(--accent);
  display: list-item; /* none by default, JS handles */
  display: none;
}

.previous-badge {
    display: none;
    background: #f1f5f9;
    color: #475569;
    font-size: 12px;
    font-weight: 600;
    padding: 8px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    border-left: 4px solid #cbd5e1;
}
</style>
</head>
<body>

<div class="card">
    <div class="previous-badge" id="prevBadge">
        <i class="fas fa-lock"></i> Your highlights have been finalized and are now read-only.
    </div>

    <span class="instruction">Highlight Question</span>
    <div class="question-text"><?= nl2br(htmlspecialchars($question)) ?></div>

    <div class="passage-box" id="passage"><?php echo $passage_html; ?></div>

    <div class="actions">
        <button id="submitBtn" class="btn btn-primary">Submit Selections</button>
    </div>

    <div id="result">
        <div style="font-weight:800; color:#64748b; font-size:12px; margin-bottom:8px; text-transform:uppercase;">Performance & Rationale</div>
        <div id="resSummary" style="font-weight:700; margin-bottom:12px;"></div>
        <div id="rationaleContent" style="font-size:14px; line-height:1.6;"></div>
    </div>
</div>

<script>
$(document).ready(function(){
    const correctPhrases = <?php echo json_encode(array_values($correct_phrases)); ?>;
    const maxAllowed = <?php echo $maxHighlights; ?>;
    const rationale = <?php echo json_encode($rationale); ?>;
    let selectedText = [];
    let isReviewMode = false;
    let initialSelectedText = [];
    let hasInteracted = false;

    function normalize(str){
        return str.toLowerCase().replace(/[^a-z0-9\s]/g, '').replace(/\s+/g, ' ').trim();
    }
    const normCorrect = correctPhrases.map(normalize);

    $('.inline-token').click(function(){
        if(isReviewMode) return; // review mode
        
        hasInteracted = true;  // Mark that user has interacted
        
        const txt = $(this).text().trim();
        const nm = normalize(txt);

        if($(this).hasClass('highlighted')) {
            $(this).removeClass('highlighted');
            selectedText = selectedText.filter(s => normalize(s) !== nm);
        } else {
            if(selectedText.length >= maxAllowed) {
                Swal.fire({ icon:'warning', title:'Limit Reached', text:'Maximum ' + maxAllowed + ' highlights allowed.' });
                return;
            }
            $(this).addClass('highlighted');
            selectedText.push(txt);
        }
    });

    function reveal(scoreHeader, prevSelected = []) {
        const displayInitial = prevSelected.length > 0 ? prevSelected : initialSelectedText;
        const displayInitialNorm = displayInitial.map(normalize);
        
        $('.inline-token').removeClass('correct-reveal wrong-reveal omitted-reveal');
        $('.inline-token').each(function(){
            const nm = normalize($(this).text().trim());
            // Show omitted if was highlighted but now not selected
            if(displayInitialNorm.includes(nm) && !selectedText.map(normalize).includes(nm)) {
                $(this).addClass('omitted-reveal');
            } else if(normCorrect.includes(nm)) {
                $(this).addClass('correct-reveal');
            } else if($(this).hasClass('highlighted')) {
                $(this).addClass('wrong-reveal');
            }
        });
        
        $('#resSummary').html(scoreHeader);
        $('#rationaleContent').html(rationale || "No rationale provided.");
        $('#result').fadeIn();
        $('.inline-token').css('cursor', 'default');
        $('#submitBtn').hide();
    }



    window.addEventListener('message', (e) => {
        if(e.data.type === 'prefill' || e.data.type === 'previous') {
            isReviewMode = e.data.isReview ?? false;
            const prev = e.data.answer || [];
            let prevInitial = e.data.initial_answer || [];
            
            // Handle JSON-encoded initial_answer from database
            if(typeof prevInitial === 'string') {
                try { prevInitial = JSON.parse(prevInitial); } catch(er) { prevInitial = []; }
            }
            
            if(prev.length > 0) {
                initialSelectedText = prevInitial.length > 0 ? prevInitial : prev;
                $('#prevBadge').show();
                selectedText = [...prev];
                $('.inline-token').each(function(){
                    const nm = normalize($(this).text().trim());
                    if(prev.map(normalize).includes(nm)) $(this).addClass('highlighted');
                });
                
                if(e.data.showRationale) {
                    let s = e.data.score || 0;
                    reveal("Score: " + Math.round(s*100) + "%", prevInitial);
                }
            }
        }
    });
    
    // Capture initial state on page load for fresh exams
    if(initialSelectedText.length === 0) {
        $('.inline-token.highlighted').each(function(){
            initialSelectedText.push($(this).text().trim());
        });
    }

    $('#submitBtn').click(function(){
        if(isReviewMode) return; // Prevent resubmission in review mode
        
        if(selectedText.length === 0) {
            Swal.fire({ icon:'error', title:'Incomplete', text:'Please highlight at least one phrase.' });
            return;
        }
        
        // Capture initial if not done yet (safety net)
        if(initialSelectedText.length === 0){
            initialSelectedText = [...selectedText];
        }

        let match = 0, wrong = 0;
        const selNorm = selectedText.map(normalize);
        selNorm.forEach(s => {
            if(normCorrect.includes(s)) match++;
            else wrong++;
        });

        const earned = Math.max(0, match - wrong);
        const total = normCorrect.length || 1;
        const normalized = parseFloat((earned / total).toFixed(2));

        reveal("Score: " + Math.round(normalized*100) + "% ("+earned+"/"+total+" pts)");
        
        // Calculate changes
        let changesData = null;
        const initialNorm = initialSelectedText.map(normalize).sort().join('|');
        const currentNorm = selectedText.map(normalize).sort().join('|');
        if(initialNorm !== currentNorm){
            changesData = {
                modified_count: 1,
                changed: true
            };
        }

        window.parent.postMessage({
            type:'answered',
            answer: selectedText,
            initial_answer: initialSelectedText.length > 0 ? initialSelectedText : null,
            correctAnswer: correctPhrases,
            correct: earned === total,
            score: normalized,
            max_points: total,
            earned_points: earned,
            changes: changesData,
            rationale: rationale,
            topic: <?= json_encode($topic) ?>,
            question_id: <?= json_encode($id) ?>,
            question_type: 'highlight'
        }, '*');
    });
});
</script>
</body>
</html>