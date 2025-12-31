<div class="card">
    <div class="card-header">
        <h2 class="card-title">{{ $title ?? 'Default Title' }}</h2>
        {{ $header ?? '' }}
    </div>
    <div class="card-body">
        {{ $slot }}
    </div>
</div>