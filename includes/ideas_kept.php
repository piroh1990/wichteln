<?php

function ideas2_kept() {
    $lang = ideas2_lang();
    $blocks = array(
        'de' => <<<'HTML'
<section class="content-section">
    <h2>Tipps für das perfekte Wichtelgeschenk</h2>
    
    <div class="highlight-box">
        <h3>💡 Das 3-Fragen-Prinzip</h3>
        <p>Bevor du ein Geschenk kaufst, stelle dir diese drei Fragen:</p>
        <ul>
            <li><strong>Ist es passend?</strong> Berücksichtige Interessen und Vorlieben der Person</li>
            <li><strong>Ist es kreativ?</strong> Überrasche mit originellen Ideen statt Standardgeschenken</li>
            <li><strong>Ist es im Budget?</strong> Halte dich an die vereinbarte Preisobergrenze</li>
        </ul>
    </div>
    
    <h3>Geschenke nach Budget</h3>
    <ul>
        <li><strong>10-15 CHF:</strong> Socken, Schokolade, Kerzen, kleine Pflanzen, Notizbücher</li>
        <li><strong>15-20 CHF:</strong> Thermotasse, Tee-Set, Handcreme, Gewürze, Kartenspiele</li>
        <li><strong>20-30 CHF:</strong> Powerbank, 3D-Puzzle, Badebomben-Set, Premium-Kaffee, Bücher</li>
    </ul>
    
    <h3>DIY-Wichtelgeschenke</h3>
    <p>Selbstgemachtes kommt immer gut an und ist persönlich:</p>
    <ul>
        <li>Selbstgebackene Cookies oder Brownie-Mischung im Glas</li>
        <li>Handgemachte Seife oder Badezusätze</li>
        <li>Selbstgestaltete Tasse mit persönlichem Design</li>
        <li>Foto-Kalender mit lustigen Gruppenbildern</li>
        <li>Handgemachte Kerzen mit individuellen Düften</li>
    </ul>
</section>

<section class="content-section">
    <h2>Wichtelgeschenke für verschiedene Gruppen</h2>
    
    <h3>Für Arbeitskollegen</h3>
    <p>Im Büro sind praktische und neutrale Geschenke ideal:</p>
    <ul>
        <li>Schreibtisch-Organizer oder Büro-Gadgets</li>
        <li>Hochwertige Stifte oder Notizbücher</li>
        <li>Kaffee, Tee oder Snacks</li>
        <li>Kleine Pflanzen für den Schreibtisch</li>
        <li>Motivierende Sprüche-Poster</li>
    </ul>
    
    <h3>Für Freunde</h3>
    <p>Bei Freunden darf es persönlicher und lustiger sein:</p>
    <ul>
        <li>Insider-Jokes als Geschenk verpackt</li>
        <li>Erlebnis-Gutscheine (Kino, Essen, Events)</li>
        <li>Personalisierte Gegenstände</li>
        <li>Party-Spiele oder Gesellschaftsspiele</li>
        <li>Witzige Accessoires oder Gadgets</li>
    </ul>
    
    <h3>Für Familienmitglieder</h3>
    <p>In der Familie zählt oft das Persönliche:</p>
    <ul>
        <li>Fotogeschenke (Kalender, Bilderrahmen)</li>
        <li>Regionale Spezialitäten oder Delikatessen</li>
        <li>Hobby-bezogene Geschenke</li>
        <li>Gemeinsame Zeit (Gutscheine für Aktivitäten)</li>
        <li>Handgemachte Geschenke</li>
    </ul>
</section>
HTML
        ,
        'fr' => <<<'HTML'
<section class="content-section">
    <h2>Conseils pour le cadeau Secret Santa parfait</h2>
    
    <div class="highlight-box">
        <h3>💡 La règle des 3 questions</h3>
        <p>Avant d’acheter un cadeau, pose-toi ces trois questions :</p>
        <ul>
            <li><strong>Est-ce que ça convient ?</strong> Tiens compte des goûts et des centres d’intérêt de la personne</li>
            <li><strong>Est-ce que c’est créatif ?</strong> Surprends avec une idée originale plutôt qu’un cadeau standard</li>
            <li><strong>Est-ce que c’est dans le budget ?</strong> Respecte le plafond de prix convenu</li>
        </ul>
    </div>
    
    <h3>Cadeaux selon le budget</h3>
    <ul>
        <li><strong>10 à 15 CHF :</strong> chaussettes, chocolat, bougies, petites plantes, carnets</li>
        <li><strong>15 à 20 CHF :</strong> mug isotherme, coffret de thés, crème pour les mains, épices, jeux de cartes</li>
        <li><strong>20 à 30 CHF :</strong> batterie externe, puzzle 3D, coffret de boules de bain, café premium, livres</li>
    </ul>
    
    <h3>Cadeaux Secret Santa faits maison</h3>
    <p>Le fait maison plaît toujours, et c’est personnel :</p>
    <ul>
        <li>Des cookies faits maison, ou un mélange à brownie dans un bocal</li>
        <li>Du savon fait main ou des sels de bain</li>
        <li>Une tasse décorée avec un motif personnel</li>
        <li>Un calendrier photo avec des photos de groupe drôles</li>
        <li>Des bougies faites main, avec des parfums choisis</li>
    </ul>
</section>

<section class="content-section">
    <h2>Des cadeaux Secret Santa selon le groupe</h2>
    
    <h3>Pour les collègues</h3>
    <p>Au bureau, les cadeaux pratiques et neutres sont les plus sûrs :</p>
    <ul>
        <li>Un organiseur de bureau ou des gadgets de bureau</li>
        <li>De beaux stylos ou des carnets</li>
        <li>Du café, du thé ou des en-cas</li>
        <li>De petites plantes pour le bureau</li>
        <li>Des affiches avec une phrase motivante</li>
    </ul>
    
    <h3>Pour les amis</h3>
    <p>Entre amis, ça peut être plus personnel et plus drôle :</p>
    <ul>
        <li>Une blague que seuls les amis comprennent, emballée comme un cadeau</li>
        <li>Des bons pour une sortie (cinéma, restaurant, événement)</li>
        <li>Des objets personnalisés</li>
        <li>Des jeux de soirée ou des jeux de société</li>
        <li>Des accessoires ou des gadgets amusants</li>
    </ul>
    
    <h3>Pour la famille</h3>
    <p>En famille, c’est souvent le côté personnel qui compte :</p>
    <ul>
        <li>Des cadeaux photo (calendrier, cadre)</li>
        <li>Des spécialités régionales ou des douceurs</li>
        <li>Des cadeaux liés à un hobby</li>
        <li>Du temps ensemble (bons pour une activité)</li>
        <li>Des cadeaux faits main</li>
    </ul>
</section>
HTML
        ,
        'en' => <<<'HTML'
<section class="content-section">
    <h2>Tips for the perfect Secret Santa gift</h2>
    
    <div class="highlight-box">
        <h3>💡 The 3-question rule</h3>
        <p>Before you buy a gift, ask yourself these three questions:</p>
        <ul>
            <li><strong>Does it fit?</strong> Think about the person’s interests and preferences</li>
            <li><strong>Is it creative?</strong> Surprise them with an original idea rather than a standard gift</li>
            <li><strong>Is it within budget?</strong> Stick to the agreed price cap</li>
        </ul>
    </div>
    
    <h3>Gifts by budget</h3>
    <ul>
        <li><strong>10–15 CHF:</strong> socks, chocolate, candles, small plants, notebooks</li>
        <li><strong>15–20 CHF:</strong> travel mug, tea set, hand cream, spices, card games</li>
        <li><strong>20–30 CHF:</strong> power bank, 3D puzzle, bath bomb set, premium coffee, books</li>
    </ul>
    
    <h3>DIY Secret Santa gifts</h3>
    <p>Something homemade always goes down well, and it is personal:</p>
    <ul>
        <li>Homemade cookies, or a brownie mix in a jar</li>
        <li>Handmade soap or bath treats</li>
        <li>A mug you decorated with a personal design</li>
        <li>A photo calendar with funny group pictures</li>
        <li>Handmade candles with scents you chose</li>
    </ul>
</section>

<section class="content-section">
    <h2>Secret Santa gifts for different groups</h2>
    
    <h3>For colleagues</h3>
    <p>In the office, practical and neutral gifts are the safest choice:</p>
    <ul>
        <li>A desk organiser or office gadgets</li>
        <li>Good pens or notebooks</li>
        <li>Coffee, tea or snacks</li>
        <li>Small plants for the desk</li>
        <li>Posters with a motivating line</li>
    </ul>
    
    <h3>For friends</h3>
    <p>With friends, it can be more personal and funnier:</p>
    <ul>
        <li>An in-joke wrapped up as a gift</li>
        <li>Experience vouchers (cinema, a meal, an event)</li>
        <li>Personalised items</li>
        <li>Party games or board games</li>
        <li>Funny accessories or gadgets</li>
    </ul>
    
    <h3>For family</h3>
    <p>In the family, the personal touch often matters most:</p>
    <ul>
        <li>Photo gifts (a calendar, a frame)</li>
        <li>Regional specialities or treats</li>
        <li>Gifts related to a hobby</li>
        <li>Time together (vouchers for an activity)</li>
        <li>Handmade gifts</li>
    </ul>
</section>
HTML
        ,
    );
    return '<div class="ideas-kept">' . $blocks[$lang] . '</div>';
}
