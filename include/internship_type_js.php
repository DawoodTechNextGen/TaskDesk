<?php
// Shared by the registration pipeline pages. Exposes to their scripts whether
// the Internship Type column should be shown and which types an Admin
// currently offers (see internship_types.php), so the column, the inline
// editor and the "Edit Internship Type" modal all follow that setting.
require_once __DIR__ . '/internship_type_helper.php';

$internshipTypeOptions = [];
foreach (getEnabledInternshipTypeValues($conn) as $typeValue) {
    $internshipTypeOptions[] = ['value' => $typeValue, 'label' => internshipTypeLabel($typeValue)];
}
?>
<script>
    const SHOW_INTERNSHIP_TYPE = <?= json_encode(count($internshipTypeOptions) > 0) ?>;
    const INTERNSHIP_TYPE_OPTIONS = <?= json_encode($internshipTypeOptions) ?>;

    // <option> tags for the enabled types, with `current` pre-selected. A
    // placeholder is added when `current` isn't one of them (not set yet, or a
    // type that has since been switched off) so picking any type fires "change".
    function internshipTypeOptionsHtml(current) {
        const known = INTERNSHIP_TYPE_OPTIONS.some(o => String(o.value) === String(current));
        return (known ? '' : '<option value="" selected disabled>Select type</option>') + INTERNSHIP_TYPE_OPTIONS.map(o =>
            `<option value="${o.value}" ${String(current) === String(o.value) ? 'selected' : ''}>${o.label}</option>`
        ).join('');
    }
</script>
