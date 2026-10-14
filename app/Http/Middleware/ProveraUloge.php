<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Propušta zahtev samo ako prijavljeni korisnik ima jednu od dozvoljenih uloga.
// Upotreba u rutama:  ->middleware('uloga:vlasnik')  ili  ->middleware('uloga:zaposleni,gost')
class ProveraUloge
{
    public function handle(Request $request, Closure $next, string ...$uloge): Response
    {
        $korisnik = $request->user();

        if (! $korisnik || ! in_array($korisnik->Uloga, $uloge, true)) {
            return response()->json(['message' => 'Nemate pravo pristupa ovoj funkciji.'], 403);
        }

        return $next($request);
    }
}
