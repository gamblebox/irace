<?php

// $club_id = 1;
extract($_POST);
$edate = date('Y-m-d', strtotime($edate . '+' . '1' . ' days'));
if (! isset($_SESSION)) {
    session_start();
}
// $user_id = $_SESSION['user_id'];
if ($_SESSION['club_id'] !== '255') {
    $club_id = $_SESSION['club_id'];
}
$club_id = (int) $club_id;
// $user_level = $_SESSION['user_level'];
session_write_close();
/*
 * Example PHP implementation used for the index.html example
 */

// DataTables PHP library
include ("DataTables.php");

// Alias Editor classes so they are easy to use
use DataTables\Editor, DataTables\Editor\Field, DataTables\Editor\Format, DataTables\Editor\Mjoin, DataTables\Editor\Upload, DataTables\Editor\Validate;

// Build our Editor instance and process the data coming from _POST
Editor::inst($db, 'banking')->fields(Field::inst('banking.banking_type'), Field::inst( 'partner_info.club_user_id' ), Field::inst('banking.user_id')->options('user_info', 'user_id', array(
    'user_id',
    'club_user_id',
    'nick_name'
), function ($q) {
    global $club_id;
    $q->where('club_id', $club_id, ($club_id === 255 ? '!=' : '='));
}, function ($row) {
    global $club_id;
    // return $row['club_user_id'].' ('.$row['nick_name'].')';
    if ($club_id === 255) {
        return $row['user_id'] . '(' . $row['nick_name'] . ')';
    } else {
        return $row['club_user_id'] . '(' . $row['nick_name'] . ')';
    }
}), Field::inst('user_info.nick_name'), Field::inst('user_info.club_user_id'), Field::inst('user_info.user_id'), Field::inst('user_info.partner_id'), Field::inst('user_info.money_real'), Field::inst('user_info.money_service'), Field::inst('bank_in_info.bank_in_nick_name'), Field::inst('banking.amount'), Field::inst('banking.bank_name'), Field::inst('banking.bank_account_no'), Field::inst('banking.bank_account_name'), Field::inst('banking.r_time'), Field::inst('banking.u_time'), Field::inst('banking.stat'), Field::inst('banking.isfirst'))
    ->leftJoin('user_info', 'user_info.user_id', '=', 'banking.user_id')
    ->leftJoin('user_info as partner_info', 'partner_info.user_id', '=', 'user_info.partner_id')
    ->leftJoin('bank_in_info', 'bank_in_info.id', '=', 'user_info.bank_in_bank_id')
    ->where('user_info.user_level', - 3, '>')
    ->where('user_info.club_id', $club_id, ($club_id === 255 ? '!=' : '='))
    ->where('banking.banking_type', 'I', '=')
    ->where('banking.u_time', $sdate, '>=')
    ->where('banking.u_time', $edate, '<')
    ->process($_POST)
    ->json();


