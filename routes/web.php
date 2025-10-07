<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Admin\WalletController as AdminWalletController;
use App\Http\Controllers\User\WalletController as UserWalletController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Livewire\CrossTrace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use App\Services\TelegramHelper;
use App\Models\Game;
use App\Models\DrawDetail;


Route::get('/', function () {
    return view('welcome');
});

Route::controller(AuthController::class)->group(function () {
    Route::get('login', 'showLoginForm')->name('login');
    Route::post('login', 'login');
});

Route::middleware(['auth'])->group(function () {
    Route::get('admin/cross-trace', function () {
        return view('livewire.admin.cross-trace'); // a simple blade that includes the Livewire component
    })->name('admin.cross-trace');
});



Route::post('/api/telegram/webhook', function (Request $request) {
    try {
        $update = $request->all();
        \Log::info('Telegram Update (web route):', $update);

        // Allowed IDs
        $allowedIds = array_filter(array_map('trim', explode(',', env('TELEGRAM_ALLOWED_IDS', ''))));
        $allowedIds = array_map(function ($v) {
            return is_numeric($v) ? (int) $v : $v;
        }, $allowedIds);

        // small safe getter
        $get = function ($arr, $keys, $default = null) {
            $carry = $arr;
            foreach ((array)$keys as $k) {
                if (!is_array($carry) || !array_key_exists($k, $carry)) return $default;
                $carry = $carry[$k];
            }
            return $carry;
        };

        // MESSAGE (text)
        if (isset($update['message'])) {
            $chatId = (int) $get($update, ['message', 'chat', 'id'], 0);
            $text   = trim((string) $get($update, ['message', 'text'], ''));

            if (!in_array($chatId, $allowedIds, true)) {
                try {
                    \App\Services\TelegramHelper::sendMessage($chatId, "❌ Access Denied. You are not authorized.");
                } catch (\Throwable $e) {
                    \Log::warning("Failed to send Access Denied message to {$chatId}: " . $e->getMessage());
                }
                return response('OK', 200);
            }

            // /start -> show games
            if ($text === '/start') {
                $games = \App\Models\Game::orderBy('id')->get();
                $buttons = [];
                foreach ($games as $g) {
                    $buttons[] = [['text' => $g->name, 'callback_data' => 'game_' . $g->id]];
                }
                \App\Services\TelegramHelper::sendKeyboard($chatId, "🎮 Select a Game:", $buttons);
                return response('OK', 200);
            }

            // awaiting claims stage
            if (Cache::has("telegram_stage_{$chatId}")) {
                $stage = Cache::get("telegram_stage_{$chatId}");
                if (isset($stage['step']) && $stage['step'] === 'await_claims') {
                    $draw = \App\Models\DrawDetail::find($stage['draw_id']);
                    if (!$draw) {
                        \App\Services\TelegramHelper::sendMessage($chatId, "⚠️ Draw not found.");
                        Cache::forget("telegram_stage_{$chatId}");
                        return response('OK', 200);
                    }

                    // parse claims
                    preg_match('/A(\d{1,2})/i', $text, $a);
                    preg_match('/B(\d{1,2})/i', $text, $b);
                    preg_match('/C(\d{1,2})/i', $text, $c);

                    $claimA = $a[1] ?? null;
                    $claimB = $b[1] ?? null;
                    $claimC = $c[1] ?? null;

                    if ($claimA === null || $claimB === null || $claimC === null) {
                        \App\Services\TelegramHelper::sendMessage($chatId, "⚠️ Invalid format.\nUse like: A5 B7 C9");
                        return response('OK', 200);
                    }

                    // write with lock and guard
                    \Illuminate\Support\Facades\DB::beginTransaction();
                    try {
                        $row = \App\Models\DrawDetail::where('id', $draw->id)->lockForUpdate()->first();
                        if (!$row) {
                            \Illuminate\Support\Facades\DB::rollBack();
                            \App\Services\TelegramHelper::sendMessage($chatId, "⚠️ Draw not found.");
                            Cache::forget("telegram_stage_{$chatId}");
                            return response('OK', 200);
                        }

                        if (!empty($row->claim_a) || !empty($row->claim_b) || !empty($row->claim_c)) {
                            \Illuminate\Support\Facades\DB::rollBack();
                            \App\Services\TelegramHelper::sendMessage(
                                $chatId,
                                "⚠️ This draw already has claims:\nA{$row->claim_a} B{$row->claim_b} C{$row->claim_c}\nIf you need to change them, send EDIT A# B# C#"
                            );
                            Cache::forget("telegram_stage_{$chatId}");
                            return response('OK', 200);
                        }

                        $row->claim_a = $claimA;
                        $row->claim_b = $claimB;
                        $row->claim_c = $claimC;
                        $row->updated_at = now();
                        $row->save();

                        \Illuminate\Support\Facades\DB::commit();

                        \App\Services\TelegramHelper::sendMessage(
                            $chatId,
                            "✅ Claim updated successfully!\n🎮 Game ID: {$row->game_id}\n🕒 Draw ID: {$row->draw_id}\n➡️ A{$claimA} B{$claimB} C{$claimC}"
                        );

                        Cache::forget("telegram_stage_{$chatId}");
                        return response('OK', 200);
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\DB::rollBack();
                        \Log::error("Failed writing claims for draw_id {$draw->id}: " . $e->getMessage());
                        \App\Services\TelegramHelper::sendMessage($chatId, "⚠️ Failed to save claims. Check server logs.");
                        Cache::forget("telegram_stage_{$chatId}");
                        return response('OK', 200);
                    }
                }
            }

            return response('OK', 200);
        }

        // CALLBACK (button)
        if (isset($update['callback_query'])) {
            $chatId = (int) $get($update, ['callback_query', 'message', 'chat', 'id'], 0);
            $data   = (string) $get($update, ['callback_query', 'data'], '');

            if (!in_array($chatId, $allowedIds, true)) {
                try {
                    \App\Services\TelegramHelper::sendMessage($chatId, "❌ Access Denied. You are not authorized.");
                } catch (\Throwable $e) {
                    \Log::warning("Failed to send Access Denied message (callback) to {$chatId}: " . $e->getMessage());
                }
                return response('OK', 200);
            }

            $now = \Carbon\Carbon::now();
            $today = $now->format('Y-m-d');

            // -------------------------------
            // Show only today's draws that are unfilled AND whose end_time has passed
            // -------------------------------
            // -------------------------------
          // -------------------------------
// game_ handler (robust: use draw_id -> draws.game_id mapping)
// -------------------------------
if (str_starts_with($data, 'game_')) {
    $gameId = (int) str_replace('game_', '', $data);
    $today  = date('Y-m-d');

    // find draw template ids for this game
    $drawIdsForGame = \App\Models\Draw::where('game_id', $gameId)->pluck('id')->toArray();
    if (empty($drawIdsForGame)) {
        \App\Services\TelegramHelper::sendMessage($chatId, "⚠️ No draw schedule found for this game.");
        return response('OK', 200);
    }

    $scheduledExpr = "STR_TO_DATE(CONCAT(`date`,' ',`end_time`), '%Y-%m-%d %H:%i')";

    // Query draw_details for today that map to those draw_ids, are unfilled, and whose scheduled end <= NOW()
    $endedUnfilled = \App\Models\DrawDetail::whereIn('draw_id', $drawIdsForGame)
        ->whereDate('date', $today)
        ->where(function ($q) {
            $q->whereNull('claim_a')->orWhere('claim_a', '')
              ->orWhereNull('claim_b')->orWhere('claim_b', '')
              ->orWhereNull('claim_c')->orWhere('claim_c', '');
        })
        ->whereRaw("$scheduledExpr <= NOW()")
        ->orderByRaw("$scheduledExpr ASC")
        ->get();

    $limitButtons = 20;

    if ($endedUnfilled->isNotEmpty()) {
        $buttons = [];
        foreach ($endedUnfilled->slice(0, $limitButtons) as $d) {
            $status = (!empty($d->claim_a) || !empty($d->claim_b) || !empty($d->claim_c)) ? ' (FILLED)' : ' (OPEN)';
            try {
    $displayTime = \Carbon\Carbon::createFromFormat('H:i', $d->end_time)
        ->addMinute()
        ->setTimezone('Asia/Kolkata')
        ->format('h:i A');
} catch (\Throwable $e) {
    $displayTime = $d->end_time ?? '—';
}
$label = $displayTime . $status;
            $buttons[] = [['text' => $label, 'callback_data' => "drawDetail_{$d->id}"]];
        }
        \App\Services\TelegramHelper::sendKeyboard($chatId, "🕐 Draws ended & OPEN (you can update):", $buttons);
        return response('OK', 200);
    }

    // fallback: next upcoming draw_detail or template
    $nextDrawDetail = \App\Models\DrawDetail::whereIn('draw_id', $drawIdsForGame)
        ->whereDate('date', $today)
        ->whereRaw("$scheduledExpr > NOW()")
        ->orderByRaw("$scheduledExpr ASC")
        ->first();

    if ($nextDrawDetail) {
        $time = date('H:i', strtotime($nextDrawDetail->date . ' ' . $nextDrawDetail->end_time));
        \App\Services\TelegramHelper::sendMessage($chatId, "ℹ️ No ended unfilled draws yet. Next draw ends at {$time}.");
        return response('OK', 200);
    }

    // fallback to master schedule (templates)
    $nextTemplate = \App\Models\Draw::where('game_id', $gameId)
        ->orderBy('id', 'asc')
        ->get()
        ->filter(function ($md) use ($today) {
            try { $ts = strtotime($today . ' ' . $md->end_time); return $ts !== false && $ts > time(); }
            catch (\Throwable $e) { return false; }
        })->first();

    if ($nextTemplate) {
        $time = date('H:i', strtotime($today . ' ' . $nextTemplate->end_time));
        \App\Services\TelegramHelper::sendMessage($chatId, "ℹ️ No ended unfilled draws yet. Next scheduled draw ends at {$time}.");
        return response('OK', 200);
    }

    \App\Services\TelegramHelper::sendMessage($chatId, "⚠️ No draws found for today.");
    return response('OK', 200);
}



            // 2) drawDetail_{id}
            if (str_starts_with($data, 'drawDetail_')) {
                $drawDetailId = (int) str_replace('drawDetail_', '', $data);
                $draw = \App\Models\DrawDetail::find($drawDetailId);

                if (!$draw) {
                    \App\Services\TelegramHelper::sendMessage($chatId, "⚠️ Draw detail not found.");
                    return response('OK', 200);
                }

                // If already filled, inform and allow update if desired
                if (!empty($draw->claim_a) || !empty($draw->claim_b) || !empty($draw->claim_c)) {
                    \App\Services\TelegramHelper::sendMessage(
                        $chatId,
                        "ℹ️ This draw already has claims:\nA{$draw->claim_a} B{$draw->claim_b} C{$draw->claim_c}\nReply with A# B# C# to update."
                    );
                    Cache::put("telegram_stage_{$chatId}", ['draw_id' => $draw->id, 'step' => 'await_claims'], 600);
                    return response('OK', 200);
                }

                // Ensure draw end_time has passed
                try {
                    $et = \Carbon\Carbon::parse($draw->date . ' ' . $draw->end_time);
                } catch (\Throwable $e) {
                    $et = null;
                }

                if ($et && $et->greaterThan(\Carbon\Carbon::now())) {
                    \App\Services\TelegramHelper::sendMessage($chatId, "⚠️ This draw hasn't ended yet (ends at {$draw->end_time}). You can only update after it ends.");
                    return response('OK', 200);
                }

                Cache::put("telegram_stage_{$chatId}", ['draw_id' => $draw->id, 'step' => 'await_claims'], 600);
                \App\Services\TelegramHelper::sendMessage($chatId, "✍️ Enter claim numbers (A B C) for draw ending at {$draw->end_time}\nExample: A5 B7 C9");
                return response('OK', 200);
            }

            // 3) drawTemplate_{id} -> only allow creating today's draw for templates whose end_time <= now
            if (str_starts_with($data, 'drawTemplate_')) {
                $masterDrawId = (int) str_replace('drawTemplate_', '', $data);
                $master = \App\Models\Draw::find($masterDrawId);

                if (!$master) {
                    \App\Services\TelegramHelper::sendMessage($chatId, "⚠️ Draw template not found.");
                    return response('OK', 200);
                }

                // compute scheduled end datetime for today
                try {
                    $scheduledEnd = \Carbon\Carbon::parse($today . ' ' . $master->end_time);
                } catch (\Throwable $e) {
                    \App\Services\TelegramHelper::sendMessage($chatId, "⚠️ Invalid template time.");
                    return response('OK', 200);
                }

                if ($scheduledEnd->greaterThan($now)) {
                    // template is for a future/running draw — do not create
                    \App\Services\TelegramHelper::sendMessage($chatId, "⚠️ This draw slot ({$master->end_time}) hasn't ended yet. You can only create today's draw after the slot ends at {$master->end_time}.");
                    return response('OK', 200);
                }

                // find or create today's draw_detail for this template (safe)
                $todayDate = $today;
                $existing = \App\Models\DrawDetail::where('draw_id', $master->id)->whereDate('date', $todayDate)->first();
                if ($existing) {
                    Cache::put("telegram_stage_{$chatId}", ['draw_id' => $existing->id, 'step' => 'await_claims'], 600);
                    \App\Services\TelegramHelper::sendMessage($chatId, "✍️ Today's draw already exists for {$existing->end_time}. Enter claim numbers (A B C). Example: A5 B7 C9");
                    return response('OK', 200);
                }

                // create safely
                try {
                    $new = \App\Models\DrawDetail::create([
                        'game_id'    => (int)($master->game_id ?? 0),
                        'draw_id'    => $master->id,
                        'start_time' => $master->start_time ?? null,
                        'end_time'   => $master->end_time ?? null,
                        'date'       => $todayDate,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } catch (\Illuminate\Database\QueryException $qe) {
                    \Log::warning("Race creating draw_detail from template {$master->id}: " . $qe->getMessage());
                    $new = \App\Models\DrawDetail::where('draw_id', $master->id)->whereDate('date', $todayDate)->first();
                    if (!$new) {
                        \Log::error("Failed to recover draw_detail for template {$master->id} after duplicate exception.");
                        \App\Services\TelegramHelper::sendMessage($chatId, "⚠️ Failed to create or find today's draw. Check server logs.");
                        return response('OK', 200);
                    }
                } catch (\Throwable $e) {
                    \Log::error("Failed to create draw_detail from template {$master->id}: " . $e->getMessage());
                    \App\Services\TelegramHelper::sendMessage($chatId, "⚠️ Failed to create today's draw from template. Check server logs.");
                    return response('OK', 200);
                }

                Cache::put("telegram_stage_{$chatId}", ['draw_id' => $new->id, 'step' => 'await_claims'], 600);
                \App\Services\TelegramHelper::sendMessage($chatId, "✍️ Created today's draw for {$new->end_time}. Now enter claim numbers (A B C). Example: A5 B7 C9");
                return response('OK', 200);
            }

            // default
            return response('OK', 200);
        }

        return response('OK', 200);
    } catch (\Throwable $e) {
        \Log::error("Telegram webhook error: " . $e->getMessage(), ['exception' => $e]);
        return response('OK', 200);
    }
})
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);





// Authentication Routes
Route::middleware('auth:web')->group(function () {

    Route::controller(DashboardController::class)->prefix('dashboard')->group(function () {
        Route::get('/', 'index')->name('dashboard');
        Route::get('add-ticket', 'addTicket')->name('ticket.add');
        // Route::get('/option-list', 'optionList')->name('dashboard.option.list');
        Route::get('/draw-details-list', 'drawDetailsList')->name('dashboard.draw.details.list');
        Route::get('/total-qty-detail-list/{drawDetail}', 'totalQtyDetailList')->name('dashboard.draw.total.qty.list.details');
        Route::get('cross-abc-detail-list', 'crossAbcList')->name('dashboard.draw.cross.abc.details.list');
        Route::get('cross-ab-list', 'getCrossAbList')->name('dashboard.draw.cross.ab.list');
        Route::get('cross-ac-list', 'getCrossAcList')->name('dashboard.draw.cross.ac.list');
        Route::get('cross-bc-list', 'getCrossBcList')->name('dashboard.draw.cross.bc.list');
    });

    Route::get('/refresh-csrf', function () {
        return response()->json(['token' => csrf_token()]);
    })->middleware('auth')->name('refresh.csrf');

    // Logout User
    Route::post('logout', function () {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect('/');
    })->name('logout');
});

/*
|--------------------------------------------------------------------------
| Admin: Wallet routes
|--------------------------------------------------------------------------
|
| Routes for admin to manage wallets and transactions.
| Adjust middleware 'admin' to match your actual admin middleware name
| (e.g. 'is_admin', 'role:admin', 'can:access-admin') if required.
|
*/
Route::prefix('admin')->middleware(['web', 'auth'])->group(function () {
    // Admin wallet pages (views handled by controller)
    Route::get('wallet/transactions', [AdminWalletController::class, 'transactionsPage'])
        ->name('admin.wallet.transactions'); // renders admin.wallet.transactions view

    Route::get('wallet/transfer', [AdminWalletController::class, 'transferPage'])
        ->name('admin.wallet.transfer'); // renders admin.wallet.transfer view

    // Plain transfer form + post handler (keeps your prior plain form routes)
    Route::get('wallet/transfer/plain', [AdminWalletController::class, 'plainForm'])
        ->name('admin.wallet.transfer.plain');

    Route::post('wallet/transfer/plain', [AdminWalletController::class, 'plainTransfer'])
        ->name('admin.wallet.transfer.plain.post');

    // API-like endpoints used by admin UI (optional)
    Route::post('wallet/transfer', [AdminWalletController::class, 'transfer'])
        ->name('admin.wallet.transfer.post');
});

/*
|--------------------------------------------------------------------------
| User: Wallet routes
|--------------------------------------------------------------------------
|
| Routes for regular authenticated users to view their own wallet,
| list their transactions, and request withdrawals/transfers.
|
*/
// Route::prefix('wallet')->middleware(['web', 'auth'])->group(function () {
//     Route::get('/', [UserWalletController::class, 'index'])
//         ->name('user.wallet.index');

//     Route::get('transactions', [UserWalletController::class, 'transactions'])
//         ->name('user.wallet.transactions');

//     Route::get('transfer', [UserWalletController::class, 'showTransferForm'])
//         ->name('user.wallet.transfer');

//     Route::post('transfer', [UserWalletController::class, 'submitTransfer'])
//         ->name('user.wallet.transfer.post');
// });

Route::middleware(['auth'])->prefix('wallet')->name('user.wallet.')->group(function () {
    Route::get('transactions', [\App\Http\Controllers\User\WalletController::class, 'transactions'])->name('transactions');
    Route::get('transfer', [\App\Http\Controllers\User\WalletController::class, 'showTransferForm'])->name('transfer');
    Route::post('transfer', [\App\Http\Controllers\User\WalletController::class, 'submitTransfer'])->name('transfer.post');
});

// ---------- ADD these alias routes for clarity: shopkeeper.* ----------
Route::middleware(['auth'])->prefix('shopkeeper/wallet')->name('shopkeeper.wallet.')->group(function () {
    // note: URL is shopkeeper/wallet/transfer — different URL but same controller method
    Route::get('transactions', [UserWalletController::class, 'transactions'])->name('transactions');
    Route::get('transfer', [UserWalletController::class, 'showTransferForm'])->name('transfer');
    Route::post('transfer', [UserWalletController::class, 'submitTransfer'])->name('transfer.post');
});

/*
|---------------------------------------------------------------------------
| Fallback: If you do not have an 'admin' middleware registered, and you
| prefer to allow admin routes to work with just 'auth', you can replace
| the admin middleware group above with the following (uncomment if needed):
|
| Route::prefix('admin')->middleware(['web','auth'])->group(function () {
|     // ...same admin routes as above...
| });
|
| But it's recommended to protect admin pages with a dedicated middleware.
|---------------------------------------------------------------------------
*/
