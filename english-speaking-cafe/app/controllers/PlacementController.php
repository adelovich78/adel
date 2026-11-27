<?php
/**
 * Test de niveau d'anglais (placement A1 → C2).
 * 24 questions, 4 par niveau. Le corrigé reste côté serveur.
 */
declare(strict_types=1);

namespace App\Controllers;

use App\Core\{DB, View, Auth};

final class PlacementController
{
    /** [niveau, question, [options], index de la bonne réponse] */
    public const QUESTIONS = [
        ['A1', 'Hello, my name ___ Anna.', ['is', 'are', 'am', 'be'], 0],
        ['A1', 'They ___ from Spain.', ['is', 'am', 'are', 'be'], 2],
        ['A1', 'I ___ coffee every morning.', ['drinks', 'drink', 'drinking', 'am drink'], 1],
        ['A1', 'There ___ two cats in the garden.', ['is', 'are', 'be', 'has'], 1],
        ['A2', 'Yesterday we ___ to the cinema.', ['go', 'goes', 'went', 'gone'], 2],
        ['A2', 'She is ___ than her brother.', ['tall', 'taller', 'tallest', 'more tall'], 1],
        ['A2', 'I’m going ___ visit my grandmother tomorrow.', ['for', 'to', 'at', 'of'], 1],
        ['A2', 'How ___ sugar do you want in your tea?', ['many', 'much', 'lot', 'few'], 1],
        ['B1', 'I’ve lived in Istanbul ___ 2019.', ['for', 'since', 'from', 'during'], 1],
        ['B1', 'If it rains tomorrow, we ___ stay at home.', ['would', 'will', 'are', 'had'], 1],
        ['B1', 'This book ___ by millions of people.', ['has read', 'has been read', 'is reading', 'reads'], 1],
        ['B1', 'She asked me where I ___.', ['live', 'lived', 'am living', 'will live'], 1],
        ['B2', 'If I ___ known about the meeting, I would have come.', ['have', 'had', 'would have', 'did'], 1],
        ['B2', 'By the time we arrived, the film ___.', ['already started', 'has already started', 'had already started', 'was already starting'], 2],
        ['B2', 'I’m not used to ___ up so early.', ['get', 'getting', 'got', 'have got'], 1],
        ['B2', 'Choose the closest meaning: “to put off a meeting”.', ['to cancel it', 'to postpone it', 'to organise it', 'to attend it'], 1],
        ['C1', '___ had I left the house than it started to pour.', ['No sooner', 'Hardly', 'Barely', 'Scarcely'], 0],
        ['C1', 'The report, ___ findings were surprising, was published today.', ['which', 'whose', 'that', 'who’s'], 1],
        ['C1', 'She’s very ___ — she always notices the smallest details.', ['observant', 'observable', 'observing', 'observation'], 0],
        ['C1', 'It’s high time we ___ a decision.', ['make', 'made', 'will make', 'have made'], 1],
        ['C2', 'His argument doesn’t hold ___ under close scrutiny.', ['water', 'ground', 'fire', 'air'], 0],
        ['C2', 'Had it not been for her help, the project ___.', ['would fail', 'would have failed', 'failed', 'will have failed'], 1],
        ['C2', 'The speech was so ___ that nobody could tell what he actually meant.', ['lucid', 'equivocal', 'candid', 'terse'], 1],
        ['C2', 'I’d rather you ___ mention this to anyone.', ['don’t', 'didn’t', 'won’t', 'not'], 1],
    ];

    /** Score total → niveau CEFR */
    public static function levelFor(int $score): string
    {
        return match (true) {
            $score >= 22 => 'C2', $score >= 18 => 'C1', $score >= 14 => 'B2',
            $score >= 10 => 'B1', $score >= 6 => 'A2', default => 'A1',
        };
    }

    public function show(): void
    {
        // Les questions sont envoyées sans le corrigé
        $qs = array_map(fn($q) => ['q' => $q[1], 'o' => $q[2]], self::QUESTIONS);
        View::render('level-test/index', ['title' => 'English level test', 'questions' => $qs, 'scripts' => ['js/level-test.js']]);
    }

    public function submit(): void
    {
        $score = 0;
        $bands = array_fill_keys(CEFR, 0);
        $answered = 0;
        foreach (self::QUESTIONS as $i => [$lvl, , , $ok]) {
            $a = $_POST['q'][$i] ?? null;
            if ($a === null || $a === '') continue;
            $answered++;
            if ((int)$a === $ok) { $score++; $bands[$lvl]++; }
        }
        if ($answered < 6) {
            flash('error', __('Please answer at least a few questions to get your level.'));
            redirect('/level-test');
        }
        $level = self::levelFor($score);
        // Résultat gardé en session (enregistrement sans possibilité de falsifier le niveau)
        $_SESSION['placement'] = ['level' => $level, 'score' => $score, 'bands' => $bands, 'at' => time()];
        redirect('/level-test/result');
    }

    public function result(): void
    {
        $r = $_SESSION['placement'] ?? null;
        if (!$r) redirect('/level-test');
        View::render('level-test/result', ['title' => 'Your English level', 'r' => $r, 'total' => count(self::QUESTIONS)]);
    }

    /** Enregistre le niveau obtenu sur le compte de l'élève */
    public function save(): void
    {
        $u = Auth::require();
        $r = $_SESSION['placement'] ?? null;
        if (!$r) redirect('/level-test');
        DB::update('users', ['cefr_level' => $r['level']], 'id = ?', [$u['id']]);
        $i = cefr_index($r['level']);
        DB::insert('progress', [
            'user_id' => $u['id'], 'cefr_level' => $r['level'], 'source' => 'self',
            'speaking' => 20 + $i * 13, 'listening' => 24 + $i * 13, 'pronunciation' => 18 + $i * 13, 'vocabulary' => 22 + $i * 13, 'confidence' => 15 + $i * 12,
            'notes' => 'Placement test: ' . $r['score'] . '/' . count(self::QUESTIONS),
        ]);
        flash('success', __('Your level :level has been saved. Your teacher will confirm it at your first table.', ['level' => $r['level']]));
        redirect('/dashboard');
    }
}
