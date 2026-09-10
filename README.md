[![Aktuelle Version](https://img.shields.io/github/package-json/v/rrze-webteam/rrze-settings/main?label=Version)](https://github.com/RRZE-Webteam/rrze-settings)
[![Release Version](https://img.shields.io/github/v/release/rrze-webteam/rrze-settings?label=Release+Version)](https://github.com/rrze-webteam/rrze-settings/releases/)
[![GitHub License](https://img.shields.io/github/license/rrze-webteam/rrze-settings)](https://github.com/RRZE-Webteam/rrze-settings)
[![GitHub issues](https://img.shields.io/github/issues/RRZE-Webteam/rrze-settings)](https://github.com/RRZE-Webteam/rrze-settings/issues)


# RRZE Settings

RRZE Settings is a WordPress plugin that provides general settings and enhancements for multisite installations.

## Features

-   Centralized settings for WordPress multisite installations.
-   Utility methods for use by other plugins.
-   Enhancements tailored for specific workflows.

## Installation

1. Clone the repository:

    ```bash
    git clone https://github.com/RRZE-Webteam/rrze-settings.git

    ```
2. Place the plugin folder in your WordPress wp-content/plugins directory.
3. Activate the plugin through the WordPress network admin interface.

## Usage

Methods Available for Other Plugins

The following methods can be used by other plugins:

-   `\RRZE\Settings\Helper::userCanViewDebugLog()`: Check if the user can see the debug log (if available).
-   `\RRZE\Settings\Helper::getBiteApiKey()`: Get the BITE API key.
-   `\RRZE\Settings\Helper::getDipEduApiKey()`: Get the DIP Edu API key.

## Public REST access and private sites

Under **Network Admin > CMS > REST API**, registered public endpoints require
explicit network approval for anonymous requests. This approval is mandatory even
when the general REST restriction is off or an IP/namespace exception applies.
Authenticated access retains its existing behavior.

Plugins declare literal routes using `rrze_rest_api_public_endpoints`:

```php
add_filter('rrze_rest_api_public_endpoints', static function ($endpoints) {
    $endpoints['example-download'] = [
        'label' => 'Signed downloads',
        'route' => '/example/v1/download',
        'methods' => ['GET', 'HEAD'],
        'description' => 'Requires a valid signed download URL.',
    ];
    return $endpoints;
});
```

IDs must be stable and compatible with `sanitize_key`; `network-route-` is a
reserved prefix. Register the declaration before `wp_loaded`, independently of
`rest_api_init`. Declarations discovered on individual subsites are remembered
for the network settings screen. Runtime exceptions require the provider to be
active on the current site; cached declarations alone do not grant access.

In **Settings > Reading > Public REST API Routes**, RRZE Private Site offers the
network-approved live endpoints and entries from **Allowed namespaces and routes**
as optional local checkboxes. No local exception is selected automatically. Entries
from the namespace list include subroutes at path boundaries (`/wp/v2/pages`
includes `/wp/v2/pages/42`, but not `/wp/v2/pages-other`). Their IDs depend on the
path, so editing a path requires a new local selection. Registered endpoints always
need their own checkbox, even inside a selected namespace. All private-site public
exceptions are limited to GET and HEAD and retain endpoint permission checks.

The `rrze_rest_api_approved_public_endpoints` filter supplies the local choices.
The `rrze_rest_api_public_request_allowed` filter receives `false`, the selected
local IDs and the `WP_REST_Request`, and revalidates them against network policy.
Removing a network approval disables the local exception on subsequent requests.
Deploy the matching Settings and Private Site versions together: without the
network policy filters, Private Site grants no public REST exceptions. Other
access-control plugins can still deny a request.

For public FAUbox downloads by visitors without existing site access, select
`rrze-faubox-download` in both network and private-site settings.
The signed URL validation in FAUbox still applies. If the network checkbox is
missing for a subsite-only plugin, visit that subsite once and reload network settings.

General Private Site access (membership, IP, password or SSO) already passes its
REST gate; a local public-route checkbox is not additionally required for these
visitors. The network REST policy and endpoint permissions still apply. The
GET/HEAD limit applies to public exceptions, not to existing site authorization.

An AC permission for an HTML page does not by itself authorize a REST request.
Also, cookie-only WordPress sessions without a REST nonce are treated as anonymous
by WordPress during REST authentication. FAUbox's signed download URLs have no
REST nonce: use the explicit public endpoint exception for this case. Do not
restore cookie privileges or convert an AC page permission into a blanket REST
exception. A plugin's public permission callback cannot override a prior denial.

### Client IP addresses and reverse proxies

Settings and Private Site use `REMOTE_ADDR` by default and ignore client-supplied
forwarding headers. Visitor IP allowlists no longer establish proxy trust. There
are no automatic trusted proxies or reverse-DNS-based trust decisions.

If PHP receives the reverse proxy's address as `REMOTE_ADDR`, configure the
actual infrastructure proxy IPs/CIDRs using the shared `rrze_trusted_proxies`
filter, for example in a network-managed MU plugin. Replace the documentation
addresses below with the actual proxy addresses; do not use visitor ranges:

```php
add_filter('rrze_trusted_proxies', static function ($proxies) {
    return ['192.0.2.10', '2001:db8:1234::/64'];
});
```

Only trusted immediate peers can supply `X-Forwarded-For`. The resolver walks
right to left through trusted proxies and uses the first untrusted address as
the client, ignoring any earlier client-controlled prefix. Proxies must append
their immediate peer or overwrite untrusted incoming headers. If the trusted
chain has no valid client address, IP-based access is denied, including when a
proxy's own address is in a visitor allowlist. IPv4/IPv6 single addresses and CIDR
masks are supported; non-IP entries are ignored. If the web server already sets
`REMOTE_ADDR` to the verified client IP, leave the proxy list empty.

Review this configuration before deploying behind a reverse proxy. The plugins
do not migrate visitor allowlists into trusted proxies or modify server settings.

Run the isolated regression tests from `wp-content/plugins`:

```sh
php rrze-settings/tests/client-ip.php
php rrze-settings/tests/rest-access-integration.php
```

The access integration script includes the public REST policy suite. These tests
use actual resolver/access classes and WordPress hooks and request/response
classes with storage/login stubs. They do not replace an HTTP integration test
with FAUbox and RRZE-AC.

## Development

Requirements

-WP >= 6.8
-PHP >= 8.2
-Node.js >= 22.8.0
-npm >= 10.8.2

Contributing

Contributions are welcome! Please follow the guidelines below:

1. Fork the repository.
2. Create a new branch for your feature or bugfix.
3. Submit a pull request with a detailed description of your changes.

## License

This plugin is licensed under the GNU General Public License (GPL) Version 3.

## Author

Developed by the RRZE Webteam.

## Support

For support, please refer to the GitHub repository.
