@extends('mobileui.layouts.mobile')

@section('title', ($title ?? 'Mobile') . ' - SIKAT Mobile')
@section('body_class', 'bg-white')
@section('has_header', true)

@section('content')
    @include('mobileui.partials.header', [
        'title' => $title ?? 'Mobile',
        'showBack' => true,
        'bgClass' => 'bg-primary',
        'textClass' => 'text-light',
    ])

    <div id="appCapsule">
        <div class="section full">
            <div class="wide-block pt-2 pb-2">
                Halaman ini masih placeholder. Nanti kita isi dengan fitur yang sesuai modul.
            </div>
        </div>
    </div>

    @include('mobileui.partials.bottom-menu', ['active' => $active ?? ''])
@endsection

