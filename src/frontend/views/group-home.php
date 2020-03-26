<?php

use tuja\util\Strings;

?>
<?= $incomplete_message ?>
<p>
	<?php printf( '<a href="%s" id="tuja_edit_group_link">%s</a>', $edit_group_link, Strings::get( 'home.link.edit_group' ) ) ?>
</p>
<p>
	<?php printf( '<a href="%s" id="tuja_edit_people_link">%s</a>', $edit_people_link, Strings::get( 'home.link.edit_people' ) ) ?>
</p>
<p>
	<?php printf( '<a href="%s" id="tuja_tickets_link">%s</a>', $tickets_link, Strings::get( 'home.link.tickets' ) ) ?>
</p>
<p>
	<?php printf( '<a href="%s" id="tuja_unregister_team_link">%s</a>', $unregister_team_link, Strings::get( 'home.link.unregister_team' ) ) ?>
</p>
