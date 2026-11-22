@mod @mod_classroomtoolbox @javascript
Feature: Counters
  In order to keep score or count things in class
  As a teacher
  I need counters I can name, raise, lower and reset

  Background:
    Given the following "users" exist:
      | username | firstname | lastname |
      | teacher1 | Tina      | Teacher  |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And the following "activities" exist:
      | activity         | name     | course | idnumber | tools   |
      | classroomtoolbox | Counting | C1     | tb1      | counter |

  Scenario: Raise, lower and reset a counter
    Given I am on the "Counting" "classroomtoolbox activity" page logged in as teacher1
    Then I should see "0" in the "output" "css_element"
    When I press "Increase Counter 1"
    And I press "Increase Counter 1"
    And I press "Increase Counter 1"
    And I press "Decrease Counter 1"
    Then I should see "2" in the "output" "css_element"
    When I press "Reset Counter 1"
    Then I should see "0" in the "output" "css_element"

  Scenario: Name a counter and add another
    Given I am on the "Counting" "classroomtoolbox activity" page logged in as teacher1
    When I set the field "Counter name" to "Team Red"
    And I press "Increase Team Red"
    Then I should see "1" in the "output" "css_element"
    When I press "Add counter"
    Then I should see "Counter 2"
    And I should see "Remove" in the "[data-region='counter-list']" "css_element"
