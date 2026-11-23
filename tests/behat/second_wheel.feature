@mod @mod_classroomtoolbox @javascript
Feature: A second wheel to split the names
  In order to pick two people at random, such as partners
  As a teacher
  I need to add a second wheel that shares the names out between two wheels

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
    And the following "activities" exist:
      | activity         | name   | course | idnumber | tools |
      | classroomtoolbox | Wheels | C1     | tb1      | wheel |

  Scenario: There is one wheel until a second one is added
    Given I am on the "Wheels" "classroomtoolbox activity" page logged in as teacher1
    Then "Add second wheel" "button" should be visible
    And "Split names again" "button" should not be visible
    And "Remove second wheel" "button" should not be visible
    And I should not see "Wheel 2"
    And I should see "4 names" in the "[data-region='wheel-count']" "css_element"

  Scenario: Adding a second wheel splits the names at random between the two
    Given I am on the "Wheels" "classroomtoolbox activity" page logged in as teacher1
    When I press "Add second wheel"
    Then I should see "Wheel 1"
    And I should see "Wheel 2"
    And "Add second wheel" "button" should not be visible
    And "Split names again" "button" should be visible
    And "Remove second wheel" "button" should be visible
    And I should see "2 names" in the "(//p[@data-region='wheel-count'])[1]" "xpath_element"
    And I should see "2 names" in the "(//p[@data-region='wheel-count'])[2]" "xpath_element"
    And the two wheels should share the names "Alice Anderson, Bob Brown, Carla Chen, Dmitri Ivanov" between them

  Scenario: An odd number of names gives one wheel the extra name
    Given the following "activities" exist:
      | activity         | name  | course | idnumber | tools | usenrolled | names |
      | classroomtoolbox | Three | C1     | tb2      | wheel | 0          | Ann   |
    When I am on the "Three" "classroomtoolbox activity" page logged in as teacher1
    And I set the field "Names on the wheel" to multiline:
      """
      Ann
      Ben
      Cat
      """
    And I press "Add second wheel"
    Then the two wheels should share the names "Ann, Ben, Cat" between them
    And I should see "2 names"
    And I should see "1 names"

  Scenario: With two wheels one button spins both and the wheel buttons are hidden
    Given I am on the "Wheels" "classroomtoolbox activity" page logged in as teacher1
    Then "Spin both wheels" "button" should not be visible
    And "[data-action='spin']" "css_element" should be visible
    When I press "Add second wheel"
    Then "Spin both wheels" "button" should be visible
    And "[data-action='spin']" "css_element" should not be visible
    And I should see "Click either wheel to spin both" in the "(//div[@data-region='wheel-unit'])[1]" "xpath_element"

  Scenario: Spinning both wheels shows the two winners together, joined by "and"
    Given I am on the "Wheels" "classroomtoolbox activity" page logged in as teacher1
    And I press "Add second wheel"
    When I press "Spin both wheels"
    And I wait until the page is ready
    Then I should see "The winners are" in the "[data-region='wheel-result']" "css_element"
    And I should see " and " in the "[data-region='wheel-winner']" "css_element"
    And I should see "Keep both on wheels" in the "[data-region='wheel-result']" "css_element"
    And I should see "Remove both from wheels" in the "[data-region='wheel-result']" "css_element"

  Scenario: Keep both leaves every name on its wheel and shows each pick
    Given I am on the "Wheels" "classroomtoolbox activity" page logged in as teacher1
    And I press "Add second wheel"
    And I press "Spin both wheels"
    And I wait until the page is ready
    When I press "Keep both on wheels"
    Then "[data-region='wheel-result']" "css_element" should not be visible
    And I should see "2 names" in the "(//p[@data-region='wheel-count'])[1]" "xpath_element"
    And I should see "2 names" in the "(//p[@data-region='wheel-count'])[2]" "xpath_element"
    And I should see "Picked:" in the "(//div[@data-region='wheel-unit'])[1]" "xpath_element"
    And I should see "Picked:" in the "(//div[@data-region='wheel-unit'])[2]" "xpath_element"

  Scenario: Remove both takes one name off each wheel, only when the teacher chooses
    Given I am on the "Wheels" "classroomtoolbox activity" page logged in as teacher1
    And I press "Add second wheel"
    And I press "Spin both wheels"
    And I wait until the page is ready
    When I press "Remove both from wheels"
    Then I should see "1 names" in the "(//p[@data-region='wheel-count'])[1]" "xpath_element"
    And I should see "1 names" in the "(//p[@data-region='wheel-count'])[2]" "xpath_element"
    And I should see "were removed from the wheels" in the "(//div[@data-region='wheel-live'])[1]" "xpath_element"
    And I should see "Picked:" in the "(//div[@data-region='wheel-unit'])[1]" "xpath_element"

  Scenario: Clicking a wheel spins both
    Given I am on the "Wheels" "classroomtoolbox activity" page logged in as teacher1
    And I press "Add second wheel"
    When I click on "(//canvas[@data-region='wheel-canvas'])[2]" "xpath_element"
    And I wait until the page is ready
    Then I should see "The winners are" in the "[data-region='wheel-result']" "css_element"

  Scenario: The second wheel starts half a second after the first
    Given I am on the "Wheels" "classroomtoolbox activity" page logged in as teacher1
    And I press "Add second wheel"
    When I start both wheels with the button and note when each starts turning
    Then the second wheel should have started between "400" and "900" milliseconds after the first

  Scenario: Both wheels need a name to spin
    Given the following "activities" exist:
      | activity         | name | course | idnumber | tools | usenrolled | names |
      | classroomtoolbox | Solo | C1     | tb3      | wheel | 0          | Ann   |
    When I am on the "Solo" "classroomtoolbox activity" page logged in as teacher1
    And I press "Add second wheel"
    And I press "Spin both wheels"
    Then I should see "Both wheels need at least one name to spin." in the "(//div[@data-region='wheel-live'])[1]" "xpath_element"
    And "[data-region='wheel-result']" "css_element" should not be visible

  Scenario: Split names again mixes everyone who is still on a wheel and clears the picked names
    Given I am on the "Wheels" "classroomtoolbox activity" page logged in as teacher1
    And I press "Add second wheel"
    And I press "Spin both wheels"
    And I wait until the page is ready
    And I press "Remove both from wheels"
    When I press "Split names again"
    Then I should not see "Picked:"
    And I should see "1 names" in the "(//p[@data-region='wheel-count'])[1]" "xpath_element"
    And I should see "1 names" in the "(//p[@data-region='wheel-count'])[2]" "xpath_element"

  Scenario: Removing the second wheel puts all the names back on one wheel
    Given I am on the "Wheels" "classroomtoolbox activity" page logged in as teacher1
    And I press "Add second wheel"
    When I press "Remove second wheel"
    Then I should see "4 names" in the "[data-region='wheel-count']" "css_element"
    And I should not see "Wheel 2"
    And I should not see "Wheel 1"
    And "Add second wheel" "button" should be visible
    And "Remove second wheel" "button" should not be visible
    And "Spin both wheels" "button" should not be visible
    And "[data-action='spin']" "css_element" should be visible

  Scenario: Reset wheel brings removed names back and splits them again
    Given I am on the "Wheels" "classroomtoolbox activity" page logged in as teacher1
    And I press "Add second wheel"
    And I press "Spin both wheels"
    And I wait until the page is ready
    And I press "Remove both from wheels"
    When I press "Reset wheel"
    Then the two wheels should share the names "Alice Anderson, Bob Brown, Carla Chen, Dmitri Ivanov" between them
    And I should not see "Picked:"
