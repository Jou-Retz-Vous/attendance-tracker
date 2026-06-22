# Système de pointage pour l'association JRV

## Contexte

L'association organise des réunions physiques régulières. Pour faciliter la gestion des présences, un système numérique simple est nécessaire : chaque participant doit pouvoir signaler son arrivée depuis son téléphone, sans friction, et un responsable doit pouvoir pointer un membre absent de téléphone.

---

## Objectifs

- Permettre à chaque participant d'enregistrer sa présence en un clic depuis un navigateur web.
- Permettre à n'importe quel participant d'enregistrer la présence d'un autre membre (téléphone déchargé, pas de smartphone, etc.).
- Conserver un historique des présences par séance.
- Pas d'installation d'application requise : tout se passe dans le navigateur.

---

## Fonctionnalités principales

### 1. Page d'accueil — Pointer ma présence

- L'utilisateur arrive sur la page web (via un lien ou un QR code affiché en salle).
- Il saisit son prénom et son nom dans un champ de texte avec autocomplétion (basée sur les membres connus), sans afficher la liste complète.
- Il confirme sa présence d'un clic.
- Sa présence est enregistrée avec l'horodatage.
- Un retour visuel immédiat confirme l'enregistrement.

> **Confidentialité** : la liste des membres n'est jamais affichée en clair. L'autocomplétion ne se déclenche qu'après quelques caractères saisis, limitant l'exposition des noms.

### 2. Pointer un autre participant ou un visiteur

- Depuis la même page, un bouton « Pointer quelqu'un d'autre » permet de saisir le nom d'un autre participant.
- Si le nom saisi correspond à un membre connu, il est associé à son profil.
- Si le nom ne correspond à personne (visiteur impromptu), il est enregistré comme **visiteur** avec le nom saisi librement.
- Utile lorsqu'un participant n'a plus de batterie, ne dispose pas de smartphone, ou est un invité ponctuel.
- Aucune authentification spéciale requise : la confiance repose sur la présence physique en salle.
- **Un responsable ne peut pas pointer un membre absent** : le pointage par proxy ne peut être fait que par quelqu'un de physiquement présent.

### 3. Sélection de la séance

- Les séances sont lues automatiquement depuis un **agenda Google public** — aucune saisie manuelle requise.
- La page de pointage propose par défaut la **séance en cours** (si l'heure actuelle est dans la plage de l'événement) ou, à défaut, la **prochaine séance à venir**.
- Un participant peut aussi choisir une **séance passée** dans la liste (arrivée tardive, oubli de pointer le jour même).
- Le lien est **permanent et toujours accessible** — un participant peut se pointer à tout moment.

### 4. Interface responsable — Correction des entrées

- Un responsable authentifié accède à une interface dédiée.
- Il peut consulter la liste des présences de la séance en cours.
- Il peut **supprimer une entrée erronée** (doublon, mauvaise sélection de nom).
- Il n'a **pas à ajouter** une présence depuis cette interface : seul le pointage depuis la page participant est nécessaire.
- Possibilité d'exporter la liste finale (CSV ou impression).

---

## Architecture technique envisagée

### Frontend

- Application web legère, utilisable sur mobile sans installation.
- Interface simple : liste de noms, bouton de confirmation, retour visuel.
- Accessible via QR code affiché en salle.

### Backend

- API REST minimale pour enregistrer et consulter les présences.
- Base de données légère (SQLite ou PostgreSQL selon le volume).
- Pas d'authentification obligatoire pour pointer (accès via lien non public).
- Intégration **Google Calendar API** (lecture seule) pour récupérer les événements de l'agenda public et les proposer comme séances.

### Déploiement

- Hébergement sur un serveur simple ou un Raspberry Pi sur le réseau local de la salle.
- Accès via Wi-Fi de l'association — pas besoin d'internet.
- Alternativement : hébergement en ligne avec un lien court ou QR code.

---

## Contraintes et choix de conception

| Contrainte | Décision |
|---|---|
| Pas d'app à installer | Application web (PWA possible) |
| Téléphone déchargé | Pointage par proxy depuis n'importe quel appareil présent en salle |
| Simplicité d'usage | Zéro compte, zéro mot de passe pour les participants |
| Visiteur impromptu | Saisie libre du nom, enregistré avec le statut « visiteur » |
| Confidentialité | Pas de liste affichée — autocomplétion déclenchée après quelques caractères |
| Fiabilité | Confirmation visuelle immédiate, données persistées côté serveur |
| Accès permanent | Lien fixe et toujours accessible — pointage possible à tout moment |
| Source des séances | Agenda Google public — pas de double saisie, le calendrier fait autorité |
| Sélection de séance | Séance en cours par défaut ; séances passées accessibles si besoin |

---

## Ce qui est hors périmètre (dans un premier temps)

- Authentification des participants (compte / mot de passe).
- Gestion des séances depuis l'interface (les séances viennent de l'agenda Google).
- Notifications ou rappels automatiques.
- Application mobile native.

---

## Étapes de réalisation

1. **Cadrage** — Définir la liste des membres, le format des séances, les droits d'accès souhaités.
2. **Prototype** — Interface de pointage minimaliste + API d'enregistrement.
3. **Test en conditions réelles** — Déploiement lors d'une réunion, recueil des retours.
4. **Ajustements** — Corrections UX, gestion des cas limites (doublon, annulation).
5. **Mise en production** — Déploiement stable, documentation pour les responsables.
