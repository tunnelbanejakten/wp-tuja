<?php
namespace tuja\admin;

use tuja\data\model\Group;
use tuja\data\model\MessageTemplate;
use tuja\data\model\Person;
use tuja\util\TemplateEditor;

$this->print_root_menu();
$this->print_leaves_menu();
?>

<h3>Skicka e-post och SMS</h3>

<form method="post" action="<?= add_query_arg( [] ) ?>" class="tuja">
    <div style="float: left;">
        <label for="">Välj grupp(er) att skicka till:</label><br>
	    <?php
	    $field_group_selector->render(
		    'tuja_messages_group_selector',
		    @$_POST['tuja_messages_group_selector'] );
	    ?>
    </div>
    <div style="float: left;">
        Välj mottagare i valda grupper:<br>
	    <?= sprintf( '<div><select name="tuja_messages_people_selector">%s</select></div>',
		    join( array_map( function ( $key, $person_selector ) {
			    $id = uniqid();

			    return sprintf( '<option value="%s" %s>%s</option>',
				    $key,
				    @$_POST['tuja_messages_people_selector'] == $key ? ' selected="selected"' : '',
				    $person_selector['label'] );
		    }, array_keys( $people_selectors ), array_values( $people_selectors ) ) ) ); ?>
    </div>
    <div style="float: left;">
        Välj format:<br>
		<?= sprintf( '<div><select name="tuja_messages_delivery_method" id="tuja-message-deliverymethod">%s</select></div>',
			join( array_map( function ( $key, $delivery_method ) {
				$id = uniqid();

				return sprintf( '<option value="%s" %s>%s</option>',
					$key,
					( @$_POST['tuja_messages_delivery_method'] ?: MessageTemplate::EMAIL ) == $key ? ' selected="selected"' : '',
					$delivery_method['label'] );
			}, array_keys( $delivery_methods ), array_values( $delivery_methods ) ) ) ); ?>
    </div>
    <div style="clear: both"></div>

    <div style="float: left;">
        <div>
            <label for="tuja-message-subject">Ämne:</label><br>
            <input type="text"
                   name="tuja_messages_subject"
                   id="tuja-message-subject"
                   size="50"
                   value="<?= @$_POST['tuja_messages_subject'] ?>">
        </div>

        <div>
            <label for="tuja_messages_body">Meddelande:</label><br>
            <?= TemplateEditor::render(
            	'tuja_messages_body',
	            @$_POST['tuja_messages_body'] ?: '', $this->get_parameters( Person::sample(), Group::sample() )
            ) ?>
        </div>
    </div>

    <div style="float: left; max-width: 30em; margin-left: 1em;">
        Meddelandemallar (ändra mallarna på <a href="<?= $settings_url ?>">inställningssidan</a>):<br>
		<?= join( '<br>', array_map( function ( MessageTemplate $template ) {
			return sprintf( '<a class="tuja-messages-template-link" href="#" data-value="%s;%s;%s">%s</a>',
				rawurlencode( $template->subject ),
				rawurlencode( $template->body ),
				rawurlencode( $template->delivery_method ),
				$template->name
			);
		}, $templates ) ) ?>
    </div>

    <div style="clear: both"></div>

	<?php if ( $is_preview ) { ?>
        <div>
            <button class="button" type="submit" name="tuja_messages_action" value="preview">
                Förhandsgranska utskick
            </button>
            <button class="button button-primary" type="submit" name="tuja_messages_action" value="send">
                Skicka
            </button>
        </div>
	<?php } elseif ( $is_send ) { ?>
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
	<?php
	if ( ! empty( $specific_recipients ) ) {
		printf( '<input type="hidden" name="tuja_messages_specificrecipients" value="%s">',
			join( ',', $specific_recipients ) );
	}
	?>
</form>
	<?php
if ( ! empty( $action_result ) ) {
	$variables        = $action_result['variables'];
	$body_template    = $action_result['body_template'];
	$subject_template = $action_result['subject_template'];
	$warnings         = $action_result['warnings'];

	if ( count( $warnings ) > 0 ) {
		foreach ( $warnings as $warning ) {
			AdminUtils::printError( $warning );
		}
	}

	$variables_headers_html = join( array_map( function ( $variable ) {
		return sprintf( '<td><strong>%s</strong></td>', $variable );
	}, $variables ) );

	printf( '<table class="tuja-table">' .
	        '  <thead>' .
	        '  <tr>' .
	        '    <td colspan="2" rowspan="2" valign="top"><strong>Mottagare</strong>' .
	        '    <td colspan="%d"><strong>Variabler</strong></td>' .
	        '    <td colspan="2" rowspan="2" valign="top"><strong>Förhandsgranskning</strong></td>' .
	        '  </tr>' .
	        '  <tr>' .
	        '    %s' .
	        '  </tr>' .
	        '  </thead>' .
	        '  <tbody>' .
	        '    %s' .
	        '  </tbody>' .
	        '</table>',
		count( $variables ),
		$variables_headers_html,
		join( array_map( function ( $result ) use ( $variables, $subject_template, $body_template ) {
			$person_name         = $result['person_name'];
			$template_parameters = $result['template_parameters'];
			$message             = $result['message'];
			$message_css_class   = $result['message_css_class'];
			$message_to          = $result['message_to'];
			$is_plain_text_body  = $result['is_plain_text_body'];

			$variables_values_html = join( array_map( function ( $variable ) use ( $template_parameters ) {
				return sprintf( '<td valign="top"><code>%s</code></td>', $template_parameters[ $variable ] );
			}, $variables ) );

			$preview_html = sprintf( '<div class="tuja-message-preview">%s</div><div class="tuja-message-preview %s">%s</div>',
				$subject_template->render( $template_parameters ),
				$is_plain_text_body ? 'tuja-message-preview-plaintext' : 'tuja-message-preview-html',
				$body_template->render( $template_parameters ) );

			return sprintf(
				'<tr>' .
				'  <td valign="top">%s (%s)</td>' .
				'  <td valign="top"><span class="tuja-admin-review-autoscore %s">%s</span></td>' .
				'  %s' .
				'  <td valign="top">%s</td>' .
				'</tr>',
				$person_name,
				$message_to,
				$message_css_class,
				$message,
				$variables_values_html,
				$preview_html );
		}, $action_result['recipients'] ) ) );
}

?>