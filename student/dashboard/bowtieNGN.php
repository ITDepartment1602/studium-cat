<?php
// bowtieNGN.php
// Requires: ../../config.php which creates $con (adjust path if needed)

include "../../config.php";
$conn = $con;

// helper: split by comma or semicolon and trim, remove empty items
function splitChoices($str) {
    if (is_null($str)) return [];
    $parts = preg_split("/[,;]+/", $str);
    $out = array_values(array_filter(array_map('trim', $parts), function($v){ return $v !== ''; }));
    return $out;
}

// If AJAX POST (JSON) -> evaluate and return JSON result
$inputJSON = file_get_contents('php://input');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($inputJSON)) {
    $json = json_decode($inputJSON, true);

    // expecting 'id', 'act' (array), 'cond' (string), 'param' (array)
    $qid = intval($json['id'] ?? 0);
    $chosen_act = $json['act'] ?? [];
    $chosen_cond = $json['cond'] ?? "";
    $chosen_param = $json['param'] ?? [];

    // fetch question row by id to evaluate
    $stmt = mysqli_prepare($conn, "SELECT * FROM bowtieNGN WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $qid);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);

    if (!$row) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Question not found']);
        exit;
    }

    // correct answers from DB
    $correct_condition = trim($row['cnc']);
    $correct_actions = splitChoices($row['narcan']); // expected 2
    $correct_parameters = splitChoices($row['system']); // expected 2

    // normalize helper
    $norm = function($s){
        return mb_strtolower(trim(preg_replace('/\s+/', ' ', (string)$s)));
    };

    // Condition check (single)
    $cond_correct = $norm($chosen_cond) === $norm($correct_condition);

    // Actions check: require intersection count >= 2 (exactly 2 in DB expected)
    $chosen_act_norm = array_map($norm, $chosen_act);
    $correct_actions_norm = array_map($norm, $correct_actions);
    $actions_intersect = array_intersect($chosen_act_norm, $correct_actions_norm);
    $act_correct = count($actions_intersect) >= 2;

    // Parameters check
    $chosen_param_norm = array_map($norm, $chosen_param);
    $correct_parameters_norm = array_map($norm, $correct_parameters);
    $params_intersect = array_intersect($chosen_param_norm, $correct_parameters_norm);
    $param_correct = count($params_intersect) >= 2;

    // Prepare response with helpful details for frontend marking
    $response = [
        'cond_correct' => $cond_correct,
        'act_correct' => $act_correct,
        'param_correct' => $param_correct,
        'correct_condition' => $correct_condition,
        'correct_actions' => $correct_actions,
        'correct_parameters' => $correct_parameters,
        'rationale' => $row['rationale'] ?? '',
        'selected' => [
            'act' => $chosen_act,
            'cond' => $chosen_cond,
            'param' => $chosen_param
        ]
    ];

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Otherwise, GET: fetch 1 random question and render page
$q = mysqli_query($conn, "SELECT * FROM bowtieNGN ORDER BY RAND() LIMIT 1");
$row = mysqli_fetch_assoc($q);
if (!$row) {
    echo "No questions found in bowtieNGN table.";
    exit;
}

// Prepare arrays for choices (accept both comma and semicolon)
$actions_bank = splitChoices($row['rows']);      // Actions to take (rows)
$conditions_bank = splitChoices($row['columns']); // Potential conditions (columns)
$parameters_bank = splitChoices($row['system']);  // Parameters to monitor (system)

// Unique ids for draggable items
function uid($prefix, $i){ return $prefix . $i . '_' . substr(md5($prefix.$i.microtime()), 0, 6); }

// Decode caseSTUD JSON
$caseStud = json_decode($row['caseSTUD'], true);
$tabs = ['nurses_notes'=>'Nurses\' Notes','vital_signs'=>'Vital Signs','history_and_physical'=>'History & Physical'];
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Bowtie NGN - Practice</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
:root{
  --bg:#eef6fb;
  --card:#fff;
  --accent:#cfe7ff;
  --accent-2:#e6f1ff;
  --blue:#1e6fb3;
  --muted:#6b7280;
}
*{box-sizing:border-box}
body{font-family:"Segoe UI", Roboto, Arial, sans-serif;background:var(--bg);margin:0;padding:28px;color:#111827;}
.container{max-width:1150px;margin:0 auto;}
.header{display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;}
.header h1{font-size:18px;margin:0}
.grid{display:grid;grid-template-columns:42% 58%;gap:20px;align-items:start;}

/* Nurses notes & tabs */
.notes {background:var(--card);border-radius:10px;padding:18px;box-shadow:0 1px 0 rgba(0,0,0,0.03);border:1px solid #e6eef6;}
.notes .tab-buttons button{flex:1;padding:6px;border:none;border-radius:6px;background:#e6f1ff;cursor:pointer;font-weight:600;color:#0f4f7a;}
.notes .tab-buttons button.active{background:#cfe7ff;}
.tab-content{display:none;color:var(--muted);white-space:pre-wrap;line-height:1.45;border:1px solid #e6eef6;padding:12px;border-radius:8px;background:#fff;}

/* Bowtie & choices (right side) */
.bowtie {background:transparent;}
.bowtie-top {display:flex;gap:12px;align-items:center;justify-content:center;margin-bottom:12px;position:relative;min-height:170px;}
.bowtie-line{width:60px;height:2px;background:#6aa4c4;position:relative;}
.left-line::before {content:"";position:absolute;top:-30px;left:0;width:60px;height:2px;background:#6aa4c4;transform:rotate(25deg);}
.left-line::after {content:"";position:absolute;top:30px;left:0;width:60px;height:2px;background:#6aa4c4;transform:rotate(-25deg);}
.right-line::before {content:"";position:absolute;top:-30px;right:0;width:60px;height:2px;background:#6aa4c4;transform:rotate(-25deg);}
.right-line::after {content:"";position:absolute;top:30px;right:0;width:60px;height:2px;background:#6aa4c4;transform:rotate(25deg);}
.drop-card {flex:1;background:linear-gradient(180deg,#f8fbff,#ffffff);border:1px solid #dbeffb;padding:14px;border-radius:10px;min-height:120px;display:flex;flex-direction:column;align-items:center;justify-content:flex-start;transition:box-shadow .12s, transform .08s;}
.drop-title{font-weight:600;color:#0f4f7a;margin-bottom:8px;font-size:14px}
.drop-zone {width:100%;min-height:56px;border-radius:8px;border:2px dashed #bcdcf6;background:#f7fbff;padding:8px;display:flex;flex-direction:column;gap:6px;align-items:center;justify-content:center;}
.choices{margin-top:14px;display:grid;grid-template-columns: repeat(3, 1fr);gap:14px;}
.col {background:var(--card);padding:10px;border-radius:10px;border:1px solid #e6eef6;min-height:160px;}
.col b{display:block;margin-bottom:8px;color:var(--blue)}
.choice {background:var(--accent-2);padding:8px 10px;margin-bottom:8px;border-radius:8px;border:1px solid #c5e3ff;cursor:grab;text-align:left;box-shadow:0 1px 0 rgba(0,0,0,0.02);font-size:14px;}
.choice[draggable="true"]:active{cursor:grabbing}
.correct {outline:4px solid rgba(34,197,94,0.12);background:#ecfdf5;}
.incorrect {outline:4px solid rgba(255,99,99,0.10);background:#fff5f5;}
.highlight-correct {background:#e6fff0;border-color:#9ef0b3;}
.controls{margin-top:14px;display:flex;gap:12px;align-items:center}
.btn {background:var(--blue);color:white;padding:10px 16px;border-radius:8px;border:0;cursor:pointer;font-weight:600;}
.btn.secondary{background:#ffffff;color:var(--blue);border:1px solid #d1e9ff}
.result-box{margin-top:12px;padding:12px;background:#fff;border-radius:8px;border:1px solid #e6eef6;}
.rationale{margin-top:8px;color:var(--muted);line-height:1.45}
@media(max-width:920px){.grid{grid-template-columns:1fr}.choices{grid-template-columns:1fr}}
</style>
</head>
<body>
<div class="container">
  <div class="header">
    <h1>Bowtie NGN Practice — Topic: <?= htmlspecialchars($row['topic']) ?></h1>
    <div style="color:var(--muted); font-size:13px">Difficulty: <?= htmlspecialchars($row['dlevel']) ?></div>
  </div>

  <div class="grid">
    <!-- LEFT: Notes Tabs -->
    <div class="notes">
      <div class="tab-buttons">
        <?php foreach($tabs as $key=>$label): ?>
          <button class="tab-btn" data-target="<?= $key ?>"><?= $label ?></button>
        <?php endforeach; ?>
      </div>
      <?php foreach($tabs as $key=>$label): ?>
        <div class="tab-content" id="<?= $key ?>"><?= nl2br(htmlspecialchars($caseStud[$key] ?? '')) ?></div>
      <?php endforeach; ?>
    </div>

    <!-- RIGHT: Bowtie -->
    <div class="bowtie">
      <div class="bowtie-top">
        <div class="drop-card">
          <div class="drop-title">Actions to Take (Choose 2)</div>
          <div class="drop-zone" id="drop_actions" ondrop="drop(event)" ondragover="allowDrop(event)"></div>
        </div>
        <div class="bowtie-line left-line"></div>
        <div class="drop-card">
          <div class="drop-title">Condition Most Likely Experiencing</div>
          <div class="drop-zone" id="drop_condition" ondrop="drop(event)" ondragover="allowDrop(event)"></div>
        </div>
        <div class="bowtie-line right-line"></div>
        <div class="drop-card">
          <div class="drop-title">Parameter to Monitor (Choose 2)</div>
          <div class="drop-zone" id="drop_parameters" ondrop="drop(event)" ondragover="allowDrop(event)"></div>
        </div>
      </div>

      <!-- Choice banks -->
      <div class="choices">
        <div class="col">
          <b>Actions to Take</b>
          <?php foreach ($actions_bank as $i => $item): $id = uid('act',$i); ?>
            <div class="choice" draggable="true" id="<?= $id ?>" data-type="action" ondragstart="drag(event)"><?= htmlspecialchars($item) ?></div>
          <?php endforeach; ?>
        </div>
        <div class="col">
          <b>Potential Conditions</b>
          <?php foreach ($conditions_bank as $i => $item): $id = uid('cond',$i); ?>
            <div class="choice" draggable="true" id="<?= $id ?>" data-type="condition" ondragstart="drag(event)"><?= htmlspecialchars($item) ?></div>
          <?php endforeach; ?>
        </div>
        <div class="col">
          <b>Parameters to Monitor</b>
          <?php foreach ($parameters_bank as $i => $item): $id = uid('parm',$i); ?>
            <div class="choice" draggable="true" id="<?= $id ?>" data-type="parameter" ondragstart="drag(event)"><?= htmlspecialchars($item) ?></div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="controls">
        <button class="btn" id="checkBtn">Submit</button>
        <button class="btn secondary" id="resetBtn" type="button">Reset</button>
        <div class="small-note">Drag items into each drop zone. Choose <strong>2 actions</strong> and <strong>2 parameters</strong>, and <strong>1 condition</strong>.</div>
      </div>

      <div id="resultArea" class="result-box" style="display:none;"></div>
    </div>
  </div>
</div>

<script>
// Drag/drop functions
function allowDrop(ev){ ev.preventDefault(); }
function drag(ev){ ev.dataTransfer.setData("text/plain", ev.target.id); }
function drop(ev){
    ev.preventDefault();
    const data = ev.dataTransfer.getData("text/plain");
    const el = document.getElementById(data);
    if(!el) return;
    ev.target.closest('.drop-zone')?.appendChild(el);
}
function getDropTexts(zoneId){
    const zone = document.getElementById(zoneId);
    return zone ? Array.from(zone.querySelectorAll('.choice')).map(c=>c.innerText.trim()) : [];
}

// Tabs
const tabButtons = document.querySelectorAll('.tab-btn');
const tabContents = document.querySelectorAll('.tab-content');
function showTab(target){
  tabContents.forEach(tc=>tc.style.display='none');
  document.getElementById(target).style.display='block';
  tabButtons.forEach(btn=>btn.classList.remove('active'));
  document.querySelector(`.tab-btn[data-target="${target}"]`).classList.add('active');
}
if(tabButtons.length) showTab(tabButtons[0].dataset.target);
tabButtons.forEach(btn=>btn.addEventListener('click',()=>showTab(btn.dataset.target)));

// Reset
const originalParents = {};
document.querySelectorAll('.choice').forEach(ch=>{originalParents[ch.id]=ch.parentElement;});
function resetAll(){
  document.querySelectorAll('.choice').forEach(ch=>{originalParents[ch.id]?.appendChild(ch);ch.classList.remove('correct','incorrect','highlight-correct');});
  document.getElementById('resultArea').style.display='none';
}
document.getElementById('resetBtn').addEventListener('click', resetAll);

// Submit
document.getElementById('checkBtn').addEventListener('click', function(){
    const actions = getDropTexts('drop_actions');
    const conds = getDropTexts('drop_condition');
    const params = getDropTexts('drop_parameters');
    const condition = conds.length ? conds[0] : "";
    if(actions.length<2 || params.length<2 || condition===""){alert("Please place 2 Actions, 1 Condition, and 2 Parameters before submitting.");return;}
    const payload={id: <?= json_encode(intval($row['id'])) ?>, act:actions, cond:condition, param:params};
    const btn=this; btn.disabled=true; btn.innerText='Checking...';
    fetch(location.href,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)})
    .then(r=>r.json())
    .then(data=>{
        btn.disabled=false; btn.innerText='Submit';
        if(data.error){alert(data.error);return;}
        // clear classes
        document.querySelectorAll('.choice').forEach(ch=>ch.classList.remove('correct','incorrect','highlight-correct'));
        // condition
        const dropCondZone=document.getElementById('drop_condition');
        dropCondZone.querySelectorAll('.choice').forEach(ch=>ch.classList.add(data.cond_correct?'correct':'incorrect'));
        // actions
        const correctActionsLower=(data.correct_actions||[]).map(s=>s.toLowerCase());
        document.getElementById('drop_actions').querySelectorAll('.choice').forEach(ch=>{
          if(correctActionsLower.includes(ch.innerText.trim().toLowerCase())) ch.classList.add('correct'); else ch.classList.add('incorrect');
        });
        // parameters
        const correctParamsLower=(data.correct_parameters||[]).map(s=>s.toLowerCase());
        document.getElementById('drop_parameters').querySelectorAll('.choice').forEach(ch=>{
          if(correctParamsLower.includes(ch.innerText.trim().toLowerCase())) ch.classList.add('correct'); else ch.classList.add('incorrect');
        });
        // highlight in banks
        document.querySelectorAll('.choice').forEach(ch=>{
          const txt=ch.innerText.trim().toLowerCase();
          if(correctActionsLower.includes(txt)||correctParamsLower.includes(txt)||(data.correct_condition && txt===data.correct_condition.toLowerCase())) ch.classList.add('highlight-correct');
        });
        // result HTML
        let html='<div style="display:flex; gap:12px; flex-wrap:wrap;">';
        html+=`<div style="min-width:160px;"><strong>Condition:</strong><br><span style="color:${data.cond_correct?'green':'red'}">${data.cond_correct?'Correct':'Incorrect'}</span></div>`;
        html+=`<div style="min-width:160px;"><strong>Actions:</strong><br><span style="color:${data.act_correct?'green':'red'}">${data.act_correct?'Correct':'Incorrect'}</span></div>`;
        html+=`<div style="min-width:160px;"><strong>Parameters:</strong><br><span style="color:${data.param_correct?'green':'red'}">${data.param_correct?'Correct':'Incorrect'}</span></div></div>`;
        html+='<div style="margin-top:10px;"><strong>Correct Answers:</strong><ul style="margin:6px 0 0 18px;">';
        html+=`<li><strong>Condition:</strong> ${escapeHtml(data.correct_condition||'')}</li>`;
        html+=`<li><strong>Actions:</strong> ${(data.correct_actions||[]).map(escapeHtml).join(' — ')}</li>`;
        html+=`<li><strong>Parameters:</strong> ${(data.correct_parameters||[]).map(escapeHtml).join(' — ')}</li></ul></div>`;
        if(data.rationale && data.rationale.trim()!==''){html+='<div class="rationale"><strong>Rationale:</strong><br>'+escapeHtml(data.rationale)+'</div>';}
        document.getElementById('resultArea').innerHTML=html; document.getElementById('resultArea').style.display='block';
    }).catch(err=>{btn.disabled=false; btn.innerText='Submit';alert("An error occurred.");console.error(err);});
});

function escapeHtml(str){return (str||'').replace(/[&<>"']/g,function(m){return({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;"}[m]);});}
</script>

</body>
</html>
