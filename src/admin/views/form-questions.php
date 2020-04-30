<?php
namespace tuja\admin;

use tuja\util\ReflectionUtils;

AdminUtils::printTopMenu( $competition );

?>

 <h3>Formulär <?= $this->form->name ?> - Grupp "<?= $this->question_group->text ?: $this->question_group->id; ?>"</h3>

<a id="tuja_form_questions_back" href="<?php echo admin_url('admin.php?page=tuja&tuja_view=Form&tuja_competition=' . $competition->id . '&tuja_form=' . $this->question_group->form_id); ?>">« Tillbaka till grupp</a>

<form method="post">
    <p><strong>Frågor</strong></p>
    <?php
    if(empty($questions)) {
		echo '<p><i>Inga frågor än. Klicka på "Ny fråga" för att lägga till en.';
		echo '<div class="clear"></div>';
	} else {
		ob_start();
		?>
		<button type="submit" name="tuja_action" class="button button-primary" value="questions_update">Spara frågor</button>
        <button type="submit" name="tuja_action" class="button" value="<?= self::ACTION_NAME_CREATE_TEXT ?>">Ny
            textfråga
        </button>
        <button type="submit" name="tuja_action" class="button" value="<?= self::ACTION_NAME_CREATE_NUMBER ?>">Ny
            nummerfråga
        </button>
        <button type="submit" name="tuja_action" class="button" value="<?= self::ACTION_NAME_CREATE_IMAGES ?>">Ny
            bildfråga
        </button>
        <button type="submit" name="tuja_action" class="button" value="<?= self::ACTION_NAME_CREATE_CHOICES ?>">Ny
            flervalsfråga
        </button>
		<?php

		foreach ($questions as $question) {
			echo '<div class="tuja-admin-question">';
			printf( '<div>%s:</div>', substr( get_class( $question ), strrpos( get_class( $question ), '\\' ) + 1 ) );
			echo '<div class="tuja-admin-question-properties">';

			$json       = $question->get_editable_properties_json( $question );
			$field_name = self::FORM_FIELD_NAME_PREFIX . '__' . $question->id;

			$options_schema = $question->json_schema();

			printf( '<div class="tuja-admin-formgenerator-form" data-schema="%s" data-values="%s" data-field-id="%s" id="%s"></div>',
                htmlentities( $options_schema ),
				htmlentities( $json ),
				htmlentities( $field_name ),
				sprintf('tuja-admin-questiongroup-form-%s', $question->id));
			printf( '<input type="hidden" name="%s" id="%s" value="" />', $field_name, $field_name );

			echo '</div>';
			printf('<button type="submit" class="button" name="tuja_action" value="%s%d" onclick="return confirm(\'Är du säker?\');">Ta bort</button>', self::ACTION_NAME_DELETE_PREFIX, $question->id);
			echo '</div>';
		}

		ob_end_flush();
	}
    ?>
    <button type="submit" name="tuja_action" class="button button-primary" value="questions_update">Spara frågor</button>
    <button type="submit" name="tuja_action" class="button" value="<?= self::ACTION_NAME_CREATE_TEXT ?>">Ny textfråga
    </button>
    <button type="submit" name="tuja_action" class="button" value="<?= self::ACTION_NAME_CREATE_NUMBER ?>">Ny
        nummerfråga
    </button>
    <button type="submit" name="tuja_action" class="button" value="<?= self::ACTION_NAME_CREATE_IMAGES ?>">Ny
        bildfråga
    </button>
    <button type="submit" name="tuja_action" class="button" value="<?= self::ACTION_NAME_CREATE_CHOICES ?>">Ny
        flervalsfråga
    </button>
</form>
