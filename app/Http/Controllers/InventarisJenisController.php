<?php

namespace App\Http\Controllers;

use App\Models\InventarisJenis;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class InventarisJenisController extends Controller
{
    public function index()
    {
        if(request()->ajax()) {
            $data = InventarisJenis::select('*');
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function($row){
                    $btn = '<button type="button" class="btn btn-warning btn-sm edit" data-id="'.$row->id_jenis.'">Edit</button>';
                    $btn .= ' <button type="button" class="btn btn-danger btn-sm delete" data-id="'.$row->id_jenis.'">Hapus</button>';
                    return $btn;
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        return view('inventaris.jenis_inventaris');
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_jenis' => 'required|unique:server_74.inventaris_jenis,id_jenis',
            'nama_jenis' => 'required',
        ]);

        InventarisJenis::create($request->all());
        return response()->json(['success' => 'Data berhasil disimpan']);
    }

    public function edit($id)
    {
        $jenis = InventarisJenis::findOrFail($id);
        return response()->json($jenis);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'id_jenis' => 'required|unique:server_74.inventaris_jenis,id_jenis,'.$id.',id_jenis',
            'nama_jenis' => 'required',
        ]);

        $jenis = InventarisJenis::findOrFail($id);
        $jenis->update($request->all());
        return response()->json(['success' => 'Data berhasil diupdate']);
    }

    public function destroy($id)
    {
        InventarisJenis::findOrFail($id)->delete();
        return response()->json(['success' => 'Data berhasil dihapus']);
    }
}
