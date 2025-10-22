<?php
/**
 * Reusable Copy to Clipboard Button
 * 
 * @param string $value - The value to copy
 * @param string $label - Button label (default: "Copy")
 * @param string $success_message - Success message after copy
 */

$label = $label ?? app_lang('copy');
$success_message = $success_message ?? app_lang('copied_to_clipboard');
$button_id = 'copy-btn-' . uniqid();
?>

<button 
    type="button" 
    id="<?php echo $button_id; ?>" 
    class="btn btn-default copy-to-clipboard" 
    data-value="<?php echo htmlspecialchars($value); ?>"
    data-success-message="<?php echo htmlspecialchars($success_message); ?>">
    <i data-feather="copy" class="icon-16"></i> <?php echo $label; ?>
</button>

<script>
$(document).ready(function() {
    $('#<?php echo $button_id; ?>').on('click', function() {
        var value = $(this).data('value');
        var message = $(this).data('success-message');
        
        navigator.clipboard.writeText(value).then(function() {
            appAlert.success(message, {duration: 2000});
        });
    });
    
    feather.replace();
});
</script>

