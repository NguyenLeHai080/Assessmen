=== Mini Assessment ===
Contributors: assessment-candidate
Requires at least: 6.4
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPLv2 or later

Headless assessment REST API and bundled React SPA with custom tables, migration,
capabilities, validation, pagination and application-level data integrity.

== Installation ==

1. Upload the plugin directory or ZIP through WordPress Admin.
2. Activate Mini Assessment.
3. Access routes under /wp-json/assessment/v1.
4. Create a WordPress page containing [mini_assessment_app] to render the React SPA.

== Data retention ==

Deactivation keeps data. Uninstall also keeps data unless the option
mini_assessment_delete_data_on_uninstall is explicitly set to 1.
