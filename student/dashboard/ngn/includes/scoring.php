<?php
/**
 * NGN Exam Standardized Scoring Engine
 * 
 * All scoring functions return an associative array:
 * ['earned' => X, 'max' => Y, 'score' => 0.00-1.00, 'isCorrect' => bool]
 * 
 * Scoring Model: NCLEX NGN "+/- Scoring"
 * - Partial credit for multi-part questions
 * - Penalty for wrong answers on select-type questions
 * - No penalty for placement-type questions (bowtie, drag-drop)
 * - Score always normalized to 0.00–1.00
 */

/**
 * Highlight: +1 per correct highlight, -1 per incorrect highlight, floor 0
 */
function calculateHighlightScore($userAnswers, $correctAnswers) {
    if (!is_array($userAnswers)) $userAnswers = [];
    if (!is_array($correctAnswers)) $correctAnswers = [];
    
    $max = count($correctAnswers);
    if ($max === 0) return ['earned' => 0, 'max' => 0, 'score' => 0.00, 'isCorrect' => false];
    
    // Normalize for comparison
    $normUser = array_map('strtolower', array_map('trim', $userAnswers));
    $normCorrect = array_map('strtolower', array_map('trim', $correctAnswers));
    
    $earned = 0;
    foreach ($normUser as $ans) {
        if (in_array($ans, $normCorrect)) {
            $earned++;
        } else {
            $earned--; // penalty for wrong highlight
        }
    }
    
    $earned = max(0, $earned);
    $score = round($earned / $max, 2);
    
    return [
        'earned' => $earned,
        'max' => $max,
        'score' => min(1.00, $score),
        'isCorrect' => ($earned === $max && count($normUser) === $max)
    ];
}

/**
 * Bowtie: 5 slots (2 actions + 1 condition + 2 parameters), +1 per correct, no penalty
 */
function calculateBowtieScore($userActions, $userConditions, $userParams, $correctActions, $correctConditions, $correctParams) {
    $earned = 0;
    $max = 5; // 2 actions + 1 condition + 2 parameters
    
    // Actions (2 slots)
    if (is_array($userActions)) {
        foreach ($userActions as $ua) {
            if ($ua && in_array(trim($ua), array_map('trim', $correctActions))) {
                $earned++;
            }
        }
    }
    
    // Conditions (1 slot)
    if (is_array($userConditions)) {
        foreach ($userConditions as $uc) {
            if ($uc && in_array(trim($uc), array_map('trim', $correctConditions))) {
                $earned++;
            }
        }
    }
    
    // Parameters (2 slots)
    if (is_array($userParams)) {
        foreach ($userParams as $up) {
            if ($up && in_array(trim($up), array_map('trim', $correctParams))) {
                $earned++;
            }
        }
    }
    
    $score = round($earned / $max, 2);
    
    return [
        'earned' => $earned,
        'max' => $max,
        'score' => $score,
        'isCorrect' => ($earned === $max)
    ];
}

/**
 * MMR (Matrix Multiple Response): +1 per correct cell, -1 per incorrect cell, floor 0
 */
function calculateMMRScore($userMatrix, $correctMatrix) {
    if (!is_array($userMatrix)) $userMatrix = [];
    if (!is_array($correctMatrix)) $correctMatrix = [];
    
    $totalCorrectCells = 0;
    $earned = 0;
    
    foreach ($correctMatrix as $col => $rows) {
        if (!is_array($rows)) continue;
        $totalCorrectCells += count($rows);
    }
    
    if ($totalCorrectCells === 0) return ['earned' => 0, 'max' => 0, 'score' => 0.00, 'isCorrect' => false];
    
    // Check each user selection
    foreach ($userMatrix as $col => $rows) {
        if (!is_array($rows)) continue;
        foreach ($rows as $row) {
            if (isset($correctMatrix[$col]) && in_array($row, $correctMatrix[$col])) {
                $earned++;
            } else {
                $earned--; // penalty
            }
        }
    }
    
    $earned = max(0, $earned);
    $score = round($earned / $totalCorrectCells, 2);
    
    return [
        'earned' => $earned,
        'max' => $totalCorrectCells,
        'score' => min(1.00, $score),
        'isCorrect' => ($earned === $totalCorrectCells)
    ];
}

/**
 * MPR (Multiple Response): +1 per correct, -1 per incorrect, floor 0
 */
function calculateMPRScore($selected, $correct) {
    if (!is_array($selected)) $selected = [];
    if (!is_array($correct)) $correct = [];
    
    $max = count($correct);
    if ($max === 0) return ['earned' => 0, 'max' => 0, 'score' => 0.00, 'isCorrect' => false];
    
    $earned = 0;
    foreach ($selected as $s) {
        if (in_array(trim($s), array_map('trim', $correct))) {
            $earned++;
        } else {
            $earned--; // penalty
        }
    }
    
    $earned = max(0, $earned);
    $score = round($earned / $max, 2);
    
    return [
        'earned' => $earned,
        'max' => $max,
        'score' => min(1.00, $score),
        'isCorrect' => ($earned === $max && count($selected) === $max)
    ];
}

/**
 * Drag & Drop: +1 per correct blank, no penalty
 */
function calculateDragDropScore($userBlanks, $correctBlanks) {
    if (!is_array($userBlanks)) $userBlanks = [];
    if (!is_array($correctBlanks)) $correctBlanks = [];
    
    $max = count($correctBlanks);
    if ($max === 0) return ['earned' => 0, 'max' => 0, 'score' => 0.00, 'isCorrect' => false];
    
    $earned = 0;
    foreach ($correctBlanks as $i => $correct) {
        if (isset($userBlanks[$i]) && trim($userBlanks[$i]) === trim($correct)) {
            $earned++;
        }
    }
    
    $score = round($earned / $max, 2);
    
    return [
        'earned' => $earned,
        'max' => $max,
        'score' => $score,
        'isCorrect' => ($earned === $max)
    ];
}

/**
 * Dropdown: +1 per correct selection, no penalty
 */
function calculateDropdownScore($userChoices, $correctChoices) {
    if (!is_array($userChoices)) $userChoices = [];
    if (!is_array($correctChoices)) $correctChoices = [];
    
    $max = count($correctChoices);
    if ($max === 0) return ['earned' => 0, 'max' => 0, 'score' => 0.00, 'isCorrect' => false];
    
    $earned = 0;
    foreach ($correctChoices as $i => $correct) {
        if (isset($userChoices[$i]) && trim($userChoices[$i]) === trim($correct)) {
            $earned++;
        }
    }
    
    $score = round($earned / $max, 2);
    
    return [
        'earned' => $earned,
        'max' => $max,
        'score' => $score,
        'isCorrect' => ($earned === $max)
    ];
}

/**
 * SATA (Select All That Apply): +1 per correct, -1 per incorrect, floor 0
 */
function calculateSATAScore($selected, $correct) {
    return calculateMPRScore($selected, $correct); // Same logic
}

/**
 * Column Matching: +1 per correct match, no penalty
 */
function calculateColumnScore($userMatchings, $correctMatchings) {
    if (!is_array($userMatchings)) $userMatchings = [];
    if (!is_array($correctMatchings)) $correctMatchings = [];
    
    $max = count($correctMatchings);
    if ($max === 0) return ['earned' => 0, 'max' => 0, 'score' => 0.00, 'isCorrect' => false];
    
    $earned = 0;
    foreach ($correctMatchings as $key => $val) {
        if (isset($userMatchings[$key]) && trim($userMatchings[$key]) === trim($val)) {
            $earned++;
        }
    }
    
    $score = round($earned / $max, 2);
    
    return [
        'earned' => $earned,
        'max' => $max,
        'score' => $score,
        'isCorrect' => ($earned === $max)
    ];
}

/**
 * Traditional (MCQ): All-or-nothing
 */
function calculateTraditionalScore($selected, $correct) {
    $isCorrect = (trim($selected) === trim($correct));
    
    return [
        'earned' => $isCorrect ? 1 : 0,
        'max' => 1,
        'score' => $isCorrect ? 1.00 : 0.00,
        'isCorrect' => $isCorrect
    ];
}
