<?php
/*
 * No direct access to this file
 */
if (! isset($data)) {
	exit;
}
?>
<div class="wpacu-clearfix"></div>

<?php
do_action('wpacu_admin_notices');
?>
<form action="<?php echo esc_url(admin_url('admin.php?page=wpassetcleanup_bulk_unloads&wpacu_bulk_menu_tab=regex_load_exceptions&wpacu_rand='.uniqid(time(), true).'#wpacu-top-area')); ?>"
      method="post">
    <div class="wpacu-clearfix"></div>
    <div class="alert">
        <div style="width: 95%; line-height: 22px; margin: 10px 0 0; background: white; padding: 10px; border: 1px solid #ccc; display: inline-block;">
            <h4 style="margin: 0;">Как заполняется приведенный ниже список?</h4>
            Этот список заполняется после того, как вы сделаете исключение для ресурса CSS/JS (дескриптора) для загрузки с помощью параметра «<em>Загрузить его для URL-адресов с URI запроса, соответствующим этому регулярному выражению».</em>" <strong>IF</strong> if the targeted CSS/JS file is already bulk unloaded (e.g. site-wide, on all post pages, etc.) by specifying a RegEx. If you wish to add new load exception RegEx rules for other CSS/JS files, access the "<em>CSS &amp; JavaScript Load Manager</em>" for a page that loads the targeted file.
        </div>
    </div>

    <div class="wpacu-clearfix"></div>

    <div style="padding: 0 10px 0 0;">
        <h3>Styles (.css)</h3>
		<?php
		$assetKey = 'styles';

		if (! empty($data['values'][$assetKey])) {
        ?>
            <table class="wp-list-table widefat fixed striped" style="width: 100%; max-width: 1024px;">
                <tr>
                    <td style="width: 10%;"><strong>Enabled?</strong></td>
                    <td style="width: 30%;"><strong>Handle</strong></td>
                    <td style="width: 60%;"><strong>RegEx Input</strong></td>
                </tr>
				<?php
				foreach ($data['values'][$assetKey] as $handle => $regExData) {
				    $regExEnable = isset($regExData['enable']) && $regExData['enable'];
                    $regExInputValue = $regExData['value'];
                    ?>
                    <tr class="wpacu_regex_rule_row <?php if ($regExEnable) { echo 'wpacu_enabled'; } ?>">
                        <td>
                            <label class="wpacu_switch_small">
                            <input type="checkbox"
                                   class="wpacu_remove_regex"
                                   name="wpacu_handle_load_regex[<?php echo esc_attr($assetKey); ?>][<?php echo esc_attr($handle); ?>][enable]"
                                   <?php if ($regExEnable) { echo 'checked="checked"'; } ?>
                                   value="remove" />
                                <span class="wpacu_slider wpacu_round"></span></label>
                        </td>
                        <td><?php wpacuRenderHandleTd($handle, $assetKey, $data); ?></td>
                        <td>
                            <label><textarea name="wpacu_handle_load_regex[<?php echo esc_attr($assetKey); ?>][<?php echo esc_attr($handle); ?>][value]"><?php echo esc_textarea($regExInputValue); ?></textarea></label>
                        </td>
                    </tr>
                <?php
				}
				?>
            </table>
			<?php
		} else {
			?>
            <p>Правила исключения загрузки <strong>RegEx</strong> (Regular Expression) не добавлены ни для каких (.css) таблиц стилей.</p>
			<?php
		}
		?>

        <h3>Scripts (.js)</h3>
	    <?php
        $assetKey = 'scripts';

	    if (! empty($data['values'][$assetKey])) {
		    ?>
            <table class="wp-list-table widefat fixed striped" style="width: 100%; max-width: 1024px;">
                <tr>
                    <td style="width: 10%;"><strong>Enabled?</strong></td>
                    <td style="width: 30%;"><strong>Handle</strong></td>
                    <td style="width: 60%;"><strong>RegEx Input</strong></td>
                </tr>
			    <?php
			    foreach ($data['values'][$assetKey] as $handle => $regExData) {
				    $regExEnable = isset($regExData['enable']) && $regExData['enable'];
				    $regExInputValue = $regExData['value'];
				    ?>
                    <tr class="wpacu_regex_rule_row <?php if ($regExEnable) { echo 'wpacu_enabled'; } ?>">
                        <td>
                            <label class="wpacu_switch_small">
                            <input type="checkbox"
                                   class="wpacu_remove_regex"
		                           <?php if ($regExEnable) { echo 'checked="checked"'; } ?>
                                   name="wpacu_handle_load_regex[<?php echo esc_attr($assetKey); ?>][<?php echo esc_attr($handle); ?>][enable]"
                                   value="1" />
                                <span class="wpacu_slider wpacu_round"></span></label>
                        </td>
                        <td><?php wpacuRenderHandleTd($handle, $assetKey, $data); ?></td>
                        <td>
	                        <label><textarea name="wpacu_handle_load_regex[<?php echo esc_attr($assetKey); ?>][<?php echo esc_attr($handle); ?>][value]"><?php echo esc_textarea($regExInputValue); ?></textarea></label>
                        </td>
                    </tr>
				    <?php
			    }
			    ?>
            </table>
		    <?php
	    } else {
			?>
            <p>Правила исключения загрузки <strong>RegEx</strong> (Regular Expression) не добавлены ни для каких (.js) скриптов.</p>
			<?php
		}
		?>
    </div>
    <?php
	$noRegExUnloadRules = ( empty($data['values']['styles']) && empty($data['values']['scripts']));
	?>
    <div class="wpacu-clearfix"></div>

    <div id="wpacu-update-button-area" class="no-left-margin">
        <p class="submit">
			<?php
			$nonceAction = 'wpacu_bulk_regex_update_load_exceptions';
			$nonceName   = $nonceAction.'_nonce';

			wp_nonce_field($nonceAction, $nonceName);
			?>
            <input type="submit"
                   name="submit"
                   id="submit"
				<?php if ($noRegExUnloadRules) { ?>
                    disabled="disabled"
				<?php } ?>
                   class="button button-primary"
                   value="<?php esc_attr_e('Apply changes', 'wp-asset-clean-up'); ?>" />
			<?php
			if ($noRegExUnloadRules) {
				?>
                &nbsp;<small>Примечание. Поскольку для управления CSS/JS нет правил RegEx, кнопка отключена..</small>
				<?php
			}
			?>
        </p>
        <div id="wpacu-updating-settings" style="margin-left: 150px;">
            <img src="<?php echo esc_url(admin_url('images/spinner.gif')); ?>" align="top" width="20" height="20" alt="" />
        </div>
    </div>
</form>
