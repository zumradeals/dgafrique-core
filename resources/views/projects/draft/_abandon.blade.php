{{-- Abandon explicite, toujours possible, jamais mis en avant. Formulaire distinct : ne partage pas les champs de l'étape. --}}
<form method="POST" action="{{ route('projects.draft.abandon', $draft) }}" style="margin-top:18px" onsubmit="return confirm('Abandonner cette idée ? Rien n’a été créé, mais le brouillon sera définitivement fermé.');">
    @csrf
    <button type="submit" class="dg-meta" style="background:none;border:0;padding:0;color:var(--dg-faint);text-decoration:underline;cursor:pointer">Abandonner cette idée</button>
</form>
