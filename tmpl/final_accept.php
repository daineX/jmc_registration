<form action="registration_form.php" method="post">
    <input name="accept" type="hidden" value="true"/>
    <input name="token" type="hidden" value="<?php echo $token ?>"/>
    <input type="submit" value="Akzeptieren">
</form>