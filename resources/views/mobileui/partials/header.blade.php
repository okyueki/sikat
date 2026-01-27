@php
    /** @var string|null $title */
    /** @var bool|null $showBack */
    $title = $title ?? '';
    $showBack = $showBack ?? true;
    $bgClass = $bgClass ?? 'bg-primary';
    $textClass = $textClass ?? 'text-light';
@endphp

<div class="appHeader {{ $bgClass }} {{ $textClass }}">
    <div class="left">
        @if ($showBack)
            <a href="javascript:;" class="headerButton goBack">
                <ion-icon name="chevron-back-outline"></ion-icon>
            </a>
        @endif
    </div>
    <div class="pageTitle">{{ $title }}</div>
    <div class="right">
        @isset($right)
            {!! $right !!}
        @endisset
    </div>
</div>
