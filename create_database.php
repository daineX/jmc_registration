<?php

require("mysql_cred.php");


$sqlstmt = "CREATE TABLE jmc_registration (
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
) default charset=utf8;";
$stmt = $mysqli->prepare($sqlstmt);
$stmt->execute();

?>