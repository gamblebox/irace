<?php
function shutDownFunction()
{
  $error = error_get_last();

  if ($error !== NULL) {
    $aResult = [
      "data" => [],
      "error" => $error,
    ];
    echo json_encode($aResult);
    exit();
  }
}
// register_shutdown_function('shutDownFunction');


function updateBettingByRaceChange($database, $raceChange)
{
  print_r($raceChange);

  if ($raceChange['type'] != '출전취소' && $raceChange['type'] != '출전제외' && $raceChange['type'] != '경주취소') {
    return true;
  }
  switch ($raceChange['type']) {
    case '출전취소':
    case '출전제외':
      $sql = "
      select id, entry_count, cancel_entry_no from irace.race where id = {$raceChange['race_id']}
    ";
      $raceInfo = select_query_one($database, $sql);
      $associationCode = $raceChange['association_code'];
      $realEntryCount = $raceInfo->entry_count - count(explode(',', $raceInfo->cancel_entry_no));

      // get betting race need change
      $sql = "
      SELECT isports.betting_race.*
      FROM
        isports.betting_race
          LEFT JOIN isports.users ON isports.users.id = isports.betting_race.user_id
          LEFT JOIN isports.betting_options AS ubo ON ubo.bo_user_id = isports.users.id
          LEFT JOIN isports.betting_options AS cbo ON cbo.bo_user_class_id = isports.users.user_class_id
          LEFT JOIN isports.betting_options AS bbo ON bbo.bo_branch_id = isports.users.branch_id
      WHERE
        isports.betting_race.stat = 'P'
        AND isports.betting_race.race_id = {$raceChange['race_id']}
        AND (
          isports.betting_race.place_1 = {$raceChange['entry_no']}
          OR isports.betting_race.place_2 = {$raceChange['entry_no']}
          OR isports.betting_race.place_3 = {$raceChange['entry_no']}
          OR (isports.betting_race.type='복연승' AND {$realEntryCount} < ifnull(ubo.bo_{$associationCode}_ticketing_entry_bokyun,ifnull(cbo.bo_{$associationCode}_ticketing_entry_bokyun,bbo.bo_{$associationCode}_ticketing_entry_bokyun)))
          OR (isports.betting_race.type='삼복승' AND {$realEntryCount} < ifnull(ubo.bo_{$associationCode}_ticketing_entry_sambok,ifnull(cbo.bo_{$associationCode}_ticketing_entry_sambok,bbo.bo_{$associationCode}_ticketing_entry_sambok)))
          OR (isports.betting_race.type='삼쌍승' AND {$realEntryCount} < ifnull(ubo.bo_{$associationCode}_ticketing_entry_samssang,ifnull(cbo.bo_{$associationCode}_ticketing_entry_samssang,bbo.bo_{$associationCode}_ticketing_entry_samssang)))
          OR (isports.betting_race.type NOT IN ('복연승','삼복승','삼쌍승') AND {$realEntryCount} < ifnull(ubo.bo_{$associationCode}_ticketing_entry,ifnull(cbo.bo_{$associationCode}_ticketing_entry,bbo.bo_{$associationCode}_ticketing_entry)))
        );
    ";
      // print_r($sql);

      $aoBettingRace = select_query($database, $sql);
      print_r($aoBettingRace);
      // return;

      if (!$aoBettingRace) {
        return true;
      }
      break;

    case '경주취소':
      // get betting race need change
      $sql = "
      SELECT isports.betting_race.*
      FROM
        isports.betting_race
      WHERE
        isports.betting_race.stat = 'P'
        AND isports.betting_race.race_id = {$raceChange['race_id']};
    ";
      // print_r($sql);

      $aoBettingRace = select_query($database, $sql);
      print_r($aoBettingRace);
      // return;

      if (!$aoBettingRace) {
        return true;
      }
      break;

    default:
      return false;
      break;
  }

  // get race info
  $sql = "
        SELECT irace.place.name AS place_name, irace.race.race_no
        FROM irace.race
          LEFT JOIN irace.place ON irace.place.id = irace.race.place_id
        WHERE irace.race.id = {$raceChange['race_id']}
      ";
  $oRaceInfo = select_query_one($database, $sql);

  // refund betting money
  foreach ($aoBettingRace as $key => $oBettingRace) {
    print_r($oBettingRace);
    // [id] => 3391
    // [user_id] => 133312
    // [branch_id] => 1
    // [race_id] => 60143
    // [own_race_no] => 0
    // [fixed_odds] => 1.00
    // [association_code] => krace
    // [type] => 복승
    // [place_1] => 1
    // [place_2] => 3
    // [place_3] => 0
    // [odds] => 0.00
    // [money_type] => R
    // [stat] => P
    // [bet_money] => 2000
    // [service_money_race] => 200
    // [result_money] => 0
    // [buy_time] => 2024-10-13 17:15:47
    // [buy_date] =>
    // [cancel_time] =>
    // [update_time] => 2024-10-13 17:34:02

    try {
      $database->beginTransaction();
      $sql = "
      UPDATE 
        isports.betting_race
        LEFT JOIN isports.info_betting ON info_betting.ib_betting_race_id = isports.betting_race.id
      SET
        isports.betting_race.stat = 'R'
        , isports.info_betting.ib_status = 'R'
      WHERE
        isports.betting_race.id = {$oBettingRace->id}
    ";
      $database->prepare($sql)->execute();

      switch ($oBettingRace->money_type) {
        case 'R':
          $sql = "
          UPDATE 
            isports.users
          SET
            isports.users.money_real = isports.users.money_real + {$oBettingRace->bet_money}
            , isports.users.money_service_race = isports.users.money_service_race - {$oBettingRace->service_money_race}
          WHERE
            isports.users.id = {$oBettingRace->user_id}
        ";
          $database->prepare($sql)->execute();

          // insert document
          $sql = "
          INSERT INTO isports.document (
            branch_id
            , user_id
            , money_type
            , system
            , division
            , cause
            , amount
            , subject
            , memo
            , base_date
            , base_time
            , balance
            , balance_money_real
            , balance_money_point
            , balance_money_service
            , balance_money_service_race
          )
          SELECT 
            branch_id
            , id
            , 'money_real'
            , 'race'
            , 'race'
            , 'refund'
            , {$oBettingRace->bet_money}
            , '환불'
            , '출전취소(제외) - 구매금액 환불'
            , date(now())
            , now()
            , money_real
            , money_real
            , money_point
            , money_service
            , money_service_race
          FROM isports.users 
          WHERE isports.users.id = {$oBettingRace->user_id}; 
        ";
          $database->prepare($sql)->execute();

          if ($oBettingRace->service_money_race > 0) {

            $sql = "
            INSERT INTO isports.document (
              branch_id
              , user_id
              , money_type
              , system
              , division
              , cause
              , amount
              , subject
              , memo
              , base_date
              , base_time
              , balance
              , balance_money_real
              , balance_money_point
              , balance_money_service
              , balance_money_service_race
            )
            SELECT 
              branch_id
              , id
              , 'money_service_race'
              , 'race'
              , 'race'
              , 'refund'
              , {$oBettingRace->service_money_race} * -1
              , '환수-경마서비스'
              , '출전취소(제외) - 베팅서비스 환수'
              , date(now())
              , now()
              , money_service_race
              , money_real
              , money_point
              , money_service
              , money_service_race
            FROM isports.users 
            WHERE isports.users.id = {$oBettingRace->user_id}; 
          ";
            $database->prepare($sql)->execute();
          }

          // insert log
          $memo = "{$oRaceInfo->place_name} {$oRaceInfo->race_no}경주 {$oBettingRace->type} {$oBettingRace->place_1}-{$oBettingRace->place_2}-{$oBettingRace->place_3} {$oBettingRace->bet_money} 환불로 인한 변동";

          $sql = "
          INSERT INTO isports.logs (
            type
            , `old_money_real`
            , `new_money_real`
            , `old_money_service_race`
            , `new_money_service_race`
            , `memo`
            , `user_id`
            , `branch_id`
          )
          SELECT
            '환불'
            , money_real - {$oBettingRace->bet_money}
            , money_real
            , money_service_race + {$oBettingRace->service_money_race}
            , money_service_race
            , '{$memo}'
            , id
            , branch_id
          FROM
            isports.users
          WHERE
            isports.users.id = {$oBettingRace->user_id};
        ";
          $database->prepare($sql)->execute();

          break;

        case 'S':
          $sql = "
          UPDATE 
            isports.users
          SET
            isports.users.money_service_race = isports.users.money_service_race + {$oBettingRace->bet_money}
          WHERE
            isports.users.id = {$oBettingRace->user_id}
        ";
          $database->prepare($sql)->execute();

          $sql = "
          INSERT INTO isports.document (
            branch_id
            , user_id
            , money_type
            , system
            , division
            , cause
            , amount
            , subject
            , memo
            , base_date
            , base_time
            , balance
            , balance_money_real
            , balance_money_point
            , balance_money_service
            , balance_money_service_race
          )
          SELECT 
            branch_id
            , id
            , 'money_service_race'
            , 'race'
            , 'race'
            , 'refund'
            , {$oBettingRace->service_money_race}
            , '환불-경마서비스'
            , '출전취소(제외) - 서비스베팅 환불'
            , date(now())
            , now()
            , money_service_race
            , money_real
            , money_point
            , money_service
            , money_service_race
          FROM isports.users 
          WHERE isports.users.id = {$oBettingRace->user_id}; 
        ";
          $database->prepare($sql)->execute();

          // insert log
          $memo = "{$oRaceInfo->place_name} {$oRaceInfo->race_no}경주 {$oBettingRace->type} {$oBettingRace->place_1}-{$oBettingRace->place_2}-{$oBettingRace->place_3} {$oBettingRace->bet_money} 환불로 인한 변동";

          $sql = "
          INSERT INTO isports.logs (
            type
            , `old_money_real`
            , `new_money_real`
            , `old_money_service_race`
            , `new_money_service_race`
            , `memo`
            , `user_id`
            , `branch_id`
          )
          SELECT
            '환불'
            , money_real
            , money_real
            , money_service_race - {$oBettingRace->service_money_race}
            , money_service_race
            , '{$memo}'
            , id
            , branch_id
          FROM
            isports.users
          WHERE
            isports.users.id = {$oBettingRace->user_id};
        ";
          $database->prepare($sql)->execute();

          break;
      }

      $database->commit();
    } catch (\Exception $e) {
      if ($database->inTransaction()) {
        $database->rollBack();
      }
      echo $e->getMessage() . PHP_EOL;

      return false;
    }
  }

  return true;
}
