@block @block_objectives
Feature: Objectives can be viewed

  Background:
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
      | student2 | Student   | 2        | student2@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
    And the following "groups" exist:
      | course | idnumber | name   |
      | C1     | Group1   | Group1 |
      | C1     | Group2   | Group2 |
    And the following "group members" exist:
      | user     | group  |
      | student1 | Group1 |
      | student2 | Group2 |
    And I log in as "teacher1"
    And I follow "Course 1"
    And I turn editing mode on
    And I add the "Lesson objectives" block
    And I log out
    And the following objectives timetable exists in course "C1":
      | day     | starttime | endtime  | group  |
      | Monday  | 10:25 AM  | 11:45 AM |        |
      | Monday  | 2:10 PM   | 4:45 PM  |        |
      | Tuesday | 3 PM      | 5 PM     | Group1 |
      | Tuesday | 3:15 PM   | 5 PM     | Group2 |
    And the following objectives exist in course "C1":
      | weekstart | day     | starttime | objectives                                             |
      | 20150601  | Monday  | 10:25 AM  | Objective 1,Objective 2,Objective 3                    |
      | 20150601  | Monday  | 2:10 PM   | Monday afternoon objective,Another afternoon objective |
      | 20150601  | Tuesday | 3 PM      | Test objective,Another test,Third test                 |
      | 20150601  | Tuesday | 3:15 PM   | Objective for group 2,More for group 2                 |
      | 20150608  | Monday  | 10:25 AM  | Next Monday objective,One more objective               |

  Scenario: A student can see the current objectives, as the time changes
    Given I log in as "student1"
    And I follow "Course 1"
    When I force the objectives block current time to "10:30 AM 1 June 2015"
    Then I should see "Objective 1"
    And I should not see "Monday afternoon objective"
    And I should not see "Next Monday objective"

    When I force the objectives block current time to "2:30 PM 1 June 2015"
    Then I should not see "Objective 1"
    And I should see "Monday afternoon objective"
    And I should not see "Next Monday objective"

    When I force the objectives block current time to "10:30 AM 8 June 2015"
    Then I should not see "Objective 1"
    And I should not see "Monday afternoon objective"
    And I should see "Next Monday objective"

  Scenario: Student 1 should see the objectives for Group 1
    Given I log in as "student1"
    And I follow "Course 1"
    When I force the objectives block current time to "3:30 PM 2 June 2015"
    Then I should see "Test objective"
    And I should not see "Objective for group 2"

  Scenario: Student 2 should see the objectives for Group 2
    Given I log in as "student2"
    And I follow "Course 1"
    When I force the objectives block current time to "3:30 PM 2 June 2015"
    Then I should not see "Test objective"
    And I should see "Objective for group 2"

  @javascript
  Scenario: Teachers can switch between group objectives
    Given I log in as "teacher1"
    And I follow "Course 1"
    And I force the objectives block current time to "3:30 PM 2 June 2015"
    When I set the field "objectives_group" to "Group1"
    Then I should see "Test objective"
    And I should not see "Objective for group 2"

    When I set the field "objectives_group" to "Group2"
    Then I should not see "Test objective"
    And I should see "Objective for group 2"

  @javascript
  Scenario: A teacher can expand the objectives to full-screen
    Given I log in as "teacher1"
    And I follow "Course 1"
    And I force the objectives block current time to "10:30 AM 1 June 2015"
    When I click on "Show objectives fullscreen" "link"
    Then "Lesson objectives" "text" in the "#lesson_objectives_fullscreen" "css_element" should be visible
    And "Objective 1" "text" in the "#lesson_objectives_fullscreen" "css_element" should be visible

  Scenario: A teacher can tick-off objectives as they are completed
    Given I log in as "teacher1"
    And I follow "Course 1"
    And I force the objectives block current time to "10:30 AM 1 June 2015"
    And "img.incomplete" "css_element" should exist in the "Objective 1" "list_item"
    And "img.incomplete" "css_element" should exist in the "Objective 2" "list_item"
    And "img.incomplete" "css_element" should exist in the "Objective 3" "list_item"
    When I follow "Objective 1"
    Then "img.complete" "css_element" should exist in the "Objective 1" "list_item"
    And "img.incomplete" "css_element" should exist in the "Objective 2" "list_item"
    And "img.incomplete" "css_element" should exist in the "Objective 3" "list_item"
