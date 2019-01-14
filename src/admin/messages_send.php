<?php

use data\store\GroupCategoryDao;
use data\store\GroupDao;
use data\store\MessageTemplateDao;
use data\store\PersonDao;
use tuja\data\model\Group;
use tuja\data\model\Person;
use util\messaging\MessageSender;
use util\messaging\OutgoingEmailMessage;
use util\messaging\OutgoingSMSMessage;
use util\Template;

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

$group_category_dao = new GroupCategoryDao($wpdb);
$group_categories = $group_category_dao->get_all_in_competition($competition->id);
$crew_category_ids = array_map(function ($category) {
    return $category->id;
}, array_filter($group_categories, function ($category) {
    return $category->is_crew;
}));

$group_dao = new GroupDao($wpdb);
$group_selectors = array_merge(
    array(
        array(
            'label' => 'Alla grupper, inkl. funk',
            'selector' => function ($group) {
                return true;
            }
        ),
        array(
            'label' => 'Alla tävlande grupper',
            'selector' => function ($group) use ($crew_category_ids) {
                return !in_array($group->category_id, $crew_category_ids);
            }
        ),
        array(
            'label' => 'Alla funktionärsgrupper',
            'selector' => function ($group) use ($crew_category_ids) {
                return in_array($group->category_id, $crew_category_ids);
            }
        ),
//        array(
//            'label' => 'Alla grupper som inte slutfört anmälan',
//            'selector' => function ($group) {
//                return true;
//            }
//        ),
//        array(
//            'label' => 'Alla grupper som inte bekräftat telefonnummer',
//            'selector' => function ($group) {
//                return true;
//            }
//        )
    ),
    array_map(
        function ($category) {
            return array(
                'label' => 'Alla grupper i kategorin ' . $category->name,
                'selector' => function ($group) use ($category) {
                    return $group->category_id === $category->id;
                }
            );
        },
        $group_categories),
    array_map(
        function ($selected_group) {
            return array(
                'label' => 'Gruppen ' . $selected_group->name,
                'selector' => function ($group) use ($selected_group) {
                    return $group->id === $selected_group->id;
                }
            );
        },
        ($group_dao)->get_all_in_competition($competition->id)));

$people_selectors = array(
    'all' => array(
        'label' => 'Alla personer i valda grupper',
        'selector' => function ($person) {
            return true;
        }
    ),
    'primary_contacts' => array(
        'label' => 'Enbart valda gruppers primära kontaktpersoner',
        'selector' => function ($person) {
            return $person->is_primary_contact;
        }
    )
);

$delivery_methods = array(
    'sms' => array(
        'label' => 'SMS',
        'message_generator' => function (Person $person, $subject_template, $body_template, $template_parameters) {
            return new OutgoingSMSMessage(
                new MessageSender(),
                $person,
                $body_template->render($template_parameters));
        },
        'is_plain_text_body' => true
    ),
    'email' => array(
        'label' => 'E-post',
        'message_generator' => function (Person $person, $subject_template, $body_template, $template_parameters) {
            return new OutgoingEmailMessage(
                new MessageSender(),
                $person,
                $body_template->render($template_parameters, true),
                $subject_template->render($template_parameters));
        },
        'is_plain_text_body' => false
    )
);

function tuja_get_parameters($person, $group)
{
    return array_merge(
        Template::group_parameters($group),
        Template::person_parameters($person),
        Template::site_parameters()
    );
}

$message_template_dao = new MessageTemplateDao($wpdb);

$templates = $message_template_dao->get_all_in_competition($competition->id);

$settings_url = add_query_arg(array(
    'tuja_competition' => $competition->id,
    'tuja_view' => 'CompetitionSettings'
));

$is_preview = $_POST['tuja_messages_action'] === 'preview';
$is_send = $_POST['tuja_messages_action'] === 'send';
?>

    <h1>Tunnelbanejakten</h1>
    <h2>Tävling <?= sprintf('<a href="%s">%s</a>', $competition_url, $competition->name) ?></h2>
    <h3>Skicka e-post och SMS</h3>

<?php
if ($_POST['tuja_points_action'] === 'send') {
} else {
    ?>
    <form method="post" action="<?= add_query_arg() ?>">
        <p><strong>Mottagare och distribution</strong></p>
        <div style="float: left;">
            <label for="">Välj grupp(er) att skicka till:</label><br>
            <select name="tuja_messages_group_selector">
                <?= join(array_map(function ($index, $group_selector) {
                    return sprintf('<option value="%d" %s>%s</option>',
                        $index,
                        $_POST['tuja_messages_group_selector'] == $index ? ' selected="selected"' : '',
                        $group_selector['label']);
                }, array_keys($group_selectors), array_values($group_selectors))) ?>
            </select>
        </div>
        <div style="float: left;">
            Välj mottagare i valda grupper:<br>
            <?= join(array_map(function ($key, $person_selector) {
                $id = uniqid();
                return sprintf('<div><input type="radio" name="tuja_messages_people_selector" id="%s" value="%s" %s/><label for="%s">%s</label></div>',
                    $id,
                    $key,
                    $_POST['tuja_messages_people_selector'] == $key ? ' checked="checked"' : '',
                    $id,
                    $person_selector['label']);
            }, array_keys($people_selectors), array_values($people_selectors))); ?>
        </div>
        <div style="float: left;">
            Välj format:<br>
            <?= join(array_map(function ($key, $delivery_method) {
                $id = uniqid();
                return sprintf('<div><input type="radio" name="tuja_messages_delivery_method" id="%s" value="%s" %s/><label for="%s">%s</label></div>',
                    $id,
                    $key,
                    $_POST['tuja_messages_delivery_method'] == $key ? ' checked="checked"' : '',
                    $id,
                    $delivery_method['label']);
            }, array_keys($delivery_methods), array_values($delivery_methods))); ?>
        </div>
        <div style="clear: both"></div>

        <p><strong>Meddelande</strong></p>

        <div style="float: left;">
            <div>
                <label for="tuja-message-subject">Ämne:</label><br>
                <input type="text"
                       name="tuja_messages_subject"
                       id="tuja-message-subject"
                       size="50"
                       value="<?= $_POST['tuja_messages_subject'] ?>">
            </div>

            <div>
                <label for="tuja-message-body">Meddelande:</label><br>
                <textarea name="tuja_messages_body"
                          id="tuja-message-body"
                          cols="80"
                          rows="10"><?= $_POST['tuja_messages_body'] ?></textarea>
            </div>
        </div>

        <div style="float: left; max-width: 30em; margin-left: 1em;">
            Meddelandemallar (ändra mallarna på <a href="<?= $settings_url ?>">inställningssidan</a>):<br>
            <?= join('<br>', array_map(function ($template) {
                return sprintf('<a class="tuja-messages-template-link" href="#" data-value="%s;%s">%s</a>',
                    rawurlencode($template->subject),
                    rawurlencode($template->body),
                    $template->name
                );
            }, $templates)) ?>
            <br>I texten kan du använda följande variabler: <br><?= join('<br>', array_map(function ($var) {
                return sprintf('<tt>{{%s}}</tt>', $var);
            }, array_keys(tuja_get_parameters(new Person(), new Group())))) ?>
            <br>Utöver variabler kan du även använda <a href="https://daringfireball.net/projects/markdown/basics">Markdown</a>
            för att göra fet text, lägga in länkar mm.
        </div>

        <div style="clear: both"></div>

        <?php if ($is_preview) { ?>
            <div>
                <button class="button" type="submit" name="tuja_messages_action" value="preview">
                    Förhandsgranska utskick
                </button>
                <button class="button button-primary" type="submit" name="tuja_messages_action" value="send">
                    Skicka
                </button>
            </div>
        <?php } elseif ($is_send) { ?>
            <div>
                <button class="button button-primary" type="submit" name="tuja_messages_action" value="preview">
                    Förhandsgranska utskick
                </button>
                <button class="button" type="button" disabled="disabled">
                    Skicka
                </button>
            </div>
        <?php } else { ?>
            <div>
                <button class="button button-primary" type="submit" name="tuja_messages_action" value="preview">
                    Förhandsgranska utskick
                </button>
                <button class="button" type="button" disabled="disabled">
                    Skicka
                </button>
            </div>
        <?php } ?>
    </form>
    <?php
    if ($is_preview || $is_send) {
        $group_selector = $group_selectors[intval($_POST['tuja_messages_group_selector'])];
        $people_selector = $people_selectors[$_POST['tuja_messages_people_selector']];
        $delivery_method = $delivery_methods[$_POST['tuja_messages_delivery_method']];
        if (isset($group_selector) && isset($people_selector) && isset($delivery_method)) {
            $groups = $group_dao->get_all_in_competition($competition->id);
            $selected_groups = array_filter($groups, $group_selector['selector']);

            $person_dao = new PersonDao($wpdb);
            $people = [];
            foreach ($selected_groups as $selected_group) {
                $group_members = array_filter($person_dao->get_all_in_group($selected_group->id), $people_selector['selector']);
                $people = array_merge($people, $group_members);
            }

            $body_template = Template::string($_POST['tuja_messages_body']);
            $subject_template = Template::string($_POST['tuja_messages_subject']);

            $variables = array_merge($body_template->get_variables(), $subject_template->get_variables());
            printf('<table>');
            printf('<thead><tr><td colspan="2"><strong>Mottagare</strong></td>%s<td><strong>Förhandsgranskning</strong></td></tr></thead>', join(array_map(function ($variable) {
                return sprintf('<td><strong>%s</strong></td>', $variable);
            }, $variables)));
            printf('<tbody>%s</tbody>', join(array_map(function ($person) use ($delivery_method, $variables, $groups, $subject_template, $body_template, $is_send) {
                $group = reset(array_filter($groups, function ($grp) use ($person) {
                    return $grp->id == $person->group_id;
                }));
                $template_parameters = tuja_get_parameters($person, $group);
                $message_generator = $delivery_method['message_generator'];
                $outgoing_message = $message_generator($person, $subject_template, $body_template, $template_parameters);
                $is_valid = 'OK';
                try {
                    if ($is_send) {
                        $outgoing_message->send();
                        $is_valid = 'Meddelande har skickats';
                    } else {
                        $outgoing_message->validate();
                    }
                } catch (Exception $e) {
                    $is_valid = $e->getMessage();
                }
                return sprintf('<tr><td valign="top">%s</td><td valign="top">%s</td>%s<td valign="top">%s</td></tr>',
                    $person->name,
                    $is_valid,
                    join(array_map(function ($variable) use ($template_parameters) {
                        return sprintf('<td valign="top">%s</td>', $template_parameters[$variable]);
                    }, $variables)),
                    sprintf('<div class="tuja-message-preview">%s</div><div class="tuja-message-preview %s">%s</div>',
                        strip_tags($subject_template->render($template_parameters)),
                        $delivery_method['is_plain_text_body'] ? 'tuja-message-preview-plaintext' : 'tuja-message-preview-html',
                        $body_template->render($template_parameters, !$delivery_method['is_plain_text_body'])));
            }, $people)));
            printf('</table>');
        }
    }
}
?>