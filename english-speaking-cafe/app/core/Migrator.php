<?php
/**
 * Migrations automatiques (exécutées une seule fois, au premier chargement après mise à jour).
 * v2 : contenus en turc (colonnes *_tr) + textes turcs par défaut.
 */
declare(strict_types=1);

namespace App\Core;

final class Migrator
{
    public const VERSION = 3;

    public static function run(): void
    {
        $v = (int)(DB::val("SELECT `value` FROM settings WHERE `key` = 'schema_version'") ?? 1);
        if ($v >= self::VERSION) return;
        if ($v < 2) self::v2();
        if ($v < 3) self::v3();
        DB::q("INSERT INTO settings (`key`, `value`, label) VALUES ('schema_version', ?, 'Schema version') ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)", [(string)self::VERSION]);
    }

    private static function addColumn(string $table, string $col, string $def): void
    {
        $exists = DB::val('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?', [$table, $col]);
        if (!$exists) DB::pdo()->exec("ALTER TABLE `$table` ADD COLUMN `$col` $def");
    }

    /** Colonnes turques + traductions du contenu de départ (sans écraser une saisie existante) */
    private static function v2(): void
    {
        foreach (['name_tr' => 'VARCHAR(120) NULL', 'tagline_tr' => 'VARCHAR(190) NULL', 'description_tr' => 'TEXT NULL', 'audience_tr' => 'VARCHAR(120) NULL'] as $c => $d) self::addColumn('programs', $c, $d);
        foreach (['headline_tr' => 'VARCHAR(190) NULL', 'bio_tr' => 'TEXT NULL'] as $c => $d) self::addColumn('teachers', $c, $d);
        foreach (['tagline_tr' => 'VARCHAR(190) NULL', 'perks_tr' => 'TEXT NULL'] as $c => $d) self::addColumn('membership_plans', $c, $d);
        foreach (['title_tr' => 'VARCHAR(160) NULL', 'description_tr' => 'TEXT NULL'] as $c => $d) self::addColumn('events', $c, $d);

        $programs = [
            'conversation-groups' => ['Küçük Grup Sohbeti', 'Beş öğrenci. Bir anadili İngilizce öğretmen. Gerçek sohbet.', 'İmza formatımız. Beş kişilik bir masada, anadili İngilizce olan bir öğretmenle hayat, iş, seyahat ve fikirler üzerine konuşun. Her seansın bir teması ve hafif bir yapısı var; ama asıl odak konuşmak: klasik bir derse göre ortalama 12 kat daha fazla konuşursunuz.', '16 yaş üstü gençler, üniversite öğrencileri, yetişkinler'],
            'one-to-one' => ['Birebir Koçluk', 'Hedefleriniz. Temponuz. Koçunuz.', 'Size özel, anadili İngilizce olan bir koçla birebir seans: iş görüşmesi, sunum, sınav, aksan ya da sadece özgüven. Tek seans veya paket olarak, kafede ya da online.', 'Herkes'],
            'conversation-tables' => ['Sohbet Masaları', 'Uğrayın, kahvenizi alın, masaya katılın.', 'Anadili İngilizce olan bir sunucunun yönettiği rahat, randevusuz sohbet masaları. Hazırlık yok, baskı yok: bir konu kartı, güzel bir içecek ve dünyanın dört bir yanından yeni insanlar.', 'Yetişkinler ve üniversite öğrencileri'],
            'business-english' => ['İş İngilizcesi', 'Toplantılar, sunumlar ve müzakereler — İngilizce.', 'Profesyoneller için pratik İngilizce: toplantılar, sunumlar, müzakereler, e-postalar ve networking. Sektöre ve seviyeye göre küçük gruplar ya da şirketinize özel programlar.', 'Profesyoneller ve şirketler'],
            'pronunciation-lab' => ['Telaffuz Atölyesi', 'Net konuşun. Özgüvenle konuşun.', 'Sesler, vurgu, ritim ve tonlama üzerine uygulamalı bir atölye. Kendinizi kaydedin, net geri bildirim alın ve her gün çalışabileceğiniz alıştırmalarla ayrılın.', 'Tüm öğrenciler'],
            'kids-english' => ['Çocuk İngilizce Kulübü', 'Oyunlar, hikâyeler ve şarkılar — İngilizce.', '6–11 yaş için: anadili İngilizce olan bir öğretmenle, masa başına beş çocukla eğlenceli seanslar. Hikâyeler, oyunlar, el işleri ve şarkılar kelime dağarcığını ve özgüveni doğal yoldan geliştirir.', '6–11 yaş çocuklar'],
            'teens-english' => ['Genç İngilizce Atölyesi', 'Sesini yükselt. Kendin ol. İngilizce.', '12–17 yaş için: kafe ortamında anadili İngilizce olan öğretmenlerle münazaralar, podcast’ler, müzik, okul desteği ve sınav hazırlığı.', '12–17 yaş gençler'],
        ];
        foreach ($programs as $slug => [$n, $t, $d, $a]) {
            DB::q('UPDATE programs SET name_tr = COALESCE(name_tr, ?), tagline_tr = COALESCE(tagline_tr, ?), description_tr = COALESCE(description_tr, ?), audience_tr = COALESCE(audience_tr, ?) WHERE slug = ?', [$n, $t, $d, $a, $slug]);
        }

        $teachers = [
            'Olivia Bennett' => ['Danışmanlıktan İş İngilizcesi koçluğuna geçti', 'Olivia, öğretmenliğe âşık olmadan önce on yıl boyunca Londra’da yönetim kurullarına sunum yaptı. Profesyonellerin net, sıcak ve ikna edici konuşmasına yardımcı oluyor.'],
            'Liam O’Connor' => ['Hikâye anlatıcısı, podcast yapımcısı, sohbet ustası', 'Liam herkesin İngilizce anlatmaya değer bir hikâyesi olduğuna inanıyor. Masaları kahkahalarıyla ve şaşırtıcı derecede derin sohbetleriyle tanınıyor.'],
            'Maya Thompson' => ['Çocuk ve genç uzmanı, eski drama öğretmeni', 'Maya oyunlar, tiyatro ve bolca enerjiyle utangaç çocukları özgüvenli konuşmacılara dönüştürüyor. Veliler onun ayrıntılı gelişim notlarına bayılıyor.'],
            'James Carter' => ['Ses koçu ve telaffuz tutkunu', 'Fonetik ve ses eğitimi almış olan James, Telaffuz Atölyesi’ni yönetiyor. “th” sesinde, kelime vurgusunda ve kendi sesinizle — sadece daha net — konuşmada size yardımcı olur.'],
            'Isla MacLeod' => ['IELTS ve Cambridge sınav deneyimi', 'Isla sınav görevlilerinin neye dikkat ettiğini çok iyi biliyor. Yapılandırılmış ve cesaretlendirici tarzıyla yüzlerce öğrencinin hedef puanına ulaşmasına yardım etti.'],
            'Noah Williams' => ['Gezgin, sörfçü, günlük İngilizce meraklısı', 'Noah dört kıtada yaşadı ve İngilizceyi kolay ve eğlenceli hâle getiriyor. Seyahat İngilizcesi ve hızla özgüven kazanmak için ideal.'],
        ];
        foreach ($teachers as $name => [$h, $b]) {
            DB::q('UPDATE teachers SET headline_tr = COALESCE(headline_tr, ?), bio_tr = COALESCE(bio_tr, ?) WHERE display_name = ?', [$h, $b, $name]);
        }

        $plans = [
            'espresso' => ['Hafif ve düzenli', "Ayda 4 seans\nGruplar, masalar, atölyeler ve çocuk kulübü\nSeviyenize uygun kütüphane erişimi\nHer ay gelişim raporu"],
            'flat-white' => ['En sevilen ritim', "Ayda 8 seans\nTüm etkinlikler dahil (quiz, münazara, film gecesi…)\nSeviyenize uygun tam kütüphane erişimi\nAylık birebir gelişim değerlendirmesi\nBekleme listelerinde öncelik"],
            'club' => ['İngilizce yaşayın', "Sınırsız grup seansı ve sohbet masası\nTüm etkinlikler dahil\nAyda bir birebir koçluk\nÜyelere özel salon ve çalışma saatleri\nHer ay bir misafir davetiyesi"],
        ];
        foreach ($plans as $slug => [$t, $p]) {
            DB::q('UPDATE membership_plans SET tagline_tr = COALESCE(tagline_tr, ?), perks_tr = COALESCE(perks_tr, ?) WHERE slug = ?', [$t, $p, $slug]);
        }

        $events = [
            'Pub Quiz Night: Around the World' => ['Pub Quiz Gecesi: Dünya Turu', 'Beşer kişilik takımlar, sekiz tur, tek şampiyon masa. Genel kültür, müzik ve görsel turlar — hepsi İngilizce.'],
            'Debate Club: Should we work 4 days a week?' => ['Münazara Kulübü: Haftada 4 gün mü çalışmalıyız?', 'Tarafınızı seçin, argümanlarınızı kurun ve anadili İngilizce olan bir moderatörle savunun.'],
            'Movie Night: Classic British Comedy' => ['Film Gecesi: Klasik İngiliz Komedisi', 'Patlamış mısır, İngilizce altyazı ve ardından keyifli bir sohbet.'],
            'Afterwork Networking in English' => ['İngilizce Afterwork Networking', '20’den fazla ülkeden profesyonellerle tanışın. Buz kırıcı oyunları iş İngilizcesi koçlarımız yönetiyor.'],
            'Book Club: Short Stories Evening' => ['Kitap Kulübü: Kısa Öykü Akşamı', 'Önceden kısa bir öykü okuyor, kahve ve kek eşliğinde birlikte tartışıyoruz.'],
            'Karaoke Night: Sing it in English!' => ['Karaoke Gecesi: İngilizce Söyle!', 'Ayın en eğlenceli telaffuz dersi. Şarkı sözleri ekranda, yargı yok.'],
            'Quiz Night: Movies & Music' => ['Quiz Gecesi: Filmler ve Müzik', 'Şarkıyı bil, filmi tahmin et, kupayı kazan.'],
            'Debate Club: AI in education' => ['Münazara Kulübü: Eğitimde Yapay Zekâ', 'Yapay zekâ gelmiş geçmiş en iyi öğretmen mi, yoksa öğrenmenin sonu mu? Hadi tartışalım.'],
        ];
        foreach ($events as $title => [$t, $d]) {
            DB::q('UPDATE events SET title_tr = COALESCE(title_tr, ?), description_tr = COALESCE(description_tr, ?) WHERE title = ?', [$t, $d, $title]);
        }

        DB::q("INSERT IGNORE INTO settings (`key`, `value`, label) VALUES ('opening_hours_tr', 'Pzt–Cum 9:00–21:30 · Cmt 10:00–18:00', 'Opening hours (Turkish)')");
        DB::q("INSERT IGNORE INTO settings (`key`, `value`, label) VALUES ('hero_subtitle_tr', ?, 'Hero subtitle (Turkish)')",
            ['Anadili İngilizce olan öğretmenlerle, beş kişilik masalarda, güzel bir kahve eşliğinde gerçek İngilizce pratiği yaptığınız butik bir kafe.']);
    }

    /** v3 : l'équipe passe à 4 professeurs (photos, CV, textes EN/TR) */
    private static function v3(): void
    {
        // ancien nom (données de démo) => nouveau profil
        $team = [
            'Maya Thompson' => ['Hatice Söz Xynidis', 'hatice', 'Hatice', 'Söz Xynidis', 'London, UK', 'British', 75, 0, 'assets/img/teachers/hatice-soz-xynidis.jpg', 'Kids,Teens,IELTS,Pronunciation',
                'Co-founder & Director · Native speaker raised in London',
                'Born and raised in London to a Turkish family, Hatice is a native English speaker who is also fully fluent in Turkish — so she knows exactly where Turkish speakers get stuck. She holds a BA (Hons) in English Language & Linguistics, an MA in TESOL and the Cambridge CELTA, and has taught for more than 15 years in the UK and in Istanbul. She co-founded XY Academy with Nikolas Xynidis to create the place she always wished existed for her own students: a warm café where English is spoken, not just studied. Today she directs the academy, designs every program, trains the team and still teaches the Kids & Teens clubs and IELTS preparation.',
                'Kurucu ortak ve Direktör · Londra’da büyümüş, anadili İngilizce',
                'Türk bir ailenin kızı olarak Londra’da doğup büyüyen Hatice, anadili İngilizce olan ve Türkçeyi de akıcı konuşan bir eğitmen — bu yüzden Türkçe konuşanların nerede zorlandığını çok iyi biliyor. İngiliz Dili ve Dilbilimi lisans (BA Hons), TESOL yüksek lisans derecelerine ve Cambridge CELTA sertifikasına sahip; 15 yılı aşkın süredir Birleşik Krallık’ta ve İstanbul’da ders veriyor. XY Academy’yi Nikolas Xynidis ile birlikte, öğrencileri için hep hayal ettiği yeri yaratmak için kurdu: İngilizcenin sadece çalışıldığı değil, konuşulduğu sıcak bir kafe. Bugün akademiyi yönetiyor, tüm programları tasarlıyor, ekibi yetiştiriyor ve Çocuk-Genç kulüpleri ile IELTS hazırlığında hâlâ ders veriyor.'],
            'Olivia Bennett' => ['Julia Rombert', 'julia', 'Julia', 'Rombert', 'Istanbul, Türkiye', 'American', 85, 1, 'assets/img/teachers/julia-rombert.jpg', 'Business,Negotiation,Presentations,Networking',
                'International business expert · Business English coach',
                'Julia grew up in Salt Lake City and studied languages and linguistics at the University of Utah before building a career in international business — sales, negotiations and partnerships between the US, Europe and Türkiye. Now living in Istanbul, she coaches professionals and teams on meetings, presentations, negotiations and networking in English, with the real-world reflexes only a business insider can teach.',
                'Uluslararası iş uzmanı · İş İngilizcesi koçu',
                'Salt Lake City’de büyüyen Julia, University of Utah’ta diller ve dilbilim okuduktan sonra uluslararası iş dünyasında kariyer yaptı: ABD, Avrupa ve Türkiye arasında satış, müzakere ve ortaklıklar. Şimdi İstanbul’da yaşayan Julia, profesyonellere ve ekiplere İngilizce toplantı, sunum, müzakere ve networking koçluğu yapıyor — ancak iş dünyasının içinden biri olarak öğretilebilecek gerçek reflekslerle.'],
            'Liam O’Connor' => ['Nikolas Xynidis', 'nikolas', 'Nikolas', 'Xynidis', 'Istanbul, Türkiye', 'Canadian', 45, 2, 'assets/img/teachers/nikolas-xynidis.jpg', 'Conversation,Teens,University life,Business basics',
                'Co-founder · Conversation host · English & Business student',
                'Raised bilingual by a Canadian father, Nikolas speaks English as a native with a relaxed North-American accent. He is in the final year of his degree in English Language & International Business, which makes him the perfect host for university students, young professionals and teens: real-life topics, today’s vocabulary, zero pressure. He co-founded XY Academy with Hatice, runs our conversation tables and evening groups, and looks after the café’s community, events and partnerships.',
                'Kurucu ortak · Sohbet sunucusu · İngilizce ve İşletme öğrencisi',
                'Kanadalı babası sayesinde iki dilli büyüyen Nikolas, rahat bir Kuzey Amerika aksanıyla İngilizceyi anadili olarak konuşuyor. İngiliz Dili ve Uluslararası İşletme bölümünün son sınıfında; bu da onu üniversite öğrencileri, genç profesyoneller ve gençler için ideal bir sunucu yapıyor: gerçek hayat konuları, güncel kelimeler, sıfır baskı. XY Academy’yi Hatice ile birlikte kurdu; sohbet masalarımızı ve akşam gruplarımızı yönetiyor, kafenin topluluğu, etkinlikleri ve iş birliklerinden sorumlu.'],
            'James Carter' => ['Ethan Clarke', 'ethan', 'Ethan', 'Clarke', 'Melbourne, Australia', 'Australian', 65, 3, 'assets/img/teachers/ethan-clarke.jpg', 'Pronunciation,Public speaking,Everyday English,Travel',
                'Pronunciation & public-speaking coach',
                'A Melbourne native, Ethan trained as an actor before moving into teaching with the Cambridge CELTA and a specialism in phonetics. After five years teaching in Sydney, London and Barcelona, he settled in Istanbul, where he runs our Pronunciation Lab: sounds, stress, rhythm and intonation — and the confidence to use them. Learners love his energy and his very practical drills.',
                'Telaffuz ve topluluk önünde konuşma koçu',
                'Melbourne doğumlu Ethan, öğretmenliğe geçmeden önce oyunculuk eğitimi aldı; Cambridge CELTA sertifikası ve fonetik uzmanlığıyla Sidney, Londra ve Barselona’da beş yıl ders verdi. Şimdi İstanbul’da yaşıyor ve Telaffuz Atölyemizi yönetiyor: sesler, vurgu, ritim ve tonlama — ve bunları kullanma özgüveni. Öğrenciler enerjisini ve son derece pratik alıştırmalarını çok seviyor.'],
        ];
        $ids = [];
        foreach ($team as $old => [$name, $slug, $first, $last, $origin, $accent, $rate, $order, $photo, $spec, $head, $bio, $headTr, $bioTr]) {
            $row = DB::one('SELECT * FROM teachers WHERE display_name = ?', [$name]) ?? DB::one('SELECT * FROM teachers WHERE display_name = ?', [$old]);
            $data = ['display_name' => $name, 'origin' => $origin, 'accent' => $accent, 'hourly_rate' => $rate, 'sort_order' => $order, 'photo' => $photo, 'specialties' => $spec,
                     'headline' => $head, 'bio' => $bio, 'headline_tr' => $headTr, 'bio_tr' => $bioTr, 'is_active' => 1];
            if ($row) { DB::update('teachers', $data, 'id = ?', [$row['id']]); $id = (int)$row['id']; }
            else $id = DB::insert('teachers', $data);
            $ids[$old] = $id;
            // Compte de connexion lié : nouveau nom (+ e-mail de démo renommé)
            if ($row && $row['user_id']) {
                $u = DB::one('SELECT email FROM users WHERE id = ?', [$row['user_id']]);
                $upd = ['first_name' => $first, 'last_name' => $last];
                $newEmail = $slug . '@xyacademy.cafe';
                if ($u && $u['email'] && str_ends_with((string)$u['email'], '@xyacademy.cafe') && !DB::val('SELECT id FROM users WHERE email = ?', [$newEmail])) $upd['email'] = $newEmail;
                DB::update('users', $upd, 'id = ?', [$row['user_id']]);
            }
        }
        // Anciens profils de démo restants : séances et rapports réattribués, profils désactivés
        foreach (['Isla MacLeod' => 'Maya Thompson', 'Noah Williams' => 'James Carter'] as $gone => $to) {
            $g = DB::one('SELECT * FROM teachers WHERE display_name = ?', [$gone]);
            if (!$g) continue;
            DB::q('UPDATE sessions SET teacher_id = ? WHERE teacher_id = ?', [$ids[$to], $g['id']]);
            DB::q('UPDATE progress SET teacher_id = ? WHERE teacher_id = ?', [$ids[$to], $g['id']]);
            DB::update('teachers', ['is_active' => 0], 'id = ?', [$g['id']]);
            if ($g['user_id']) DB::update('users', ['is_active' => 0], 'id = ?', [$g['user_id']]);
        }
    }
}
