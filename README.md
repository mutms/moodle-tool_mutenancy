# Multi-tenancy plugin for Moodle™ LMS

[![MDL Shield](https://img.shields.io/endpoint?url=https%3A%2F%2Fmdlshield.com%2Fapi%2Fbadge%2Ftool_mutenancy)](https://mdlshield.com/plugins/tool_mutenancy) ![Moodle Plugin CI](https://github.com/mutms/moodle-tool_mutenancy/actions/workflows/moodle-ci.yml/badge.svg) [![camp](https://camp-registry.org/badge/tool_mutenancy.svg)](https://camp-registry.org/plugin/tool_mutenancy.html)

Introduces multi-tenancy to standard Moodle™ LMS installations — fully open source under GPL 3.0,
with no restrictions on commercial use. Part of the [MuTMS suite](https://github.com/mutms).

Multi-tenancy allows a single Moodle instance to be partitioned into isolated tenants, each with
their own users, roles, courses, appearance, and settings — making it possible to serve multiple
independent business units or client organisations from one installation.

This plugin requires a small core patch to Moodle™ LMS. The patch and all MuTMS plugins are
included in the [MuTMS distribution](https://github.com/mutms/mutms) for easy deployment.

## Features

* Tenant management — create, configure, and delete tenants
* User isolation — users are scoped to their tenant and cannot access other tenants
* Tenant-specific roles and permissions
* Tenant-specific appearance and branding
* Tenant-specific catalogue sections with the [Catalogue plugin](https://github.com/mutms/moodle-tool_mucatalog)
* Non-intrusive design — standard Moodle features and workflows remain fully functional
* Fully uninstallable — removing the plugin does not affect the rest of your Moodle installation

## Guest access

Tenants are fully separated only for logged in users. Anonymous visitors and guests are not
members of any tenant, they can open the login page of any tenant and see what that tenant
shows to guests, such as catalogue sections visible to guests and courses with guest access
when "Allow guest access to tenants" is enabled. Keep guest access off where the existence or
content of a tenant must stay private.

## Requirements

> This plugin is included in the [MuTMS distribution](https://github.com/mutms/mutms) —
> no manual installation needed if you use the distribution.

For manual installation:

* Moodle™ LMS 5.3
* [MuTMS core patch](https://github.com/mutms/patches/tree/patch/mutenancy/MOODLE_503_DEV)
* [Additional tools library plugin](https://github.com/mutms/moodle-tool_mulib)

## Documentation

See the [online documentation](https://docs.mutms.org/mutenancy/) for installation
instructions and configuration reference.

## AI disclosure

Parts of this plugin were written with the help of Claude (Anthropic). A human
maintainer reviewed, corrected and accepted everything before it was committed.
The design decisions and the final code are the maintainer's own.

---

> MuTMS is an independent open-source project, not affiliated with Moodle HQ.
