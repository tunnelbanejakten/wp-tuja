<?php
namespace tuja\admin;

use DateTime;
use tuja\view\FieldImages;

AdminUtils::printTopMenu( $competition );
?>

<h3>Importera</h3>
<?php
$import_url = add_query_arg(array(
	'tuja_competition' => $this->competition->id,
	'tuja_view'        => 'MessagesImport'
));
printf('<p><a href="%s">Importera meddelanden</a></p>', $import_url);
?>

<h3>Skicka</h3>
<?php
$import_url = add_query_arg(array(
	'tuja_competition' => $this->competition->id,
	'tuja_view'        => 'MessagesSend'
));
printf('<p><a href="%s">Skicka meddelanden</a></p>', $import_url);
?>

<h3>Meddelanden utan tydlig avsändare</h3>
<p>De här meddelandena har inte kunnat kopplas till någon av de tävlande lagen:</p>
<table>
    <tbody>

    <?php
    // TODO: Show messages nicer (also in group.php)
    foreach ($messages as $message) {
        if (is_array($message->image_ids) && count($message->image_ids) > 0) {
            $field = new FieldImages([]);
            // For each user-provided answer, render the photo description and a photo thumbnail:
            $images = array_map(function ($image_id) use ($field) {
                return $field->render_admin_preview("$image_id,,");
            }, $message->image_ids);
        } else {
            $images = [];
        }

        printf('<tr>' .
            '<td valign="top">%s</td>' .
            '<td valign="top">%s</td>' .
            '<td valign="top">%s</td>' .
            '<td valign="top">%s</td>' .
            '</tr>',
            $message->date_received->format(DateTime::ISO8601),
            join('', $images),
            $message->text,
            $message->source_message_id);
    }
    ?>
    </tbody>
</table>
