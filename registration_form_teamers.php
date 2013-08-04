<?php
/*
JMC-Anmeldeformular
© Paul Seidel, puseidel@gmail.com

Database table defition
(copy&paste for MySQL)

CREATE TABLE jmc_registration (
id int not null auto_increment primary key,
created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
token varchar (32),
finished bool,
accepted bool,
gender varchar(1),
first_name varchar (256),
last_name varchar (256),
birthday date,
parents_contact varchar(512),
street varchar (256),
zip varchar(5),
city varchar (128),
email varchar (256),
telephone varchar (128),
workshop_1 varchar (128),
workshop_2 varchar (128),
workshop_3 varchar (128),
meat bool,
offers_shelter bool,
shelters int,
seeking_shelter bool, 
tshirt_size varchar (2),
girlie_size varchar (2),
pullover_size varchar (2),
pullover_color varchar (2),
no_mail bool,
allow_contact_info bool,
comments varchar(512)
) default charset=utf8;

*/

//true -> turn off captcha; initial form cannot be sent . 
$DEBUG = false;

// require_once('recaptchalib.php');
require_once("pdf_text_definitions.php");
require('config.php');
require('mysql_cred.php');
require('mysql_bind_funcs.php');
require('forms_and_fields.php');


require("tfpdf.php");
//overwrite FPDF so FPDI uses tFPDF instead
class FPDF extends tFPDF
{

}
require('fpdi.php');


class JMCPDF extends FPDI
{

//implement me

}


function map_name($field) { return $field->name; };
function map_question_mark($field) { return "?"; };
function map_value($field) { return $field->get_value(); };
function map_type($field) { return $field->sql_type(); };
function map_name_value($field) { return $field->name . " = ?"; };



//===============================PDF Generation=================================

function generate_pdf($form)
{
    global $pdf_text_definitions;
    global $comment_coordinates;
    global $parents_contact_coordinates;
    global $check_box_coordinates;
    global $choice_check_box_coordinates;
    global $black_pullover_check_box_coordinates;
    global $green_pullover_check_box_coordinates;

    $values = array();
    foreach ($form->fields as $field)
    {
        $values[$field->name] = $field->render_value();
    }

    $pdf = new JMCPDF();
    $pdf->AddFont('DejaVu', '', 'DejaVuSansCondensed.ttf', true);

    $pdf->setSourceFile("anmeldung2013.pdf");
    $tmplIdx = $pdf->importPage(1);

    $pdf->SetLeftMargin(30.0);
    $pdf->SetRightMargin(20.0);

    $pdf->AddPage();
    $pdf->useTemplate($tmplIdx);

    foreach($form->fields as $field)
    {
        if (array_key_exists($field->name, $pdf_text_definitions))
        {
            $pdf_data = $pdf_text_definitions[$field->name];
            list($font_size, $x, $y) = $pdf_data;
            $pdf->SetFont('DejaVu', '', $font_size);
            $pdf->Text($x, $y, $field->render_value());
        }

        $pdf->SetFont('DejaVu', '', 12);

        if (array_key_exists($field->name, $check_box_coordinates))
        {
            if ($field->checked)
            {
                $pdf_data = $check_box_coordinates[$field->name];
                list($x, $y) = $pdf_data;
                $pdf->Text($x, $y, "X");
            }
        }

        if (array_key_exists($field->name, $choice_check_box_coordinates))
        {
            $pdf_data = $choice_check_box_coordinates[$field->name];
            list($x, $y) = $pdf_data[$field->value];
            $pdf->Text($x, $y, "X");
        }
    }



    if ($form->get_field("pullover_color")->value == "s")
    {
        $pdf_data = $black_pullover_check_box_coordinates[$form->get_field("pullover_size")->value];
        if ($pdf_data)
        {
            list($x, $y) = $pdf_data;
            $pdf->Text($x, $y, "X");
        }
    }

    if ($form->get_field("pullover_color")->value == "g")
    {
        $pdf_data = $green_pullover_check_box_coordinates[$form->get_field("pullover_size")->value];
        if ($pdf_data)
        {
            list($x, $y) = $pdf_data;
            $pdf->Text($x, $y, "X");
        }
    }

//==============Uncomment to check every box===================
//     foreach($black_pullover_check_box_coordinates as $coords)
//     {
//         list($x, $y) = $coords;
//         $pdf->Text($x, $y, "X");
//     }
// 
//     foreach($green_pullover_check_box_coordinates as $coords)
//     {
//         list($x, $y) = $coords;
//         $pdf->Text($x, $y, "X");
//     }
// 
//     foreach($choice_check_box_coordinates as $ze_field)
//     {
//         foreach($ze_field as $coords)
//         {
//             list($x, $y) = $coords;
//             $pdf->Text($x, $y, "X");
//         }
//     }
// 
//     foreach($check_box_coordinates as $coords)
//     {
//         list($x, $y) = $coords;
//         $pdf->Text($x, $y, "X");
//     }
//=============================================================


    list($font_size, $x, $y) = $parents_contact_coordinates;
    $pdf->SetFont('DejaVu', '', $font_size);
    $pdf->SetXY($x, $y);

    $contact = $form->get_field('parents_contact')->render_value();
    $contact = str_replace("\n", " ", $contact);
    $pdf->Write(5, $contact);

    list($font_size, $x, $y) = $comment_coordinates;
    $pdf->SetFont('DejaVu', '', $font_size);
    $pdf->SetXY($x, $y);

    $comments = $form->get_field('comments')->render_value();
    $comments = str_replace("\n", " ", $comments);
    $pdf->Write(5, $comments);

    $tmplIdx = $pdf->importPage(2);
    $pdf->AddPage();
    $pdf->useTemplate($tmplIdx);

    return $pdf->Output("dummy", "S");
}


//==============================E-Mail Generation===============================

// build e-mail headers
function build_headers($email_text, $pdf_filename, $pdf_data)
{
    $id = md5(uniqid(time()));

    $additional_headers = "From: Jugendmediencamp <anmeldung@jugendmediencamp.de>\n";
    $additional_headers .= "BCC: anmeldung@jugendmediencamp.de\n";
    $additional_headers .= "MIME-Version: 1.0\n";
    $additional_headers .= "Content-Type: multipart/mixed; boundary=$id\n\n";
    $additional_headers .= "This is a multi-part message in MIME format\n";
    $additional_headers .= "--$id\n";
    $additional_headers .= "Content-Type: text/plain; charset=UTF-8\n";
    $additional_headers .= "Content-Transfer-Encoding: 8bit\n\n";
    $additional_headers .= $email_text; // Inhalt der E-Mail (Body)
    $additional_headers .= "\n--$id";

    $additional_headers .= "\nContent-Type: application/pdf; name=$pdf_filename\n";
    $additional_headers .= "Content-Transfer-Encoding: base64\n";
    $additional_headers .= "Content-Disposition: attachment; filename=$pdf_filename\n\n";
    $additional_headers .= chunk_split(base64_encode($pdf_data));
    $additional_headers .= "\n--$id--";
    return $additional_headers;
}


//=====================================Form Validation==========================


function check_not_empty($value)
{
    if (strlen($value) == 0) {
        return false;
    }
    else {
        return true;
    }
}

function check_post_value($value_name)
{
    if (isset($_POST[$value_name]))
    {
        $value = $_POST[$value_name];
        if (check_not_empty($value)) {
            return $value;
        }
    }
    return null;
}

function check_email($value)
{
    $email_parts = explode('@', $value);
    if (strlen($email_parts[0]) == 0)
        return "Ungültige E-Mailadresse";
    if (count($email_parts) != 2)
        return "Ungültige E-Mailadresse";
    $host = explode('.', $email_parts[1]);
    if (strlen($host[0]) == 0 or strlen($host[1]) == 0)
        return "Ungültige E-Mailadresse.";
    return null;
}

function dateDifference($startDate, $endDate)
{
    $startDate = strtotime($startDate);
    $endDate = strtotime($endDate);
    if ($startDate === false || $startDate < 0 || $endDate === false || $endDate < 0 || $startDate > $endDate)
        return false;

    $years = date('Y', $endDate) - date('Y', $startDate);

    $endMonth = date('m', $endDate);
    $startMonth = date('m', $startDate);

    // Calculate months
    $months = $endMonth - $startMonth;
    if ($months <= 0)  {
        $months += 12;
        $years--;
    }
    if ($years < 0)
        return false;

    // Calculate the days
                $offsets = array();
                if ($years > 0)
                    $offsets[] = $years . (($years == 1) ? ' year' : ' years');
                if ($months > 0)
                    $offsets[] = $months . (($months == 1) ? ' month' : ' months');
                $offsets = count($offsets) > 0 ? '+' . implode(' ', $offsets) : 'now';

                $days = $endDate - strtotime($offsets, $startDate);
                $days = date('z', $days);

    return array($years, $months, $days);
}



function get_jmc_age($birthday_date)
{

    //Startdatum des JMCs; bitte jedes Jahr ändern ..
    $jmc_start_date = '2013/05/25';
    $birthday = ($birthday_date['tm_year']+1900) . '/' . ($birthday_date['tm_mon']+1) . '/' . $birthday_date['tm_mday'];

    list($years, $months, $days) = dateDifference($birthday, $jmc_start_date);
    return $years;
//     $jmc_end_date = new DateTime('2013-05-25');
//     $birthday = new DateTime();
//     $birthday->setTime(0, 0, 0);
//     $birthday->setDate($birthday_date['tm_year'] + 1900, $date['tm_mon']+1, $date['tm_mday']);
//     return $birthday->diff($jmc_end_date)->y;
}


function check_birthday($value)
{
    $date = strptime($value, '%d.%m.%Y');
    if ($date == false)
        return "Ungültiges Datum (Tag.Monat.Jahr)";

//     $age = get_jmc_age($date);
//     if ($age < 14)
//         return "Du bist leider zu jung für das Camp (Mindestalter: 14 Jahre).";
//     if ($age > 24)
//         return "Du bist leider zu alt für das Camp (Maximalalter: 24 Jahre).";
 
    return null;
}

function check_telephone($value)
{
    $allowed_characters = array('+', '/', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0');
    foreach (str_split($value) as $val)
    {
        if (!in_array($val, $allowed_characters))
            return "Ungültige Telefonnummer";
    }
    return null;
}

function check_zip($value)
{
    $allowed_characters = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0');
    foreach (str_split($value) as $val)
    {
        if (!in_array($val, $allowed_characters))
            return "Ungültige Postleitzahl";
    }
    return null;
}


function check_shelters($value)
{
    if (intval($value) == 0)
    {
        return "Ungültiger Wert!";
    }
    return null;
}


function check_comments($value)
{
    if (strlen($value) > 512)
        return "Zu lang (maximal 512 Zeichen).";
    return null;
}


function check_shelter_fields($form)
{
    $offer_field = $form->get_field("offers_shelter");
    $seek_field = $form->get_field("seeking_shelter");

    if ($offer_field->get_value() and $seek_field->get_value())
        return "Man kann nicht gleichzeitig Schlafplätze suchen und anbieten.";
    return null;
}


function check_parents_contact($form)
{
    $birthday_field = $form->get_field("birthday");
    $parents_contact_field = $form->get_field("parents_contact");

    $birthday_date = $birthday_field->get_value();

    $parents_contact = $parents_contact_field->get_value();

    $date = strptime($birthday_date, '%Y-%m-%d');
    $age = get_jmc_age($date);
    if ($age < 18 and $parents_contact == null)
    {
       $parents_contact_field->error_msg = "Bitte gib Kontaktdaten deines Erziehungsberechtigten an.";
       return "Bitte gib Kontaktdaten deines Erziehungsberechtigten an.";
    }
    return null;
}


function get_workshop_category($value)
{
    global $workshops;
    foreach($workshops as $category)
    {
        list($category_name, $workshop_names) = $category;
        if (array_key_exists($value, $workshop_names))
            return $category_name;
    }
    return null;
}

function check_workhops($form)
{
    $workshop_1 = $form->get_field("workshop_1")->get_value();
    $workshop_2 = $form->get_field("workshop_2")->get_value();
    $workshop_3 = $form->get_field("workshop_3")->get_value();

    if ($workshop_1 == $workshop_3 or $workshop_2 == $workshop_3)
        return "Ein Workshop darf nicht doppelt gewählt werden";

    $category_1 = get_workshop_category($workshop_1);
    $category_2 = get_workshop_category($workshop_2);
    $category_3 = get_workshop_category($workshop_3);
    if (is_null($category_1) or is_null($category_2) or is_null($category_3))
        return "Ungültiger Workshop";
    if ($category_1 == $category_2)
        return "Erstwunsch und Zweitwunsch dürfen nicht aus derselben Kategorie stammen.";

    return null;
}

if (isset($_REQUEST['token']))
    $token = $_REQUEST['token'];
else
    $token = md5(uniqid(uniqid(rand()), true));


//=======================Form definition========================================
// see forms_and_fields.php for implementation details


$form = new Form(array(new Chooser("gender", array(
                            "m" => "Männlich",
                            "w" => "Weiblich",
                       ), true),
                       new LimitedField("first_name", 256),
                       new LimitedField("last_name", 256),
                       new LimitedField("street", 256),
                       new ExactField("zip", 5),
                       new LimitedField("city", 128),
                       new LimitedField("email", 256, "email"),
                       new DateField("birthday"),
                       new TextArea("parents_contact"),
                       new LimitedField("telephone", 128),
                       new CategoryChooser("workshop_1", $workshops, false),
//                        new CategoryChooser("workshop_2", $workshops, true),
//                        new CategoryChooser("workshop_3", $workshops, true),
                       new CheckBox("meat", "meat", true),
                       new CheckBox("offers_shelter", "offers_shelter", false),
                       new NumberField("shelters"),
                       new CheckBox("seeking_shelter", "offers_shelter", false),
//                        new Chooser("tshirt_size", array(
//                             '' => 'kein T-Shirt',
//                             'S' => 'S',
//                             'M' => 'M',
//                             'L' => 'L',
//                             'XL' => 'XL')),
//                        new Chooser("girlie_size", array(
//                             '' => 'kein Girlie-Shirt',
//                             'S' => 'S',
//                             'M' => 'M',
//                             'L' => 'L')),
//                        new Chooser("pullover_size", array(
//                             '' => 'kein Pullover',
//                             'S' => 'S',
//                             'M' => 'M',
//                             'L' => 'L',
//                             'XL' => 'XL')),
//                        new Chooser ("pullover_color", array(
//                             's' => 'schwarz',
//                             'g' => 'dunkelgrün')),
//                        new CheckBox("no_mail", "no_mail", false),
//                        new CheckBox("allow_contact_info", "allow_contact_info", true),
                       new TextArea("comments"),
                ));

$form->get_field("email")->register_validator('check_email');
$form->get_field("birthday")->register_validator('check_birthday');
$form->get_field("shelters")->register_validator('check_shelters');
$form->get_field("shelters")->auto_validate = false;
$form->get_field("comments")->register_validator('check_comments');
$form->get_field("telephone")->register_validator('check_telephone');
$form->get_field("zip")->register_validator('check_zip');

// $form->register_validator('check_workhops');
$form->register_validator('check_shelter_fields');
$form->register_validator('check_parents_contact');


$labels = array("gender" => "Geschlecht",
                "first_name" => "Vorname",
                "last_name" => "Nachname",
                "street" => "Straße/Hausnummer",
                "zip" => 'Postleitzahl',
                "city" => "Wohnort",
                "email" => "E-Mail",
                "birthday" => "Geburtsdatum",
                "parents_contact" => "Kontaktdaten des Erziehungsberechtigten für den Notfall",
                "telephone" => "Telefonnummer",
                "workshop_1" => "Workshop",
                "workshop_2" => "Zweitwunsch",
                "workshop_3" => "Drittwunsch",
                "meat" => "Ich bin Fleischesser",
                "offers_shelter" => "Ich biete freie Schlafplätze an.",
                "shelters" => "Freie Schlafplätze",
                "seeking_shelter" => "Ich suche einen Schlafplatz.",
                "tshirt_size" => "Ich kaufe ein T-Shirt für 8€.",
                "girlie_size" => "Ich kaufe ein Girlie-Shirt für 8€.",
                "pullover_size" => "Ich kaufe einen Pullover für 20€.",
                "pullover_color" => "Pullover-Farbe",
                "no_mail" => "Ich möchte meine Anmeldebestätigung nicht per E-Mail, sondern per Post erhalten",
                "allow_contact_teamer" => "Meine Teamer dürfen mich per Mail kontaktieren",
                "allow_contact_association" => "Ich möchte Information meines lokalen Jugendpresseverbandes erhalten.",
                "allow_contact_info" => "Ich möchte in die JMC Infoliste eingetragen werden, worüber ich zum Nachtreffen eingeladen werde.",
                "comments" => "Bemerkungen",
);

$info_labels = array("birthday" => "(TT.MM.JJJJ)",
                     "parents_contact" => "bei Minderjährigen",
                     "tshirt_size" => "hellgrün",
                     "girlie_size" => "hellgrün",
                     "pullover_color" => '<span class="black">schwarz</span> oder <span class="darkgreen">dunkelgrün</span>',
                     "comments" => "Workshop-Teamer, Org",
                     "telephone" => "für Rückfragen"
);




$back = $_REQUEST['back'];
$accept = $_REQUEST['accept'];


//================================Form database logic===========================
    
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $form->update_values();

    if ($back)
    {
        $sqlstmt = "update jmc_registration set finished = 0 where token=?";
        $stmt = $mysqli->prepare($sqlstmt);
        $stmt->bind_param('s', $token);
        $stmt->execute();

        $sqlstmt = "select " .implode(",", array_map("map_name", $form->fields)) . " from jmc_registration where token=?";
        $stmt = $mysqli->prepare($sqlstmt);
        $stmt->bind_param('s', $token);
        $stmt->execute();


        $column_names = array_map("map_name", $form->fields);

        $data = bind_result_columns($stmt, $column_names);

        $res = $stmt->fetch();

        foreach ($form->fields as $field)
        {
            $field->special_set_value($data[$field->name]);
        }
        $stmt->close();
    }

    else if (!$accept) {

        $valid_form = $form->validate();
        if ($form->get_field("offers_shelter")->checked)
        {
            $valid_form &= $form->get_field("shelters")->validate();
        }

        if ($valid_form) 
        {
            $token_stmt = $mysqli->prepare("SELECT * FROM jmc_registration where token=?");
            $token_stmt->bind_param('s', $token);
            $token_stmt->execute();
            $token_stmt->store_result();
            $num_rows = $token_stmt->num_rows;
            $token_stmt->close();



            if ($num_rows)
            {
                $update = true;

                $name_values = implode(',', array_map("map_name_value", $form->fields));

                $sqlstmt = "UPDATE jmc_registration set finished = true, " . $name_values . " where token = ?";
                $stmt = $mysqli->prepare($sqlstmt);

                $values = array_map("map_value", $form->fields);

                $type_string = implode(array_map("map_type", $form->fields)) . 's';

                $values = array_merge(array("type_string" => $type_string), $values, array("token" => $token));
                bind_param_columns($stmt, $values);
                $stmt->execute();

                $stmt->close();
            }
            else
            {
                $update = false;

                $sqlstmt = "INSERT INTO jmc_registration (token, finished, " . implode(",", array_map("map_name", $form->fields)) . ") VALUES (?,?," . implode(",", array_map("map_question_mark", $form->fields)) . ")";
                $stmt = $mysqli->prepare($sqlstmt);

                $values = array_map("map_value", $form->fields);
                $type_string = 'si' . implode(array_map("map_type", $form->fields));
                $finished = true;

                $values = array_merge(array("type_string" => $type_string, "token" => $token, "finished" => $finished), $values);
                bind_param_columns($stmt, $values);

                $stmt->execute();


                $stmt->close();
            }
        }
    }

}


?>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
    <link rel="stylesheet" type="text/css" href="style.css" />
</head>
<body>


<?php
    $sqlstmt = "SELECT finished from jmc_registration where token=?";
    $stmt = $mysqli->prepare($sqlstmt);
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $stmt->bind_result($finished);
    $stmt->fetch();
    $stmt->close();
    if (!$finished or $back) {
?>
<h1>Anmeldung Jugendmediencamp 2013</h1>
Um die Anmeldung abzuschließen, benötigst du einen Drucker, da wir eine Unterschrift brauchen.<br/>
Wenn du partout keinen Drucker zur Hand hast, schicken wir dir natürlich auch gerne ein "analoges" Formular zu.<br/>
Schreib einfach an <a href="mailto:info@jugendmediencamp.de">info@jugendmediencamp.de</a>!
<?php
        if ($form->error_msg)
        {
           ?><br/><span class="error"><? echo $form->error_msg; ?></span><?
        }
?>

    <form id="registration_form" action="registration_form_teamers.php" method="post">
        <table>
        <?php
        foreach ($form->fields as $field)
        {
        ?>
        <tr class="field <? echo $field->name ?>">
            <td class="label">
                <label for="<? echo $field->name ?>"><? echo $labels[$field->name]?></label>
                <? if (array_key_exists($field->name, $info_labels)) { ?>
                    <br/><span class="info"><? echo $info_labels[$field->name]; ?></span>
                <? } ?>
            </td>
            <td class="value">
                <? echo $field->render(); ?>
                <span class="error"><? echo $field->render_error(); ?></span>
            </td>
        </tr>
        <?php
        }
        ?>
        </table>
        <input name="token" type="hidden" value="<?php echo $token ?>"/></br>
        <input type="submit">
    </form>

    <span class="required_info">Felder mit * werden benötigt.</span>
    <?php } else if (!$accept) {

        $sqlstmt = "select " .implode(",", array_map("map_name", $form->fields)) . " from jmc_registration where token=?";
        $stmt = $mysqli->prepare($sqlstmt);
        $stmt->bind_param('s', $token);
        $stmt->execute();


        $column_names = array_map("map_name", $form->fields);

        $data = bind_result_columns($stmt, $column_names);

        $res = $stmt->fetch();

        foreach ($form->fields as $field)
        {
            $field->special_set_value($data[$field->name]);
        }
        $stmt->close();

    ?>
    <table>
    <tr style="vertical-align: top;">
    <td>
    
    <h1>Übersicht</h1>
    <table class="overview">
    <?php
    foreach ($form->fields as $field)
    {
    ?>
    <tr class="field">
        <td class="label">
            <label for="<? echo $field->name ?>"><? echo $labels[$field->name]?></label>
        </td>
        <td class="value">
            <? echo htmlspecialchars($field->render_value()); ?>
        </td>
    </tr>
    <?php
    }
    ?>
    </table>

    <form action="registration_form_teamers.php" method="post">
        <input type="submit" value="Eingaben ändern">
        <input name="token" type="hidden" value="<?php echo $token ?>"/></br>
        <input name="back" type="hidden" value="true"/>
    </form>

    <h1> Teilnahmebedingungen </h1>
     <ol>
    <li>Das Jugendmediencamp findet vom 17. Mai 2013 18:00 Uhr bis 21. Mai 2013 12:00 Uhr statt.</li>
<li>Das Jugendmediencamp ist offen für Jugendliche von 14 bis 24 Jahren.</li>
<li>Veranstaltungsort ist Zeltplatz “Naturfreund” in Kratzeburg. Die Zeltplatzordnung hängt vor Ort aus und ist einzuhalten.</li>
<li>Der Teilnahmebeitrag beträgt 35 Euro. Der Betrag wird vor Ort in bar bezahlt. Finanzkräftigere Teilnehmer können die Veranstaltung durch einen höheren Unkostenbeitrag unterstützen.</li>
<li>Die Teilnahme am Jugendmediencamp verpflichtet zur Teilnahme an den Workshops und den Diskussionsrunden.</li>
<li>Die Teilnehmer sind für ihr Handeln selbst verantwortlich. Minderjährige werden durch die Unterschrift der Erziehungsberechtigten von einer ständigen Beaufsichtigung durch die Organisatoren des Jugendmediencamps befreit. Die Erziehungsberechtigten gestatten das unbeaufsichtigte Baden im See. Ein absolutes Bade- und Schwimmverbot besteht bei Nacht, Gewitter, Sturm sowie bei Sturmwarnung.</li>
<li>Freizeit darf nach eigenem Ermessen gestaltet werden; das Verlassen des Geländes ist nur in kleinen Gruppen erlaubt.</li>
<li>Die Teilnehmer und ihre Erziehungsberechtigten erklären, dass auf dem Jugendmediencamp entstandenes Bild- und Tonmaterial, sowie Texte für Werbe- und Pressezwecke verwendet werden kann. Dies gilt auch für Abbildungen von Einzelpersonen.</li>
<li>Alkohol ist unerwünscht. Rauchen ist nur direkt am Lagerfeuer und ausschließlich volljährigen Teilnehmern gestattet.</li>
<li>Wer den Anweisungen des Veranstalters wiederholt nicht Folge leistet, kann auf eigene Kosten nach Hause geschickt werden.</li>
<li>Wer eine Anmeldung abgeschickt hat und trotzdem nicht kommen kann, muss bis zum 10. Mai 2013 absagen. Die Absage muss schriftlich erfolgen. Es gilt das Datum des Poststempels. Wer nach diesem Termin absagt und keinen Ersatzteilnehmer stellt, muss eine Ausfallgebühr von 40 Euro bezahlen.</li>
<li>Wir, der Veranstalter, behalten uns Änderungen am Programm, eine Absage sowie die Nichtzulassung von Anmeldungen vor.</li>
<li>Das Jugendmediencamp findet auch bei Regenwetter statt. Schlechtes Wetter ist kein Grund für eine Absage seitens der Teilnehmer.</li>
<li>Die Teilnehmer führen die nötigen Personal- und Gesundheitsdokumente mit sich.</li>
<li>Wir übernehmen keine Haftung für Diebstahl und Beschädigung mitgeführter Gegenstände.</li>
<li>Das Jugendmediencamp wird vom Jugendpresseverband Brandenburg e.V., Schulstr. 9, 14482 Potsdam veranstaltet.</li>
<li>Um dich mit Informationen aus der Jugendmedienwelt auf dem Laufenden zu halten darf dich dein lokaler Jugendpresseverband über aktuelle Angebote informieren.</li>
<li>Der Veranstalter übernimmt die Kosten für Zeltplatz, Verpflegung und Programm. Mitzubringen sind Zelte, Schlafsäcke, wetterfeste Kleidung und persönliche Gegenstände.</li>
<li>Die Anmeldebestätigungen mit Workshopzuteilung werden spätestens am 5. Mai 2013 verschickt. Eine Erstwunschzuteilung kann leider nicht garantiert werden.</li>
<li>Eine An- und Abreise per Bahn ist ausdrücklich erwünscht. Wer trotzdem mit dem Auto anreist und auf dem Gelände parkt, muss pro Tag 10 Euro Parkgebühren bezahlen.</li>
    </ol>


    <form action="registration_form_teamers.php" method="post">
        <input name="accept" type="hidden" value="true"/>
        <input name="token" type="hidden" value="<?php echo $token ?>"/>
        <input type="submit" value="Akzeptieren">
    </form>

    <?php } else { 
    $sqlstmt = "UPDATE jmc_registration SET accepted = true WHERE token=?";
    $stmt = $mysqli->prepare($sqlstmt);
    $stmt->bind_param('s', $token);
    $stmt->execute();

    $sqlstmt = "select " .implode(",", array_map("map_name", $form->fields)) . " from jmc_registration where token=?";
    $stmt = $mysqli->prepare($sqlstmt);
    $stmt->bind_param('s', $token);
    $stmt->execute();


    $column_names = array_map("map_name", $form->fields);

    $data = bind_result_columns($stmt, $column_names);

    $res = $stmt->fetch();

    foreach ($form->fields as $field)
    {
        $field->special_set_value($data[$field->name]);
    }
    $stmt->close();

    $link = "http://jugendmediencamp.de/registration/generate_pdf.php?token=" . $token;

    $pdf_filename = "anmeldung_jmc.pdf";
    $pdf_data = generate_pdf($form);

    $email_text  = "Hallo, " . $data["first_name"] . ",\n";
    $email_text .= "\n";
    $email_text .= "vielen Dank für deine Anmeldung zum Jugendmediencamp 2013.\n";
    $email_text .= "Deine Anmeldung ist schon fast am Ziel. Einfach die angehängte PDF-Datei\n";
    $email_text .= "ausdrucken, unterschreiben und an\n";
    $email_text .= "\n";
    $email_text .= "Jugendmediencamp\n";
    $email_text .= "Schulstr. 9\n";
    $email_text .= "14482 Potsdam\n";
    $email_text .= "\n";
    $email_text .= "schicken.\n";
    $email_text .= "\n";
    $email_text .= "Wichtig: Wenn du noch nicht 18 bist, müssen deine Eltern auch unterschreiben.\n";
    $email_text .= "Erst wenn wir das unterschriebene Formular erhalten haben, bist du angemeldet. \n";
    $email_text .= "Also schnell ausdrucken und bis 19. April 2013 abschicken.\n";
    $email_text .= "\n";
    $email_text .= "Erst nach Anmeldeschluss teilen wir die Workshops zu. So hat jeder die gleiche Chance,\n";
    $email_text .= "einen Platz in seinem Wunsch-Workshop zu bekommen. Bis 5. Mai schicken wir dir\n";
    $email_text .= "dann eine Anmeldebestätigung, in der dir dein Workshop mitgeteilt wird.\n";
    $email_text .= "\n";
    $email_text .= "Solltest du nicht kommen können, musst du bis zum 10. Mai 2013\n";
    $email_text .= "absagt haben, ansonsten ist eine Ausfallgebühr von 40€ zu zahlen oder\n";
    $email_text .= "ein Krankenschein vorzulegen.\n";
    $email_text .= "\n";
    $email_text .= "Wenn du weitere Fragen hast, wende dich bitte an info@jugendmediencamp.de \n";
    $email_text .= "oder 030 / 577 034 120.\n";
    $email_text .= "\n";
    $email_text .= "Viele Grüße und bis Pfingsten,\n";
    $email_text .= "dein JMC-Org-Team";



    $additional_headers = build_headers($email_text, $pdf_filename, $pdf_data);

    $subject = "Anmeldung zum JMC 2013";

    if (!mail($data["email"], '=?UTF-8?B?'.base64_encode($subject).'?=', "", $additional_headers))
    {
        echo "Sending mail to " . $data["email"] . " failed!";
    }
    

    ?>
    Eine E-Mail mit einer PDF zum Ausfüllen wurde an deine E-Mailadresse versandt. Bitte schaue auch im Spam-Ordner nach.
    <?php } ?>
</body>

<?php
$mysqli->close();
?>

</html>

