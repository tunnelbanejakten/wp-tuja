<?php
/*
    Plugin Name: Tuja
    Description: Made for Tunnelbanejakten.se
    Version: 0.0.1
    Author: Mikael Svensson
    Author URI: https://tunnelbanejakten.se
*/
// TODO: This list of includes is not maintainable. How should we do things instead?
include 'util/Id.php';
include 'util/Recaptcha.php';
include 'util/score/ScoreCalculator.php';
include 'util/messaging/MessageSender.php';
include 'util/Template.php';
include 'util/SmsBackupRestoreXmlFileProcessor.php';
include 'util/ImageManager.php';
include 'util/MessageImporter.php';
include 'util/Phone.php';
include 'view/FieldImages.php';
include 'view/FieldText.php';
include 'view/FieldChoices.php';
include 'view/FormShortcode.php';
include 'view/PointsShortcode.php';
include 'view/FormReadonlyShortcode.php';
include 'view/GroupNameShortcode.php';
include 'view/AbstractGroupShortcode.php';
include 'view/EditGroupShortcode.php';
include 'view/EditPersonShortcode.php';
include 'view/CreateGroupShortcode.php';
include 'view/CreatePersonShortcode.php';
include 'data/store/AbstractDao.php';
include 'data/store/CompetitionDao.php';
include 'data/store/FormDao.php';
include 'data/store/GroupDao.php';
include 'data/store/QuestionDao.php';
include 'data/store/ResponseDao.php';
include 'data/store/PersonDao.php';
include 'data/store/PointsDao.php';
include 'data/store/MessageDao.php';
include 'data/model/Form.php';
include 'data/model/Points.php';
include 'data/model/Group.php';
include 'data/model/Question.php';
include 'data/model/Competition.php';
include 'data/model/Response.php';
include 'data/model/Person.php';
include 'data/model/ValidationException.php';
include 'data/model/Message.php';
include 'db.init.php';

const SLUG = 'tuja';

use data\store\CompetitionDao;
use data\store\FormDao;
use data\store\GroupDao;
use data\store\MessageDao;
use data\store\PointsDao;
use data\store\QuestionDao;
use data\store\ResponseDao;
use tuja\data\model\Competition;
use tuja\util\Id;
use view\CreateGroupShortcode;
use view\CreatePersonShortcode;
use view\EditGroupShortcode;
use view\EditPersonShortcode;
use view\FormShortcode;
use view\FormReadonlyShortcode;
use view\GroupNameShortcode;
use view\PointsShortcode;

add_shortcode('tuja_form', 'tuja_form');

function tuja_form($atts)
{
    global $wp_query, $wpdb;
    $form_id = $atts['form'];
    $is_readonly = in_array(strtolower($atts['readonly']), ['yes', 'true']);
    $group_id = $wp_query->query_vars['group_id'];
    $component = $is_readonly ?
        new FormReadonlyShortcode($wpdb, $form_id, $group_id) :
        new FormShortcode($wpdb, $form_id, $group_id);
    return $component->render();
}

add_shortcode('tuja_points', 'tuja_points');

function tuja_points($atts)
{
    global $wp_query, $wpdb;
    $competition_id = $atts['competition'];
    $group_id = $wp_query->query_vars['group_id'];
    $component = new PointsShortcode($wpdb, $competition_id, $group_id);
    return $component->render();
}

add_shortcode('tuja_group_name', 'tuja_group_name_shortcode');

function tuja_group_name_shortcode($atts)
{
    global $wp_query, $wpdb;
    $group_id = $wp_query->query_vars['group_id'];
    $component = new GroupNameShortcode($wpdb, $group_id);
    return $component->render();
}

add_shortcode('tuja_edit_group', 'tuja_edit_group_shortcode');

function tuja_edit_group_shortcode($atts)
{
    global $wp_query, $wpdb;
    $group_id = $wp_query->query_vars['group_id'];
    $component = new EditGroupShortcode($wpdb, $group_id);
    return $component->render();
}

add_shortcode('tuja_create_group', 'tuja_create_group_shortcode');

function tuja_create_group_shortcode($atts)
{
    global $wpdb;
    $competition_id = $atts['competition'];
    $edit_link_template = $atts['edit_link_template'];
    $component = new CreateGroupShortcode($wpdb, $competition_id, $edit_link_template);
    return $component->render();
}

add_shortcode('tuja_create_person', 'tuja_create_person_shortcode');

function tuja_create_person_shortcode($atts)
{
    global $wp_query, $wpdb;
    $group_id = $wp_query->query_vars['group_id'];
    $edit_link_template = $atts['edit_link_template'];
    $component = new CreatePersonShortcode($wpdb, $group_id, $edit_link_template);
    return $component->render();
}

add_shortcode('tuja_edit_person', 'tuja_edit_person_shortcode');

function tuja_edit_person_shortcode($atts)
{
    global $wp_query, $wpdb;
    $person_key = $wp_query->query_vars['group_id'];
    $component = new EditPersonShortcode($wpdb, $person_key);
    return $component->render();
}

add_filter('query_vars', 'tuja_query_vars');

function tuja_query_vars($vars)
{
    $vars[] = 'group_id';
    return $vars;
}

add_filter('rewrite_rules_array', 'tujo_rewrite_rules');

function tujo_rewrite_rules($rules)
{
    $rules = array('([^/]+)/([' . Id::RANDOM_CHARS . ']{' . Id::LENGTH . '})$' => 'single.php?pagename=$matches[1]&group_id=$matches[2]') + $rules;
    return $rules;
}

add_action('plugins_loaded', 'tuja_db_migrate');

register_activation_hook(__FILE__, 'tuja_db_migrate');

add_action('admin_menu', 'tuja_add_menu');

function tuja_add_menu()
{
    add_menu_page('Tunnelbanejakten', 'Tunnelbanejakten', 'manage_options', SLUG, 'tuja_show_admin_page');
}

function tuja_admin_theme_style()
{
    wp_enqueue_style('tuja-admin-theme', plugins_url('admin.css', __FILE__));
}

add_action('admin_enqueue_scripts', 'tuja_admin_theme_style');
//add_action('login_enqueue_scripts', 'tuja_admin_theme_style');
function tuja_wp_theme_style()
{
    wp_enqueue_style('tuja-wp-theme', plugins_url('wp.css', __FILE__));
}

add_action('wp_enqueue_scripts', 'tuja_wp_theme_style');

function tuja_recaptcha_script()
{
    wp_register_script('tuja-recaptcha-script', 'https://www.google.com/recaptcha/api.js');
}

add_action('wp_enqueue_scripts', 'tuja_recaptcha_script');

function tuja_upload_script()
{
    wp_register_script('tuja-upload-script', plugins_url('upload.js', __FILE__));
}

add_action('wp_enqueue_scripts', 'tuja_upload_script');

function tuja_show_admin_page()
{
    global $wpdb;
    // TODO: Create DAOs on-demand, not all-at-once here.
    $db_competition = new CompetitionDao($wpdb);
    $db_form = new FormDao($wpdb);
    $db_groups = new GroupDao($wpdb);
    $db_question = new QuestionDao($wpdb);
    $db_response = new ResponseDao($wpdb);
    $db_points = new PointsDao($wpdb);
    $db_message = new MessageDao($wpdb);

    if ($_POST['tuja_action'] == 'competition_create') {
        $props = new Competition();
        $props->name = $_POST['tuja_competition_name'];
        $db_competition->create($props);
    }

    $view = $_GET['tuja_view'] ?: 'index';

    printf('<div class="tuja tuja-view-%s">', $view);
    include "admin/$view.php";
    print('</div>');
}