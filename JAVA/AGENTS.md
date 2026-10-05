# OpenFiber
OpenFiber è una libreria indipendente in JAVA per l'interfacciamento atomico, astratto, asincrono e dinamico tra OLO e B2B Gateway di Open Fiber che può essere integrata anche nei vari framework java.
Si basa sulle direttive e specifiche tecniche emanate da OpenFiber.
OpenFiber fornisce i certificati del B2B Gateway.
OLO deve fornire a OpenFiber i certificati del suo Gateway.
Gli OLO che adottano questa libreria dovranno sviluppare ed integrare con proprio sistema gestionale.

# Sviluppo
La cartella di lavoro per lo sviluppo è **TASSATIVAMENTE `STEFANMCDS-MNT/OpenFiber/JAVA/`**.
Lo sviluppo **DEVE ESSERE TASSATIVAMENTE ED ESCLUSIVAMENTE ATOMICO, ASTRATTO, ASINCRONO E DINAMICO basato sui files delle specifiche tecniche presenti in cartella `STEFANMCDS-MNT/OpenFiber/SPECIFICHE/`**.
**Non devono esserci classi e/o funzioni che svolgono operazioni simili o uguali, se necessarie accorpare in un unica classe che sia atomica, astratta, asincrona e dinamica**.

# Struttura
**La struttura della libreria ha la seguenti regole fondamentali**:
1. Configurazione articolata su:
   - database (sempre predefinito);
   - files;
   - env.
   viene indicata in fase di inizializzazione e configurazione, possibilità cambiare successivamente ripete la procedura di Download Dinamico specifica:
   - per il Database il file sql è presente in cartella `STEFANMCDS-MNT/OpenFiber/JAVA/migrations/`;
   - per i files in path.
2. Download Dinamico dei files WSDL atomico, astratto, asincrono e dinamico con parametro scelta al fine di salvare in database o in cartella.
3. Client SOAP atomico, astratto, asicrono e dinamico per interfacciarsi con B2B Gateway di OpenFiber per le request, **non deve esserci nessun elemento, files dedicato e specifico per i serviceName**.
4. Server SOAP atomico, astratto, asincrono e dinamico esposto verso B2B Gateway OpenFiber per le response, **non deve esserci nessun elemento, files dedicato e specifico per i serviceName**.
5. Gestione Avanzata atomica, astratta, asincrona e dinamica degli Errori, **non deve esserci nessun elemento, files dedicato e specifico per i serviceName**.
6. Interfaccia di inizializzazione, configurazione e gestione della libreria sia su java cli che grafica html.
7. Documentazione della libreria dettagliata ed esaustiva con tutti gli esempi di utilizzo.

# Interfaccia Inizializzazione e Gestione
L'interfaccia di inizializzazione e gestione della libreria deve essere semplice e di facile utilizzo documentata in ogni sua componente con un sistema di help.
Costituisce elemente nevralgico ed essenziale per utilizzo della libreria è il **VERBO E VERITA' ASSOLUTA** senza non può funzionare.
Deve avere le seguenti procedure:
- impostare il tipo di configurazione database o files;
- impostare la variabili comuni utilizzate in libreria;
- scaricare tramite il dowload dinamico i files WSDL passati come singolo o lista di url;
- impostare il Client SOAP;
- impostare il Server SOAP.

# Download Dinamico
Il Download Dinamico è fondamentale tramite questa procedura si scaricano i files WSDL dai quali viene impostato tutto il lavoro del Client SOAP.
I WSDL possono essere salvati in una cartella oppure in database.

# Client SOAP
Il client SOAP è lo strumento per inviare al B2B Gateway di OpenFiber le richieste dell'OLO.
Il client in funzione del serviceName da utilizzare cerca la configurazione endpoint,path,certificati e le variabili da valorizzare prelevati da database/file.
Confronta le variabili da valorizzare con quelle passate come argomento.
Genera il payload da inviare al B2B creando la url endpoint del servizio esposto da B2B Gateway OpenFiber es. `https://endpointurl/servicename` ed invia la request.

# Server SOAP
E' il Server SOAP esposto verso B2B Gateway di OpenFiber è lo strumento per ricevere le risposte invite dal client.
Verifica se endpoint e ip di provenienza è tra quelli autorizzati, confronta l'host del certificato sia corrispondente a quello dell'endpoint provenienza, analizza la risposta e restituisce un oggetto che può essere array, json e testo per le azioni in gestionale.

# Gestione Errori
La gestione degli errori per inizializzazione e gestione, soap ed altra iterazione.
