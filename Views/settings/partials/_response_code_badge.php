<?php
/**
 * Reusable Response Code Badge Component
 * 
 * @param int $code - HTTP response code
 */

$badge_class = 'bg-secondary';

if ($code >= 200 && $code < 300) {
    $badge_class = 'bg-success';
} else if ($code >= 300 && $code < 400) {
    $badge_class = 'bg-info';
} else if ($code >= 400 && $code < 500) {
    $badge_class = 'bg-warning';
} else if ($code >= 500) {
    $badge_class = 'bg-danger';
}
?>

<span class="badge <?php echo $badge_class; ?>"><?php echo $code; ?></span>

