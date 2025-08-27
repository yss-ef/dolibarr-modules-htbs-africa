# Module Trésorerie Mensuelle pour Dolibarr

## 1. Vue d'ensemble

Le module **Trésorerie Mensuelle** offre un tableau de bord complet pour visualiser l'état de la trésorerie d'une entreprise pour une période donnée. Il a été conçu pour résoudre deux problèmes majeurs :

1.  **Centraliser l'information** financière qui est normalement éparpillée sur plusieurs pages de Dolibarr.
2.  **Automatiser la synthèse** des données pour éliminer le suivi manuel via des tableurs externes (Excel) et réduire le risque d'erreurs.

La fonctionnalité clé de ce module est la possibilité de gérer des **charges fixes** (telles que les loyers, salaires, etc.) qui, pour des raisons de confidentialité ou de complexité, ne sont pas toujours présentes dans Dolibarr. Un outil de gestion complet (CRUD) est intégré pour permettre aux utilisateurs habilités d'ajouter, modifier et supprimer ces charges en toute autonomie.

## 2. Installation

1.  Copiez le dossier du module `tresorerie` dans le répertoire `<racine_dolibarr>/htdocs/custom/`.
2.  Connectez-vous à votre instance Dolibarr avec un compte administrateur.
3.  Allez dans **Accueil -> Configuration -> Modules/Applications**.
4.  Trouvez le module **Trésorerie Mensuelle** dans la liste et cliquez sur le bouton "Activer".

L'activation du module créera automatiquement la nouvelle table nécessaire en base de données pour stocker les charges fixes. Aucune action manuelle n'est requise sur la base de données.

## 3. Utilisation

Le module s'articule autour de trois nouvelles pages accessibles depuis le menu Dolibarr.

### 3.1. Gérer les Charges Fixes

Avant de consulter le tableau de bord, il est recommandé d'ajouter les charges fixes récurrentes.

1.  Naviguez vers la page de gestion des charges fixes.
2.  Ici, vous pouvez voir la liste des charges déjà créées, les **modifier** ou les **supprimer**.
3.  Pour en ajouter une nouvelle, cliquez sur le bouton "Ajouter une charge fixe" et remplissez le formulaire (libellé, montant, date, etc.).

### 3.2. Consulter le Tableau de Bord de Trésorerie

La page principale du module est le tableau de bord. Il affiche une synthèse complète de la situation financière via six tableaux :

- Les factures fournisseurs **à payer**.
- Les factures clients **à recevoir**.
- Les factures fournisseurs **déjà réglées**.
- Les factures clients **déjà encaissées**.
- La liste des **charges fixes** que vous avez configurées.
- Un **tableau de synthèse final** qui calcule le solde prévisionnel (`Total à payer`, `Total à recevoir`) pour savoir en un coup d'œil si la trésorerie est positive ou négative.
