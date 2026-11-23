@mod @mod_classroomtoolbox @javascript
Feature: Pick random partners
  In order to pair students up for an activity
  As a teacher
  I need a wheel that spins twice to pick a pair

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
      | activity         | name     | course | idnumber | tools    |
      | classroomtoolbox | Partners | C1     | tb1      | partners |

  Scenario: Two spins make a pair and both leave the wheel
    Given I am on the "Partners" "classroomtoolbox activity" page logged in as teacher1
    Then I should see "4 names" in the "[data-region='wheel-count']" "css_element"
    And I should see "No pairs yet" in the "[data-region='partners']" "css_element"
    When I press "Spin"
    And I wait until the page is ready
    Then I should see "First person" in the "[data-region='wheel-result']" "css_element"
    And I should not see "Next pair"
    When I press "Spin for a partner"
    Then I should see "3 names" in the "[data-region='wheel-count']" "css_element"
    And I should see "Spin to find a partner for" in the "[data-region='wheel-hint']" "css_element"
    When I press "Spin"
    And I wait until the page is ready
    Then I should see "Partners" in the "[data-region='wheel-result']" "css_element"
    When I press "Next pair"
    Then I should see "2 names" in the "[data-region='wheel-count']" "css_element"
    And I should not see "No pairs yet"
    And "[data-region='partners-pairs'] li" "css_element" should exist
    And I should see "are partners" in the "[data-region='wheel-live']" "css_element"

  Scenario: Pick someone else puts the partner back on the wheel
    Given I am on the "Partners" "classroomtoolbox activity" page logged in as teacher1
    And I press "Spin"
    And I wait until the page is ready
    And I press "Spin for a partner"
    And I press "Spin"
    And I wait until the page is ready
    When I press "Pick someone else"
    Then I should see "3 names" in the "[data-region='wheel-count']" "css_element"
    And I should see "Spin to find a partner for" in the "[data-region='wheel-hint']" "css_element"
    And I should see "No pairs yet" in the "[data-region='partners']" "css_element"

  Scenario: An odd one out joins the last pair
    Given the following "activities" exist:
      | activity         | name  | course | idnumber | tools    | usenrolled | names |
      | classroomtoolbox | Three | C1     | tb2      | partners | 0          | Ann   |
    When I am on the "Three" "classroomtoolbox activity" page logged in as teacher1
    And I set the field "Names on the wheel" to multiline:
      """
      Ann
      Ben
      Cat
      """
    And I press "Spin"
    And I wait until the page is ready
    And I press "Spin for a partner"
    And I press "Spin"
    And I wait until the page is ready
    And I press "Next pair"
    Then I should see "0 names" in the "[data-region='wheel-count']" "css_element"
    And I should see "Ann" in the "[data-region='partners-pairs']" "css_element"
    And I should see "Ben" in the "[data-region='partners-pairs']" "css_element"
    And I should see "Cat" in the "[data-region='partners-pairs']" "css_element"
    And "[data-region='partners-pairs'] li" "css_element" should exist

  Scenario: One name is not enough to make a pair
    Given the following "activities" exist:
      | activity         | name | course | idnumber | tools    | usenrolled | names |
      | classroomtoolbox | Solo | C1     | tb3      | partners | 0          | Ann   |
    When I am on the "Solo" "classroomtoolbox activity" page logged in as teacher1
    And I press "Spin"
    Then I should see "Add at least two names to make a pair" in the "[data-region='wheel-hint']" "css_element"
    And "[data-region='wheel-result']" "css_element" should not be visible

  Scenario: Reset brings everyone back and clears the pairs
    Given I am on the "Partners" "classroomtoolbox activity" page logged in as teacher1
    And I press "Spin"
    And I wait until the page is ready
    And I press "Spin for a partner"
    And I press "Spin"
    And I wait until the page is ready
    And I press "Next pair"
    When I press "Reset"
    Then I should see "4 names" in the "[data-region='wheel-count']" "css_element"
    And I should see "No pairs yet" in the "[data-region='partners']" "css_element"
