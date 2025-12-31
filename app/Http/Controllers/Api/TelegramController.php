<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pegawai;
use App\Models\TelegramUser;

class TelegramController extends Controller
{
    public function handleWebhook(Request $request)
    {
        $update = $request->all();
        \Log::info('Telegram Update:', $update);

        if (!isset($update['message'])) {
            return response()->json(['status' => 'no_message']);
        }

        $message   = $update['message'];
        $chatId    = $message['chat']['id'];
        $text      = trim($message['text'] ?? '');
        $username  = $message['from']['username'] ?? null;

        // 1. Kalau user ketik /start
        if ($text === '/start') {
            $this->sendMessage($chatId, "Halo 👋\nSilakan ketik NIK Anda untuk registrasi.");
            return response()->json(['status' => 'ok']);
        }

        // 2. Kalau input cukup panjang → dianggap NIK
        if (strlen($text) >= 8) {
            $nik = $text;

            $pegawai = Pegawai::where('nik', $nik)->first();

            if ($pegawai) {
                // Cek apakah chat_id ini sudah terhubung dengan NIK lain
                $existingByChatId = TelegramUser::where('chat_id', $chatId)->first();
                if ($existingByChatId && $existingByChatId->nik !== $nik) {
                    $this->sendMessage($chatId, "⚠️ Akun Telegram ini sudah terhubung dengan NIK {$existingByChatId->nik}. Hubungi admin jika ingin reset.");
                    return response()->json(['status' => 'chat_id_already_used']);
                }

                // Cek apakah NIK ini sudah terhubung dengan chat_id lain
                $existingByNik = TelegramUser::where('nik', $nik)->first();
                if ($existingByNik && $existingByNik->chat_id !== $chatId) {
                    $this->sendMessage($chatId, "⚠️ NIK {$nik} sudah terhubung dengan akun Telegram lain. Hubungi admin jika ingin reset.");
                    return response()->json(['status' => 'nik_already_used']);
                }

                // Kalau aman → simpan/update
                TelegramUser::updateOrCreate(
                    ['nik' => $pegawai->nik],
                    [
                        'nama_pegawai' => $pegawai->nama,
                        'username'     => $username,
                        'chat_id'      => $chatId,
                    ]
                );

                $this->sendMessage($chatId, "✅ Registrasi berhasil!\nHalo {$pegawai->nama}, notifikasi akan dikirim lewat bot ini.");
            } else {
                $this->sendMessage($chatId, "❌ NIK tidak ditemukan. Coba lagi atau hubungi admin.");
            }

            return response()->json(['status' => 'ok']);
        }

        // 3. Default (input tidak dikenali)
        $this->sendMessage($chatId, "⚠️ Perintah tidak dikenali. Ketik /start untuk memulai.");
        return response()->json(['status' => 'ok']);
    }

    private function sendMessage($chatId, $text)
    {
        $token = env('TELEGRAM_BOT_TOKEN');
        $url = "https://api.telegram.org/bot{$token}/sendMessage";

        $client = new \GuzzleHttp\Client();
        $client->post($url, [
            'form_params' => [
                'chat_id' => $chatId,
                'text'    => $text,
            ]
        ]);
    }
}
