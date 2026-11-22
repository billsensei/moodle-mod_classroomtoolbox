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

  Scenario: At least one tool must stay ticked
    Given I am on the "Course 1" course page logged in as teacher1
    And I add a "classroomtoolbox" activity to course "Course 1" section "1"
    And I expand all fieldsets
    When I set the following fields to these values:
      | Name      | No tools |
      | Name wheel | 0       |
    And I press "Save and display"
    Then I should see "Choose at least one tool."
