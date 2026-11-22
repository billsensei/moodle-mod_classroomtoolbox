@mod @mod_classroomtoolbox @javascript
Feature: Spin the name wheel
  In order to pick a student at random
  As a teacher or student
  I need to spin the wheel and choose whether to remove the winner

  Background:
    Given the following "users" exist:
      | username | firstname | lastname |
      | teacher1 | Tina      | Teacher  |
      | student1 | Alice     | Anderson |
      | student2 | Bob       | Brown    |
      | student3 | Carla     | Chen     |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | student3 | C1     | student        |
    And the following "activities" exist:
      | activity  | name       | course | idnumber | names         |
      | classroomtoolbox | Pick one   | C1     | wheel1   | Guest Speaker |

  Scenario: The wheel lists the enrolled students and the typed names
    When I am on the "Pick one" "classroomtoolbox activity" page logged in as student1
    Then I should see "4 names" in the "[data-region='wheel-count']" "css_element"
    And the field "Names on the wheel" matches multiline:
      """
      Alice Anderson
      Bob Brown
      Carla Chen
      Guest Speaker
      """

  Scenario: Spinning shows a winner who can be kept
    Given I am on the "Pick one" "classroomtoolbox activity" page logged in as student1
    When I click on "[data-region='wheel-canvas']" "css_element"
    And I wait until the page is ready
    Then I should see "The winner is" in the "[data-region='wheel-result']" "css_element"
    When I press "Keep on wheel"
    Then "[data-region='wheel-result']" "css_element" should not be visible
    And I should see "4 names" in the "[data-region='wheel-count']" "css_element"

  Scenario: Spinning shows a winner who can be removed
    Given I am on the "Pick one" "classroomtoolbox activity" page logged in as student1
    When I press "Spin"
    And I wait until the page is ready
    Then I should see "The winner is" in the "[data-region='wheel-result']" "css_element"
    When I press "Remove from wheel"
    Then "[data-region='wheel-result']" "css_element" should not be visible
    And I should see "3 names" in the "[data-region='wheel-count']" "css_element"
    And I should see "was removed from the wheel" in the "[data-region='wheel-live']" "css_element"

  Scenario: Reset brings removed names back and the names box can be edited
    Given I am on the "Pick one" "classroomtoolbox activity" page logged in as student1
    And I press "Spin"
    And I wait until the page is ready
    And I press "Remove from wheel"
    When I press "Reset wheel"
    Then I should see "4 names" in the "[data-region='wheel-count']" "css_element"
    When I set the field "Names on the wheel" to multiline:
      """
      Two
      One
      """
    Then I should see "2 names" in the "[data-region='wheel-count']" "css_element"
    When I press "Sort A-Z"
    Then the field "Names on the wheel" matches multiline:
      """
      One
      Two
      """

  Scenario: The winner dialog keeps keyboard focus inside and closes with Escape
    Given I am on the "Pick one" "classroomtoolbox activity" page logged in as student1
    When I click on "[data-region='wheel-canvas']" "css_element"
    And I wait until the page is ready
    Then the focus should be on "[data-action='keep']"
    And "[data-region='wheel-canvas']" should be inert
    When I press the "Tab" key in "[data-action='keep']"
    Then the focus should be on "[data-action='remove']"
    When I press the "Shift+Tab" key in "[data-action='remove']"
    Then the focus should be on "[data-action='keep']"
    When I press the "Escape" key in "[data-action='keep']"
    Then "[data-region='wheel-result']" "css_element" should not be visible
    And the focus should be on "[data-region='wheel-canvas']"
    And "[data-region='wheel-canvas']" should not be inert

  Scenario: The wheel ticks while it spins and chimes when a name is picked
    Given I am on the "Pick one" "classroomtoolbox activity" page logged in as student1
    And I install an audio spy
    When I click on "[data-region='wheel-canvas']" "css_element"
    And I wait until the page is ready
    Then I should see "The winner is" in the "[data-region='wheel-result']" "css_element"
    And at least "15" sounds should have been played

  Scenario: Sound can be switched off, the choice is remembered, and it can be switched back on
    Given I am on the "Pick one" "classroomtoolbox activity" page logged in as student1
    And I install an audio spy
    When I click on "Sound" "checkbox"
    And I press "Spin"
    And I wait until the page is ready
    Then I should see "The winner is" in the "[data-region='wheel-result']" "css_element"
    And no sounds should have been played
    When I press "Keep on wheel"
    And I reload the page
    Then the field "Sound" matches value ""
    When I click on "Sound" "checkbox"
    Then the field "Sound" matches value "1"
