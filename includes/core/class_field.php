<?php

// test static library, actual code in finction.php

Class Field{
    
    
    public static function fields_normalization(&$data, $array_fields, &$error_data=null) {
        foreach ($array_fields as $field) {
            field_normalization($data, $field, $error_data);
        }
    }

    public static function field_normalization(&$data, $field, &$error_data=null) {
        // check
        $value = $data[$field] ?? ( is_scalar($data) ? $data: null );
        if (empty($value) or !is_scalar($value)) return null;
        $error_string='';
        // normalization
        switch ($field) {
            case 'phone':
                $value = preg_replace('~[^\d]+~', '', $value);
                if ( false === strpos('78', substr($value, 0, 1)) ) add_param_string($error_string,'must start with 7 or 8');
                if ( strlen($value)<>11 ) add_param_string($error_string,'must be 11 digits');
                break;

            case 'email':
                $value = strtolower(trim($value));
                break;
                
            default:
                return null;
        }
        // error
        if (!empty($error_string)){
            add_param_string($error_string,"value=$value");
            $error_data[$field] = $error_string;
            $value = null;
        }
        // set
        if (isset($data[$field])) $data[$field] = $value;
        else $data = $value;
        
        return $value;
    }

    public static function fields_setting(&$array_source, $non_empty_fields, $can_empty_fields, &$error_data, $delemiter=', ', $normalization_fields=null) {
        //normalization
        $error_data_normalization = [];
        if (!empty($normalization_fields)){
            fields_normalization($array_source, $normalization_fields, $error_data_normalization);        
        }
        // vars
        $set_string = ''; $fields_string = ''; $values_string = '';
        $all_fields = array_merge( $non_empty_fields, $can_empty_fields );
        // setting
        foreach ($all_fields as $field) {

            if (isset($array_source[$field])){ //array_key_exists with null
                
                $field_value = $array_source[$field];
                if ( empty($field_value) and in_array($field, $non_empty_fields) ) {
                    $error_data[$field] = 'empty field';
                    continue;
                }

                $field_value = flt_input( $field_value ); // DB::connect()->quote( $field_value )
                if ($delemiter==="VALUES"){
                    add_param_string($fields_string, $field);
                    add_param_string($values_string, "'$field_value'");
                } else {
                    add_param_string($set_string, "$field='$field_value'", $delemiter);
                }
            };
        }
        $error_data = $error_data_normalization + $error_data; //by priority
        if ($delemiter==="VALUES") return $fields_string ? "($fields_string) VALUES ($values_string)" : $fields_string;
        return $set_string;
    }

    public static function fields_error($call_metod, $error_data, $fields_setting_string) {
        if (!empty($error_data))           return error_response(1003, "$call_metod : one of the parameters was missing or was passed in the wrong format.", $error_data);
        if (empty($fields_setting_string)) return error_response(1003, "$call_metod : the request does not contain any of the fields.");
        return false;
    }


}