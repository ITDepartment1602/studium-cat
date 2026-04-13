<?php
// dragndrop/index.php
require_once '../../../../config.php';
// session_start handled by config.php

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id === 0) die("No question ID provided!");

$q = mysqli_query($con, "SELECT * FROM dragndrop WHERE id='$id'");
$data = mysqli_fetch_assoc($q);
if (!$data) die("Question not found!");

$topic = $data['topic'];
$question = $data['question'];
$items = json_decode($data['items'], true);
shuffle($items);
$rationale = $data['rationale'];
$system = $data['system'] ?? 'N/A';
$cnc = $data['cnc'] ?? 'N/A';
$dlevel = $data['dlevel'] ?? 'N/A';
$correct = json_decode($data['correct'], true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Drag & Drop Question</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Mobile Drag & Drop Polyfill -->
<script src="https://bernardo-castilho.github.io/DragDropTouch/DragDropTouch.js"></script>
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
  padding: clamp(8px, 3vh, 32px) 12px;
}

.card {
  background: var(--surface);
  border-radius: 12px;
  padding: 16px;
  width: 100%;
  max-width: 900px;
  box-shadow: 0 4px 20px -5px rgba(0,0,0,0.05);
  border: 1px solid var(--border);
  margin-bottom: 24px;
}

/* MOBILE SQUEEZE */
@media (max-width: 600px) {
  .card { padding: 12px; border-radius: 8px; }
  .question-container { font-size: 14px; line-height: 1.6; margin-bottom: 16px; }
  .blank { min-width: 80px; height: 30px; font-size: 12px; margin: 2px; }
  .instruction { font-size: 9px; margin-bottom: 8px; }
  .choices-bank { padding: 12px; gap: 6px; }
  .choice-item { padding: 5px 10px; font-size: 12px; }
  .btn { padding: 8px 16px; font-size: 12px; width: 100%; }
}

/* RESPONSIVE */
@media (max-width: 640px) {
  .card { border-radius: 0; padding: 16px; }
  .question-container { font-size: 16px; line-height: 1.8; }
  .blank { min-width: 100px; font-size: 13px; height: 32px; }
  .choices-bank { padding: 16px; gap: 8px; }
  .choice-item { padding: 6px 12px; font-size: 13px; }
  body { overflow-y: auto; }
}

.instruction {
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    color: var(--accent);
    letter-spacing: 1px;
    margin-bottom: 12px;
    display: block;
}

.question-container {
  font-size: 18px;
  font-weight: 600;
  line-height: 2;
  margin-bottom: 32px;
  color: var(--primary);
}

.blank {
  display: inline-flex;
  min-width: 120px;
  height: 36px;
  background: #f1f5f9;
  border: 2px dashed #cbd5e1;
  border-radius: 8px;
  margin: 0 4px;
  vertical-align: middle;
  align-items: center;
  justify-content: center;
  font-size: 15px;
  font-weight: 700;
  color: var(--accent);
  transition: all 0.2s;
  cursor: pointer;
}

.blank.active { border-color: var(--accent); background: #eff6ff; }
.blank.filled { border-style: solid; border-color: #3b82f6; background: white; box-shadow: 0 2px 6px rgba(59, 130, 246, 0.1); }

/* Reveal Feedback */
.blank.correct-reveal { border-color: var(--success); background: #f0fdf4; color: #15803d; }
.blank.wrong-reveal { border-color: var(--danger); background: #fef2f2; color: #b91c1c; }
.blank.omitted-reveal { border-color: #f59e0b; background: #fffbeb; color: #92400e; text-decoration: line-through; opacity: 0.75; }

.choices-bank {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  padding: 24px;
  background: #f8fafc;
  border-radius: 12px;
  border: 1px solid var(--border);
}

.choice-item {
  background: white;
  border: 1px solid var(--border);
  padding: 10px 20px;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 600;
  cursor: grab;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
  transition: all 0.2s;
}
.choice-item:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.05); }

.actions { display: flex; gap: 12px; margin-top: 32px; }
.btn { padding: 12px 32px; border-radius: 10px; font-weight: 700; font-size: 14px; cursor: pointer; border: none; transition: all 0.2s; }
.btn-primary { background: var(--primary); color: white; }
.btn-outline { background: transparent; border: 2px solid var(--border); color: #64748b; }

#result {
  margin-top: 24px;
  padding: 24px;
  background: #f8fafc;
  border-radius: 12px;
  border-left: 4px solid var(--accent);
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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<div class="card">
    <div class="previous-badge" id="prevBadge">
        <i class="fas fa-lock"></i> Your ordering has been submitted and is now read-only.
    </div>

    <span class="instruction">Drag and Drop Ordered Response</span>
    
    <div class="question-container" id="questionBox">
        <?php
        echo preg_replace_callback('/_{3,}/', function() {
            static $i = 0;
            return '<div class="blank" data-idx="'.($i++).'">Drop Here</div>';
        }, htmlspecialchars($question));
        ?>
    </div>

    <div class="choices-bank" id="choicesBank">
        <?php foreach ($items as $item): ?>
            <div class="choice-item" draggable="true"><?php echo htmlspecialchars($item); ?></div>
        <?php endforeach; ?>
    </div>

    <div class="actions">
        <button id="submitBtn" class="btn btn-primary">Submit Answer</button>
    </div>

    <div id="result">
        <div style="font-weight:800; color:#64748b; font-size:12px; margin-bottom:8px; text-transform:uppercase;">Performance & Rationale</div>
        <div id="resSummary" style="font-weight:700; margin-bottom:12px;"></div>
        <div id="rationaleText" style="font-size:14px; line-height:1.6;"></div>
    </div>
</div>

<script>
$(document).ready(function(){
    const correct = <?= json_encode($correct) ?>;
    const rationale = <?= json_encode($rationale) ?>;
    let dragged = null;
    let isReviewMode = false;
    let initialAnswers = [];
    let hasInteracted = false; // Track if user has made first interaction
    
    // Drag and Drop Handles
    $(document).on('dragstart', '.choice-item', function(e){
        dragged = this;
        $(this).css('opacity', '0.5');
    });

    $(document).on('dragend', '.choice-item', function(){
        $(this).css('opacity', '1');
    });

    $('.blank').on('dragover', function(e){ e.preventDefault(); $(this).addClass('active'); });
    $('.blank').on('dragleave', function(){ $(this).removeClass('active'); });
    
    $('.blank').on('drop', function(e){
        e.preventDefault();
        if(isReviewMode) return; // prevent changes in review mode
        $(this).removeClass('active');
        if(!dragged) return;

        // CAPTURE INITIAL ANSWERS ON FIRST INTERACTION
        if(!hasInteracted) {
            hasInteracted = true;
            $('.blank').each(function(i){
                initialAnswers[i] = $(this).text().trim();
            });
        }

        let existing = $(this).contents().filter(function() { return this.nodeType === 3; }).text().trim();
        if($(this).hasClass('filled')) {
            $('#choicesBank').append(`<div class="choice-item" draggable="true">${$(this).text()}</div>`);
        }

        $(this).text($(dragged).text().trim());
        $(this).addClass('filled');
        $(dragged).remove();
        dragged = null;
    });

    // Return item on click
    $('.blank').click(function(){
        if(isReviewMode) return; // prevent changes in review mode
        if(!$(this).hasClass('filled') || $(this).prop('disabled')) return;
        
        // Save initial on first interaction if not already done
        if(!hasInteracted) {
            hasInteracted = true;
            $('.blank').each(function(i){
                initialAnswers[i] = $(this).text().trim();
            });
        }
        
        $('#choicesBank').append(`<div class="choice-item" draggable="true">${$(this).text()}</div>`);
        $(this).text('Drop Here').removeClass('filled');
    });

    function showResult(scoreHeader, userAnswers = [], prevInitial = []) {
        $('.blank').removeClass('correct-reveal wrong-reveal omitted-reveal');
        const displayInitial = prevInitial.length > 0 ? prevInitial : initialAnswers;
        
        $('.blank').each(function(i){
            let txt = $(this).text().trim();
            // Show omitted if was filled but now empty or different
            if(displayInitial[i] && displayInitial[i] !== txt && txt !== 'Drop Here'){
                $(this).addClass('omitted-reveal');
            } else if(txt === correct[i]) {
                $(this).addClass('correct-reveal');
            } else {
                $(this).addClass('wrong-reveal');
            }
        });

        $('#resSummary').html(scoreHeader);
        $('#rationaleText').html(rationale || "No rationale provided.");
        $('#result').fadeIn();
        $('.blank').css('cursor', 'default');
        $('.choice-item').attr('draggable', false);
        $('#submitBtn').hide();
    }



    window.addEventListener('message', (e) => {
        if(e.data.type === 'prefill' || e.data.type === 'previous') {
            isReviewMode = e.data.isReview ?? false;
            const ans = e.data.answer || [];
            const prevInitial = e.data.initial_answer || [];
            
            if(ans.length > 0) {
                initialAnswers = prevInitial.length > 0 ? prevInitial : ans;
                $('#prevBadge').show();
                ans.forEach((val, i) => {
                    if(val && val !== 'Drop Here') {
                        let blank = $(`.blank:eq(${i})`);
                        blank.text(val).addClass('filled');
                        // Remove from bank if exists
                        $('.choice-item').each(function(){
                            if($(this).text().trim() === val) $(this).remove();
                        });
                    }
                });
                
                if(e.data.showRationale) {
                    let s = e.data.score || 0;
                    showResult("Score: " + Math.round(s*100) + "%", ans, prevInitial);
                }
            }
        }
    });

    $('#submitBtn').click(function(){
        if(isReviewMode) return; // Prevent resubmission in review mode
        
        let userAnswers = [];
        let incomplete = false;
        $('.blank').each(function(){
            let txt = $(this).text().trim();
            if(!$(this).hasClass('filled')) incomplete = true;
            userAnswers.push(txt);
        });

        if(incomplete) {
            Swal.fire({ icon:'warning', title:'Incomplete', text:'Please fill all blanks.' });
            return;
        }
        
        // Capture initial on submit if somehow not captured yet (safety net)
        if(initialAnswers.length === 0){
            initialAnswers = [...userAnswers];
        }

        let earned = 0;
        userAnswers.forEach((v, i) => { 
            if(v.toString().trim().toLowerCase() === correct[i].toString().trim().toLowerCase()) earned++; 
        });

        const total = correct.length;
        const normalized = parseFloat((earned / total).toFixed(2));
        showResult("Score: " + Math.round(normalized*100) + "% ("+earned+"/"+total+" pts)", userAnswers);
        
        // Calculate changes
        let changesData = null;
        if(JSON.stringify(initialAnswers) !== JSON.stringify(userAnswers)){
            changesData = {
                modified_count: userAnswers.filter((ans, i) => ans !== initialAnswers[i]).length,
                changed: true
            };
        }

        window.parent.postMessage({
            type:'answered',
            question_id: <?= json_encode($id) ?>,
            answer: userAnswers,
            initial_answer: initialAnswers.length > 0 ? initialAnswers : null,
            correctAnswer: correct,
            isCorrect: earned === total,
            score: normalized,
            max_points: total,
            earned_points: earned,
            changes: changesData,
            rationale: rationale,
            topic: <?= json_encode($topic) ?>,
            question_type: 'dragndrop'
        }, '*');
    });

    // ===== AUTO-SCROLL FOR DRAG & DROP ON MOBILE =====
    let isDragging = false;
    let autoScrollInterval = null;

    // Detect when dragging starts
    $(document).on('dragstart', '.choice-item', function(e){
        isDragging = true;
    });

    // Detect when dragging ends
    $(document).on('dragend', '.choice-item', function(e){
        isDragging = false;
        if (autoScrollInterval) clearInterval(autoScrollInterval);
    });

    // Auto-scroll while dragging on desktop/mouse
    $(document).on('dragover', function(e){
        if (!isDragging) return;

        const mouseY = e.clientY;
        const scrollThreshold = 100; // pixels from top/bottom
        let scrollAmount = 0;

        // Check if mouse is near top of viewport
        if (mouseY < scrollThreshold) {
            scrollAmount = -10; // scroll up
        }
        // Check if mouse is near bottom of viewport
        else if (mouseY > window.innerHeight - scrollThreshold) {
            scrollAmount = 10; // scroll down
        }

        if (scrollAmount !== 0) {
            window.scrollBy(0, scrollAmount);
        }
    });

    // For touch/mobile drag - also monitor drag events on drop zones
    document.addEventListener('dragover', function(e){
        if (!isDragging) return;

        // Get touch position if available (from DragDropTouch polyfill)
        let clientY = e.clientY;
        if (clientY === 0 && e.touches && e.touches[0]) {
            clientY = e.touches[0].clientY;
        }

        const scrollThreshold = 100;
        if (clientY < scrollThreshold) {
            window.scrollBy(0, -10);
        } else if (clientY > window.innerHeight - scrollThreshold) {
            window.scrollBy(0, 10);
        }
    }, true); // Use capture phase for better responsiveness

    // Final fallback for touch end
    $(document).on('touchend', function(){
        isDragging = false;
        if (autoScrollInterval) clearInterval(autoScrollInterval);
    });
});
</script>
</body>
</html>
