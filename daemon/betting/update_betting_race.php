<?php
require_once('/srv/irace/daemon/common/pdo-tool.php');
require_once('/srv/irace/daemon/common/config-db.php');

$database = new PDO('mysql:host=' . $host . ';dbname=' . $dbname . ';port=' . $port . ';charset=utf8mb4', $user, $password, array(
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_EMULATE_PREPARES => false,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
));

// BEGIN
// 	IF (NEW.status = 3 AND NEW.settlement = -1) THEN 
// 		update IGNORE isports.info_betting_detail set ibd_result = 'C', ibd_result_benefit = 1 WHERE ibd_bet_id = NEW.id;
// 	END IF;
// 	IF (NEW.status = 3 AND NEW.settlement = 1) THEN 
// 		update IGNORE isports.info_betting_detail set ibd_result = 'L', ibd_result_benefit = 0 WHERE ibd_bet_id = NEW.id;
// 	END IF;
// 	IF (NEW.status = 3 AND NEW.settlement = 2) THEN 
// 		update IGNORE isports.info_betting_detail set ibd_result = 'W', ibd_result_benefit = ibd_benefit WHERE ibd_bet_id = NEW.id;
// 	END IF;
// 	IF (NEW.status = 3 AND NEW.settlement = 3) THEN 
// 		update IGNORE isports.info_betting_detail set ibd_result = 'R', ibd_result_benefit = 1 WHERE ibd_bet_id = NEW.id;
// 	END IF;
// 	IF (NEW.status = 3 AND NEW.settlement = 4) THEN 
// 		update IGNORE isports.info_betting_detail set ibd_result = 'HL', ibd_result_benefit = 1 WHERE ibd_bet_id = NEW.id;
// 	END IF;
// 	IF (NEW.status = 3 AND NEW.settlement = 5) THEN 
// 		update IGNORE isports.info_betting_detail set ibd_result = 'HW', ibd_result_benefit = 1 WHERE ibd_bet_id = NEW.id;
// 	END IF;
// END

// lsports
$sql = "
  SELECT DISTINCT
    irace.race.id AS race_id
  FROM
    irace.race
  JOIN isports.betting_race ON isports.betting_race.race_id = irace.race.id
  WHERE
    isports.betting_race.stat IN('P')
    AND irace.race.stat = 'E'
";

$aRace = select_query($database, $sql);
print_r($aRace);
// exit();

foreach ($aRace as $race) {
  $sql = "
    SELECT DISTINCT
      isports.betting_race.user_id
      , irace.race.id race_id
    FROM
      irace.race
    JOIN isports.betting_race ON isports.betting_race.race_id = irace.race.id
    WHERE
      irace.race.id = {$race->race_id}
      AND isports.betting_race.stat = 'P'
  ";
  // print_r($sql);
  $aRaceUserBetting = select_query($database, $sql);
  print_r($aRaceUserBetting);
  // exit();

  foreach ($aRaceUserBetting as $raceUserBetting) {
    $sql = "
      SELECT isports.betting_race.*, irace.race.place_id
      FROM isports.betting_race
      LEFT JOIN irace.race ON irace.race.id = isports.betting_race.race_id
      WHERE 
        isports.betting_race.user_id = {$raceUserBetting->user_id}
        AND isports.betting_race.race_id = {$raceUserBetting->race_id}
        AND isports.betting_race.stat = 'P'
    ";
    // print_r($sql);

    echo "race_id=>{$raceUserBetting->race_id} user_id=>{$raceUserBetting->user_id}" . PHP_EOL;
    $aUserBetting = select_query($database, $sql);
    print_r($aUserBetting);
    // exit();

    updateUserBetting($database, $aUserBetting);
  }
}

function updateUserBetting($database, $aUserBetting)
{
  $raceId = $aUserBetting[0]->race_id;
  $userId = $aUserBetting[0]->user_id;
  $winMoney = 0;
  $cutMoney = 0;

  foreach ($aUserBetting as $key => $userBetting) {
    $resultMoney = 0;
    $status = 'L';
    $sql = "
      SELECT *
      FROM irace.result
      WHERE
        irace.result.race_id = {$raceId}
        AND irace.result.type = '{$userBetting->type}'
    ";
    $aoResult = select_query($database, $sql);
    print_r($userBetting);
    print_r($aoResult);
    // exit();

    // check result
    foreach ($aoResult as $key => $oResult) {
      if (
        $userBetting->place_1 == $oResult->place_1
        && $userBetting->place_2 == $oResult->place_2
        && $userBetting->place_3 == $oResult->place_3
      ) { // win
        echo 'win' . PHP_EOL;
        $status = 'W';
        $resultMoney = round($userBetting->bet_money * $oResult->odds);
        $winMoney += $resultMoney;
        break;
      }
    }


    $sql = "
      UPDATE isports.betting_race
      SET isports.betting_race.stat = '{$status}'
        , isports.betting_race.result_money = {$resultMoney}
      WHERE isports.betting_race.id = {$userBetting->id}
    ";
    print_r($sql);
    $msg = exec_query_msg($database, $sql);
    print_r($msg);

    $sql = "
      UPDATE isports.info_betting
      SET isports.info_betting.ib_status = '{$status}'
        , isports.info_betting.ib_flag = 'E'
        , isports.info_betting.ib_pay = {$resultMoney}
      WHERE isports.info_betting.ib_betting_race_id = {$userBetting->id}
    ";
    print_r($sql);
    $msg = exec_query_msg($database, $sql);
    print_r($msg);
  }

  // update user money
  if ($winMoney > 0) {
    // get user info
    $associationCode4ResultMoneyOffset = $userBetting->association_code;
    if ($userBetting->place_id == 6) {
      $associationCode4ResultMoneyOffset = 'kjrace';
    }
    $sql = "SELECT 
        money_real
        , branch_id
        , ifnull(ubo.bo_{$associationCode4ResultMoneyOffset}_result_money_offset,ifnull(cbo.bo_{$associationCode4ResultMoneyOffset}_result_money_offset,bbo.bo_{$associationCode4ResultMoneyOffset}_result_money_offset)) AS result_money_offset 
      FROM isports.users 
      LEFT JOIN isports.betting_options AS ubo ON ubo.bo_user_id = isports.users.id
			LEFT JOIN	isports.betting_options AS cbo ON cbo.bo_user_class_id = isports.users.user_class_id
			LEFT JOIN	isports.betting_options AS bbo ON bbo.bo_branch_id = isports.users.branch_id
      WHERE isports.users.id = {$userId}";

    $user = select_query_one($database, $sql);
    print_r($user);
    // exit();

    // cutting offset money
    $orginWinMoney = $winMoney;
    $offsetMoney = $user->result_money_offset;
    if ($offsetMoney > 0 && $winMoney > $offsetMoney) {
      $cutMoney = $winMoney - $offsetMoney;
      $winMoney = $offsetMoney;
    }
    // $cutMoney = 1000000;

    $sql = "
        UPDATE isports.users
        SET isports.users.money_real = isports.users.money_real + {$winMoney}
        WHERE isports.users.id = {$userId}      
      ";
    $msg = exec_query_msg($database, $sql);
    print_r($msg);

    // createDocument
    $system = 'race';
    $time = date('Y-m-d H:i:s');
    $date = date('Y-m-d', strtotime($time));
    $response = createDocument($database, [
      'user_id' => $userId,
      'by_user_id' => 0,
      'money_type' => 'money_real',
      'system' => $system,
      'division' => $system,
      'category' => 'default',
      'cause' => 'win',
      'status' => 'end',
      'amount' => $winMoney,
      // 'balance' => $balance,
      'subject' => "race - pay win money",
      'memo' => "race - pay win money",
      'base_betting_amount' => abs($winMoney),
      'base_date' => $date,
      'base_time' => $time,
      'ref_id' => 0,
      'log_id' => 0,
      'ref_table' => 'betting_race',
      'ref_table_id' => 0,
    ]);
    print_r($response);

    // get race info
    $sql = "
        select
          irace.race.id
          , irace.place.name AS place_name
          , race_no
        from irace.race
        left join irace.place on irace.place.id = irace.race.place_id
        where irace.race.id = {$raceId}
      ";
    $race = select_query_one($database, $sql);

    $memo = "당첨금지급: {$race->place_name} {$race->race_no}경주 총당첨:{$orginWinMoney}원";
    if ($cutMoney > 0) {
      $memo = "당첨금지급: {$race->place_name} {$race->race_no}경주 총당첨:{$orginWinMoney}원 |상한공제: {$cutMoney}원 지급:{$winMoney}원";

      // insert cut_money
      $sql = "
        INSERT INTO isports.cut_money
        (
          user_id
        , race_id
        , branch_id
        , cut_money
        , result_money
        , result_money_offset
        )
        SELECT
          {$userId}
        , {$race->id}
        , {$user->branch_id}
        , $cutMoney
        , $orginWinMoney
        , $offsetMoney
        FROM isports.users
        WHERE isports.users.id = {$userId}

      ";
      $msg = exec_query_msg($database, $sql);
      print_r($msg);
    }

    // insert log
    $sql = "
        INSERT INTO isports.logs
        (
          user_id
         , branch_id
         , type
         , old_money_real
         , new_money_real
         , memo
        )
        SELECT
          {$userId}
         , {$user->branch_id}
         , '경마당첨금액지급'
         , {$user->money_real}
         , money_real
         , '{$memo}'
        FROM isports.users
        WHERE isports.users.id = {$userId}

      ";
    $msg = exec_query_msg($database, $sql);
    print_r($msg);
  }
}
