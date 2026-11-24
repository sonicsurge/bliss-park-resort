<?php
/**
 * csrf.php — prints the hidden token field used by every form that changes data.
 *
 * security.php loads this file and does the check in csrf_check().
 * A form includes the field by calling csrf_field() between <form> and </form>.
 * The value is escaped with h() because it is placed inside an HTML attribute.
 * The token itself is random; escaping stops a broken token from breaking the page.
 */
function csrf_field(): void
{
    echo '<input type="hidden" name="csrf_token" value="' . h(csrf_token()) . '">';
}
