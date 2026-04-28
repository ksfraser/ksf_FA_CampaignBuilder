<?php
// CampaignBuilder DB Functions
function get_campaigns():?object{global$db;return$db->query("SELECT*FROM ".TB_PREF."campaigns ORDER BY created_at DESC");}
function get_campaign(int$id):?array{global$db;$r=$db->query("SELECT*FROM ".TB_PREF."campaigns WHERE id=".$id);return$r&&$db->num_rows($r)>0?$db->fetch($r):null;}
function create_campaign(string$name,string$trigger=''):int{global$db;$db->query("INSERT INTO ".TB_PREF."campaigns(name,trigger_type,created_by)VALUES(".$db->escape($name).",".$db->escape($trigger).",".user_id().")");return$db->insert_id;}
function get_campaign_nodes(int$cid):?object{global$db;return$db->query("SELECT*FROM ".TB_PREF."campaign_nodes WHERE campaign_id=".$cid);}