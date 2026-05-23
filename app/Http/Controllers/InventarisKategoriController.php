<?php

namespace App\Http\Controllers;

use App\Models\InventarisKategori;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class InventarisKategoriController extends Controller
{
    public function index()
    {
        if(request()->ajax()) {
            $data = InventarisKategori::select('*');
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function($row){
                    $btn = '<button type="button" class="btn btn-warning btn-sm edit" data-id="'.$row->id_kategori.'">Edit</button>';
                    $btn .= ' <button type="button" class="btn btn-danger btn-sm delete" data-id="'.$row->id_kategori.'">Hapus</button>';
                    return $btn;
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        return view('inventaris.kategori_inventaris');
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_kategori' => 'required|unique:server_74.inventaris_kategori,id_kategori',
            'nama_kategori' => 'required',
        ]);

        InventarisKategori::create($request->all());
        return response()->json(['success' => 'Data berhasil disimpan']);
    }

    public function edit($id)
    {
        $kategori = InventarisKategori::findOrFail($id);
        return response()->json($kategori);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'id_kategori' => 'required|unique:server_74.inventaris_kategori,id_kategori,'.$id.',id_kategori',
            'nama_kategori' => 'required',
        ]);

        $kategori = InventarisKategori::findOrFail($id);
        $kategori->update($request->all());
        return response()->json(['success' => 'Data berhasil diupdate']);
    }

    public function destroy($id)
    {
        InventarisKategori::findOrFail($id)->delete();
        return response()->json(['success' => 'Data berhasil dihapus']);
    }
}
