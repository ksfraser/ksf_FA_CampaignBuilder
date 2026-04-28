<?php
$module_id = 'CampaignBuilder'; $module_version = '1.0.0'; $module_name = 'Campaign Builder'; $module_description = 'Visual campaign workflow builder';
$module_tables = ['fa_campaigns', 'fa_campaign_nodes']; $module_capabilities = ['SA_CAMPAIGNVIEW'=>'View Campaigns','SA_CAMPAIGNCREATE'=>'Create Campaigns'];
function campaignbuilder_install():bool{return install_module_sql('CampaignBuilder');}function campaignbuilder_enable():bool{return enable_module('CampaignBuilder');}function campaignbuilder_disable():bool{return disable_module('CampaignBuilder');}function campaignbuilder_remove():bool{return remove_module_sql('CampaignBuilder');}
add_module($module_name,$module_version,$module_description);