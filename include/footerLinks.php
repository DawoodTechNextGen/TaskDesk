<script>
    const role = "<?php echo $_SESSION['user_role']; ?>";
</script>

<?php
// A Collaborator's write access is per-module now (see include/permissions.php).
// The controllers already reject write requests the current module doesn't allow;
// this just keeps the write controls from being clickable in the first place. A
// page with no module mapping (e.g. one a Collaborator has no business on) is
// treated as fully locked down.
if (function_exists('isReadOnlyRole') && isReadOnlyRole()):
    $__page_module = currentPageModule();
    $__page_can_write = $__page_module ? canWriteModule($__page_module) : false;
    if (!$__page_can_write):
?>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const writeSelectors = [
            '.open-modal', '[data-modal]',
            '[class*="edit-"]', '[class*="delete-"]', '[class*="add-"]',
            '[class*="approve-"]', '[class*="reject-"]', '[class*="refund-"]',
            '[class*="reactivate"]', '[class*="decline-"]',
            'button[type="submit"]', 'input[type="submit"]'
        ];
        document.querySelectorAll(writeSelectors.join(',')).forEach(el => {
            el.disabled = true;
            el.classList.add('opacity-40', 'cursor-not-allowed', 'pointer-events-none');
            el.title = 'Read-only access';
        });
    });
</script>
<?php
    endif;
endif;
?>

<script src="./assets/js/tailwind.js"></script>
<script src="./assets/js/script.js"></script>
<script src="./assets/js/searchable.js"></script>