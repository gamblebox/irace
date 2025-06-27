<?php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_NOTICE & ~E_WARNING);

// $cut_user_level = '-1';
// $today = date('Y-m-d');
// $sdate = $today;
// $fast = TRUE;
// $check_date = date('Y-m-d', strtotime($today . '+' . '-7' . ' days'));
// $club_id = 1;
// $user_level = 100;

extract($_POST);
if (strtotime($sdate) < strtotime($check_date)) {
    $fast = FALSE;
}
if (strtotime($edate) > strtotime($today)) {
    $fast = FALSE;
}
$edate = date('Y-m-d', strtotime($edate . '+' . '1' . ' days'));

$a = $action;
if (!isset($_SESSION)) {
    session_start();
}
$user_id = $_SESSION['user_id'];
if ($_SESSION['club_id'] !== '255') {
    $club_id = (int)$_SESSION['club_id'];
}
$user_level = $_SESSION['user_level'];
$club_code = $_SESSION['club_code'];
$club_id = ( int )$club_id;
session_write_close();
/*
 * Example PHP implementation used for the index.html example
 */

// DataTables PHP library
include("DataTables.php");

// Alias Editor classes so they are easy to use
use
    DataTables\ Editor,
    DataTables\ Editor\ Field,
    DataTables\ Editor\ Format,
    DataTables\ Editor\ Mjoin,
    DataTables\ Editor\ Upload,
    DataTables\ Editor\ Validate;

// Build our Editor instance and process the data coming from _POST
$editor = Editor::inst($db, 'user_info')->fields(
    Field::inst('id'),
    Field::inst('user_id')->validator('Validate::notEmpty', array('message' => '아이디는 필수로 입력하여야 합니다'))->validator('Validate::unique', array('message' => '아이디는 중복 되지 않아야 합니다')),
    Field::inst('memo'),
    Field::inst('club_user_id'),
    Field::inst('nick_name'),
    Field::inst('partner_id')->options('user_info', 'user_id', array('user_id', 'club_user_id', 'nick_name'), function ($q) {
        global $club_id;
        global $user_level;
        global $user_id;
        $q->where('club_id', $club_id, ($club_id === 255 ? '!=' : '='));
        $q->where('user_level', 90, '>=');
        $q->where('user_level', 100, '<');
        if ($user_level < 100) {
            $q->where(function ($r) {
                global $user_id;
                $r->where('partner_id', "( with recursive cte ( user_id, partner_id ) as ( select user_id, partner_id from user_info where partner_id = '" . $user_id . "' union all select r.user_id, r.partner_id from user_info r inner join cte on r.partner_id = cte.user_id ) select distinct partner_id from cte )", 'IN', false);
                $r->or_where('user_id', $user_id);
            });
        }
    },

        function ($row) {
            global $club_id;
            //return $row['club_user_id'].' ('.$row['nick_name'].')';
            if ($club_id === 255) {
                return $row['user_id'] . '(' . $row['nick_name'] . ')';
            } else {
                return $row['club_user_id'] . '(' . $row['nick_name'] . ')';
            }
        }),
    Field::inst('club_id'),
    Field::inst('login_ip'),
    Field::inst('mobile'),
    Field::inst('mobile_web'),
    Field::inst('phone'),
    Field::inst('udomain'),
    Field::inst('recommender')->options('user_info', 'user_id', array('user_id', 'club_user_id', 'nick_name'), function ($q) {
        global $club_id;
        $q->where('club_id', $club_id, ($club_id === 255 ? '!=' : '='));
        $q->where('user_level', -4, '>');
        $q->where('user_level', 50, '<');
    },
        function ($row) {
            global $club_id;
            //return $row['club_user_id'].' ('.$row['nick_name'].')';
            if ($club_id === 255) {
                return $row['user_id'] . '(' . $row['nick_name'] . ')';
            } else {
                return $row['club_user_id'] . '(' . $row['nick_name'] . ')';
            }
        }),
    Field::inst('bank_in_bank_id')->options('bank_in_info', 'id', array('bank_in_nick_name', 'bank_in_bank_name', 'bank_in_bank_account_no', 'bank_in_bank_account_name'), function ($q) {
        global $club_id;
        $q->where(function ($r) {
            global $club_id;
            $r->where('club_id', $club_id, ($club_id === 255 ? '!=' : '='));
            $r->or_where('id', 0, '=');
        });//->where( 'isuse', 'Y', '=' );
    }, function ($row) {
        global $club_id;
        global $club_code;
        //return $row['club_user_id'].' ('.$row['nick_name'].')';
        return $row['bank_in_nick_name'] . '(' . $row['bank_in_bank_name'] . ' ' . $row['bank_in_bank_account_no'] . ' ' . $row['bank_in_bank_account_name'] . ')';
    }),
    Field::inst('user_class_id')->options('user_class', 'id', 'class_name', function ($q) {
        global $club_id;
        $q->where(function ($r) {
            global $club_id;
            $r->where('club_id', $club_id, ($club_id === 255 ? '!=' : '='));
            $r->or_where('id', 0, '=');
        });//->where( 'class_isuse', 'Y', '=' );
    }),
    Field::inst('bank_out_bank_name'),
    Field::inst('bank_out_bank_account_no'),
    Field::inst('bank_out_bank_account_name'),
    Field::inst('bank_out_bank_info_ok'),
    Field::inst('batch_allin_service'),
    Field::inst('money_real'),
    Field::inst('money_service'),
    Field::inst('user_level'),
    Field::inst('r_time')->setFormatter('Format::ifEmpty', '2000-01-01'),
    Field::inst('u_time')->setFormatter('Format::ifEmpty', '2000-01-01'),
    Field::inst('t_time')->setFormatter('Format::nullEmpty'),
    Field::inst('login_time'),
    Field::inst('stat'),
    Field::inst('u_iskralive'),
    Field::inst('result_money_offset_config')->setFormatter('Format::nullEmpty'),
    Field::inst('u_result_money_offset')->setFormatter('Format::nullEmpty'),
    Field::inst('u_powerball_result_money_offset')->setFormatter('Format::nullEmpty'),
    Field::inst('u_korea_race_sb_result_money_offset')->setFormatter('Format::nullEmpty'),
    Field::inst('u_korea_race_j_result_money_offset')->setFormatter('Format::nullEmpty'),
    Field::inst('u_japan_race_result_money_offset')->setFormatter('Format::nullEmpty'),
    Field::inst('u_jra_race_result_money_offset')->setFormatter('Format::nullEmpty'),
    Field::inst('u_jcycle_race_result_money_offset')->setFormatter('Format::nullEmpty'),
    Field::inst('u_jboat_race_result_money_offset')->setFormatter('Format::nullEmpty'),
    Field::inst('u_jbike_race_result_money_offset')->setFormatter('Format::nullEmpty'),
    Field::inst('u_osr_race_result_money_offset')->setFormatter('Format::nullEmpty'),
    Field::inst('u_osh_race_result_money_offset')->setFormatter('Format::nullEmpty'),
    Field::inst('u_osg_race_result_money_offset')->setFormatter('Format::nullEmpty'),
    Field::inst('u_cycle_race_result_money_offset')->setFormatter('Format::nullEmpty'),
    Field::inst('u_boat_race_result_money_offset')->setFormatter('Format::nullEmpty'),
    Field::inst('bet_service_config'),
    Field::inst('allin_service_config'),
    Field::inst('u_allin_service')->setFormatter('Format::nullEmpty'),
    Field::inst('u_allin_service_min_offset')->setFormatter('Format::nullEmpty'),
    Field::inst('u_allin_service_max_offset')->setFormatter('Format::nullEmpty'),
    Field::inst('u_allin_service2')->setFormatter('Format::nullEmpty'),
    Field::inst('u_allin_service_min_offset2')->setFormatter('Format::nullEmpty'),
    Field::inst('u_allin_service_max_offset2')->setFormatter('Format::nullEmpty'),
    Field::inst('game_config'),
    Field::inst('bet_limit_config'),
    Field::inst('u_bet_limit_bokyun_use'),
    Field::inst('u_bet_limit_sambok_use'),
    Field::inst('u_bet_limit_samssang_use'),
    Field::inst('u_bet_limit_bokyun')->setFormatter('Format::nullEmpty'),
    Field::inst('u_bet_limit_sambok')->setFormatter('Format::nullEmpty'),
    Field::inst('u_bet_limit_samssang')->setFormatter('Format::nullEmpty'),
    Field::inst('u_bet_limit_japanrace_bokyun_use'),
    Field::inst('u_bet_limit_japanrace_sambok_use'),
    Field::inst('u_bet_limit_japanrace_samssang_use'),
    Field::inst('u_bet_limit_japanrace_bokyun')->setFormatter('Format::nullEmpty'),
    Field::inst('u_bet_limit_japanrace_sambok')->setFormatter('Format::nullEmpty'),
    Field::inst('u_bet_limit_japanrace_samssang')->setFormatter('Format::nullEmpty'),
    Field::inst('u_bet_limit_jra_race_bokyun_use'),
    Field::inst('u_bet_limit_jra_race_sambok_use'),
    Field::inst('u_bet_limit_jra_race_samssang_use'),
    Field::inst('u_bet_limit_jra_race_bokyun')->setFormatter('Format::nullEmpty'),
    Field::inst('u_bet_limit_jra_race_sambok')->setFormatter('Format::nullEmpty'),
    Field::inst('u_bet_limit_jra_race_samssang')->setFormatter('Format::nullEmpty'),
    Field::inst('u_bet_limit_jcycle_race_sambok_use'),
    Field::inst('u_bet_limit_jcycle_race_sambok')->setFormatter('Format::nullEmpty'),
    Field::inst('u_bet_limit_jboat_race_sambok_use'),
    Field::inst('u_bet_limit_jboat_race_sambok')->setFormatter('Format::nullEmpty'),

    Field::inst('u_korea_race_ticketing_entry')->setFormatter('Format::nullEmpty'),
    Field::inst('u_japan_race_ticketing_entry')->setFormatter('Format::nullEmpty'),
    Field::inst('u_jra_race_ticketing_entry')->setFormatter('Format::nullEmpty'),
    Field::inst('u_jcycle_race_ticketing_entry')->setFormatter('Format::nullEmpty'),
    Field::inst('u_jboat_race_ticketing_entry')->setFormatter('Format::nullEmpty'),
    Field::inst('u_jbike_race_ticketing_entry')->setFormatter('Format::nullEmpty'),
    Field::inst('u_osr_race_ticketing_entry')->setFormatter('Format::nullEmpty'),
    Field::inst('u_osh_race_ticketing_entry')->setFormatter('Format::nullEmpty'),
    Field::inst('u_osg_race_ticketing_entry')->setFormatter('Format::nullEmpty'),
    Field::inst('u_cycle_race_ticketing_entry')->setFormatter('Format::nullEmpty'),
    Field::inst('u_boat_race_ticketing_entry')->setFormatter('Format::nullEmpty'),
    Field::inst('u_korea_race_ticketing_bokyun_entry')->setFormatter('Format::nullEmpty'),
    Field::inst('u_korea_race_ticketing_sambok_entry')->setFormatter('Format::nullEmpty'),
    Field::inst('u_korea_race_ticketing_samssang_entry')->setFormatter('Format::nullEmpty'),
    Field::inst('u_japan_race_ticketing_bokyun_entry')->setFormatter('Format::nullEmpty'),
    Field::inst('u_japan_race_ticketing_sambok_entry')->setFormatter('Format::nullEmpty'),
    Field::inst('u_japan_race_ticketing_samssang_entry')->setFormatter('Format::nullEmpty'),
    Field::inst('u_jra_race_ticketing_bokyun_entry')->setFormatter('Format::nullEmpty'),
    Field::inst('u_jra_race_ticketing_sambok_entry')->setFormatter('Format::nullEmpty'),
    Field::inst('u_jra_race_ticketing_samssang_entry')->setFormatter('Format::nullEmpty'),
    Field::inst('u_korea_race_ticketing_type')->setFormatter('Format::nullEmpty'),
    Field::inst('u_japan_race_ticketing_type')->setFormatter('Format::nullEmpty'),
    Field::inst('u_jra_race_ticketing_type')->setFormatter('Format::nullEmpty'),
    Field::inst('u_jcycle_race_ticketing_type')->setFormatter('Format::nullEmpty'),
    Field::inst('u_jboat_race_ticketing_type')->setFormatter('Format::nullEmpty'),
    Field::inst('u_jbike_race_ticketing_type')->setFormatter('Format::nullEmpty'),
    Field::inst('u_osr_race_ticketing_type')->setFormatter('Format::nullEmpty'),
    Field::inst('u_osh_race_ticketing_type')->setFormatter('Format::nullEmpty'),
    Field::inst('u_osg_race_ticketing_type')->setFormatter('Format::nullEmpty'),

    /*	->setFormatter( function ( $val, $data, $opts ) {
            if ( $val === '' ) {
                return null;
            } else {
                return implode( $val );
            }
        } ),*/
    Field::inst('u_cycle_race_ticketing_type')->setFormatter('Format::nullEmpty'),
    Field::inst('u_boat_race_ticketing_type')->setFormatter('Format::nullEmpty'),
    Field::inst('u_ticketing_association')->setFormatter('Format::nullEmpty'),
    Field::inst('u_korea_race_bet_service')->setFormatter('Format::nullEmpty'),
    Field::inst('u_korea_race_bet_service_samssang')->setFormatter('Format::nullEmpty'),
    Field::inst('u_korea_race_bet_service_sambok')->setFormatter('Format::nullEmpty'),
    Field::inst('u_korea_race_bet_service_bokyun')->setFormatter('Format::nullEmpty'),
    Field::inst('u_korea_race_bet_service_dan')->setFormatter('Format::nullEmpty'),
    Field::inst('u_korea_race_bet_service_yun')->setFormatter('Format::nullEmpty'),
    Field::inst('u_japan_race_bet_service')->setFormatter('Format::nullEmpty'),
    Field::inst('u_japan_race_bet_service_samssang')->setFormatter('Format::nullEmpty'),
    Field::inst('u_japan_race_bet_service_sambok')->setFormatter('Format::nullEmpty'),
    Field::inst('u_japan_race_bet_service_bokyun')->setFormatter('Format::nullEmpty'),
    Field::inst('u_japan_race_bet_service_dan')->setFormatter('Format::nullEmpty'),
    Field::inst('u_japan_race_bet_service_yun')->setFormatter('Format::nullEmpty'),
    Field::inst('u_jra_race_bet_service')->setFormatter('Format::nullEmpty'),
    Field::inst('u_jra_race_bet_service_samssang')->setFormatter('Format::nullEmpty'),
    Field::inst('u_jra_race_bet_service_sambok')->setFormatter('Format::nullEmpty'),
    Field::inst('u_jra_race_bet_service_bokyun')->setFormatter('Format::nullEmpty'),
    Field::inst('u_jra_race_bet_service_dan')->setFormatter('Format::nullEmpty'),
    Field::inst('u_jra_race_bet_service_yun')->setFormatter('Format::nullEmpty'),
    Field::inst('u_jcycle_race_bet_service')->setFormatter('Format::nullEmpty'),
    Field::inst('u_jcycle_race_bet_service_samssang')->setFormatter('Format::nullEmpty'),
    Field::inst('u_jcycle_race_bet_service_sambok')->setFormatter('Format::nullEmpty'),
    Field::inst('u_jcycle_race_bet_service_bokyun')->setFormatter('Format::nullEmpty'),
    Field::inst('u_jboat_race_bet_service')->setFormatter('Format::nullEmpty'),
    Field::inst('u_jboat_race_bet_service_samssang')->setFormatter('Format::nullEmpty'),
    Field::inst('u_jboat_race_bet_service_sambok')->setFormatter('Format::nullEmpty'),
    Field::inst('u_jboat_race_bet_service_bokyun')->setFormatter('Format::nullEmpty'),
    Field::inst('u_jbike_race_bet_service')->setFormatter('Format::nullEmpty'),
    Field::inst('u_jbike_race_bet_service_samssang')->setFormatter('Format::nullEmpty'),
    Field::inst('u_jbike_race_bet_service_sambok')->setFormatter('Format::nullEmpty'),
    Field::inst('u_jbike_race_bet_service_bokyun')->setFormatter('Format::nullEmpty'),
    Field::inst('u_osr_race_bet_service')->setFormatter('Format::nullEmpty'),
    Field::inst('u_osr_race_bet_service_samssang')->setFormatter('Format::nullEmpty'),
    Field::inst('u_osr_race_bet_service_sambok')->setFormatter('Format::nullEmpty'),
    Field::inst('u_osr_race_bet_service_bokyun')->setFormatter('Format::nullEmpty'),
    Field::inst('u_osr_race_bet_service_dan')->setFormatter('Format::nullEmpty'),
    Field::inst('u_osr_race_bet_service_yun')->setFormatter('Format::nullEmpty'),
    Field::inst('u_osh_race_bet_service')->setFormatter('Format::nullEmpty'),
    Field::inst('u_osh_race_bet_service_samssang')->setFormatter('Format::nullEmpty'),
    Field::inst('u_osh_race_bet_service_sambok')->setFormatter('Format::nullEmpty'),
    Field::inst('u_osh_race_bet_service_bokyun')->setFormatter('Format::nullEmpty'),
    Field::inst('u_osh_race_bet_service_dan')->setFormatter('Format::nullEmpty'),
    Field::inst('u_osh_race_bet_service_yun')->setFormatter('Format::nullEmpty'),
    Field::inst('u_osg_race_bet_service')->setFormatter('Format::nullEmpty'),
    Field::inst('u_osg_race_bet_service_samssang')->setFormatter('Format::nullEmpty'),
    Field::inst('u_osg_race_bet_service_sambok')->setFormatter('Format::nullEmpty'),
    Field::inst('u_osg_race_bet_service_bokyun')->setFormatter('Format::nullEmpty'),
    Field::inst('u_osg_race_bet_service_dan')->setFormatter('Format::nullEmpty'),
    Field::inst('u_osg_race_bet_service_yun')->setFormatter('Format::nullEmpty'),
    Field::inst('u_cycle_race_bet_service')->setFormatter('Format::nullEmpty'),
    Field::inst('u_cycle_race_bet_service_sambok')->setFormatter('Format::nullEmpty'),
    Field::inst('u_cycle_race_bet_service_samssang')->setFormatter('Format::nullEmpty'),
    Field::inst('u_cycle_race_bet_service_bokyun')->setFormatter('Format::nullEmpty'),
    Field::inst('u_cycle_race_bet_service_dan')->setFormatter('Format::nullEmpty'),
    Field::inst('u_cycle_race_bet_service_yun')->setFormatter('Format::nullEmpty'),
    Field::inst('u_boat_race_bet_service')->setFormatter('Format::nullEmpty'),
    Field::inst('u_boat_race_bet_service_sambok')->setFormatter('Format::nullEmpty'),
    Field::inst('u_boat_race_bet_service_samssang')->setFormatter('Format::nullEmpty'),
    Field::inst('u_boat_race_bet_service_bokyun')->setFormatter('Format::nullEmpty'),
    Field::inst('u_boat_race_bet_service_dan')->setFormatter('Format::nullEmpty'),
    Field::inst('u_boat_race_bet_service_yun')->setFormatter('Format::nullEmpty'),
    Field::inst('deposit_service_config'),
    Field::inst('u_deposit_service_first')->setFormatter('Format::nullEmpty'),
    Field::inst('u_deposit_service_first_min_offset')->setFormatter('Format::nullEmpty'),
    Field::inst('u_deposit_service_first_max_offset')->setFormatter('Format::nullEmpty'),
    Field::inst('u_deposit_service')->setFormatter('Format::nullEmpty'),
    Field::inst('u_deposit_service_offset')->setFormatter('Format::nullEmpty'),
    Field::inst('u_finish_time_offset')->setFormatter('Format::nullEmpty'),
    Field::inst('u_korea_race_finish_time_offset')->setFormatter('Format::nullEmpty'),
    Field::inst('u_korea_race_sb_finish_time_offset')->setFormatter('Format::nullEmpty'),
    Field::inst('u_korea_race_j_finish_time_offset')->setFormatter('Format::nullEmpty'),
    Field::inst('u_japan_race_finish_time_offset')->setFormatter('Format::nullEmpty'),
    Field::inst('u_jra_race_finish_time_offset')->setFormatter('Format::nullEmpty'),
    Field::inst('u_jcycle_race_finish_time_offset')->setFormatter('Format::nullEmpty'),
    Field::inst('u_jboat_race_finish_time_offset')->setFormatter('Format::nullEmpty'),
    Field::inst('u_jbike_race_finish_time_offset')->setFormatter('Format::nullEmpty'),
    Field::inst('u_osr_race_finish_time_offset')->setFormatter('Format::nullEmpty'),
    Field::inst('u_osh_race_finish_time_offset')->setFormatter('Format::nullEmpty'),
    Field::inst('u_osg_race_finish_time_offset')->setFormatter('Format::nullEmpty'),
    Field::inst('u_cycle_race_finish_time_offset')->setFormatter('Format::nullEmpty'),
    Field::inst('u_boat_race_finish_time_offset')->setFormatter('Format::nullEmpty'),
    Field::inst('finish_time_offset_config'),
    Field::inst('user_pw')
)
    ->on('preCreate', function ($editor, $values) {
        global $club_id;
        global $club_code;
        $editor->field('club_id')
            ->setValue($club_id);
        /*
         * $user_id = '_' . $club_code . '_' . $values[ 'club_user_id' ];
         * $editor
         * ->field( 'user_id' )->setValue( $user_id );
         */
    })
    ->
    on('preEdit', function ($editor, $id, $values) {
        global $club_code;
        if (isset($values['club_user_id'])) {
            $user_id = '_' . $club_code . '_' . $values['club_user_id'];
            $editor->field('user_id')
                ->setValue($user_id);
        }
    })
    ->where('club_id', $club_id, ($club_id === 255 ? '!=' : '='))
    ->process($_POST);
//->json();
$d = json_decode($editor->json(false));

include(__DIR__ . '/../../../application/configs/configdb.php');
$data = array();

$db = new PDO('mysql:host=' . $host . ';dbname=' . $dbname . ';charset=utf8mb4', $user, $password, array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
));

if ($user_level > 99) {
    $sql = "SELECT
	#SQL_NO_CACHE STRAIGHT_JOIN
	#*,
	CONCAT('row_', u.id) AS DT_RowId,
	u.id,
	u.user_id,
	u.club_user_id,
	u.club_id,
	u.user_pw,
	u.user_level,
	u.user_class_id,
	u.nick_name,
	u.phone,
	u.partner_id,
	u.recommender,
	u.partner_class_id,
	u.partner_level,
	u.partner_calculate_rule,
	u.partner_calculate_ratio,
	u.partner_calculate_sf_win_ratio,
	u.partner_calculate_sf_lose_ratio,
	u.partner_calculate_mf_win_ratio,
	u.partner_calculate_mf_lose_ratio,
	u.partner_stat,
	u.login_ip,
	u.ck,
	u.browser,
	u.mobile,
	u.mobile_web,
	u.cdomain,
	u.udomain,
	u.bank_in_bank_id,
	u.bank_out_bank_name,
	u.bank_out_bank_account_no,
	u.bank_out_bank_account_name,
	u.money_real,
	u.money_service,
	u.money_etc,
	u.money_point,
	u.result_money,
	u.stat,
	u.memo,
	u.tg,
	u.is_ticketing_club_odds,
	u.r_time,
	u.u_time,
	u.t_time,
	u.login_time,
	u.islogin,
	u.bank_out_bank_info_ok,
	u.user_config,
	u.result_money_offset_config,
	u_result_money_offset,
	u_powerball_result_money_offset,
	u_korea_race_result_money_offset,
	u_korea_race_sb_result_money_offset,
	u_korea_race_j_result_money_offset,
	u_japan_race_result_money_offset,
	u_jra_race_result_money_offset,
    u_jcycle_race_result_money_offset,
    u_jboat_race_result_money_offset,
    u_jbike_race_result_money_offset,
	u_cycle_race_result_money_offset,
	u_boat_race_result_money_offset,
	u_osr_race_result_money_offset,
	u_osh_race_result_money_offset,
	u_osg_race_result_money_offset,
	u.deposit_service_config,
	u_deposit_service_first,
	u_deposit_service_first_max_offset,
	u_deposit_service_first_min_offset,
	u_deposit_service,
	u_deposit_service_offset,
	u.finish_time_offset_config,
	u_finish_time_offset,
	u_powerball_finish_time_offset,
	u_korea_race_finish_time_offset,
	u_korea_race_sb_finish_time_offset,
	u_korea_race_j_finish_time_offset,
	u_japan_race_finish_time_offset,
	u_jra_race_finish_time_offset,
	u_jcycle_race_finish_time_offset,
	u_jboat_race_finish_time_offset,
	u_jbike_race_finish_time_offset,
	u_cycle_race_finish_time_offset,
	u_boat_race_finish_time_offset,
	u_osr_race_finish_time_offset,
	u_osh_race_finish_time_offset,
	u_osg_race_finish_time_offset,
	u.batch_allin_service,
	u_iskralive,
	u.allin_service_config,
	u_allin_service,
	u_allin_service_min_offset,
	u_allin_service_max_offset,
	u_allin_service2,
	u_allin_service_min_offset2,
	u_allin_service_max_offset2,
	u.bet_service_config,
	u.bet_limit_config,
	u_bet_limit_bokyun_use,
	u_bet_limit_sambok_use,
	u_bet_limit_samssang_use,
	u_bet_limit_bokyun,
	u_bet_limit_sambok,
	u_bet_limit_samssang,
	u_bet_limit_japanrace_bokyun_use,
	u_bet_limit_japanrace_sambok_use,
	u_bet_limit_japanrace_samssang_use,
	u_bet_limit_japanrace_bokyun,
	u_bet_limit_japanrace_sambok,
	u_bet_limit_japanrace_samssang,
	u_bet_limit_jra_race_bokyun_use,
	u_bet_limit_jra_race_sambok_use,
	u_bet_limit_jra_race_samssang_use,
	u_bet_limit_jra_race_bokyun,
	u_bet_limit_jra_race_sambok,
	u_bet_limit_jra_race_samssang,
	u_bet_limit_jcycle_race_sambok_use,
	u_bet_limit_jcycle_race_sambok,
	u_bet_limit_jboat_race_sambok_use,
	u_bet_limit_jboat_race_sambok,

	u.game_config,
	u_korea_race_ticketing_entry,
	u_japan_race_ticketing_entry,
	u_jra_race_ticketing_entry,
	u_jcycle_race_ticketing_entry,
	u_jboat_race_ticketing_entry,
	u_jbike_race_ticketing_entry,
	u_cycle_race_ticketing_entry,
	u_boat_race_ticketing_entry,
	u_osr_race_ticketing_entry,
	u_osh_race_ticketing_entry,
	u_osg_race_ticketing_entry,
	u_korea_race_ticketing_bokyun_entry,
	u_korea_race_ticketing_sambok_entry,
	u_korea_race_ticketing_samssang_entry,
	u_korea_race_ticketing_dan_entry,
	u_korea_race_ticketing_yun_entry,
	u_japan_race_ticketing_dan_entry,
	u_japan_race_ticketing_yun_entry,
	u_japan_race_ticketing_bokyun_entry,
	u_japan_race_ticketing_sambok_entry,
	u_japan_race_ticketing_samssang_entry,
	u_jra_race_ticketing_dan_entry,
	u_jra_race_ticketing_yun_entry,
	u_jra_race_ticketing_bokyun_entry,
	u_jra_race_ticketing_sambok_entry,
	u_jra_race_ticketing_samssang_entry,
	u_korea_race_ticketing_type,
	u_japan_race_ticketing_type,
	u_jra_race_ticketing_type,
	u_jcycle_race_ticketing_type,
	u_jboat_race_ticketing_type,
	u_jbike_race_ticketing_type,
	u_cycle_race_ticketing_type,
	u_boat_race_ticketing_type,
	u_osr_race_ticketing_type,
	u_osh_race_ticketing_type,
	u_osg_race_ticketing_type,
	u_ticketing_association,
	u_korea_race_bet_service,
	u_korea_race_bet_service_samssang,
	u_korea_race_bet_service_sambok,
	u_korea_race_bet_service_bokyun,
	u_korea_race_bet_service_dan,
	u_korea_race_bet_service_yun,
	u_japan_race_bet_service,
	u_japan_race_bet_service_samssang,
	u_japan_race_bet_service_sambok,
	u_japan_race_bet_service_bokyun,
	u_japan_race_bet_service_dan,
	u_japan_race_bet_service_yun,
	u_jra_race_bet_service,
	u_jra_race_bet_service_samssang,
	u_jra_race_bet_service_sambok,
	u_jra_race_bet_service_bokyun,
	u_jra_race_bet_service_dan,
	u_jra_race_bet_service_yun,
	u_jcycle_race_bet_service,
	u_jcycle_race_bet_service_samssang,
	u_jcycle_race_bet_service_sambok,
	u_jcycle_race_bet_service_bokyun,
	u_jboat_race_bet_service,
	u_jboat_race_bet_service_samssang,
	u_jboat_race_bet_service_sambok,
	u_jboat_race_bet_service_bokyun,
	u_jbike_race_bet_service,
	u_jbike_race_bet_service_samssang,
	u_jbike_race_bet_service_sambok,
	u_jbike_race_bet_service_bokyun,
	u_osr_race_bet_service,
	u_osr_race_bet_service_samssang,
	u_osr_race_bet_service_sambok,
	u_osr_race_bet_service_bokyun,
	u_osr_race_bet_service_dan,
	u_osr_race_bet_service_yun,
	u_osh_race_bet_service,
	u_osh_race_bet_service_samssang,
	u_osh_race_bet_service_sambok,
	u_osh_race_bet_service_bokyun,
	u_osh_race_bet_service_dan,
	u_osh_race_bet_service_yun,
	u_osg_race_bet_service,
	u_osg_race_bet_service_samssang,
	u_osg_race_bet_service_sambok,
	u_osg_race_bet_service_bokyun,
	u_osg_race_bet_service_dan,
	u_osg_race_bet_service_yun,
	u_cycle_race_bet_service,
	u_cycle_race_bet_service_sambok,
	u_cycle_race_bet_service_samssang,
	u_cycle_race_bet_service_bokyun,
	u_cycle_race_bet_service_dan,
	u_cycle_race_bet_service_yun,
	u_boat_race_bet_service,
	u_boat_race_bet_service_sambok,
	u_boat_race_bet_service_samssang,
	u_boat_race_bet_service_bokyun,
	u_boat_race_bet_service_dan,
	u_boat_race_bet_service_yun,
	c.club_name,
	uc.class_name,
	b.deposit_money_all,
	b.withdraw_money_all,
	o.bet_money_all,
	o.bet_money_real_all,
	o.bet_money_service_all,
	o.service_money_all,
	o.result_money_all,
	o.profit,
	cr.cut_money_all,
	s.allin_service_money_all,
	s.deposit_service_money_all,
	s.any_service_money_all,
	s.recommend_service_money_all,
	a.attendance_count
FROM
	user_info AS u
LEFT OUTER JOIN club_info AS c ON
	u.club_id = c.id
LEFT OUTER JOIN user_class AS uc ON
	u.user_class_id = uc.id
LEFT OUTER JOIN (
	SELECT
		u.user_id u_id,
		SUM(IF(b.banking_type = 'I' AND b.stat = 'E', b.amount, 0)) AS `deposit_money_all`,
		SUM(IF(b.banking_type = 'O' AND b.stat = 'E', b.amount, 0)) AS `withdraw_money_all`
	FROM
		user_info u
	LEFT OUTER JOIN `banking` b ON
		u.user_id = b.user_id
	WHERE
		u.club_id " . ($club_id === 255 ? '!= ' : '= ') . $club_id . "
		AND b.u_time >= DATE('" . $sdate . "')
		AND b.u_time < DATE('" . $edate . "')
	GROUP BY
		u.user_id) AS b ON
	u.user_id = b.u_id
LEFT OUTER JOIN (
	SELECT
		o.user_id u_id,
		SUM(o.`bet_money`) AS `bet_money_all`,
		SUM(IF((o.`money_type` = 'R'), o.`bet_money`, 0)) AS `bet_money_real_all`,
		SUM(IF((o.`money_type` = 'S'), o.`bet_money`, 0)) AS `bet_money_service_all`,
		SUM(o.`service_money`) AS `service_money_all`,
		SUM(o.`result_money`) AS `result_money_all`,
		(SUM(IF((o.`money_type` = 'R'), o.`bet_money`, 0)) - SUM(o.`result_money`)) AS `profit`
	FROM
		`order` o
	WHERE
		o.buy_time >= DATE('" . $sdate . "')
		AND o.buy_time < DATE('" . $edate . "')
		AND o.`stat` NOT IN ('C',
		'R')
		AND o.club_id " . ($club_id === 255 ? '!= ' : '= ') . $club_id . "
	GROUP BY
		o.user_id ) AS o ON
	u.user_id = o.u_id
LEFT OUTER JOIN (
	SELECT
		user_id u_id,
		SUM(`cut_money`) AS `cut_money_all`
	FROM
		cut_result_money
	LEFT OUTER JOIN race AS r ON
		r.id = race_id
	WHERE
		club_id " . ($club_id === 255 ? '!= ' : '= ') . $club_id . "
		AND r.start_time >= DATE('" . $sdate . "')
		AND r.start_time < DATE('" . $edate . "')
	GROUP BY
		user_id) AS cr ON
	u.user_id = cr.u_id
LEFT OUTER JOIN (
	SELECT
		l.user_id u_id ,
		SUM(IF(l.type = '손실금', new_money_service - old_money_service, 0)) AS allin_service_money_all ,
		SUM(IF(l.type = '충전', new_money_service - old_money_service, 0)) AS deposit_service_money_all ,
		SUM(IF(l.type = '임의 지급', new_money_service - old_money_service, 0)) AS any_service_money_all ,
		SUM(IF(l.type = '추천인', new_money_service - old_money_service, 0)) AS recommend_service_money_all
	FROM
		log_money l
	WHERE
		u_time >= DATE('" . $sdate . "')
		AND u_time < DATE('" . $edate . "')
		AND l.`type` IN ('손실금',
		'충전',
		'임의 지급',
		'추천인')
	GROUP BY
		user_id) AS s ON
	u.user_id = s.u_id
LEFT OUTER JOIN (
	SELECT
		a.user_id u_id,
		COUNT(DISTINCT a.attendance_date) AS attendance_count
	FROM
		`attendance` a
	WHERE
		a.attendance_date >= DATE('" . $sdate . "')
		AND a.attendance_date < DATE('" . $edate . "')
	GROUP BY
		a.user_id) AS a ON
	u.user_id = a.u_id
WHERE
	c.club_level > -1
	AND u.club_id " . ($club_id === 255 ? '!= ' : '= ') . $club_id . "
	AND u.user_level > " . $cut_user_level . "
	AND u.user_level < 50";
} else {
    $sql = "select STRAIGHT_JOIN *,concat('row_', u.id) as DT_RowId, u.r_time as r_time from club_info as c  right outer join user_info u on u.club_id = c.id  left outer join user_class cl on u.user_class_id = cl.id left outer join (select u.user_id u_id, sum(if(b.banking_type = 'I' and b.stat = 'E',b.amount,0)) AS `deposit_money_all`, sum(if(b.banking_type = 'O' and b.stat = 'E',b.amount,0)) AS `withdraw_money_all` from user_info u left outer join `banking` b on u.user_id = b.user_id where u.club_id " . ($club_id === 255 ? '!= ' : '= ') . $club_id . " and b.u_time >= date('" . $sdate . "') and b.u_time < date('" . $edate . "')  group by u.user_id ) as b on u.user_id = b.u_id left outer join (select o.user_id u_id, sum(o.`bet_money`) AS `bet_money_all`,sum(if((o.`money_type` = 'R'),o.`bet_money`,0)) AS `bet_money_real_all`,sum(if((o.`money_type` = 'S'),o.`bet_money`,0)) AS `bet_money_service_all`,sum(o.`service_money`) AS `service_money_all`,sum(o.`result_money`) AS `result_money_all`,(sum(if((o.`money_type` = 'R'),o.`bet_money`,0)) - sum(o.`result_money`)) AS `profit` from `order` o where o.club_id " . ($club_id === 255 ? '!= ' : '= ') . $club_id . " and o.`stat` NOT IN ('C','R') and o.buy_time >= date('" . $sdate . "') and  o.buy_time < date('" . $edate . "') group by o.user_id) as o on u.user_id = o.u_id LEFT OUTER JOIN (SELECT user_id u_id, SUM(`cut_money`) AS `cut_money_all` FROM cut_result_money LEFT OUTER JOIN race AS r ON r.id = race_id WHERE club_id " . ($club_id === 255 ? '!= ' : '= ') . $club_id . " and r.start_time >= DATE('" . $sdate . "') AND r.start_time < DATE('" . $edate . "') GROUP BY user_id) AS cr ON u.user_id = cr.u_id LEFT OUTER JOIN (SELECT l.user_id u_id
, SUM(IF(l.type = '손실금',new_money_service - old_money_service, 0)) AS allin_service_money_all
, SUM(IF(l.type = '충전',new_money_service - old_money_service, 0)) AS deposit_service_money_all
, SUM(IF(l.type = '임의 지급',new_money_service - old_money_service, 0)) AS any_service_money_all
, SUM(IF(l.type = '추천인',new_money_service - old_money_service, 0)) AS recommend_service_money_all
FROM log_money l WHERE u_time >= DATE('" . $sdate . "') AND u_time < DATE('" . $edate . "')  AND l.`type` IN ('손실금','충전','임의 지급','추천인')
GROUP BY user_id) AS s ON u.user_id = s.u_id LEFT OUTER JOIN (SELECT a.user_id u_id, COUNT(DISTINCT a.attendance_date) as attendance_count FROM `attendance`a WHERE a.attendance_date >= date('" . $sdate . "') AND a.attendance_date < date('" . $edate . "') GROUP BY a.user_id) AS a ON u.user_id = a.u_id where c.club_level > -1 and c.id " . ($club_id === 255 ? '!= ' : '= ') . $club_id . "  AND u.club_id " . ($club_id === 255 ? '!= ' : '= ') . $club_id . " and u.user_level > " . $cut_user_level . " and u.partner_id IN ( with recursive cte ( user_id, partner_id ) as ( select user_id, partner_id from user_info where partner_id = '" . $user_id . "' union all select r.user_id, r.partner_id from user_info r inner join cte on r.partner_id = cte.user_id ) select distinct partner_id from cte ) and u.user_level < 50";
$sql = "
select
    STRAIGHT_JOIN *,
    concat('row_',
    u.id) as DT_RowId,
    u.r_time as r_time 
from
    club_info as c  
right outer join
    user_info u 
        on u.club_id = c.id  
left outer join
    user_class cl 
        on u.user_class_id = cl.id 
left outer join
    (
        select
            u.user_id u_id,
            sum(if(b.banking_type = 'I' 
            and b.stat = 'E',
            b.amount,
            0)) AS `deposit_money_all`,
            sum(if(b.banking_type = 'O' 
            and b.stat = 'E',
            b.amount,
            0)) AS `withdraw_money_all` 
        from
            user_info u 
        left outer join
            `banking` b 
                on u.user_id = b.user_id 
        where
            u.club_id " . ($club_id === 255 ? '!= ' : '= ') . $club_id . " 
            and b.u_time >= date('" . $sdate . "') 
            and b.u_time < date('" . $edate . "')  
        group by
            u.user_id 
    ) as b 
        on u.user_id = b.u_id 
left outer join
    (
        select
            o.user_id u_id,
            sum(o.`bet_money`) AS `bet_money_all`,
            sum(if((o.`money_type` = 'R'),
            o.`bet_money`,
            0)) AS `bet_money_real_all`,
            sum(if((o.`money_type` = 'S'),
            o.`bet_money`,
            0)) AS `bet_money_service_all`,
            sum(o.`service_money`) AS `service_money_all`,
            sum(o.`result_money`) AS `result_money_all`,
            (sum(if((o.`money_type` = 'R'),
            o.`bet_money`,
            0)) - sum(o.`result_money`)) AS `profit` 
        from
            `order` o where
            o.club_id " . ($club_id === 255 ? '!= ' : '= ') . $club_id . " 
            and o.`stat` NOT IN (
                'C','R'
            ) 
            and o.buy_time >= date('" . $sdate . "') 
            and  o.buy_time < date('" . $edate . "') 
        group by
            o.user_id
    ) as o 
        on u.user_id = o.u_id 
LEFT OUTER JOIN
    (
        SELECT
            user_id u_id,
            SUM(`cut_money`) AS `cut_money_all` 
        FROM
            cut_result_money 
        LEFT OUTER JOIN
            race AS r 
                ON r.id = race_id 
        WHERE
            club_id " . ($club_id === 255 ? '!= ' : '= ') . $club_id . " 
            and r.start_time >= DATE('" . $sdate . "') 
            AND r.start_time < DATE('" . $edate . "') 
        GROUP BY
            user_id
    ) AS cr 
        ON u.user_id = cr.u_id 
LEFT OUTER JOIN
    (
        SELECT
            l.user_id u_id ,
            SUM(IF(l.type = '손실금',
            new_money_service - old_money_service,
            0)) AS allin_service_money_all ,
            SUM(IF(l.type = '충전',
            new_money_service - old_money_service,
            0)) AS deposit_service_money_all ,
            SUM(IF(l.type = '임의 지급',
            new_money_service - old_money_service,
            0)) AS any_service_money_all ,
            SUM(IF(l.type = '추천인',
            new_money_service - old_money_service,
            0)) AS recommend_service_money_all 
        FROM
            log_money l 
        WHERE
            u_time >= DATE('" . $sdate . "') 
            AND u_time < DATE('" . $edate . "')  
            AND l.`type` IN (
                '손실금','충전','임의 지급','추천인'
            ) 
        GROUP BY
            user_id
    ) AS s 
        ON u.user_id = s.u_id 
LEFT OUTER JOIN
    (
        SELECT
            a.user_id u_id,
            COUNT(DISTINCT a.attendance_date) as attendance_count 
        FROM
            `attendance`a 
        WHERE
            a.attendance_date >= date('" . $sdate . "') 
            AND a.attendance_date < date('" . $edate . "') 
        GROUP BY
            a.user_id
    ) AS a 
        ON u.user_id = a.u_id 
where
    c.club_level > -1 
    and c.id " . ($club_id === 255 ? '!= ' : '= ') . $club_id . "  
    AND u.club_id " . ($club_id === 255 ? '!= ' : '= ') . $club_id . " 
    and u.user_level > " . $cut_user_level . " 
    and u.partner_id IN (
        with recursive cte ( user_id, partner_id ) as ( select
            user_id,
            partner_id 
        from
            user_info 
        where
            partner_id = '" . $user_id . "' 
        union
        all select
            r.user_id,
            r.partner_id 
        from
            user_info r 
        inner join
            cte 
                on r.partner_id = cte.user_id ) select
                distinct partner_id 
        from
            cte 
    ) 
    and u.user_level < 50
";
}
// echo $sql;
// exit();

//echo $sql;Editor::ACTION_CREATE - Create a new record
//Editor::ACTION_EDIT - Edit existing data
//Editor::ACTION_DELETE - Delete existing row(s)

if (count($d->data) !== 0) {
    if ($a === Editor::ACTION_CREATE) {
        $sql .= " and u.id = " . str_replace('row_', '', $d->data[0]->DT_RowId);
        //$a = 1;
    } else if ($a === Editor::ACTION_EDIT) {
        foreach ($d->data as $value) {
            $ids[] = str_replace('row_', '', $value->DT_RowId);
        }
        $sql .= " and u.id in (" . implode(',', $ids) . ")";
        // echo $sql;
    }
    $data = array();
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $data = $stmt->fetchAll();

    foreach ($data as $key => $value) {
        $data[$key]->u_ticketing_association = explode(',', $value->u_ticketing_association);
        $data[$key]->u_korea_race_ticketing_type = explode(',', $value->u_korea_race_ticketing_type);
        $data[$key]->u_japan_race_ticketing_type = explode(',', $value->u_japan_race_ticketing_type);
        $data[$key]->u_jra_race_ticketing_type = explode(',', $value->u_jra_race_ticketing_type);
        $data[$key]->u_jcycle_race_ticketing_type = explode(',', $value->u_jcycle_race_ticketing_type);
        $data[$key]->u_jboat_race_ticketing_type = explode(',', $value->u_jboat_race_ticketing_type);
        $data[$key]->u_jbike_race_ticketing_type = explode(',', $value->u_jbike_race_ticketing_type);
        $data[$key]->u_cycle_race_ticketing_type = explode(',', $value->u_cycle_race_ticketing_type);
        $data[$key]->u_boat_race_ticketing_type = explode(',', $value->u_boat_race_ticketing_type);
    }
    $d->data = $data;
}
//$d->data = $sql;
echo json_encode($d, JSON_UNESCAPED_UNICODE);
// 접속 종료
?>