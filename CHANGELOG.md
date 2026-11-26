# Change log

Plugin versioning is derived from Moodle releases, it does not comply with the semantic versioning standard.

The format of this change log follows the advice given at [Keep a CHANGELOG](https://keepachangelog.com).

## [Unreleased]

### Added

- Czech translation and AI-produced previews of German, French, Italian, Portuguese and Spanish translations
- tenant cohorts are described and cohort usage in tenants is listed in advanced cohort management
- Cohort management of tenant category in Tenant management primary menu

### Changed

- tenant managers and associated users are selected in a users picker panel with identity columns and paging instead of an autocomplete
- migration to new forms library
- tenant switching searches tenants instead of listing all of them
- cohort names link to cohort management, the advanced cohort management page is used when installed
- tenants and tenant users use the Advanced report builder: general search, filter chips, saved filters,
  configurable columns and filters
- tool_mucohort plugin is required, standard cohort pages are replaced by advanced cohort management
  when multi-tenancy is activated and during upgrade of sites with active multi-tenancy

### Fixed

- _tool_mutenancy_allocate_user_ web service received user id and tenant id swapped
- wrong page and return URLs of authentication and appearance forms
- theme caches were not reset when appearance overrides were removed
