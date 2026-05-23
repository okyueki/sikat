{{--
  Breadcrumb multi-level (setelah "Beranda" di partial page-header).

  Contoh di view anak:
  @section('breadcrumbs')
      <x-sikat.breadcrumbs :items="[
          ['label' => 'Inventaris', 'url' => route('inventaris.index')],
          ['label' => 'Edit'],
      ]" />
  @endsection

  Item terakhir selalu ditampilkan sebagai halaman aktif (tanpa link).
--}}
@props(['items' => []])

@foreach ($items as $item)
    @if ($loop->last)
        <li class="breadcrumb-item active text-truncate" aria-current="page">{{ $item['label'] }}</li>
    @else
        <li class="breadcrumb-item text-truncate">
            <a href="{{ $item['url'] ?? '#' }}">{{ $item['label'] }}</a>
        </li>
    @endif
@endforeach
