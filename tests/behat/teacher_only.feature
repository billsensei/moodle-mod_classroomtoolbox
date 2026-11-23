@mod @mod_classroomtoolbox
Feature: The toolbox is for teachers only
  In order to use the toolbox on the classroom screen without students getting at it
  As a teacher
  I need the toolbox to be closed to students

  Background:
    Given the following "users" exist:
      | username | firstname | lastname |
      | teacher1 | Tina      | Teacher  |
      | student1 | Alice     | Anderson |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following "activities" exist:
      | activity         | name    | course | idnumber | tools |
      | classroomtoolbox | Toolbox | C1     | tb1      | wheel |

  Scenario: A student does not see the toolbox in the course
    When I am on the "C1" "Course" page logged in as student1
    Then I should not see "Toolbox"

  Scenario: A student cannot open the toolbox, even with its address
    When I am on the "Toolbox" "classroomtoolbox activity" page logged in as student1
    Then "[data-region='toolbox']" "css_element" should not exist
    And I should not see "Spin"

  Scenario: A teacher sees and opens the toolbox
    When I am on the "C1" "Course" page logged in as teacher1
    Then I should see "Toolbox"
    When I follow "Toolbox"
    Then "[data-region='toolbox']" "css_element" should exist
