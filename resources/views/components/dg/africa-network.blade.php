{{--
    Illustration décorative du hero landing : silhouette africaine texturée en points, avec un
    graphe de réseau superposé (nœuds reliés). Purement décoratif — aria-hidden, aucune donnée.
--}}
@props(['class' => ''])
<svg viewBox="0 0 420 460" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" role="presentation" {{ $attributes->merge(['class' => $class]) }}>
    <defs>
        <clipPath id="dg-africa-mask">
            <path d="M150,10 C220,5 280,20 320,60 C345,90 330,130 360,150 C390,165 400,190 385,210
                     C365,240 350,270 355,300 C360,330 340,360 320,390 C300,420 260,445 222,455
                     C210,440 206,410 196,390 C182,360 165,330 150,310 C120,290 90,300 70,280
                     C50,260 55,230 40,210 C25,190 10,170 20,145 C30,120 55,110 60,85
                     C65,60 90,45 110,35 C122,25 136,15 150,10 Z" />
        </clipPath>
        <pattern id="dg-africa-dots" width="13" height="13" patternUnits="userSpaceOnUse">
            <circle cx="2" cy="2" r="1.5" fill="var(--dg-copper)" opacity=".4" />
        </pattern>
    </defs>

    <rect x="0" y="0" width="420" height="460" fill="url(#dg-africa-dots)" clip-path="url(#dg-africa-mask)" />
    <path d="M150,10 C220,5 280,20 320,60 C345,90 330,130 360,150 C390,165 400,190 385,210
             C365,240 350,270 355,300 C360,330 340,360 320,390 C300,420 260,445 222,455
             C210,440 206,410 196,390 C182,360 165,330 150,310 C120,290 90,300 70,280
             C50,260 55,230 40,210 C25,190 10,170 20,145 C30,120 55,110 60,85
             C65,60 90,45 110,35 C122,25 136,15 150,10 Z"
          fill="none" stroke="var(--dg-line-dashed)" stroke-width="1.5" stroke-dasharray="1 5" stroke-linecap="round" />

    {{-- Graphe de réseau : nœuds + liaisons, superposés à la silhouette --}}
    <g stroke="var(--dg-forest-action)" stroke-width="1.2" opacity=".55">
        <path d="M96,150 L180,100" />
        <path d="M180,100 L270,90" />
        <path d="M180,100 L150,210" />
        <path d="M150,210 L96,150" />
        <path d="M150,210 L230,250" />
        <path d="M230,250 L270,90" />
        <path d="M230,250 L200,340" />
        <path d="M200,340 L150,210" />
        <path d="M270,90 L320,130" />
    </g>
    <g>
        <circle cx="96" cy="150" r="4.5" fill="var(--dg-forest-action)" />
        <circle cx="180" cy="100" r="5.5" fill="var(--dg-copper)" />
        <circle cx="270" cy="90" r="4.5" fill="var(--dg-forest-action)" />
        <circle cx="150" cy="210" r="6.5" fill="var(--dg-saffron)" />
        <circle cx="230" cy="250" r="4.5" fill="var(--dg-forest-action)" />
        <circle cx="320" cy="130" r="4" fill="var(--dg-forest-action)" />
        <circle cx="200" cy="340" r="4.5" fill="var(--dg-copper)" />
    </g>
</svg>
