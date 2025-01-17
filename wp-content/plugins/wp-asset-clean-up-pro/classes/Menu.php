<?php
namespace WpAssetCleanUp;

/**
 * Class Menu
 * @package WpAssetCleanUp
 */
class Menu
{
	/**
	 * @var array|string[]
	 */
	public static $allMenuPages = array();

	/**
	 * @var string
	 */
	private static $_capability = 'administrator';

	/**
	 * @var string
	 */
	private static $_slug;

    /**
     * Menu constructor.
     */
    public function __construct()
    {
    	self::$allMenuPages = array(
		    WPACU_PLUGIN_ID . '_getting_started',
		    WPACU_PLUGIN_ID . '_settings',
		    WPACU_PLUGIN_ID . '_assets_manager',
		    WPACU_PLUGIN_ID . '_plugins_manager',
		    WPACU_PLUGIN_ID . '_bulk_unloads',
		    WPACU_PLUGIN_ID . '_overview',
		    WPACU_PLUGIN_ID . '_tools',
		    WPACU_PLUGIN_ID . '_license',
		    WPACU_PLUGIN_ID . '_get_help',
		    WPACU_PLUGIN_ID . '_go_pro'
	    );

    	self::$_slug = WPACU_PLUGIN_ID . '_getting_started';

        add_action('admin_menu', array($this, 'activeMenu'));

	    if (Misc::getVar('get', 'page') === WPACU_PLUGIN_ID . '_feature_request') {
		    header('Location: '.WPACU_PLUGIN_FEATURE_REQUEST_URL.'?utm_source=plugin_feature_request_from_pro');
		    exit();
	    }

	    add_filter( 'post_row_actions', array($this, 'editPostRowActions'), 10, 2 );
	    add_filter( 'page_row_actions', array($this, 'editPostRowActions'), 10, 2 );

	    add_action('admin_page_access_denied', array($this, 'pluginPagesAccessDenied'));
    }

    /**
     *
     */
    public function activeMenu()
    {
	    // User should be of 'administrator' role and allowed to activate plugins
	    if (! self::userCanManageAssets()) {
		    return;
	    }

        add_menu_page(
            WPACU_PLUGIN_TITLE,
	        WPACU_PLUGIN_TITLE,
	        self::getAccessCapability(),
            self::$_slug,
            array(new Info, 'gettingStarted'),
	        WPACU_PLUGIN_URL.'/assets/icons/icon-asset-cleanup.png'
        );

	    add_submenu_page(
		    self::$_slug,
		    __('Settings', 'wp-asset-clean-up'),
		    __('Settings', 'wp-asset-clean-up'),
		    self::getAccessCapability(),
		    WPACU_PLUGIN_ID . '_settings',
		    array(new Settings, 'settingsPage')
	    );

	    add_submenu_page(
		    self::$_slug,
		    __('CSS/JS Manager', 'wp-asset-clean-up'),
		    __('CSS/JS Manager', 'wp-asset-clean-up'),
		    self::getAccessCapability(),
		    WPACU_PLUGIN_ID . '_assets_manager',
		    array(new AssetsPagesManager, 'renderPage')
	    );

	    add_submenu_page(
		    self::$_slug,
		    __('Plugins Manager', 'wp-asset-clean-up'),
		    __('Plugins Manager', 'wp-asset-clean-up'),
		    self::getAccessCapability(),
		    WPACU_PLUGIN_ID . '_plugins_manager',
		    array(new PluginsManager, 'page')
	    );

	    add_submenu_page(
	        self::$_slug,
            __('Bulk Changes', 'wp-asset-clean-up'),
            __('Bulk Changes', 'wp-asset-clean-up'),
		    self::getAccessCapability(),
		    WPACU_PLUGIN_ID . '_bulk_unloads',
            array(new BulkChanges, 'pageBulkUnloads')
        );

	    add_submenu_page(
		    self::$_slug,
		    __('Overview', 'wp-asset-clean-up'),
		    __('Overview', 'wp-asset-clean-up'),
		    self::getAccessCapability(),
		    WPACU_PLUGIN_ID . '_overview',
		    array(new Overview, 'pageOverview')
	    );

	    add_submenu_page(
		    self::$_slug,
		    __('Tools', 'wp-asset-clean-up'),
		    __('Tools', 'wp-asset-clean-up'),
		    self::getAccessCapability(),
		    WPACU_PLUGIN_ID . '_tools',
		    array(new Tools, 'toolsPage')
	    );

		// [wpacu_pro]
	    $wpacuAnyWarningSign = '';
	    $licenseStatus = get_option(WPACU_PLUGIN_ID . '_pro_license_status');

	    if (! $licenseStatus) {
		    $licenseStatus = 'inactive'; // default if no value is found
	    }

	    if (in_array($licenseStatus, array('inactive', 'expired', 'invalid', 'disabled'))) {
		    $wpacuAnyWarningSign = <<<HTML
&nbsp;<span id="wpacu-sidebar-menu-license-status" class="update-plugins" style="position: relative;">
	<span style="font-weight: 300; font-size: 11px;">{$licenseStatus}</span>
</span>
HTML;
	    }
	    // [/wpacu_pro]

	    // License Page
	    add_submenu_page(
		    self::$_slug,
		    __('License', 'wp-asset-clean-up'),
		    __('License', 'wp-asset-clean-up') . $wpacuAnyWarningSign,
		    self::getAccessCapability(),
		    WPACU_PLUGIN_ID . '_license',
		    array(new \WpAssetCleanUpPro\License, 'licensePage')
	    );

        // Get Help | Support Page
        add_submenu_page(
	        self::$_slug,
            __('Help', 'wp-asset-clean-up'),
            __('Help', 'wp-asset-clean-up'),
	        self::getAccessCapability(),
	        WPACU_PLUGIN_ID . '_get_help',
            array(new Info, 'help')
        );

	    // Add "Asset CleanUp Pro" Settings Link to the main "Settings" menu within the Dashboard
	    // For easier navigation
	    $GLOBALS['submenu']['options-general.php'][] = array(
		    WPACU_PLUGIN_TITLE,
		    self::getAccessCapability(),
		    esc_url(admin_url( 'admin.php?page=' . WPACU_PLUGIN_ID . '_settings')),
		    WPACU_PLUGIN_TITLE,
	    );

        // Rename first item from the menu which has the same title as the menu page
        $GLOBALS['submenu'][self::$_slug][0][0] = esc_attr__('Getting Started', 'wp-asset-clean-up');
    }

	/**
	 * @return bool
	 */
	public static function userCanManageAssets()
	{
		if (is_super_admin()) {
			return true; // For security reasons, super admins will always be able to access the plugin's settings
		}

		// Has self::$_capability been changed? Just user current_user_can()
		if (self::getAccessCapability() !== self::$_capability) {
			return current_user_can(self::getAccessCapability());
		}

		// self::$_capability default value: "administrator"
		return current_user_can(self::getAccessCapability());
	}

	/**
	 * @return bool
	 */
	public static function isPluginPage()
	{
		return isset($_GET['page']) && in_array($_GET['page'], self::$allMenuPages);
	}

	/**
	 * Here self::$_capability can be overridden
	 *
	 * @return mixed|void
	 */
	public static function getAccessCapability()
	{
		return apply_filters('wpacu_access_role', self::$_capability);
	}

	/**
	 * @param $actions
	 * @param $post
	 *
	 * @return mixed
	 */
	public function editPostRowActions($actions, $post)
	{
		// Check for your post type.
		if ( $post->post_type === 'post' ) {
			$wpacuFor = 'posts';
		} elseif ( $post->post_type === 'page' ) {
			$wpacuFor = 'pages';
		} elseif ( $post->post_type === 'attachment' ) {
			$wpacuFor = 'media-attachment';
		} else {
			$wpacuFor = 'custom-post-types';
		}

		$postTypeObject = get_post_type_object($post->post_type);

		if ( ! (isset($postTypeObject->public) && $postTypeObject->public == 1) ) {
			return $actions;
		}

		if ( ! in_array(get_post_status($post), array('publish', 'private')) ) {
			return $actions;
		}

		// Do not show the management link to specific post types that are marked as "public", but not relevant such as "ct_template" from Oxygen Builder
		if (in_array($post->post_type, MetaBoxes::$noMetaBoxesForPostTypes)) {
			return $actions;
		}

		// Build your links URL.
		$url = esc_url(admin_url( 'admin.php?page=wpassetcleanup_assets_manager' ));

		// Maybe put in some extra arguments based on the post status.
		$edit_link = add_query_arg(
			array(
				'wpacu_for'     => $wpacuFor,
				'wpacu_post_id' => $post->ID
			), $url
		);

		// Only show it to the user that has "administrator" access, and it's in the following list (if a certain list of admins is provided)
		// "Settings" -> "Plugin Usage Preferences" -> "Allow managing assets to:"
		if (self::userCanManageAssets() && Main::currentUserCanViewAssetsList()) {
			/*
			 * You can reset the default $actions with your own array, or simply merge them
			 * here I want to rewrite my Edit link, remove the Quick-link, and introduce a
			 * new link 'Copy'
			 */
			$actions['wpacu_manage_assets'] = sprintf( '<a href="%1$s">%2$s</a>',
				esc_url( $edit_link ),
				esc_html( __( 'Manage CSS &amp; JS', 'wp-asset-clean-up' ) )
			);
		}

		return $actions;
	}

	/**
	 * Message to show if the user does not have self::$_capability role and tries to access a plugin's page
	 */
	public function pluginPagesAccessDenied()
	{
		if ( ! self::isPluginPage() ) {
			// Not an Asset CleanUp page
			return;
		}

		$userMeta = get_userdata(get_current_user_id());
		$userRoles = $userMeta->roles;

		wp_die(
			__('Извините, у вас нет доступа к этой странице.').'<br /><br />'.
			sprintf(__('Asset CleanUp требует роли «%s» и возможности активировать плагины для доступа к его страницам..', 'wp-asset-clean-up'), '<span style="color: green; font-weight: bold;">'.self::getAccessCapability().'</span>').'<br />'.
			sprintf(__('Ваша текущая роль(и): <strong>%s</strong>', 'wp-asset-clean-up'), implode(', ', $userRoles)).'<br /><br />'.
			__('Значение (зеленого цвета) можно изменить, если вы используете следующий фрагмент в functions.php (в рамках вашей темы/дочерней темы или пользовательского плагина):').'<br />'.
			'<p style="margin: -10px 0 0;"><code style="background: #f2f3ea; padding: 5px;">add_filter(\'wpacu_access_role\', function($role) { return \'your_role_here\'; });</code></p>'.
			'<p>Если сниппет не используется, по умолчанию он будет «administrator".</p>'.
			'<p>Возможные значения: <strong>manage_options</strong>, <strong>activate_plugins</strong>, <strong>manager</strong> etc.</p>'.
			'<p>Читать далее: <a target="_blank" href="https://wordpress.org/support/article/roles-and-capabilities/#summary-of-roles">https://wordpress.org/support/article/roles-and-capabilities/#summary-of-roles</a></p>',
			403
		);
	}
}
