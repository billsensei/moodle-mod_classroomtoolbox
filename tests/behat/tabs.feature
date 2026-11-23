@mod @mod_classroomtoolbox @javascript
Feature: Switch between the classroom tools
  In order to use several tools in one place
  As a teacher
  I need tabs for the tools switched on

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
      | activity         | name    | course | idnumber | tools                                                    |
      | classroomtoolbox | Toolbox | C1     | tb1      | wheel,partners,groups,stopwatch,timer,counter,scoreboard |

  Scenario: Every tool has a tab and only one shows at a time
    Given I am on the "Toolbox" "classroomtoolbox activity" page logged in as teacher1
    Then I should see "Name wheel" in the "[data-region='toolbox'] [role='tablist']" "css_element"
    And I should see "Partner wheel" in the "[data-region='toolbox'] [role='tablist']" "css_element"
    And I should see "Group generator" in the "[data-region='toolbox'] [role='tablist']" "css_element"
    And I should see "Stopwatch" in the "[data-region='toolbox'] [role='tablist']" "css_element"
    And I should see "Timer" in the "[data-region='toolbox'] [role='tablist']" "css_element"
    And I should see "Counter" in the "[data-region='toolbox'] [role='tablist']" "css_element"
    And I should see "Scoreboard" in the "[data-region='toolbox'] [role='tablist']" "css_element"
    And "[data-region='wheel-canvas']" "css_element" should be visible
    And "[data-region='sw-display']" "css_element" should not be visible
    When I click on "Stopwatch" "button" in the "[data-region='toolbox'] [role='tablist']" "css_element"
    Then "[data-region='sw-display']" "css_element" should be visible
    And "[data-region='wheel-canvas']" "css_element" should not be visible
    When I click on "Counter" "button" in the "[data-region='toolbox'] [role='tablist']" "css_element"
    Then "[data-region='counter-list']" "css_element" should be visible
    And "[data-region='sw-display']" "css_element" should not be visible
    When I click on "Name wheel" "button" in the "[data-region='toolbox'] [role='tablist']" "css_element"
    Then "[data-region='wheel-canvas']" "css_element" should be visible

  Scenario: The name wheel and the partner wheel sit side by side without mixing
    Given I am on the "Toolbox" "classroomtoolbox activity" page logged in as teacher1
    Then "[data-region='wheel'] [data-region='wheel-canvas']" "css_element" should be visible
    And "[data-region='partners'] [data-region='wheel-canvas']" "css_element" should not be visible
    When I click on "Partner wheel" "button" in the "[data-region='toolbox'] [role='tablist']" "css_element"
    Then "[data-region='partners'] [data-region='wheel-canvas']" "css_element" should be visible
    And "[data-region='wheel'] [data-region='wheel-canvas']" "css_element" should not be visible
    And I should see "Pairs so far"
    When I click on "Name wheel" "button" in the "[data-region='toolbox'] [role='tablist']" "css_element"
    Then I should not see "Pairs so far"

  Scenario: A toolbox with one tool has no tab bar
    Given the following "activities" exist:
      | activity         | name | course | idnumber | tools   |
      | classroomtoolbox | Solo | C1     | tb2      | counter |
    When I am on the "Solo" "classroomtoolbox activity" page logged in as teacher1
    Then "[data-region='toolbox'] [role='tablist']" "css_element" should not exist
    And "[data-region='counter-list']" "css_element" should be visible

  Scenario: The toolbox opens on the tab that was used last
    Given I am on the "Toolbox" "classroomtoolbox activity" page logged in as teacher1
    When I click on "Stopwatch" "button" in the "[data-region='toolbox'] [role='tablist']" "css_element"
    And I reload the page
    Then "[data-region='sw-display']" "css_element" should be visible
    And "[data-region='wheel-canvas']" "css_element" should not be visible
