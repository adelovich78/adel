<?php
/**
 * Données initiales : réglages, programmes, profs, formules, événements, bibliothèque, planning.
 * $demo = true ajoute des comptes de démonstration (mot de passe : demo1234).
 */
declare(strict_types=1);

use App\Core\DB;

function seed_database(bool $demo, string $currency = 'EUR'): void
{
    $now = time();
    $dt = fn(int $ts) => date('Y-m-d H:i:s', $ts);

    // --- Réglages
    foreach (\App\Controllers\AdminController::SETTINGS as $k => [$label, $def]) {
        $v = $k === 'currency' ? $currency : $def;
        if ($k === 'hero_subtitle') $v = 'A boutique café where you practise real English with native teachers — in tables of five, over great coffee.';
        if ($k === 'address') $v = '12 Rue des Lilas, Paris';
        if ($k === 'opening_hours') $v = 'Mon–Fri 9:00–21:30 · Sat 10:00–18:00';
        if ($k === 'phone') $v = '+33 1 23 45 67 89';
        if ($k === 'contact_email') $v = 'hello@xyacademy.cafe';
        DB::q('INSERT IGNORE INTO settings (`key`,`value`,label) VALUES (?,?,?)', [$k, $v, $label]);
    }

    // --- Programmes
    $programs = [
        ['conversation-groups', 'Small Group Conversation', 'group', 'Five learners. One native teacher. Real conversation.', 'Our signature format. Sit at a table of five with a native teacher and talk — about life, work, travel, ideas. Every session has a theme, a light structure and lots of speaking time: on average you speak 12× more than in a classic class.', 'Teens 16+, students, adults', 'A2', 'C2', 75, 5, 29, 'conversation,travel,career', 'green'],
        ['one-to-one', '1-to-1 Coaching', 'one_to_one', 'Your goals. Your pace. Your coach.', 'A private session with a native coach, tailored to you: job interview, presentation, exam, accent or simply confidence. Book single sessions or packs, at the café or online.', 'Everyone', 'A1', 'C2', 60, 1, 65, 'conversation,career,travel,exams,pronunciation,school', 'espresso'],
        ['conversation-tables', 'Conversation Tables', 'table', 'Drop in, grab a coffee, join the table.', 'Relaxed drop-in tables hosted by a native speaker. No preparation, no pressure — just a topic card, a great drink and new people from all over the world.', 'Adults & students', 'A2', 'C1', 60, 5, 15, 'conversation,travel', 'amber'],
        ['business-english', 'Business English', 'business', 'Meetings, pitches and negotiations — in English.', 'Practical English for professionals: meetings, presentations, negotiations, emails and networking. Small groups by sector and level, or tailored programs for your company.', 'Professionals & companies', 'B1', 'C2', 90, 5, 45, 'career', 'charcoal'],
        ['pronunciation-lab', 'Pronunciation Lab', 'pronunciation', 'Sound clear. Sound confident.', 'A hands-on workshop on sounds, stress, rhythm and intonation. Record yourself, get precise feedback and leave with drills you can practise every day.', 'All learners', 'A2', 'C2', 60, 5, 35, 'pronunciation,career', 'green'],
        ['kids-english', 'Kids English Club', 'kids', 'Games, stories and songs — in English.', 'For 6–11 year olds: playful sessions with a native teacher, five kids per table. Stories, games, crafts and songs build vocabulary and confidence naturally.', 'Kids 6–11', 'A1', 'B1', 60, 5, 25, 'school', 'amber'],
        ['teens-english', 'Teens English Lab', 'teens', 'Speak up. Be yourself. In English.', 'For 12–17 year olds: debates, podcasts, music, school support and exam preparation in a cool café setting with native teachers.', 'Teens 12–17', 'A2', 'B2', 75, 5, 27, 'school,exams,conversation', 'cream'],
    ];
    $pid = [];
    foreach ($programs as $i => $p) {
        $pid[$p[0]] = DB::insert('programs', array_combine(
            ['slug', 'name', 'category', 'tagline', 'description', 'audience', 'min_level', 'max_level', 'duration_min', 'capacity', 'price', 'goals', 'color'], $p
        ) + ['sort_order' => $i, 'membership_eligible' => $p[2] === 'one_to_one' ? 0 : 1]);
    }

    // --- Professeurs natifs (+ comptes de connexion)
    $teachers = [
        ['Olivia Bennett', 'London, UK', 'British', 'Ex-consultant turned business English coach', 'Olivia spent ten years presenting to boards in the City before falling in love with teaching. She helps professionals sound sharp, warm and persuasive.', 'Business,Presentations,Negotiation'],
        ['Liam O’Connor', 'Dublin, Ireland', 'Irish', 'Storyteller, podcaster, chief small-talk officer', 'Liam believes everyone has a story worth telling in English. His tables are known for laughter and surprisingly deep conversations.', 'Conversation,Storytelling,Idioms'],
        ['Maya Thompson', 'Toronto, Canada', 'Canadian', 'Kids & teens specialist, former drama teacher', 'Maya turns shy kids into confident speakers with games, theatre and a lot of energy. Parents love her detailed progress notes.', 'Kids,Teens,Drama'],
        ['James Carter', 'New York, USA', 'American', 'Voice coach & pronunciation nerd', 'Trained in phonetics and voice, James runs the Pronunciation Lab. He will help you tame the “th”, master word stress and sound like yourself — only clearer.', 'Pronunciation,Accent,Public speaking'],
        ['Isla MacLeod', 'Edinburgh, Scotland', 'Scottish', 'IELTS & Cambridge examiner experience', 'Isla knows exactly what examiners listen for. Her structured, encouraging style has helped hundreds of students reach their target band.', 'IELTS,Cambridge,Academic'],
        ['Noah Williams', 'Sydney, Australia', 'Australian', 'Traveller, surfer, everyday-English enthusiast', 'Noah has lived on four continents and makes English feel easy and fun. Perfect for travel English and building confidence fast.', 'Travel,Everyday English,Confidence'],
    ];
    $tid = [];
    foreach ($teachers as $i => $t) {
        $slug = strtolower(preg_replace('/[^a-z]/i', '', explode(' ', $t[0])[0]));
        $uid = DB::insert('users', ['role' => 'teacher', 'first_name' => explode(' ', $t[0])[0], 'last_name' => explode(' ', $t[0], 2)[1], 'email' => $demo ? "$slug@xyacademy.cafe" : null, 'password_hash' => $demo ? password_hash('demo1234', PASSWORD_DEFAULT) : null, 'cefr_level' => 'C2']);
        $tid[] = DB::insert('teachers', ['user_id' => $uid, 'display_name' => $t[0], 'origin' => $t[1], 'accent' => $t[2], 'headline' => $t[3], 'bio' => $t[4], 'specialties' => $t[5], 'hourly_rate' => 65, 'sort_order' => $i]);
    }
    [$olivia, $liam, $maya, $james, $isla, $noah] = $tid;

    // --- Formules
    $plans = [
        ['espresso', 'Espresso', 'Light & regular', 89, 4, 0, "4 sessions per month\nGroups, tables, labs & kids club\nAccess to the library for your level\nProgress report every month", 0],
        ['flat-white', 'Flat White', 'Our most popular rhythm', 159, 8, 1, "8 sessions per month\nAll events included (quiz, debate, movie night…)\nFull library access for your level\nMonthly 1-to-1 progress check\nPriority on waitlists", 1],
        ['club', 'Club Unlimited', 'Live in English', 249, 0, 1, "Unlimited group sessions & tables\nAll events included\nOne 1-to-1 coaching per month\nMembers’ lounge & coworking hours\nGuest pass every month", 0],
    ];
    foreach ($plans as $i => $p) DB::insert('membership_plans', ['slug' => $p[0], 'name' => $p[1], 'tagline' => $p[2], 'price' => $p[3], 'credits' => $p[4], 'includes_events' => $p[5], 'perks' => $p[6], 'is_featured' => $p[7], 'sort_order' => $i]);

    // --- Événements
    $events = [
        ['quiz', 'Pub Quiz Night: Around the World', 'Teams of five, eight rounds, one champion table. Trivia, music and picture rounds — all in English.', 4, '19:30', 40, 10, 'A2'],
        ['debate', 'Debate Club: Should we work 4 days a week?', 'Pick a side, build your arguments and defend them with a native moderator.', 6, '19:00', 24, 8, 'B1'],
        ['movie', 'Movie Night: Classic British Comedy', 'Popcorn, English subtitles and a relaxed discussion afterwards.', 9, '19:30', 35, 6, 'A2'],
        ['networking', 'Afterwork Networking in English', 'Meet professionals from 20+ countries. Ice-breakers hosted by our business coaches.', 11, '18:30', 50, 15, 'B1'],
        ['book_club', 'Book Club: Short Stories Evening', 'We read a short story together beforehand and discuss it over coffee and cake.', 13, '18:30', 16, 0, 'B2'],
        ['karaoke', 'Karaoke Night: Sing it in English!', 'The most fun pronunciation lesson of the month. Lyrics on screen, no judgement.', 16, '20:00', 45, 12, 'A1'],
        ['quiz', 'Quiz Night: Movies & Music', 'Name that tune, guess the film, win the trophy mug.', 18, '19:30', 40, 10, 'A2'],
        ['debate', 'Debate Club: AI in education', 'Is AI the best teacher ever — or the end of learning? Let’s discuss.', 20, '19:00', 24, 8, 'B2'],
    ];
    foreach ($events as $ev) {
        $st = strtotime(date('Y-m-d', strtotime("+{$ev[3]} days", $now)) . ' ' . $ev[4]);
        DB::insert('events', ['type' => $ev[0], 'title' => $ev[1], 'description' => $ev[2], 'starts_at' => $dt($st), 'ends_at' => $dt($st + 7200), 'capacity' => $ev[5], 'price' => $ev[6], 'min_level' => $ev[7], 'location' => 'XY Academy Speaking Café']);
    }

    // --- Bibliothèque
    $books = json_decode((string)file_get_contents(__DIR__ . '/books.json'), true) ?: [];
    foreach ($books as $b) {
        DB::insert('books', [
            'title' => $b['title'], 'author' => 'XY Academy Editorial', 'level' => $b['level'], 'category' => $b['category'],
            'description' => 'Units: ' . implode(' · ', $b['units']) . '. Key phrases, café dialogues and speaking prompts to bring to your table.',
            'file_path' => $b['file'], 'cover_color' => $b['color'], 'pages' => $b['pages'], 'downloads' => random_int(12, 340),
        ]);
    }

    // --- Planning des 4 prochaines semaines
    $slot = function (string $prog, ?int $teacher, int $dayTs, string $time, ?string $level = null, ?string $title = null, ?int $cap = null) use ($pid, $dt) {
        $p = DB::one('SELECT duration_min, capacity FROM programs WHERE id = ?', [$pid[$prog]]);
        $st = strtotime(date('Y-m-d', $dayTs) . ' ' . $time);
        return DB::insert('sessions', ['program_id' => $pid[$prog], 'teacher_id' => $teacher, 'level' => $level, 'title' => $title, 'starts_at' => $dt($st), 'ends_at' => $dt($st + $p['duration_min'] * 60), 'capacity' => $cap ?? $p['capacity'], 'room' => ['Green booth', 'Library table', 'Window table', 'Lounge'][random_int(0, 3)]]);
    };
    $themes = ['Food & culture', 'Dream jobs', 'Travel stories', 'Tech & us', 'Cities we love', 'Money talk', 'Movies & series', 'Big decisions', 'Sport & health', 'The future'];
    $sessionsMade = [];
    for ($d = 0; $d < 28; $d++) {
        $day = strtotime("+$d day", strtotime('today'));
        $dow = (int)date('N', $day);
        $th = $themes[$d % count($themes)];
        if ($dow <= 5) {
            $sessionsMade[] = $slot('conversation-tables', $d % 2 ? $liam : $noah, $day, '10:00', null, 'Morning table: ' . $th);
            $sessionsMade[] = $slot('one-to-one', [$olivia, $james, $isla, $noah, $liam][$dow - 1], $day, '09:00');
            $sessionsMade[] = $slot('one-to-one', [$isla, $olivia, $liam, $james, $maya][$dow - 1], $day, '14:00');
            $sessionsMade[] = $slot('conversation-groups', $liam, $day, '18:00', 'B1', $th);
            $sessionsMade[] = $slot('conversation-groups', $noah, $day, '19:30', 'B2', $th);
            if (in_array($dow, [2, 4], true)) $sessionsMade[] = $slot('business-english', $olivia, $day, '12:30', 'B2', 'Meetings & presentations');
            if (in_array($dow, [1, 3], true)) $sessionsMade[] = $slot('pronunciation-lab', $james, $day, '18:30', null, ['Vowels & the schwa', 'Word stress & rhythm'][$dow === 1 ? 0 : 1]);
            if ($dow === 3) $sessionsMade[] = $slot('kids-english', $maya, $day, '14:30', 'A1', 'Story time & games');
            if ($dow === 3) $sessionsMade[] = $slot('teens-english', $maya, $day, '16:30', 'A2', 'Podcast club');
            if ($dow === 2) $sessionsMade[] = $slot('conversation-groups', $isla, $day, '18:00', 'A2', 'Everyday English');
            if ($dow === 4) $sessionsMade[] = $slot('conversation-groups', $isla, $day, '18:00', 'C1', 'Debate & nuance');
        } elseif ($dow === 6) {
            $sessionsMade[] = $slot('kids-english', $maya, $day, '10:00', 'A1', 'Saturday kids club');
            $sessionsMade[] = $slot('kids-english', $maya, $day, '11:15', 'A2', 'Saturday kids club');
            $sessionsMade[] = $slot('teens-english', $liam, $day, '13:30', 'B1', 'Teens debate');
            $sessionsMade[] = $slot('conversation-tables', $noah, $day, '15:30', null, 'Weekend table: ' . $th);
        }
    }

    if (!$demo) return;

    // --- Comptes de démonstration
    $pw = password_hash('demo1234', PASSWORD_DEFAULT);
    $mk = fn(array $d) => DB::insert('users', $d + ['password_hash' => $pw]);
    $sofia = $mk(['role' => 'student', 'first_name' => 'Sofia', 'last_name' => 'Martin', 'email' => 'student@demo.cafe', 'cefr_level' => 'B1', 'goal' => 'career']);
    $parent = $mk(['role' => 'parent', 'first_name' => 'Karim', 'last_name' => 'Haddad', 'email' => 'parent@demo.cafe', 'cefr_level' => 'B2']);
    $adam = DB::insert('users', ['role' => 'student', 'first_name' => 'Adam', 'last_name' => 'Haddad', 'cefr_level' => 'A2', 'birth_year' => 2013, 'parent_id' => $parent, 'goal' => 'school']);
    $lina = DB::insert('users', ['role' => 'student', 'first_name' => 'Lina', 'last_name' => 'Haddad', 'cefr_level' => 'A1', 'birth_year' => 2017, 'parent_id' => $parent, 'goal' => 'school']);
    $company = DB::insert('companies', ['name' => 'Northwind Logistics', 'contact_email' => 'hr@demo.cafe', 'seats_included' => 12]);
    $hr = $mk(['role' => 'corporate', 'first_name' => 'Claire', 'last_name' => 'Dubois', 'email' => 'hr@demo.cafe', 'company_id' => $company, 'cefr_level' => 'C1']);
    $emps = [];
    foreach ([['Thomas', 'Leroy', 'B1'], ['Amélie', 'Roux', 'B2'], ['Yanis', 'Benali', 'A2']] as $e) {
        $emps[] = $mk(['role' => 'student', 'first_name' => $e[0], 'last_name' => $e[1], 'email' => strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', $e[0])) . '@northwind.demo', 'cefr_level' => $e[2], 'company_id' => $company, 'goal' => 'career']);
    }
    $crowd = [];
    foreach (['Lucas', 'Emma', 'Hugo', 'Chloé', 'Nina', 'Omar', 'Julia', 'Kenji'] as $n) {
        $crowd[] = DB::insert('users', ['role' => 'student', 'first_name' => $n, 'last_name' => 'Demo', 'cefr_level' => 'B1', 'email' => null]);
    }

    // Abonnement actif de Sofia
    $mid = DB::insert('memberships', ['user_id' => $sofia, 'plan_id' => (int)DB::val("SELECT id FROM membership_plans WHERE slug='flat-white'"), 'status' => 'active', 'starts_at' => date('Y-m-d', strtotime('-12 days')), 'ends_at' => date('Y-m-d', strtotime('+18 days')), 'credits_remaining' => 5]);
    DB::insert('payments', ['user_id' => $sofia, 'purpose' => 'membership', 'reference_id' => $mid, 'amount' => 159, 'currency' => $currency, 'method' => 'stripe', 'status' => 'paid', 'paid_at' => $dt(strtotime('-12 days')), 'provider_ref' => 'demo_seed']);

    // Historique : séances passées + progression croissante
    $past = [];
    $skills = [[38, 45, 35, 40, 30], [42, 48, 38, 44, 36], [47, 52, 41, 47, 44], [51, 55, 46, 52, 51], [55, 58, 50, 56, 58], [59, 62, 55, 60, 64]];
    foreach ($skills as $k => $s) {
        $when = strtotime('-' . (60 - $k * 10) . ' days 18:00');
        $sid = DB::insert('sessions', ['program_id' => $pid['conversation-groups'], 'teacher_id' => $k % 2 ? $liam : $noah, 'level' => 'B1', 'title' => $themes[$k], 'starts_at' => $dt($when), 'ends_at' => $dt($when + 4500), 'capacity' => 5, 'status' => 'completed']);
        $bid = DB::insert('bookings', ['session_id' => $sid, 'user_id' => $sofia, 'booked_by' => $sofia, 'status' => $k === 2 ? 'no_show' : 'attended', 'membership_id' => $mid, 'teacher_note' => $k === 5 ? 'Great energy today — work on past perfect and linking words.' : null]);
        DB::insert('progress', ['user_id' => $sofia, 'teacher_id' => $k % 2 ? $liam : $noah, 'session_id' => $sid, 'cefr_level' => $k >= 5 ? 'B1' : ($k >= 2 ? 'B1' : 'A2'), 'speaking' => $s[0], 'listening' => $s[1], 'pronunciation' => $s[2], 'vocabulary' => $s[3], 'confidence' => $s[4], 'notes' => ['Shy at first but engaged.', 'Good questions, more past tenses please.', 'Missed session.', 'Much more fluent!', 'Excellent debate contributions.', 'Ready to aim for B2 next term.'][$k], 'created_at' => $dt($when + 5000)]);
    }

    // Réservations à venir + une séance complète avec liste d'attente
    $upcoming = DB::all("SELECT s.id, p.slug FROM sessions s JOIN programs p ON p.id = s.program_id WHERE s.starts_at > NOW() + INTERVAL 1 DAY AND s.status='scheduled' ORDER BY s.starts_at LIMIT 60");
    $groups = array_values(array_filter($upcoming, fn($s) => $s['slug'] === 'conversation-groups'));
    $kids = array_values(array_filter($upcoming, fn($s) => $s['slug'] === 'kids-english'));
    $biz = array_values(array_filter($upcoming, fn($s) => $s['slug'] === 'business-english'));
    $tables = array_values(array_filter($upcoming, fn($s) => $s['slug'] === 'conversation-tables'));

    $bk = function (int $session, int $user, int $by, string $status = 'confirmed', ?int $pos = null) use ($currency) {
        $id = DB::insert('bookings', ['session_id' => $session, 'user_id' => $user, 'booked_by' => $by, 'status' => $status, 'waitlist_position' => $pos]);
        if ($status === 'confirmed') {
            $p = DB::insert('payments', ['user_id' => $by, 'purpose' => 'booking', 'reference_id' => $id, 'amount' => 0, 'currency' => $currency, 'method' => 'credit', 'status' => 'paid', 'paid_at' => date('Y-m-d H:i:s')]);
            DB::update('bookings', ['payment_id' => $p], 'id = ?', [$id]);
        }
        return $id;
    };
    if (isset($groups[0])) { // séance complète + 2 en attente
        foreach (array_slice($crowd, 0, 5) as $c) $bk((int)$groups[0]['id'], $c, $c);
        $bk((int)$groups[0]['id'], $crowd[5], $crowd[5], 'waitlist', 1);
        $bk((int)$groups[0]['id'], $sofia, $sofia, 'waitlist', 2);
    }
    if (isset($groups[2])) { $bk((int)$groups[2]['id'], $sofia, $sofia); foreach (array_slice($crowd, 5, 3) as $c) $bk((int)$groups[2]['id'], $c, $c); }
    if (isset($tables[1])) $bk((int)$tables[1]['id'], $sofia, $sofia);
    if (isset($kids[0])) { $bk((int)$kids[0]['id'], $lina, $parent); }
    if (isset($kids[1])) { $bk((int)$kids[1]['id'], $adam, $parent); }
    if (isset($biz[0])) foreach ($emps as $e) $bk((int)$biz[0]['id'], $e, $hr);

    foreach ([$adam => [[30, 34, 28, 33, 40], [36, 40, 33, 38, 48]], $lina => [[18, 22, 20, 19, 25], [24, 27, 25, 26, 35]]] as $child => $rows) {
        foreach ($rows as $k => $s) DB::insert('progress', ['user_id' => $child, 'teacher_id' => $maya, 'cefr_level' => $child === $adam ? 'A2' : 'A1', 'speaking' => $s[0], 'listening' => $s[1], 'pronunciation' => $s[2], 'vocabulary' => $s[3], 'confidence' => $s[4], 'notes' => $k ? 'Loves the story games, speaks more every week.' : 'First session — a little shy.', 'created_at' => $dt(strtotime('-' . (30 - $k * 14) . ' days'))]);
    }
    foreach ($emps as $k => $e) DB::insert('progress', ['user_id' => $e, 'teacher_id' => $olivia, 'cefr_level' => ['B1', 'B2', 'A2'][$k], 'speaking' => [52, 66, 34][$k], 'listening' => [58, 70, 40][$k], 'pronunciation' => [49, 61, 33][$k], 'vocabulary' => [55, 68, 37][$k], 'confidence' => [47, 63, 30][$k], 'notes' => 'Onboarding assessment']);
    DB::insert('contact_messages', ['name' => 'Julie Morel', 'email' => 'julie@example.com', 'topic' => 'Business', 'message' => 'Hello, we are a team of 15 and would love a tailored Business English program. Can we talk?']);
}
