<?php
/**
 * Created by PhpStorm.
 * User: evgeniy
 * Date: 28.04.16
 * Time: 17:41
 */

namespace reg2005\PmPayLaravel\Http\Controllers;

use App\Http\Controllers\Controller;
use App;
use reg2005\PmPayLaravel\Lib\Pm;

class PmController extends Controller
{

    public function index()
    {
        $ps = (new Pm() )->run();

        return response()->json($ps);
    }
}