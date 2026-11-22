@mod @mod_classroomtoolbox @javascript
Feature: Combine groups on one wheel
  In order to run an activity with only some of the class
  As a teacher
  I need to tick several groups and see only their members on the wheel

  Background:
    Given the following "users" exist:
      | username | firstname | lastname |
      | teacher1 | Tina      | Teacher  |
      | student1 | Alice     | Anderson |
      | student2 | Bob       | Brown    |
      | student3 | Carla     | Chen     |
      | student4 | Dmitri    | Ivanov   |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | student3 | C1     | student        |
      | student4 | C1     | student        |
    And the following "groups" exist:
      | name    | course | idnumber |
      | Group A | C1     | GA       |
      | Group B | C1     | GB       |
    And the following "group members" exist:
      | user     | group |
      | student1 | GA    |
      | student2 | GA    |
      | student3 | GB    |
    And the following "activities" exist:
      | activity  | name     | course | idnumber | groupmode |
      | classroomtoolbox | Pick one | C1     | wheel1   | 2         |

  Scenario: Ticking groups combines them and removed names stay removed
    Given I am on the "Pick one" "classroomtoolbox activity" page logged in as teacher1
    Then I should see "Group A (2)"
    And I should see "Group B (1)"
    And I should see "Not in any group (1)"
    And I should see "4 names" in the "[data-region='wheel-count']" "css_element"
    When I press "Select none"
    Then I should see "0 names" in the "[data-region='wheel-count']" "css_element"
    When I set the field "Group A (2)" to "1"
    Then I should see "2 names" in the "[data-region='wheel-count']" "css_element"
    When I set the field "Group B (1)" to "1"
    Then I should see "3 names" in the "[data-region='wheel-count']" "css_element"
    And the field "Names on the wheel" matches multiline:
      """
      Alice Anderson
      Bob Brown
      Carla Chen
      """
    When I press "Select all"
    Then I should see "4 names" in the "[data-region='wheel-count']" "css_element"

  Scenario: A student in separate groups only sees their own group
    Given I am on the "Pick one" "classroomtoolbox activity" page logged in as teacher1
    And I log out
    And the following "activities" exist:
      | activity  | name      | course | idnumber | groupmode |
      | classroomtoolbox | Separate  | C1     | wheel2   | 1         |
    When I am on the "Separate" "classroomtoolbox activity" page logged in as student1
    Then I should see "Group A (2)"
    And I should not see "Group B"
    And I should not see "Not in any group"
    And I should see "2 names" in the "[data-region='wheel-count']" "css_element"
    And the field "Names on the wheel" matches multiline:
      """
      Alice Anderson
      Bob Brown
      """
