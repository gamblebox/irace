<?php
$club_id=1;
$date = date('Y-m-d');
$sdate = $date;
$edate = $date;
extract( $_POST );
$a = $action;
if ( !isset( $_SESSION ) ) {
	session_start();
}
$user_id = $_SESSION['user_id'];
if ($_SESSION['club_id'] !== '255') {
	$club_id = (int) $_SESSION['club_id'];
}
$user_level = $_SESSION['user_level'];
$club_code = $_SESSION[ 'club_code' ];
$club_id = ( int )$club_id;
/*
 * Example PHP implementation used for the index.html example
 */

// DataTables PHP library
include( "DataTables.php" );

// Alias Editor classes so they are easy to use
use
	DataTables\Editor,
	DataTables\Editor\Field,
	DataTables\Editor\Format,
	DataTables\Editor\Mjoin,
	DataTables\Editor\Upload,
	DataTables\Editor\Validate;

// Build our Editor instance and process the data coming from _POST
Editor::inst( $db, 'partner_info' )
	->fields(
		//Field::inst( 'banking_type' ),
		Field::inst( 'partner_name' ),
		//Field::inst( 'club_id' ),
		Field::inst( 'partner_id' )->validator( 'Validate::notEmpty' )->validator( 'Validate::unique' ),
		Field::inst( 'club_partner_id' ),
		Field::inst( 'club_id' ),
		Field::inst( 'partner_pw' ),
		Field::inst( 'partner_level' ),
		Field::inst( 'partner_calculate_rule' ),
		Field::inst( 'partner_calculate_ratio' ),
		Field::inst( 'phone' ),
		Field::inst( 'memo' ),
		Field::inst( 'r_time' )
	)

	->on( 'preCreate', function ( $editor, $values ) {
			global $club_id ;
			global $club_code ;
			$editor
					->field( 'club_id' )
					->setValue( $club_id );
			$partner_id =  '_' . $club_code . '_' . $values['club_partner_id'];
			$editor
					->field( 'partner_id' )
					->setValue($partner_id);
	} )
	->on( 'preEdit', function ( $editor, $id, $values) {
			global $club_code ;
			if (isset($values['club_partner_id'])){
				$partner_id =  '_' . $club_code . '_' . $values['club_partner_id'];
				$editor
						->field( 'partner_id' )
						->setValue($partner_id);
			}
	} )			
  //->leftJoin( 'club_info',     'club_id',          '=', 'club_info.id' )
	//->leftJoin( 'user_info', 'user_info.user_id',          '=', 'bbs_qna.user_id' )	
	//->leftJoin( 'view_order_sum', 'user_id',          '=', 'view_order_sum.user_id' )	

	->where( 'club_id' , $club_id, ($club_id === '255'? '!=': '='))
	//->where( 'date(bbs_qna.u_time)',date('Y-m-d',strtotime(date('Y-m-d').'-2 days')), '>')	
	//->where( 'date(order.buy_time)',date('Y-m-d',strtotime(date('Y-m-d').'-7 days')), '>')	

	->process( $_POST );
	//->json();
	
	//====================
	if ($user_level > 99) {
$sql = "select *, count(u.user_id) as user_count, p.id as id, p.partner_id as partner_id, p.phone as phone, p.r_time as r_time, sum(money_real) as money_real_all, sum(money_service) as money_service_all from partner_info as p left outer join  club_info as c  on p.club_id = c.id left outer join user_info as u on u.partner_id = p.partner_id  left outer join (select u.partner_id p_id, sum(if(b.banking_type = 'I' and b.stat = 'E',b.amount,0)) AS `deposit_money_all`, sum(if(b.banking_type = 'O' and b.stat = 'E',b.amount,0)) AS `withdraw_money_all` from user_info u left outer join `banking` b on u.user_id = b.user_id where date(b.u_time) >= date('" . $sdate . "') and  date(b.u_time) <= date('" . $edate . "') and u.partner_id != '' group by u.partner_id ) as b on u.partner_id = b.p_id left outer join (select u.partner_id p_id, sum(o.`bet_money`) AS `bet_money_all`,sum(if((o.`money_type` = 'R'),o.`bet_money`,0)) AS `bet_money_real_all`,sum(if((o.`money_type` = 'S'),o.`bet_money`,0)) AS `bet_money_service_all`,sum(o.`service_money`) AS `service_money_all`,sum(o.`result_money`) AS `result_money_all`,(sum(if((o.`money_type` = 'R'),o.`bet_money`,0)) - sum(o.`result_money`)) AS `profit` from user_info u left outer join `order`o on u.user_id = o.user_id  where ((o.`stat` <> 'C') and (o.`stat` <> 'R')) and date(o.buy_time) >= date('" . $sdate . "') and  date(o.buy_time) <= date('" . $edate . "') and u.partner_id != '' group by u.partner_id) as o on u.partner_id = o.p_id LEFT OUTER JOIN (SELECT u.partner_id p_id, SUM(`cut_money`) AS `cut_money_all` FROM user_info u left outer join `cut_result_money` c on u.user_id = c.user_id  WHERE DATE(c_date) >= DATE('" . $sdate . "') AND DATE(c_date) <= DATE('" . $edate . "') and u.partner_id != '' GROUP BY u.partner_id) AS cr ON u.partner_id = cr.p_id where c.club_level > 0 and c.id " . ($club_id === '255'? '!= ': '= ') . $club_id . " group by p.partner_id" ;
	}
	else{
		
	}

include (__DIR__ . '/../../../application/configs/configdb.php');
$data = array();
$mysqli = new mysqli($host, $user, $password, $dbname);
 // 연결 오류 발생 시 스크립트 종료
 if ($mysqli->connect_errno) {
    die('Connect Error: '.$mysqli->connect_error);
}

			if ($result = $mysqli->query($sql)) {
			// 레코드 출력
			//$o = array();
						while ($row = mysqli_fetch_object($result)) {
							$row->DT_RowId = 'row_' . $row->id;
							//print_r($row->id);
							$data[] = $row;
								/*
								//$t = new stdClass();

								$t->id = $row->id;
												//echo $row->name;
								$t->name = $row->name;
								$t->country = $row->country;
								$o[] = $t;
								unset($t);*/
						}
				} else {
						$data = array( 0 => 'empty');
			}
			$d = new stdClass();
			$d->data = $data;
			echo json_encode($d, JSON_UNESCAPED_UNICODE);
			
			$result->free(); //메모리해제

unset($data);
// 접속 종료
$mysqli->close();
