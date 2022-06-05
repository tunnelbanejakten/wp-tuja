<?php namespace tuja\admin; ?>

<div class="tuja tuja-view-index">
    <form method="post" action="<?= add_query_arg( [] ) ?>" class="tuja">
		<?php
		$url = add_query_arg( array(
			'tuja_view' => 'Settings'
			) );
		printf( '<p><a href="%s">Inställningar</a></p>', $url );
		?>
        <h1>Tävlingar</h1>
		<?php
		foreach ( $competitions as $competition ) {
			$url = add_query_arg( array(
				'tuja_view'        => 'Forms',
				'tuja_competition' => $competition->id
				) );
				printf( '<p><a href="%s">%s</a></p>', $url, $competition->name );
			}
		?>
		<?php
		$url = add_query_arg( array(
			'tuja_view' => 'CompetitionBootstrap'
		) );
		printf( '<p><a href="%s">Skapa ny tävling</a></p>', $url );
		?>
    </form>
</div>