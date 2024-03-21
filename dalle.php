<?php
/**
 * Plugin Name: DALL-E API
 * Description: Adds a REST Endpoint for interacting with DALL-E
 * Author: Daniel Garcia Briseno (daniel@dangarbri.tech)
 * Version: 1.1.2
 * Disclaimer - I don't know wordpress well, most of this PHP code was written with Copilot.
 *              the associated app.js and styles.css are hand written.
 */


// create custom plugin settings menu
add_action('admin_menu', 'dalle_rest_api_create_menu');

function dalle_rest_api_create_menu()
{
    //create new top-level menu
    add_menu_page('DALL-E Plugin Settings', 'DALL-E Settings', 'administrator', __FILE__, 'dalle_rest_api_settings_page', plugins_url('/images/icon.png', __FILE__));

    //call register settings function
    add_action('admin_init', 'register_dalle_rest_api_settings');
}

function register_dalle_rest_api_settings()
{
    //register our settings
    register_setting('dalle-rest-api-settings-group', 'dalle_rest_api_key');
}

function dalle_rest_api_settings_page()
{
    ?>
    <div class="wrap">
        <h1>DALL-E</h1>

        <form method="post" action="options.php">
            <?php settings_fields('dalle-rest-api-settings-group'); ?>
            <?php do_settings_sections('dalle-rest-api-settings-group'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">DALL-E Rest API Key</th>
                    <td><input type="text" name="dalle_rest_api_key" value="<?php echo esc_attr(get_option('dalle_rest_api_key')); ?>" /></td>
                </tr>
            </table>

            <?php submit_button(); ?>

        </form>
    </div>
<?php }

register_uninstall_hook(__FILE__, 'dalle_plugin_uninstall');

function dalle_plugin_uninstall()
{
    delete_option('dalle_rest_api_count');
    delete_option('dalle_rest_api_key');
}

add_action('rest_api_init', function () {
    register_rest_route(
        'dalle-rest-api/v1',
        '/dalle/',
        array (
            'methods' => 'GET',
            'callback' => 'dalle_plugin_request_image',
            'args' => array (
                'prompt' => array (
                    'required' => false,
                ),
            ),
        )
    );

    register_rest_route(
        'dalle-rest-api/v1',
        '/count/',
        array (
            'methods' => 'GET',
            'callback' => 'dalle_plugin_get_count'
        )
    );
});

function dalle_plugin_request_image($data)
{
    $prompt = $data['prompt'];

    // Your DALL-E API endpoint
    $api_endpoint = 'https://api.openai.com/v1/images/generations';

    // Get the API key from the WordPress option
    $api_key = get_option('dalle_rest_api_key');

    // Set up the API headers
    $headers = array(
        'Authorization' => 'Bearer ' . $api_key,
        'Content-Type' => 'application/json',
    );

    // Set up the API parameters
    $body = array(
        'model' => 'dall-e-3',
        'prompt' => $prompt,
        'n' => 1,
        'size' => '1024x1024',
    );

    // Make the API request
    $response = wp_remote_post(
        $api_endpoint,
        array(
            'headers' => $headers,
            'body' => json_encode($body),
            'timeout' => 30
        )
    );

    // Check for errors
    if (is_wp_error($response)) {
        return 'Error: ' . $response->get_error_message();
    }

    // Get the response body
    $body = wp_remote_retrieve_body($response);

    // Decode the JSON response
    $json = json_decode($body, true);

    // Increment counter
    if (!array_key_exists('error', $json)) {
        $count = get_option('dalle_rest_api_count', 0);
        update_option('dalle_rest_api_count', $count + 1);
    }
  
    // Return the JSON response
    return $json;
}

function dalle_plugin_get_count() {
    return array ('count' => get_option('dalle_rest_api_count', 0));
}