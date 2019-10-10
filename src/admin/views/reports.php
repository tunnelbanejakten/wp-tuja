<?php
namespace tuja\admin;

AdminUtils::printTopMenu( $competition );
?>

<?php foreach ( $reports as $report ) { ?>
    <h3><?= $report['name'] ?></h3>
    <div>
        <?php
        if ( $report['options_schema'] !== false ) {
            printf( '<div class="tuja-admin-report-config" data-options-schema="%s"></div>', htmlentities( $report['options_schema'] ) );
        }
        ?>
        <div class="tuja-buttons">
            <a href="<?= $report['html_url'] ?>"
               data-original-href="<?= $report['html_url'] ?>"
               title="<?= htmlspecialchars( $report['name'] ) ?>"
               class="thickbox button button-primary"
               target="_blank">
                Visa
                </a>
            <a href="<?= $report['csv_url'] ?>"
               data-original-href="<?= $report['csv_url'] ?>"
               class="button">
                Ladda ner som CSV-fil
                </a>
        </div>
    </div>
<?php } ?>
