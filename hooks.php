<?php
/**
 * FA_CampaignBuilder Module Hooks for FrontAccounting
 */

define('SS_CAMPAIGN', 116 << 8);

class hooks_fa_campaignbuilder extends hooks {

    private function ensure_composer_dependencies(): void {
        $module_dir = dirname(__FILE__);
        $autoload_path = $module_dir . '/vendor/autoload.php';
        
        if (!file_exists($autoload_path)) {
            $composer_path = $module_dir . '/composer.json';
            if (file_exists($composer_path)) {
                chdir($module_dir);
                $output = [];
                $return_code = 0;
                exec('composer install --no-interaction --prefer-dist 2>&1', $output, $return_code);
                if ($return_code !== 0) {
                    error_log('KSF Module: composer install failed: ' . implode("\n", $output));
                }
            }
        }
    }
    var $module_name = 'fa_campaignbuilder';

    private function ensure_composer_dependencies(): void {
        $module_dir = dirname(__FILE__);
        $autoload_path = $module_dir . '/vendor/autoload.php';
        
        if (!file_exists($autoload_path)) {
            $composer_path = $module_dir . '/composer.json';
            if (file_exists($composer_path)) {
                chdir($module_dir);
                $output = [];
                $return_code = 0;
                exec('composer install --no-interaction --prefer-dist 2>&1', $output, $return_code);
                if ($return_code !== 0) {
                    error_log('KSF Module: composer install failed: ' . implode("\n", $output));
                }
            }
        }
    }
    var $version = '1.0.0';

    private function ensure_composer_dependencies(): void {
        $module_dir = dirname(__FILE__);
        $autoload_path = $module_dir . '/vendor/autoload.php';
        
        if (!file_exists($autoload_path)) {
            $composer_path = $module_dir . '/composer.json';
            if (file_exists($composer_path)) {
                chdir($module_dir);
                $output = [];
                $return_code = 0;
                exec('composer install --no-interaction --prefer-dist 2>&1', $output, $return_code);
                if ($return_code !== 0) {
                    error_log('KSF Module: composer install failed: ' . implode("\n", $output));
                }
            }
        }
    }

    function install_options($app) {
        global $path_to_root;

        switch($app->id) {
            case 'CRM':
                $app->add_lapp_function(0, _("Campaign Builder"),
                    $path_to_root."/modules/".$this->module_name."/campaigns.php", 'SA_CAMPAIGNVIEW', MENU_ENTRY);
                $app->add_lapp_function(1, _("Campaign Nodes"),
                    $path_to_root."/modules/".$this->module_name."/nodes.php", 'SA_CAMPAIGNCREATE', MENU_ENTRY);
                $app->add_rapp_function(2, _("Campaign Reports"),
                    $path_to_root."/modules/".$this->module_name."/reports.php", 'SA_CAMPAIGNVIEW', MENU_REPORT);
                break;
        }
    }

    function install_access() {
        $security_sections[SS_CAMPAIGN] = _("Campaign Builder");
        $security_areas['SA_CAMPAIGNVIEW'] = array(SS_CAMPAIGN | 1, _("View Campaigns"));
        $security_areas['SA_CAMPAIGNCREATE'] = array(SS_CAMPAIGN | 2, _("Create Campaigns"));
        return array($security_areas, $security_sections);
    }

    function activate_extension($company, $check_only=true) {
        $updates = array('sql/update.sql' => array($this->module_name));
        $ok = $this->update_databases($company, $updates, $check_only);
        if ($check_only || !$ok) {
            return $ok;
        }
        $this->ensure_campaign_schema();
        return $ok;
    }

    private function table_exists($table) {
        $sql = "SHOW TABLES LIKE " . db_escape($table);
        $res = db_query($sql, 'Failed checking table existence');
        return db_num_rows($res) > 0;
    }

    private function ensure_campaign_schema() {
        $tables = array(
            TB_PREF . "fa_campaigns" => "
                CREATE TABLE IF NOT EXISTS `" . TB_PREF . "fa_campaigns` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT,
                    `name` VARCHAR(100) NOT NULL,
                    `description` TEXT,
                    `status` VARCHAR(20) DEFAULT 'Draft',
                    `start_date` DATE DEFAULT NULL,
                    `end_date` DATE DEFAULT NULL,
                    `budget` DECIMAL(15,2) DEFAULT 0,
                    `created_by` VARCHAR(100) DEFAULT NULL,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    KEY `idx_status` (`status`),
                    KEY `idx_dates` (`start_date`, `end_date`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            TB_PREF . "fa_campaign_nodes" => "
                CREATE TABLE IF NOT EXISTS `" . TB_PREF . "fa_campaign_nodes` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT,
                    `campaign_id` INT(11) NOT NULL,
                    `node_type` VARCHAR(20) NOT NULL,
                    `node_data` TEXT,
                    `position_x` DECIMAL(10,2) DEFAULT 0,
                    `position_y` DECIMAL(10,2) DEFAULT 0,
                    `parent_node_id` INT(11) DEFAULT NULL,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    KEY `idx_campaign` (`campaign_id`),
                    KEY `idx_parent` (`parent_node_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        foreach ($tables as $table_name => $sql) {
            db_query($sql, "Could not create Campaign Builder table: $table_name");
        }
    }

    function db_prevoid($trans_type, $trans_no) {
        // Handle voiding if needed
    }
}
?>
