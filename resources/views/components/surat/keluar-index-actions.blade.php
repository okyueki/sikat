@if (!empty($canEdit))
    <a class="btn btn-info waves-effect waves-light edit" href="{{ route('surat_keluar.kirimsurat', $encryptedKodeSurat) }}">
        <i class="far fa-edit"></i>
    </a>
    <form action="{{ route('surat_keluar.destroy', $suratId) }}" method="POST" style="display:inline;">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-danger waves-effect waves-light deletesurat">
            <i class="far fa-trash-alt"></i>
        </button>
    </form>
@endif
<a class="btn btn-primary waves-effect waves-light" href="{{ route('surat_keluar.detail', $encryptedKodeSurat) }}">
    <i class="far fa-eye"></i>
</a>
