<?php

use tuja\frontend\CompetitionSignup;

?>
<?= $intro ?>
<form method="post" data-role-group-leader-label="<?= htmlentities(CompetitionSignup::ROLE_LABEL_GROUP_LEADER) ?>">
	<?= $errors_overall ?>

	<?= $form ?>

	<?= $submit_button ?>
</form>
<?= $fineprint ?>
