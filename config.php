<?php

$DUMP_PDF = false;

$workshops = array(
    array('', array('' => 'Bitte wählen...')),
    array('Bild', array('camptv' => 'CampTV',
                        'digital' => 'Foto Digital',
                        'kurzfilm' => 'Kurzfilm',
                        'analog' => 'Foto Analog',
                        'vjing' => 'VJing')),
    array('Ton', array('acapampa' => 'Acapampa',
                       'jamsession' => 'JamSession',
                       'radio' => 'Radio')),
    array('Wort', array('gespraechsfuehrung' => 'Gesprächsführung',
                        'moderation' => 'Rhetorik und Moderation',
                        'pampaper' => 'PampaPaper',
                        'poetry_slam' => 'Poetry Slam',
                        'drehbuch' => 'Drehbücher')),
    array('Gesellschaft', array('3affen' => '3 Affen',
                                'medienphilo' => 'Philosophie 2.0',
                                'medienmani' => 'Medienmanipulation')),
    array('Kreatives', array('theater' => 'Theater',
                             'kunst' => 'Ein Körnchen Kunst',
                             'kackscheisse' => 'KREATIVE KACKSCHEISSE',
                             'poster' => 'PosterMania')),
    );


$jmc_start_date = '2014/06/06';

$pdf_template = "anmeldung2014.pdf";
?>

