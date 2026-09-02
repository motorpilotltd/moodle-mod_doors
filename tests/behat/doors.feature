@mod @mod_doors
Feature: View and open doors in a door calendar
  In order to reveal daily content
  As a student
  I need to see and open the doors in a door calendar

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Sam       | Student  | student1@example.com |
      | teacher1 | Terry     | Teacher  | teacher1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
      | teacher1 | C1     | editingteacher |
    And the following "activities" exist:
      | activity | course | name        | idnumber | numdoors |
      | doors    | C1     | Countdown   | DOORS1   | 3        |

  Scenario: A student sees every door and the progress bar
    When I am on the "DOORS1" "Activity" page logged in as "student1"
    Then "button.doors-door[data-doornumber='1']" "css_element" should exist
    And "button.doors-door[data-doornumber='3']" "css_element" should exist
    And I should see "0 of 3 doors opened"

  @javascript
  Scenario: A student opens a door and sees its content
    Given I am on the "DOORS1" "Activity" page logged in as "student1"
    When I click on "button.doors-door[data-doornumber='2']" "css_element"
    Then I should see "Door 2" in the ".doors-modal-title" "css_element"
    And I should see "Nothing has been put behind this door yet." in the ".doors-modal-body" "css_element"
    And I click on ".doors-modal-close" "css_element"
    And I should see "1 of 3 doors opened"

  @javascript
  Scenario: A teacher puts a title behind a door from the manage page
    Given I am on the "DOORS1" "Activity" page logged in as "teacher1"
    When I navigate to "Manage doors" in current page administration
    And I click on "Edit" "link" in the "1" "table_row"
    And I set the field "Title" to "Welcome"
    And I press "Save changes"
    Then I should see "Door 1 saved"
    And I should see "Welcome" in the ".doors-admin-table" "css_element"
