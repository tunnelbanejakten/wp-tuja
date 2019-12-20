<?php namespace tuja\admin;

use tuja\data\store\GroupCategoryDao;
use tuja\data\store\GroupDao;
use tuja\data\store\PointsDao;
use tuja\data\store\QuestionDao;
use tuja\data\store\ResponseDao;
use tuja\util\score\ScoreCalculator;

AdminUtils::printTopMenu( $competition );
?>

<form method="post" action="<?= add_query_arg() ?>" class="tuja">
    <?php
    foreach ($forms as $form) {
        $url = add_query_arg(array(
            'tuja_view' => 'Form',
            'tuja_form' => $form->id
        ));
        printf( '<p><a href="%s">%s</a></p>', $url, $form->name );
    }
    ?>
    <input type="text" name="tuja_form_name" id="tuja_form_name"/>
    <button type="submit" class="button" name="tuja_action" value="form_create" id="tuja_form_create_button">Skapa</button>
</form>
