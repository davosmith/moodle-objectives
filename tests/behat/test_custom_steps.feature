@block @block_objectives
Feature: The custom steps work as expected

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

  Scenario: I override the time to 3pm on Tuesday 2nd June 2015 and that time is displayed
    When I force the objectives block current time to "15:00 2 June 2015"
    Then I should see "Tuesday, 2 June 2015"
    And I should see "No lesson/objectives at the moment (3:00 PM)"

    When I click on "View objectives" "link"
    Then I should see "Week beginning Monday, 1 June 2015"

  Scenario: Directly creating timetable elements using the custom step works as expected
    When the following objectives timetable exists in course "C1":
      | day     | starttime | endtime  | group  |
      | Monday  | 10:25 AM  | 11:45 AM |        |
      | Monday  | 2:10 PM   | 4:45 PM  |        |
      | Tuesday | 3 PM      | 5 PM     | Group1 |
      | Friday  | 8 AM      | 2 PM     | Group1 |
      | Sunday  | 4 PM      | 17:08    |        |
    And I follow "Edit objectives"
    And I expand all fieldsets
    Then I should see "Monday"
    And "10:25 AM-11:45 AM" "text" should exist in the "fieldset#id_monday" "css_element"
    And "2:10 PM-4:45 PM" "text" should exist in the "fieldset#id_monday" "css_element"
    And I should see "Tuesday"
    And "3:00 PM-5:00 PM (Group1)" "text" should exist in the "fieldset#id_tuesday" "css_element"
    And I should not see "Wednesday"
    And I should not see "Thursday"
    And I should see "Friday"
    And "8:00 AM-2:00 PM (Group1)" "text" should exist in the "fieldset#id_friday" "css_element"
    And I should not see "Saturday"
    And I should see "Sunday"
    And "4:00 PM-5:08 PM" "text" should exist in the "fieldset#id_sunday" "css_element"

  Scenario: Direclty created objectives (using the custom step) appear on the course page
    Given the following objectives timetable exists in course "C1":
      | day     | starttime | endtime  | group  |
      | Monday  | 10:25 AM  | 11:45 AM |        |
      | Monday  | 2:10 PM   | 4:45 PM  |        |
      | Tuesday | 3 PM      | 5 PM     | Group1 |
      | Friday  | 8 AM      | 2 PM     | Group1 |
      | Sunday  | 4 PM      | 17:08    |        |
    When the following objectives exist in course "C1":
      | weekstart | day     | starttime | objectives                             |
      | 20150601  | Monday  | 2:10 PM   | Objective 1,Objective 2,Objective 3    |
      | 20150601  | Tuesday | 3 PM      | Test objective,Another test,Third test |
      | 20150608  | Friday  | 8 AM      | More objectives,Extra stuff to do      |
    And I force the objectives block current time to "3:15 PM 2 June 2015"
    Then I should see "Test objective"
    And I should see "Another test"
    And I should see "Third test"
    And I should not see "Objective 1"
    And I should not see "More objectives"

  Scenario: Directly created objectives (using the custom step) appear on the 'View objectives' page
    Given the following objectives timetable exists in course "C1":
      | day     | starttime | endtime  | group  |
      | Monday  | 10:25 AM  | 11:45 AM |        |
      | Monday  | 2:10 PM   | 4:45 PM  |        |
      | Tuesday | 3 PM      | 5 PM     | Group1 |
      | Friday  | 8 AM      | 2 PM     | Group1 |
      | Sunday  | 4 PM      | 17:08    |        |
    When the following objectives exist in course "C1":
      | weekstart | day     | starttime | objectives                             |
      | 20150601  | Monday  | 2:10 PM   | Objective 1,Objective 2,Objective 3    |
      | 20150601  | Tuesday | 3 PM      | Test objective,Another test,Third test |
      | 20150608  | Friday  | 8 AM      | More objectives,Extra stuff to do      |
    And I force the objectives block current time to "12:00 3 June 2015"
    And I follow "View objectives"
    Then I should see "Week beginning Monday, 1 June 2015"
    And "Objective 1" "list_item" should exist in the "2:10 PM-4:45 PM" "table_row"
    And "Objective 2" "list_item" should exist in the "2:10 PM-4:45 PM" "table_row"
    And "Objective 3" "list_item" should exist in the "2:10 PM-4:45 PM" "table_row"
    And "Test objective" "list_item" should exist in the "3:00 PM-5:00 PM" "table_row"
    And "Another test" "list_item" should exist in the "3:00 PM-5:00 PM" "table_row"
    And "Third test" "list_item" should exist in the "3:00 PM-5:00 PM" "table_row"
    And I should not see "More objectives"
    And I should not see "Extra stuff to do"

    When I follow "Next week"
    Then I should not see "Objective 1"
    And I should not see "Test objective"
    And "More objectives" "list_item" should exist in the "8:00 AM-2:00 PM" "table_row"
    And "Extra stuff to do" "list_item" should exist in the "8:00 AM-2:00 PM" "table_row"
