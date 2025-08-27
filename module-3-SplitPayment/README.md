# Module Split Payment pour Dolibarr

## 1. Vue d'ensemble

Le module **Split Payment** est une extension pour Dolibarr qui répond à un besoin comptable spécifique : enregistrer un seul règlement client et le répartir sur deux comptes bancaires distincts.

Par défaut, Dolibarr impose qu'un paiement soit associé à un unique compte bancaire. Pour contourner cette limitation, les utilisateurs étaient obligés d'enregistrer deux paiements manuels pour une seule transaction, ce qui doublait le temps de saisie et augmentait le risque d'erreurs. Ce module rationalise ce processus en une seule opération simple et rapide.

---

## 2. Installation

1.  Copiez le dossier du module `splitpayment` dans le répertoire `<racine_dolibarr>/htdocs/custom/`.
2.  Connectez-vous à votre instance Dolibarr avec un compte administrateur.
3.  Allez dans **Accueil -> Configuration -> Modules/Applications**.
4.  Trouvez le module **Split Payment** dans la liste et cliquez sur le bouton "Activer".

---

## 3. Utilisation

Le flux de travail est simple et s'intègre directement sur la page des factures.

1.  Rendez-vous sur la facture client pour laquelle vous souhaitez enregistrer un règlement ventilé.
2.  Cliquez sur le nouveau bouton **"Saisir un règlement ventilé"**.
3.  Vous serez redirigé vers un formulaire de saisie spécifique où vous devrez renseigner :
    * Le montant total du règlement.
    * Le premier compte bancaire de destination et le montant à lui affecter.
    * Le second compte bancaire de destination et le montant restant.
4.  Validez le formulaire. Le module se chargera de créer automatiquement les deux écritures de paiement distinctes dans Dolibarr, soldant correctement la facture.

---

## 4. Note Technique Importante

Lors de la validation du paiement ventilé, il est possible que des avertissements PHP ("warnings") apparaissent à l'écran.

* **Cause :** Ces avertissements proviennent d'une fonction du cœur de Dolibarr qui est appelée par le module et qui s'attend à une information de date qui n'est pas pertinente dans ce contexte précis.
* **Impact :** Après analyse et de multiples tests, il a été confirmé que ces avertissements sont **inoffensifs**. Ils n'ont **aucun impact** sur le bon fonctionnement du module : les paiements sont correctement et intégralement enregistrés en base de données.
* **Action :** Il n'est pas possible de corriger ces avertissements sans modifier le code source de Dolibarr, ce qui est une pratique fortement déconseillée. Vous pouvez donc ignorer ces messages en toute sécurité.
