<?php
extract($_POST);
if (!isset($_SESSION)) {
	session_start();
}
$club_id = $_SESSION['club_id'];
$club_id = (int) $club_id;
$user_level = $_SESSION['user_level'];
session_write_close();
/*
 * Example PHP implementation used for the index.html example
 */

// DataTables PHP library
include "DataTables.php";

// Alias Editor classes so they are easy to use
use
DataTables\ Editor,
DataTables\ Editor\ Field,
DataTables\ Editor\ Format,
DataTables\ Editor\ Mjoin,
DataTables\ Editor\ Upload,
DataTables\ Editor\ Validate;

// Build our Editor instance and process the data coming from _POST
Editor::inst($db, 'club_info')->fields(
	//Field::inst( 'banking_type' ),
	Field::inst('id'),
	Field::inst('club_name'),
	Field::inst('club_code'),
	Field::inst('domain'),
	Field::inst('domain_open'),
	Field::inst('isjoin'),
	Field::inst('domain_isjoin'),
	Field::inst('m_isjoin'),
	Field::inst('support_mobile'),
	Field::inst('isuse_mobile'),
	Field::inst('isuse_top_info'),
	Field::inst('operation'),
	Field::inst('admin_theme'),
	Field::inst('club_top_info'),
	Field::inst('club_top_notice'),
	Field::inst('club_level'),
	Field::inst('club_broad_srv'),
	Field::inst('finish_time_offset'),
	Field::inst('korea_race_finish_time_offset'),
	Field::inst('korea_race_sb_finish_time_offset'),
	Field::inst('korea_race_j_finish_time_offset'),
	Field::inst('japan_race_finish_time_offset'),
	Field::inst('jra_race_finish_time_offset'),
	Field::inst('jcycle_race_finish_time_offset'),
	Field::inst('jboat_race_finish_time_offset'),
	Field::inst('jbike_race_finish_time_offset'),
	Field::inst('osr_race_finish_time_offset'),
	Field::inst('osh_race_finish_time_offset'),
	Field::inst('osg_race_finish_time_offset'),
	Field::inst('cycle_race_finish_time_offset'),
	Field::inst('boat_race_finish_time_offset'),
	Field::inst('auto_next'),
	Field::inst('ischoicerace'),
	Field::inst('ischoiceplace'),
	Field::inst('isbackupbet'),
	Field::inst('bet_unit'),
	Field::inst('tawk_id'),
	Field::inst('withdraw_bank_info_confirm'),
	Field::inst('rolling_cut'),
	Field::inst('rolling_ratio'),
	Field::inst('rolling_race_count'),
	Field::inst('rolling_powerball_count'),
	Field::inst('isuse_korea_race_sambok'),
	Field::inst('dup_login'),
	Field::inst('iscalendar'),
	Field::inst('calendar_deposit'),
	Field::inst('calendar_bet'),
	Field::inst('calendar_set'),
	Field::inst('recommender_service'),
	Field::inst('result_money_offset'),
	Field::inst('powerball_result_money_offset')->validator(Validate::minNum( 10000 )),
	Field::inst('korea_race_sb_result_money_offset')->validator(Validate::minNum( 10000 )),
	Field::inst('korea_race_j_result_money_offset')->validator(Validate::minNum( 10000 )),
	Field::inst('japan_race_result_money_offset')->validator(Validate::minNum( 10000 )),
	Field::inst('jra_race_result_money_offset')->validator(Validate::minNum( 10000 )),
	Field::inst('jcycle_race_result_money_offset')->validator(Validate::minNum( 10000 )),
	Field::inst('jboat_race_result_money_offset')->validator(Validate::minNum( 10000 )),
	Field::inst('jbike_race_result_money_offset')->validator(Validate::minNum( 10000 )),
	Field::inst('osr_race_result_money_offset')->validator(Validate::minNum( 10000 )),
	Field::inst('osh_race_result_money_offset')->validator(Validate::minNum( 10000 )),
	Field::inst('osg_race_result_money_offset')->validator(Validate::minNum( 10000 )),
	Field::inst('cycle_race_result_money_offset')->validator(Validate::minNum( 10000 )),
	Field::inst('boat_race_result_money_offset')->validator(Validate::minNum( 10000 )),
	Field::inst('allin_service_isauto'),
	Field::inst('allin_service'),
	Field::inst('allin_service_min_offset'),
	Field::inst('allin_service_max_offset'),
	Field::inst('allin_service2'),
	Field::inst('allin_service_min_offset2'),
	Field::inst('allin_service_max_offset2'),
	Field::inst('korea_race_bet_service'),
	Field::inst('korea_race_bet_service_samssang'),
	Field::inst('korea_race_bet_service_sambok'),
	Field::inst('korea_race_bet_service_bokyun'),
	Field::inst('korea_race_bet_service_dan'),
	Field::inst('korea_race_bet_service_yun'),
	Field::inst('japan_race_bet_service'),
	Field::inst('japan_race_bet_service_samssang'),
	Field::inst('japan_race_bet_service_sambok'),
	Field::inst('japan_race_bet_service_bokyun'),
	Field::inst('japan_race_bet_service_dan'),
	Field::inst('japan_race_bet_service_yun'),
	Field::inst('jra_race_bet_service'),
	Field::inst('jra_race_bet_service_samssang'),
	Field::inst('jra_race_bet_service_sambok'),
	Field::inst('jra_race_bet_service_bokyun'),
	Field::inst('jra_race_bet_service_dan'),
	Field::inst('jra_race_bet_service_yun'),
	Field::inst('jcycle_race_bet_service'),
	Field::inst('jcycle_race_bet_service_samssang'),
	Field::inst('jcycle_race_bet_service_sambok'),
	Field::inst('jcycle_race_bet_service_bokyun'),
	Field::inst('jboat_race_bet_service'),
	Field::inst('jboat_race_bet_service_samssang'),
	Field::inst('jboat_race_bet_service_sambok'),
	Field::inst('jboat_race_bet_service_bokyun'),
	Field::inst('jbike_race_bet_service'),
	Field::inst('jbike_race_bet_service_samssang'),
	Field::inst('jbike_race_bet_service_sambok'),
	Field::inst('jbike_race_bet_service_bokyun'),
	Field::inst('osr_race_bet_service'),
	Field::inst('osr_race_bet_service_samssang'),
	Field::inst('osr_race_bet_service_sambok'),
	Field::inst('osr_race_bet_service_bokyun'),
	Field::inst('osr_race_bet_service_dan'),
	Field::inst('osr_race_bet_service_yun'),
	Field::inst('osh_race_bet_service'),
	Field::inst('osh_race_bet_service_samssang'),
	Field::inst('osh_race_bet_service_sambok'),
	Field::inst('osh_race_bet_service_bokyun'),
	Field::inst('osh_race_bet_service_dan'),
	Field::inst('osh_race_bet_service_yun'),
	Field::inst('osg_race_bet_service'),
	Field::inst('osg_race_bet_service_samssang'),
	Field::inst('osg_race_bet_service_sambok'),
	Field::inst('osg_race_bet_service_bokyun'),
	Field::inst('osg_race_bet_service_dan'),
	Field::inst('osg_race_bet_service_yun'),
	Field::inst('cycle_race_bet_service'),
	Field::inst('cycle_race_bet_service_sambok'),
	Field::inst('cycle_race_bet_service_samssang'),
	Field::inst('cycle_race_bet_service_bokyun'),
	Field::inst('cycle_race_bet_service_dan'),
	Field::inst('cycle_race_bet_service_yun'),
	Field::inst('boat_race_bet_service'),
	Field::inst('boat_race_bet_service_sambok'),
	Field::inst('boat_race_bet_service_samssang'),
	Field::inst('boat_race_bet_service_bokyun'),
	Field::inst('boat_race_bet_service_dan'),
	Field::inst('boat_race_bet_service_yun'),
	Field::inst('deposit_service_first'),
	Field::inst('deposit_service_first_min_offset'),
	Field::inst('deposit_service_first_max_offset'),
	Field::inst('deposit_service'),
	Field::inst('deposit_service_offset'),
	Field::inst('bet_limit_bokyun_use'),
	Field::inst('bet_limit_sambok_use'),
	Field::inst('bet_limit_samssang_use'),
	Field::inst('bet_limit_bokyun'),
	Field::inst('bet_limit_sambok'),
	Field::inst('bet_limit_samssang'),
	Field::inst('bet_limit_japanrace_bokyun_use'),
	Field::inst('bet_limit_japanrace_sambok_use'),
	Field::inst('bet_limit_japanrace_samssang_use'),
	Field::inst('bet_limit_japanrace_bokyun'),
	Field::inst('bet_limit_japanrace_sambok'),
	Field::inst('bet_limit_japanrace_samssang'),
	Field::inst('bet_limit_jra_race_bokyun_use'),
	Field::inst('bet_limit_jra_race_sambok_use'),
	Field::inst('bet_limit_jra_race_samssang_use'),
	Field::inst('bet_limit_jra_race_bokyun'),
	Field::inst('bet_limit_jra_race_sambok'),
	Field::inst('bet_limit_jra_race_samssang'),
	Field::inst('bet_limit_jcycle_race_sambok_use'),
	Field::inst('bet_limit_jcycle_race_sambok'),
	Field::inst('bet_limit_jboat_race_sambok_use'),
	Field::inst('bet_limit_jboat_race_sambok'),

	Field::inst('bet_limit_oe'),
	Field::inst('bet_limit_oe_ratio'),
	Field::inst('pb_race_ticketing_entry')->validator(Validate::minNum( 7 )),
	Field::inst('isuse_pb_service_money'),
	Field::inst('bet_limit_pb'),
	Field::inst('bet_limit_pb_ratio'),
	Field::inst('is_nb_uo_var'),
	Field::inst('isuse_pbcombo'),
	Field::inst('nb_uo_var_ratio')->validator(Validate::maxNum( 100 )),
	Field::inst('odds_base_nbuo'),
	Field::inst('odds_odd'),
	Field::inst('odds_even'),
	Field::inst('odds_pbodd'),
	Field::inst('odds_pbeven'),
	Field::inst('odds_pbunder'),
	Field::inst('odds_pbover'),
	Field::inst('odds_nbodd'),
	Field::inst('odds_nbeven'),
	Field::inst('odds_nbunder'),
	Field::inst('odds_nbover'),
	Field::inst('isuse_powerball_service_money'),
	Field::inst('bet_limit_powerball_service_money_ratio'),
	Field::inst('powerballcombo_count_limit')->validator(Validate::minNum( 1 )),
	Field::inst('powerballcombo_odds_limit')->validator(Validate::minNum( 2 )),
	Field::inst('powerballcombo_ratio'),
	Field::inst('bet_limit_powerball'),
	Field::inst('bet_limit_powerball_pb'),
	Field::inst('bet_limit_powerball_nb'),
	Field::inst('bet_limit_powerball_mf'),
	Field::inst('powerball_finish_time_offset'),
	Field::inst('isuse_powerballcombo'),
	Field::inst('odds_powerballodd'),
	Field::inst('odds_powerballeven'),
	Field::inst('odds_powerballunder'),
	Field::inst('odds_powerballover'),
	Field::inst('odds_ballsumodd'),
	Field::inst('odds_ballsumeven'),
	Field::inst('odds_ballsumunder'),
	Field::inst('odds_ballsumover'),
	Field::inst('odds_ballsumbig'),
	Field::inst('odds_ballsummiddle'),
	Field::inst('odds_ballsumsmall'),
	
	Field::inst('odds_powerballodd_ballsumodd'),
	Field::inst('odds_powerballodd_ballsumeven'),
	Field::inst('odds_powerballeven_ballsumodd'),
	Field::inst('odds_powerballeven_ballsumeven'),
	Field::inst('isuse_powerballoe_ballsumoe'),
	Field::inst('odds_ballsumodd_ballsumunder'),
	Field::inst('odds_ballsumodd_ballsumover'),
	Field::inst('odds_ballsumeven_ballsumunder'),
	Field::inst('odds_ballsumeven_ballsumover'),
	Field::inst('isuse_ballsumoe_ballsumuo'),
	Field::inst('odds_ballsumodd_ballsumbig'),
	Field::inst('odds_ballsumodd_ballsummiddle'),
	Field::inst('odds_ballsumodd_ballsumsmall'),
	Field::inst('odds_ballsumeven_ballsumbig'),
	Field::inst('odds_ballsumeven_ballsummiddle'),
	Field::inst('odds_ballsumeven_ballsumsmall'),
	Field::inst('isuse_ballsumoe_ballsumbms'),
	
	Field::inst('korea_race_ticketing_entry'),
	Field::inst('japan_race_ticketing_entry'),
	Field::inst('jra_race_ticketing_entry'),
	Field::inst('jcycle_race_ticketing_entry'),
	Field::inst('jboat_race_ticketing_entry'),
	Field::inst('jbike_race_ticketing_entry'),
	Field::inst('osr_race_ticketing_entry'),
	Field::inst('osh_race_ticketing_entry'),
	Field::inst('osg_race_ticketing_entry'),
	Field::inst('cycle_race_ticketing_entry'),
	Field::inst('boat_race_ticketing_entry'),
	Field::inst('korea_race_ticketing_bokyun_entry'),
	Field::inst('korea_race_ticketing_sambok_entry'),
	Field::inst('korea_race_ticketing_samssang_entry'),
	Field::inst('korea_race_ticketing_dan_entry'),
	Field::inst('korea_race_ticketing_yun_entry'),
	Field::inst('japan_race_ticketing_bokyun_entry'),
	Field::inst('japan_race_ticketing_sambok_entry'),
	Field::inst('japan_race_ticketing_samssang_entry'),
	Field::inst('japan_race_ticketing_dan_entry'),
	Field::inst('japan_race_ticketing_yun_entry'),
	Field::inst('jra_race_ticketing_bokyun_entry'),
	Field::inst('jra_race_ticketing_sambok_entry'),
	Field::inst('jra_race_ticketing_samssang_entry'),
	Field::inst('jra_race_ticketing_dan_entry'),
	Field::inst('jra_race_ticketing_yun_entry'),
	Field::inst('osr_race_ticketing_bokyun_entry'),
	Field::inst('osr_race_ticketing_sambok_entry'),
	Field::inst('osr_race_ticketing_samssang_entry'),
	Field::inst('osr_race_ticketing_dan_entry'),
	Field::inst('osr_race_ticketing_yun_entry'),
	Field::inst('osh_race_ticketing_bokyun_entry'),
	Field::inst('osh_race_ticketing_sambok_entry'),
	Field::inst('osh_race_ticketing_samssang_entry'),
	Field::inst('osh_race_ticketing_dan_entry'),
	Field::inst('osh_race_ticketing_yun_entry'),
	Field::inst('osg_race_ticketing_bokyun_entry'),
	Field::inst('osg_race_ticketing_sambok_entry'),
	Field::inst('osg_race_ticketing_samssang_entry'),
	Field::inst('osg_race_ticketing_dan_entry'),
	Field::inst('osg_race_ticketing_yun_entry'),
	Field::inst('korea_race_ticketing_type'),
	Field::inst('japan_race_ticketing_type'),
	Field::inst('jra_race_ticketing_type'),
	Field::inst('jcycle_race_ticketing_type'),
	Field::inst('jboat_race_ticketing_type'),
	Field::inst('jbike_race_ticketing_type'),
	Field::inst('osr_race_ticketing_type'),
	Field::inst('osh_race_ticketing_type'),
	Field::inst('osg_race_ticketing_type'),
	Field::inst('cycle_race_ticketing_type'),
	Field::inst('boat_race_ticketing_type'),
	Field::inst('ticketing_association'),
	Field::inst('due_money'),
	Field::inst('due_date')
)
	->where('id', $club_id, ($club_id === 255 ? '!=' : '='))
	->process($_POST)->json();
