@if (!empty($editRoute))
    <a class="btn btn-info waves-effect waves-light edit" href="{{ route($editRoute, $encryptedKodeSurat) }}">
        <i class="far fa-edit"></i>
    </a>
@endif
<a class="btn btn-primary waves-effect waves-light" href="{{ route('surat_masuk.detail', $encryptedKodeSurat) }}">
    <i class="far fa-eye"></i>
</a>
