<?php
$user_id = 'kaiji';
$date = date('Y-m-d');
$sdate = '2016-05-01';
$edate = $date;
extract($_POST);
$edate = date('Y-m-d', strtotime($edate . '+' . '1' . ' days'));
if (!isset($_SESSION)) {
	session_start();
}
$club_id = $_SESSION['club_id'];
$user_level = $_SESSION['user_level'];
session_write_close();

include(__DIR__ . '/../../../application/configs/configdb.php');
$db = new PDO('mysql:host=' . $host . ';dbname=' . $dbname . ';charset=utf8mb4', $user, $password, array(
	PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
	PDO::ATTR_EMULATE_PREPARES => false,
	PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
));

$partner_line_list =  array();
$sql = "WITH recursive cte (user_id, partner_id, user_level) AS (
SELECT user_id, partner_id, user_level
FROM user_info
WHERE partner_id = '" . $partner_id . "' UNION ALL
SELECT r.user_id, r.partner_id, r.user_level
FROM user_info r
INNER JOIN cte ON r.partner_id = cte.user_id)
SELECT DISTINCT user_id AS partner_id
FROM cte
WHERE user_level > 50";
$stmt = $db->prepare($sql);
$stmt->execute();
$result = $stmt->fetchAll();
foreach ($result as $value) {
	array_push($partner_line_list, $value->partner_id);
}
array_unshift($partner_line_list, $partner_id);
$partner_line = "'" . implode("','", $partner_line_list) . "'";

/*
 * Example PHP implementation used for the index.html example
 */

// DataTables PHP library
include("DataTables.php");

// Alias Editor classes so they are easy to use
use
	DataTables\Editor,
	DataTables\Editor\Field,
	DataTables\Editor\Format,
	DataTables\Editor\Mjoin,
	DataTables\Editor\Upload,
	DataTables\Editor\Validate;

// Build our Editor instance and process the data coming from _POST
Editor::inst($db, 'banking')
	->fields(
		Field::inst('banking.banking_type'),
		Field::inst('banking.user_id'),
		Field::inst('user_info.nick_name'),
		Field::inst('user_info.user_id'),
		Field::inst('banking.amount'),
		Field::inst('banking.bank_name'),
		Field::inst('banking.bank_account_no'),
		Field::inst('banking.bank_account_name'),
		Field::inst('banking.r_time'),
		Field::inst('banking.u_time'),
		Field::inst('banking.stat')
		//->options( 'place', 'id', 'name' ),
		//Field::inst( 'place_id' ),
		//Field::inst( 'race.race_no' ),

		//Field::inst( 'race.start_time' ),

		//Field::inst( 'race.entry_count' ),
		//Field::inst( 'race.stat' )


		/*		Field::inst( 'first_name' )->validator( 'Validate::notEmpty' ),
		Field::inst( 'last_name' )->validator( 'Validate::notEmpty' ),
		Field::inst( 'position' ),
		Field::inst( 'email' ),
		Field::inst( 'office' ),
		Field::inst( 'extn' ),
		Field::inst( 'age' )
			->validator( 'Validate::numeric' )
			->setFormatter( 'Format::ifEmpty', null ),
		Field::inst( 'salary' )
			->validator( 'Validate::numeric' )
			->setFormatter( 'Format::ifEmpty', null ),
		Field::inst( 'start_date' )
			->validator( 'Validate::dateFormat', array(
				"format"  => Format::DATE_ISO_8601,
				"message" => "Please enter a date in the format yyyy-mm-dd"
			) )
			->getFormatter( 'Format::date_sql_to_format', Format::DATE_ISO_8601 )
			->setFormatter( 'Format::date_format_to_sql', Format::DATE_ISO_8601 )*/
	)
	->leftJoin('user_info', 'user_info.user_id',          '=', 'banking.user_id')
	->where(function ($q) {
		global $partner_line;
		$q->where('user_info.partner_id', '(' . $partner_line . ')', 'IN', false);
	})
	->where('banking.u_time', $sdate, '>=')
	->where('banking.u_time', $edate, '<')
	->where('banking.stat', 'E', '=')

	->process($_POST)
	->json();
