<?php
/**
 * PHPUnit Test Bootstrap
 */

define('TB_PREF', 'fa_');
define('INPUT_COOKIE', '');

function db_query($sql) {
    return true;
}

function db_fetch_assoc($result) {
    return null;
}

function db_insert_id() {
    return 1;
}

function db_escape($value) {
    return "'" . addslashes($value) . "'";
}