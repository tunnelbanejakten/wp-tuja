<?php
namespace tuja\admin;

AdminUtils::printTopMenu( $competition );

?>

<form method="post" action="<?php echo add_query_arg( array() ); ?>" class="tuja">
<?php if ( AdminUtils::is_admin_mode() ) { ?>
		<h3>Radera tävling</h3>

		<p>
			<input type="checkbox" name="tuja_competition_delete_confirm" id="tuja_competition_delete_confirm" value="true">
			<label for="tuja_competition_delete_confirm">
				Ja, jag vill verkligen ta bort tävlingen
			</label>
		</p>

		<div class="tuja-buttons">
			<button type="submit" class="button" name="tuja_action" value="competition_delete">
				Ta bort denna tävling
			</button>
		</div>
	<?php } ?>
</form>
