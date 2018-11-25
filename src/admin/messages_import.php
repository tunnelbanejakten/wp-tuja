<?php

use data\store\MessageDao;
use data\store\PersonDao;
use util\ImageManager;
use util\MessageImporter;
use util\SmsBackupRestoreXmlFileProcessor;

$competition = $db_competition->get($_GET['tuja_competition']);
if (!$competition) {
    print 'Could not find competition';
    return;
}

// TODO: Make helper function for generating URLs
$competition_url = add_query_arg(array(
    'tuja_competition' => $competition->id,
    'tuja_view' => 'competition'
));

function tuja_get_mms_messages_to_import()
{
    $date_limit = $_POST['tuja_import_onlyrecent'] === 'yes' ? new DateTime('yesterday') : null;

    $importer = new SmsBackupRestoreXmlFileProcessor('.', $date_limit);

    $mms_messages = null;
    if (!empty($_POST['tuja_import_url'])) {
        $mms_messages = $importer->process($_POST['tuja_import_url']);
    } elseif (file_exists($_FILES['tuja_import_file']['tmp_name'])) {
        $mms_messages = $importer->process($_FILES['tuja_import_file']['tmp_name']);
    }
    return $mms_messages;
}

?>

    <h1>Tunnelbanejakten</h1>
    <h2>Tävling <?= sprintf('<a href="%s">%s</a>', $competition_url, $competition->name) ?></h2>
    <h2>Importera SMS och MMS</h2>

<?php
if ($_POST['tuja_points_action'] === 'import') {
    $mms_messages = tuja_get_mms_messages_to_import();

    if (isset($mms_messages)) {
        $im = new ImageManager();

        $person_dao = new PersonDao($wpdb);
        $message_dao = new MessageDao($wpdb);

        $importer = new MessageImporter(
            $message_dao,
            $person_dao->get_all_in_competition($competition->id));

        foreach ($mms_messages as $mms) {
            $text_value = join('. ', $mms->texts);

            $image_value = array_reduce($mms->images, function ($carry, $image_path) use ($im) {
                try {
                    $image_file_hash = $im->import_jpeg($image_path);
                    $carry[] = $image_file_hash;
                    return $carry;
                } catch (Exception $e) {
                    printf('<p>Kunde inte importera: %s</p>', $e->getMessage());
                }
                return $carry;
            }, []);

            try {
                $message = $importer->import($text_value, $image_value, $mms->from, $mms->date);
                printf('<p>Importerade bilderna %s med id=%s</p>', $message->image, $message->source_message_id);
            } catch (Exception $e) {
                printf('<p>Kunde inte importera: %s</p>', $e->getMessage());
            }
        }
    }
} else {
    ?>
    <form method="post" action="<?= add_query_arg() ?>" enctype="multipart/form-data">

        <h3>Krav för att kunna importera SMS och MMS:</h3>
        <ul>
            <li>Du har appen
                <a href="https://play.google.com/store/apps/details?id=com.riteshsahu.SMSBackupRestore&hl=en">
                    SMS Backup &amp; Restore
                </a>
                på din telefon. Denna app finns enbart för Android.
            </li>
            <li>Inställningar i appen:
                <ul>
                    <li>Meddelanden: Ja</li>
                    <li>MMS: Ja</li>
                    <li>Emojis och specialtecken: Nej</li>
                </ul>
            </li>
        </ul>

        <h3>Varje gång du vill importera SMS och MMS gör du så här:</h3>

        <p><strong>Skapa fil</strong></p>
        <ol>
            <li>Starta appen.</li>
            <li>Tryck på <em>Säkerhetskopiera nu</em> så att säkerhetkopian antingen sparas som en fil i din
                mobiltelefon eller sparas som en publik fil på webben (dvs. en fil som går att ladda ner utan
                att behöva logga in någonstans).
            </li>
        </ol>

        <p><strong>Importera fil</strong></p>

        <p>Alternativ 1: Fil som du sparat på din dator eller mobil</p>
        <div>
            <input type="hidden" name="MAX_FILE_SIZE" value="100000000"/>
            <input type="file" name="tuja_import_file" class="file">
        </div>

        <p>Alternativ 2: Fil som finns på nätet</p>
        <div>
            <input type="text" name="tuja_import_url" class="text" placeholder="http://" size="100">
        </div>

        <p><strong>Inställningar</strong></p>

        <div>
            <input type="checkbox" value="yes" name="tuja_import_onlyrecent" id="tuja-import-onlyrecent">
            <label for="tuja-import-onlyrecent">Importera bara meddelanden från idag och igår</label>
        </div>

        <p>Okej, nu är det dags att importera:</p>
        <div>
            <button class="button button-primary" type="submit" name="tuja_points_action" value="import">
                Importera
            </button>
        </div>
    </form>
    <?php
}
?>