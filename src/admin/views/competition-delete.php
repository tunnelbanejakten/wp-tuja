<?php
namespace tuja\admin;

AdminUtils::printTopMenu( $competition );

?>

<form method="post" action="<?php echo add_query_arg( array() ); ?>" class="tuja">
		<h3>Dataskydd</h3>

		<p>Om du inte vill ta bort lagen men ändå vill skydda personuppgifterna så kan du använda verktyget för att
			anonymisera personuppgifter.</p>
		<p>
			<input type="radio" name="tuja_anonymizer_filter" value="all" id="tuja_anonymizer_filter_all"><label
					for="tuja_anonymizer_filter_all">Anonymisera personuppgifter för <em>alla tävlande och
					funktionärer</em></label><br/>
			<input type="radio" name="tuja_anonymizer_filter" value="participants"
				   id="tuja_anonymizer_filter_participants"><label for="tuja_anonymizer_filter_participants">Anonymisera
				personuppgifter för <em>alla tävlande</em></label><br/>
			<input type="radio" name="tuja_anonymizer_filter" value="non_contacts"
				   id="tuja_anonymizer_filter_non_contacts"><label for="tuja_anonymizer_filter_non_contacts">Anonymisera
				personuppgifter för <em>alla tävlande utom kontaktpersoner</em></label><br/>
		</p>
		<p><input type="checkbox" name="tuja_anonymizer_confirm" id="tuja_anonymizer_confirm" value="true"><label
					for="tuja_anonymizer_confirm">Ja, jag vill verkligen anonymisera personuppgifterna</label></p>

		<div class="tuja-buttons">
			<button type="submit" class="button" name="tuja_action" value="anonymize">Anonymisera valda
				personuppgifter
			</button>
		</div>

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
</form>
