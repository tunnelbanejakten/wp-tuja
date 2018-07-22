<?php
/*
    Plugin Name: Tuja
    Description: Made for Tunnelbanejakten.se
    Version: 0.0.1
    Author: Mikael Svensson
    Author URI: https://tunnelbanejakten.se
*/
include 'util/Id.php';
include 'view/FieldText.php';
include 'view/FieldDropdown.php';
include 'view/FormShortcode.php';
include 'data/store/AbstractDao.php';
include 'data/store/CompetitionDao.php';
include 'data/store/FormDao.php';
include 'data/store/GroupDao.php';
include 'data/store/QuestionDao.php';
include 'data/store/ResponseDao.php';
include 'data/model/Form.php';
include 'data/model/Group.php';
include 'data/model/Question.php';
include 'data/model/Competition.php';
include 'data/model/Response.php';
include 'db.init.php';

const SLUG = 'tuja';

use data\store\CompetitionDao;
use data\store\FormDao;
use data\store\GroupDao;
use data\store\QuestionDao;
use tuja\data\model\Competition;
use tuja\util\Id;
use view\FormShortcode;

add_shortcode('tuja_textfield', 'tuja_textfield');

function tuja_textfield()
{
    return (new tuja\view\FieldText())->render();
}

add_shortcode('tuja_randomstring', 'tuja_randomstring');

function tuja_randomstring($atts)
{
    global $wp_query;
    return (new Id())->random_string(isset($atts["length"]) ? $atts["length"] : 10);
}

add_shortcode('tuja_form', 'tuja_form');

function tuja_form($atts)
{
    global $wp_query, $wpdb;
    $form_id = $atts['form'];
    $team_id = $wp_query->query_vars['team_id'];
    $component = new FormShortcode($wpdb, $form_id, $team_id);
    return $component->render();
}

add_filter('query_vars', 'tuja_query_vars');

function tuja_query_vars($vars)
{
    $vars[] = 'team_id';
    return $vars;
}

add_filter('rewrite_rules_array', 'tujo_rewrite_rules');

function tujo_rewrite_rules($rules)
{
    $rules = array('([^/]+)/team-([^/]+)/?$' => 'single.php?pagename=$matches[1]&team_id=$matches[2]') + $rules;
    return $rules;
}

add_action('plugins_loaded', 'tuja_db_migrate');

register_activation_hook(__FILE__, 'tuja_db_migrate');

add_action('admin_menu', 'tuja_add_menu');

function tuja_add_menu()
{
    add_menu_page('Tunnelbanejakten', 'Tunnelbanejakten', 'manage_options', SLUG, 'tuja_show_admin_page');
}

function tuja_admin_theme_style() {
    wp_enqueue_style('tuja-admin-theme', plugins_url('admin.css', __FILE__));
}
add_action('admin_enqueue_scripts', 'tuja_admin_theme_style');
//add_action('login_enqueue_scripts', 'tuja_admin_theme_style');

function tuja_show_admin_page()
{
    global $wpdb;
    $db_competition = new CompetitionDao($wpdb);
    $db_form = new FormDao($wpdb);
    $db_groups = new GroupDao($wpdb);
    $db_question = new QuestionDao($wpdb);

    if ($_POST['tuja_action'] == 'competition_create') {
        $props = new Competition();
        $props->name = $_POST['tuja_competition_name'];
        $db_competition->create($props);
    }

    $view = $_GET['tuja_view'] ?: 'index';

    include "admin/$view.php";
}