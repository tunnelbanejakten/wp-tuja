<?php

use tuja\frontend\CompetitionSignup;
use tuja\util\Strings;

?>
<?= $intro ?>
<form method="post" data-role-group-leader-label="<?= htmlentities(Strings::get('competition_signup.role_label.group_leader')) ?>">
	<?= $errors_overall ?>

	<?= $form ?>

    <div id="tuja-competitionsignup-active-form"></div>

	<?= $submit_button ?>
</form>
<div id="tuja-competitionsignup-inactive-forms" style="display: none;">
	<?= $person_forms ?>
</div>
<?= $fineprint ?>
