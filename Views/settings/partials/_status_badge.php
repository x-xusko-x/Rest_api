<?php
/**
 * Reusable Status Badge Component
 * 
 * @param string $status - The status value (active, inactive, revoked, etc.)
 * @param array $colors - Optional color mapping ['status' => 'bg-class']
 */

$default_colors = [
    'active' => 'bg-success',
    'inactive' => 'bg-secondary',
    'revoked' => 'bg-danger',
    'expired' => 'bg-warning',
    'pending' => 'bg-info'
];

$colors = $colors ?? $default_colors;
$badge_class = $colors[$status] ?? 'bg-secondary';
$label = app_lang($status);
?>

<span class="badge <?php echo $badge_class; ?>"><?php echo $label; ?></span>

