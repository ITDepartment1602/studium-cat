<?php
/**
 * UI/View Helper Functions
 * 
 * Common functions for rendering UI components and views.
 */

/**
 * Render a flash message if exists
 * 
 * @return string HTML for flash message or empty
 */
function renderFlashMessage(): string {
    $types = ['success', 'error', 'warning', 'info'];
    $colors = [
        'success' => '#4caf50',
        'error' => '#f44336',
        'warning' => '#ff9800',
        'info' => '#2196f3'
    ];
    
    foreach ($types as $type) {
        $message = getFlash($type);
        if ($message) {
            return sprintf(
                '<div style="background:%s;color:white;padding:15px;margin:10px 0;border-radius:4px;">%s</div>',
                $colors[$type],
                htmlspecialchars($message)
            );
        }
    }
    
    return '';
}

/**
 * Render pagination
 * 
 * @param int $currentPage Current page number
 * @param int $totalPages Total number of pages
 * @param string $baseUrl Base URL for pagination links
 * @return string HTML pagination
 */
function renderPagination(int $currentPage, int $totalPages, string $baseUrl): string {
    if ($totalPages <= 1) {
        return '';
    }
    
    $html = '<div style="text-align:center;margin:20px 0;">';
    
    // Previous button
    if ($currentPage > 1) {
        $html .= sprintf(
            '<a href="%s?page=%d" style="padding:8px 16px;margin:0 4px;background:#2196f3;color:white;text-decoration:none;border-radius:4px;">← Previous</a>',
            $baseUrl,
            $currentPage - 1
        );
    }
    
    // Page numbers
    $start = max(1, $currentPage - 2);
    $end = min($totalPages, $currentPage + 2);
    
    for ($i = $start; $i <= $end; $i++) {
        if ($i === $currentPage) {
            $html .= sprintf(
                '<span style="padding:8px 16px;margin:0 4px;background:#333;color:white;border-radius:4px;">%d</span>',
                $i
            );
        } else {
            $html .= sprintf(
                '<a href="%s?page=%d" style="padding:8px 16px;margin:0 4px;background:#ddd;color:#333;text-decoration:none;border-radius:4px;">%d</a>',
                $baseUrl,
                $i,
                $i
            );
        }
    }
    
    // Next button
    if ($currentPage < $totalPages) {
        $html .= sprintf(
            '<a href="%s?page=%d" style="padding:8px 16px;margin:0 4px;background:#2196f3;color:white;text-decoration:none;border-radius:4px;">Next →</a>',
            $baseUrl,
            $currentPage + 1
        );
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Format score as percentage with color
 * 
 * @param int $score Score value
 * @param int $total Total possible
 * @return string HTML with colored percentage
 */
function formatScore(int $score, int $total): string {
    if ($total === 0) {
        return '<span style="color:#999">N/A</span>';
    }
    
    $percentage = round(($score / $total) * 100);
    
    if ($percentage >= 75) {
        $color = '#4caf50'; // Green
    } elseif ($percentage >= 50) {
        $color = '#ff9800'; // Orange
    } else {
        $color = '#f44336'; // Red
    }
    
    return sprintf(
        '<span style="color:%s;font-weight:bold;">%d%%</span>',
        $color,
        $percentage
    );
}

/**
 * Render status badge
 * 
 * @param string $status Status text
 * @return string HTML badge
 */
function renderStatusBadge(string $status): string {
    $colors = [
        'active' => '#4caf50',
        'inactive' => '#f44336',
        'pending' => '#ff9800',
        'expired' => '#9e9e9e',
        'completed' => '#2196f3',
        'user' => '#4caf50',
        'admin' => '#9c27b0',
        'paid' => '#4caf50',
        'unpaid' => '#f44336'
    ];
    
    $statusLower = strtolower($status);
    $color = $colors[$statusLower] ?? '#757575';
    
    return sprintf(
        '<span style="background:%s;color:white;padding:4px 8px;border-radius:4px;font-size:12px;text-transform:uppercase;">%s</span>',
        $color,
        htmlspecialchars($status)
    );
}

/**
 * Format relative time (e.g., "2 hours ago")
 * 
 * @param string $datetime DateTime string
 * @return string Formatted relative time
 */
function timeAgo(string $datetime): string {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M d, Y', $time);
    }
}

/**
 * Render user avatar placeholder
 * 
 * @param string $name User name
 * @param int $size Size in pixels
 * @return string HTML avatar
 */
function renderAvatar(string $name, int $size = 40): string {
    $initials = strtoupper(substr($name, 0, 1));
    if (strpos($name, ' ') !== false) {
        $parts = explode(' ', $name);
        $initials = strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1));
    }
    
    $colors = ['#f44336', '#e91e63', '#9c27b0', '#673ab7', '#3f51b5', '#2196f3', '#03a9f4', '#00bcd4', '#009688', '#4caf50'];
    $color = $colors[array_sum(str_split($name)) % count($colors)];
    
    return sprintf(
        '<div style="width:%dpx;height:%dpx;border-radius:50%%;background:%s;color:white;display:flex;align-items:center;justify-content:center;font-weight:bold;font-size:%dpx;">%s</div>',
        $size,
        $size,
        $color,
        $size / 2,
        htmlspecialchars($initials)
    );
}

/**
 * Truncate text with ellipsis
 * 
 * @param string $text Text to truncate
 * @param int $length Max length
 * @return string Truncated text
 */
function truncate(string $text, int $length = 50): string {
    if (strlen($text) <= $length) {
        return $text;
    }
    
    return substr($text, 0, $length) . '...';
}

/**
 * Render data table from array
 * 
 * @param array $data Array of associative arrays
 * @param array $columns Column definitions ['key' => 'Label']
 * @param string|null $actionUrl Base URL for actions
 * @param string|null $idKey Key to use as ID for actions
 * @return string HTML table
 */
function renderDataTable(array $data, array $columns, ?string $actionUrl = null, ?string $idKey = 'id'): string {
    if (empty($data)) {
        return '<p style="text-align:center;color:#999;padding:20px;">No data available</p>';
    }
    
    $html = '<table style="width:100%%;border-collapse:collapse;">';
    
    // Header
    $html .= '<thead><tr style="background:#f5f5f5;">';
    foreach ($columns as $key => $label) {
        $html .= sprintf(
            '<th style="padding:12px;text-align:left;border-bottom:2px solid #ddd;">%s</th>',
            htmlspecialchars($label)
        );
    }
    if ($actionUrl) {
        $html .= '<th style="padding:12px;text-align:center;border-bottom:2px solid #ddd;">Actions</th>';
    }
    $html .= '</tr></thead>';
    
    // Body
    $html .= '<tbody>';
    foreach ($data as $row) {
        $html .= '<tr style="border-bottom:1px solid #eee;">';
        foreach ($columns as $key => $label) {
            $value = $row[$key] ?? '';
            $html .= sprintf(
                '<td style="padding:12px;">%s</td>',
                htmlspecialchars((string)$value)
            );
        }
        if ($actionUrl && isset($row[$idKey])) {
            $html .= sprintf(
                '<td style="padding:12px;text-align:center;">
                    <a href="%s?id=%d" style="color:#2196f3;text-decoration:none;">View</a>
                    <a href="%s?delete=%d" style="color:#f44336;text-decoration:none;margin-left:10px;" onclick="return confirm(\'Delete?\')">Delete</a>
                </td>',
                $actionUrl,
                $row[$idKey],
                $actionUrl,
                $row[$idKey]
            );
        }
        $html .= '</tr>';
    }
    $html .= '</tbody></table>';
    
    return $html;
}

/**
 * Render progress bar
 * 
 * @param int $current Current value
 * @param int $total Total value
 * @param string|null $label Label text
 * @return string HTML progress bar
 */
function renderProgressBar(int $current, int $total, ?string $label = null): string {
    if ($total === 0) {
        $percentage = 0;
    } else {
        $percentage = min(100, round(($current / $total) * 100));
    }
    
    if ($percentage >= 75) {
        $color = '#4caf50';
    } elseif ($percentage >= 50) {
        $color = '#ff9800';
    } else {
        $color = '#f44336';
    }
    
    $html = '<div style="margin:10px 0;">';
    if ($label) {
        $html .= sprintf('<div style="margin-bottom:5px;">%s</div>', htmlspecialchars($label));
    }
    $html .= sprintf(
        '<div style="background:#eee;border-radius:4px;overflow:hidden;">
            <div style="width:%d%%;background:%s;color:white;padding:8px;text-align:center;transition:width 0.3s;">%d%%</div>
        </div>',
        $percentage,
        $color,
        $percentage
    );
    $html .= '</div>';
    
    return $html;
}

/**
 * Get environment indicator (for admin/debug use)
 * 
 * @return string HTML indicator
 */
function renderEnvironmentIndicator(): string {
    $env = getEnvironment();
    $color = $env['is_production'] ? '#f44336' : '#4caf50';
    $text = $env['is_production'] ? 'PRODUCTION' : 'LOCAL';
    
    return sprintf(
        '<div style="position:fixed;bottom:10px;right:10px;background:%s;color:white;padding:8px 16px;border-radius:4px;font-size:12px;font-weight:bold;z-index:9999;">%s</div>',
        $color,
        $text
    );
}
?>
