# Changelog

## mu-5.1.0-03

Release date: xx/11/2025

* Tenant switching has been simplified: associated users and tenant managers can now switch tenants by default. Internally, the tool/mutenancy:switch capability is now used in the tenant context instead of the system context, and no longer requires the tool/mutenancy:view capability. Existing tenant manager roles need to be updated manually to include the switch permission.

## mu-5.1.0-02

Release date: 08/11/2025

* No changes.

## mu-5.1.0-01

Release date: 06/10/2025

* Added support for Moodle 5.1.0 release.
