<?php

use tuja\data\model\Ticket;
use tuja\util\Strings;

?>
<form method="post">
	<?php echo $error_message; ?>
	<?php echo $success_message; ?>

	<?php echo $field_description_html; ?>
	<?php echo $field_amount_html; ?>
	<?php echo $field_date_html; ?>

	<?php echo $field_name_html; ?>
	<?php echo $field_email_html; ?>
	<?php echo $field_bank_account_html; ?>

	<div class="tuja-expense-report">
		<?php echo $button; ?>
	</div>

</form>
