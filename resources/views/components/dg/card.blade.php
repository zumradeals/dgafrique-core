@props(['accent' => false])

<section {{ $attributes->class(['dg-card', 'dg-card--accent' => $accent]) }}>
    <div class="dg-card__body">
        {{ $slot }}
    </div>
</section>
