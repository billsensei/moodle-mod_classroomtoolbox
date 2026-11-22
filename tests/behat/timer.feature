@mod @mod_classroomtoolbox @javascript
Feature: Countdown timers
  In order to keep activities to time
  As a teacher
  I need timers with presets that tell me when time is up

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
      | activity         | name   | course | idnumber | tools | timerpresets |
      | classroomtoolbox | Timers | C1     | tb1      | timer | 0.5,2        |

  Scenario: Presets set the time
    Given I am on the "Timers" "classroomtoolbox activity" page logged in as teacher1
    Then I should see "30 s" in the "[data-region='timer-card']" "css_element"
    And I should see "2 min" in the "[data-region='timer-card']" "css_element"
    And I should see "00:30" in the "[role='timer']" "css_element"
    When I click on "2 min" "button"
    Then I should see "02:00" in the "[role='timer']" "css_element"

  Scenario: A timer counts down, can be paused, and flashes when time is up
    Given I am on the "Timers" "classroomtoolbox activity" page logged in as teacher1
    When I set the field "Minutes" to "0"
    And I set the field "Seconds" to "2"
    And I press "Start timer"
    Then I should see "Pause timer"
    When I press "Pause timer"
    Then I should see "Start timer"
    When I press "Start timer"
    And I wait "3" seconds
    Then I should see "00:00" in the "[role='timer']" "css_element"
    And ".mod_classroomtoolbox-timer-done" "css_element" should exist
    And I should see "Start timer"

  Scenario: Several timers can run side by side
    Given I am on the "Timers" "classroomtoolbox activity" page logged in as teacher1
    And I should not see "Remove timer"
    When I press "Add timer"
    Then I should see "Timer 2"
    And I should see "Remove timer"
    When I click on "(//button[@data-action='timer-remove'])[2]" "xpath_element"
    Then I should not see "Timer 2"

  Scenario: A finished timer sounds an alarm
    Given I am on the "Timers" "classroomtoolbox activity" page logged in as teacher1
    And I install an audio spy
    When I set the field "Minutes" to "0"
    And I set the field "Seconds" to "2"
    And I press "Start timer"
    And I wait "3" seconds
    Then ".mod_classroomtoolbox-timer-done" "css_element" should exist
    And at least "3" sounds should have been played
