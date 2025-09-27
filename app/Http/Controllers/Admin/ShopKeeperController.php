<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\Admin\ShopKeeperDrawDetailsDataTable;
use App\DataTables\Admin\ShopkeepersDataTable;
use App\DataTables\DrawProfilLossDataTable;
use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ShopKeeperController extends Controller
{
    public function index(ShopkeepersDataTable $dataTable)
    {
        return $dataTable->render('admin.shopkeepers.index');
    }

    public function addEditShopKeeper(Request $request)
    {
        $user = null;
        if ($request->user_id) {
            $user = User::findOrfail($request->user_id);
        }

        return view('admin.shopkeepers.add', compact('user'));
    }

    public function view(ShopKeeperDrawDetailsDataTable $dataTable, Request $request)
    {
        $user = User::findOrFail($request->user_id);

        // return view('admin.shopkeepers.view', compact('user'));
        return $dataTable->render('admin.shopkeepers.view', compact('user'));
    }

    public function getShopKeeperDrawList(DrawProfilLossDataTable $dataTable,Request $request){
        $userId = $request->user_id;
        if ($request->user_id) {
            $user = User::findOrfail($request->user_id);
        }
        // ✅ Pass $dataTable properly
        return $dataTable->setUserId($userId)->render('admin.shopkeepers.drawlist',compact('user'));
    }
}
