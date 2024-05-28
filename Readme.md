# BugQuest 360 Panorama

Une simple application web pour afficher ses panorama 360 "Equirectangular"  

Je suis ici en php native sans framework, sans composer.  
Avec Three.js en version DOM et Vue.js en version DOM.  
  
Toutes les panorama sont en format ** .jpg**  et vont dans le dossier ** ./images**  à la racine du projet.  
  
Le panorama de base doit etre dans une dimension respectant la ** base 2**  et ne depassant pas **4096x2048px pour pouvoir fonctionner sur mobile** .
  
On peut mettre des dimension superieur pour pc, il faudra les renommer ** "nom_du_panorama_hd.jpg"**  pour du 8192x4096px et ** "nom_du_panorama_max.jpg"**  pour la taille max.  
Bien respecter les prefixs et mettre le même nom pour les mêmes panorama, sans prefix pour le panorama de base, dit "mobile".  
  
Je n'ai pas encore tésté la limite de taille.  