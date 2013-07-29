<?php
/*
	Plugin Name: Wordpress Dienstplan
    Plugin URI: https://github.com/PowerPan/wp-dienstplan
    Description: Wordpress Dienstplan Plugin
	Author: Johannes Rudolph
	Author URI: https://github.com/PowerPan/
    Version: 0.1
*/
// Erstellt die Tabelle beim ersten Start
function dienstplan_install_multisite(){
    global $wpdb;
    if (function_exists('is_multisite') && is_multisite()) {
        // check if it is a network activation - if so, run the activation function for each blog id
        if ($networkwide) {
            $old_blog = $wpdb->blogid;
            // Get all blog ids
            $blogids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
            foreach ($blogids as $blog_id) {
                switch_to_blog($blog_id);
                dienstplan_install();
            }
            switch_to_blog($old_blog);
            return;
        }
    }
    dienstplan_install();
}



function dienstplan_install() {
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
                   `term_id` int(11) DEFAULT NULL,
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
register_activation_hook(__FILE__,'dienstplan_install_multisite');

function dienstplan_menu() {
    add_menu_page('Dienstplan', 'Dienstplan', 1,__FILE__, 'dienstplan_backend');
    add_submenu_page(__FILE__, 'Neuer Dienst', 'Neuer Dienst',1  ,'dienstplan_neu', 'dienstplan_neu');
    add_submenu_page(__FILE__, 'Einstellungen', 'Einstellungen',8,  'dienstplan_einstellungen', 'dienstplan_einstellungen');
}
add_action('admin_menu', 'dienstplan_menu');

function dienstplan_neu(){

    wp_enqueue_script('jquery-ui-datepicker');
    wp_enqueue_style('sticky_post-admin-ui-css','http://ajax.googleapis.com/ajax/libs/jqueryui/1.9.0/themes/base/jquery-ui.css',false,"1.9.0",false);
    global $wpdb;
    $table_name_dienst = $wpdb->prefix . "dienstplan_dienste";
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
        'show_option_none'         => __(' '),
        'pad_counts'               => false );
    echo "<h1>Neuer Dienst</h1>";
    print_r($_POST);
    if(isset($_POST['cat'])){
        $datetime = dienstplan_date_german2mysql($_POST['datum'])." ".$_POST['selectbox_uhrzeit_hour'].":".$_POST['selectbox_uhrzeit_minute'];
        $wpdb->insert($table_name_dienst,array('datetime' => $datetime,'ort' => $_POST['ort'],'beschreibung' => $_POST['beschreibung'],'gruppen' => implode(',',$_POST['select_gruppe']),'term_id' => $_POST['cat']),array('%s','%s','%s','%s','%d'));
    }
    echo "<form METHOD='POST'>";
    echo "<table class='wp-list-table widefat'>";
    echo "<tr>";
    echo "<td>";
    echo "Bereich";
    echo "</td>";
    echo "<td>";
    wp_dropdown_categories( $catargs );
    echo "</td>";
    echo "</tr>";
    echo "<tr>";
    echo "<td>";
    echo "Gruppe";
    echo "</td>";
    echo "<td>";
    echo '<select id="select_gruppe" multiple="multiple" name="select_gruppe[]"></select>';
    echo "</td>";
    echo "</tr>";
    echo "<tr>";
    echo "<td>";
    echo "Datum";
    echo "</td>";
    echo "<td>";
    echo '<input id="datum" size="10" name="datum" value="" />';
    echo "</td>";
    echo "</tr>";
    echo "<tr>";
    echo "<td>";
    echo "Uhrzeit";
    echo "</td>";
    echo "<td>";
    dienstplan_input_select_time('uhrzeit');
    echo "</td>";
    echo "</tr>";
    echo "<tr>";
    echo "<td>";
    echo "Beschreibung";
    echo "</td>";
    echo "<td>";
    echo '<input id="beschreibung" size="50" name="beschreibung" value="" />';
    echo "</td>";
    echo "</tr>";
    echo "<tr>";
    echo "<td>";
    echo "Ort";
    echo "</td>";
    echo "<td>";
    echo '<input id="ort" size="50" name="ort" value="" />';
    echo "</td>";
    echo "</tr>";
    echo "<tr>";
    echo "<td>";
    echo "&nbsp;";
    echo "</td>";
    echo "<td>";
    echo "<input type='submit' value='speichern' class='button button-primary button-large'/>";
    echo "</td>";
    echo "</tr>";

    echo "</table>";

    echo "</form>";

    dienstplan_backend();

    ?>
    <script type="text/javascript">
        jQuery(document).ready(function(){
            jQuery('#datum').datepicker({dateFormat: "dd.mm.yy",minDate: 0 });
        });

        var dropdown = document.getElementById("cat");
        function onCatChange() {
            if ( dropdown.options[dropdown.selectedIndex].value > 0 ) {
                dienstplan_backend_gruppen_load(dropdown.options[dropdown.selectedIndex].value);
            }
        }

        dropdown.onchange = onCatChange;
    </script>
<?php
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
    if(isset($_POST['cat'])){
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

function dienstplan_input_select_time($id,$zeit = null){
    $zeit = explode(":",$zeit);
    echo "<select id=\"selectbox_".$id."_hour\"  name=\"selectbox_".$id."_hour\"> \n";
    for($i = 0; $i <=23;$i++){
        if($i < 10){
            echo "\t<option ";
            if($zeit[0] == $i){
                echo " selected=\"selected\"";
            }
            echo " value=\"0".$i."\">0".$i."</option>\n";
        }
        else {
            echo "\t<option ";
            if($zeit[0] == $i){
                echo " selected=\"selected\"";
            }
            echo " value=\"".$i."\">".$i."</option>\n";
        }
    }
    echo "</select>\n";
    echo ":";
    echo "<select id=\"selectbox_".$id."_minute\" name=\"selectbox_".$id."_minute\"> \n";
    for($i = 0; $i <=59;$i++){
        if($i < 10){
            echo "\t<option ";
            if($zeit[1] == $i){
                echo " selected=\"selected\"";
            }
            echo " value=\"0".$i."\">0".$i."</option>\n";
        }
        else {
            echo "\t<option ";
            if($zeit[1] == $i){
                echo " selected=\"selected\"";
            }
            echo " value=\"".$i."\">".$i."</option>\n";
        }
    }
    echo "</select>\n";
    echo " Uhr";
}

function dienstplan_date_german2mysql($date) {
    if(strlen($date) >1) {
        if(strlen($date) == 10) {
            $d    =    explode(".",$date);
            return    sprintf("%04d-%02d-%02d", $d[2], $d[1], $d[0]);
        }
        else {
            $da 	= explode(" ",$date);
            $da[0]	= date_german2mysql($da[0]);
            $date 	= $da[0]." ".$da[1];
            return $date;
        }
    }
    else {
        return null;
    }
}

function dienstplan_monat($date = null,$pdf = null){
    if($date == null){
        $monat = date("n");
    }
    else{
        $monat = date("n",strtotime($date));
    }
    switch($monat){
        case "1":   $return =  "Januar";break;
        case "2":   $return =  "Februar";break;
        case "3":   $return =  "M&auml;rz";break;
        case "4":   $return =  "April";break;
        case "5":   $return =  "Mai";break;
        case "6":   $return =  "Juni";break;
        case "7":   $return =  "Juli";break;
        case "8":   $return =  "August";break;
        case "9":   $return =  "September";break;
        case "10":   $return =  "Oktober";break;
        case "11":   $return =  "November";break;
        case "12":   $return =  "Dezember";break;
    }
    //if($pdf == 1){
        return $return;
    //}
    /*else{
        echo $return;
    }*/
}


function dienstplan_backend(){
    global $wpdb;
    $table_name_gruppen = $wpdb->prefix . "dienstplan_gruppen";
    $table_name_dienst = $wpdb->prefix . "dienstplan_dienste";
    echo "<h1>Dienstplan</h1>";
    echo "<table class='wp-list-table widefat'>";
    echo "<tr>";
    echo "<th>Datum / Uhrzeit</th>";
    echo "<th>Gruppen</th>";
    echo "<th>Bereich</th>";
    echo "<th>Beschreibung</th>";
    echo "<th>Ort</th>";

    echo "</tr>";
    $rows = $wpdb->get_results("SELECT d.id,DATE_FORMAT(d.datetime,'%d.%m.%Y %H:%i') datetime,d.ort,d.beschreibung,d.gruppen,t.name termaname FROM ".$table_name_dienst." d inner join ".$wpdb->prefix . "terms as t on (d.term_id = t.term_id) where d.datetime > NOW() order by d.datetime");

    foreach($rows as $row){
        




        
        echo "<tr>";
        echo "<td>";
        echo $row->datetime;
        echo "</td>";
        echo "<td>";
        $rows_gruppen = $wpdb->get_results("select name from ".$table_name_gruppen." where id in (".$row->gruppen.")");
        foreach($rows_gruppen as $row_gruppe){
            echo $row_gruppe->name."<br/>";

        }
        //echo $row->gruppen;
        echo "</td>";
        echo "<td>";
        echo $row->termaname;
        echo "</td>";
        echo "<td>";
        echo $row->beschreibung;
        echo "</td>";
        echo "<td>";
        echo $row->ort;
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

function dienstplan_page($term_id){
    global $wpdb;
    $table_name_gruppen = $wpdb->prefix . "dienstplan_gruppen";
    $table_name_dienst = $wpdb->prefix . "dienstplan_dienste";
    $html =  "<table class='wp-list-table widefat' width='100%'>";
    $html .=  "<tr>";
    $html .=  "<th>Datum</th>";
    $html .=  "<th>Uhrzeit</th>";
    $html .=  "<th>Gruppen</th>";
    $html .=  "<th>Beschreibung</th>";
    $html .=  "<th>Ort</th>";

    $html .=  "</tr>";
    $rows = $wpdb->get_results("SELECT d.id,DATE_FORMAT(d.datetime,'%d.%m.%Y %H:%i') datetime,d.ort,d.beschreibung,d.gruppen,t.name termaname FROM ".$table_name_dienst." d inner join ".$wpdb->prefix . "terms as t on (d.term_id = t.term_id) where t.term_id = '".$term_id."' and d.datetime > NOW() order by d.datetime");
    $ersterlauf = 0;
    $monat = '';
    foreach($rows as $row){
        if($ersterlauf == 1){
            $ersterlauf = 0;
            $monat = substr($row->datetime,3,2);
            $html .= "<tr>\n";
            $html .= "<td colspan=\"5\" style=\"font-weight:bold\">";
            $html .= dienstplan_monat($row->datetime);
            $html .= "</td>\n";
            $html .= "</tr>\n";
        }
        else{
            $monatneu = substr($row->datetime,3,2);
            if($monatneu != $monat){
                $monat = $monatneu;
                $html .= "<tr>\n";
                $html .= "<td colspan=\"5\" style=\"font-weight:bold\">";
                $html .= dienstplan_monat($row->datetime);
                $html .= "</td>\n";
                $html .= "</tr>\n";
            }
        }
        $html .= "<tr>\n";
        $html .= "<td>";
        $html .= substr($row->datetime,0,10);
        $html .= "</td>\n";
        $html .= "<td>";
        $zeit = substr($row->datetime,11,5);
        if($zeit == "00:00"){
            $zeit = "";
        }
        $html .= $zeit;
        $html .= "</td>\n";
        $html .= "<td>";
        $rows_gruppen = $wpdb->get_results("select name from ".$table_name_gruppen." where id in (".$row->gruppen.")");
        foreach($rows_gruppen as $row_gruppe){
            $html .= $row_gruppe->name."<br/>";

        }
        $html .= "</td>\n";
        $html .= "<td>";
        $html .= $row->beschreibung;
        $html .= "</td>\n";
        $html .= "<td>";
        $html .= $row->ort;
        $html .= "</td>\n";
        /*$html .=  "<tr>";
        $html .=  "<td>";
        $html .=  $row->datetime;
        $html .=  "</td>";
        $html .=  "<td>";
        $rows_gruppen = $wpdb->get_results("select name from ".$table_name_gruppen." where id in (".$row->gruppen.")");
        foreach($rows_gruppen as $row_gruppe){
            $html .=  $row_gruppe->name."<br/>";

        }
        //$html .=  $row->gruppen;
        $html .=  "</td>";
        $html .=  "<td>";
        $html .=  $row->termaname;
        $html .=  "</td>";
        $html .=  "<td>";
        $html .=  $row->beschreibung;
        $html .=  "</td>";
        $html .=  "<td>";
        $html .=  $row->ort;
        $html .=  "</td>";
        $html .=  "</tr>";*/
    }
    $html .=  "</table>";
    return $html;
}

add_action( 'admin_footer', 'dienstplan_backend_gruppen_load_javascript' );

function dienstplan_backend_gruppen_load_javascript() {
    ?>
    <script type="text/javascript" >
        function dienstplan_backend_gruppen_load(term_id){


        jQuery(document).ready(function($) {

            var data = {
                action: 'dienstplan_backend_gruppen_load',
                term_id: term_id
            };

            // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
            $.post(ajaxurl, data, function(response) {
                response = JSON.parse(response);
                $('#select_gruppe').find('option').remove();
                for(var i = 0;i < response.length;i++)
                    $('#select_gruppe').append($('<option>', { value : response[i].id }).text(response[i].name));
                //alert(response.toSource());
            });
        });
        }
    </script>
<?php
}

add_action('wp_ajax_dienstplan_backend_gruppen_load', 'dienstplan_backend_gruppen_load_callback');

function dienstplan_backend_gruppen_load_callback() {
    global $wpdb; // this is how you get access to the database
    $table_name_gruppen = $wpdb->prefix . "dienstplan_gruppen";
    $rows = $wpdb->get_results("SELECT id,name FROM ".$table_name_gruppen." where term_id ='".$_POST['term_id']."'");
    foreach($rows as $row){
        $row->name = $row->name;
        $data[] = array("id" => $row->id,"name" => $row->name);
    }
    echo json_encode($data);

    die(); // this is required to return a proper result
}



function dienstplan_filter($content) {
    $suche = "/[dienstplan bereich=[0-9][0-9]?[0-9]?[0-9]?]/";
    preg_match($suche,$content,$ergebnis);
    $id = str_replace("]","",str_replace("=","",$ergebnis[0]));
    $content = str_replace("[dienstplan bereich=".$id."]",dienstplan_page($id),$content);
    return $content;
}
add_filter('the_content', 'dienstplan_filter');

//TODO: PDF Export nach Bereich
//TODO: Deintsalltion -> Löschen der Tabellen