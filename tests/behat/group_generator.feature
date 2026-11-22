@mod @mod_classroomtoolbox @javascript
Feature: Split people into random groups
  In order to organise group work quickly
  As a teacher
  I need to split the class into a number of groups or into groups of a size

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
      | activity         | name    | course | idnumber | tools  | names   |
      | classroomtoolbox | Groupie | C1     | tb1      | groups | Visitor |

  Scenario: The people list holds the students and the typed names
    When I am on the "Groupie" "classroomtoolbox activity" page logged in as teacher1
    Then I should see "5 people" in the "[data-region='groupgen-count']" "css_element"
    And the field "People to split into groups" matches multiline:
      """
      Alice Anderson
      Bob Brown
      Carla Chen
      Dmitri Ivanov
      Visitor
      """

  Scenario: Make a number of groups
    Given I am on the "Groupie" "classroomtoolbox activity" page logged in as teacher1
    When I set the field "Amount" to "2"
    And I press "Generate groups"
    Then I should see "Group 1 (3)" in the "[data-region='groupgen-result']" "css_element"
    And I should see "Group 2 (2)" in the "[data-region='groupgen-result']" "css_element"
    And I should not see "Group 3" in the "[data-region='groupgen-result']" "css_element"
    And I should see "Alice Anderson" in the "[data-region='groupgen-result']" "css_element"
    And I should see "Visitor" in the "[data-region='groupgen-result']" "css_element"

  Scenario: Make groups of a size
    Given I am on the "Groupie" "classroomtoolbox activity" page logged in as teacher1
    When I click on "People per group" "radio"
    And I set the field "Amount" to "2"
    And I press "Generate groups"
    Then I should see "Group 3 (1)" in the "[data-region='groupgen-result']" "css_element"
    And I should not see "Group 4" in the "[data-region='groupgen-result']" "css_element"

  Scenario: Edit the list, and an empty list gives a message
    Given I am on the "Groupie" "classroomtoolbox activity" page logged in as teacher1
    When I set the field "People to split into groups" to multiline:
      """
      One
      Two
      Three
      """
    Then I should see "3 people" in the "[data-region='groupgen-count']" "css_element"
    When I set the field "People to split into groups" to ""
    And I press "Generate groups"
    Then I should see "Add at least one name to make groups." in the "[data-region='groupgen-result']" "css_element"
    When I press "Reset names"
    Then I should see "5 people" in the "[data-region='groupgen-count']" "css_element"
