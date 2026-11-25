# PubPla Gatekeeper

Contributors: tarosky, Takahashi_Fumiki  
Tags: login security, inactive users, brute force, pwned passwords, basic auth  
Requires at least: 6.6  
Tested up to: 7.1  
Requires PHP: 7.4  
Stable Tag: nightly  
License: GPLv2 or later  
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Keep privileged accounts accountable: tiered login protection, dormant account isolation, and breached-password checks for WordPress.

## Description

PubPla Gatekeeper decides two things: who gets in, and who should still have the keys.

Most login security plugins treat every account the same. PubPla Gatekeeper scales authentication strength and account lifecycle to what each user can actually do. An account that can install plugins deserves stricter treatment than an account that can only write its own posts.

<!-- only:github/ -->
> **Status:** This plugin is under active development. Only the Basic authentication compatibility feature is available at this time. The other features below are on the roadmap. See [the epic issue](https://github.com/tarosky/pubpla-gatekeeper/issues/10) for progress.
<!-- /only:github -->

### Capability tiers

Users are classified by their effective capabilities (not role names), so custom roles and hardened setups such as `DISALLOW_FILE_MODS` are handled correctly.

- **T4 Code execution**: can install, upload, or edit plugins and themes, or update core. Super admins on multisite.
- **T3 Site settings**: can manage options or users.
- **T2 Others' content**: can edit others' posts or post unfiltered HTML.
- **T1 Own content**: can edit their own posts.
- **T0 Read only**: not affected.

Each feature uses these tiers to decide how strictly to act.

### Features

**Account-based login lock** (planned)

- Counts failed logins per account, not per IP address, so distributed credential-stuffing attacks are covered.
- While locked, every password attempt fails, even a correct one. Attackers learn nothing about whether a password was right.
- The legitimate user receives a one-time unlock link by email. The lock stays in place for everyone else.
- Responses are identical for existing and non-existing usernames, so usernames cannot be enumerated.

**Dormant account isolation** (planned)

- Privileged accounts with no recent activity are locked in advance and can sign in only through an emailed one-time link.
- If they stay dormant, they are demoted to no role. The previous role is saved and can be restored with one click.
- Sessions and application passwords are revoked on demotion.
- The last administrator (or super admin) is never demoted.
- Reports are available for periodic access reviews.

**Breached-password checks** (planned)

- Checks passwords against the [Have I Been Pwned](https://haveibeenpwned.com/Passwords) Pwned Passwords database using k-anonymity. Neither the password nor its full hash leaves your server.
- Privileged users with a breached password must change it before entering the admin screen.
- Breached passwords cannot be set on profile updates, password resets, or registration.
- If the API is unreachable, the check is skipped and login is never blocked.

**Two-factor enforcement** (planned)

- Requires two-factor authentication for users at or above a configurable tier, using the [Two Factor](https://wordpress.org/plugins/two-factor/) plugin.

**Application password restrictions** (planned)

- Allows or denies application passwords by tier. Unused application passwords are revoked automatically.

**Basic authentication compatibility** (available)

- Password-protected posts work even when `wp-login.php` is protected by Basic authentication. The password form is submitted to a REST API endpoint instead of `wp-login.php`, so you do not need to make any exception in your Basic authentication settings.

### Designed for teams and enterprises

- Protection is enforced in the authentication process itself (`authenticate`), not on a particular screen or URL, so custom login forms are covered too.
- State changes fire action hooks so you can forward them to your own audit log.
- Each feature can be turned on or off independently.
- WP-CLI commands and multisite are supported.
- Works alongside other security plugins and Basic authentication.

## Installation

1. Upload the `pubpla-gatekeeper` folder to the `/wp-content/plugins/` directory, or install the plugin through the WordPress plugins screen.
2. Activate the plugin through the "Plugins" screen in WordPress.

<!-- only:github/ -->
For development setup and testing, see [Development.md](Development.md) and [TESTING.md](TESTING.md).
<!-- /only:github -->

## Frequently Asked Questions

### Does this plugin send passwords to external services?

No. The breached-password check sends only the first five characters of the SHA-1 hash of a password to the Have I Been Pwned API. The comparison is done on your server.

### Will users be locked out if an external service is down?

No. External checks fail open: if the API cannot be reached, the check is skipped.

### Does this plugin implement two-factor authentication?

No. Two-factor authentication itself is provided by the Two Factor plugin. PubPla Gatekeeper only enforces it for privileged users.

### I protect `wp-login.php` with Basic authentication. Can visitors still view password-protected posts?

Yes. Password-protected posts are handled through the REST API, so visitors never have to pass Basic authentication.

## Changelog

### 0.1.0

* Renamed from Taro Bypass Password Protection to PubPla Gatekeeper.
