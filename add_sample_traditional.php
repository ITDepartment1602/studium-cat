<?php
include 'config.php';
session_start();

// Sample questions for traditional (Multiple Choice)
$samples = [
    [
        'question' => 'A 45-year-old patient presents with chest pain and dyspnea. What is the most likely diagnosis?',
        'choices' => json_encode(['Myocardial infarction', 'Pneumonia', 'Pulmonary embolism', 'Anxiety disorder']),
        'correct' => 'A',
        'rationale' => 'The combination of chest pain and dyspnea in a 45-year-old suggests acute coronary syndrome (ACS). Myocardial infarction is the most common cause of acute chest pain in this age group.',
        'topic' => 'Cardiology',
        'system' => 'Cardiovascular',
        'cnc' => 'Assessment',
        'dlevel' => 'Moderate'
    ],
    [
        'question' => 'Which medication is used as first-line treatment for hypertension in a patient with diabetes?',
        'choices' => json_encode(['ACE inhibitors', 'Beta-blockers', 'Calcium channel blockers', 'Diuretics']),
        'correct' => 'A',
        'rationale' => 'ACE inhibitors and ARBs are preferred first-line agents for hypertensive patients with diabetes due to their renal protective effects.',
        'topic' => 'Pharmacology',
        'system' => 'Endocrine',
        'cnc' => 'Intervention',
        'dlevel' => 'Moderate'
    ],
    [
        'question' => 'A patient with atrial fibrillation is prescribed warfarin. What is the most important lab value to monitor?',
        'choices' => json_encode(['Platelet count', 'INR (International Normalized Ratio)', 'Prothrombin time', 'Hemoglobin level']),
        'correct' => 'B',
        'rationale' => 'INR is the standard measure of warfarin therapy effectiveness. Target INR for most atrial fibrillation patients is 2-3.',
        'topic' => 'Pharmacology',
        'system' => 'Cardiovascular',
        'cnc' => 'Monitoring',
        'dlevel' => 'Moderate'
    ],
    [
        'question' => 'During morning rounds, a nurse notices a patient\'s oxygen saturation has dropped from 96% to 89%. What is the initial nursing action?',
        'choices' => json_encode(['Administer oxygen', 'Check vital signs and assess respiratory status', 'Call the physician immediately without assessment', 'Document the change and continue rounds']),
        'correct' => 'B',
        'rationale' => 'The initial nursing action is to assess the patient comprehensively before notifying the physician. This includes checking vital signs, respiratory effort, and auscultating lung sounds.',
        'topic' => 'Nursing Assessment',
        'system' => 'Respiratory',
        'cnc' => 'Assessment',
        'dlevel' => 'Moderate'
    ],
    [
        'question' => 'A 72-year-old patient with heart failure is experiencing signs of acute decompensation. Which finding would be expected?',
        'choices' => json_encode(['Decreased breath sounds', 'Pink frothy sputum', 'Hyperkalemia', 'Increased urine output']),
        'correct' => 'B',
        'rationale' => 'Pink frothy sputum is a classic sign of acute pulmonary edema in heart failure, indicating pulmonary congestion from left ventricular failure.',
        'topic' => 'Pathophysiology',
        'system' => 'Cardiovascular',
        'cnc' => 'Assessment',
        'dlevel' => 'Moderate'
    ]
];

// Insert samples
$count = 0;
foreach ($samples as $q) {
    $question = mysqli_real_escape_string($con, $q['question']);
    $choices = mysqli_real_escape_string($con, $q['choices']);
    $correct = mysqli_real_escape_string($con, $q['correct']);
    $rationale = mysqli_real_escape_string($con, $q['rationale']);
    $topic = mysqli_real_escape_string($con, $q['topic']);
    $system = mysqli_real_escape_string($con, $q['system']);
    $cnc = mysqli_real_escape_string($con, $q['cnc']);
    $dlevel = mysqli_real_escape_string($con, $q['dlevel']);
    
    $sql = "INSERT INTO traditional 
            (question, choices, correct, rationale, topic, system, cnc, dlevel) 
            VALUES ('$question', '$choices', '$correct', '$rationale', '$topic', '$system', '$cnc', '$dlevel')";
    
    if (mysqli_query($con, $sql)) {
        $count++;
    } else {
        echo "Error inserting question: " . mysqli_error($con) . "\n";
    }
}

echo "SUCCESS: $count sample questions added to the traditional table!\n";
echo "You can now see Multiple Choice questions in your exam.\n";
?>
