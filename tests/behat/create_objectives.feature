@block @block_objectives @javascript
Feature: Lesson objectives editing works as expected

  Background:
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And the following "groups" exist:
      | course | idnumber | name   |
      | C1     | Group1   | Group1 |
    And I log in as "teacher1"
    And I follow "Course 1"
    And I turn editing mode on
    And I add the "Lesson objectives" block
    And I force the objectives block current time to "15:00 2 june 2015"
    And I log out

  Scenario: A teacher can create a timetable
    Given I log in as "teacher1"
    And I follow "Course 1"
    And I follow "Edit objectives"
    And I should see "Edit timetables"

  # Add a lesson to Monday 8am - 10:30am for 'All groups'
    When I expand all fieldsets
    And I set the field with xpath "//*[@id='id_monday']//select[starts-with(@name, 'lgroup')]" to "All groups"
    And I set the field with xpath "//*[@id='id_monday']//select[starts-with(@name, 'lstarthour')]" to "08"
    And I set the field with xpath "//*[@id='id_monday']//select[starts-with(@name, 'lendhour')]" to "10"
    And I set the field with xpath "//*[@id='id_monday']//select[starts-with(@name, 'lendminute')]" to "30"

  # Add a lesson to Tuesday 3:15pm - 5:25pm for 'Group 1'
    And I set the field with xpath "//*[@id='id_tuesday']//select[starts-with(@name, 'lgroup')]" to "Group1"
    And I set the field with xpath "//*[@id='id_tuesday']//select[starts-with(@name, 'lstarthour')]" to "15"
    And I set the field with xpath "//*[@id='id_tuesday']//select[starts-with(@name, 'lstartminute')]" to "15"
    And I set the field with xpath "//*[@id='id_tuesday']//select[starts-with(@name, 'lendhour')]" to "17"
    And I set the field with xpath "//*[@id='id_tuesday']//select[starts-with(@name, 'lendminute')]" to "25"

    And I press "Save and edit objectives"

    Then I should see "Edit objectives"
    And I expand all fieldsets
    And I should see "Monday"
    And I should see "Tuesday"
    And I should not see "Wednesday"
    And "8:00 AM-10:30 AM" "text" should exist in the "fieldset#id_monday" "css_element"
    And "3:15 PM-5:25 PM (Group1)" "text" should exist in the "fieldset#id_tuesday" "css_element"

  Scenario: A teacher can create lesson objectives
    Given the following objectives timetable exists in course "C1":
      | day     | starttime | endtime  |
      | Monday  | 10:25 AM  | 11:45 AM |
      | Monday  | 2:10 PM   | 4:45 PM  |
      | Tuesday | 3 PM      | 5 PM     |
    When I log in as "teacher1"
    And I follow "Course 1"
    And I follow "Edit objectives"
    And I should see "Week beginning Monday, 1 June 2015"
    And I set the field "10:25 AM-11:45 AM" to multiline
    """
    Objective 1
    Objective 2
     Objective 2a
    """
    And I set the field "3:00 PM-5:00 PM" to multiline
    """
    Another objective
    One more thing to do
     A sub-objective
     Another sub-objective
    """
    And I press "Save and return to course"
    And I follow "View objectives"
    Then I should see "Week beginning Monday, 1 June 2015"
    And "10:25 AM-11:45 AM" "text" should exist in the "Monday" "table_row"
    And "3:00 PM-5:00 PM" "text" should exist in the "Tuesday" "table_row"
    And "Objective 1" "list_item" should exist in the "10:25 AM-11:45 AM" "table_row"
    And "Objective 2" "list_item" should exist in the "10:25 AM-11:45 AM" "table_row"
    And "Objective 2a" "list_item" should exist in the "10:25 AM-11:45 AM" "table_row"
    And "Another objective" "list_item" should exist in the "3:00 PM-5:00 PM" "table_row"
    And "One more thing to do" "list_item" should exist in the "3:00 PM-5:00 PM" "table_row"
    And "A sub-objective" "list_item" should exist in the "3:00 PM-5:00 PM" "table_row"
    And "Another sub-objective" "list_item" should exist in the "3:00 PM-5:00 PM" "table_row"
