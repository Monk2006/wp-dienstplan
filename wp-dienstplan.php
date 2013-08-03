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

require_once("pdf.php");
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
                  `id_md5` varchar(40) DEFAULT NULL,
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
    add_menu_page('Dienstplan', 'Dienstplan', 1,__FILE__, 'dienstplan_backend', get_bloginfo('wpurl').'/wp-content/plugins/wp-dienstplan/icon.png',26);
    add_submenu_page(__FILE__, 'Neuer Dienst', 'Neuer Dienst',1  ,'dienstplan_neu', 'dienstplan_neu');
    add_submenu_page(__FILE__, 'Einstellungen', 'Einstellungen',8,  'dienstplan_einstellungen', 'dienstplan_einstellungen');
    add_submenu_page(null, 'Bearbeiten', 'Bearbeiten',1,  'dienstplan_bearbeiten', 'dienstplan_bearbeiten');
}
add_action('admin_menu', 'dienstplan_menu');

function dienstplan_bearbeiten(){
    wp_enqueue_script('jquery-ui-datepicker');
    wp_enqueue_style('sticky_post-admin-ui-css','http://ajax.googleapis.com/ajax/libs/jqueryui/1.9.0/themes/base/jquery-ui.css',false,"1.9.0",false);
    global $wpdb;
    $table_name_gruppen = $wpdb->prefix . "dienstplan_gruppen";
    $table_name_dienst = $wpdb->prefix . "dienstplan_dienste";
    if(isset($_POST['cat'])){
        $datetime = dienstplan_date_german2mysql($_POST['datum'])." ".$_POST['selectbox_uhrzeit_hour'].":".$_POST['selectbox_uhrzeit_minute'];
        $wpdb->update($table_name_dienst,array('datetime' => $datetime,'ort' => $_POST['ort'],'beschreibung' => $_POST['beschreibung'], 'gruppen' => implode(',',$_POST['select_gruppe']), 'term_id' => $_POST['cat'] ),array("id" => $_POST['id']) );
        dienstplan_backend();
        return false;

    }

    $categories = get_categories( $catargs );
    foreach($categories as $categorie){
        $categoriesarray[] = $categorie->term_id;
    }
    $categories = implode(",",$categoriesarray);
    echo "<h1>Bearbeiten</h1>";
    $dienst_id = $_GET['dienst_id'];
    $row = $wpdb->get_results("Select id,id_md5,DATE_FORMAT(datetime,'%d.%m.%Y') date,DATE_FORMAT(datetime,'%h:%i') time,ort,beschreibung,gruppen,term_id from ".$table_name_dienst." where id_md5 = '".$dienst_id."' ");
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
        'selected'                 => $row[0]->term_id,

        'pad_counts'               => false );
    echo "<form METHOD='POST'>";
    echo "<input type='hidden' value='".$row[0]->gruppen."' id='gruppen' />";
    echo "<input type='hidden' value='".$row[0]->id."' name='id' />";
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
    echo '<input id="datum" size="10" name="datum" value="'.$row[0]->date.'" />';
    echo "</td>";
    echo "</tr>";
    echo "<tr>";
    echo "<td>";
    echo "Uhrzeit";
    echo "</td>";
    echo "<td>";
    dienstplan_input_select_time('uhrzeit',$row[0]->time);
    echo "</td>";
    echo "</tr>";
    echo "<tr>";
    echo "<td>";
    echo "Beschreibung";
    echo "</td>";
    echo "<td>";
    echo '<input id="beschreibung" size="50" name="beschreibung" value="'.$row[0]->beschreibung.'" />';
    echo "</td>";
    echo "</tr>";
    echo "<tr>";
    echo "<td>";
    echo "Ort";
    echo "</td>";
    echo "<td>";
    echo '<input id="ort" size="50" name="ort" value="'.$row[0]->ort.'" />';
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

    ?>
    <script type="text/javascript">
        jQuery(document).ready(function(){
            jQuery('#datum').datepicker({dateFormat: "dd.mm.yy",minDate: 0 });
            onCatChange();

        });

        var dropdown = document.getElementById("cat");
        function onCatChange() {
            var gruppen = jQuery("#gruppen").val();
            if ( dropdown.options[dropdown.selectedIndex].value > 0 ) {
                dienstplan_backend_gruppen_load(dropdown.options[dropdown.selectedIndex].value,gruppen);
            }
        }

        dropdown.onchange = onCatChange;
    </script>
<?php
}

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
    //print_r($_POST);
    if(isset($_POST['cat'])){
        $datetime = dienstplan_date_german2mysql($_POST['datum'])." ".$_POST['selectbox_uhrzeit_hour'].":".$_POST['selectbox_uhrzeit_minute'];
        $wpdb->insert($table_name_dienst,array('datetime' => $datetime,'ort' => $_POST['ort'],'beschreibung' => $_POST['beschreibung'],'gruppen' => implode(',',$_POST['select_gruppe']),'term_id' => $_POST['cat']),array('%s','%s','%s','%s','%d'));
        $wpdb->update($table_name_dienst,array('id_md5' => md5($wpdb->insert_id)),array("id" => $wpdb->insert_id) );
        //echo md5($wpdb->insert_id);
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
    $categories = get_categories( $catargs );
    foreach($categories as $categorie){
        $categoriesarray[] = $categorie->term_id;
    }
    $categories = implode(",",$categoriesarray);
    if($_GET['action'] == "deletedienst"){
        $wpdb->delete( $table_name_dienst, array( 'id_md5' => $_GET['dienstid'] ) );
        $_SERVER['QUERY_STRING'] = explode("&action=deletedienst",$_SERVER['QUERY_STRING']);
        $_SERVER['QUERY_STRING'] = $_SERVER['QUERY_STRING'][0];
    }
    echo "<h1>Dienstplan</h1>";
    echo "<table class='wp-list-table widefat'>";
    echo "<tr>";
    echo "<th>Datum / Uhrzeit</th>";
    echo "<th>Gruppen</th>";
    echo "<th>Bereich</th>";
    echo "<th>Beschreibung</th>";
    echo "<th>Ort</th>";
    echo "<th>Bearbeiten</th>";

    echo "</tr>";
    $rows = $wpdb->get_results("SELECT d.id,d.id_md5,DATE_FORMAT(d.datetime,'%d.%m.%Y %H:%i') datetime,d.ort,d.beschreibung,d.gruppen,t.name termaname FROM ".$table_name_dienst." d inner join ".$wpdb->prefix . "terms as t on (d.term_id = t.term_id) where d.datetime > NOW() and t.term_id in (".$categories.") order by d.datetime");

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
        echo "<td>";
        echo '<a href="admin.php?page=dienstplan_bearbeiten&dienst_id='.$row->id_md5.'">[ bearbeiten ]</a><a href="'.$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'].'&action=deletedienst&dienstid='.$row->id_md5.'">[ l&ouml;schen ]</a>';
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

function dienstplan_page($term_id){
    global $wpdb;
    $table_name_gruppen = $wpdb->prefix . "dienstplan_gruppen";
    $table_name_dienst = $wpdb->prefix . "dienstplan_dienste";
    $anzahl_gruppen = $wpdb->get_var("Select count(*) from ".$table_name_gruppen." where term_id = ".$term_id." ");
    $html =  "<table class='wp-list-table widefat' width='100%'>";
    $html .=  "<tr>";
    $html .=  "<th>Datum</th>";
    $html .=  "<th>Uhrzeit</th>";
    if($anzahl_gruppen > 1)
        $html .=  "<th>Gruppen</th>";
    $html .=  "<th>Beschreibung</th>";
    $html .=  "<th>Ort</th>";

    $html .=  "</tr>";
    $rows = $wpdb->get_results("SELECT d.id,DATE_FORMAT(d.datetime,'%d.%m.%Y %H:%i') datetime,d.ort,d.beschreibung,d.gruppen,t.name termaname FROM ".$table_name_dienst." d inner join ".$wpdb->prefix . "terms as t on (d.term_id = t.term_id) where t.term_id = '".$term_id."' and d.datetime > NOW() order by d.datetime");
    $ersterlauf = 0;
    $monat = '';
    foreach($rows as $row){
        if($anzahl_gruppen > 1)
            $rowspan = 5;
        else
            $rowspan = 4;

        if($ersterlauf == 1){
            $ersterlauf = 0;
            $monat = substr($row->datetime,3,2);
            $html .= "<tr>\n";
            $html .= "<td colspan=\"".$rowspan."\" style=\"font-weight:bold\">";
            $html .= dienstplan_monat($row->datetime);
            $html .= "</td>\n";
            $html .= "</tr>\n";
        }
        else{
            $monatneu = substr($row->datetime,3,2);
            if($monatneu != $monat){
                $monat = $monatneu;
                $html .= "<tr>\n";
                $html .= "<td colspan=\"".$rowspan."\" style=\"font-weight:bold\">";
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
        if($anzahl_gruppen > 1) {
            $html .= "<td>";
            $rows_gruppen = $wpdb->get_results("select name from ".$table_name_gruppen." where id in (".$row->gruppen.")");
            foreach($rows_gruppen as $row_gruppe){
                $html .= $row_gruppe->name."<br/>";

            }
            $html .= "</td>\n";
        }
        $html .= "<td>";
        $html .= $row->beschreibung;
        $html .= "</td>\n";
        $html .= "<td>";
        $html .= $row->ort;
        $html .= "</td>\n";
    }
    $html .=  "</table>";
    return $html;
}

add_action( 'admin_footer', 'dienstplan_backend_gruppen_load_javascript' );

function dienstplan_backend_gruppen_load_javascript() {
    ?>
    <script type="text/javascript" >
        function dienstplan_backend_gruppen_load(term_id,gruppen){
        if(gruppen)
            gruppen = gruppen.split(",");
        else
            gruppen = null;


        jQuery(document).ready(function($) {

            var data = {
                action: 'dienstplan_backend_gruppen_load',
                term_id: term_id
            };

            // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
            $.post(ajaxurl, data, function(response) {
                response = JSON.parse(response);
                $('#select_gruppe').find('option').remove();
                for(var i = 0;i < response.length;i++){
                    if(jQuery.inArray(response[i].id,gruppen) != -1)
                        $('#select_gruppe').append($('<option>', { value : response[i].id,selected: 'selected' }).text(response[i].name));
                    else
                        $('#select_gruppe').append($('<option>', { value : response[i].id }).text(response[i].name));

                }
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

add_action('init', 'dienstplan_pdf');
add_action('init', 'dienstplan_icalendar');

function dienstplan_pdf(){
    if(isset($_GET['pdfdienstplan'])){
        $pdf=new PDF();
        $pdf->SetLeftMargin(20);

        $pdf->AliasNbPages();
        $pdf->AddPage();
        $pdf->SetFont('Arial','',12);

        /* INHALT */

        $pdf->SetFillColor(255,0,0);
        $pdf->SetTextColor(255);
        $pdf->SetDrawColor(128,0,0);
        $pdf->SetLineWidth(.1);
        $pdf->SetFont('Arial','B');
        $pdf->Ln();
        //Color and font restoration
        $pdf->SetFillColor(224,235,255);
        $pdf->SetTextColor(0);
        $pdf->SetFont('Arial','',10);
        //Data
        $fill=false;
        $ersterlauf = 1;
        $monat = '';
        $start_page_y = $pdf->GetY();

        global $wpdb;
        $table_name_gruppen = $wpdb->prefix . "dienstplan_gruppen";
        $table_name_dienst = $wpdb->prefix . "dienstplan_dienste";
        $anzahl_gruppen = $wpdb->get_var("Select count(*) from ".$table_name_gruppen." where term_id = ".$_GET['pdfdienstplan']." ");

        $rows = $wpdb->get_results("SELECT d.id,DATE_FORMAT(d.datetime,'%d.%m.%Y %H:%i') datetime,d.ort,d.beschreibung,d.gruppen,t.name termaname FROM ".$table_name_dienst." d inner join ".$wpdb->prefix . "terms as t on (d.term_id = t.term_id) where t.term_id = '".$_GET['pdfdienstplan']."' and d.datetime > NOW() order by d.datetime");

        foreach($rows as $row){
            $y2 = 0;
            $pdf->SetX(20);
            if($pdf->GetY() > 260){
                $pdf->AddPage();
                $ersterlauf = 1;
            }
            if($ersterlauf == 1){
                $ersterlauf = 0;
                $monat = substr($row->datetime,3,2);
                $pdf->Cell(20,6,utf8_decode(dienstplan_monat($row->datetime,1)),0,0,'L');
                $pdf->Ln();

            }
            else{
                $monatneu = substr($row->datetime,3,2);
                if($monatneu != $monat){
                    $monat = $monatneu;
                    $pdf->Ln(10);
                    $pdf->Cell(20,6,utf8_decode(dienstplan_monat($row->datetime,1)),0,0,'L');
                    $pdf->Ln();
                }
            }
            $pdf->SetX(20);
            $y = $pdf->GetY();
            //$pdf->Cell(20,6,$pdf->GetY(),0,0,'L');
            $pdf->Cell(20,6,substr($row->datetime,0,10),0,0,'L');
            $pdf->SetX(40);
            $zeit = substr($row->datetime,11,5);
            if($zeit == "00:00"){
                $zeit = "";
            }
            $pdf->Cell(15,6,$zeit,0,0,'L');
            $pdf->SetXY(50,$y);

            $gruppen = "";
            if($anzahl_gruppen > 1) {
                $rows_gruppen = $wpdb->get_results("select name from ".$table_name_gruppen." where id in (".$row->gruppen.")");
                foreach($rows_gruppen as $row_gruppe){
                    $gruppen .= utf8_decode($row_gruppe->name."\n");

                }
            }



            $pdf->MultiCell(30,6,$gruppen,0,'L');
            if($pdf->GetY() > $y2){
                //echo "1";
                $y2 = $pdf->GetY();
            }
            $pdf->SetXY(85,$y);
            $pdf->MultiCell(75,6,utf8_decode($row->beschreibung),0,'L');
            if($pdf->GetY() > $y2){
                $y2 = $pdf->GetY();
            }
            $pdf->SetXY(160,$y);
            $pdf->MultiCell(40,6,utf8_decode($row->ort),0,'L');
            if($pdf->GetY() > $y2){
                $y2 = $pdf->GetY();
            }
            $pdf->SetXY(160,$y2);

            //$pdf->Ln();
            //$fill=!$fill;
        }







        $pdf->Output();


        return null;


        die();
    }

}

function dienstplan_icalendar(){
    if(isset($_GET['icalendardienstplan'])){
        include_once('ical/class.iCal.inc.php');
        global $wpdb;
        $table_name_gruppen = $wpdb->prefix . "dienstplan_gruppen";
        $table_name_dienst = $wpdb->prefix . "dienstplan_dienste";
        $anzahl_gruppen = $wpdb->get_var("Select count(*) from ".$table_name_gruppen." where term_id = ".$_GET['pdfdienstplan']." ");

        $rows = $wpdb->get_results("SELECT d.id,DATE_FORMAT(d.datetime,'%d.%m.%Y %H:%i') datetime,d.ort,d.beschreibung,d.gruppen,t.name termaname FROM ".$table_name_dienst." d inner join ".$wpdb->prefix . "terms as t on (d.term_id = t.term_id) where t.term_id = '".$_GET['pdfdienstplan']."' and d.datetime > NOW() order by d.datetime");


        $organizer =  array('Kurt', 'kurt2@flaimo.com');
        $categories = array('Freetime','Party');
        $attendees = (array) array(
            'Michi' => 'flaimo2@gmx.net,1',
            'Felix' => ' ,2',
            'Walter' => 'flaimo2@gmx.net,3'
        );
        $days = array (2,3);

        $iCal = new iCal("",0,"");
        foreach ($rows as $row){
            $iCal->addEvent(
                "", // Organizer
                time()+10000, // Start Time (timestamp; for an allday event the startdate has to start at YYYY-mm-dd 00:00:00)
                time()+40000, // End Time (write 'allday' for an allday event instead of a timestamp)
                'Vienna', // Location
                0, // Transparancy (0 = OPAQUE | 1 = TRANSPARENT)
                $categories, // Array with Strings
                'See homepage for more details...', // Description
                'Air and Style Snowboard Contest', // Title
                1, // Class (0 = PRIVATE | 1 = PUBLIC | 2 = CONFIDENTIAL)
                $attendees, // Array (key = attendee name, value = e-mail, second value = role of the attendee [0 = CHAIR | 1 = REQ | 2 = OPT | 3 =NON])
                5, // Priority = 0-9
                5, // frequency: 0 = once, secoundly - yearly = 1-7
                10, // recurrency end: ('' = forever | integer = number of times | timestring = explicit date)
                2, // Interval for frequency (every 2,3,4 weeks...)
                $days, // Array with the number of the days the event accures (example: array(0,1,5) = Sunday, Monday, Friday
                0, // Startday of the Week ( 0 = Sunday - 6 = Saturday)
                '', // exeption dates: Array with timestamps of dates that should not be includes in the recurring event
                0,  // Sets the time in minutes an alarm appears before the event in the programm. no alarm if empty string or 0
                1, // Status of the event (0 = TENTATIVE, 1 = CONFIRMED, 2 = CANCELLED)
                'http://flaimo.com/', // optional URL for that event
                'de', // Language of the Strings
                '' // Optional UID for this event
            );
        }


        $iCal->outputFile('ics');

        die();
    }

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
//TODO: Beim Bearbeiten und erstellen felder überprüfen
//TODO: Endezeit Dienst / Dauer
//TODO: ICalender einstellungen