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
