# Getting Started

Get the Docker stuff up-and-running:

    $ docker-compose up

Go to http://localhost:8080/ and configure your demo site.

Go to http://localhost:8080/wp-admin/plugins.php and activate the Tuja plugin/


# Debugging

Open shell to Wordpress installation:

    $ docker-compose exec wordpress bash
    
# To Do

0. Use Case _Team Answers Form_:
    * Data model for _team_, _form_, _form_question_, _form_question_response_.
    * GUI for displaying form using WP shortcode.
    * Form pre-filled with team's previous responses.
    * Form values should not be cleared if values cannot be saves. 
    * The address should be something like https://www.tunnelbanejakten.se/form/THE_FORM_ID/THE_TEAM_ID
        * The custom address could potentially be implemented using two WP features: "The Rewrite API" and "query_vars". 
        * https://premium.wpmudev.org/blog/building-customized-urls-wordpress/
        * https://www.rlmseo.com/blog/passing-get-query-string-parameters-in-wordpress-url/
        * Customer Post Type might also be an option... https://wpshout.com/use-custom-post-types-wordpress/
0. Use Case _Crew Reports Points_:
    * ?
    * The address should be something like https://www.tunnelbanejakten.se/form/THE_FORM_ID/THE_CREW_TEAM_ID
0. Use Case _Crew Creates Form Which Teams Will Answer_:
    * Data model for _competition_.
    * GUI for creating and deleting competition.
    * GUI for creating and deleting form in a competition.
    * GUI for editing a form.
        * Maybe initially this can be done by editing a YAML document?
        * Editor side-by-side with a preview window?
        * Updated questions could be identified by their random string identifier? New questions would not have one and one would be assigned?
0. Use Case _Initial Team Signup_:
    * GUI for creating a new team.
    * Form should be dynamic enough to specify the names and roles of up to 8 team members.
    * Should not require any authentication. 
    * The address should be something like https://www.tunnelbanejakten.se/team/PARTICIPANT_TEAM_FORM_ID
0. Use Case _Crew Signup_:
    * GUI for adding person to pre-defined team (the "crew team").
    * Should not require any authentication.
    * The address should be something like https://www.tunnelbanejakten.se/team/CREW_TEAM_FORM_ID/THE_CREW_TEAM_ID
0. Use Case _Crew Edits Auto-review Rules_:
    * ?
0. Use Case _Edit Team Signup_:
    * GUI for adding, changing and removing person to pre-defined team.
    * The address should be something like https://www.tunnelbanejakten.se/team/PARTICIPANT_TEAM_FORM_ID/THE_TEAM_ID
0. Use Case _Team Sends Answer by SMS_:
    * ?
0. Use Case _Crew Checks Scoreboard_:
    * ?
0. Use Case _Crew Contacts All Teams_:
    * ?
0. Use Case _Team Sends Photo by MMS_:
    * ?
0. Use Case _Team Sends Photo by Email_:
    * ?
0. Use Case _Team Check Which Photo Assignments Have Been Approved_:
    * The team sees a list of all the photo assignments and sees for which they have provided accepted pictures.
    * The team does not necessarily see awarded points or the actual image or if a picture has been received but not yet approved/accepted.
    * The team might see submitted photos in a separate list, but the best would be to see them in the same list.
0. Use Case _Team Check-In_:
    * ?
0. Use Case _Crew Reviews Photo_:
    * A crew member looks at a photo and, if it is good enough, marks it as Approved in a form.
    * Ideally, the photo is automatically associated with a particular photo assignment (i.e. question) but that might
      not be possible (for various reasons). This would, for example, not be possible if the photo is submitted in
      an Instagram message.
    * Photos are approved using a regular form, but one restricted so that only crew members can submit to it.
      * Would "security by obscurity" be enough in that the page with the form could have a random URL?
    * A page could be created where the first part is the form, without any pictures, and the second part is a list of
      all submitted photos. The reviewer would have to scroll up and down quite a lot, but at least it would work.
0. Use Case _Crew Reviews Answer_:
    * ?
0. Use Case _Crew Imports Contacts to Phone_:
    * ?
0. Use Case _Crew Corrects Points_:
    * ?
0. Use Case _Crew Types Answers for Team_:
    * ?
0. Use Case _Crew Contacts a Team_:
    * ?
0. Use Case _Crew Sends Out Link to Review Form for Competition_:
    * ?
0. Use Case _Crew Downloads All Data_:
    * ?
0. Use Case _Crew Removes Personal Data_:
    * ?
0. Use Case _Crew Opens or Closes Competition_:
    * ?
