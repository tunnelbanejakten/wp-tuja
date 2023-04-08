<?php namespace tuja\admin;

use tuja\data\model\payment\PaymentTransaction;

$this->print_root_menu();
$this->print_menu();
?>

<form method="post" action="<?php echo add_query_arg( array() ); ?>" class="tuja">
	<div>
		<textarea name="tuja_import_raw" id="tuja_import_raw" cols="100" rows="30">
		</textarea>
	</div>
	<button type="submit" class="button button-primary" name="tuja_action" value="<?php echo self::ACTION_NAME_START; ?>" id="tuja_save_button">
		Fortsätt
	</button>
</form>
