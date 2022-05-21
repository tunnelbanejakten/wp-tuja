<?php
namespace tuja\admin;

use tuja\data\model\MessageTemplate;
use tuja\util\DateUtils;
use tuja\util\Strings;
use tuja\util\TemplateEditor;

AdminUtils::printTopMenu( $competition );

$this->print_menu();
?>

<?php
	$basic_url = add_query_arg( array(
		'tuja_competition' => $competition->id,
		'tuja_view'        => 'CompetitionSettingsBasic'
	) );
	printf( '<p><a href="%s">Namn och tid</a></p>', $basic_url );
	$group_categories_url = add_query_arg( array(
		'tuja_competition' => $competition->id,
		'tuja_view'        => 'CompetitionSettingsGroupCategories'
	) );
	printf( '<p><a href="%s">Gruppkategorier</a></p>', $group_categories_url );
	$strings_url = add_query_arg( array(
		'tuja_competition' => $competition->id,
		'tuja_view'        => 'CompetitionSettingsStrings'
	) );
	printf( '<p><a href="%s">Texter</a></p>', $strings_url );
	$app_url = add_query_arg( array(
		'tuja_competition' => $competition->id,
		'tuja_view'        => 'CompetitionSettingsApp'
	) );
	printf( '<p><a href="%s">Appen</a></p>', $app_url );
	$payment_url = add_query_arg( array(
		'tuja_competition' => $competition->id,
		'tuja_view'        => 'CompetitionSettingsFees'
	) );
	printf( '<p><a href="%s">Avgifter</a></p>', $payment_url );
	$lifecycle_url = add_query_arg( array(
		'tuja_competition' => $competition->id,
		'tuja_view'        => 'CompetitionSettingsGroupLifecycle'
	) );
	printf( '<p><a href="%s">Livscykel för grupper</a></p>', $lifecycle_url );
	$message_templates_url = add_query_arg( array(
		'tuja_competition' => $competition->id,
		'tuja_view'        => 'CompetitionSettingsMessageTemplates'
	) );
	printf( '<p><a href="%s">Meddelandemallar</a></p>', $message_templates_url );
?>

