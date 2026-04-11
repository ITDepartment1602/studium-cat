<?php
/**
 * ============================================================
 *  NGN SCORING ENGINE
 * ============================================================
 *  Centralized scoring logic for Next Generation NCLEX (NGN)
 *  question types: MMR, Dropdown, Bowtie, SATA, Traditional
 *
 *  Implements partial scoring per NGN specifications.
 * ============================================================
 */

class ScoringEngine {
    
    /**
     * Main scoring entry point
     * 
     * @param string $questionType The question type (mmr, dropdown, bowtie, sata, traditional)
     * @param mixed $userAnswer The user's submitted answer
     * @param mixed $correctAnswer The correct answer from database
     * @return array ['earned' => float, 'max' => int, 'isCorrect' => bool]
     */
    public static function score(string $questionType, $userAnswer, $correctAnswer): array {
        $type = strtolower(trim($questionType));
        
        // Decode JSON if needed
        $user = is_string($userAnswer) ? json_decode($userAnswer, true) : $userAnswer;
        $correct = is_string($correctAnswer) ? json_decode($correctAnswer, true) : $correctAnswer;
        
        switch ($type) {
            case 'mmr':
            case 'matrix':
                return self::scoreMMR($user, $correct);
                
            case 'dropdown':
            case 'ddl':
                return self::scoreDropdown($user, $correct);
                
            case 'bowtie':
            case 'bt':
                return self::scoreBowtie($user, $correct);
                
            case 'sata':
            case 'msr':
                return self::scoreSATA($user, $correct);
                
            case 'traditional':
            case 'mc':
            case 'highlight':
            default:
                return self::scoreTraditional($user, $correct);
        }
    }
    
    /**
     * MMR (Matrix/Multi-Response) Scoring
     * Points = (Correct_Selected) / (Total_Correct_Options)
     */
    private static function scoreMMR($user, $correct): array {
        $user = self::normalizeToArray($user);
        $correct = self::normalizeToArray($correct);
        
        $totalCorrectOptions = count($correct);
        if ($totalCorrectOptions === 0) {
            return ['earned' => 0, 'max' => 1, 'isCorrect' => false];
        }
        
        $correctSelected = 0;
        foreach ($correct as $c) {
            if (in_array($c, $user, true)) {
                $correctSelected++;
            }
        }
        
        $earned = $correctSelected / $totalCorrectOptions;
        
        return [
            'earned' => round($earned, 2),
            'max' => 1,
            'isCorrect' => $earned >= 1.0
        ];
    }
    
    /**
     * Dropdown Scoring
     * Points = (Sum of Correct Blanks) / (Total Blanks)
     */
    private static function scoreDropdown($user, $correct): array {
        $user = self::normalizeToArray($user);
        $correct = self::normalizeToArray($correct);
        
        $totalBlanks = count($correct);
        if ($totalBlanks === 0) {
            return ['earned' => 0, 'max' => 1, 'isCorrect' => false];
        }
        
        $correctBlanks = 0;
        foreach ($correct as $key => $value) {
            $userVal = $user[$key] ?? null;
            if ($userVal !== null && strval($userVal) === strval($value)) {
                $correctBlanks++;
            }
        }
        
        $earned = $correctBlanks / $totalBlanks;
        
        return [
            'earned' => round($earned, 2),
            'max' => 1,
            'isCorrect' => $earned >= 1.0
        ];
    }
    
    /**
     * Bowtie Scoring
     * Each correct action/parameter = 1pt; Max 5pts per question
     * 
     * Expected structure:
     * {
     *   "conditions": [...],
     *   "actions": [...], 
     *   "parameters": [...]
     * }
     */
    private static function scoreBowtie($user, $correct): array {
        $user = is_array($user) ? $user : [];
        $correct = is_array($correct) ? $correct : [];
        
        $maxPoints = 5;
        $earned = 0;
        
        // Score conditions (2 max per section)
        $userConditions = self::normalizeToArray($user['conditions'] ?? []);
        $correctConditions = self::normalizeToArray($correct['conditions'] ?? []);
        $conditionScore = 0;
        foreach ($correctConditions as $c) {
            if (in_array($c, $userConditions, true)) {
                $conditionScore++;
            }
        }
        $earned += min($conditionScore, 2);

        // Score actions (2 max per section)
        $userActions = self::normalizeToArray($user['actions'] ?? []);
        $correctActions = self::normalizeToArray($correct['actions'] ?? []);
        $actionScore = 0;
        foreach ($correctActions as $c) {
            if (in_array($c, $userActions, true)) {
                $actionScore++;
            }
        }
        $earned += min($actionScore, 2);

        // Score parameters (1 max per section)
        $userParams = self::normalizeToArray($user['parameters'] ?? []);
        $correctParams = self::normalizeToArray($correct['parameters'] ?? []);
        $paramScore = 0;
        foreach ($correctParams as $c) {
            if (in_array($c, $userParams, true)) {
                $paramScore++;
            }
        }
        $earned += min($paramScore, 1);

        $earned = min($earned, $maxPoints); // defensive global cap
        
        return [
            'earned' => $earned,
            'max' => $maxPoints,
            'isCorrect' => $earned >= $maxPoints
        ];
    }
    
    /**
     * SATA (Select All That Apply) - STRICT SCORING
     * 1 if ALL correct AND NO wrong selected, 0 if any wrong
     * 
     * Per NGN spec: Use Strict Scoring (1 if all correct, 0 if 1+ wrong)
     */
    private static function scoreSATA($user, $correct): array {
        $user = self::normalizeToArray($user);
        $correct = self::normalizeToArray($correct);
        
        // Check if user selected any wrong answers
        foreach ($user as $u) {
            if (!in_array($u, $correct, true)) {
                // Wrong answer selected - strict scoring = 0
                return ['earned' => 0, 'max' => 1, 'isCorrect' => false];
            }
        }
        
        // Check if user selected ALL correct answers
        $allCorrectSelected = true;
        foreach ($correct as $c) {
            if (!in_array($c, $user, true)) {
                $allCorrectSelected = false;
                break;
            }
        }
        
        $earned = $allCorrectSelected ? 1 : 0;
        
        return [
            'earned' => $earned,
            'max' => 1,
            'isCorrect' => $earned >= 1
        ];
    }
    
    /**
     * Traditional/Highlight/MC Scoring
     * Binary correct/incorrect
     */
    private static function scoreTraditional($user, $correct): array {
        // Normalize to comparable values
        $userVal = is_array($user) ? json_encode($user) : strval($user);
        $correctVal = is_array($correct) ? json_encode($correct) : strval($correct);
        
        $isCorrect = ($userVal === $correctVal);
        
        return [
            'earned' => $isCorrect ? 1 : 0,
            'max' => 1,
            'isCorrect' => $isCorrect
        ];
    }
    
    /**
     * Helper: Normalize value to array
     */
    private static function normalizeToArray($value): array {
        if (is_array($value)) {
            return $value;
        }
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [$value];
        }
        if ($value === null || $value === false) {
            return [];
        }
        return [$value];
    }
    
    /**
     * Detect if an answer is omitted (empty)
     * 
     * @param mixed $answer The user answer to check
     * @return bool True if answer is empty/omitted
     */
    public static function isOmitted($answer): bool {
        if ($answer === null) {
            return true;
        }
        if (is_string($answer)) {
            $trimmed = trim($answer);
            if ($trimmed === '' || $trimmed === '{}' || $trimmed === '[]') {
                return true;
            }
            $decoded = json_decode($trimmed, true);
            if ($decoded !== null && empty($decoded)) {
                return true;
            }
        }
        if (is_array($answer) && empty($answer)) {
            return true;
        }
        return false;
    }
    
    /**
     * Compare two answers for changes
     * 
     * @param mixed $initial The initial answer
     * @param mixed $current The current answer
     * @return bool True if answers are different
     */
    public static function hasChanged($initial, $current): bool {
        $initialStr = is_string($initial) ? $initial : json_encode($initial);
        $currentStr = is_string($current) ? $current : json_encode($current);
        return $initialStr !== $currentStr;
    }
    
    /**
     * CAT: Update Theta (ability estimate) after each question
     * 
     * Simple implementation: +/- 0.3 based on correctness
     * In production, this would use Item Response Theory (IRT)
     * 
     * @param float $currentTheta Current ability estimate
     * @param bool $isCorrect Whether the answer was correct
     * @param float $questionDifficulty Question difficulty logit
     * @return float New theta value
     */
    public static function updateTheta(float $currentTheta, bool $isCorrect, float $questionDifficulty = 0.0): float {
        // Simple adjustment: larger changes when theta and difficulty differ
        $diff = $currentTheta - $questionDifficulty;
        $adjustment = $isCorrect ? 0.3 : -0.3;
        
        // If correct on hard question (difficulty > theta), boost more
        // If wrong on easy question (difficulty < theta), reduce more
        if ($isCorrect && $questionDifficulty > $currentTheta) {
            $adjustment += 0.1;
        }
        if (!$isCorrect && $questionDifficulty < $currentTheta) {
            $adjustment -= 0.1;
        }
        
        $newTheta = $currentTheta + $adjustment;
        
        // Bound theta between -4 and 4 (standard NCLEX range)
        return max(-4.0, min(4.0, $newTheta));
    }
    
    /**
     * CAT: Update Standard Error
     * 
     * SE decreases as more questions are answered
     * 
     * @param int $questionCount Number of questions answered
     * @return float Standard error
     */
    public static function calculateStandardError(int $questionCount): float {
        if ($questionCount <= 0) {
            return 1.0;
        }
        // SE = 1 / sqrt(n) approximated for simple CAT
        return max(0.1, 1.0 / sqrt($questionCount + 1));
    }
    
    /**
     * CAT: Check if exam should stop
     * 
     * Stopping rules per NCLEX:
     * 1. 95% confidence interval entirely above passing (pass)
     * 2. 95% confidence interval entirely below passing (fail)
     * 3. Maximum questions reached (continue to final determination)
     * 
     * @param float $theta Current ability estimate
     * @param float $se Standard error
     * @param float $passingLogit Passing threshold (typically 0.0)
     * @return string|null 'pass', 'fail', or null (continue)
     */
    public static function checkStoppingRule(float $theta, float $se, float $passingLogit = 0.0): ?string {
        // 95% confidence interval: theta ± 1.96 * SE
        $ciLower = $theta - (1.96 * $se);
        $ciUpper = $theta + (1.96 * $se);
        
        // If CI entirely above passing, PASS
        if ($ciLower > $passingLogit) {
            return 'pass';
        }
        
        // If CI entirely below passing, FAIL
        if ($ciUpper < $passingLogit) {
            return 'fail';
        }
        
        // Continue testing
        return null;
    }
}
