<?php
$yandex_iot_client_id="";
$yandex_iot_client_secret="";
$yandex_iot_file="iot_yandex_token.json";

function universal_curl($url,$headers,$data = "",$method = ""){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    if($method!=""){
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    }
    if($data!=""){
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }
    curl_setopt($ch, CURLOPT_FAILONERROR, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_VERBOSE, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $result = curl_exec($ch);
    $result = json_decode($result,true);
    curl_close($ch);
    return $result;
}

function universal_yandex_iot($function,$headers_add = array(),$data = "",$method = ""){
    $iot_api_url="https://api.iot.yandex.net";
    $url=$iot_api_url.$function;
    $headers=array_merge(array("Authorization: Bearer ".get_yandex_iot_token()),$headers_add);
    return(universal_curl($url,$headers,$data,$method));
}

function get_yandex_iot_power_state($device_id){
    $function="/v1.0/devices/".$device_id;
    $result=universal_yandex_iot($function);
    if(isset($result["capabilities"])){
        foreach($result["capabilities"] as $capability){
            if($capability["type"]=="devices.capabilities.on_off"){
                if($capability["state"]["value"]==""){
                    return(0);
                }elseif($capability["state"]["value"]=="1"){
                    return(1);
                }else{
                    return(-1);
                }
            }
        }
    }else{
        return(-1);
    }
}

function get_yandex_iot_info(){
    $function="/v1.0/user/info";
    $result=universal_yandex_iot($function);
    return($result);
}

function yandex_iot_power_change($device_id,$state){
    $function="/v1.0/devices/actions";
    $headers=["Content-Type: application/json"];
    $data='{"devices":[{"id":"'.$device_id.'","actions":[{"type":"devices.capabilities.on_off","state":{"instance":"on","value":'.$state.'}}]}]}';
    $result=universal_yandex_iot($function,$headers,$data);
    if(isset($result["status"])&&$result["status"]=="ok"){
        return(true);
    }else{
        return(false);
    }
}

function get_yandex_iot_token(){
    global $yandex_iot_file;
    $token=file_get_contents($yandex_iot_file);
    $token_arr=json_decode($token,true);
    if($token_arr["expires"]-time()<20995200){
        update_yandex_iot_token();
    }
    return($token_arr["access_token"]);
}

function update_yandex_iot_token(){
    global $yandex_iot_file,$yandex_iot_client_id,$yandex_iot_client_secret;
    $token=file_get_contents($yandex_iot_file);
    $token_arr=json_decode($token,true);
    $url="https://oauth.yandex.ru/token";
    $auth=base64_encode($yandex_iot_client_id.":".$yandex_iot_client_secret);
    $headers=["Authorization: Basic ".$auth,"application/x-www-form-urlencoded"];
    $data="grant_type=refresh_token&refresh_token=".$token_arr["refresh_token"];
    $response=universal_curl($url,$headers,$data);
    if(isset($response["access_token"])){
        $response["expires"]=$response["expires_in"]+time();
        if(file_put_contents($yandex_iot_file, json_encode($response))){
            return(true);
        }
    }else{
        return(false);
    }
}
?>
