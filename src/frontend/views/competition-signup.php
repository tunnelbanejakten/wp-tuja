<?php

use tuja\frontend\CompetitionSignup;

?>
<?= $intro ?>
<form method="post" data-role-group-leader-label="<?= htmlentities(CompetitionSignup::ROLE_LABEL_GROUP_LEADER) ?>">
	<?= $errors_overall ?>

	<?= $form ?>

    <div id="tuja-competitionsignup-active-form"></div>

	<?= $submit_button ?>
</form>
<div id="tuja-competitionsignup-inactive-forms" style="display: none;">
	<?= $person_forms ?>
</div>
<?= $fineprint ?>
