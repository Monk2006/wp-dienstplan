<?php
require('fpdf17/fpdf.php');
class PDF extends FPDF
{
// Page header
    function Header()
    {
        // Logo
        $this->Image('css/logo.png',10,15,20);
        // Arial bold 15
        $this->SetFont('Arial','B',10);
        $this->SetXY(35,15);

        // Move to the right
        $this->Cell(20,5,'Elektroinstallation - Beleuchtungstechnik - Kommunikationstechnik - Schaltanlagen - EIB-Partner Industriemontagen - 24 Std.-Service - E-CHECK');
        $this->ln(5);
        $this->SetX(35);
        $this->Cell(20,5,'bkw elektro gmbh - Hauptstrasse 86 - 26639 Wiesmoor');
        $this->ln(15);
        $this->SetX(35);
        $this->SetFont('Arial','B',18);
        $this->Cell(20,5,utf8_decode('Inventar'));


    }

// Page footer
    function Footer()
    {
        // Position at 1.5 cm from bottom
        $this->SetY(-15);
        $this->SetX(10);
        $this->Cell(50,10,"Erstellt: ".date("d.m.Y H:i:s"));
        // Arial italic 8
        $this->SetFont('Arial','',8);
        // Page number
        $this->SetX(275);
        $this->Cell(0,10,'Seite '.$this->PageNo(),0,0,'C');
    }
}
