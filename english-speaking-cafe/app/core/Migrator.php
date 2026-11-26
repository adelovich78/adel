<?php
/**
 * Migrations automatiques (exécutées une seule fois, au premier chargement après mise à jour).
 * v2 : contenus en turc (colonnes *_tr) + textes turcs par défaut.
 */
declare(strict_types=1);

namespace App\Core;

final class Migrator
{
    public const VERSION = 2;

    public static function run(): void
    {
        $v = (int)(DB::val("SELECT `value` FROM settings WHERE `key` = 'schema_version'") ?? 1);
        if ($v >= self::VERSION) return;
        if ($v < 2) self::v2();
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
}
