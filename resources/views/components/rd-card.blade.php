<!-- resources/views/components/rd-card.blade.php -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">{{ $title ?? 'Default Title' }}</h2>
        {{ $header ?? '' }} <!-- Tempat untuk slot header -->
    </div>
    <div class="card-body">
        {{ $slot }} <!-- Tempat untuk slot utama (isi card) -->
    </div>
</div>