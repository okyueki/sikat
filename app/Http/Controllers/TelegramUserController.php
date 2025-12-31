<?php

namespace App\Http\Controllers;

use App\Models\TelegramUser;
use App\Models\Pegawai;
use Illuminate\Http\Request;

class TelegramUserController extends Controller
{
    public function index()
    {
        $users = TelegramUser::paginate(10);
        return view('telegram_users.index', compact('users'));
    }

    public function create()
    {
        $usedNik = TelegramUser::pluck('nik')->toArray();

        $pegawai = Pegawai::on('server_74')
            ->select('nik', 'nama')
            ->where('stts_aktif', 'AKTIF')
            ->whereNotIn('nik', $usedNik)
            ->orderBy('nama')
            ->get();

        return view('telegram_users.create', compact('pegawai'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nik' => 'required|unique:telegram_users,nik',
            'chat_id' => 'required|numeric|unique:telegram_users,chat_id',
        ]);

        $pegawai = Pegawai::on('server_74')
            ->select('nama')
            ->where('nik', $request->nik)
            ->first();

        TelegramUser::create([
            'nik' => $request->nik,
            'nama_pegawai' => $pegawai ? $pegawai->nama : null,
            'chat_id' => $request->chat_id,
        ]);

        return redirect()->route('telegram-users.index')->with('success', 'User berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $user = TelegramUser::findOrFail($id);

        $pegawai = Pegawai::on('server_74')
            ->select('nik', 'nama')
            ->where('stts_aktif', 'AKTIF')
            ->get();

        return view('telegram_users.edit', compact('user', 'pegawai'));
    }

    public function update(Request $request, $id)
    {
        $user = TelegramUser::findOrFail($id);

        $request->validate([
            'nik' => 'required|unique:telegram_users,nik,' . $user->id,
            'chat_id' => 'required|numeric|unique:telegram_users,chat_id,' . $user->id,
        ]);

        $pegawai = Pegawai::on('server_74')
            ->select('nama')
            ->where('nik', $request->nik)
            ->first();

        $user->update([
            'nik' => $request->nik,
            'nama_pegawai' => $pegawai ? $pegawai->nama : null,
            'chat_id' => $request->chat_id,
        ]);

        return redirect()->route('telegram-users.index')->with('success', 'User berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $user = TelegramUser::findOrFail($id);
        $user->delete();

        return redirect()->route('telegram-users.index')->with('success', 'User berhasil dihapus.');
    }
}
