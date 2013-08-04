<?php
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

<form action="registration_form.php" method="post">
    <input type="submit" value="Eingaben ändern">
    <input name="token" type="hidden" value="<?php echo htmlspecialchars($token) ?>"/></br>
    <input name="back" type="hidden" value="true"/>
</form>
