@mod @mod_classroomtoolbox @javascript
Feature: Scoreboard
  In order to run a points competition in class
  As a teacher
  I need a scoreboard I can add teams to and give points, that students can watch

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
    And the following "groups" exist:
      | name    | course | idnumber |
      | Group A | C1     | GA       |
    And the following "activities" exist:
      | activity         | name  | course | idnumber | tools      |
      | classroomtoolbox | Board | C1     | tb1      | scoreboard |

  Scenario: A teacher builds a board and gives points
    Given I am on the "Board" "classroomtoolbox activity" page logged in as teacher1
    Then I should see "No teams or names yet" in the "[data-region='score-empty']" "css_element"
    When I set the field "Team or name (separate several with commas)" to "Team Red, Team Blue"
    And I click on "[data-region='score-add-form'] button[type='submit']" "css_element"
    Then the scoreboard order should be "Team Red, Team Blue"
    When I press "Add points to Team Blue"
    And I press "Add points to Team Blue"
    And I press "Add points to Team Red"
    Then the scoreboard order should be "Team Blue, Team Red"
    And the scoreboard score of "Team Blue" should be "2"
    And the scoreboard score of "Team Red" should be "1"
    When I set the field "Points per click" to "5"
    And I press "Add points to Team Red"
    Then the scoreboard order should be "Team Red, Team Blue"
    And the scoreboard score of "Team Red" should be "6"
    When I press "Subtract points from Team Red"
    Then the scoreboard score of "Team Red" should be "1"

  Scenario: A teacher can type an exact score and rename an entry
    Given the scoreboard of "Board" has these entries:
      | label    | score |
      | Team Red | 3     |
    And I am on the "Board" "classroomtoolbox activity" page logged in as teacher1
    When I set the field with xpath "//input[@data-field='points' and @aria-label='Points for Team Red']" to "42"
    Then the scoreboard score of "Team Red" should be "42"
    When I set the field with xpath "//input[@data-field='label']" to "Crimson"
    Then the scoreboard order should be "Crimson"
    And the scoreboard score of "Crimson" should be "42"

  Scenario: Entries can be added from the groups and the participants
    Given I am on the "Board" "classroomtoolbox activity" page logged in as teacher1
    When I press "Add groups"
    Then the scoreboard order should be "Group A"
    When I press "Add participants"
    Then the scoreboard order should be "Group A, Alice Anderson"

  Scenario: Entries can be removed, and resetting asks first
    Given the scoreboard of "Board" has these entries:
      | label     | score |
      | Team Red  | 12    |
      | Team Blue | 7     |
    And I am on the "Board" "classroomtoolbox activity" page logged in as teacher1
    When I press "Remove Team Blue"
    Then the scoreboard order should be "Team Red"
    When I press "Reset scores"
    Then I should see "Set every score back to zero?"
    When I click on "Cancel" "button" in the "Reset the scoreboard" "dialogue"
    Then the scoreboard score of "Team Red" should be "12"
    When I press "Reset scores"
    And I click on "Reset scores" "button" in the "Reset the scoreboard" "dialogue"
    Then the scoreboard score of "Team Red" should be "0"
    And the scoreboard order should be "Team Red"

  Scenario: A student sees the board but cannot change it
    Given the scoreboard of "Board" has these entries:
      | label     | score |
      | Team Blue | 7     |
      | Team Red  | 12    |
    When I am on the "Board" "classroomtoolbox activity" page logged in as student1
    Then the scoreboard order should be "Team Red, Team Blue"
    And the scoreboard score of "Team Red" should be "12"
    And I should see "This board updates by itself."
    And "[data-region='score-add-form']" "css_element" should not exist
    And "[data-region='score-list'] input" "css_element" should not exist
    And "[data-region='score-list'] button" "css_element" should not exist
    And I should not see "Reset scores"

  Scenario: A viewer can pause the live updates
    Given the scoreboard of "Board" has these entries:
      | label    | score |
      | Team Red | 12    |
    And I am on the "Board" "classroomtoolbox activity" page logged in as student1
    And the scoreboard order should be "Team Red"
    When I click on "Pause live updates" "checkbox"
    And the scoreboard of "Board" has these entries:
      | label     | score |
      | Team Blue | 20    |
    And I wait "7" seconds
    Then the scoreboard order should be "Team Red"
    When I click on "Pause live updates" "checkbox"
    And I wait "2" seconds
    Then the scoreboard order should be "Team Blue, Team Red"

