<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use App\Helpers\TelegramHelper;
use App\Models\Game;
use App\Models\DrawDetail;



Route::post('/telegram/webhook', function (Request $request) {
    $update = $request->all();
    Log::info('Telegram Update:', $update);

    $allowedIds = explode(',', env('TELEGRAM_ALLOWED_IDS'));

    // 📨 Message received
    if (isset($update['message'])) {
        $chatId = $update['message']['chat']['id'];
        $text   = trim($update['message']['text']);

        // 🛑 Security check
        if (!in_array($chatId, $allowedIds)) {
            TelegramHelper::sendMessage($chatId, "❌ Access Denied. You are not authorized.");
            return;
        }

        // Start command
        if ($text == '/start') {
            $games = Game::orderBy('id')->get();
            $buttons = [];
            foreach ($games as $g) {
                $buttons[] = [['text' => $g->name, 'callback_data' => 'game_' . $g->id]];
            }
            TelegramHelper::sendKeyboard($chatId, "🎮 Select a Game:", $buttons);
        }

        // Check if awaiting claim numbers
        if (Cache::has("telegram_stage_$chatId")) {
            $stage = Cache::get("telegram_stage_$chatId");

            if ($stage['step'] == 'await_claims') {
                $draw = DrawDetail::find($stage['draw_id']);
                if (!$draw) {
                    TelegramHelper::sendMessage($chatId, "⚠️ Draw not found.");
                    Cache::forget("telegram_stage_$chatId");
                    return;
                }

                // Parse input like "A5 B7 C9"
                preg_match('/A(\d)/i', $text, $a);
                preg_match('/B(\d)/i', $text, $b);
                preg_match('/C(\d)/i', $text, $c);

                $claimA = $a[1] ?? null;
                $claimB = $b[1] ?? null;
                $claimC = $c[1] ?? null;

                if ($claimA === null || $claimB === null || $claimC === null) {
                    TelegramHelper::sendMessage($chatId, "⚠️ Invalid format.\nUse like: A5 B7 C9");
                    return;
                }

                // ✅ Update DB
                $draw->update([
                    'claim_a' => $claimA,
                    'claim_b' => $claimB,
                    'claim_c' => $claimC,
                    'updated_at' => now(),
                ]);

                TelegramHelper::sendMessage($chatId, "✅ Claim updated successfully!\n🎮 Game ID: {$draw->game_id}\n🕒 Draw ID: {$draw->draw_id}\n➡️ A{$claimA} B{$claimB} C{$claimC}");

                Cache::forget("telegram_stage_$chatId");
            }
        }
    }

    // 🔘 Button clicked
    if (isset($update['callback_query'])) {
        $chatId = $update['callback_query']['message']['chat']['id'];
        $data   = $update['callback_query']['data'];

        // 🛑 Security check
        if (!in_array($chatId, explode(',', env('TELEGRAM_ALLOWED_IDS')))) {
            TelegramHelper::sendMessage($chatId, "❌ Access Denied. You are not authorized.");
            return;
        }

        // Game selected
        if (str_starts_with($data, 'game_')) {
            $gameId = str_replace('game_', '', $data);
            $draws = DrawDetail::where('game_id', $gameId)
                ->whereDate('date', today())
                ->orderBy('end_time', 'asc')
                ->get();

            if ($draws->isEmpty()) {
                TelegramHelper::sendMessage($chatId, "⚠️ No draws found for today.");
                return;
            }

            $buttons = [];
            foreach ($draws as $d) {
                $buttons[] = [['text' => $d->end_time, 'callback_data' => "draw_{$d->id}"]];
            }

            TelegramHelper::sendKeyboard($chatId, "🕐 Select Draw Time:", $buttons);
        }

        // Draw selected
        if (str_starts_with($data, 'draw_')) {
            $drawId = str_replace('draw_', '', $data);
            Cache::put("telegram_stage_$chatId", [
                'draw_id' => $drawId,
                'step' => 'await_claims'
            ], 600); // valid for 10 min

            TelegramHelper::sendMessage($chatId, "✍️ Enter claim numbers (A B C)\nExample: A5 B7 C9");
        }
    }
});