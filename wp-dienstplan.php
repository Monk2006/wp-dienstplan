<?php
/*
	Plugin Name: Wordpress Dienstplan
    Plugin URI: https://github.com/PowerPan/wp-dienstplan
    Description: Wordpress Dienstplan Plugin
	Author: Johannes Rudolph
	Author URI: hhttps://github.com/PowerPan/
    Version: 0.0.1
*/
// Erstellt die Tabelle beim ersten Start
function install () {
    global $wpdb;

    $table_name = $wpdb->prefix . "dienstplan_dienste";
    if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
         //Calendar Tabelle
         $sql = "CREATE TABLE " . $table_name . " (
                  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                  `datetime` datetime NOT NULL,
                  `ort` varchar(500) NOT NULL DEFAULT '',
                  `beschreibung` varchar(1000) NOT NULL DEFAULT '',
                  `gruppen` varchar(50) DEFAULT '',
                  PRIMARY KEY (`id`)
                )";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    $table_name = $wpdb->prefix . "dienstplan_gruppen";
    if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
        //Calendar Tabelle
        $sql = "CREATE TABLE " . $table_name . " (
                  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                  `name` varchar(100) NOT NULL DEFAULT '',
                  `term_id` int(11) NOT NULL,
                  PRIMARY KEY (`id`)
                )";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
register_activation_hook(__FILE__,'install');

function dienstplan_menu() {
    add_menu_page('Dienstplan', 'Dienstplan', 8, __FILE__, 'dienstplan_backend');
    add_submenu_page(__FILE__, 'Neuer Dienst', 'Neuer Dienst', 8, 'dienstplan_neu', 'dienstplan_neu');
    add_submenu_page(__FILE__, 'Einstellungen', 'Einstellungen', 8, 'dienstplan_einstellungen', 'dienstplan_einstellungen');
}
add_action('admin_menu', 'dienstplan_menu');

function dienstplan_neu(){
    echo "<h1>Neuer Dienst</h1>";
}

function dienstplan_einstellungen(){
    global $wpdb;
    $table_name_gruppen = $wpdb->prefix . "dienstplan_gruppen";
    $catargs = array(
        'type'                     => 'post',
        'child_of'                 => 0,
        'parent'                   => '',
        'orderby'                  => 'name',
        'order'                    => 'ASC',
        'hide_empty'               => 0,
        'hierarchical'             => 1,
        'exclude'                  => '',
        'include'                  => '',
        'number'                   => '',
        'taxonomy'                 => 'category',
        'pad_counts'               => false );
    echo "<h1>Dienstplan Einstellungen</h1>";
    echo "<h2>Gruppen</h2>";
    //print_r($_POST);
    if(isset($_POST['dienstplan_gruppe_neu'])){
        $wpdb->insert($table_name_gruppen,array('name' => $_POST['dienstplan_gruppe_neu'],'term_id' => $_POST['cat']),array('%s','%d'));
    }
    echo "<form METHOD='POST'>";
    echo "<table class='wp-list-table widefat'>";
    echo "<tr>";
    echo "<th>Gruppename</th>";
    echo "<th>Kategorie</th>";
    echo "</tr>";

    $rows = $wpdb->get_results("SELECT g.id,g.name,g.term_id,t.name termaname FROM ".$table_name_gruppen." g inner join ".$wpdb->prefix . "terms as t on (g.term_id = t.term_id)");
    foreach($rows as $row){
        echo "<tr>";
        echo "<td>";
        echo $row->name;
        echo "</td>";
        echo "<td>";
        echo $row->termaname;
        echo "</td>";
        echo "</tr>";
    }
    echo "<tr>";
    echo "<td><input type='text' name='dienstplan_gruppe_neu'/></td>";
    echo "<td>";
    wp_dropdown_categories( $catargs );
    echo "<input type='submit' value='speichern'/></td>";
    echo "</tr>";
    echo "</table>";
    echo "</form>";
}

function dienstplan_backend(){
    echo "<h1>Dienstplan</h1>";
}
