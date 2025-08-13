<?php
require_once(INCLUDE_DIR . 'class.plugin.php');

class FireSyncPluginConfig extends PluginConfig {
    function getOptions() {
        return array(
            'project_id' => new TextboxField(array(
                'label' => 'Firebase Project ID',
                'required' => true,
            )),
        );
    }

    function pre_save(&$config, &$errors) {
        return true;
    }
}
