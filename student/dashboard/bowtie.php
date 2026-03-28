<?php
include "../../config.php";

// Fetch a bowtie question
$id = isset($_GET['id']) ? intval($_GET['id']) : 1;

$stmt = $con->prepare("SELECT * FROM bowtie_questions WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$question = $result->fetch_assoc();

if (!$question) {
    die("Question not found");
}

// Prepare case studies and answers
$caseStudies = explode("||", $question['items']);
$answers = explode("||", $question['targets']);

// Prepare correct answers per column
$correctAnswers = [
    'actionsToTake' => explode("||", $question['actionsToTake']),
    'potentialConditions' => explode("||", $question['potentialConditions']),
    'parametersToMonitor' => explode("||", $question['parametersToMonitor'])
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>NCLEX Bowtie Question</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">

<div class="max-w-5xl mx-auto bg-white p-6 rounded shadow">
    <!-- Topic & Question -->
    <h2 class="text-2xl font-bold mb-2"><?php echo htmlspecialchars($question['topic']); ?></h2>
    <p class="mb-4"><?php echo htmlspecialchars($question['question']); ?></p>

    <!-- Case Studies -->
    <div class="mb-4 grid grid-cols-3 gap-4">
        <?php foreach($caseStudies as $cs): ?>
            <button class="case-btn bg-blue-500 text-white py-2 px-4 rounded"
                    data-content="<?php echo htmlspecialchars($cs); ?>">
                <?php echo htmlspecialchars($cs); ?>
            </button>
        <?php endforeach; ?>
    </div>

    <div id="caseContent" class="mb-6 p-4 bg-gray-50 rounded hidden"></div>

    <!-- Bowtie Table -->
    <div class="grid grid-cols-3 gap-4 mb-6">
        <?php
        $columns = [
            'actionsToTake' => 'Actions to Take',
            'potentialConditions' => 'Condition Most Likely Experiencing',
            'parametersToMonitor' => 'Parameter to Monitor'
        ];
        foreach($columns as $key => $label):
        ?>
            <div class="bg-white p-4 rounded shadow">
                <h3 class="font-semibold mb-2"><?php echo $label; ?></h3>
                <ul id="<?php echo $key; ?>"
                    class="dropzone min-h-[150px] border-2 border-dashed border-gray-300 p-2 rounded"
                    data-column="<?php echo $key; ?>">
                </ul>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Draggable Answers -->
    <div>
        <h3 class="font-semibold mb-2">Drag & Drop Options:</h3>
        <ul id="answers" class="flex flex-wrap gap-2">
            <?php foreach($answers as $ans): ?>
                <?php
                    // Determine which column this answer belongs to
                    $columnType = '';
                    foreach($correctAnswers as $col => $vals){
                        if(in_array($ans, $vals)){
                            $columnType = $col;
                            break;
                        }
                    }
                ?>
                <li class="draggable bg-gray-200 p-2 rounded cursor-move"
                    draggable="true"
                    data-column="<?php echo $columnType; ?>">
                    <?php echo htmlspecialchars($ans); ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <!-- Submit & Results -->
    <button id="submitBtn" class="mt-4 bg-green-500 text-white px-4 py-2 rounded">Submit</button>

    <div id="results" class="mt-4 hidden bg-yellow-50 p-4 rounded"></div>
</div>

<script>
// Case study toggle
document.querySelectorAll('.case-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const content = btn.dataset.content;
        const csDiv = document.getElementById('caseContent');
        csDiv.textContent = content;
        csDiv.classList.remove('hidden');
    });
});

// Drag & drop logic with column restriction
let dragged = null;

document.addEventListener("dragstart", function(event) {
    if(event.target.classList.contains('draggable')){
        dragged = event.target;
    }
});

document.querySelectorAll('.dropzone').forEach(zone => {
    zone.addEventListener("dragover", function(event) {
        event.preventDefault();
    });

    zone.addEventListener("drop", function(event) {
        event.preventDefault();
        if(!dragged) return;

        // Only allow drop if column matches
        const zoneColumn = this.dataset.column;
        const dragColumn = dragged.dataset.column;

        if(zoneColumn === dragColumn){
            this.appendChild(dragged);
        } else {
            alert("You cannot place this answer in this column!");
        }
    });
});

// Submit logic
const rationale = <?php echo json_encode($question['rationale']); ?>;
const cnc = <?php echo json_encode($question['cnc']); ?>;
const narcan = <?php echo json_encode($question['narcan']); ?>;
const system = <?php echo json_encode($question['system']); ?>;
const dlevel = <?php echo json_encode($question['dlevel']); ?>;

document.getElementById('submitBtn').addEventListener('click', function() {
    const resultsDiv = document.getElementById('results');
    resultsDiv.classList.remove('hidden');

    resultsDiv.innerHTML = `
        <p><strong>Rationale:</strong> ${rationale}</p>
        <p><strong>Client Needs:</strong> ${cnc}</p>
        <p><strong>NARC Notes:</strong> ${narcan}</p>
        <p><strong>System:</strong> ${system}</p>
        <p><strong>Difficulty:</strong> ${dlevel}</p>
    `;
});
</script>

</body>
</html>
