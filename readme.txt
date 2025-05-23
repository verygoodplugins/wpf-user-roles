=== WP Fusion - User Roles Addon ===
Contributors: verygoodplugins
Tags: wp fusion, roles
Requires at least: 4.0
Tested up to: 6.8.1
Stable tag: 1.2.2

Allows linking a CRM tag with a WordPress user role to automatically set roles when tags are modified, as well as applying tags based on user role changes.

== Description ==

Allows linking a CRM tag with a WordPress user role to automatically set roles when tags are modified, as well as applying tags based on user role changes.

== Changelog ==

= 1.2.2 - 4/29/2025 =
* Added multi-role support for User Role Editor
* Fixed auto-enrollments in the logs showing the tag ID instead of tag name with CRMs that use tag IDs
* Fixed role changes not applying tags if the user's tags had already been modified in the same request

= 1.2.1 - 10/3/2022 =
* Fixed tags not being applied to new users when the role was set before WP Fusion had synced the contact to the CRM

= 1.2.0 - 8/30/2022 =
* Refactored and released as an official WP Fusion addon

= 1.1.2 - 10/25/2021 =
* Updated to run on `set_user_role` and `user_register`

= 1.1.1 - 10/8/2020 =
* Updated for WP Fusion 3.35 compatibility

= 1.1.0 - 8/30/2020 =
* Added "Apply Tags" setting
* Improved - When a role is removed, the linked tag will also be removed

= 1.0.2 - 4/28/2020 =
* Fixed error when settings were empty

= 1.0.1 - 11/10/2019 =
* Updated so roles can also be removed

= 1.0 - 9/20/2019 =
* Initial release