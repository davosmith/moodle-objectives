Lesson objectives block
=======================

This is a block for Moodle 1.9 and Moodle 2.x.
This is the Moodle 2.x version ( download the Moodle 1.9 version here - https://github.com/davosmith/moodle-objectives/zipball/MOODLE_19_STABLE )

It displays current lesson objectives in the side bar (to both teacher and students) and allows a teacher to check them off as they are completed. You can enter a timetable, linked to different groups, so that objectives can be entered as far in advance as you want and will be displayed at the appropriate time.

Other features include:
* Abiliy to display a large version of the objectives, when teaching from the front of a class room (click on the 'expand' icon)
* Students can view all the objectives, a week at a time, to help keep track of what they have been doing (and will be doing)

==Recent changes==

2014-07-06 - Minor 2.7 compatibility fixes
2014-04-25 - Fix timezone handling when displaying current objectives.
2013-11-28 - Minor 2.6 compatibility fixes
2012-12-07 - Moodle 2.4 compatibility fixes
2012-02-07 - Renamed all database tables to match new strict requirements on Moodle.org
2012-02-06 - Minor changes to improve 2.2 compatibility

==Installation==

1. If you haven't done so already, download the zip file from https://github.com/davosmith/moodle-objectives/zipball/master
2. Extract all the files to a suitable location
3. Create a folder on your server '<moodle root>/blocks/objectives'
4. Upload all the files in the 'davosmith-moodle-objectives-XXXXX/objectives' folder into this blocks/objectives folder on the server.
5. Log in as an administrator and click on the 'Notifications' link.
6. Go into a course, turn on editing and choose 'Lesson objectives' from the 'Add a block' menu

==Usage==

Click on 'Edit objectives ...' in the footer of the block.
The first time, this will take you directly to the 'Edit timetables' screen (there is a button at the top of the 'Edit objectives' page to get back here later).
For each slot in the timetable, choose a group to display the objectives to (or 'all groups', for everyone on the course), as well as a start / end time for that lesson.
Three blank slots are added to each day every time you click 'Save'.
Once the timetable slots are filled in, click 'Save and edit objectives', to start entering lesson objectives.

Type in the lesson objectives for each lesson slot, press enter to start a new objective; start a line with one (or two) spaces to indent the objectives.
Click on 'Save' before moving to another week (via the next / previous links at the top of the page).
Click 'Save and view course' to get back to the course.

During a lesson, a teacher can click on an objective to toggle the 'tick' box between completed / not completed (this is just to help keep track of the progress through the lesson and does not affect anything else).

==Contact==

Any questions or suggestions for improvement, please drop an email to "davo AT davodev DOT co DOT uk" or visit http://www.davodev.co.uk
