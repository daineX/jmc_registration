<?php

/* Form and field definitions */

class Form 
{


    function __construct($fields = array())
    {
        foreach ($fields as $field)
        {
            $this->add_field($field);
        }
        $this->validation_callbacks = array();
        $this->error_msg = null;
    }


    function register_validator($validator)
    {
        array_push($this->validation_callbacks, $validator);
    }


    function validate()
    {
        $valid = true;
        $this->error_msg = null;

        foreach($this->validation_callbacks as $callback)
        {
            $error_msg = call_user_func($callback, $this);
            if ($error_msg)
            {
                $this->error_msg = $error_msg;
                $valid = false;
                break;
            }
        }

        foreach ($this->fields as $field)
        {
            if ($field->auto_validate)
                $valid &= $field->validate();
        }
        return $valid;
    }


    function add_field($field)
    {
        $this->fields[$field->name] = $field;
    }

    function get_field($field_name)
    {
        return $this->fields[$field_name];
    }

    function update_values()
    {
        foreach ($this->fields as $field)
        {
            $field->set_value($_POST[$field->name]);
        }
    }

}



class Field
{

    function __construct($name, $type = "text", $value = null)
    {
        $this->name = $name;
        $this->type = $type;
        $this->value = $value;
        $this->validation_callbacks = array();
        $this->error_msg = null;
        $this->auto_validate = true;
    }


    function validate()
    {
        foreach ($this->validation_callbacks as $callback)
        {
            $error_msg = call_user_func($callback, $this->value);
            if ($error_msg)
            {
                $this->error_msg = $error_msg;
                return False;
            }
        }

        $this->error_msg = null;
        return True;
    }

    function register_validator($validator)
    {
        array_push($this->validation_callbacks, $validator);
    }

    function set_value($value)
    {
        $this->value = $value;
    }

    function special_set_value($value)
    {
        $this->set_value($value);
    }

    function render()
    {
        return '<input id="' . $this->name . '" name="' . $this->name . '" type="' . $this->type . '" value="' . htmlspecialchars($this->value) . '"/>';
    }

    function render_error()
    {
        if ($this->error_msg)
            return $this->error_msg;
    }

    function render_value()
    {
        return $this->value;
    }

    function get_value()
    {
        return $this->value;
    }

    function sql_type()
    {
        return "s";
    }
}


class NotEmptyField extends Field 
{


    function validate()
    {
        if(!parent::validate())
            return False;
        if (strlen($this->value) == 0)
        {   
            $this->error_msg = "Eingabe benötigt.";
            return False;
        }

        return True;
    }

    function render()
    {
        return '<input id="' . $this->name . '" name="' . $this->name . '" type="' . $this->type . '" value="' . htmlspecialchars($this->value) . '"/>*';
    }

}


class LimitedField extends NotEmptyField
{
    function __construct($name, $limit=0, $type = "text", $value = null)
    {
        parent::__construct($name, $type, $value);
        $this->limit = $limit;
    }

    function validate()
    {
        if(!parent::validate())
            return False;
        if ($this->limit == 0)
            return True;
        if (strlen($this->value) > $this->limit)
        {
            $this->error_msg = "Zuviele Zeichen (" . $this->limit . " maximal).";
            return False;
        }
        return True;
    }
}


class ExactField extends LimitedField
{

    function validate()
    {
        if(!parent::validate())
            return False;
        if ($this->limit == 0)
            return True;
        if (strlen($this->value) != $this->limit)
        {
            $this->error_msg = "Genau " . $this->limit . " Zeichen erwartet.";
            return False;
        }
        return True;
    }

}


class Chooser extends Field
{
    
    function __construct ($name, $options, $required=false)
    {
        parent::__construct($name);
        $this->options = $options;
        $this->required = $required;
    }

    
    function render()
    {
        $result = '<select id="'. $this->name . '" name="' . $this->name . '" size="1">';
        foreach ($this->options as $option_value => $option_name)
        {
            if ($option_value == $this->value)
                $result .= '<option selected="selected" value="' . $option_value .'">' . $option_name . '</option>';
            else
                $result .= '<option value="' . $option_value .'">' . $option_name . '</option>';
        }
        $result .= '</select>';
        if ($this->required)
            $result .= '*';
        return $result;
    }

    function render_value()
    {
        return $this->options[$this->value];
    }

    function validate()
    {
        if(!parent::validate())
            return False;
        if (!array_key_exists($this->value, $this->options))
        {
            $this->error_msg = "Ungültiger Wert.";
            return False;
        }
        return True;
    }
}


class CategoryChooser extends Field
{
    
    function __construct ($name, $categories, $required=false)
    {
        parent::__construct($name);
        $this->categories = $categories;
        $this->required = $required;
    }

    
    function render()
    {
        $result = '<select id="'. $this->name . '" name="' . $this->name . '" size="1">';
        foreach ($this->categories as $category)
        {
            $result .= '<optgroup id="' . $this->name . '_' . $category[0] .'" label="' . $category[0] . '">';
            foreach ($category[1] as $option_value => $option_name)
            {
                if ($option_value == $this->value)
                    $result .= '<option selected="selected" value="' . $option_value .'">' . $option_name . '</option>';
                else
                    $result .= '<option value="' . $option_value .'">' . $option_name . '</option>';
            }
            $result .= '</optgroup>';
        }
        $result .= '</select>';
        if ($this->required)
            $result .= '*';
        return $result;
    }

    function _get_category($value)
    {
        foreach($this->categories as $category)
        {
            list($category_name, $element_names) = $category;
            if (array_key_exists($value, $element_names))
                return $category_name;
        }
        return null;
    }

    function _get_element_name($value)
    {
        foreach($this->categories as $category)
        {
            list($category_name, $element_names) = $category;
            if (array_key_exists($value, $element_names))
                return $element_names[$value];
        }
        return null;
    }

    function render_value()
    {
        return $this->_get_element_name($this->value);
    }

    function validate()
    {
        if(!parent::validate())
            return False;
        if (strlen($this->value) == 0 and $this->required)
        {   
            $this->error_msg = "Eingabe benötigt.";
            return False;
        }

        return True;
    }
}


class CheckBox extends Field
{
    function __construct($name, $value = null, $checked = false)
    {
        parent::__construct($name, "checkbox", $value);
        $this->checked = $checked;
    }

    function set_value($value)
    {
        $this->checked = ($value == $this->value);
    }

    function special_set_value($value)
    {
        $this->checked = $value;
    }

    function render()
    {
        if ($this->checked)
            return '<input id="' . $this->name . '" name="' . $this->name . '" type="' . $this->type . '" value="' . htmlspecialchars($this->value) . '" checked="checked"/>';
        else
            return parent::render();
    }

    function render_value()
    {
        if ($this->checked)
            return "Ja";
        else
            return "Nein";
    }

    function get_value()
    {
        return $this->checked;
    }

    function sql_type()
    {
        return "i";
    }
}

class NumberField extends Field
{
    function validate()
    {
        if(!parent::validate())
            return False;
        if (intval($this->value) == 0)
        {   
            $this->error_msg = "Anahl ungültig";
            return False;
        }
        return True;
    }

    function get_value()
    {
        return intval($this->value);
    }


    function sql_type()
    {
        return "i";
    }
}

class DateField extends NotEmptyField
{

    function get_value()
    {
        $ftime = strptime($this->value, '%d.%m.%Y');
        $timestamp = mktime(
                            $ftime['tm_hour'],
                            $ftime['tm_min'],
                            $ftime['tm_sec'],
                            1 ,
                            $ftime['tm_yday'] + 1,
                            $ftime['tm_year'] + 1900
                        ); 
        return  strftime("%Y-%m-%d", $timestamp);
    }

    function special_set_value($value)
    {
        $ftime = strptime($value, "%Y-%m-%d");
        $timestamp = mktime(
                            $ftime['tm_hour'],
                            $ftime['tm_min'],
                            $ftime['tm_sec'],
                            1 ,
                            $ftime['tm_yday'] + 1,
                            $ftime['tm_year'] + 1900
                        ); 
        $this->value =  strftime('%d.%m.%Y', $timestamp);
    }
}

class TextArea extends Field
{

    function __construct($name, $cols = null, $rows = null, $value = null)
    {
        $this->name = $name;
        $this->type = "";
        $this->value = $value;
        $this->validation_callbacks = array();
        $this->error_msg = null;
        $this->auto_validate = false;
        if ($cols)
            $this->cols = $cols;
        else
            $this->cols = 40;
        if ($rows)
            $this->rows = $rows;
        else
            $this->rows = 5;
    }


    function render()
    {
        return '<textarea name="' . $this->name . '" cols="' . $this->cols . '" rows="' . $this->rows . '">' . htmlspecialchars($this->value) . '</textarea>';
    }

}

