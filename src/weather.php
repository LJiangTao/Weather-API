<?php /** @noinspection ALL */
// $date = $_POST['time'];
$date =$_POST['date'];
$city =$_POST['city'];
$mysqli = @new mysqli("localhost","admin","123456","weatherdatabase");//mysqli("服务器地址"，"数据库用户名","数据库密码","数据库名称");
$mysqli -> set_charset('utf8');
$city_id = 'SELECT citycode from city_id where qu like \'%' . $city . '%\'';//筛选出日期与时间
$str1=null;
Main();

function UpdateHtmlFile(){
    global $mysqli,$date,$str1,$city_id;
    $citycode = mysqli_fetch_assoc($mysqli->query($city_id))['citycode'];//获取citycode字符串
    $select = 'SELECT date,city from city_weather where date = \''.$date.'\' and citycode = \''.$citycode.'\'' ;//筛选出日期与时间
    if (($mysqli->query($select))->num_rows) {//判断数据库存在相同日期的值
        //以下为提交信息至前端

        $get_city_index_db =  'select iname,ivalue,detail from city_index where citycode = \''.$citycode.'\'' ;
        $res_index = $mysqli->query($get_city_index_db);
        //获取城市各项指数

        $get_city_weather_db = 'select * from city_weather where  date = \''.$date.'\' and citycode = \''.$citycode.'\'' ;
        $res_normal = $mysqli->query($get_city_weather_db);
        //获取城市当天天气情况

        $get_city_weathe_24_db = 'select time,weather,temp from city_hour_detail where citycode = \''.$citycode.'\'' ;
        $res_24 = $mysqli->query($get_city_weathe_24_db);
        //获取城市24个小时的气温情况

        $get_city_week_detail =  'select * from city_week_detail where   citycode = \''.$citycode.'\'' ;
        $res_week = $mysqli->query($get_city_week_detail);
        //获取城市一周情况
        function while_mysqli_fetch_all($res){
            $rows =  null;
            while ($rs = mysqli_fetch_all($res)) {
                $rows  = $rs;
            };
            return $rows;
        };
        function while_mysqli_fetch_assoc($res){
            $rows =  null;
            while ($rs = mysqli_fetch_assoc($res)) {
                $rows  = $rs;
            };
            return $rows;
        };
//
        $result['status'] = "0";
        $Index['index']= while_mysqli_fetch_all($res_index);
        $Index['24']= while_mysqli_fetch_all($res_24);
        $Index['week']= while_mysqli_fetch_all($res_week);
        $Index['normal']= while_mysqli_fetch_assoc($res_normal);
        $result['result']=$Index;
        ;

//    for ($i = 0;$i<sizeof($rows);$i++){
//        foreach ($rows[$i] as $key => $value){
//            $rows [] = $rs;
//        };
//    };
        $enJson = json_encode($result);
        echo $enJson;
    }else{
        Main();
    }
}
///上方为刷新前端数据
function Main(){
    global $mysqli, $date,$city,$Json,$str1,$city_id;
    $citycode = mysqli_fetch_assoc($mysqli->query($city_id))['citycode'];//获取citycode字符串
    $select = 'SELECT date,city from city_weather where date = \'' . $date . '\' and citycode = \'' . $citycode . '\'';//筛选出日期与时间
    if ((($mysqli->query($city_id))->num_rows)!==1){
        $str['status'] = "111";
        echo json_encode($str);
        exit();
    }else{
        if (($mysqli->query($select))->num_rows) {
            $str1 = file_get_contents('doc/json.json');
            $Json = json_decode($str1, true);
            week($Json);
        } else {

            $str1 = ReflushDatabase($citycode);
//            $str1 = file_get_contents('doc/json.json');
            $Json = json_decode($str1, true);
                    file_put_contents('doc/json.json',$str1);
            CheckStatus($Json);
            DateBase($Json);
            Index($Json);
            hours($Json);
            week($Json);
        }
    }
    UpdateHtmlFile();
}
function CheckStatus($Json){
    if ($Json['status'] == "202"){
        $res['status'] ='202';
        echo json_encode($res);
        exit();
    }else if ($Json['status'] == "203"){
        $res['status'] ='203';
        echo json_encode($res);
        exit();
    }else if ($Json['status'] == "210"){
        $res['status'] ='210';
        echo json_encode($res);
        exit();
    }else if ($Json['status'] == "0"){

    }
}//查询接口返回值是否存在错误,错误则直接返回错误代码至前端,并结束运行;


function ReflushDatabase($city){
    $host = "https://jisutqybmf.market.alicloudapi.com";
    $path = "/weather/query";
    $method = "GET";
    $appcode = "b04857c141ca49be8b74bd401238d8b8";
    $headers = array();
    array_push($headers, "Authorization:APPCODE " . $appcode);
    $querys = "citycode=".$city;
    $bodys = "";
    $url = $host . $path . "?" . $querys;
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_FAILONERROR, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HEADER, false);
    if (1 == strpos("$".$host, "https://"))
    {
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    }

    return curl_exec($curl);
}//刷新从接口或得到的数据;


//下面是刷新数据从接口到数据库
function DateBase($Json){
    global $mysqli;
    $Select = 'select city from city_weather where city=\''.$Json["result"]['city'].'\'';
    $Check = $mysqli->query($Select);
    $Result = $Json['result'];
    if($Check->num_rows){
        $i = 0;
        if (is_array($Result)){
            foreach ($Result as $Key => $value){//更新result中数组前部
                $UPdate = 'UPDATE city_weather SET '.$Key.' = \''.$Result[$Key].'\' where city =\''.$Json['result']['city'].'\'';
                $mysqli->query($UPdate);
                $i++;
                if ($i >= 16){
                    break;
                }
            }
            $o = 0;
            foreach ($Result['aqi'] as $Key => $value){//更新AQI信息
                $UPdate= 'UPDATE city_weather SET '.$Key.' = \''.$Result['aqi'][$Key].'\' where city =\''.$Json['result']['city'].'\'';
                $mysqli->query($UPdate);
                if ($o++>=23){
                    foreach ($Result['aqi']['aqiinfo'] as $Key =>$Value){
                        $UPdate= 'UPDATE city_weather SET '.$Key.' = \''.$Result['aqi']['aqiinfo'][$Key].'\' where city =\''.$Json['result']['city'].'\'';
                        $mysqli->query($UPdate);
                    }
                    break;
                };
            }
        }
//
    }else{
        $Insert = 'INSERT INTO city_weather (city) VALUES (\''.$Json["result"]['city'].'\')';
        $mysqli->query($Insert);
        DateBase($Json);
    }
};
function Index($Json){
    global $mysqli;
    $Result = $Json['result']['index'];
    foreach ($Result as $Key => $Value){
        foreach ($Result[$Key] as  $Key_1 => $Value_1 ){
            $Select = 'select iname from city_index where iname=\''.$Result[$Key]['iname'].'\' and city = \''.$Json['result']['city'].'\'';
            if(($mysqli->query($Select))->num_rows){
                $UPdate = 'UPDATE city_index SET '.$Key_1.' = \''.$Result[$Key][$Key_1].'\' where iname=\''.$Result[$Key]['iname'].'\' and city = \''.$Json['result']['city'].'\'';
                $mysqli->query($UPdate);
            }else{
                $Insert = 'INSERT INTO city_index (city,iname,citycode) VALUES (\''.$Json["result"]['city'].'\',\''.$Result[$Key]['iname'].'\',\''.$Json['result']['citycode'].'\')';
                $mysqli->query($Insert);
                $UPdate = 'UPDATE city_index SET '.$Key_1.' = \''.$Result[$Key][$Key_1].'\' where iname=\''.$Result[$Key]['iname'].'\' and city = \''.$Json['result']['city'].'\'';
                $mysqli->query($UPdate);
            }
        }
    }
}
function hours($Json){
    global $mysqli;
    $Result = $Json['result']['hourly'];
    foreach ($Result as $Key => $Value){
        foreach ($Result[$Key] as  $Key_1 => $Value_1 ){
            $Select = 'select time from city_hour_detail where time=\''.$Result[$Key]['time'].'\' and city = \''.$Json['result']['city'].'\'';
            if(($mysqli->query($Select))->num_rows){
                $UPdate = 'UPDATE city_hour_detail SET '.$Key_1.' = \''.$Result[$Key][$Key_1].'\' where time=\''.$Result[$Key]['time'].'\' and city = \''.$Json['result']['city'].'\'';
                $mysqli->query($UPdate);
            }else{
                $Insert = 'INSERT INTO city_hour_detail (city,time,citycode) VALUES (\''.$Json["result"]['city'].'\',\''.$Result[$Key]['time'].'\',\''.$Json['result']['citycode'].'\')';
                $mysqli->query($Insert);
                $UPdate = 'UPDATE city_hour_detail SET '.$Key_1.' = \''.$Result[$Key][$Key_1].'\' where time=\''.$Result[$Key]['time'].'\' and city = \''.$Json['result']['city'].'\'';
                $mysqli->query($UPdate);
            }
        }
    }
}
function week($Json){
    global $mysqli;
    $Result = $Json['result']['daily'];
    function UploadtoDataBase($Result,$index){
        global $mysqli,$Json;
        $i = 0;
        foreach ($Result[$index] as $Key => $value) {//更新result中数组前部
            $UPdate = 'UPDATE city_week_detail SET ' . $Key . ' = \'' . $Result[$index][$Key] . '\' where city =\'' . $Json['result']['city'] . '\' and date=\''.$Result[$index]['date'].'\'';
            $mysqli->query($UPdate);
            $i++;
            if ($i >= 4) {
                break;
            }
        }
        $o = 0;
        foreach ($Result[$index]['night'] as $Key => $value) {//更新AQI信息
            $UPdate = 'UPDATE city_week_detail SET ' . $Key . ' = \'' . $Result[$index]['night'][$Key] . '\' where city =\'' . $Json['result']['city'] . '\' and date=\''.$Result[$index]['date'].'\'';
            $mysqli->query($UPdate);
        }
        foreach ($Result[$index]['day'] as $Key => $value) {//更新AQI信息
            $UPdate = 'UPDATE city_week_detail SET ' . "D" . $Key . ' = \'' . $Result[$index]['day'][$Key] . '\' where city =\'' . $Json['result']['city'] . '\' and date=\''.$Result[$index]['date'].'\'';
            $mysqli->query($UPdate);
        }
    }
    for( $index = 0;$index<sizeof($Result);$index++){
        $Select = 'select city,date from city_week_detail where city = \''.$Json["result"]['city'].'\' and date=\''.$Result[$index]['date'].'\'';
        if(($mysqli->query($Select))->num_rows) {
            UploadtoDataBase($Result,$index);
        }else{
            $Insert = 'INSERT INTO city_week_detail (city,citycode,date) VALUES (\''.$Json["result"]['city'].'\',\''.$Json['result']['citycode'].'\',\''.$Result[$index]['date'].'\')';
            $mysqli->query($Insert);
            UploadtoDataBase($Result,$index);
        }
    }

}

?>