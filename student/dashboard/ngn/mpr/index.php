<?php
// mpr/index.php
include '../../../../config.php';
session_start();

$question_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if($question_id > 0){
    $q = mysqli_query($con, "SELECT * FROM mpr WHERE id = $question_id LIMIT 1");
} else {
    $q = mysqli_query($con, "SELECT * FROM mpr ORDER BY RAND() LIMIT 1");
}

$data = mysqli_fetch_assoc($q);
if (!$data) {
    die('<div style="font-family: Arial; padding: 20px;">No MPR question found.</div>');
}

$items = explode("\n", $data['items']); 
$correct = explode(",", $data['correct']);
$rationale = $data['rationale'] ?? '';
$question = $data['question'] ?? '';

$required = 1;
if (preg_match('/Select\s+(\d+)/i', $question, $match)) {
    $required = (int)$match[1];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MPR Question</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<style>
:root {
  --primary: #0a1628;
  --accent: #3b82f6;
  --success: #10b981;
  --danger: #ef4444;
  --bg: #f8fafc;
  --surface: #ffffff;
  --border: #e2e8f0;
  --text: #0f172a;
  --text-muted: #64748b;
}

* { box-sizing: border-box; margin: 0; padding: 0; }
body {
  font-family: 'Inter', sans-serif;
  background: transparent; /* Parent handles background */
  color: var(--text);
  padding: 20px;
}

.card {
  background: var(--surface);
  border-radius: 12px;
  padding: 32px;
  max-width: 900px;
  margin: 0 auto;
}

.question-text {
  font-size: 18px;
  font-weight: 600;
  line-height: 1.6;
  margin-bottom: 24px;
  color: var(--primary);
}

.instruction-badge {
  display: inline-block;
  background: #eff6ff;
  color: #1e40af;
  font-size: 12px;
  font-weight: 700;
  padding: 4px 12px;
  border-radius: 100px;
  margin-bottom: 20px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.options-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.option-item {
  position: relative;
  display: flex;
  align-items: center;
  padding: 16px 20px;
  border: 2px solid var(--border);
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.option-item:hover {
  border-color: #cbd5e1;
  background: #fcfcfd;
}

.option-item.selected {
  border-color: var(--accent);
  background: #f0f7ff;
}

.option-item input {
  position: absolute;
  opacity: 0;
  cursor: pointer;
}

.custom-checkbox {
  width: 20px;
  height: 20px;
  border: 2px solid #cbd5e1;
  border-radius: 6px;
  margin-right: 16px;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
  background: white;
}

.option-item.selected .custom-checkbox {
  background: var(--accent);
  border-color: var(--accent);
}

.custom-checkbox::after {
  content: "✓";
  color: white;
  font-size: 14px;
  font-weight: bold;
  display: none;
}

.option-item.selected .custom-checkbox::after {
  display: block;
}

.option-text {
  font-size: 15px;
  font-weight: 500;
  color: var(--text);
  line-height: 1.4;
}

/* Feedback Styles */
.option-item.correct-reveal {
  border-color: var(--success);
  background: #ecfdf5;
}
.option-item.correct-reveal .custom-checkbox {
  background: var(--success);
  border-color: var(--success);
}

.option-item.wrong-reveal {
  border-color: var(--danger);
  background: #fef2f2;
}
.option-item.wrong-reveal .custom-checkbox {
  background: var(--danger);
  border-color: var(--danger);
}

/* Omitted answer - item was selected but later deselected */
.option-item.omitted-reveal {
  border-color: #f59e0b;
  background: #fffbeb;
  opacity: 0.75;
}
.option-item.omitted-reveal .option-text {
  text-decoration: line-through;
  color: #92400e;
}
.option-item.omitted-reveal .custom-checkbox {
  background: #f59e0b;
  border-color: #f59e0b;
}

.actions {
  margin-top: 32px;
  display: flex;
  align-items: center;
  gap: 16px;
}

.btn {
  padding: 12px 32px;
  border-radius: 10px;
  font-weight: 700;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s ease;
  border: none;
}

.btn-primary {
  background: var(--primary);
  color: white;
}

.btn-primary:hover {
  background: #1e293b;
  transform: translateY(-1px);
}

.btn-outline {
  background: transparent;
  border: 2px solid var(--border);
  color: var(--text-muted);
}

.btn-outline:hover {
  background: #f1f5f9;
  border-color: #cbd5e1;
}

#result {
  margin-top: 24px;
  padding: 24px;
  border-radius: 12px;
  background: #f8fafc;
  border-left: 4px solid var(--accent);
  display: none;
  animation: slideDown 0.3s ease;
}

@keyframes slideDown {
  from { opacity: 0; transform: translateY(-10px); }
  to { opacity: 1; transform: translateY(0); }
}

.rationale-title {
  font-size: 14px;
  font-weight: 800;
  text-transform: uppercase;
  color: var(--text-muted);
  margin-bottom: 8px;
  letter-spacing: 0.5px;
}

.rationale-text {
  font-size: 15px;
  line-height: 1.6;
  color: var(--text);
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
        <i class="fas fa-lock"></i> This question has been submitted and is now read-only.
    </div>

    <div class="instruction-badge">Multiple Response</div>
    
    <div class="question-text">
        <?= nl2br(htmlspecialchars($question)) ?>
    </div>

    <form id="mprForm" class="options-list">
        <?php foreach($items as $item): 
            $letter = trim(substr($item, 0, 1));
        ?>
        <label class="option-item" data-value="<?= $letter ?>">
            <input type="checkbox" name="answers[]" value="<?= $letter ?>">
            <div class="custom-checkbox"></div>
            <div class="option-text"><?= htmlspecialchars($item) ?></div>
        </label>
        <?php endforeach; ?>
    </form>

    <div class="actions">
        <button id="submitBtn" class="btn btn-primary">Submit Answer</button>
    </div>

    <div id="result">
        <div class="rationale-title" id="resType">Rationale</div>
        <div class="rationale-text" id="rationaleText"></div>
    </div>
</div>

<script>
$(document).ready(function(){
    let correct = <?= json_encode($correct) ?>;
    let required = <?= $required ?>;
    let rationale = <?= json_encode($rationale) ?>;
    let isEditing = false;
    let isReviewMode = false;
    let hasInteracted = false;      // Track first user interaction
    let initialAnswers = [];
    let currentAnswers = [];
    let changes = null;

    // ===== PREFILL MESSAGE HANDLER (for review/resume) =====
    window.addEventListener('message', (event) => {
        if(event.data.type === 'prefill' || event.data.type === 'previous'){
            isReviewMode = event.data.isReview ?? false;
            const previousAnswers = event.data.answer || [];
            const prevInitial = event.data.initial_answer || [];
            
            if(previousAnswers.length > 0){
                initialAnswers = prevInitial.length > 0 ? prevInitial : previousAnswers;
                currentAnswers = previousAnswers;
                
                $('#prevBadge').show();
                $('input[name="answers[]"]').each(function(){
                    let checked = previousAnswers.includes($(this).val());
                    $(this).prop('checked', checked);
                    if(checked) $(this).closest('.option-item').addClass('selected');
                });
                
                if(event.data.showRationale) {
                    let score = event.data.score || 0;
                    let earned = event.data.earned_points || 0;
                    let max = event.data.max_points || 0;
                    showResult(Math.round(score*100) + "% ("+earned+"/"+max+" pts)", true, prevInitial);
                }
            }
        }
    });

    // Toggle selection styles
    $(document).on('change', 'input[name="answers[]"]', function(){
        if(isReviewMode) return; // Prevent changes in review mode
        
        // Capture initial answers on first interaction (only if not prefilled)
        if(!hasInteracted && initialAnswers.length === 0) {
            hasInteracted = true;
            $('input[name="answers[]"]').each(function(){
                if($(this).is(':checked')) {
                    initialAnswers.push($(this).val());
                }
            });
        } else if(!hasInteracted) {
            hasInteracted = true; // Mark as interacted even if prefilled
        }
        
        let parent = $(this).closest('.option-item');
        if(this.checked) {
            parent.addClass('selected');
        } else {
            parent.removeClass('selected');
        }

        let checkedCount = $('input[name="answers[]"]:checked').length;
        if(checkedCount > required){
            this.checked = false;
            parent.removeClass('selected');
            Swal.fire({
                icon:'warning',
                title:'Limit Reached',
                text:'You can only select ' + required + ' items.'
            });
        }
    });

    function showResult(scoreText, showCorrectLines = true, prevInitial = null) {
        $('.option-item').removeClass('correct-reveal wrong-reveal omitted-reveal');
        
        if(showCorrectLines) {
            const displayInitial = prevInitial && prevInitial.length > 0 ? prevInitial : initialAnswers;
            
            $('input[name="answers[]"]').each(function(){
                let val = $(this).val();
                let parent = $(this).closest('.option-item');
                
                // Show omitted items (were in initial but not in current)
                if(displayInitial.includes(val) && !currentAnswers.includes(val)) {
                    parent.addClass('omitted-reveal');
                } else if(correct.includes(val)){
                    parent.addClass('correct-reveal');
                } else if($(this).is(':checked')) {
                    parent.addClass('wrong-reveal');
                }
            });
        }

        $('#rationaleText').html(rationale || "No rationale provided.");
        $('#resType').html("Score: " + scoreText + " — Rationale");
        $('#result').fadeIn();
        
        $('input[name="answers[]"]').prop('disabled', true);
        $('.option-item').css('cursor', 'default').off('click');
        $('#submitBtn').hide();
    }

    $('#submitBtn').click(function(){
        if(isReviewMode) return; // Prevent resubmission in review mode
        
        let selected = [];
        $('input[name="answers[]"]:checked').each(function(){
            selected.push($(this).val());
        });

        if(selected.length === 0){
            Swal.fire({ icon:'error', title:'Incomplete', text:'Please select at least one answer.' });
            return;
        }

        // Track initial answers if not set
        if(initialAnswers.length === 0) {
            initialAnswers = [...selected];
        }
        
        currentAnswers = selected;

        let earned = 0;
        let maxPoints = correct.length;
        const normCorrect = correct.map(s => s.toString().trim().toLowerCase());

        $('input[name="answers[]"]').each(function(){
            let val = $(this).val().trim().toLowerCase();
            let checked = $(this).is(':checked');
            if(normCorrect.includes(val) && checked) earned++;
            else if(!normCorrect.includes(val) && checked) earned--;
        });

        earned = Math.max(0, earned);
        let normalizedScore = maxPoints > 0 ? parseFloat((earned / maxPoints).toFixed(2)) : 0;

        showResult(Math.round(normalizedScore * 100) + "% ("+earned+"/"+maxPoints+" pts)");

        // Calculate changes
        let changesData = null;
        if(initialAnswers.length > 0 && initialAnswers.length !== selected.length) {
            const added = selected.filter(a => !initialAnswers.includes(a));
            const removed = initialAnswers.filter(a => !selected.includes(a));
            
            if(added.length > 0 || removed.length > 0) {
                changesData = {
                    added: added,
                    removed: removed,
                    modified_count: added.length + removed.length,
                    changed: true
                };
            }
        }

        window.parent.postMessage({
            type:'answered',
            answer:selected,
            initial_answer: initialAnswers.length > 0 ? initialAnswers : null,
            correctAnswer:correct,
            correct: earned === maxPoints && selected.length === maxPoints,
            score: normalizedScore,
            max_points: maxPoints,
            earned_points: earned,
            changes: changesData,
            rationale: rationale,
            topic: <?= json_encode($data['topic'] ?? 'General') ?>,
            system: <?= json_encode($data['system'] ?? 'N/A') ?>,
            cnc: <?= json_encode($data['cnc'] ?? 'N/A') ?>,
            dlevel: <?= json_encode($data['dlevel'] ?? 'N/A') ?>,
            question_id: <?= json_encode($data['id']) ?>,
            question_type:'mpr'
        },'*');
    });
});
</script>
</body>
</html>