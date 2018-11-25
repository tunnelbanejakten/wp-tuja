<?php

use tuja\view\FieldImages;

$competition = $db_competition->get($_GET['tuja_competition']);
if (!$competition) {
    print 'Could not find competition';
    return;
}

$competition_url = add_query_arg(array(
    'tuja_competition' => $competition->id,
    'tuja_view' => 'competition'
));
?>

<h1>Tunnelbanejakten</h1>
<h2>Tävling <?= sprintf('<a href="%s">%s</a>', $competition_url, $competition->name) ?></h2>

<h3>Importera</h3>
<?php
$import_url = add_query_arg(array(
    'tuja_competition' => $competition->id,
    'tuja_view' => 'messages_import'
));
printf('<p><a href="%s">Importera meddelanden</a></p>', $import_url);
?>

<h3>Meddelanden utan tydlig avsändare</h3>
<p>De här meddelandena har inte kunnat kopplas till någon av de tävlande lagen:</p>
<table>
    <tbody>

    <?php
    // TODO: Show messages nicer (also in group.php)
    $messages = $db_message->get_without_group();
    foreach ($messages as $message) {
        $image_ids = explode(',', $message->image);
        if (is_array($image_ids)) {
            $field = new FieldImages([]);
            // For each user-provided answer, render the photo description and a photo thumbnail:
            $images = array_map(function ($image_id) use ($field) {
                return $field->render_admin_preview("$image_id,,");
            }, $image_ids);
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
