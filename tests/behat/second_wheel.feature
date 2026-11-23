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

  Scenario: Either wheel can be spun first and names stay unless the teacher removes them
    Given I am on the "Wheels" "classroomtoolbox activity" page logged in as teacher1
    And I press "Add second wheel"
    When I press "Spin (Wheel 2)"
    And I wait until the page is ready
    Then I should see "The winner is (Wheel 2)" in the "[data-region='wheel-result']" "css_element"
    When I press "Keep on wheel"
    Then I should see "2 names" in the "(//p[@data-region='wheel-count'])[1]" "xpath_element"
    And I should see "2 names" in the "(//p[@data-region='wheel-count'])[2]" "xpath_element"
    And I should see "Picked:" in the "(//div[@data-region='wheel-unit'])[2]" "xpath_element"
    When I press "Spin (Wheel 1)"
    And I wait until the page is ready
    Then I should see "The winner is (Wheel 1)" in the "[data-region='wheel-result']" "css_element"
    When I press "Remove from wheel"
    Then I should see "1 names" in the "(//p[@data-region='wheel-count'])[1]" "xpath_element"
    And I should see "2 names" in the "(//p[@data-region='wheel-count'])[2]" "xpath_element"
    And I should see "Picked:" in the "(//div[@data-region='wheel-unit'])[1]" "xpath_element"
    And I should see "was removed from the wheel" in the "(//div[@data-region='wheel-live'])[1]" "xpath_element"

  Scenario: Split names again mixes everyone who is still on a wheel and clears the picked names
    Given I am on the "Wheels" "classroomtoolbox activity" page logged in as teacher1
    And I press "Add second wheel"
    And I press "Spin (Wheel 1)"
    And I wait until the page is ready
    And I press "Remove from wheel"
    When I press "Split names again"
    Then I should not see "Picked:"
    And I should see "2 names"
    And I should see "1 names"

  Scenario: Removing the second wheel puts all the names back on one wheel
    Given I am on the "Wheels" "classroomtoolbox activity" page logged in as teacher1
    And I press "Add second wheel"
    When I press "Remove second wheel"
    Then I should see "4 names" in the "[data-region='wheel-count']" "css_element"
    And I should not see "Wheel 2"
    And I should not see "Wheel 1"
    And "Add second wheel" "button" should be visible
    And "Remove second wheel" "button" should not be visible

  Scenario: Reset wheel brings removed names back and splits them again
    Given I am on the "Wheels" "classroomtoolbox activity" page logged in as teacher1
    And I press "Add second wheel"
    And I press "Spin (Wheel 2)"
    And I wait until the page is ready
    And I press "Remove from wheel"
    When I press "Reset wheel"
    Then the two wheels should share the names "Alice Anderson, Bob Brown, Carla Chen, Dmitri Ivanov" between them
    And I should not see "Picked:"
