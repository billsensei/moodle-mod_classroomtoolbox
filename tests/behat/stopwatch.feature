@mod @mod_classroomtoolbox @javascript
Feature: Stopwatch
  In order to time activities
  As a teacher
  I need a stopwatch with laps

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
      | activity         | name  | course | idnumber | tools     |
      | classroomtoolbox | Watch | C1     | tb1      | stopwatch |

  Scenario: Start, lap, stop and reset
    Given I am on the "Watch" "classroomtoolbox activity" page logged in as teacher1
    Then I should see "00:00.00" in the "[data-region='sw-display']" "css_element"
    When I press "Start stopwatch"
    And I wait "2" seconds
    And I press "Lap"
    Then I should see "Lap 1" in the "[data-region='sw-laps']" "css_element"
    When I press "Stop stopwatch"
    Then I should see "Start stopwatch"
    And I should not see "00:00.00" in the "[data-region='sw-display']" "css_element"
    When I press "Reset stopwatch"
    Then I should see "00:00.00" in the "[data-region='sw-display']" "css_element"
    And "[data-region='sw-laps-wrap']" "css_element" should not be visible
