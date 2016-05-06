GeoNueBot e' un servizio per georiferire informazioni in mappa.

Per inviare una segnalazione, clicca [Invia posizione] dall'icona a forma di graffetta e aspetta una decina di secondi. Quando ricevi la risposta automatica, puoi scrivere un testo descrittivo o allegare un contenuto video foto audio ect.

OPZIONI AVANZATE

/me - il tuo profilo
/C+[numero segnalazione] - cancella segnalazione (es: /C001), solo se in stato registrata o sospesa 
/maplist - lista mappe disponibili
/setmap - imposta mappa da usare
/infomap+[idmappa] - informazioni mappa (es: /infomap1)
/alerton - attiva avvisi (nuove mappe, cancellazione mappe etc..)
/alertoff - disattiva avvisi
/webservice - servizi di interoperabilità per consultare e scaricare i dati

/manager - funzionalità dedicate per gestione mappe
/admin - funzionalità dedicate agli amministratori


GESTIONE SEGNALAZIONI 

Alla domanda [APPROVI? Y/N] rispondere Y per si, N per no. 

Per approvazione diretta: 
/A+numrequest - approva (es: /A001)
/R+numrequest - respingi (es: /R001)
/S+numrequest - sospendi (es: /S001)
/C+numrequest - cancella (es: /C001)


CREAZIONE E GESTIONE MAPPE

/mymap - crea mappa personale (una per utente)
/newmap - crea nuova mappa (solo per profili avanzati)

[ Prima di utilizzare queste funzionalità impostare la mappa con /setmap ]
/enablemap - attiva mappa
/disabledmap - disattiva mappa
/privatemap - rende mappa privata
/publicmap - rende mappa pubblica
/onapproved - abilita procedura approvazione  
/offapproved - disabilita procedura approvazione
/Alist - lista segnalazioni da approvare
/Slist - lista segnalazioni in sospeso


FUNZIONALITA DEDICATE AGLI AMMINISTRATORI
      
/listallmap - lista di tutte le mappe del sistema
/setdefaultmap+[idmappa] - imposta la mappa del bot di default (es: /setdefaultmap1)
