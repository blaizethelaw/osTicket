<?php
require_once(INCLUDE_DIR . 'class.plugin.php');

class FireSyncPluginConfig extends PluginConfig {
    function getOptions() {
        return array(
            'project_id' => new TextboxField(array(
                'label' => 'Firebase Project ID',
                'required' => true,
            )),
            'verbose_logging' => new BooleanField(array(
                'label' => 'Verbose Logging',
                'default' => false,
                'configuration' => array(
                    'desc' => 'Log request and response data for debugging',
                ),
            )),
        );
    }

    function pre_save(&$config, &$errors) {
        return true;
    }
}
