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

## Development

Requirements

-WP >= 6.7
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
