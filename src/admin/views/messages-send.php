<?php 
	namespace tuja\admin; 
	
	use tuja\data\model\Group;
	use tuja\data\model\Person;
?>

<h1>Tunnelbanejakten</h1>
<h2>Tävling <?= sprintf('<a href="%s">%s</a>', $competition_url, $this->competition->name) ?></h2>
<h3>Skicka e-post och SMS</h3>

<form method="post" action="<?= add_query_arg() ?>">
	<p><strong>Mottagare och distribution</strong></p>
	<div style="float: left;">
		<label for="">Välj grupp(er) att skicka till:</label><br>
		<select name="tuja_messages_group_selector">
			<?= join(array_map(function ($index, $group_selector) {
				return sprintf('<option value="%d" %s>%s</option>',
					$index,
					$_POST['tuja_messages_group_selector'] == $index ? ' selected="selected"' : '',
					$group_selector['label']);
			}, array_keys($group_selectors), array_values($group_selectors))) ?>
		</select>
	</div>
	<div style="float: left;">
		Välj mottagare i valda grupper:<br>
		<?= join(array_map(function ($key, $person_selector) {
			$id = uniqid();
			return sprintf('<div><input type="radio" name="tuja_messages_people_selector" id="%s" value="%s" %s/><label for="%s">%s</label></div>',
				$id,
				$key,
				$_POST['tuja_messages_people_selector'] == $key ? ' checked="checked"' : '',
				$id,
				$person_selector['label']);
		}, array_keys($people_selectors), array_values($people_selectors))); ?>
	</div>
	<div style="float: left;">
		Välj format:<br>
		<?= join(array_map(function ($key, $delivery_method) {
			$id = uniqid();
			return sprintf('<div><input type="radio" name="tuja_messages_delivery_method" id="%s" value="%s" %s/><label for="%s">%s</label></div>',
				$id,
				$key,
				$_POST['tuja_messages_delivery_method'] == $key ? ' checked="checked"' : '',
				$id,
				$delivery_method['label']);
		}, array_keys($delivery_methods), array_values($delivery_methods))); ?>
	</div>
	<div style="clear: both"></div>

	<p><strong>Meddelande</strong></p>

	<div style="float: left;">
		<div>
			<label for="tuja-message-subject">Ämne:</label><br>
			<input type="text"
				name="tuja_messages_subject"
				id="tuja-message-subject"
				size="50"
				value="<?= $_POST['tuja_messages_subject'] ?>">
		</div>

		<div>
			<label for="tuja-message-body">Meddelande:</label><br>
			<textarea name="tuja_messages_body"
					id="tuja-message-body"
					cols="80"
					rows="10"><?= $_POST['tuja_messages_body'] ?></textarea>
		</div>
	</div>

	<div style="float: left; max-width: 30em; margin-left: 1em;">
		Meddelandemallar (ändra mallarna på <a href="<?= $settings_url ?>">inställningssidan</a>):<br>
		<?= join('<br>', array_map(function ($template) {
			return sprintf('<a class="tuja-messages-template-link" href="#" data-value="%s;%s">%s</a>',
				rawurlencode($template->subject),
				rawurlencode($template->body),
				$template->name
			);
		}, $templates)) ?>
		<br>I texten kan du använda följande variabler: <br><?= join('<br>', array_map(function ($var) {
			return sprintf('<tt>{{%s}}</tt>', $var);
		}, array_keys($this->get_parameters(new Person(), new Group())))) ?>
		<br>Utöver variabler kan du även använda <a href="https://daringfireball.net/projects/markdown/basics">Markdown</a>
		för att göra fet text, lägga in länkar mm.
	</div>

	<div style="clear: both"></div>

	<?php if ($is_preview) { ?>
		<div>
			<button class="button" type="submit" name="tuja_messages_action" value="preview">
				Förhandsgranska utskick
			</button>
			<button class="button button-primary" type="submit" name="tuja_messages_action" value="send">
				Skicka
			</button>
		</div>
	<?php } elseif ($is_send) { ?>
		<div>
			<button class="button button-primary" type="submit" name="tuja_messages_action" value="preview">
				Förhandsgranska utskick
			</button>
			<button class="button" type="button" disabled="disabled">
				Skicka
			</button>
		</div>
	<?php } else { ?>
		<div>
			<button class="button button-primary" type="submit" name="tuja_messages_action" value="preview">
				Förhandsgranska utskick
			</button>
			<button class="button" type="button" disabled="disabled">
				Skicka
			</button>
		</div>
	<?php } ?>
</form>