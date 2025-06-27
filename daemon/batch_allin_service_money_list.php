<?php
header("Content-Type:application/json");
header("Content-Type:text/html;charset=UTF-8");
include_once( __DIR__ . '/../../../application/configs/configdb.php' );

function query_error() {
    $data[ 'Ok' ] = 'Error';
    $data[ 'Error' ] = '통신 오류'; //$e->getMessage();//
    exit( json_encode( $data, JSON_UNESCAPED_UNICODE ) );
}

function select_query( $db, $sql, $array = array() ) {
    try {
        $stmt = $db->prepare( $sql );
        $stmt->execute( $array );
        $data = $stmt->fetchAll();
    } catch ( Exception $e ) {
        query_error();
    }
    echo json_encode( $data, JSON_UNESCAPED_UNICODE );
}

//$user_id='kaiji';
$date = date('2017-08-06');
$sdate = $date;
$edate = date('Y-m-d', strtotime($date . '+' . '1' . ' days'));

extract($_POST); 
$edate = date('Y-m-d', strtotime($edate . '+' . '1' . ' days'));
if ( !isset( $_SESSION ) ) {
	session_start();
}
$club_id = (int)$_SESSION['club_id'];
$user_level = $_SESSION['user_level'];
session_write_close();

$db = new PDO( 'mysql:host=' . $host . ';dbname=' . $dbname . ';charset=utf8mb4', $user, $password, array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ ) );
$data = array();

$sql = "select u.user_id as user_id, club_user_id, nick_name, bet_money_all,bet_money_real_all,bet_money_service_all,service_money_all,result_money_all,cut_money_all, profit, @crprofit := if(profit is null, 0, profit) + if (cut_money_all is null, 0, cut_money_all) as crprofit, u.allin_service_config, @allin_service := if(u.allin_service_config = 'C', c.allin_service, u.u_allin_service) as allin_service, @allin_service_min_offset := if(u.allin_service_config = 'C', c.allin_service_min_offset, u.u_allin_service_min_offset) as allin_service_min_offset, @allin_service_max_offset := if(u.allin_service_config = 'C', c.allin_service_max_offset, u.u_allin_service_max_offset) as allin_service_max_offset, round(if(@allin_service_max_offset =0, (if(profit is null, 0, profit) + if (cut_money_all is null, 0, cut_money_all)) * (if(u.allin_service_config = 'C', c.allin_service, u.u_allin_service)/100),if(@allin_service_max_offset >= (if(profit is null, 0, profit) + if (cut_money_all is null, 0, cut_money_all)),(if(profit is null, 0, profit) + if (cut_money_all is null, 0, cut_money_all)) * (if(u.allin_service_config = 'C', c.allin_service, u.u_allin_service)/100), @allin_service_max_offset * (if(u.allin_service_config = 'C', c.allin_service, u.u_allin_service)/100)))) as allin_service_money from user_info as u left outer join club_info c on u.club_id = c.id left outer join (select u.user_id, sum(`o`.`bet_money`) AS `bet_money_all`, sum(if((`o`.`money_type` = 'R'),`o`.`bet_money`,0)) AS `bet_money_real_all`,sum(if((`o`.`money_type` = 'S'),`o`.`bet_money`,0)) AS `bet_money_service_all`,sum(`o`.`service_money`) AS `service_money_all`,sum(`o`.`result_money`) AS `result_money_all`, (sum(if((`o`.`money_type` = 'R'),`o`.`bet_money`,0)) - sum(`o`.`result_money`)) AS `profit` from `order` as o left outer join `user_info` as `u` on `u`.user_id = o.user_id where ((`o`.`stat` != 'C') and (`o`.`stat` != 'R')) and o.buy_time >= ? and o.buy_time < ?	group by u.user_id) as o on u.user_id = o.user_id LEFT OUTER JOIN (SELECT user_id u_id, SUM(`cut_money`) AS `cut_money_all` FROM cut_result_money WHERE c_date >= ? AND c_date < ? GROUP BY user_id) AS cr ON u.user_id = cr.u_id where u.club_id = ? and u.allin_service_config != 'N' and u.user_level < 50 and o.bet_money_all > 0 and (if(profit is null, 0, profit) + if (cut_money_all is null, 0, cut_money_all)) >= if(u.allin_service_config = 'C', c.allin_service_min_offset, u.u_allin_service_min_offset);";

select_query( $db, $sql, array( $sdate, $edate, $sdate, $edate, $club_id ) );

?>

