@mod @mod_classroomtoolbox @javascript
Feature: Configure a name wheel
  In order to fit the wheel to my class
  As a teacher
  I need to choose its colour scheme and extra names

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

  Scenario: A teacher adds a wheel with a colour scheme and extra names
    Given I am on the "Course 1" course page logged in as teacher1
    And I add a "classroomtoolbox" activity to course "Course 1" section "1"
    And I set the following fields to these values:
      | Name          | Class wheel                              |
      | Colour scheme | High contrast (dark colours, white text) |
      | Extra names   | Visitor                                  |
    When I press "Save and display"
    Then I should see "Class wheel"
    And the field "Names on the wheel" matches value "Visitor"

  Scenario: The colour scheme list offers all five schemes
    Given I am on the "Course 1" course page logged in as teacher1
    And I add a "classroomtoolbox" activity to course "Course 1" section "1"
    When I expand all fieldsets
    Then the "Colour scheme" select box should contain "Classic (bold primary colours)"
    And the "Colour scheme" select box should contain "Ocean (blues and teals)"
    And the "Colour scheme" select box should contain "Sunset (reds, oranges and purples)"
    And the "Colour scheme" select box should contain "Pastel (soft colours, dark text)"
    And the "Colour scheme" select box should contain "High contrast (dark colours, white text)"

  Scenario: The tools can be chosen
    Given I am on the "Course 1" course page logged in as teacher1
    And I add a "classroomtoolbox" activity to course "Course 1" section "1"
    And I expand all fieldsets
    Then the field "id_tool_wheel" matches value "1"
    And the field "id_tool_partners" matches value "1"
    And the field "id_tool_groups" matches value "1"
    And the field "id_tool_stopwatch" matches value "1"
    And the field "id_tool_timer" matches value "1"
    And the field "id_tool_counter" matches value "1"
    And the field "id_tool_scoreboard" matches value "1"

  Scenario: At least one tool must stay ticked
    Given I am on the "Course 1" course page logged in as teacher1
    And I add a "classroomtoolbox" activity to course "Course 1" section "1"
    And I expand all fieldsets
    When I set the following fields to these values:
      | Name              | No tools |
      | id_tool_wheel     | 0        |
      | id_tool_partners  | 0        |
      | id_tool_groups    | 0        |
      | id_tool_stopwatch | 0        |
      | id_tool_timer     | 0        |
      | id_tool_counter   | 0        |
      | id_tool_scoreboard | 0       |
    And I press "Save and display"
    Then I should see "Choose at least one tool."

  Scenario: Timer presets are checked
    Given I am on the "Course 1" course page logged in as teacher1
    And I add a "classroomtoolbox" activity to course "Course 1" section "1"
    And I expand all fieldsets
    When I set the following fields to these values:
      | Name                       | Timers |
      | Timer presets (minutes)    | abc    |
    And I press "Save and display"
    Then I should see "Enter up to 8 numbers between 0.1 and 180, separated by commas."
    When I set the field "Timer presets (minutes)" to "15, 0.5, 2"
    And I press "Save and display"
    Then I should see "Timers"
