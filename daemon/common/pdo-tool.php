<?php
// include_once(__DIR__ . '/../common/pdoTool.php');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
/**
 * Replaces any parameter placeholders in a query with the value of that
 * parameter. Useful for debugging. Assumes anonymous parameters from
 * $params are are in the same order as specified in $query
 *
 * @param string $query The sql query with parameter placeholders
 * @param array $params The array of substitution parameters
 * @return string The interpolated query
 */
function interpolateQuery($query, $params)
{
  $keys = [];
  $values = $params;

  # build a regular expression for each parameter
  foreach ($params as $key => $value) {
    if (is_string($key)) {
      $keys[] = '/:' . $key . '/';
    } else {
      $keys[] = '/[?]/';
    }

    if (is_string($value)) {
      $values[$key] = "'" . $value . "'";
    }

    if (is_array($value)) {
      $values[$key] = "'" . implode("','", $value) . "'";
    }

    if (is_null($value)) {
      $values[$key] = 'NULL';
    }
  }

  $query = preg_replace($keys, $values, $query);

  return $query;
}

function insert_money_log($database, $array)
{
  $sql =
    "INSERT INTO `logs` (`old_money_real`, `new_money_real`, `old_money_point`, `new_money_point`, `type`, `memo`, `user_id`, `branch_id`) select ? , money_real, ?, money_point, ? , ?, id, branch_id from users where id = ?";
  $query = $database->prepare($sql);
  $query->execute($array);
}

function insert_error_log($database, $user_id, $type, $memo)
{
  $sql =
    "INSERT INTO `logs` (`type`, `memo`, `user_id`, `branch_id`) select ? , ?, id, branch_id from users where id = ?";
  $query = $database->prepare($sql);
  $query->execute(array($type, $memo, $user_id));
}

function insert_log($database, $user_id, $type, $memo, $is_important)
{
  $sql =
    "INSERT INTO `logs` (`is_important`, `type`, `memo`, `user_id`, `branch_id`) select ?, ?, ? , id, branch_id from users where id = ?";
  $query = $database->prepare($sql);
  $query->execute(array($is_important, $type, $memo, $user_id));
}

function select_query($database, $sql, $array = [])
{
  $query = $database->prepare($sql);
  $query->execute($array);
  $data = $query->fetchAll();
  return $data;
}

function select_query_one($database, $sql, $array = [])
{
  $query = $database->prepare($sql);
  $query->execute($array);
  $data = $query->fetch();
  return $data;
}

// function exec_query($db, $sql, $array = [])
// {
//   try {
//     $stmt = $db->prepare($sql);
//     $stmt->execute($array);
//     $data = true;
//   } catch (PDOException $e) {
//     $data = $e->getMessage();
//   }
//   return $data;
// }

function exec_query_transaction($db, $sql, $array = [])
{
  $data = new stdClass();
  $db->beginTransaction();
  try {
    $stmt = $db->prepare($sql);
    $stmt->execute($array);
    $db->commit();
    $data->result = 1;
  } catch (PDOException $e) {
    $db->rollBack();
    $data->result = 0;
    $data->message = $e->getMessage();
  }
  return $data;
}

// function exec_query($db, $sql, $array = [])
// {
//   $sth = $db->prepare($sql);
//   return $sth->execute($array);
// }

function exec_query($db, $sql, $array = [])
{
  try {
    $stmt = $db->prepare($sql);
    $stmt->execute($array);
    $data = 1;
  } catch (PDOException $e) {
    $data = 0;
  }
  return $data;
}

function exec_query_msg($db, $sql, $array = [])
{
  $data = new stdClass();
  try {
    $stmt = $db->prepare($sql);
    $stmt->execute($array);
    $data->result = 1;
  } catch (PDOException $e) {
    $data->result = 0;
    $data->message = $e->getMessage();
  }
  return $data;
}

// function exec_query_lastId($db, $sql, $array = [])
// {
//   $db->beginTransaction();
//   try {
//     $stmt = $db->prepare($sql);
//     $stmt->execute($array);
//     $db->commit();
//     $data = $db->lastInsertId();
//   } catch (PDOException $e) {
//     $db->rollBack();
//     $data = $e->getMessage();
//   }
//   return $data;
// }
function exec_query_lastId($db, $sql, $array = [])
{
  $obj = new stdClass();
  try {
    $stmt = $db->prepare($sql);
    $obj->result = $stmt->execute($array);
    $obj->id = $db->lastInsertId();
  } catch (PDOException $e) {
    $obj->result = 0;
    $obj->msg = $e->getMessage();
  }
  // $obj->result = true;
  return $obj;
}

function exec_query_lastId_transaction($db, $sql, $array = [])
{
  $obj = new stdClass();
  try {
    $stmt = $db->prepare($sql);
    $obj->result = $stmt->execute($array);
    $obj->id = $db->lastInsertId();
  } catch (PDOException $e) {
    $obj->result = 0;
    $obj->msg = $e->getMessage();
  }
  // $obj->result = true;
  return $obj;
}

function query_error()
{
  $data['Ok'] = 'Error';
  $data['Error'] = '통신 오류';
  exit(json_encode($data, JSON_UNESCAPED_UNICODE));
}

function change_user_money_pcr($database, $user_id, $money_real_amount, $money_point_amount, $type, $memo, $game_type, $memo_admin, $gameId, $tableId, $bettingId, $bettingDate, $isMyBet)
{
  try {
    $sql = "update users set money_real = money_real + ?, money_point = money_point +? where id = ?";
    $query = $database->prepare($sql);
    $response = $query->execute(array($money_real_amount, $money_point_amount, $user_id));
    if (!$response) {
      return false;
    }
    $sql =
      "INSERT INTO `logs` (`old_money_real`, `new_money_real`, `old_money_point`, `new_money_point`, `type`, `memo`, `memo_admin`, `cg_id`, `ct_id`, `ib_id`, `ib_date`, `game_type`, `is_my_bet`, `user_id`, `branch_id`) select money_real-?, money_real, money_point-?, money_point, ?, ?, ?, ?, ?, ?, ?, ?, ?, id, branch_id from users where id = ?";
    $query = $database->prepare($sql);
    $query->execute(array($money_real_amount, $money_point_amount, $type, $memo, $memo_admin, $gameId, $tableId, $bettingId, $bettingDate, $game_type, $isMyBet, $user_id));

    $data = true;
  } catch (PDOException $e) {
    $data = false;
  }
  return $data;
}

function change_user_money_pcr_msg($database, $user_id, $money_real_amount, $money_point_amount, $type, $memo, $game_type, $memo_admin, $gameId, $tableId, $bettingId, $bettingDate, $isMyBet)
{
  $data = new stdClass();

  try {
    $sql = "update users set money_real = money_real + ?, money_point = money_point +? where id = ?";
    $query = $database->prepare($sql);
    $response = $query->execute(array($money_real_amount, $money_point_amount, $user_id));
    if (!$response) {
      return false;
    }
    $sql =
      "INSERT INTO `logs` (`old_money_real`, `new_money_real`, `old_money_point`, `new_money_point`, `type`, `memo`, `memo_admin`, `cg_id`, `ct_id`, `ib_id`, `ib_date`, `game_type`, `is_my_bet`, `user_id`, `branch_id`) select money_real-?, money_real, money_point-?, money_point, ?, ?, ?, ?, ?, ?, ?, ?, ?, id, branch_id from users where id = ?";
    $query = $database->prepare($sql);
    $query->execute(array($money_real_amount, $money_point_amount, $type, $memo, $memo_admin, $gameId, $tableId, $bettingId, $bettingDate, $game_type, $isMyBet, $user_id));

    $data->result = 1;
  } catch (PDOException $e) {
    $data->result = 0;
    $data->message = $e->getMessage();
  }
  return $data;
}

function change_user_money($database, $user_id, $money_real_amount, $money_point_amount, $type, $memo)
{
  $database->beginTransaction();

  try {
    $sql = "select money_real, money_point from users where id = ?";
    $user_info = select_query_one($database, $sql, array($user_id));
    $old_money_real = $user_info->money_real;
    $old_money_point = $user_info->money_point;
    $new_money_real = $old_money_real + $money_real_amount;
    $new_money_point = $old_money_point + $money_point_amount;

    $sql = "update users set money_real = ?, money_point = ? where id = ?";
    $query = $database->prepare($sql);
    $query->execute(array($new_money_real, $new_money_point, $user_id));
    $sql =
      "INSERT INTO `logs` (`old_money_real`, `new_money_real`, `old_money_point`, `new_money_point`, `type`, `memo`, `user_id`, `branch_id`) select ?, ?, ?, ?, ?, ?, id, branch_id from users where id = ?";
    $query = $database->prepare($sql);
    $query->execute(array($old_money_real, $new_money_real, $old_money_point, $new_money_point, $type, $memo, $user_id));
    $database->commit();

    $data = true;
  } catch (PDOException $e) {
    $database->rollback();
    $data = $e->getMessage();
  }
  return $data;
}

function change_user_money_by_chips($database, $user_id, $money_real_amount, $money_point_amount, $type, $memo, $bettingId)
{
  if ($money_real_amount == 0) {
    return false;
  }
  // $database->beginTransaction();
  try {
    // $sql = "select money_real, money_point, money_casino from users where id = ?";
    // $user_info = select_query_one($database, $sql, array($user_id));
    // $old_money_real = $user_info->money_real;
    // $new_money_real = $old_money_real + $money_real_amount;
    // $old_money_point = $user_info->money_point;
    // $new_money_point = $old_money_point + $money_point_amount;
    // $old_money_casino = $user_info->money_casino;
    // $money_casino_amount = $money_real_amount * (-1);
    // $new_money_casino = $old_money_casino + $money_casino_amount;

    // update money
    $sql = "update users set money_real = money_real + ?, money_point = money_point +? where id = ?";
    $query = $database->prepare($sql);
    $response = $query->execute(array($money_real_amount, $money_point_amount, $user_id));
    if (!$response) {
      return false;
    }

    // insert log
    $sql =
      "INSERT INTO `logs` (`old_money_real`, `new_money_real`, `old_money_point`, `new_money_point`, `type`, `memo`, `ib_id`, `user_id`, `branch_id`) select money_real-?, money_real, money_point-?, money_point, ?, ?, ?, id, branch_id from users where id = ?";
    $query = $database->prepare($sql);
    $query->execute(array($money_real_amount, $money_point_amount, $type, $memo, $bettingId, $user_id));

    // $database->commit();
    $data = true;
  } catch (PDOException $e) {
    // $database->rollBack();
    // $data = $e->getMessage();
    $data = false;
  }
  return $data;
}

// function change_user_money($database, $user_id, $money_real_amount, $money_point_amount, $type, $memo, $is_important = 'N')
// {
//   $database->beginTransaction();

//   try {
//     $sql = "select money_real, money_point from users where id = ?";
//     $user_info = select_query_one($database, $sql, array($user_id));
//     $old_money_real = $user_info->money_real;
//     $old_money_point = $user_info->money_point;
//     $new_money_real = $old_money_real + $money_real_amount;
//     $new_money_point = $old_money_point + $money_point_amount;

//     $sql = "update users set money_real = ?, money_point = ? where id = ?";
//     $query = $database->prepare($sql);
//     $query->execute(array($new_money_real, $new_money_point, $user_id));
//     $sql =
//       "INSERT INTO `logs` (`is_important`, `old_money_real`, `new_money_real`, `old_money_point`, `new_money_point`, `type`, `memo`, `user_id`, `branch_id`, `is_important`) select ?, ?, ?, ?, ?, ?, ?, id, branch_id from users where id = ?";
//     $query = $database->prepare($sql);
//     $query->execute(array(
//       $is_important, $old_money_real, $new_money_real, $old_money_point, $new_money_point, $type,
//       $memo, $user_id
//     ));



//     $database->commit();

//     $data = true;
//   } catch (PDOException $e) {
//     $database->rollback();
//     $data = $e->getMessage();
//   }
//   return $data;
// }
function change_user_money_game_id($database, $user_id, $money_real_amount, $money_point_amount, $type, $memo, $game_type, $gameId = null)
{
  $database->beginTransaction();
  try {
    $sql = "select money_real, money_point from users where id = ?";
    $user_info = select_query_one($database, $sql, array($user_id));
    $old_money_real = $user_info->money_real;
    $old_money_point = $user_info->money_point;
    $new_money_real = $old_money_real + $money_real_amount;
    $new_money_point = $old_money_point + $money_point_amount;

    $sql = "update users set money_real = ?, money_point = ? where id = ?";
    $query = $database->prepare($sql);
    $query->execute(array($new_money_real, $new_money_point, $user_id));
    $sql =
      "INSERT INTO `logs` (`old_money_real`, `new_money_real`, `old_money_point`, `new_money_point`, `type`, `memo`, `cg_id`, `game_type`, `user_id`, `branch_id`) select ?, ?, ?, ?, ?, ?, ?, ?, id, branch_id from users where id = ?";
    $query = $database->prepare($sql);
    $query->execute(array($old_money_real, $new_money_real, $old_money_point, $new_money_point, $type, $memo, $gameId, $game_type, $user_id));
    $database->commit();
    $data = true;
  } catch (PDOException $e) {
    $database->rollback();
    $data = $e->getMessage();
  }
  return $data;
}

function insert_cut_money($db, $user_id, $top_partner, $top_partner_result_money_offset, $ib_id, $result_money, $cut_money)
{
  $sql = "select * from users where id = ?";
  $user = select_query_one($db, $sql, array($user_id));

  $memo = $user->user_name . '(' . $user->nick_name . ')' . ' 최상위 파트너 ' . $top_partner->user_name . '(' . $top_partner->nick_name . ')' . ' 상한가: ' . number_format($top_partner_result_money_offset) . ' 적용 공제';

  $sql =
    "INSERT INTO `cut_moneys` (`user_id`, `ib_id`, `branch_id`, `cut_money`, `result_money`, `result_money_offset`, `memo`) VALUES(?, ?, ?, ?, ?, ?, ?);";
  $response = exec_query($db, $sql, array($user_id, $ib_id, $user->branch_id, $cut_money, $result_money, $top_partner_result_money_offset, $memo));
  if (!$response) {
    echo 'Error Insert Cut money!!!' . PHP_EOL;
    exit();
  }
  return true;
}

function createDocument($db, $aData)
{
  $sql = "
    INSERT INTO isports.document (
      branch_id,
      user_id,
      balance,
      balance_money_real,
      balance_money_point,
      balance_money_service,
      balance_money_service_race,
      by_user_id,
      money_type,
      system,
      division,
      category,
      cause,
      status,
      amount,
      subject,
      memo,
      base_betting_amount,
      base_date,
      base_time,
      ref_id,
      log_id,
      ref_table,
      ref_table_id
    ) SELECT 
        branch_id,
        id,
        {$aData['money_type']},
        money_real,
        money_point,
        money_service,
        money_service_race,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?
      FROM isports.users
      WHERE id =?
  ";

  $response = exec_query_msg($db, $sql, [
    // $aData['branch_id'],
    // $aData['user_id'],
    $aData['by_user_id'],
    $aData['money_type'],
    $aData['system'],
    $aData['division'],
    $aData['category'],
    $aData['cause'],
    $aData['status'],
    $aData['amount'],
    $aData['subject'],
    $aData['memo'],
    $aData['base_betting_amount'],
    $aData['base_date'],
    $aData['base_time'],
    $aData['ref_id'],
    $aData['log_id'],
    $aData['ref_table'],
    $aData['ref_table_id'],
    $aData['user_id'],

  ]);

  return $response;
}


function curl($url)
{

  // curl 리소스를 초기화
  $ch = curl_init();

  // url을 설정
  curl_setopt($ch, CURLOPT_URL, $url);

  // 헤더는 제외하고 content 만 받음
  curl_setopt($ch, CURLOPT_HEADER, 0);

  // 응답 값을 브라우저에 표시하지 말고 값을 리턴
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

  // 브라우저처럼 보이기 위해 user agent 사용
  curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.103 Safari/537.36');

  $headers = array(
    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
    'Cache-Control: max-age=0'
  );
  //$curl_setopt($ch, CURLOPT_HEADER, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

  $content = curl_exec($ch);

  curl_close($ch);

  return $content;
}
