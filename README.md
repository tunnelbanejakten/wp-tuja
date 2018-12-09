# Getting Started

Get the Docker stuff up-and-running:

	$ cp .env-example .env
    $ docker-compose up -d

Go to http://localhost:8080/ and configure your demo site.

Go to http://localhost:8080/wp-admin/plugins.php and activate the Tuja plugin/

Configuring e-mail: You must install some kind of e-mail plugin in order for Wordpress 
to be able to send e-mail. One such plugin is https://wordpress.org/plugins/wp-mail-smtp/.

# Debugging

Open shell to Wordpress installation:

    $ docker-compose exec wordpress bash

# Short Codes

## tuja_form

Features:
* Displays GUI for answering questions in a form.
* Uses id string in URL to identify the team answering the questions. 
* Lets the user choose one of the "participant groups" instead if the id string actually identifies a "crew group".

Attributes:
* form: The integer id (i.e. primary key in the database) for the form to display.
* readonly: Boolean flag for whether or not user should be able to provide answers or if only current answer for each question is displayed. 

Known issues:
* 

## tuja_group_name

Features:
* Displays the group name references by the "group_id" URL parameter (handled in a WP query variable).

Attributes:
No attributes.

Known issues:
* 

## tuja_edit_group

Features:
* Edit group properties (like name and age group).
* Edit group members (adding, changing and removing people in group).
* Uses id string in URL to identify the group. 

Attributes:
* is_crew_form: Sets which group categories to show in the form. The value "yes" means that only crew categories 
  are shown, otherwise only non-crew categories are shown. 

Known issues:
* 

## tuja_create_group

Features:
* Handles registration of new groups (i.e. signing up new teams).
* Create one group and its initial group member (who is presumably the group leader).

Attributes:
* competition: The competition to which group should belong.
* edit_link_template: Template for URL used to edit group once created. Needs to be an absolute URL.
* is_crew_form: Sets which group categories to show in the form. The value "yes" means that only crew categories 
  are shown, otherwise only non-crew categories are shown. 

Known issues:
* 

## tuja_create_person

Features:
* Creates a new group member, i.e. person. Basically a sign-me-up-to-specific-team form.
* Uses id string in URL to identify the group to which the person should be added. 

Attributes:
* edit_link_template: Template for URL used to edit person once created. Needs to be an absolute URL.

Known issues:
* 

## tuja_edit_person

Features:
* Form for changing name, phone number and email address of a specific person.
* Uses id string in URL to identify the person. 

Attributes:
No attributes.

Known issues:
* 

## tuja_points

Features:
* Form for setting explicit score for teams. Will override any points set by the automatic score calculator.
* Optimistic locking feature prevents concurrent users from overwriting each others changes (Alice cannot submit 
  points if Bob user submits points for the same questions after Alice loads the page with the form). 
* Uses id string in URL to identify the user's team.
* Only crew teams are permitted to use the form. 

Attributes:
* competition: The competition for which users can report points with the form.

Known issues:
* See to-do items in source code... 

    
# To Do

0. Use Case _Crew Reports Points_:
    * ?
    * The address should be something like https://www.tunnelbanejakten.se/form/THE_FORM_ID/THE_CREW_TEAM_ID
0. Use Case _Crew Edits Auto-review Rules_:
    * ?
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
