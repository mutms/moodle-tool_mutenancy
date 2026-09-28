@tool @tool_mutenancy @MuTMS @javascript
Feature: Tenant switching
  Background:
    Given unnecessary Admin bookmarks block gets deleted
    And the following "cohorts" exist:
      | name     | idnumber  |
      | Cohort 1 | cohort1   |
      | Cohort 2 | cohort2   |
      | Cohort 4 | cohort4   |
    And the following "tool_mutenancy > tenants" exist:
      | name     | idnumber | sitefullname     | siteshortname | archived | assoccohort |
      | Tenant 1 | TEN1     | Tent Site full 1 | TSS1          | 0        | cohort1     |
      | Tenant 2 | TEN2     | Tent Site full 2 | TSS2          | 0        | cohort2     |
      | Tenant 3 | TEN3     | Tent Site full 3 | TSS3          | 0        |             |
      | Tenant 4 | TEN4     | Tent Site full 4 | TSS4          | 1        | cohort4     |

  Scenario: Admin may switch to any active tenant
    Given I log in as "admin"
    And I should see "Acceptance test site" in the ".navbar" "css_element"

    When I click on "Switch tenant" "link" in the ".navbar" "css_element"
    And I click on "Switch tenant" "button" in the "dialog[open]" "css_element"
    And I should see "Change required" in the "dialog[open]" "css_element"
    And I set the following muform fields in the "dialog[open]" "css_element":
      | Tenant      | Tenant 1         |
    And I click on "Switch tenant" "button" in the "dialog[open]" "css_element"
    Then I should see "TSS1" in the ".navbar" "css_element"

    When I click on "Switch tenant" "link" in the ".navbar" "css_element"
    And I click on "Switch tenant" "button" in the "dialog[open]" "css_element"
    And I should see "Change required" in the "dialog[open]" "css_element"
    And I set the following muform fields in the "dialog[open]" "css_element":
      | Tenant      | Tenant 2         |
    And I click on "Switch tenant" "button" in the "dialog[open]" "css_element"
    Then I should see "TSS2" in the ".navbar" "css_element"

    When I click on "Switch tenant" "link" in the ".navbar" "css_element"
    And I set the following muform fields in the "dialog[open]" "css_element":
      | Tenant      | No tenant        |
    And I click on "Switch tenant" "button" in the "dialog[open]" "css_element"
    Then I should see "Acceptance test site" in the ".navbar" "css_element"

  Scenario: Tenant switcher may switch to tenants
    Given the following "roles" exist:
      | name            | shortname |
      | Tenant switcher | tswitcher |
    And the following "permission overrides" exist:
      | capability                           | permission | role      | contextlevel | reference |
      | tool/mutenancy:switch                | Allow      | tswitcher | System       |           |
    And the following "users" exist:
      | username  | firstname | lastname  | email                 | tenant |
      | tswitcher | Tenant    | Switcher  | tswitcher@example.com |        |
    And the following "role assigns" exist:
      | user      | role          | contextlevel | reference |
      | tswitcher | tswitcher     | Tenant       | TEN1      |
      | tswitcher | tswitcher     | Tenant       | TEN2      |
      | tswitcher | tswitcher     | Tenant       | TEN4      |
    And I log in as "tswitcher"
    And I should see "Acceptance test site" in the ".navbar" "css_element"

    When I click on "Switch tenant" "link" in the ".navbar" "css_element"
    And I set the following muform fields in the "dialog[open]" "css_element":
      | Tenant      | Tenant 1         |
    And I click on "Switch tenant" "button" in the "dialog[open]" "css_element"
    Then I should see "TSS1" in the ".navbar" "css_element"

    When I click on "Switch tenant" "link" in the ".navbar" "css_element"
    And I set the following muform fields in the "dialog[open]" "css_element":
      | Tenant      | Tenant 2         |
    And I click on "Switch tenant" "button" in the "dialog[open]" "css_element"
    Then I should see "TSS2" in the ".navbar" "css_element"

    When I click on "Switch tenant" "link" in the ".navbar" "css_element"
    And I set the following muform fields in the "dialog[open]" "css_element":
      | Tenant      | No tenant        |
    And I click on "Switch tenant" "button" in the "dialog[open]" "css_element"
    Then I should see "Acceptance test site" in the ".navbar" "css_element"

  Scenario: Associated users may switch to tenants
    And the following "users" exist:
      | username  | firstname | lastname  | email                 | tenant |
      | tswitcher | Tenant    | Switcher  | tswitcher@example.com |        |
    And the following "cohort members" exist:
      | user      | cohort  |
      | tswitcher | cohort1 |
      | tswitcher | cohort2 |
      | tswitcher | cohort4 |
    And I log in as "tswitcher"
    And I should see "Acceptance test site" in the ".navbar" "css_element"

    When I click on "Switch tenant" "link" in the ".navbar" "css_element"
    And I set the following muform fields in the "dialog[open]" "css_element":
      | Tenant      | Tenant 1         |
    And I click on "Switch tenant" "button" in the "dialog[open]" "css_element"
    Then I should see "TSS1" in the ".navbar" "css_element"

    When I click on "Switch tenant" "link" in the ".navbar" "css_element"
    And I set the following muform fields in the "dialog[open]" "css_element":
      | Tenant      | Tenant 2         |
    And I click on "Switch tenant" "button" in the "dialog[open]" "css_element"
    Then I should see "TSS2" in the ".navbar" "css_element"

    When I click on "Switch tenant" "link" in the ".navbar" "css_element"
    And I set the following muform fields in the "dialog[open]" "css_element":
      | Tenant      | No tenant        |
    And I click on "Switch tenant" "button" in the "dialog[open]" "css_element"
    Then I should see "Acceptance test site" in the ".navbar" "css_element"

  Scenario: Associated users may switch to custom tenant entity names
    And the following "users" exist:
      | username  | firstname | lastname  | email                 | tenant |
      | tswitcher | Tenant    | Switcher  | tswitcher@example.com |        |
    And the following "cohort members" exist:
      | user      | cohort  |
      | tswitcher | cohort1 |
      | tswitcher | cohort2 |
      | tswitcher | cohort4 |
    And the following config values are set as admin:
      | tenantentity   | Faculty   | tool_mutenancy |
      | tenantentities | Faculties | tool_mutenancy |
    And I log in as "tswitcher"
    And I should see "Acceptance test site" in the ".navbar" "css_element"

    When I click on "Switch Faculty" "link" in the ".navbar" "css_element"
    And I set the following muform fields in the "dialog[open]" "css_element":
      | Faculty     | Tenant 1         |
    And I click on "Switch Faculty" "button" in the "dialog[open]" "css_element"
    Then I should see "TSS1" in the ".navbar" "css_element"

    When I click on "Switch Faculty" "link" in the ".navbar" "css_element"
    And I set the following muform fields in the "dialog[open]" "css_element":
      | Faculty      | Tenant 2         |
    And I click on "Switch Faculty" "button" in the "dialog[open]" "css_element"
    Then I should see "TSS2" in the ".navbar" "css_element"

    When I click on "Switch Faculty" "link" in the ".navbar" "css_element"
    And I set the following muform fields in the "dialog[open]" "css_element":
      | Faculty      | No Faculty       |
    And I click on "Switch Faculty" "button" in the "dialog[open]" "css_element"
    Then I should see "Acceptance test site" in the ".navbar" "css_element"

  Scenario: Admin picks from both ends of a long tenant list
    Given the following "tool_mutenancy > tenants" exist:
      | name       | idnumber  | sitefullname     | siteshortname | archived | assoccohort |
      | Faculty 01 | FAC01     | Faculty site 01   | FS01          | 0        |             |
      | Faculty 02 | FAC02     | Faculty site 02   | FS02          | 0        |             |
      | Faculty 03 | FAC03     | Faculty site 03   | FS03          | 0        |             |
      | Faculty 04 | FAC04     | Faculty site 04   | FS04          | 0        |             |
      | Faculty 05 | FAC05     | Faculty site 05   | FS05          | 0        |             |
      | Faculty 06 | FAC06     | Faculty site 06   | FS06          | 0        |             |
      | Faculty 07 | FAC07     | Faculty site 07   | FS07          | 0        |             |
      | Faculty 08 | FAC08     | Faculty site 08   | FS08          | 0        |             |
      | Faculty 09 | FAC09     | Faculty site 09   | FS09          | 0        |             |
      | Faculty 10 | FAC10     | Faculty site 10   | FS10          | 0        |             |
      | Faculty 11 | FAC11     | Faculty site 11   | FS11          | 0        |             |
      | Faculty 12 | FAC12     | Faculty site 12   | FS12          | 0        |             |
      | Faculty 13 | FAC13     | Faculty site 13   | FS13          | 0        |             |
      | Faculty 14 | FAC14     | Faculty site 14   | FS14          | 0        |             |
      | Faculty 15 | FAC15     | Faculty site 15   | FS15          | 0        |             |
      | Faculty 16 | FAC16     | Faculty site 16   | FS16          | 0        |             |
      | Faculty 17 | FAC17     | Faculty site 17   | FS17          | 0        |             |
      | Faculty 18 | FAC18     | Faculty site 18   | FS18          | 0        |             |
      | Faculty 19 | FAC19     | Faculty site 19   | FS19          | 0        |             |
      | Faculty 20 | FAC20     | Faculty site 20   | FS20          | 0        |             |
      | Faculty 21 | FAC21     | Faculty site 21   | FS21          | 0        |             |
      | Faculty 22 | FAC22     | Faculty site 22   | FS22          | 0        |             |
      | Faculty 23 | FAC23     | Faculty site 23   | FS23          | 0        |             |
      | Faculty 24 | FAC24     | Faculty site 24   | FS24          | 0        |             |
      | Faculty 25 | FAC25     | Faculty site 25   | FS25          | 0        |             |
      | Faculty 26 | FAC26     | Faculty site 26   | FS26          | 0        |             |
      | Faculty 27 | FAC27     | Faculty site 27   | FS27          | 0        |             |
      | Faculty 28 | FAC28     | Faculty site 28   | FS28          | 0        |             |
      | Faculty 29 | FAC29     | Faculty site 29   | FS29          | 0        |             |
      | Faculty 30 | FAC30     | Faculty site 30   | FS30          | 0        |             |
      | Faculty 31 | FAC31     | Faculty site 31   | FS31          | 0        |             |
      | Faculty 32 | FAC32     | Faculty site 32   | FS32          | 0        |             |
      | Faculty 33 | FAC33     | Faculty site 33   | FS33          | 0        |             |
      | Faculty 34 | FAC34     | Faculty site 34   | FS34          | 0        |             |
      | Faculty 35 | FAC35     | Faculty site 35   | FS35          | 0        |             |
      | Faculty 36 | FAC36     | Faculty site 36   | FS36          | 0        |             |
      | Faculty 37 | FAC37     | Faculty site 37   | FS37          | 0        |             |
      | Faculty 38 | FAC38     | Faculty site 38   | FS38          | 0        |             |
      | Faculty 39 | FAC39     | Faculty site 39   | FS39          | 0        |             |
      | Faculty 40 | FAC40     | Faculty site 40   | FS40          | 0        |             |
      | Faculty 41 | FAC41     | Faculty site 41   | FS41          | 0        |             |
      | Faculty 42 | FAC42     | Faculty site 42   | FS42          | 0        |             |
      | Faculty 43 | FAC43     | Faculty site 43   | FS43          | 0        |             |
      | Faculty 44 | FAC44     | Faculty site 44   | FS44          | 0        |             |
      | Faculty 45 | FAC45     | Faculty site 45   | FS45          | 0        |             |
      | Extra 01   | EXT01     | Extra site 01     | ES01          | 0        |             |
      | Extra 02   | EXT02     | Extra site 02     | ES02          | 0        |             |
      | Extra 03   | EXT03     | Extra site 03     | ES03          | 0        |             |
      | Extra 04   | EXT04     | Extra site 04     | ES04          | 0        |             |
      | Extra 05   | EXT05     | Extra site 05     | ES05          | 0        |             |
      | Extra 06   | EXT06     | Extra site 06     | ES06          | 0        |             |
      | Extra 07   | EXT07     | Extra site 07     | ES07          | 0        |             |
    And I log in as "admin"
    When I click on "Switch tenant" "link" in the ".navbar" "css_element"
    And I type "Faculty" into the "tenantid" muform search field
    Then I should see "Faculty 45" in the "dialog[open] [role='listbox']" "css_element"
    And the open muform list should be fully visible
    When I click on "//dialog[@open]//li[@data-muform-autocomplete-option][normalize-space(.)='Faculty 45']" "xpath_element"
    And I click on "Switch tenant" "button" in the "dialog[open]" "css_element"
    Then I should see "FS45" in the ".navbar" "css_element"

    When I click on "Switch tenant" "link" in the ".navbar" "css_element"
    And I type "Faculty" into the "tenantid" muform search field
    And I click on "//dialog[@open]//li[@data-muform-autocomplete-option][normalize-space(.)='Faculty 01']" "xpath_element"
    And I click on "Switch tenant" "button" in the "dialog[open]" "css_element"
    Then I should see "FS01" in the ".navbar" "css_element"

    # More matches than the list shows, the user is asked to keep typing.
    When I click on "Switch tenant" "link" in the ".navbar" "css_element"
    And I type "t" into the "tenantid" muform search field
    Then I should see "Too many results" in the "dialog[open] [role='listbox']" "css_element"
    When I set the following muform fields in the "dialog[open]" "css_element":
      | Tenant | Extra 07 |
    And I click on "Switch tenant" "button" in the "dialog[open]" "css_element"
    Then I should see "ES07" in the ".navbar" "css_element"

