<form method="post" action="<?= add_query_arg() ?>">

    <h1>Tunnelbanejakten</h1>
    <h2>Tävlingar</h2>
    <?php
    $competitions = $db_competition->get_all();
    foreach ($competitions as $competition) {
        $url = add_query_arg(array(
            'tuja_view' => 'competition',
            'tuja_competition' => $competition->id
        ));
        printf('<p><a href="%s">%s</a></p>', $url, $competition->name);
    }
    ?>

    <input type="text" name="tuja_competition_name"/>
    <button type="submit" name="tuja_action" value="competition_create">Skapa</button>
</form>
<?php
$url = add_query_arg(array(
    'tuja_view' => 'settings'
));
printf('<p><a href="%s">Inställningar</a></p>', $url);
?>