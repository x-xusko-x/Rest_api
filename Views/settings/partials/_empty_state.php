<?php
/**
 * Reusable Empty State Component
 * 
 * @param string $icon - Feather icon name
 * @param string $title - Empty state title
 * @param string $message - Empty state message
 * @param string $action_url - Optional action button URL
 * @param string $action_text - Optional action button text
 */

$icon = $icon ?? 'inbox';
$title = $title ?? app_lang('no_data_found');
$message = $message ?? '';
?>

<div class="empty-state text-center" style="padding: 60px 20px;">
    <i data-feather="<?php echo $icon; ?>" class="icon-64 text-muted mb20"></i>
    <h4 class="text-muted"><?php echo $title; ?></h4>
    <?php if ($message): ?>
        <p class="text-muted"><?php echo $message; ?></p>
    <?php endif; ?>
    
    <?php if (isset($action_url) && isset($action_text)): ?>
        <a href="<?php echo $action_url; ?>" class="btn btn-primary mt15">
            <?php echo $action_text; ?>
        </a>
    <?php endif; ?>
</div>

<script>
    feather.replace();
</script>

