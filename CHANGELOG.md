# Change log

Plugin versioning is derived from Moodle releases, it does not comply with the semantic versioning standard.

The format of this change log follows the advice given at [Keep a CHANGELOG](https://keepachangelog.com).

## [Unreleased]

### Changed

- migration to new forms library
- tenant switching searches tenants instead of listing all of them

### Fixed

- _tool_mutenancy_allocate_user_ web service received user id and tenant id swapped
- wrong page and return URLs of authentication and appearance forms- theme caches were not reset when appearance overrides were removed
