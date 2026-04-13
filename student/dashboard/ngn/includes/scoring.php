<?php
/**
 * DEPRECATED — Do not add new scoring logic here.
 *
 * All scoring is handled exclusively by /core/ScoringEngine.php.
 * Stubs below exist only to prevent fatal errors in legacy callers.
 *
 * To remove: grep -rn "includes/scoring.php" and delete all includes, then delete this file.
 */

function calculateHighlightScore($u, $c)  { return ScoringEngine::score('highlight',   $u, $c); }
function calculateMMRScore($u, $c)         { return ScoringEngine::score('mmr',         $u, $c); }
function calculateMPRScore($u, $c)         { return ScoringEngine::score('mpr',         $u, $c); }
function calculateDragDropScore($u, $c)    { return ScoringEngine::score('dragndrop',   $u, $c); }
function calculateDropdownScore($u, $c)    { return ScoringEngine::score('dropdown',    $u, $c); }
function calculateSATAScore($u, $c)        { return ScoringEngine::score('sata',        $u, $c); }
function calculateColumnScore($u, $c)      { return ScoringEngine::score('column',      $u, $c); }
function calculateTraditionalScore($u, $c) { return ScoringEngine::score('traditional', $u, $c); }

function calculateBowtieScore($uA, $uC, $uP, $cA, $cC, $cP) {
    $user    = ['actions' => $uA, 'conditions' => $uC, 'parameters' => $uP];
    $correct = ['actions' => $cA, 'conditions' => $cC, 'parameters' => $cP];
    return ScoringEngine::score('bowtie', $user, $correct);
}
