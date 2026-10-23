<script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "FAQPage",
        "mainEntity": [
            {
                "@type": "Question",
                "name": "C’est quoi, Wichtlä.ch ?",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": <?php echo premium_json_text('Wichtlä.ch est un outil en ligne gratuit pour organiser un Secret Santa. Tu peux créer des groupes, ajouter des participants, tirer les noms automatiquement et de façon équitable, envoyer les attributions par e-mail et définir des exclusions. Le tout sans compte et sans application : directement dans le navigateur.', 'premium.extra.faq_what'); ?>
                }
            },
            {
                "@type": "Question",
                "name": "Wichtlä.ch est-il payant ?",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": <?php echo premium_json_text('Non, Wichtlä.ch est 100 % gratuit. Il n’y a pas de frais cachés, pas de comptes premium et pas de limitations.', 'premium.extra.faq_json'); ?>
                }
            },
            {
                "@type": "Question",
                "name": "Ai-je besoin d’un compte ?",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "Non. Aucun compte n’est nécessaire. Tu crées simplement un groupe, tu ajoutes des participants et tu reçois un lien admin par e-mail."
                }
            },
            {
                "@type": "Question",
                "name": "Combien de personnes peuvent participer ?",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "Recommandé : au moins 3 personnes ; techniquement possible à partir de 2. Il n’y a pratiquement pas de limite maximale : 5, 20 ou 100 participants, le système convient à toutes les tailles de groupe."
                }
            },
            {
                "@type": "Question",
                "name": "Comment fonctionne le tirage ?",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "L’algorithme veille à ce que personne ne tire son propre nom, que chaque personne offre un cadeau à exactement une autre personne, que chaque personne reçoive exactement un cadeau et que les exclusions définies soient respectées. Qui a laissé une adresse reçoit l’attribution par e-mail. Sans e-mail, tu la vois via le lien personnel. On ne montre que la personne à qui tu offres un cadeau."
                }
            },
            {
                "@type": "Question",
                "name": "Que sont les exclusions et à quoi servent-elles ?",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "Les exclusions empêchent certaines personnes de tomber l’un sur l’autre. C’est utile pour les couples, les amis proches, les frères et sœurs dans un Secret Santa en famille, ou les responsables et les collaborateurs dans un Secret Santa en entreprise."
                }
            },
            {
                "@type": "Question",
                "name": "Puis-je ajouter des participants plus tard ?",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "Oui. En tant qu’admin, tu peux à tout moment ajouter de nouveaux participants via ton lien admin, compléter ou modifier des listes de souhaits et définir d’autres exclusions. Une fois les noms tirés, le tirage doit être refait."
                }
            },
            {
                "@type": "Question",
                "name": "Puis-je refaire le tirage ?",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "Oui. En tant qu’admin, tu peux réinitialiser le tirage et le relancer. C’est utile si tu as oublié des exclusions, si de nouvelles personnes ont rejoint le groupe ou si une erreur s’est produite."
                }
            },
            {
                "@type": "Question",
                "name": "Que se passe-t-il si quelqu’un perd son attribution ?",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "Il n’y a pas de bouton sur l’accueil pour récupérer l’attribution. Chaque personne a un lien personnel de participant, dans l’e-mail de confirmation et chez l’admin, prêt à être copié. Ce lien montre la personne à qui offrir un cadeau. Après le tirage, l’admin peut aussi renvoyer l’attribution par e-mail."
                }
            },
            {
                "@type": "Question",
                "name": "Puis-je créer plusieurs groupes Secret Santa ?",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "Oui, absolument. Tu peux créer autant de groupes que tu veux, par exemple un pour la famille, un pour les amis et un pour le travail. Chaque groupe a son propre lien admin."
                }
            },
            {
                "@type": "Question",
                "name": "Mes données sont-elles en sécurité ?",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "Oui. J’utilise le chiffrement SSL pour toutes les connexions, le serveur est en Suisse, je respecte la loi suisse sur la protection des données (LPD) et les groupes sont supprimés automatiquement à l’échéance."
                }
            },
            {
                "@type": "Question",
                "name": "Quelles données sont enregistrées ?",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "Je n’enregistre que le nécessaire : les noms des participants, les adresses e-mail (pour les notifications), les listes de souhaits (facultatif), les exclusions et les résultats du tirage. Ne sont pas enregistrés : numéros de téléphone, adresses postales, données de paiement ou autres informations personnelles."
                }
            },
            {
                "@type": "Question",
                "name": "Combien de temps mes données sont-elles conservées ?",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "Les groupes et toutes les données associées sont supprimés automatiquement trois mois après l’événement. Sans date d’événement, le délai part de la création du groupe. En tant qu’admin, tu peux aussi supprimer ton groupe à tout moment."
                }
            },
            {
                "@type": "Question",
                "name": "Quelqu’un d’autre peut-il voir mon attribution ?",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "Au début, chaque personne ne voit que la personne à qui elle offre un cadeau, et seulement via son lien personnel. Dans la gestion, il n’y a pas de liste de toutes les attributions. Si l’admin envoie la révélation, tous les participants avec une adresse e-mail reçoivent la liste complète de qui était le Père Noël secret de qui."
                }
            },
            {
                "@type": "Question",
                "name": "Je n’ai pas reçu d’e-mail. Que faire ?",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "Vérifie le dossier spam et l’adresse e-mail. L’admin peut, dans la zone Participants, copier le lien personnel ou renvoyer l’attribution par e-mail. Il n’existe pas de fonction sur l’accueil pour renvoyer l’attribution."
                }
            },
            {
                "@type": "Question",
                "name": "J’ai perdu mon lien admin !",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "Sur la page Renvoyer le lien admin, indique l’adresse e-mail utilisée lors de la création du groupe. Si un groupe existe pour cette adresse, le lien est renvoyé. Un code de sécurité et une limite du nombre d’essais protègent le formulaire. La réponse ne révèle pas si l’adresse est enregistrée."
                }
            },
            {
                "@type": "Question",
                "name": "C’est quoi, l’e-mail de révélation ?",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "Après le tirage, l’admin peut envoyer la révélation à tout le monde. Chaque personne avec une adresse e-mail reçoit alors la liste de qui était le Père Noël secret de qui. Jusque-là, chacun ne voit que la personne à qui il offre un cadeau. Un second envoi demande une confirmation explicite."
                }
            },
            {
                "@type": "Question",
                "name": "Le tirage ne fonctionne pas. Pourquoi ?",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "Causes possibles : trop d’exclusions (mathématiquement impossible), trop peu de participants (recommandé : au moins 3 personnes ; techniquement possible à partir de 2) ou un problème technique (vide le cache du navigateur). Réduis les exclusions ou ajoute des participants."
                }
            },
            {
                "@type": "Question",
                "name": "Puis-je supprimer un groupe ?",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "Oui. En tant qu’admin, tu trouves dans l’espace admin une option Supprimer le groupe. Toutes les données sont effacées immédiatement et sans retour possible."
                }
            },
            {
                "@type": "Question",
                "name": "Wichtlä.ch fonctionne-t-il sur smartphone ?",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "Oui, entièrement. Wichtlä.ch est complètement adaptatif et fonctionne sur les smartphones (iOS et Android), les ordinateurs et les tablettes. Pas besoin d’application : ouvre simplement le navigateur."
                }
            },
            {
                "@type": "Question",
                "name": "Quel est le meilleur moment pour commencer ?",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "3 à 4 semaines avant l’événement, c’est l’idéal. Assez de temps pour trouver les cadeaux, les participants peuvent réfléchir tranquillement, et il reste de la marge pour corriger un problème. Pour un Secret Santa spontané, 1 à 2 semaines d’avance suffisent aussi."
                }
            },
            {
                "@type": "Question",
                "name": "Quel budget est recommandé ?",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "Ça dépend de ton groupe : 10 à 15 CHF pour les amis, les étudiants et les grands groupes ; 15 à 25 CHF comme standard pour la plupart des Secret Santa ; 25 à 50 CHF pour les entreprises et les cercles plus petits. Choisis un budget que tout le monde peut se permettre."
                }
            },
            {
                "@type": "Question",
                "name": "Le Secret Santa doit-il rester anonyme ?",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "C’est une question de goût. Anonyme, c’est plus palpitant et il y a plus de devinettes. Ouvert, c’est plus personnel et plus simple pour choisir le cadeau. Beaucoup de groupes font ainsi : les cadeaux sont emballés sans nom, puis la personne qui a offert se dévoile après l’ouverture."
                }
            },
            {
                "@type": "Question",
                "name": "Que faire si quelqu’un ne veut pas participer ?",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "Un Secret Santa doit toujours rester volontaire. Personne ne devrait se sentir obligé. Pour un Secret Santa en entreprise : fais un sondage avant, et n’organise l’événement que si assez de personnes veulent participer."
                }
            },
            {
                "@type": "Question",
                "name": "Puis-je faire un Secret Santa par WhatsApp ?",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "Oui, avec le lien d’invitation. Dans l’espace admin, Partager par WhatsApp envoie ce lien dans la discussion. L’inscription et le tirage se font sur wichtlä.ch, pas dans un bot WhatsApp. Qui laisse une adresse reçoit le résultat par e-mail. Sans e-mail, le résultat est sur le lien personnel, que l’admin peut copier et envoyer, par exemple sur WhatsApp."
                }
            },
            {
                "@type": "Question",
                "name": "Y a-t-il un générateur de Secret Santa ?",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "Le tirage est un générateur Secret Santa pour les noms. Il répartit les noms au hasard, personne ne tire son propre nom, et les exclusions comptent. Ce n’est ni un générateur de nombres général ni une roue publique."
                }
            },
            {
                "@type": "Question",
                "name": "Puis-je tirer au sort sans e-mail ?",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "Les participants peuvent s’inscrire sans e-mail. Le tirage fonctionne quand même. Le résultat n’arrive alors pas par e-mail, mais via le lien personnel que l’admin copie et transmet. Pour le groupe, l’admin a besoin d’une adresse, parce que le lien admin y est envoyé. La révélation et le rappel ne partent que vers les adresses enregistrées."
                }
            },
            {
                "@type": "Question",
                "name": "Faut-il une application Secret Santa ?",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "Non. wichtlä.ch fonctionne dans le navigateur, sur le téléphone et sur l’ordinateur. Il n’y a rien à installer."
                }
            },
            {
                "@type": "Question",
                "name": "Comment créer un groupe Secret Santa ?",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "Sous Créer un groupe, tu indiques le nom, ton e-mail et, si tu veux, un budget et une date. Tu reçois un lien admin et un lien d’invitation. Tu partages le lien d’invitation par e-mail, WhatsApp ou comme le groupe le préfère. Ensuite tu lances le tirage dans l’espace admin."
                }
            }
        ]
    }
    </script>
<div class="content-page">
        <div class="breadcrumb">
            <a href="<?php echo h(i18n_href('index')); ?>">Accueil</a> / FAQ
        </div>
        
        <header class="page-header">
            <h1 class="page-title">Questions fréquentes</h1>
            <p class="page-subtitle">Toutes les réponses sur Wichtlä.ch, les fonctions, la protection des données et les conseils pour un Secret Santa réussi</p>
        </header>
        
        <article>
            <!-- Questions générales -->
            <section class="faq-section">
                <h2>🎁 Questions générales</h2>
                
                                <div class="faq-item">
                    <h3>
                        <button class="faq-question" aria-expanded="false" aria-controls="faq-answer-1" id="faq-question-1" onclick="toggleFaq(this)">
                            <span class="faq-title">C’est quoi, Wichtlä.ch ?</span>
                            <span class="faq-icon" aria-hidden="true"></span>
                        </button>
                    </h3>
                    <div id="faq-answer-1" class="faq-answer" role="region" aria-labelledby="faq-question-1">
<?php if (function_exists('premium_feature_enabled') && premium_feature_enabled()): ?>
<p><?php echo h(t('premium.extra.faq_what')); ?></p>
<?php else: ?>
<p>
                            Wichtlä.ch est un <strong>outil en ligne gratuit</strong> pour organiser un Secret Santa. Tu peux tout simplement :
                        </p>
<?php endif; ?>
                        <ul>
                            <li>créer des groupes Secret Santa</li>
                            <li>ajouter des participants</li>
                            <li>tirer les noms au sort automatiquement et de façon équitable</li>
                            <li>envoyer les attributions par e-mail</li>
                            <li>définir des exclusions (par exemple, les couples ne tombent pas l’un sur l’autre)</li>
                        </ul>
                        <p>
                            Le tout sans compte et sans application : directement dans le navigateur !
                        </p>
                    </div>
                </div>
                
                                <div class="faq-item">
                    <h3>
                        <button class="faq-question" aria-expanded="false" aria-controls="faq-answer-2" id="faq-question-2" onclick="toggleFaq(this)">
                            <span class="faq-title">Wichtlä.ch est-il payant ?</span>
                            <span class="faq-icon" aria-hidden="true"></span>
                        </button>
                    </h3>
                    <div id="faq-answer-2" class="faq-answer" role="region" aria-labelledby="faq-question-2">
<?php if (function_exists('premium_feature_enabled') && premium_feature_enabled()): ?>
<p><strong><?php echo h(t('premium.extra.faq_lead')); ?></strong></p>
<p><?php echo h(t('premium.extra.faq_more')); ?> <?php echo premium_extra_link_html(); ?>.</p>
<?php else: ?>
<p>
                            <strong>Non, Wichtlä.ch est 100 % gratuit !</strong>
                        </p>
                        <p>
                            Il n’y a pas de frais cachés, pas de comptes premium et pas de limitations.
                        </p>
<?php endif; ?>
                    </div>
                </div>
                
                                <div class="faq-item">
                    <h3>
                        <button class="faq-question" aria-expanded="false" aria-controls="faq-answer-3" id="faq-question-3" onclick="toggleFaq(this)">
                            <span class="faq-title">Ai-je besoin d’un compte ?</span>
                            <span class="faq-icon" aria-hidden="true"></span>
                        </button>
                    </h3>
                    <div id="faq-answer-3" class="faq-answer" role="region" aria-labelledby="faq-question-3">
<p>
                            <strong>Non !</strong> Aucun compte n’est nécessaire. Tu crées simplement un groupe, tu ajoutes des participants et tu reçois un lien admin par e-mail. C’est tout !
                        </p>
                        <p>
                            C’est ce qui rend Wichtlä.ch particulièrement simple et rapide à utiliser.
                        </p>
                    </div>
                </div>
                
                                <div class="faq-item">
                    <h3>
                        <button class="faq-question" aria-expanded="false" aria-controls="faq-answer-4" id="faq-question-4" onclick="toggleFaq(this)">
                            <span class="faq-title">Combien de personnes peuvent participer ?</span>
                            <span class="faq-icon" aria-hidden="true"></span>
                        </button>
                    </h3>
                    <div id="faq-answer-4" class="faq-answer" role="region" aria-labelledby="faq-question-4">
<p>
                            <strong>Recommandé : au moins 3 personnes ; techniquement possible à partir de 2.</strong> Il n’y a pratiquement pas de limite maximale : 5, 20 ou 100 participants, le système convient à toutes les tailles de groupe.
                        </p>
                        <p>
                            Les groupes de 5 à 30 personnes sont particulièrement appréciés, par exemple en famille, entre amis ou en équipe.
                        </p>
                    </div>
                </div>
            </section>
            
            <section class="faq-section" id="wichteln-whatsapp">
                <h2>Lien, WhatsApp et sans e-mail</h2>

                <div class="faq-item" id="wichteln-per-whatsapp">
                    <h3>
                        <button class="faq-question" aria-expanded="false" aria-controls="faq-answer-25" id="faq-question-25" onclick="toggleFaq(this)">
                            <span class="faq-title">Puis-je faire un Secret Santa par WhatsApp ?</span>
                            <span class="faq-icon" aria-hidden="true"></span>
                        </button>
                    </h3>
                    <div id="faq-answer-25" class="faq-answer" role="region" aria-labelledby="faq-question-25">
                        <p>Oui, avec le lien d’invitation. Dans l’espace admin, <strong>Partager par WhatsApp</strong> envoie ce lien dans la discussion. L’inscription et le tirage se font sur wichtlä.ch, pas dans un bot WhatsApp.</p>
                        <p>Qui laisse une adresse reçoit le résultat par e-mail. Sans e-mail, le résultat est sur le lien personnel, que l’admin peut copier et envoyer, par exemple sur WhatsApp.</p>
                    </div>
                </div>

                <div class="faq-item" id="wichtel-generator">
                    <h3>
                        <button class="faq-question" aria-expanded="false" aria-controls="faq-answer-26" id="faq-question-26" onclick="toggleFaq(this)">
                            <span class="faq-title">Y a-t-il un générateur de Secret Santa ?</span>
                            <span class="faq-icon" aria-hidden="true"></span>
                        </button>
                    </h3>
                    <div id="faq-answer-26" class="faq-answer" role="region" aria-labelledby="faq-question-26">
                        <p>Le tirage est un générateur Secret Santa pour les noms. Il répartit les noms au hasard, personne ne tire son propre nom, et les exclusions comptent. Ce n’est ni un générateur de nombres général ni une roue publique.</p>
                    </div>
                </div>

                <div class="faq-item" id="ohne-e-mail">
                    <h3>
                        <button class="faq-question" aria-expanded="false" aria-controls="faq-answer-27" id="faq-question-27" onclick="toggleFaq(this)">
                            <span class="faq-title">Puis-je tirer au sort sans e-mail ?</span>
                            <span class="faq-icon" aria-hidden="true"></span>
                        </button>
                    </h3>
                    <div id="faq-answer-27" class="faq-answer" role="region" aria-labelledby="faq-question-27">
                        <p>Les participants peuvent s’inscrire sans e-mail. Le tirage fonctionne quand même. Le résultat n’arrive alors pas par e-mail, mais via le lien personnel que l’admin copie et transmet.</p>
                        <p>Pour le groupe, l’admin a besoin d’une adresse, parce que le lien admin y est envoyé. La révélation et le rappel ne partent que vers les adresses enregistrées.</p>
                    </div>
                </div>

                <div class="faq-item" id="wichtel-app">
                    <h3>
                        <button class="faq-question" aria-expanded="false" aria-controls="faq-answer-28" id="faq-question-28" onclick="toggleFaq(this)">
                            <span class="faq-title">Faut-il une application Secret Santa ?</span>
                            <span class="faq-icon" aria-hidden="true"></span>
                        </button>
                    </h3>
                    <div id="faq-answer-28" class="faq-answer" role="region" aria-labelledby="faq-question-28">
                        <p>Non. wichtlä.ch fonctionne dans le navigateur, sur le téléphone et sur l’ordinateur. Il n’y a rien à installer.</p>
                    </div>
                </div>

                <div class="faq-item" id="wichtelgruppe-erstellen">
                    <h3>
                        <button class="faq-question" aria-expanded="false" aria-controls="faq-answer-29" id="faq-question-29" onclick="toggleFaq(this)">
                            <span class="faq-title">Comment créer un groupe Secret Santa ?</span>
                            <span class="faq-icon" aria-hidden="true"></span>
                        </button>
                    </h3>
                    <div id="faq-answer-29" class="faq-answer" role="region" aria-labelledby="faq-question-29">
                        <p>Sous <a href="<?php echo h(i18n_href('create_group')); ?>">Créer un groupe</a>, tu indiques le nom, ton e-mail et, si tu veux, un budget et une date. Tu reçois un lien admin et un lien d’invitation. Tu partages ce lien par e-mail, WhatsApp ou comme le groupe le préfère. Ensuite tu lances le tirage dans l’espace admin.</p>
                    </div>
                </div>
            </section>

            <!-- Fonctions -->
            <section class="faq-section">
                <h2>⚙️ Fonctions et utilisation</h2>
                
                                <div class="faq-item">
                    <h3>
                        <button class="faq-question" aria-expanded="false" aria-controls="faq-answer-5" id="faq-question-5" onclick="toggleFaq(this)">
                            <span class="faq-title">Comment fonctionne le tirage ?</span>
                            <span class="faq-icon" aria-hidden="true"></span>
                        </button>
                    </h3>
                    <div id="faq-answer-5" class="faq-answer" role="region" aria-labelledby="faq-question-5">
<p>
                            L’algorithme veille à ce que :
                        </p>
                        <ul>
                            <li>✅ personne ne tire son propre nom</li>
                            <li>✅ chaque personne offre un cadeau à exactement une autre personne</li>
                            <li>✅ chaque personne reçoive exactement un cadeau</li>
                            <li>✅ les exclusions définies soient respectées</li>
                        </ul>
                        <p>
                            Qui a laissé une adresse reçoit l’attribution par e-mail. Sans e-mail, tu la vois via le lien personnel. On ne montre que la personne à qui tu offres un cadeau.
                        </p>
                    </div>
                </div>
                
                                <div class="faq-item">
                    <h3>
                        <button class="faq-question" aria-expanded="false" aria-controls="faq-answer-6" id="faq-question-6" onclick="toggleFaq(this)">
                            <span class="faq-title">Que sont les exclusions et à quoi servent-elles ?</span>
                            <span class="faq-icon" aria-hidden="true"></span>
                        </button>
                    </h3>
                    <div id="faq-answer-6" class="faq-answer" role="region" aria-labelledby="faq-question-6">
<p>
                            Les <strong>exclusions</strong> empêchent certaines personnes de tomber l’un sur l’autre. C’est utile pour :
                        </p>
                        <ul>
                            <li>les couples ou les conjoints (ils connaissent souvent déjà les envies)</li>
                            <li>les amis proches, qui s’offrent déjà des cadeaux</li>
                            <li>les frères et sœurs dans un Secret Santa en famille</li>
                            <li>les responsables et les collaborateurs dans un Secret Santa en entreprise</li>
                        </ul>
                        <p>
                            Exemple : tu indiques « Anna ↔ Peter ». Anna ne peut alors pas tirer Peter, et Peter ne peut pas tirer Anna.
                        </p>
                    </div>
                </div>
                
                                <div class="faq-item">
                    <h3>
                        <button class="faq-question" aria-expanded="false" aria-controls="faq-answer-7" id="faq-question-7" onclick="toggleFaq(this)">
                            <span class="faq-title">Puis-je ajouter des participants plus tard ?</span>
                            <span class="faq-icon" aria-hidden="true"></span>
                        </button>
                    </h3>
                    <div id="faq-answer-7" class="faq-answer" role="region" aria-labelledby="faq-question-7">
<p>
                            <strong>Oui !</strong> En tant qu’admin, tu peux à tout moment, via ton lien admin :
                        </p>
                        <ul>
                            <li>ajouter de nouveaux participants</li>
                            <li>compléter ou modifier des listes de souhaits</li>
                            <li>définir d’autres exclusions</li>
                        </ul>
                        <p>
                            <strong>Important :</strong> une fois les noms tirés, tu ne peux plus ajouter de participants. Il faudrait refaire le tirage.
                        </p>
                    </div>
                </div>
                
                                <div class="faq-item">
                    <h3>
                        <button class="faq-question" aria-expanded="false" aria-controls="faq-answer-8" id="faq-question-8" onclick="toggleFaq(this)">
                            <span class="faq-title">Puis-je refaire le tirage ?</span>
                            <span class="faq-icon" aria-hidden="true"></span>
                        </button>
                    </h3>
                    <div id="faq-answer-8" class="faq-answer" role="region" aria-labelledby="faq-question-8">
<p>
                            <strong>Oui</strong>, en tant qu’admin tu peux réinitialiser le tirage et le relancer. C’est utile si :
                        </p>
                        <ul>
                            <li>tu as oublié des exclusions</li>
                            <li>de nouvelles personnes ont rejoint le groupe</li>
                            <li>une erreur s’est produite</li>
                        </ul>
                        <p>
                            <strong>Attention :</strong> un nouveau tirage efface toutes les attributions précédentes !
                        </p>
                    </div>
                </div>
                
                                <div class="faq-item">
                    <h3>
                        <button class="faq-question" aria-expanded="false" aria-controls="faq-answer-9" id="faq-question-9" onclick="toggleFaq(this)">
                            <span class="faq-title">Que se passe-t-il si quelqu’un perd son attribution ?</span>
                            <span class="faq-icon" aria-hidden="true"></span>
                        </button>
                    </h3>
                    <div id="faq-answer-9" class="faq-answer" role="region" aria-labelledby="faq-question-9">
<p>
                            Il n’y a pas de bouton « Récupérer l’attribution » sur l’accueil. Chaque personne a un lien personnel de participant :
                        </p>
                        <ul>
                            <li>dans l’e-mail de confirmation après l’inscription</li>
                            <li>auprès de l’admin, qui peut le copier sous « Participants »</li>
                        </ul>
                        <p>
                            Avec ce lien, tu vois la personne à qui tu offres un cadeau et tu peux modifier ta liste de souhaits. Après le tirage, l’admin peut aussi renvoyer l’attribution par e-mail.
                        </p>
                    </div>
                </div>
                
                                <div class="faq-item">
                    <h3>
                        <button class="faq-question" aria-expanded="false" aria-controls="faq-answer-10" id="faq-question-10" onclick="toggleFaq(this)">
                            <span class="faq-title">Puis-je créer plusieurs groupes Secret Santa ?</span>
                            <span class="faq-icon" aria-hidden="true"></span>
                        </button>
                    </h3>
                    <div id="faq-answer-10" class="faq-answer" role="region" aria-labelledby="faq-question-10">
<p>
                            <strong>Oui, absolument !</strong> Tu peux créer autant de groupes que tu veux, par exemple :
                        </p>
                        <ul>
                            <li>un pour la famille</li>
                            <li>un pour les amis</li>
                            <li>un pour le travail</li>
                        </ul>
                        <p>
                            Chaque groupe a son propre lien admin, que tu reçois par e-mail.
                        </p>
                    </div>
                </div>
            </section>
            
            <!-- Protection des données -->
            <section class="faq-section">
                <h2>🔒 Protection des données et sécurité</h2>
                
                                <div class="faq-item">
                    <h3>
                        <button class="faq-question" aria-expanded="false" aria-controls="faq-answer-11" id="faq-question-11" onclick="toggleFaq(this)">
                            <span class="faq-title">Mes données sont-elles en sécurité ?</span>
                            <span class="faq-icon" aria-hidden="true"></span>
                        </button>
                    </h3>
                    <div id="faq-answer-11" class="faq-answer" role="region" aria-labelledby="faq-question-11">
<p>
                            <strong>Oui !</strong> Je prends la protection des données très au sérieux :
                        </p>
                        <ul>
                            <li>🔐 chiffrement SSL pour toutes les connexions</li>
                            <li>🇨🇭 serveur situé en Suisse</li>
                            <li>📜 respect de la loi suisse sur la protection des données (LPD)</li>
                            <li>🗑️ suppression automatique à l’échéance du groupe</li>
                            <li>🚫 aucune transmission à des tiers</li>
                        </ul>
                        <p>
                            Plus de détails dans ma <a href="<?php echo h(i18n_href('datenschutz')); ?>">politique de confidentialité</a>.
                        </p>
                    </div>
                </div>
                
                                <div class="faq-item">
                    <h3>
                        <button class="faq-question" aria-expanded="false" aria-controls="faq-answer-12" id="faq-question-12" onclick="toggleFaq(this)">
                            <span class="faq-title">Quelles données sont enregistrées ?</span>
                            <span class="faq-icon" aria-hidden="true"></span>
                        </button>
                    </h3>
                    <div id="faq-answer-12" class="faq-answer" role="region" aria-labelledby="faq-question-12">
<p>
                            Je n’enregistre que le nécessaire :
                        </p>
                        <ul>
                            <li>les noms des participants</li>
                            <li>les adresses e-mail (pour les notifications)</li>
                            <li>les listes de souhaits (facultatif, si elles sont indiquées)</li>
                            <li>les exclusions</li>
                            <li>les résultats du tirage</li>
                        </ul>
                        <p>
                            <strong>Ne sont pas enregistrés :</strong> numéros de téléphone, adresses postales, données de paiement ou autres informations personnelles.
                        </p>
                    </div>
                </div>
                
                                <div class="faq-item">
                    <h3>
                        <button class="faq-question" aria-expanded="false" aria-controls="faq-answer-13" id="faq-question-13" onclick="toggleFaq(this)">
                            <span class="faq-title">Combien de temps mes données sont-elles conservées ?</span>
                            <span class="faq-icon" aria-hidden="true"></span>
                        </button>
                    </h3>
                    <div id="faq-answer-13" class="faq-answer" role="region" aria-labelledby="faq-question-13">
<p>
                            Les groupes et toutes les données associées sont <strong>supprimés automatiquement trois mois après l’événement</strong>. Sans date d’événement, le délai part de la création du groupe.
                        </p>
                        <p>
                            En tant qu’admin, tu peux aussi supprimer ton groupe à tout moment, à la main. Toutes les données sont alors retirées immédiatement.
                        </p>
                    </div>
                </div>
                
                                <div class="faq-item">
                    <h3>
                        <button class="faq-question" aria-expanded="false" aria-controls="faq-answer-14" id="faq-question-14" onclick="toggleFaq(this)">
                            <span class="faq-title">Quelqu’un d’autre peut-il voir mon attribution ?</span>
                            <span class="faq-icon" aria-hidden="true"></span>
                        </button>
                    </h3>
                    <div id="faq-answer-14" class="faq-answer" role="region" aria-labelledby="faq-question-14">
<p>
                            Au début, chaque personne ne voit que la personne à qui elle offre un cadeau, et seulement via son lien personnel. Dans la gestion, il n’y a pas de liste de toutes les attributions.
                        </p>
                        <p>
                            Ça change lorsque l’admin envoie la <strong>révélation</strong>. Tous les participants avec une adresse e-mail reçoivent alors la liste complète de qui était le Père Noël secret de qui. Avant cela, ça reste secret.
                        </p>
                    </div>
                </div>
            </section>
            
            <!-- Problèmes et assistance -->
            <section class="faq-section">
                <h2>🆘 Problèmes et assistance</h2>
                
                                <div class="faq-item">
                    <h3>
                        <button class="faq-question" aria-expanded="false" aria-controls="faq-answer-15" id="faq-question-15" onclick="toggleFaq(this)">
                            <span class="faq-title">Je n’ai pas reçu d’e-mail. Que faire ?</span>
                            <span class="faq-icon" aria-hidden="true"></span>
                        </button>
                    </h3>
                    <div id="faq-answer-15" class="faq-answer" role="region" aria-labelledby="faq-question-15">
<p>
                            Si tu n’as pas reçu d’e-mail, vérifie ceci :
                        </p>
                        <ul>
                            <li>📧 <strong>Dossier spam :</strong> mes e-mails s’y retrouvent parfois</li>
                            <li>✉️ <strong>Adresse e-mail :</strong> une faute de frappe est possible</li>
                            <li>⏱️ <strong>Délai :</strong> les e-mails peuvent prendre 1 à 2 minutes</li>
                            <li>🚫 <strong>Filtres :</strong> les messageries d’entreprise bloquent parfois les expéditeurs externes</li>
                        </ul>
                        <p>
                            <strong>Solution :</strong> demande à l’admin de copier le lien personnel dans la zone « Participants », ou de renvoyer l’attribution par e-mail. Il n’existe pas de fonction « Renvoyer l’attribution » sur l’accueil.
                        </p>
                    </div>
                </div>
                
                                <div class="faq-item">
                    <h3>
                        <button class="faq-question" aria-expanded="false" aria-controls="faq-answer-16" id="faq-question-16" onclick="toggleFaq(this)">
                            <span class="faq-title">J’ai perdu mon lien admin !</span>
                            <span class="faq-icon" aria-hidden="true"></span>
                        </button>
                    </h3>
                    <div id="faq-answer-16" class="faq-answer" role="region" aria-labelledby="faq-question-16">
<p>
                            Tu peux redemander le lien : sur <a href="<?php echo h(i18n_href('admin-link')); ?>">Renvoyer le lien admin</a>, indique l’adresse e-mail utilisée lors de la création du groupe. Si un groupe existe pour cette adresse, je renvoie le lien admin. Un code de sécurité et une limite du nombre d’essais protègent le formulaire.
                        </p>
                        <p>
                            La réponse est toujours formulée de la même façon, pour que personne ne puisse vérifier si une adresse est enregistrée chez moi.
                        </p>
                        <div class="highlight-box">
                            <strong>Astuce :</strong> enregistre le lien admin dans les favoris du navigateur.
                        </div>
                    </div>
                </div>
                
                                <div class="faq-item">
                    <h3>
                        <button class="faq-question" aria-expanded="false" aria-controls="faq-answer-24" id="faq-question-24" onclick="toggleFaq(this)">
                            <span class="faq-title">C’est quoi, l’e-mail de révélation ?</span>
                            <span class="faq-icon" aria-hidden="true"></span>
                        </button>
                    </h3>
                    <div id="faq-answer-24" class="faq-answer" role="region" aria-labelledby="faq-question-24">
<p>
                            Après le tirage, l’admin peut choisir <strong>« Envoyer la révélation à tout le monde »</strong>. Chaque personne avec une adresse e-mail reçoit alors la liste de qui était le Père Noël secret de qui (Père Noël secret → destinataire).
                        </p>
                        <p>
                            Jusque-là, chacun ne voit que la personne à qui il offre un cadeau. Un second envoi n’est possible qu’avec une confirmation explicite, pour que l’e-mail ne parte pas plusieurs fois par erreur.
                        </p>
                    </div>
                </div>

                                <div class="faq-item">
                    <h3>
                        <button class="faq-question" aria-expanded="false" aria-controls="faq-answer-17" id="faq-question-17" onclick="toggleFaq(this)">
                            <span class="faq-title">Le tirage ne fonctionne pas. Pourquoi ?</span>
                            <span class="faq-icon" aria-hidden="true"></span>
                        </button>
                    </h3>
                    <div id="faq-answer-17" class="faq-answer" role="region" aria-labelledby="faq-question-17">
<p>
                            Causes possibles :
                        </p>
                        <ul>
                            <li><strong>Trop d’exclusions :</strong> avec trop d’exclusions, un tirage valable est mathématiquement impossible</li>
                            <li><strong>Trop peu de participants :</strong> recommandé, au moins 3 personnes ; techniquement possible à partir de 2</li>
                            <li><strong>Problème technique :</strong> vide le cache du navigateur et réessaie</li>
                        </ul>
                        <p>
                            <strong>Astuce :</strong> réduis le nombre d’exclusions ou ajoute des participants.
                        </p>
                    </div>
                </div>
                
                                <div class="faq-item">
                    <h3>
                        <button class="faq-question" aria-expanded="false" aria-controls="faq-answer-18" id="faq-question-18" onclick="toggleFaq(this)">
                            <span class="faq-title">Puis-je supprimer un groupe ?</span>
                            <span class="faq-icon" aria-hidden="true"></span>
                        </button>
                    </h3>
                    <div id="faq-answer-18" class="faq-answer" role="region" aria-labelledby="faq-question-18">
<p>
                            <strong>Oui !</strong> En tant qu’admin, tu trouves dans l’espace admin une option « Supprimer le groupe ». Toutes les données sont effacées immédiatement et sans retour possible.
                        </p>
                        <p>
                            <strong>Attention :</strong> cette action ne peut pas être annulée !
                        </p>
                    </div>
                </div>
                
                                <div class="faq-item">
                    <h3>
                        <button class="faq-question" aria-expanded="false" aria-controls="faq-answer-19" id="faq-question-19" onclick="toggleFaq(this)">
                            <span class="faq-title">Wichtlä.ch fonctionne-t-il sur smartphone ?</span>
                            <span class="faq-icon" aria-hidden="true"></span>
                        </button>
                    </h3>
                    <div id="faq-answer-19" class="faq-answer" role="region" aria-labelledby="faq-question-19">
<p>
                            <strong>Oui, entièrement !</strong> Wichtlä.ch est complètement adaptatif et fonctionne sur tous les appareils :
                        </p>
                        <ul>
                            <li>📱 smartphones (iOS et Android)</li>
                            <li>📲 tablettes</li>
                            <li>💻 ordinateurs</li>
                        </ul>
                        <p>
                            Pas besoin d’application : ouvre simplement le navigateur !
                        </p>
                    </div>
                </div>
            </section>
            
            <!-- Conseils -->
            <section class="faq-section">
                <h2>💡 Conseils et bonnes pratiques</h2>
                
                                <div class="faq-item">
                    <h3>
                        <button class="faq-question" aria-expanded="false" aria-controls="faq-answer-20" id="faq-question-20" onclick="toggleFaq(this)">
                            <span class="faq-title">Quel est le meilleur moment pour commencer ?</span>
                            <span class="faq-icon" aria-hidden="true"></span>
                        </button>
                    </h3>
                    <div id="faq-answer-20" class="faq-answer" role="region" aria-labelledby="faq-question-20">
<p>
                            <strong>3 à 4 semaines avant l’événement</strong>, c’est l’idéal :
                        </p>
                        <ul>
                            <li>assez de temps pour trouver les cadeaux</li>
                            <li>les participants peuvent réfléchir tranquillement</li>
                            <li>en cas de problème, il reste de la marge pour corriger</li>
                        </ul>
                        <p>
                            Pour un Secret Santa spontané, 1 à 2 semaines d’avance suffisent aussi.
                        </p>
                    </div>
                </div>
                
                                <div class="faq-item">
                    <h3>
                        <button class="faq-question" aria-expanded="false" aria-controls="faq-answer-21" id="faq-question-21" onclick="toggleFaq(this)">
                            <span class="faq-title">Quel budget est recommandé ?</span>
                            <span class="faq-icon" aria-hidden="true"></span>
                        </button>
                    </h3>
                    <div id="faq-answer-21" class="faq-answer" role="region" aria-labelledby="faq-question-21">
<p>
                            Ça dépend de ton groupe :
                        </p>
                        <ul>
                            <li><strong>10 à 15 CHF :</strong> amis, étudiants, grands groupes</li>
                            <li><strong>15 à 25 CHF :</strong> le standard pour la plupart des Secret Santa</li>
                            <li><strong>25 à 50 CHF :</strong> entreprises, groupes plus petits, cercles plus proches</li>
                        </ul>
                        <p>
                            <strong>Important :</strong> choisis un budget que tout le monde peut se permettre !
                        </p>
                    </div>
                </div>
                
                                <div class="faq-item">
                    <h3>
                        <button class="faq-question" aria-expanded="false" aria-controls="faq-answer-22" id="faq-question-22" onclick="toggleFaq(this)">
                            <span class="faq-title">Le Secret Santa doit-il rester anonyme ?</span>
                            <span class="faq-icon" aria-hidden="true"></span>
                        </button>
                    </h3>
                    <div id="faq-answer-22" class="faq-answer" role="region" aria-labelledby="faq-question-22">
<p>
                            C’est une question de goût :
                        </p>
                        <ul>
                            <li><strong>Anonyme :</strong> plus palpitant, plus de devinettes, la variante classique</li>
                            <li><strong>Ouvert :</strong> plus personnel, plus simple pour choisir le cadeau</li>
                        </ul>
                        <p>
                            Beaucoup de groupes font ainsi : les cadeaux sont emballés sans nom, puis la personne qui a offert se dévoile après l’ouverture. Le meilleur des deux !
                        </p>
                    </div>
                </div>
                
                                <div class="faq-item">
                    <h3>
                        <button class="faq-question" aria-expanded="false" aria-controls="faq-answer-23" id="faq-question-23" onclick="toggleFaq(this)">
                            <span class="faq-title">Que faire si quelqu’un ne veut pas participer ?</span>
                            <span class="faq-icon" aria-hidden="true"></span>
                        </button>
                    </h3>
                    <div id="faq-answer-23" class="faq-answer" role="region" aria-labelledby="faq-question-23">
<p>
                            <strong>Un Secret Santa doit toujours rester volontaire !</strong> Personne ne devrait se sentir obligé.
                        </p>
                        <p>
                            Pour un Secret Santa en entreprise : fais un sondage avant, et n’organise l’événement que si assez de personnes veulent participer. Celles qui ne participent pas ne devraient pas se sentir mises à l’écart.
                        </p>
                    </div>
                </div>
            </section>
            
            <div class="cta-section">
                <h2>Encore des questions ?</h2>
                <p>Écris-moi un e-mail, ou lance simplement ton premier Secret Santa : c’est un jeu d’enfant !</p>
                <a href="<?php echo h(i18n_href('create_group')); ?>" class="cta-button-white">Créer un groupe maintenant →</a>
            </div>
        </article>
    </div>
    
    <script>
        function toggleFaq(button) {
            toggleFAQ(button);
        }
    </script>
    
    <!-- Pied de page simple -->
