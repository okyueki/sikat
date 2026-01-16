<?php

namespace App\Http\Controllers;

use App\Models\InventarisProdusen;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class InventarisProdusenController extends Controller
{
    public function index()
    {
        if(request()->ajax()) {
            $data = InventarisProdusen::select('*');
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function($row){
                    $btn = '<button type="button" class="btn btn-warning btn-sm edit" data-id="'.$row->kode_produsen.'">Edit</button>';
                    $btn .= ' <button type="button" class="btn btn-danger btn-sm delete" data-id="'.$row->kode_produsen.'">Hapus</button>';
                    return $btn;
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        return view('inventaris.produsen_inventaris');
    }

    public function store(Request $request)
    {
        $request->validate([
            'kode_produsen' => 'required|unique:server_74.inventaris_produsen,kode_produsen',
            'nama_produsen' => 'required',
            'alamat_produsen' => 'nullable',
            'no_telp' => 'nullable',
            'email' => 'nullable|email',
            'website_produsen' => 'nullable|url',
        ]);

        InventarisProdusen::create($request->all());
        return response()->json(['success' => 'Data berhasil disimpan']);
    }

    public function edit($id)
    {
        $produsen = InventarisProdusen::findOrFail($id);
        return response()->json($produsen);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'kode_produsen' => 'required|unique:server_74.inventaris_produsen,kode_produsen,'.$id.',kode_produsen',
            'nama_produsen' => 'required',
            'alamat_produsen' => 'nullable',
            'no_telp' => 'nullable',
            'email' => 'nullable|email',
            'website_produsen' => 'nullable|url',
        ]);

        $produsen = InventarisProdusen::findOrFail($id);
        $produsen->update($request->all());
        return response()->json(['success' => 'Data berhasil diupdate']);
    }

    public function destroy($id)
    {
        InventarisProdusen::findOrFail($id)->delete();
        return response()->json(['success' => 'Data berhasil dihapus']);
    }
}
