<?php
namespace tuja\admin;

AdminUtils::printTopMenu( $competition );
?>

<table>
    <tbody>
	<?php foreach ( $reports as $report ) { ?>
        <tr>
            <td><?= $report['name'] ?></td>
            <td>
                <a href="<?= $report['html_url'] ?>"
                   title="<?= htmlspecialchars( $report['name'] ) ?>"
                   class="thickbox"
                   target="_blank">
                    HTML
                </a>
            </td>
            <td>
                <a href="<?= $report['csv_url'] ?>">
                    CSV
                </a>
            </td>
        </tr>
	<?php } ?>
    </tbody>
</table>
