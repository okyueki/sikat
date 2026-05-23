<?php

namespace App\Http\Controllers;

use App\Models\InventarisMerk;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class InventarisMerkController extends Controller
{
    public function index()
    {
        if(request()->ajax()) {
            $data = InventarisMerk::select('*');
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function($row){
                    $btn = '<button type="button" class="btn btn-warning btn-sm edit" data-id="'.$row->kode_merk.'">Edit</button>';
                    $btn .= ' <button type="button" class="btn btn-danger btn-sm delete" data-id="'.$row->kode_merk.'">Hapus</button>';
                    return $btn;
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        return view('inventaris.merk_inventaris');
    }

    public function store(Request $request)
    {
        $request->validate([
            'kode_merk' => 'required|unique:server_74.inventaris_merk,kode_merk',
            'nama_merk' => 'required',
        ]);

        InventarisMerk::create($request->all());
        return response()->json(['success' => 'Data berhasil disimpan']);
    }

    public function edit($id)
    {
        $merk = InventarisMerk::findOrFail($id);
        return response()->json($merk);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'kode_merk' => 'required|unique:server_74.inventaris_merk,kode_merk,'.$id.',kode_merk',
            'nama_merk' => 'required',
        ]);

        $merk = InventarisMerk::findOrFail($id);
        $merk->update($request->all());
        return response()->json(['success' => 'Data berhasil diupdate']);
    }

    public function destroy($id)
    {
        InventarisMerk::findOrFail($id)->delete();
        return response()->json(['success' => 'Data berhasil dihapus']);
    }
}
