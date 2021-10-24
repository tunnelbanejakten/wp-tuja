<?php namespace tuja\admin;

use tuja\data\store\GroupCategoryDao;
use tuja\data\store\GroupDao;
use tuja\data\store\PointsDao;
use tuja\data\store\QuestionDao;
use tuja\data\store\ResponseDao;
use tuja\util\score\ScoreCalculator;

?>

<p>
	<?php printf( '<a href="%s">« Tillbaka</a>', $back_link ); ?>
</p>

<form method="post" action="<?php echo add_query_arg( array() ); ?>" class="tuja">
	<div>
		<label for="competition_name">Namn</label><br>
		<input type="text" name="tuja_competition_name" id="tuja_competition_name" value="<?php echo @$_POST['competition_name']; ?>"/>
	</div>
	<?php print join( $checkboxes ); ?>
	<button type="submit" class="button" name="tuja_action" value="competition_bootstrap" id="tuja_competition_bootstrap_button">Skapa</button>
</form>
