<?php
require('fpdf17/fpdf.php');
class PDF extends FPDF {
    function Header(){
        //include("config.inc");
        //$bereich_id = $_GET['bereich'];
        global $wpdb;
        $table_name_gruppen = $wpdb->prefix . "dienstplan_gruppen";
        $table_name_dienst = $wpdb->prefix . "dienstplan_dienste";
        $term_id = $_GET['pdfdienstplan'];
        $bereich = $wpdb->get_var("Select name from $wpdb->terms where term_id = $term_id");
        //$query = "select bereich_name from bereich where bereich_id = '".$bereich_id."'";
        //$quelle = mysql_query($query);
        //$row = mysql_fetch_assoc($quelle);

        //echo $config_feuerwehrname_gemeinde;
        //Logo

        //$this->Image('css/wappenstadt.jpg',20,10,33);
        //$this->Image('css/wappenff.jpg',170,10,33);
        //Arial bold 15
        $this->SetFont('Times','B',15);
        //Move to the right
        $this->Ln();
        //Title
        $this->Ln(8);
        $this->SetY(15);
        $this->Cell(0,0,get_bloginfo( 'name' ),0,0,'C');
        $this->Ln(10);

            $this->Cell(0,0,'- '.html_entity_decode(get_bloginfo( 'description' )).' -',0,2,'C');
            $this->Ln(10);
        $this->Cell(0,0,$bereich,0,0,'C');
        $this->Ln(10);

        $this->Cell(0,0,'Dienstplan',0,2,'C');
        $this->Ln(20);
    }

    function LoadData(){
        //Read file lines
        //$lines=file($file);
        /*$data=array();
        $bereich_id = $_GET['bereich'];
        $query = "  select
                        	d.dienst_id
                        	,d.dienst_datum_zeit
                        	,d.dienst_beschreibung
                        	,d.dienst_ort
                        	,d.bereich_id
                        from
                        	dienst d
                        where
                            d.bereich_id = '".$bereich_id."'
                            and d.dienst_datum_zeit > NOW()
                        order by
                            d.dienst_datum_zeit
                         ";
        $quelle = mysql_query($query);
        while($row = mysql_fetch_assoc($quelle)){
            $data[] = $row;
        }
        return $data;*/
    }

    function FancyTable($data){
        //print_r($data);
        //Colors, line width and bold font
        $this->SetFillColor(255,0,0);
        $this->SetTextColor(255);
        $this->SetDrawColor(128,0,0);
        $this->SetLineWidth(.1);
        $this->SetFont('Arial','B');
        //Header
        //w=array(0,5,10,15,20,25,30,35,40,45,50,55,60,65,70);
        /*for($i=0;$i<count($header);$i++)
            $this->Cell($w[$i],7,$header[$i],1,0,'C',true);*/
        $this->Ln();
        //Color and font restoration
        $this->SetFillColor(224,235,255);
        $this->SetTextColor(0);
        $this->SetFont('Arial','',10);
        //Data
        $fill=false;
        $ersterlauf = 1;
        $monat = '';
        $start_page_y = $this->GetY();
        //echo $start_page_y;
        foreach($data as $row){
            $y2 = 0;
            $this->SetX(20);
            if($this->GetY() > 260){
                $this->AddPage();
                $ersterlauf = 1;
            }
            if($ersterlauf == 1){
                $ersterlauf = 0;
                $monat = substr(date_mysql2german($row['dienst_datum_zeit']),3,2);
                $this->Cell(20,6,html_entity_decode(monat($row['dienst_datum_zeit'],1)),0,0,'L');
                $this->Ln();

            }
            else{
                $monatneu = substr(date_mysql2german($row['dienst_datum_zeit']),3,2);
                if($monatneu != $monat){
                    $monat = $monatneu;
                    $this->Ln(10);
                    $this->Cell(20,6,html_entity_decode(monat($row['dienst_datum_zeit'],1)),0,0,'L');
                    $this->Ln();
                }
            }
            $this->SetX(20);
            $y = $this->GetY();
            //$this->Cell(20,6,$this->GetY(),0,0,'L');
            $this->Cell(20,6,date_mysql2german(substr($row['dienst_datum_zeit'],0,10)),0,0,'L');
            $this->SetX(40);
            $zeit = substr($row['dienst_datum_zeit'],11,5);
            if($zeit == "00:00"){
                $zeit = "";
            }
            $this->Cell(15,6,$zeit,0,0,'L');
            $this->SetXY(50,$y);
            $this->MultiCell(30,6,read_dienstgruppe_dienst($row['dienst_id'],1),0,'L');
            if($this->GetY() > $y2){
                //echo "1";
                $y2 = $this->GetY();
            }
            $this->SetXY(85,$y);
            $this->MultiCell(75,6,html_entity_decode($row['dienst_beschreibung']),0,'L');
            if($this->GetY() > $y2){
                $y2 = $this->GetY();
            }
            $this->SetXY(160,$y);
            $this->MultiCell(40,6,html_entity_decode($row['dienst_ort']),0,'L');
            if($this->GetY() > $y2){
                $y2 = $this->GetY();
            }
            $this->SetXY(160,$y2);

            //$this->Ln();
            //$fill=!$fill;
        }
        //$this->Cell(array_sum($w),0,'');
    }



    //Page footer
    function Footer(){
        //Position at 1.5 cm from bottom
        $this->SetY(-15);
        //Arial italic 8
        $this->SetFont('Arial','I',8);
        //Page number
        //$this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
    }

}