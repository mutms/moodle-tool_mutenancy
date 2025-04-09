# Multi-tenancy Plugin for Moodle™

## What is Multi-tenancy?

Multi-tenancy makes it possible to handle a variety of use cases, such as:

1. **Corporate Training**  
   Create separate tenants for departments, regions, or subsidiaries. This setup allows for tailored training programs, while management stays centralized.  
   *Example:* A multinational corporation can set up tenants for Europe, Asia, and the Americas, each with its own specific training content while sharing global compliance courses.

2. **Client Training**  
   Offer customized learning environments for each client with unique permissions and branding.  
   *Example:* A consultancy firm provides training portals for different clients, ensuring each client sees their unique branding and only their specific training materials.

3. **Partner and Vendor Training**  
   Provide dedicated tenants for external partners or vendors, giving them secure access to their training materials without interfering with internal operations.  
   *Example:* A manufacturing company trains its distributors and suppliers through separate partner-specific tenants, ensuring they only access relevant information.

4. **Franchise Management**  
   Give franchisees their own tenants to run localized training programs while keeping things consistent across the organization.  
   *Example:* A restaurant franchise offers tenants for franchise owners to manage staff training tailored to their region while enforcing corporate standards.

5. **Shared Resources**  
   Use shared spaces between tenants to distribute common resources, like compliance courses or company announcements.  
   *Example:* A global healthcare company shares mandatory compliance training across tenants while allowing each region to manage its additional training needs.

---

## Drawbacks of Multi-tenancy

1. **Later Splitting of Tenants is Hard**  
   Breaking up one multi-tenant site into separate independent sites can be a very complex process, requiring site cloning and a lot of data cleanup.

2. **Shared Infrastructure Issues**  
   If one tenant uses too many resources, it can slow things down for everyone else.

3. **Limited Customization**  
   Tenants can make some branding and setting changes, but they're more limited compared to standalone Moodle™ instances.

4. **Security Concerns**  
   Even with isolation measures, shared infrastructure creates the risk of vulnerabilities affecting multiple tenants.

5. **Plugin and Feature Restrictions**  
   Some standard functionalities might not work as expected, and third-party plugins like enrolment or authentication tools may need significant adjustments.

---

## Installation steps

- Apply the multi-tenancy patch to Moodle™ codebase: [GitHub Repository](https://github.com/mutms/moodle/tree/patch/mutenancy/MOODLE_405_STABLE)
- Install the `tool_mulib` plugin: [GitHub Plugin Page](https://github.com/mutms/mutms-tool_mulib)
- Install the `tool_mutenancy` plugin: [GitHub Plugin Page](https://github.com/mutms/mutms-tool_mutenancy)
- Install or upgrade the site
- Login as site administrator
- Activate multi-tenancy in: Site administration / General / Tenants
- Start creating tenants and member accounts

---

## Known Limitations

- Only the latest Moodle™ 4.5.x releases to be supported in 2025.
- Supported databases: PostgreSQL, MySQL, or MariaDB
- Tenant-specific appearance settings may not display correctly in the Moodle Mobile App.

---

## Support

- Report bugs: [GitHub Issues](https://github.com/mutms/mutms-tool_mutenancy/issues)
- Report security vulnerabilities: [security@mutms.org](mailto:security@mutms.org?subject=Security%20bug%20report)

_Paid support options will be available starting in 2026._

---

## Technical Design Details

Tenants are added as a new context level between system and user contexts. This setup enables permissions to be delegated using standard roles and capabilities. Separate top-level categories are used to ensure tenant content is kept isolated.

### User Accounts
- Accounts can either be global (available across all tenants) or tied to a specific tenant.

### Tenant Categories
- Each tenant is assigned its own top-level category to organize its courses.

### Tenant Managers
- Tenant managers have the authority to manage user accounts and courses within their tenant.
- They are automatically assigned a special role in tenant contexts and their top-level categories.

### Tenant Separation
- Permission restrictions ensure that members of one tenant can’t access courses or categories belonging to another tenant.

### Tenant Cohorts
- Each tenant gets a system-wide cohort for its members when the tenant is created.

### Tenant Associated Users Cohort
- During enrolment or role assignment, user options are limited to those connected to the relevant tenant.
- Associated users are visible in tenant courses but do not receive extra capabilities in tenant contexts.
