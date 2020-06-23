<?php
$url = "https://www.worldometers.info/coronavirus/";
$ch = curl_init();
$timeout = 5;
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
$html = curl_exec($ch);
curl_close($ch);

$dom = new DOMDocument();
@$dom->loadHTML($html);

$table_tr = $dom->getElementsByTagName('table')[0]->getElementsByTagName('tr');
foreach($table_tr as $row) {
    $cols = $row->getElementsByTagName('td');
    if(count($cols) < 6){
      continue;
    }
    
    $name = trim($cols[1]->getElementsByTagName('a')[0]->textContent);
    if($name == false){
      $name = trim($cols[1]->getElementsByTagName('span')[0]->textContent);
      if($name == false){
        $name = trim($cols[0]->textContent);
      }
    }
        
    $conf = intval($cols[2]->textContent);
    $deaths =  intval($cols[4]->textContent);
    $Recovered = intval($cols[6]->textContent);
        
    $todaySick = intval($cols[3]->textContent);
    $hardSick = intval($cols[9]->textContent);
    $todayDeath = intval($cols[5]->textContent);
    $totalChecks = intval($cols[12]->textContent);
                
    $data = array("Country" => $name, "Confirmed" => $conf, "Deaths" => $deaths, "Recovered" => $Recovered, "todaySick"=>$todaySick, "hardSick"=>$hardSick, "todayDeath"=>$todayDeath, "totalChecks"=>$totalChecks, "nowSick"=>($conf-$Recovered-$deaths));
        
    $bad_list = ["World", "North America", "Europe", "Asia", "South America", "Oceania", "Africa", "Total:", ""];
    if(!in_array($name, $bad_list)){
      $json[] = $data;
    }
}

$log = json_decode(file_get_contents("log.php"), true); // "Israel":{"conf":10, "death":1, "recovered":2}
$new_updates = [];
foreach($json as $item) {

  if(in_array($item["Country"], $log)) {
    if(($item["Confirmed"] - $log[$item["Country"]]["conf"]) >= 20) {
      $new_updates[$item["Country"]]["new_conf"] = ($item["Confirmed"] - $log[$item["Country"]]["conf"]);
    }
    
    if(($item["Deaths"] - $log[$item["Country"]]["death"]) >= 3) {
      $new_updates[$item["Country"]]["new_death"] = ($item["Deaths"] - $log[$item["Country"]]["death"]);
    }
    
    if(($item["Recovered"] - $log[$item["Country"]]["recovered"]) >= 40) {
      $new_updates[$item["Country"]]["new_recovered"] = ($item["Recovered"] - $log[$item["Country"]]["recovered"]);
    }
  
  } else {
  
    $log[$item["Country"]] = {"conf":$item["Confirmed"], "death":$item["Deaths"], "recovered":$item["Recovered"]};
  
  }
  
}

// save log file
file_put_contents("log.php", json_encode($log));

$message = "*CoronaVirus* - ".implode(", ", array_keys($new_updates))."\n\n";
foreach($new_updates as $CountryName => $item) {
  
  if(in_array("new_conf", $item)) {
    $list[] = $item["new_conf"]." more people infected";
  }
  
  if(in_array("new_death", $item)) {
    $list[] = $item["new_death"]." new deaths";
  }
  
  if(in_array("new_recovered", $item)) {
    $list[] = $item["new_recovered"]." people recovered";
  }
  
  $message .= $CountryName.", ".implode(", ", $list).".";
}


// send message to Telegram channel
$data = [
  'chat_id' => '@CoronaVirus_Alerts', // channel id
  'text' => $message,
  'parse_mode' => 'Markdown'
];
        
file_get_contents("https://api.telegram.org/bot@@@@@@@@/sendMessage?".http_build_query($data));
echo "success";
