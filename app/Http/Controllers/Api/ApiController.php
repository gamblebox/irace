<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ApiController extends Controller
{
    // Register API (POST)
    public function register(Request $request)
    {

        // Data validation
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Create user
        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            "status" => true,
            "message" => "User created successfully"
        ]);
    }

    // Login API (POST)
    public function login(Request $request)
    {

        // Data validation
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Check credentials
        if (Auth::attempt([
            'email' => $request->email,
            'password' => $request->password
        ])) {
            // User authenticated
            $user = Auth::user();

            $token = $user->createToken('myToken')->accessToken;

            return response()->json([
                "status" => true,
                "token" => $token,
                "message" => "User authenticated successfully"
            ]);
        } else {
            return response()->json([
                "status" => false,
                "message" => "Invalid credentials"
            ]);
        }
    }

    // Profile API (GET)
    public function profile()
    {
        $user = Auth::user();

        return response()->json([
            "status" => true,
            "message" => "Profile information",
            "data" => $user
        ]);
    }

    // Logout API (GET)
    public function logout()
    {
        // Auth::user()->tokens()->delete();
        auth()->user()->token()->revoke();

        return response()->json([
            "status" => true,
            "message" => "User logged out successfully"
        ]);
    }

    // create branch API (POST)
    public function createBranch(Request $request)
    {
        $branchId = $request->branchId;
        $sql = "
            insert into branch_place (branch_id, place_id) select ?, id from place
        ";
        DB::insert($sql, [$branchId]);

        return response()->json([
            "status" => true,
            "message" => "Create Branch successfully"
        ]);
    }

    // get today race API (POST)
    public function getTodayRace(Request $request)
    {
        $branchId = $request->branchId;
        $raceDay = $request->raceDay;
        $ta = $request->ta;

        $raceDayEnd = date('Y-m-d', strtotime($raceDay . '+' . '1' . ' days'));

        $data = array();

        $sql = "
            SELECT
                race.id,
                race.id AS race_id,
                place.name AS place_name,
                place.place_code AS place_code,
                place.id AS place_id,
                association_info.code AS association_code,
                association_info.id AS association_id,
                association_info.name AS association_name,
                association_info.a_baedang_info_bok_1,
                association_info.a_baedang_info_bok_2,
                association_info.a_baedang_info_bokyun_1,
                association_info.a_baedang_info_bokyun_2,
                association_info.a_baedang_info_ssang_1,
                association_info.a_baedang_info_ssang_2,
                place.fast_report,
                association_info.a_broad_info,
                association_info.a_baedang_info_sambok_1,
                place.own_id,
                association_info.a_baedang_info_total_1,
                race.start_time,
                race.own_race_no,
                race.race_no,
                race.race_length,
                race.entry_count,
                race.remark,
                race.stat,
                race.pb_stat,
                race.cancel_entry_no,
                race.broadcast_channel,
                place.broad_info,
                race.rk_race_code
            FROM
                race
                LEFT OUTER JOIN `place` ON race.place_id = place.id
                LEFT OUTER JOIN `branch_place` ON branch_place.place_id = place.id
                LEFT OUTER JOIN `association_info` ON place.association_id = association_info.id
            WHERE
                race.isuse = 'Y'
                AND branch_place.isuse = 'Y'
                AND branch_place.branch_id = ?
                AND((place.id = 6
                    AND race.remark = '중계')
                OR(place.id != 6))
                AND association_info.code IN({$ta})
                AND race.start_time >= ?
                AND race.start_time < ?
            GROUP BY
                race.place_id,
                race.race_no
            ORDER BY
                race.start_time
        ";

        // print_r($sql);
        // exit();

        $data['race'] = DB::select($sql, [
            $branchId,
            // $ta,
            $raceDay,
            $raceDayEnd,
        ]);


        // return response()->json([
        //     "status" => true,
        //     "message" => "Get today race successfully",
        //     "data" => $data
        // ]);

        $sql = "
            SELECT
                place.place_code AS place_code,
                association_info.code AS association_code,
                association_info.id AS association_id,
                place.name AS place_name,
                place.e_name,
                place.id AS place_id,
                COUNT(race.id) AS race_count,
                start_time
            FROM
                race
                LEFT OUTER JOIN `place`ON race.place_id = place.id
                LEFT OUTER JOIN `branch_place` ON branch_place.place_id = place.id
                LEFT OUTER JOIN `association_info` ON place.association_id = association_info.id
            WHERE
                race.isuse = 'Y'
                AND branch_place.isuse = 'Y'
                AND branch_place.branch_id = ?
                AND((place.id = 6
                    AND race.remark = '중계')
                OR(place.id != 6))
                AND association_info.code IN($ta)
                AND race.start_time >= ?
                AND race.start_time < ?
            GROUP BY
                place.place_code
            ORDER BY
                race.start_time
        ";

        $data['place'] = DB::select($sql, [
            $branchId,
            // $ta,
            $raceDay,
            $raceDayEnd,
        ]);

        return response()->json([
            "status" => true,
            "message" => "Get today race successfully",
            "data" => $data
        ]);
    }

    // get association information API (POST)
    public function getAssociationInfo(Request $request)
    {
        $sql = "SELECT * FROM `info` WHERE  isuse = 'Y' and code != '' order by association_name, type asc";

        $data = DB::select($sql);

        return response()->json([
            "status" => true,
            "message" => "Get association information successfully",
            "data" => $data
        ]);
    }

    // get change race info API (POST)
    public function getRaceChangeInfo(Request $request)
    {
        $raceId = $request->raceId;

        $sql = "SELECT * FROM `race_change_info` WHERE  `race_id`= ?";

        $data = DB::select($sql, [$raceId]);

        return response()->json([
            "status" => true,
            "message" => "Get race change successfully",
            "data" => $data
        ]);
    }

    // get race result API (POST)
    public function getRaceResult(Request $request)
    {
        $ta = $request->ta;

        $sql = "
            SELECT
                *,
                DATE_FORMAT(race.start_time, '%H:%i') AS time,
                place.name AS place_name,
                association_info.name AS association_name
            FROM
                `race`
                LEFT OUTER JOIN `place` ON race.place_id = place.id
                LEFT OUTER JOIN `association_info` ON place.association_id = association_info.id
            WHERE
                stat = 'E'
                AND race.start_time >= date(now())
                AND race.start_time < date_add(DATE(NOW()), INTERVAL + 1 DAY)
                AND association_info.code in(" . $ta . ")
                AND association_info.code != 'powerball'
            ORDER BY
                race.start_time DESC
        ";

        $data = DB::select($sql);

        echo json_encode($data);
        exit();

        return response()->json([
            "status" => true,
            "message" => "Get race result successfully",
            "data" => $data
        ]);
    }

    // get race info API (POST)
    public function getRaceInfo(Request $request)
    {
        $raceId = $request->raceId;

        $sql = "SELECT *, race.id AS id FROM race LEFT JOIN place ON place.id = race.place_id WHERE race.id = ?";

        $data = DB::select($sql, [$raceId]);

        return response()->json([
            "status" => true,
            "message" => "Get race info successfully",
            "data" => $data[0],
        ]);
    }

    // get race info API (POST)
    public function getPlaceInfo(Request $request)
    {
        $placeId = $request->placeId;

        $sql = "SELECT * FROM `place` WHERE  `id`= ?";

        $data = DB::select($sql, [$placeId]);

        return response()->json([
            "status" => true,
            "message" => "Get place info successfully",
            "data" => $data
        ]);
    }
}
