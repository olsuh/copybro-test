<?php

function class_autoload($api = false) {
    if ($api) {
        spl_autoload_register(function($class_name) {
            require('./../includes/core/class_'.strtolower($class_name).'.php');
        });
    } else {
        spl_autoload_register(function($class_name) {
            require('./includes/core/class_'.strtolower($class_name).'.php');
        });
    }
}

function flt_input($var) {
    return str_replace(['\\', "\0", "'", '"', "\x1a", "\x00"], ['\\\\', '\\0', "\\'", '\\"', '\\Z', '\\Z'], $var);
}

function generate_rand_str($length, $type = 'hexadecimal') {
    // vars
    $str = '';
    if ($type == 'decimal') $chars = '0123456789';
    else if ($type == 'password') $chars = ['0123456789', 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz'];
    else $chars = 'abcdef0123456789';
    // generate
    for ($i = 0; $i < $length; $i++) {
        $microtime = round(microtime(true));
        if ($type != 'password') {
            srand($microtime + $i);
            $size = strlen($chars);
            $str .= $chars[rand(0, $size-1)];
        } else {
            $l = rand(-3, -1);
            $sub = substr($str, $l);
            if (!preg_match('~[0-9]~', $sub)) $chars_a = $chars[0];
            else if (!preg_match('~[A-Z]~', $sub)) $chars_a = $chars[1];
            else $chars_a = $chars[2];
            srand($microtime + $i);
            $size = strlen($chars_a);
            $str .= $chars_a[rand(0, $size-1)];
        }
    }
    // output
    return $str;
}

function error_response($code, $msg, $data = []) {
    $http_code = 400;
    header($_SERVER['SERVER_PROTOCOL'].' '.$http_code.' Error', true, $http_code);
    $result['error_code'] = $code;
    $result['error_msg'] = $msg;
    if ($data) $result['error_data'] = $data;
    return $result;
}

function response($response) {
    $response = !isset($response['error_code']) ? ['success'=>'true', 'response'=>$response] : ['success'=>'false', 'error'=>$response];
    exit(json_encode($response, JSON_UNESCAPED_UNICODE));
}

function call($method_allow, $method_use, $data, $callback, $response_out_mode = 1) {
    if ($method_allow != $method_use) response(error_response(1003, 'Application authorization failed: HTTP method is not supported.'));
    $response = $data ? $callback($data) : $callback();
    // out mode: 1 - all, 0 - error, -1 - none
    if ( $response_out_mode === 1 or $response_out_mode === 0 and isset($response['error_code']) ) response($response);
    return $response;
}

function phone_formatting($phone) {
    if (preg_match('~^[78][\d]{10}$~', $phone)) $phone = preg_replace('~^([78])([\d]{3})([\d]{3})([\d]{2})([\d]{2})$~', '+$1 ($2) $3-$4-$5', $phone);
    return $phone;
}

function add_param_string(&$data_string, $parametr, $delemiter=', ') {
    if (!empty($data_string)) $data_string .= $delemiter;
    $data_string .= $parametr;
    return $data_string;
}

function fields_normalization(&$data, $array_fields, &$error_data=null) {
    foreach ($array_fields as $field) {
        field_normalization($data, $field, $error_data);
    }
}

function field_normalization(&$data, $field, &$error_data=null) {
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

function fields_setting(&$array_source, $non_empty_fields, $can_empty_fields, &$error_data, $delemiter=', ', $normalization_fields=null) {
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

function fields_error($call_metod, $error_data, $fields_setting_string) {
    if (!empty($error_data))           return error_response(1003, "$call_metod : one of the parameters was missing or was passed in the wrong format.", $error_data);
    if (empty($fields_setting_string)) return error_response(1003, "$call_metod : the request does not contain any of the fields.");
    return false;
}

function json_decode_w_throw(string $json, ?bool $associative = null, int $depth = 512, int $flags = 0 )
{
    try {
        $obj = json_decode($json, $associative, $depth, $flags);
    } catch (\Throwable $th) {
        //response(error_response(1003, "json_decode: {$th->getMessage()}", ['json' => $json] ));
    }
    $error_massege = isset($th) ? $th->getMessage() : ( json_last_error() ? json_last_error_msg() : null );
    if ($error_massege) response(error_response(1003, "json_decode: $error_massege", ['json' => $json] ));
    return $obj;
}
