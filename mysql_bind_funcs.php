
<?php

function bind_result_columns($stmt, $columns)
{
    $data = array() ; // Array that accepts the data.
    $params = array() ; // Parameter array passed to 'bind_result()'
    foreach($columns as $col_name)
    {
    // Assign the fetched value to the variable '$data[$name]'
        $params[] =& $data[$col_name] ;
    } 
    call_user_func_array(array($stmt, "bind_result"), $params);

    return $data;
}


function bind_param_columns($stmt, $data)
{
    $params = array() ; // Parameter array passed to 'bind_result()'
    foreach(array_keys($data) as $col_name)
    {
    // Assign the fetched value to the variable '$data[$name]'
        $params[] =& $data[$col_name];
    } 
    call_user_func_array(array($stmt, "bind_param"), $params);
}



?>

