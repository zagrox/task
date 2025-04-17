<div {{ $attributes->merge(['class' => 'version-badge']) }}>
    <span class="badge bg-{{ $type ?? 'info' }} text-{{ $textColor ?? 'white' }}">
        <i class="fas fa-code-branch me-1"></i> v{{ $version ?? config('app.version') }}
    </span>
    @if($showLabel ?? false)
        <span class="ms-1 small text-muted">{{ $label ?? 'Version' }}</span>
    @endif
</div> 