<?php
/**
 * Reusable HTTP Method Badge Component
 * 
 * @param string $method - HTTP method (GET, POST, PUT, DELETE, PATCH)
 */

$method_colors = [
    'GET' => 'bg-success',
    'POST' => 'bg-primary',
    'PUT' => 'bg-warning',
    'DELETE' => 'bg-danger',
    'PATCH' => 'bg-info'
];

$badge_class = $method_colors[$method] ?? 'bg-secondary';
?>

<span class="badge <?php echo $badge_class; ?>"><?php echo $method; ?></span>

