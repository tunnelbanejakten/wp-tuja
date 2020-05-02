<?php

use tuja\util\Strings;

?>
<form method="post">
	<?= $errors_overall ?>

	<?= join($forms) ?>

	<?= $form_save_button ?>

</form>

<p class="tuja-buttons">
	<?php printf( '<a href="%s">Tillbaka</a>', $home_link ) ?>
</p>
