@tool @tool_mutenancy @MuTMS @javascript
Feature: Tenant members and associated users section
  Background:
    Given unnecessary Admin bookmarks block gets deleted
    And the following "tool_mutenancy > tenants" exist:
      | name     | idnumber |
      | Tenant 1 | TEN1     |
      | Tenant 2 | TEN2     |
    And the following "users" exist:
      | username  | firstname | lastname  | email                | tenant |
      | manager1  | Tenant 1  | Manager   | manager1@example.com | TEN1   |
    And the following "tool_mutenancy > tenant managers" exist:
      | tenant | user     |
      | TEN1   | manager1 |

  Scenario: Tenant manager may add, update and delete tenant members
    Given I log in as "manager1"
    And I am on the "TEN1" "tool_mutenancy > Tenant users" page

    When I press "Create account"
    And I set the following fields to these values:
      | Username      | member1              |
      | New password  | tEstP_s8             |
      | First name    | First                |
      | Last name     | Member               |
      | Email address | member1@example.com  |
    And I press "Create account"
    Then the following should exist in the "reportbuilder-table" table:
      | First name    | Email address        | Tenant member |
      | First Member  | member1@example.com  | Yes           |

    And I log out
    And I am on homepage
    When I set the field "Username" to "member1"
    And I set the field "Password" to "tEstP_s8"
    And I press "Log in"
    Then I should see "Welcome, First!"

    And I log in as "manager1"
    And I am on the "TEN1" "tool_mutenancy > Tenant users" page
    When I click on "Actions" "link" in the "First Member" "table_row"
    And I click on "Edit" "link" in the "First Member" "table_row"
    And I set the following fields to these values:
      | Username      | student1             |
      | First name    | Prvni                |
      | Last name     | Student              |
      | Email address | student1@example.com |
    And I press "Update account"
    Then the following should exist in the "reportbuilder-table" table:
      | First name    | Email address        | Tenant member |
      | Prvni Student | student1@example.com | Yes           |

    When I click on "Actions" "link" in the "Prvni Student" "table_row"
    And I click on "Suspend user account" "link" in the "Prvni Student" "table_row"
    And I click on "Suspend user account" "button" in the "dialog[open]" "css_element"
    Then I should see "Suspended" in the "student1@example.com" "table_row"

    When I click on "Actions" "link" in the "Prvni Student" "table_row"
    And I click on "Activate user account" "link" in the "Prvni Student" "table_row"
    And I click on "Activate user account" "button" in the "dialog[open]" "css_element"
    Then I should not see "Suspended" in the "student1@example.com" "table_row"

    When I click on "Actions" "link" in the "Prvni Student" "table_row"
    And I click on "Delete" "link" in the "Prvni Student" "table_row"
    And I click on "Delete" "button" in the "dialog[open]" "css_element"
    Then I should not see "Prvni student"

  Scenario: Tenant manager may confirm and unlock tenant member accounts
    Given the following config values are set as admin:
      | lockoutthreshold | 3 |
    And the following "users" exist:
      | username | firstname | lastname | email               | tenant | confirmed |
      | member1  | Neovereny | Clen     | member1@example.com | TEN1   | 0         |
      | member2  | Zamceny   | Clen     | member2@example.com | TEN1   | 1         |
    And the following "user preferences" exist:
      | user    | preference    | value      |
      | member2 | login_lockout | 9999999999 |
    And I log in as "manager1"
    And I am on the "TEN1" "tool_mutenancy > Tenant users" page

    When I click on "Actions" "link" in the "Neovereny Clen" "table_row"
    And I click on "Resend confirmation email" "link" in the "Neovereny Clen" "table_row"
    And I click on "Resend confirmation email" "button" in the "dialog[open]" "css_element"
    Then I should see "Neovereny Clen"
    And "dialog[open]" "css_element" should not exist

    When I click on "Actions" "link" in the "Neovereny Clen" "table_row"
    And I click on "Confirm account" "link" in the "Neovereny Clen" "table_row"
    And I click on "Confirm account" "button" in the "dialog[open]" "css_element"
    And I click on "Actions" "link" in the "Neovereny Clen" "table_row"
    Then I should not see "Confirm account" in the "Neovereny Clen" "table_row"
    And I should not see "Resend confirmation email" in the "Neovereny Clen" "table_row"
    And I press the escape key

    When I click on "Actions" "link" in the "Zamceny Clen" "table_row"
    And I click on "Unlock account" "link" in the "Zamceny Clen" "table_row"
    And I click on "Unlock account" "button" in the "dialog[open]" "css_element"
    And I click on "Actions" "link" in the "Zamceny Clen" "table_row"
    Then I should not see "Unlock account" in the "Zamceny Clen" "table_row"

  Scenario: Global users may be associated with tenant and disassociated
    Given the following "cohorts" exist:
      | name     | idnumber |
      | Cohort 1 | COH1     |
    And the following "tool_mutenancy > tenants" exist:
      | name     | idnumber | assoccohort |
      | Tenant 3 | TEN3     | COH1        |
    And the following "users" exist:
      | username | firstname | lastname | email               | tenant |
      | global1  | Globalni  | Prvni    | global1@example.com |        |
      | global2  | Globalni  | Druhy    | global2@example.com |        |
      | global3  | Globalni  | Treti    | global3@example.com |        |
      | member2  | Cizi      | Clen     | member2@example.com | TEN2   |
      | member3  | Vlastni   | Clen     | member3@example.com | TEN3   |
    And the following "cohort members" exist:
      | user    | cohort |
      | global3 | COH1   |
    And I log in as "admin"

    # Tenants without associated users cohort cannot have associated users.
    When I am on the "TEN1" "tool_mutenancy > Tenant users" page
    Then "Associate users" "button" should not exist

    When I am on the "TEN3" "tool_mutenancy > Tenant users" page
    Then the following should exist in the "reportbuilder-table" table:
      | First name     | Email address       | Tenant member |
      | Vlastni Clen   | member3@example.com | Yes           |
      | Globalni Treti | global3@example.com | No            |
    And I should not see "Globalni Prvni"

    # Only global users that are not associated yet are candidates.
    When I press "Associate users"
    Then I should see "Cohort 1" in the "dialog[open]" "css_element"
    And I type "Clen" into the "userids" muform search field
    And I should see "No results" in the "dialog.tool_mulib-picker-panel" "css_element"
    And I type "Treti" into the "userids" muform search field
    And I should see "No results" in the "dialog.tool_mulib-picker-panel" "css_element"
    And I set the following muform fields in the "dialog[open]" "css_element":
      | Users | Globalni Prvni, global2@example.com |
    And I click on "Associate users" "button" in the "dialog[open]" "css_element"
    Then the following should exist in the "reportbuilder-table" table:
      | First name     | Email address       | Tenant member |
      | Vlastni Clen   | member3@example.com | Yes           |
      | Globalni Prvni | global1@example.com | No            |
      | Globalni Druhy | global2@example.com | No            |
      | Globalni Treti | global3@example.com | No            |
    And I should not see "Cizi Clen"

    When I click on "Actions" "link" in the "Globalni Druhy" "table_row"
    And I click on "Disassociate user" "link" in the "Globalni Druhy" "table_row"
    Then I should see "Globalni Druhy" in the "dialog[open]" "css_element"
    And I click on "Disassociate user" "button" in the "dialog[open]" "css_element"
    Then I should not see "Globalni Druhy"
    And I should see "Globalni Prvni"
    And I should see "Globalni Treti"
