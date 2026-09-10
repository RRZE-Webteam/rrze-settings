<?php

namespace RRZE\Settings\RestAPI;

defined('ABSPATH') || exit;

use RRZE\Settings\Settings as MainSettings;
use RRZE\Settings\Library\Network\IPUtils;

/**
 * Class Settings
 *
 * This class handles the settings for the REST API section of the plugin.
 *
 * @package RRZE\Settings\RestAPI
 */
class Settings extends MainSettings
{
    /**
     * Menu page slug
     *
     * @var string
     */
    protected $menuPage = 'rrze-settings-rest-api';

    /**
     * Settings section name
     *
     * @var string
     */
    protected $sectionName = 'rrze-settings-rest-api-section';

    /**
     * Adds a submenu page to the network admin menu
     *
     * @return void
     */
    public function networkAdminMenu()
    {
        add_submenu_page(
            'rrze-settings',
            __('REST API', 'rrze-settings'),
            __('REST API', 'rrze-settings'),
            'manage_options',
            $this->menuPage,
            [$this, 'optionsPage']
        );
    }

    /**
     * Validate the options
     *
     * @param array $input The input data
     * @return object The validated options
     */
    public function optionsValidate($input)
    {
        $input['disabled'] = !empty($input['disabled']) ? 1 : 0;

        if (isset($input['restnetwork'])) {
            $input['restnetwork'] = $this->sanitizeRestnetworkField($input['restnetwork']);
        }

        if (isset($input['restwhite'])) {
            $input['restwhite'] = $this->sanitizeRestwhiteField($input['restwhite']);
        }

        $input['restpublic'] = $this->sanitizePublicRestEndpoints($input['restpublic'] ?? []);

        return $this->parseOptionsValidate($input, 'rest');
    }

    /**
     * Adds sections and fields to the settings page
     *
     * @return void
     */
    public function networkAdminPage()
    {
        add_settings_section(
            $this->sectionName,
            __('REST API', 'rrze-settings'),
            [$this, 'mainSectionDescription'],
            $this->menuPage
        );

        add_settings_field(
            'disabled',
            __('REST API Settings', 'rrze-settings'),
            [$this, 'disabledField'],
            $this->menuPage,
            $this->sectionName
        );

        if ($this->siteOptions->rest->disabled) {
            add_settings_field(
                'restnetwork',
                __('Allowed ip addresses', 'rrze-settings'),
                [$this, 'restnetworkField'],
                $this->menuPage,
                $this->sectionName
            );
        }

        // These choices also govern private-site exceptions when the general
        // network REST restriction is disabled, so keep both controls visible.
        add_settings_field(
            'restwhite',
            __('Allowed namespaces and routes', 'rrze-settings'),
            [$this, 'restwhiteField'],
            $this->menuPage,
            $this->sectionName
        );

        add_settings_field(
            'restpublic',
            __('Registered public endpoints', 'rrze-settings'),
            [$this, 'publicRestEndpointsField'],
            $this->menuPage,
            $this->sectionName
        );
    }

    /**
     * Display the main section description
     * 
     * @return void
     */
    public function mainSectionDescription()
    {
        esc_html_e('Network administrators can centrally configure REST API access across the multisite network. The settings lets them disable the API for non‑authenticated users, whitelist specific IP addresses, and permit chosen namespaces—strengthening security and access control on every website.', 'rrze-settings');
    }

    /**
     * Renders the disabled field
     * 
     * @return void
     */
    public function disabledField()
    {
?>
        <label>
            <input type="checkbox" id="rrze-settings-disabled" name="<?php printf('%s[disabled]', $this->optionName); ?>" value="1" <?php checked($this->siteOptions->rest->disabled, 1); ?>>
            <?php _e('Disables the REST API for visitors who are not logged in.', 'rrze-settings'); ?>
        </label>
    <?php
    }

    /**
     * Renders the restnetwork field
     * 
     * @return void
     */
    public function restnetworkField()
    {
    ?>
        <textarea id="rrze-settings-restnetwork" name="<?php printf('%s[restnetwork]', $this->optionName); ?>" aria-describedby="limited-email-domains-desc" cols="46" rows="5"><?php echo $this->getRestnetworkOption(); ?></textarea>
        <p class="description"><?php _e("Enter the IP addresses that allow access to the REST API when disabled.", 'rrze-settings'); ?></p>
        <p class="description"><?php _e("To leave a comment, type a hash symbol (#) followed by the text of your comment.", 'rrze-settings'); ?></p>
        <p class="description"><?php _e("One IP address or IP range per line.", 'rrze-settings'); ?></p>
    <?php
    }

    /**
     * Renders the restwhite field
     * 
     * @return void
     */
    public function restwhiteField()
    {
    ?>
        <textarea id="rrze-settings-restwhite" name="<?php printf('%s[restwhite]', $this->optionName); ?>" aria-describedby="limited-email-domains-desc" cols="46" rows="5"><?php echo $this->getRestwhiteOption(); ?></textarea>
        <p class="description"><?php esc_html_e('Enter one REST API namespace or route per line, for example /wp/v2/pages. Each entry includes its subroutes and permits access when the general REST API restriction is enabled.', 'rrze-settings'); ?></p>
        <p class="description"><?php esc_html_e('Private Site administrators can additionally select these entries for public GET and HEAD access on their own site. Registered public endpoints always require their separate checkbox approval.', 'rrze-settings'); ?></p>
<?php
    }

    /**
     * Render public endpoints declared by installed plugins.
     *
     * @return void
     */
    public function publicRestEndpointsField()
    {
        $endpoints = $this->getPublicRestEndpoints();
        $selected = $this->siteOptions->rest->restpublic ?? [];
        $selected = is_array($selected) ? $selected : [];

        if (empty($endpoints)) {
            echo '<p class="description">';
            esc_html_e('No installed plugin has declared a public REST API endpoint.', 'rrze-settings');
            echo '</p>';
            return;
        }
        ?>
        <fieldset>
            <?php foreach ($endpoints as $id => $endpoint) : ?>
                <label for="rrze-settings-rest-public-<?php echo esc_attr($id); ?>">
                    <input
                        type="checkbox"
                        id="rrze-settings-rest-public-<?php echo esc_attr($id); ?>"
                        name="<?php printf('%s[restpublic][]', esc_attr($this->optionName)); ?>"
                        value="<?php echo esc_attr($id); ?>"
                        <?php checked(in_array($id, $selected, true)); ?>
                    >
                    <strong><?php echo esc_html($endpoint['label']); ?></strong>
                </label>
                <br>
                <code><?php echo esc_html(implode(', ', $endpoint['methods']) . ' ' . $endpoint['route']); ?></code>
                <?php if ($endpoint['description'] !== '') : ?>
                    <p class="description"><?php echo esc_html($endpoint['description']); ?></p>
                <?php endif; ?>
            <?php endforeach; ?>
            <p class="description"><?php esc_html_e('Registered endpoints require explicit approval for anonymous access, even when the general REST API restriction is off or an IP address or namespace is allowed. Only the exact route and listed methods are approved. Visitors without existing access to a private site additionally need a local endpoint selection (GET and HEAD only). Endpoint permission checks still apply.', 'rrze-settings'); ?></p>
        </fieldset>
    <?php
    }

    /**
     * Get the REST network option
     * 
     * @return string The formatted REST network option
     */
    protected function getRestnetworkOption()
    {
        $option = $this->siteOptions->rest->restnetwork;
        if (empty($option) || !is_array($option)) {
            return '';
        }

        $renderedOption = [];
        $option = array_filter($option);
        foreach ($option as $value) {
            $valueAry = explode('#', $value);
            $valueAry = array_filter(array_map('trim', $valueAry));
            $ipRange = $valueAry[0] ?? '';
            $comment = $valueAry[1] ?? '';
            if (!empty($ipRange)) {
                $renderedOption[] = $comment ? $ipRange . ' #' . $comment : $ipRange;
            }
        }

        return !empty($renderedOption) ? implode(PHP_EOL, $renderedOption) : '';
    }

    /**
     * Sanitize the REST network field
     * 
     * @param string $input The input data
     * @return array The sanitized IP ranges
     */
    protected function sanitizeRestnetworkField($input)
    {
        if (empty($input)) {
            return [];
        }
        $ipRanges = [];
        $comments = [];
        $inputAry = array_filter(array_map('trim', explode(PHP_EOL, sanitize_textarea_field($input))));
        foreach ($inputAry as $key => $value) {
            $valueAry = explode('#', $value);
            $valueAry = array_filter(array_map('trim', $valueAry));
            $ipRange = $valueAry[0] ?? '';
            $comment = $valueAry[1] ?? '';
            if (!empty($ipRange)) {
                $ipRanges[$key] = $ipRange;
                $comments[$key] = $comment;
            }
        }

        $ipRanges = $this->getIpRanges($ipRanges);
        if (!empty($ipRanges)) {
            foreach ($ipRanges as $key => $value) {
                $comment = $comments[$key] ?? '';
                $ipRanges[$key] = $comment ? $value . ' #' . $comments[$key] : $value;
            }
        }

        return $ipRanges;
    }

    /**
     * Get the IP ranges from the input data
     * 
     * @param array $data The input data
     * @return array The sanitized IP ranges
     */
    protected function getIpRanges($data)
    {
        $ipRanges = [];
        if (!empty($data) && is_array($data)) {
            foreach ($data as $key => $value) {
                $value = trim($value);
                if (empty($value)) {
                    continue;
                }
                $sanitizedValue = IPUtils::sanitizeIpRange($value);
                if (!is_null($sanitizedValue)) {
                    $ipRanges[$key] = $sanitizedValue;
                }
            }
        }
        return $ipRanges;
    }

    /**
     * Get the REST white option
     * 
     * @return string The formatted REST white option
     */
    protected function getRestwhiteOption()
    {
        $option = $this->siteOptions->rest->restwhite;
        if (!empty($option) && is_array($option)) {
            return implode(PHP_EOL, $option);
        }
        return '';
    }

    /**
     * Sanitize the REST white field
     * 
     * @param string $input The input data
     * @return array|string The sanitized REST white option
     */
    protected function sanitizeRestwhiteField($input)
    {
        if (!empty($input)) {
            return array_filter(array_map('trim', explode(PHP_EOL, sanitize_textarea_field($input))));
        }
        return '';
    }

    /**
     * Validate the registered public endpoints selected by the network administrator.
     *
     * @param mixed $input Submitted endpoint IDs.
     * @return array Valid registered endpoint IDs.
     */
    protected function sanitizePublicRestEndpoints($input)
    {
        return PublicEndpoints::sanitizeSelection($input);
    }

    /**
     * Return valid endpoint definitions supplied by installed plugins.
     *
     * @return array<string, array{label: string, route: string, methods: array, description: string}>
     */
    protected function getPublicRestEndpoints()
    {
        return PublicEndpoints::getForSettings();
    }
}
