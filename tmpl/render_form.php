<h1>Anmeldung Jugendmediencamp 2014</h1>
Um die Anmeldung abzuschließen, benötigst du einen Drucker, da wir eine Unterschrift brauchen.<br/>
Wenn du partout keinen Drucker zur Hand hast, schicken wir dir natürlich auch gerne ein "analoges" Formular zu.<br/>
Schreib einfach an <a href="mailto:info@jugendmediencamp.de">info@jugendmediencamp.de</a>!
<?php
        if ($form->error_msg)
        {
           ?><br/><div class="error"><? echo $form->error_msg; ?></div><?
        }
?>
<form id="registration_form" action="registration_form.php" method="post">
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
            <div class="error"><? echo $field->render_error(); ?></div>
        </td>
    </tr>
    <?php
    }
    ?>
    </table>
    <input name="token" type="hidden" value="<?php echo htmlspecialchars($token) ?>"/></br>
    <input type="submit">
</form>

<span class="required_info">Felder mit * werden benötigt.</span>