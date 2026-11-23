@mod @mod_classroomtoolbox @javascript @accessibility
Feature: Accessibility checks (axe-core)
  In order to keep the toolbox usable with assistive technology
  As a maintainer
  I need every tool to pass automated accessibility checks

  Background:
    Given the following "users" exist:
      | username | firstname | lastname |
      | teacher1 | Tina      | Teacher  |
      | student1 | Alice     | Anderson |
      | student2 | Bob       | Brown    |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
    And the following "groups" exist:
      | name    | course | idnumber |
      | Group A | C1     | GA       |
    And the following "group members" exist:
      | user     | group |
      | student1 | GA    |
    And the following "activities" exist:
      | activity         | name    | course | idnumber | tools                                                    | groupmode |
      | classroomtoolbox | Toolbox | C1     | tb1      | wheel,partners,groups,stopwatch,timer,counter,scoreboard | 2         |
    And the scoreboard of "Toolbox" has these entries:
      | label     | score |
      | Team Red  | 12    |
      | Team Blue | 7     |

  Scenario: Wheel tab
    Given I am on the "Toolbox" "classroomtoolbox activity" page logged in as teacher1
    Then the "[data-region='toolbox']" "css_element" should meet accessibility standards with "best-practice" extra tests

  Scenario: Wheel result dialog
    Given I am on the "Toolbox" "classroomtoolbox activity" page logged in as teacher1
    When I click on "[data-region='wheel-canvas']" "css_element"
    And I wait until the page is ready
    And I wait "2" seconds
    Then the "[data-region='wheel-result']" "css_element" should meet accessibility standards with "best-practice" extra tests

  Scenario: Partner wheel tab
    Given I am on the "Toolbox" "classroomtoolbox activity" page logged in as teacher1
    When I click on "Partner wheel" "button" in the "[data-region='toolbox'] [role='tablist']" "css_element"
    Then the "[data-region='toolbox']" "css_element" should meet accessibility standards with "best-practice" extra tests

  Scenario: Partner wheel result dialog
    Given I am on the "Toolbox" "classroomtoolbox activity" page logged in as teacher1
    When I click on "Partner wheel" "button" in the "[data-region='toolbox'] [role='tablist']" "css_element"
    And I click on "[data-region='partners'] [data-region='wheel-canvas']" "css_element"
    And I wait until the page is ready
    And I wait "2" seconds
    Then the "[data-region='partners'] [data-region='wheel-result']" "css_element" should meet accessibility standards with "best-practice" extra tests

  Scenario: Group generator tab
    Given I am on the "Toolbox" "classroomtoolbox activity" page logged in as teacher1
    When I click on "Group generator" "button" in the "[data-region='toolbox'] [role='tablist']" "css_element"
    And I press "Generate groups"
    And I wait "2" seconds
    Then the "[data-region='toolbox']" "css_element" should meet accessibility standards with "best-practice" extra tests

  Scenario: Stopwatch tab
    Given I am on the "Toolbox" "classroomtoolbox activity" page logged in as teacher1
    When I click on "Stopwatch" "button" in the "[data-region='toolbox'] [role='tablist']" "css_element"
    And I press "Start stopwatch"
    And I press "Lap"
    Then the "[data-region='toolbox']" "css_element" should meet accessibility standards with "best-practice" extra tests

  Scenario: Timer tab
    Given I am on the "Toolbox" "classroomtoolbox activity" page logged in as teacher1
    When I click on "Timer" "button" in the "[data-region='toolbox'] [role='tablist']" "css_element"
    And I press "Add timer"
    Then the "[data-region='toolbox']" "css_element" should meet accessibility standards with "best-practice" extra tests

  Scenario: Counter tab
    Given I am on the "Toolbox" "classroomtoolbox activity" page logged in as teacher1
    When I click on "Counter" "button" in the "[data-region='toolbox'] [role='tablist']" "css_element"
    And I press "Add counter"
    Then the "[data-region='toolbox']" "css_element" should meet accessibility standards with "best-practice" extra tests

  Scenario: Scoreboard tab as a teacher
    Given I am on the "Toolbox" "classroomtoolbox activity" page logged in as teacher1
    When I click on "Scoreboard" "button" in the "[data-region='toolbox'] [role='tablist']" "css_element"
    Then the "[data-region='toolbox']" "css_element" should meet accessibility standards with "best-practice" extra tests
