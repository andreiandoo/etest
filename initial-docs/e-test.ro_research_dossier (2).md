# e-test.ro — Research Dossier

**Platformă:** e-test.ro  
**Status:** Discovery v1 închis; inventariere tehnică începută  
**Data ultimei actualizări:** 19 septembrie 2026  
**Scop:** inventarierea domeniilor de examene/teste relevante pentru România, a surselor de întrebări și răspunsuri, a posibilităților de reutilizare și a direcției tehnice pentru o platformă gratuită de pregătire.

---

## 1. Decizia de produs

Domeniul principal al proiectului va fi **e-test.ro**.

Poziționare recomandată:

> **eTest — teste și simulări pentru examene, admiteri, autorizări și certificări din România.**

Produsul nu trebuie construit ca „un site de chestionare auto”, ci ca un **motor universal de pregătire pentru examene**. Același nucleu trebuie să poată susține examene foarte diferite: permis auto, admitere la medicină, Barou, ARR, vânătoare, radioamator, rezidențiat, concursuri ANFP etc.

Domeniul `eduplan.ro` poate fi păstrat pentru o extensie ulterioară orientată spre planificarea învățării / tutor / plan de studiu sau poate redirecționa inițial către e-test.ro.

### Stack-ul avut în vedere

- Laravel
- Blade
- Tailwind CSS
- Alpine.js
- autentificare utilizatori
- PostgreSQL recomandat pentru flexibilitatea relațiilor și căutării
- Redis opțional pentru cache / sesiuni / quiz queues

### Experiența de bază

Pentru fiecare examen:

1. **Învățare** — toate întrebările / întrebări pe capitole.
2. **Test rapid** — 5/10/20/50 întrebări.
3. **Simulare examen** — reguli identice cu examenul real.
4. **Întrebările mele greșite**.
5. **Întrebări favorite**.
6. **Statistici pe capitole**.
7. **Progres**.
8. **Istoric simulări**.
9. **Explicații + sursa legală/bibliografică**, unde este posibil.

---

## 2. Principiul critic: „public” nu înseamnă automat „liber de republicat”

Pentru fiecare sursă e-test.ro trebuie să rețină separat:

- dacă documentul este accesibil public;
- dacă este o sursă oficială;
- dacă există o licență / permisiune explicită pentru reproducere;
- dacă există o restricție explicită;
- dacă avem nevoie de acord scris;
- dacă întrebările trebuie recreate editorial din legislație/bibliografie.

### Legendă reutilizare

- 🟢 **VERDE — reutilizare explicit permisă**: sursa publică precizează condițiile de reproducere.
- 🟡 **GALBEN — public oficial, reutilizare neclară**: bun pentru research și import tehnic numai după validarea drepturilor / acord.
- 🔴 **ROȘU — restricție explicită**: nu importăm în produs fără acord/licență.
- 🔵 **ALBASTRU — conținut original e-test**: putem crea întrebări proprii pe baza legislației, programelor sau materialelor publice, fără copierea itemilor protejați.

Acest document nu este o opinie juridică. Pentru lansarea comercială/publică a fiecărei bănci trebuie verificată licența/permisiunea efectivă.

---

# 3. Matrice generală de oportunități

| Verticală | Examen / categorie | Sursa de întrebări | Reutilizare | Potențial | Prioritate |
|---|---|---|---|---|---|
| Auto | Permis auto / DRPCIV-DGPCI | simulator oficial + RoD-TAL ca referință | 🟡 / 🔴 dataset | foarte mare | P0 |
| Transport | ARR – CPI/CPC/ADR/taxi/manager etc. | bancă oficială ARR | 🟡 | foarte mare | P0 |
| Cinegetic | Permis de vânător | set oficial întrebări+răspunsuri | 🟡 | mare | P0 |
| Radio | Radioamator ANCOM | liste/subiecte oficiale | 🟢 | mediu | P0 |
| Medicină | Admitere UMF | examene + grile oficiale | 🟡 | foarte mare | P0 |
| Medicină | Rezidențiat | grile/bareme oficiale istorice | 🟡 | foarte mare | P0/P1 |
| Medicină | Grad principal asistenți | teste+grile oficiale | 🟡 | mare | P0 |
| Drept | Admitere Drept UB | arhivă subiecte+răspunsuri | 🟡 | mare | P0 |
| Drept | Admitere Drept UBB | subiecte+barem oficial | 🟡 | mare | P0 |
| Drept | Barou | subiecte+bareme oficiale | 🟡 | mare | P0 |
| Drept | INM | arhivă oficială CSM | 🟡 | mare | P1 |
| Drept | SNG | subiecte+bareme oficiale | 🟡 | mediu/mare | P1 |
| Drept | Notar stagiar | grilă, arhivă/parțial | 🟡 | mediu | P1 |
| Insolvență | UNPIR | modele oficiale + tematică | 🟡 | mediu | P1 |
| Administrație | ANFP | baterii teste + rezolvări | 🟡 | mare | P0 |
| Finanțe | CAFR | subiecte+rezolvări 2021–2025 | 🟡 | mediu | P1 |
| Fiscal | Consultant fiscal CCF | chestionare+grile oficiale | 🟡 | mediu | P1 |
| Evaluare | ANEVAR | format oficial, bază min. 500 există | 🟡 | mediu | P2 |
| Asigurări | ISF | examen definit; bancă publică neidentificată | 🔵/🟡 | mediu | P2 |
| Energie | ANRE electrician autorizat | exemple oficiale | 🟡 + 🔵 | mediu | P1/P2 |
| Aeronautic | Drone A1/A3/A2/STS | materiale oficiale; fără bancă completă | 🔵 | mediu | P1/P2 |
| Naval | Permis ambarcațiune | întrebări orientative ANR | 🔴 | mediu/mare | P2 după acord |
| MAI | Academia de Poliție / școli MAI / concursuri | subiecte+bareme publicate | 🟡 | mare | P1 |
| Educație | Titularizare | subiecte+bareme oficiale | 🔴 pentru copiere | foarte mare | P2 cu itemi proprii |
| Educație | Definitivat | subiecte+bareme oficiale | 🔴 pentru copiere | mare | P2 cu itemi proprii |
| Educație | BAC | subiecte+bareme oficiale | 🔴 pentru copiere | enorm | P2/P3 cu itemi proprii |
| Educație | Evaluare Națională | subiecte+bareme oficiale | 🔴 pentru copiere | enorm | P2/P3 cu itemi proprii |
| Contabilitate | CECCAR | materiale preponderent comerciale | 🔴/neclar | mare | nu la început |
| Radioprotecție | CNCAN — permise de exercitare | 21 bănci oficiale + răspunsuri comentate | 🟡 | mare | P0 inventariere |
| Medical | Dietetician autorizat | bancă oficială 2025, 263 itemi | 🟡 | mediu | P1 |
| Tehnic | ISCIR / ISC / RAR / audit energetic | exemple, proceduri și tematici oficiale | 🟡 + 🔵 | mare cumulat | P1/P2 |
| Naval / feroviar | ANR / CENAFER | examinări oficiale; acces și drepturi variabile | 🔴/🟡/🔵 | mediu | P2 |
| Proprietate intelectuală | Consilier OSIM | proceduri și tematici oficiale | 🔵/🟡 | nișă valoroasă | P2 |
| Agricultură | Utilizatori produse fitosanitare | instruire obligatorie; bancă publică de verificat | 🔵/🟡 | mediu | P2 |

---

# 4. AUTO — permis de conducere

## 4.1. Surse identificate

### A. DGPCI / simulator oficial

Portal oficial:  
https://dgpci.mai.gov.ro/

DGPCI oferă simulatorul oficial al probei teoretice. Pentru e-test.ro, acesta este reperul de fidelitate pentru structură și reguli, dar nu a fost identificat încă un dump oficial JSON/CSV al întregii bănci publice cu licență explicită de reutilizare.

**Status:** 🟡 — trebuie clarificate drepturile și metoda de obținere a băncii complete.

### B. RoD-TAL — benchmark academic

GitHub cod/notebook-uri:  
https://github.com/vladman-25/RoD-TAL

Dataset Hugging Face:  
https://huggingface.co/datasets/GRAI-UNSTPB/RoD-TAL

README dataset:  
https://huggingface.co/datasets/GRAI-UNSTPB/RoD-TAL/blob/main/README.md

Conținut release curent:

- `split_1`: 638 întrebări
- `split_2`: 181 întrebări
- `split_3`: 316 întrebări multimodale
- `split_4`: 71 întrebări multimodale
- total: **1.206 intrări**
- corpus legislație: 443 articole/fragmente
- corpus indicatoare: 140 elemente

Câmpuri utile identificate:

- id
- primary_category
- secondary_category
- question
- answers
- legislation
- image
- traffic_signs (pentru split-urile vizuale)

**Important:** codul GitHub este MIT, dar datasetul este `CC BY-NC-SA 4.0` și, suplimentar, este gated. Condiția de acces spune explicit că utilizarea trebuie să fie exclusiv academică, să nu fie folosit în produse comerciale și să nu fie redistribuit.

Pentru e-test.ro datasetul este **foarte bun ca referință de structură și research, dar NU trebuie importat/redistribuit direct fără permisiune**.

**Status:** 🔴 pentru import direct.

### C. Repo-uri istorice găsite ca urme/referințe

- `AdiCutitoiu/chestionare-auto` — menționat în alte repo-uri ca „drpciv-crawler”; repo-ul nu mai este disponibil normal.
- `70mmy/drpciv-helper` — extensie Chrome pentru pregătire permis; referințe istorice, repo inaccesibil/redenumit/șters la verificare.

Nu pot fi considerate surse operaționale.

## 4.2. Recomandare

1. Folosim RoD-TAL ca **model de taxonomie și schemă**, nu ca sursă redistribuită.
2. Investigăm oficial DGPCI/DGPCI simulator și condițiile de reutilizare.
3. Alternativ, cerem acord oficial sau construim o bancă proprie pe baza legislației rutiere, marcând clar că este „pregătire compatibilă cu examenul”, nu „întrebări oficiale” dacă nu sunt oficiale.

---

# 5. TRANSPORT — ARR

Sursă oficială principală:  
https://www.arr.ro/servicii_doc_164_eliberare-atestate_pg_0.htm

ARR precizează explicit că publică **baza de întrebări pentru obținerea certificatelor de atestare profesională**.

Platformă de testare menționată de ARR:  
https://testare.arr.ro/

Comunicat privind publicarea chestionarelor în limba engleză începând cu 20.02.2025:  
https://www.arr.ro/arr_doc_1229_comunicat-privind-sustinerea-unor-examene-in-limba-engleza_pg_0.htm

### Certificări/direcții identificate

- CPI marfă
- CPI persoane
- CPI scurt marfă/persoane
- CPC marfă
- CPC persoane
- ADR
- conducător auto taxi
- transport în regim de închiriere
- viză taxi / viză închiriere
- manager transport marfă
- manager transport persoane
- instructor auto
- profesor de legislație rutieră
- consilier de siguranță
- transport vehicule avariate
- alte atestate profesionale publicate de ARR

**Avantaj:** o singură instituție poate alimenta multe examene e-test.ro.

**Status reutilizare:** 🟡 — banca este publicată oficial, dar trebuie verificat dreptul de republicare în propria platformă.

**Prioritate:** P0.

---

# 6. PERMIS DE VÂNĂTOARE

Sursă oficială Ministerul Mediului:  
https://mmediu.ro/domenii/mediu/management-cinegetic/permis-de-vanatoare/

La 16 iulie 2026 Ministerul a publicat un **„Set de întrebări și răspunsuri privind examenul de vânător”**, actualizat conform legislației.

Structura legală cunoscută: banca utilizată pentru pregătire/examen are întrebări cu trei variante și un răspuns corect; setul oficial este foarte potrivit pentru import într-un quiz engine.

### UX recomandat

- mod învățare
- toate întrebările
- pe capitole
- întrebări greșite
- favorite
- simulare în formatul examenului

**Status:** 🟡 — sursă oficială excelentă, dar licența de republicare trebuie verificată.

**Prioritate:** P0.

---

# 7. RADIOAMATOR — ANCOM

Pagina radioamatori:  
https://www.ancom.ro/category/autorizare-ro/radioamatori/

Sesiuni examinare:  
https://www.ancom.ro/autorizare-ro/radioamatori/radioamatori-sesiuni-de-examinare/

Rezultate sesiuni:  
https://www.ancom.ro/autorizare-ro/radioamatori/rezultate-sesiuni-de-examinare/

ANCOM publică programele și liste de subiecte, inclusiv pentru radiotehnică și clasele de certificate.

### Element foarte important: permisiune explicită de reproducere

Disclaimer ANCOM:  
https://www.ancom.ro/uncategorized-ro/disclaimer/

ANCOM spune explicit că reproducerea integrală sau parțială a conținutului este permisă cu condiția:

- indicării sursei ANCOM;
- nemodificării sensului;
- neprezentării materialului ca provenind din altă sursă.

**Status:** 🟢 — cea mai clară sursă juridic dintre cele analizate.

**Prioritate:** P0.

---

# 8. NAVAL — permis conducător ambarcațiune de agrement (CAA)

Sursă oficială ANR:  
https://nou.rna.ro/personal-navigant/conducator-ambarcatiune-de-agrement/examinare-caa/

ANR publică:

- bibliografie;
- COLREG;
- regulament navigație pe Dunăre;
- manuale;
- **teste de examen CAA — întrebări orientative**, precizând că reprezintă doar o parte dintre întrebările de examen;
- momentan sunt vizibile explicit liste pentru clasele C și D pe pagina verificată.

### Restricție explicită

Termeni ANR:  
https://portal.rna.ro/Pagini/Termeni-si-conditii.aspx

ANR precizează că nicio parte din informațiile/materialele site-ului nu poate fi folosită sau incorporată într-o altă publicație/lucrare fără acord prealabil pentru reproducere.

**Status:** 🔴.

### Recomandare

Trimitem solicitare oficială ANR pentru drept de utilizare pe e-test.ro, explicând:

- platformă educațională;
- acces gratuit;
- sursa afișată permanent;
- fără modificarea întrebărilor oficiale;
- posibilitate de link către ANR.

**Prioritate:** P2 după obținerea acordului.

---

# 9. DRONE — AACR / UAS

Ghid AACR pentru A1/A3 și A2:  
https://www.caa.ro/uploads/pages/Ghid%20utilizare%20aplicatie%20online%20AACR%2016.04.2021.pdf

Reguli relevante:

- A1/A3: 40 întrebări;
- A2: 30 întrebări;
- minimum 75% pentru promovare;
- variante multiple cu un singur răspuns corect;
- întrebările sunt concepute în general pe baza materialelor de instruire AACR.

Examinare STS / categoria „specifică”:  
https://www.caa.ro/uploads/pages/Examinarea%20cunsotintelor%20teoretice%20categoria%20specifice%20pentru%20operatiuni%20UAS%20in%20cadrul%20scenariilor%20standard%20%28STS%29.pdf

Pentru STS, în funcție de certificările deținute, documentul prevede 30 sau 40 întrebări și prag de 75%.

Nu a fost identificată o bancă oficială completă publică.

### Strategie

🔵 Construim **itemi originali e-test.ro** pe baza:

- materialelor oficiale AACR;
- regulamentelor EASA;
- tematicii oficiale.

Nu îi etichetăm drept „întrebări oficiale” dacă nu sunt.

**Prioritate:** P1/P2.

---

# 10. ANRE — electrician autorizat

Pagina oficială examene:  
https://anre.ro/participanti-la-piata-de-energie/persoane-fizice/energie-electrica/electricieni/examene/

ANRE spune că examenele sunt organizate de regulă de două ori pe an și publică:

- tematica;
- bibliografia;
- **exemple de întrebări / probleme**.

Arhivă exemple:  
https://arhiva.anre.ro/ro/energie-electrica/legislatie/autorizare-electricieni/exemple-intrebari-in-curs-de-actualizare

Exemplele sunt grupate pe grade / electrotehnică / legislație etc.

**Status:** 🟡 pentru copierea exemplelor; 🔵 pentru întrebări originale generate editorial din bibliografie.

**Prioritate:** P1/P2.

---

# 11. MAI / ACADEMIA DE POLIȚIE / ȘCOLI MAI

Portal admitere Academia de Poliție:  
https://admitere.academiadepolitie.ro/

Portal HUB MAI:  
https://hub.mai.gov.ro/

Exemplu de concurs unde MAI publică subiectele și baremul ulterior:  
https://hub.mai.gov.ro/recrutarecandidat/anunt/view?id=8589

Pagina de mai sus are fișiere publicate ulterior concursului, inclusiv:

- „Subiecte concurs”;
- „Barem/Grila corectare”.

Pagina oficială Academiei publică de asemenea subiecte și răspunsuri pentru anumite admiteri/examene (de exemplu masterat profesional 2026).

### Direcții

- Academia de Poliție — Facultatea de Poliție;
- Facultatea de Pompieri;
- masterat profesional;
- școli postliceale MAI;
- concursuri de ocupare posturi în care se publică testul și baremul.

**Status:** 🟡.

**Prioritate:** P1, cu selecție atentă a examenelor recurente.

---

# 12. MEDICINĂ — verticală strategică

Medicina poate deveni una dintre cele mai importante verticale e-test.ro:

```text
Medicină
├── Admitere facultate
│   ├── UMF Carol Davila
│   ├── UMF Cluj
│   ├── UMF Iași
│   ├── UMF Timișoara
│   ├── UMFST Târgu Mureș
│   └── UMF Craiova
├── Rezidențiat
│   ├── Medicină
│   ├── Medicină Dentară
│   └── Farmacie
└── Grad principal asistenți medicali
```

## 12.1. Admitere UMF Carol Davila București

Grile/concurs 2026:  
https://umfcd.ro/general/grile-concurs-admitere-2026/

Pagina care indică explicit și caietele de întrebări:  
https://umfcd.ro/general/extindere-timp-contestatii-privind-continutul-intrebarilor-scrise-sau-corectitudinea-grilei-oficiale-de-raspunsuri-concurs-admitere-2026/

Aceasta trimite către:

- caiete întrebări Medicină;
- caiete întrebări Stomatologie;
- caiete întrebări Farmacie;
- grile răspunsuri corecte.

În 2026 UMFCD a raportat **6.916 candidați înscriși** la admitere, dintre care 2.727 la Medicină și 800 la Stomatologie.  
Sursă:  
https://umfcd.ro/general/comunicat-de-presa-admitere-umf-carol-davila2026-record-absolut-6-916-de-candidati-inscrisi/

**Status:** 🟡.

## 12.2. UMF Cluj — Iuliu Hațieganu

Pagina admitere 2026:  
https://umfcluj.ro/programe-studii/admitere-licenta/admitere-iulie-2026/

Publică pentru 2026:

- grile corecte Medicină / Medicină militară — Biologie și Chimie, variante 1–4;
- grile Medicină Dentară — variante 1–4;
- Farmacie — Biologie / Chimie;
- rezultate contestații.

Presimulare 2026:  
https://umfcluj.ro/programe-studii/admitere-licenta/presimulare-admitere-2026/

Publică și grilele corecte pentru presimulare.

**Status:** 🟡.

## 12.3. UMF Iași — Grigore T. Popa

Portal oficial:  
https://www.umfiasi.ro/

Site-ul indică admitere prin probă scrisă (test grilă) pentru programele relevante. Arhivele/subiectele trebuie inventariate în pasul de import; există și agregatoare terțe care indică subiecte 2017–2026, dar pentru e-test.ro trebuie folosite prioritar fișierele oficiale sau obținut acord.

**Status:** 🟡, necesită inventariere oficială mai adâncă.

## 12.4. UMF Victor Babeș Timișoara

Pagina admitere 2026:  
https://www.umft.ro/ro/admitere-ciclul-licenta-2026/

Publică explicit:

- subiecte;
- barem corectură;
- pentru sesiuni/tipuri de admitere 2026.

**Status:** 🟡.

## 12.5. UMFST Târgu Mureș

Rezultate/grile 2026:  
https://adminfo.umfst.ro/rezultate-admitere-2026/

Publică grile corecte pentru:

- Medicină — Biologie și Chimie, variante 1–4;
- Medicină Dentară — Biologie și Chimie;
- Farmacie — Chimie Organică.

Criterii admitere:  
https://adminfo.umfst.ro/criterii-de-admitere-2026/

Pentru Medicină proba scrisă este test grilă cu 100 de întrebări, la prima vedere, Biologie sau Chimie, cu una sau două variante corecte.

**Status:** 🟡.

## 12.6. UMF Craiova

Tematică și bibliografie:  
https://www.umfcv.ro/ro/admitere/admitere-licenta-2026/tematica-si-bibliografia-concurs-admitere-2026

Grile răspunsuri admitere 26.07.2026:  
https://umfcv.ro/ro/admitere/admitere-licenta-2026/grile-raspunsuri-admitere-26-iulie-2026

Comunicat privind grilele corecte / modificările după contestații:  
https://umfcv.ro/ro/admitere/admitere-licenta-2026/grile-raspunsuri-admitere-26-iulie-2026/comunicat-grile-corecte

Descrierea sistemului de grilă:  
https://new.umfcv.ro/ro/admitere/admitere-licenta-2026/desfasurare-concurs-admitere-2026

**Status:** 🟡.

### Recomandare pentru admiterea la medicină

Fiecare universitate trebuie modelată ca `institution`, nu ca examen complet separat în baza de date. Astfel:

- subject = Biologie / Chimie / Fizică etc.;
- exam = Admitere Medicină;
- institution = UMFCD / UMF Cluj / UMF Iași etc.;
- session = Admitere iulie 2026;
- question_set = Varianta 1/2/3/4.

**Prioritate verticală:** P0.

---

# 13. REZIDENȚIAT

Portal UMFCD/Rezidențiat:  
https://rezidentiat.umfcd.ro/afisares/index/rezidentiat

Pentru sesiunea 16 noiembrie 2025 sunt publicate oficial grilele corecte:

- Medicină A/B/C/D;
- Medicină Dentară A/B/C/D;
- Farmacie A/B/C/D.

Pentru 2026 sunt deja publicate tematica și bibliografia pentru sesiunea 15.11.2026.

Ministerul Sănătății — date volum 2025:  
https://ms.gov.ro/ro/centrul-de-presa/ministerul-s%C4%83n%C4%83t%C4%83%C8%9Bii-organizeaz%C4%83-ast%C7%8Ezi-concursul-na%C8%9Bional-de-admitere-%C3%AEn-reziden%C8%9Biat-pe-locuri-%C8%99i-pe-posturi/

Date 2025:

- **10.147 candidați** total;
- 6.956 Medicină;
- 1.950 Medicină Dentară;
- 1.241 Farmacie;
- examen: 200 întrebări / 4 ore.

### Risc

Bibliografia este comercială și întrebările au legătură directă cu conținutul acesteia. Nu trebuie copiate bănci private ale competitorilor.

Strategie:

- arhivă a examenelor oficiale, dacă dreptul de reutilizare este confirmat;
- întrebări originale e-test pe capitole, redactate independent pe baza tematicii și surselor permise;
- versionare strictă anuală.

**Status:** 🟡 pentru materialele oficiale; 🔵 pentru banca proprie.

**Prioritate:** P0/P1.

---

# 14. ASISTENȚI MEDICALI — EXAMEN GRAD PRINCIPAL

Sursă oficială OAMGMAMR 2026:  
https://www.oamr.ro/teste-grila-grad-sesiunea2026/

OAMGMAMR publică **testele-grilă și grilele de corectură** pentru toate specialitățile organizate în 2026:

- Asistență Medicală Generală;
- Balneofizioterapie;
- Farmacie;
- Igienă și sănătate publică;
- Laborator;
- Moașă;
- Nutriție și dietetică;
- Radiologie;
- Stomatologie.

Aceasta este o sursă extrem de bună pentru arhivă istorică și simulări.

**Status:** 🟡.

**Prioritate:** P0.

---

# 15. DREPT — verticală strategică

Structură recomandată:

```text
Drept
├── Admitere facultate
│   ├── Drept UB
│   └── Drept UBB
├── Barou
├── INM
├── Școala Națională de Grefieri
├── Notar stagiar
└── Practician în insolvență
```

---

# 16. ADMITERE DREPT — UNIVERSITATEA DIN BUCUREȘTI

Arhivă oficială a concursurilor anterioare:  
https://drept.unibuc.ro/subiecte-de-la-concursurile-anterioare-s103-ro.htm

Pagina conține subiecte și răspunsuri/grile pe mulți ani și simulări.

În 2026 examenul de admitere a avut **1.756 candidați**, record raportat de facultate.  
Sursă:  
https://drept.unibuc.ro/Cifre-record-si-interes-istoric-la-examenul-de-admitere-pentru-programul-de-licenta-sesiunea-iulie-2026-s61-avz1056-ro.htm

Materii utilizate în structura curentă:

- Limba română;
- Economie;
- Gândire critică.

**Status:** 🟡.

**Prioritate:** P0.

---

# 17. ADMITERE DREPT — UBB CLUJ

Pagina oficială admitere:  
https://law.ubbcluj.ro/admitere/admitere-licenta/

Pentru 20 iulie 2026 UBB publică:

- Grila 1–6;
- baremul;
- răspunsurile corecte;
- proces-verbal contestații;
- probă de raționament logic, 1 oră.

**Status:** 🟡.

**Prioritate:** P0.

---

# 18. BAROU — INPPA / UNBR

Pagina examene INPPA:  
https://inppa.ro/examene/

Subiecte și bareme aprilie 2026:  
https://inppa.ro/publicam-subiectele-si-baremele-la-proba-scrisa-tip-grila-sustinuta-in-data-de-26-aprilie-2026-la-examenul-de-primire-in-profesia-de-avocat-pentru-obtinerea-titlului-de-avocat-stagiar-si-pe/

Mirror/UNBR:  
https://unbr.ro/publicam-subiectele-si-baremele-la-proba-scrisa-tip-grila-sustinuta-in-data-de-26-aprilie-2026-la-examenul-de-primire-in-profesia-de-avocat-pentru-obtinerea-titlului-de-avocat-stagiar-si-pe/

Sunt publicate:

- Stagiari — Grila 1–4 + bareme;
- Definitivi — Grila 1–4 + bareme;
- bareme finale după contestații unde este cazul.

Materii:

- organizarea profesiei;
- drept civil;
- procedură civilă;
- drept penal;
- procedură penală.

### Cerință tehnică importantă

Întrebările juridice trebuie versionate:

- `valid_from`
- `valid_until`
- `exam_year`
- `legal_reference`
- `is_current`

O întrebare din 2021 poate avea alt răspuns în 2026 după modificări legislative.

**Status:** 🟡.

**Prioritate:** P0.

---

# 19. INM — Institutul Național al Magistraturii

Concurs 2026–2027:  
https://csm1909.ro/PageDetails/12817/Concurs-de-admitere-la-Institutul-Na%C5%A3ional-al-Magistraturii%2C-organizat-%C3%AEn-perioada--iulie-2026-%E2%80%93-aprilie-2027

Arhiva 2025–2026:  
https://www.csm1909.ro/PageDetails/12150/

CSM publică documente pentru fiecare sesiune, inclusiv:

- regulament;
- tematică;
- bibliografie;
- test-grilă / bareme în etapele relevante;
- contestații;
- probe scrise juridice;
- rezultate.

Exemplu document oficial barem/probă scrisă 2025:  
https://www.csm1909.ro/ViewFile.ashx?guid=89d5aac5-6cb0-40e5-b097-56d66288e967-InfoCSM

**Status:** 🟡.

**Prioritate:** P1.

---

# 20. ȘCOALA NAȚIONALĂ DE GREFIERI — SNG

Pagina oficială concurs 2026:  
https://www.csm1909.ro/PageDetails/12636/

CSM publică într-un singur loc:

- Subiecte;
- Barem;
- procese verbale contestații;
- tematică și bibliografie;
- calendar;
- rezultate.

Aceasta este una dintre cele mai curate structuri pentru import istoric.

**Status:** 🟡.

**Prioritate:** P1.

---

# 21. NOTAR STAGIAR — Institutul Notarial Român

Pagina concurs 2026:  
https://www.institutulnotarial.ro/site/concurs-notari-stagiari-2026/

Regulamentul / modelul de grilă:  
https://www.institutulnotarial.ro/site/wp-content/uploads/Regulamentul-pentru-organizarea-si-desfasurarea-examenului-sau-a-concursului-pentru-dobandirea-calitatii-de-notar-stagiar.pdf

Format:

- probă scrisă tip grilă;
- drept civil;
- drept procesual civil;
- procedură notarială;
- legislație notarială;
- model formular cu 100 întrebări;
- întrebări cu una sau două variante corecte conform regulamentului curent.

Nu a fost identificată încă o arhivă completă și simplu importabilă cu toate examenele istorice; există documente de contestații și materiale care pot revela întrebări, dar necesită cercetare punctuală.

**Status:** 🟡.

**Prioritate:** P1.

---

# 22. UNPIR — PRACTICIAN ÎN INSOLVENȚĂ

Pagina oficială examen:  
https://www.unpir.ro/examen-pentru-dobandirea-calitatii-de-practician

Anunț examen 17.10.2026:  
https://www.unpir.ro/anunt-privind-examenul-de-acces-profesia-de-practician-insolventa-sesiunea-17102026

UNPIR precizează explicit că pune la dispoziție:

- tematica;
- bibliografia;
- **model de grilă de examen**;
- **model de chestionar**.

Materii:

- drept;
- contabilitate/fiscalitate.

**Status:** 🟡.

**Prioritate:** P1.

---

# 23. ANFP — FUNCȚIONARI PUBLICI / CONCURS NAȚIONAL

Teste oficiale de antrenament:  
https://concurs-national.anfp.gov.ro/materiale-utile/teste-antrenament/

ANFP publică baterii de teste grilă cu **rezolvările aferente** pentru:

- administrație publică;
- respectarea demnității umane;
- drepturi și libertăți fundamentale;
- prevenirea și combaterea discriminării;
- egalitatea de șanse și tratament;
- alte componente ale probei de testare preliminară.

**Status:** 🟡.

**Prioritate:** P0.

---

# 24. CAFR — AUDITOR FINANCIAR

Examen competență profesională 2026:  
https://cafr.ro/examen-de-competenta-profesionala-2026/

CAFR pune la dispoziție:

1. **Culegere cu subiectele și rezolvările din sesiunile 2021–2025**;
2. broșură cu modele de subiecte pentru examen.

Examenul include:

- probă teoretică cu grile și sinteză;
- probă practică de aplicare.

Pentru e-test.ro folosim în primul rând partea de grilă; subiectele de sinteză pot deveni ulterior modul separat de „practice cases”.

**Status:** 🟡.

**Prioritate:** P1.

---

# 25. CONSULTANT FISCAL — CCF

Exemplu oficial chestionar + grila de corectare, Consultant Fiscal noiembrie 2025:  
https://www.ccfiscali.ro/content/examen/chestionare-grile/2025%20noiembrie%20subiecte%20si%20raspunsuri%20cons.fiscali.pdf

Consultant Fiscal Asistent noiembrie 2025:  
https://www.ccfiscali.ro/content/examen/chestionare-grile/2025%20noiembrie%20subiecte%20si%20raspunsuri%20cf%20asistenti.pdf

Instrucțiuni examen:  
https://ccfiscali.ro/content/examen/2025/InstructiuniCuPrivireLaDesfasurareaExamenului.pdf

Format identificat pentru sesiunea documentată:

- 40 întrebări;
- 4 variante;
- un singur răspuns corect;
- prag 75/100.

### Risc major: volatilitate legislativă

Fiscalitatea se schimbă rapid. Fiecare întrebare trebuie să aibă obligatoriu:

- anul/sesiunea;
- actele normative pe care se bazează;
- `valid_from`;
- `valid_until`;
- `last_reviewed_at`;
- stare: current / historical / withdrawn.

**Status:** 🟡.

**Prioritate:** P1.

---

# 26. ANEVAR — EVALUATOR AUTORIZAT

Pagina „Cum devin membru stagiar”:  
https://www.anevar.ro/p/educatie-si-evenimente/formare-profesionala/cum-devin-membru-stagiar-anevar/cum-devin-membru-stagiar

Format curent publicat:

- test grilă;
- 100 întrebări;
- prag 70 puncte;
- conținut inclusiv din bibliografie, logică și gramatică.

Regulamente/hotărâri istorice confirmă existența unei baze de **minimum 500 de întrebări**, din care se selectează cele 100 de întrebări.

Exemplu document ANEVAR care confirmă baza de 500:  
https://www.anevar.ro/images/_upload/hotarari-cd-2013-actualizat-21-04-2026.pdf

**Problema:** nu a fost identificată banca completă publică de 500 de întrebări.

### Strategie

- cercetăm dacă baza se publică membrilor/candidaților;
- dacă nu, construim itemi originali pe baza bibliografiei și standardelor permise.

**Status:** 🟡 / 🔵.

**Prioritate:** P2.

---

# 27. ISF — DISTRIBUȚIE ASIGURĂRI

Întrebări frecvente ISF:  
https://www.isf.ro/ro/intrebari-frecvente

Format curent:

- test grilă;
- 40 întrebări;
- 40 minute;
- o singură variantă corectă;
- minimum 28 răspunsuri/puncte pentru promovare, conform paginii curente.

Metodologia descrie și platforma de simulare/examinare, însă nu a fost identificată o bancă publică completă reutilizabilă.

### Strategie

🔵 Itemi originali pe baza curriculumului/materialelor oficiale, dacă licențele permit utilizarea acestora ca sursă de cunoaștere.

**Status:** 🔵/🟡.

**Prioritate:** P2.

---

# 28. EDUCAȚIE — BAC / EVALUARE NAȚIONALĂ / TITULARIZARE / DEFINITIVAT

Portal oficial subiecte 2026:  
https://subiecte.edu.ro/2026/

Titularizare:  
https://subiecte.edu.ro/2026/titularizare/

Definitivare:  
https://subiecte.edu.ro/2026/definitivare/Subiecte_si_bareme/

Portalul conține:

- Evaluare Națională;
- BAC;
- simulări;
- Titularizare;
- Definitivat;
- concurs directori/directori adjuncți;
- modele;
- subiecte și bareme.

### Cerere de piață

Titularizare 2026 — Ministerul Educației a raportat 39.996 candidați cu drept de participare.  
Sursă:  
https://www.edu.ro/press_rel_56_26_rezultate_initiale_proba_scrisa_titularizare

Definitivat 2026 — 9.615 candidați cu drept de participare, la 106 discipline.  
Sursă:  
https://edu.ro/press_rel_53_26_rezultate_initiale_proba_scrisa_definitivat

### Restricție explicită de proprietate intelectuală

Termeni:  
https://subiecte.edu.ro/2026/termeni

Ministerul/CNCE declară proprietate intelectuală:

- toate variantele de subiecte;
- toate variantele pentru simulări/pretestări;
- itemii finali;
- băncile de itemi;
- instrumentele de evaluare.

Utilizarea gratuită și fără restricții este precizată pentru activitatea didactică desfășurată la clasă / activități școlare aprobate, nu ca permisiune generală de republicare într-o platformă publică independentă.

**Status:** 🔴 pentru copiere/import direct fără acord.

### Strategie recomandată

Pentru e-test.ro putem construi 🔵 **itemi originali** conform programelor oficiale și competențelor evaluate:

- Teste BAC Biologie;
- BAC Istorie;
- BAC Geografie;
- BAC Română;
- EN VIII Matematică;
- EN VIII Română;
- Titularizare pe discipline;
- Definitivat pe discipline.

Nu copiem itemii ministerului și nu facem itemi care diferă doar cosmetic dacă termenii acoperă și astfel de produse similare.

**Potențial:** enorm, dar necesită producție editorială proprie.

**Prioritate:** P2/P3 după stabilizarea platformei.

---

# 29. CECCAR

Examenul de acces la profesia contabilă este atractiv ca audiență, dar materialele de pregătire principale sunt comercializate de CECCAR (ghid/culegeri).

Nu a fost identificată o bancă oficială completă, publică și clar reutilizabilă comparabilă cu ARR sau ANCOM.

### Strategie

- nu lansăm la început;
- ulterior putem construi itemi proprii pe baza legislației și tematicii dacă drepturile asupra materialelor de bază sunt respectate.

**Status:** 🔴/neclar.

---

# 30. CATALOG EXTINS — DISCOVERY V1 ÎNCHIS

Această rundă a transformat o parte importantă din backlog în surse oficiale identificate. Numărătoarea și starea operațională a fiecărei surse sunt menținute separat în [`e-test.ro_content_inventory.md`](./e-test.ro_content_inventory.md).

## 30.1. Surse cu bănci sau teste publice identificate

### CNCAN — permise de exercitare în domeniul nuclear

- au fost identificate **21 de specialități** cu fișiere distincte de întrebări și de răspunsuri corecte comentate;
- totalul brut măsurat este de **13.673 apariții de itemi**;
- există suprapuneri mari între specialități, deci totalul nu reprezintă întrebări unice;
- PDF-urile sunt text și au potențial mare de automatizare;
- reutilizarea în produs rămâne 🟡 până la clarificarea drepturilor.

Surse: https://www.cncan.ro/ și exemplu de bancă oficială: https://www.cncan.ro/assets/Surse-de-radiatii-ionizante/permise/Rntgendiagnostic-RTG/RTG-Intrebari.pdf

### Colegiul Dieteticienilor — examen național de dietetician autorizat

- banca oficială 2025 conține **263 de întrebări**;
- examenul are 50 de întrebări și 90 de minute; metodologia prevede 75% din banca publicată și 25% itemi nepublicați pentru sesiunile ulterioare;
- în PDF-ul inventariat nu a fost identificat un barem al răspunsurilor corecte;
- formatul cu două coloane cere un parser atent la layout.

Surse: https://www.colegiuldieteticienilor.ro/metodologia-examenului-national-de-dietetician-autorizat/ și https://www.colegiuldieteticienilor.ro/examen-de-dietetician-autorizat-2025/

### OAMGMAMR — examen de grad principal, sesiunea 2026

- sunt publicate **9 specialități**, fiecare cu test de 100 de întrebări și grilă de corectură separată;
- totalul sesiunii 2026 este de **900 de apariții de itemi**;
- acestea sunt teste de sesiune, nu o bancă unică declarată ca atare;
- PDF-urile sunt text și importul este fezabil, cu versionare pe an și specialitate.

Sursă: https://www.oamr.ro/teste-grila-grad-sesiunea2026/

### ARR — atestate profesionale pentru transport

- pagina oficială publică bănci pentru ADR, instructori/profesori, conducători auto, consilieri de siguranță, manageri și studii de caz;
- subsetul numărat exact până acum însumează **cel puțin 4.621 de apariții de itemi**;
- catalogul ARR este mai larg decât subsetul numărat, iar unele documente au structură nenumerotată sau layout atipic;
- fișierele temporare de corecții trebuie tratate ca patch-uri peste versiunea sursei, nu ca bănci independente;
- baremul complet nu a fost identificat în PDF-urile inventariate.

Sursă: https://www.arr.ro/servicii_doc_164_eliberare-atestate_pg_0.htm

### Permis de vânător

- setul oficial conține **1.000 de întrebări**, fiecare cu trei variante;
- răspunsul corect este marcat prin formatare bold în documentul Word;
- importul trebuie să păstreze run-urile Word, nu doar textul simplu;
- există o neconcordanță de versionare între pagina actualizată în 2026 și antetul documentului, care trebuie rezolvată înainte de publicare.

Sursă: https://mmediu.ro/domenii/mediu/management-cinegetic/permis-de-vanatoare/

### ANCOM — radioamatori

- banca de radiotehnică inventariată conține **632 de întrebări**;
- răspunsul corect este marcat cu `@`;
- pagina curentă încă trimite la fișierul din 2017, care trebuie etichetat explicit cu versiunea sa;
- ANCOM are un disclaimer care permite reproducerea cu indicarea sursei, ceea ce face această sursă cel mai bun candidat juridic pentru primul import.

Surse: https://ancom.ro/category/autorizare-ro/radioamatori/ și https://www.ancom.ro/uncategorized-ro/disclaimer/

## 30.2. Verticale oficiale validate pentru cercetare și conținut original

| Verticală | Instituție / examen | Ce s-a validat | Direcție recomandată |
|---|---|---|---|
| Fizioterapie | autorizare profesională | cadru/metodologie oficială | itemi proprii + monitorizare sesiuni |
| ISCIR | operator RSVTI și alte ocupații | exemple oficiale de întrebări; listă largă de ocupații reglementate | inventar separat pe ocupație |
| ISC | diriginți de șantier / RTE | proceduri și sesiuni oficiale | itemi proprii din tematică; arhivare pe an |
| ANRE | gaze naturale | pagină oficială pentru autorizarea persoanelor fizice | extindere după electricieni |
| RAR | ITP, tahografe, conducători de atelier | proceduri și certificări distincte | investigație pe fiecare examen |
| Construcții | auditori energetici, verificatori și experți tehnici | proceduri oficiale actuale | conținut editorial versionat legislativ |
| Feroviar | CENAFER | centru și examinări oficiale | cartografiere examene/bănci |
| Naval | ANR maritim și fluvial | fluxuri oficiale de examinare | numai conținut original sau acord explicit |
| Financiar | ISF piață de capital | rută oficială de certificare | cercetare modele/bănci publice |
| Proprietate intelectuală | consilier OSIM | profesie și evidență oficială | verificare examen, tematică, arhivă |
| Cadastru | autorizare ANCPI | regulament oficial | itemi originali pe categorii |
| Agricultură | utilizatori profesioniști de produse fitosanitare | instruire obligatorie oficială | verificare format examen și furnizori |
| Tehnic | F-Gas | organism profesional și certificări | clarificare curriculum, examen, drepturi |

Surse oficiale principale:

- Fizioterapie: https://legislatie.just.ro/Public/DetaliiDocument/306348
- ISCIR RSVTI: https://iscir.ro/exemple-intrebari-teste-operator-rsvti
- ISC diriginți: https://isc.gov.ro/files/2016/Autorizari/procedura%20diriginti%20de%20santier%202011.pdf
- ISC RTE 2026: https://isc.gov.ro/autorizare_rte_sesiuni_2026.html
- ANRE gaze: https://anre.ro/participanti-la-piata-de-energie/persoane-fizice/gaze-naturale/
- RAR ITP: https://www.rarom.ro/?page_id=1340
- RAR tahograf: https://www.rarom.ro/?page_id=298579
- RAR conducători atelier: https://www.rarom.ro/?page_id=774
- auditori energetici pentru clădiri: https://legislatie.just.ro/Public/DetaliiDocumentAfis/309527
- verificatori/experți tehnici: https://legislatie.just.ro/Public/FormaPrintabila/00000G0FG2PL6OWEQ5Q0IRQKG0R5L0WK
- CENAFER: https://www.cenafer.ro/
- ANR maritim: https://portal.rna.ro/personal-navigant/maritim/examinare-personal-navigant-maritim/
- ANR fluvial: https://portal.rna.ro/personal-navigant/fluvial/examinare-personal-navigant-fluvial/
- ISF piață de capital: https://www.isf.ro/ro/certificare-piata-de-capital
- OSIM: https://www.osim.ro/consilieri-in-proprietate-industriala
- ANCPI: https://legislatie.just.ro/Public/DetaliiDocument/117871
- fitosanitar: https://anfdf.ro/cursurile-de-instruire-pentru-utilizarea-produselor-de-protectie-a-plantelor-obligatorii-pentru-toti-utilizatorii-profesionisti/
- F-Gas: https://www.agfro.ro/

## 30.3. Admiteri și concursuri suplimentare validate

- colegii militare: https://dgmru.mapn.ro/pages/colegii-militare
- Psihologie — Universitatea din București: https://fpse.unibuc.ro/studii-universitare-de-licenta/
- Informatică UBB — subiecte admitere 2026: https://www.cs.ubbcluj.ro/subiectele-probei-scrise-din-cadrul-concursului-de-admitere-nivel-licenta-sesiunea-iulie-2026/
- ASE — admitere licență 2026: https://ase.ro/admitere/licenta-iulie-2026/
- Politehnica București / ETTI — faza a II-a: https://etti.upb.ro/cp_services/faza-2/
- detectiv particular — exemplu de anunț/examen: https://b.politiaromana.ro/files/pages_files/26-01-21-11-20-09Anunt_examen_trim_4_detectivi_particulari.pdf

## 30.4. Limite confirmate

- băncile oficiale pentru unele examene aeronautice sunt confidențiale; se poate lucra cu programa și itemi originali, nu cu extragerea băncii;
- testele psihologice licențiate nu trebuie reproduse;
- materialele ANR au condiții restrictive și cer acord sau conținut original;
- pentru executorii judecătorești, formatul clasic este în principal scris și nu oferă încă o oportunitate clară de bancă grilă;
- pentru regimul armelor nu a fost identificată o bancă oficială publică suficientă pentru import.

---

# 31. MODEL DE DATE RECOMANDAT

Modelul trebuie construit din prima pentru multi-domeniu și versionare.

## Entități principale

```text
domains
    id
    name
    slug
    description

institutions
    id
    name
    type
    website

subjects
    id
    name
    slug

exams
    id
    domain_id
    institution_id nullable
    name
    slug
    description
    active

exam_sessions
    id
    exam_id
    year
    session_name
    exam_date nullable
    rules_json

question_sets
    id
    exam_session_id
    name
    source_document_id
    variant

questions
    id
    canonical_question_id nullable
    text
    explanation nullable
    question_type
    status
    valid_from nullable
    valid_until nullable
    is_official
    is_original
    last_verified_at

answers
    id
    question_id
    label
    text
    is_correct

question_subject
question_exam
question_tags

sources
    id
    authority
    title
    url
    published_at
    fetched_at
    source_type
    reuse_status
    license_text nullable
    notes

source_documents
    id
    source_id
    local_path/storage_key
    mime_type
    checksum
    version

legal_references
    id
    question_id
    act_name
    article
    paragraph nullable
    url nullable

user_question_stats
    user_id
    question_id
    seen_count
    correct_count
    incorrect_count
    last_seen_at
    mastery_score

quiz_attempts
quiz_attempt_questions
favorites
study_plans
```

## Tipuri de întrebare

- `single_choice`
- `multiple_choice`
- `true_false`
- `image_single_choice`
- `image_multiple_choice`
- `numeric`
- `free_text` (pentru viitor)
- `case_study`

## Status sursă / drepturi

```text
reuse_status:
- explicit_allowed
- permission_received
- official_public_unclear
- restricted
- original_content
- research_only
```

---

# 32. VERSIONAREA ÎNTREBĂRILOR

Este obligatorie pentru:

- drept;
- fiscalitate;
- transport;
- legislație rutieră;
- energie;
- administrație;
- examene care își schimbă programa anual.

O întrebare nu trebuie suprascrisă când se schimbă răspunsul corect. Se creează o nouă versiune.

Exemplu:

```text
canonical_question_id: 1421
version 1: valid 2024-01-01 → 2025-06-30
version 2: valid 2025-07-01 → null
```

Astfel putem avea simultan:

- „Examen Barou 2023” exact cum a fost;
- „Învățare Barou 2026” doar cu întrebări valabile acum.

---

# 33. PIPELINE RECOMANDAT DE IMPORT

Nu recomand un scraper monolitic. Folosim conector separat pentru fiecare autoritate/sursă.

```text
Source discovery
    ↓
Download source document
    ↓
Store immutable original + checksum
    ↓
Parser per source
    ↓
Normalize questions/answers
    ↓
Validation rules
    ↓
Manual review queue
    ↓
Publish
```

### Comenzi Laravel propuse

```bash
php artisan sources:sync arr
php artisan sources:sync ancom
php artisan sources:sync oamgmamr
php artisan sources:sync unbr
php artisan sources:sync csm
php artisan sources:sync umfcd

php artisan questions:validate
php artisan questions:deduplicate
php artisan questions:review-stale
```

### Reguli importante

- păstrăm documentul original;
- stocăm URL-ul și data accesării;
- calculăm SHA-256;
- detectăm modificări la sursă;
- importul nou nu șterge istoria;
- întrebările oficiale sunt `official=true` numai dacă sursa oficială le publică efectiv ca atare;
- întrebările create editorial sunt `original=true` și nu trebuie prezentate drept oficiale.

---

# 34. SISTEMUL DE SIMULARE

Regulile examenului nu trebuie hardcodate în controller.

Exemplu `rules_json`:

```json
{
  "question_count": 40,
  "time_limit_minutes": 40,
  "passing_type": "absolute",
  "passing_score": 28,
  "selection": {
    "strategy": "random_by_subject_weight"
  },
  "navigation": {
    "allow_back": true
  }
}
```

Astfel același engine poate simula:

- permis;
- CCF;
- ISF;
- rezidențiat;
- admitere UMF;
- Barou;
- vânătoare.

---

# 35. SEO / STRUCTURĂ URL

```text
/
/examene

/auto
/auto/permis-categoria-b
/transport/adr
/transport/cpi-marfa

/medicina
/medicina/admitere
/medicina/admitere/umf-carol-davila
/medicina/admitere/umf-cluj
/medicina/rezidentiat
/medicina/grad-principal

/drept
/drept/admitere-unibuc
/drept/admitere-ubb
/drept/barou
/drept/inm
/drept/sng
/drept/notar-stagiar

/administratie/anfp
/finante/auditor-financiar
/fiscal/consultant-fiscal
/radio/radioamator
/vanatoare/permis-vanator
/drone/a1-a3
/drone/a2
```

Pentru sesiuni istorice:

```text
/drept/barou/2026-aprilie
/medicina/admitere/umfcd/2026
/rezidentiat/2025/medicina
```

---

# 36. ROADMAP RECOMANDAT

Inventarul operațional din [`e-test.ro_content_inventory.md`](./e-test.ro_content_inventory.md) devine sursa de adevăr pentru numărul de itemi, formatele sursă, existența baremelor, drepturi și stadiul importului. Roadmap-ul de mai jos rămâne ordinea de produs și poate fi reprioritizat după deduplicare și verificarea drepturilor.

## Wave 0 — fundația

- multi-domain data model;
- auth;
- quiz engine configurabil;
- source registry;
- import pipeline;
- review/admin;
- progress/attempts;
- SEO pages.

## Wave 1 — lansare cu identitate multi-domeniu

### Auto & transport
- Permis auto (după clarificarea sursei/licenței)
- ARR

### Medicină
- Admitere UMF
- Grad principal asistenți

### Drept
- Admitere Drept UB
- Admitere Drept UBB
- Barou

### Alte certificări
- Vânătoare
- Radioamator ANCOM
- ANFP

Scop: utilizatorul trebuie să înțeleagă din prima zi că e-test.ro este o **platformă națională de pregătire**, nu un produs de nișă auto.

## Wave 2

- Rezidențiat
- INM
- SNG
- Notariat
- UNPIR
- CAFR
- Consultant fiscal
- ANRE
- Drone
- Academia de Poliție / MAI

## Wave 3

- BAC
- Evaluare Națională
- Titularizare
- Definitivat

Pentru Wave 3 se folosește conținut original sau acord explicit, nu copierea băncilor Ministerului.

---

# 37. ORDINE RECOMANDATĂ DUPĂ RAPORT CERERE / CONȚINUT DISPONIBIL

### Tier A — lansare rapidă

1. ARR
2. Radioamator ANCOM
3. Vânătoare
4. Grad principal asistenți
5. Admitere Drept UB/UBB
6. Barou
7. ANFP
8. Admitere Medicină (după verificarea drepturilor de republicare ale fiecărei universități)

### Tier B — mare valoare, import/versionare mai dificilă

9. Rezidențiat
10. INM
11. SNG
12. Consultant fiscal
13. CAFR
14. ANRE
15. MAI
16. Notariat
17. UNPIR

### Tier C — trebuie creat conținut propriu / acord

18. Drone
19. ISF
20. ANEVAR
21. ANR ambarcațiuni — numai după acord
22. Titularizare
23. Definitivat
24. BAC
25. Evaluare Națională
26. CECCAR

---

# 38. CHECKLIST PENTRU ORICE EXAMEN NOU

Înainte de a adăuga o verticală nouă:

- [ ] examenul este recurent?
- [ ] câți candidați are aproximativ?
- [ ] există test grilă / răspuns obiectiv?
- [ ] instituția publică subiectele?
- [ ] publică baremul?
- [ ] există arhivă istorică?
- [ ] există o bancă completă?
- [ ] formatul este HTML / PDF text / PDF scanat / JSON?
- [ ] drepturile de reutilizare sunt clare?
- [ ] trebuie cerut acord?
- [ ] conținutul se schimbă legislativ?
- [ ] avem nevoie de versionare anuală?
- [ ] putem genera legal itemi originali din programa oficială?
- [ ] există suficient search intent pentru SEO?
- [ ] simularea poate reproduce exact regulile examenului?

---

# 39. SURSE PRINCIPALE — INDEX RAPID

## Auto / transport
- DGPCI: https://dgpci.mai.gov.ro/
- ARR: https://www.arr.ro/servicii_doc_164_eliberare-atestate_pg_0.htm
- testare ARR: https://testare.arr.ro/
- RoD-TAL GitHub: https://github.com/vladman-25/RoD-TAL
- RoD-TAL dataset: https://huggingface.co/datasets/GRAI-UNSTPB/RoD-TAL

## Mediu / hobby / tehnic
- Vânătoare: https://mmediu.ro/domenii/mediu/management-cinegetic/permis-de-vanatoare/
- ANCOM radioamatori: https://www.ancom.ro/category/autorizare-ro/radioamatori/
- ANCOM disclaimer: https://www.ancom.ro/uncategorized-ro/disclaimer/
- CNCAN: https://www.cncan.ro/
- ISCIR RSVTI: https://iscir.ro/exemple-intrebari-teste-operator-rsvti
- ISC RTE: https://isc.gov.ro/autorizare_rte_sesiuni_2026.html
- RAR ITP: https://www.rarom.ro/?page_id=1340
- CENAFER: https://www.cenafer.ro/
- ANR CAA: https://nou.rna.ro/personal-navigant/conducator-ambarcatiune-de-agrement/examinare-caa/
- ANR termeni: https://portal.rna.ro/Pagini/Termeni-si-conditii.aspx
- AACR drone: https://www.caa.ro/
- ANRE electricieni: https://anre.ro/participanti-la-piata-de-energie/persoane-fizice/energie-electrica/electricieni/examene/

## Medicină
- Dietetician autorizat: https://www.colegiuldieteticienilor.ro/examen-de-dietetician-autorizat-2025/
- UMFCD: https://umfcd.ro/
- UMF Cluj admitere: https://umfcluj.ro/programe-studii/admitere-licenta/admitere-iulie-2026/
- UMF Iași: https://www.umfiasi.ro/
- UMF Timișoara: https://www.umft.ro/ro/admitere-ciclul-licenta-2026/
- UMFST: https://adminfo.umfst.ro/rezultate-admitere-2026/
- UMF Craiova: https://www.umfcv.ro/ro/admitere/admitere-licenta-2026/
- Rezidențiat: https://rezidentiat.umfcd.ro/afisares/index/rezidentiat
- OAMGMAMR grad principal: https://www.oamr.ro/teste-grila-grad-sesiunea2026/

## Drept
- Drept UB arhivă: https://drept.unibuc.ro/subiecte-de-la-concursurile-anterioare-s103-ro.htm
- Drept UBB: https://law.ubbcluj.ro/admitere/admitere-licenta/
- INPPA: https://inppa.ro/examene/
- UNBR: https://unbr.ro/
- CSM INM: https://csm1909.ro/PageDetails/12817/Concurs-de-admitere-la-Institutul-Na%C5%A3ional-al-Magistraturii%2C-organizat-%C3%AEn-perioada--iulie-2026-%E2%80%93-aprilie-2027
- CSM SNG: https://www.csm1909.ro/PageDetails/12636/
- INR: https://www.institutulnotarial.ro/site/concurs-notari-stagiari-2026/
- UNPIR: https://www.unpir.ro/examen-pentru-dobandirea-calitatii-de-practician

## Administrație / financiar
- ANFP teste: https://concurs-national.anfp.gov.ro/materiale-utile/teste-antrenament/
- CAFR: https://cafr.ro/examen-de-competenta-profesionala-2026/
- CCF: https://www.ccfiscali.ro/
- ANEVAR: https://www.anevar.ro/p/educatie-si-evenimente/formare-profesionala/cum-devin-membru-stagiar-anevar/cum-devin-membru-stagiar
- ISF: https://www.isf.ro/ro/intrebari-frecvente

## Educație
- Subiecte examene naționale: https://subiecte.edu.ro/2026/
- Termeni/copyright: https://subiecte.edu.ro/2026/termeni

## MAI
- Admitere Academia de Poliție: https://admitere.academiadepolitie.ro/
- HUB MAI: https://hub.mai.gov.ro/

---

# 40. CONCLUZIE

e-test.ro are suficientă materie pentru a deveni o platformă națională mare de testare și pregătire, nu un proiect limitat la o singură nișă.

Cele mai puternice trei verticale strategice sunt:

1. **Auto & transport** — permis + ARR;
2. **Medicină** — admitere UMF + rezidențiat + grad principal;
3. **Drept** — admitere facultate + Barou + INM + SNG + notariat.

La acestea se adaugă un grup excelent de certificări cu intenție mare de căutare:

- ANFP;
- permis vânător;
- radioamator;
- CAFR;
- consultant fiscal;
- ANRE;
- drone;
- MAI.

Diferențiatorul e-test.ro nu trebuie să fie doar numărul de întrebări, ci **încrederea**:

- fiecare întrebare are sursă;
- fiecare sursă este versionată;
- utilizatorul vede dacă întrebarea este oficială sau originală;
- întrebările vechi nu sunt amestecate cu cele curente;
- regulile simulării corespund sesiunii reale;
- platforma monitorizează actualizările legislative și marchează întrebările care necesită reverificare.

Discovery v1 confirmă și o a patra familie strategică: **autorizări tehnice și profesionale**, unde CNCAN, ISCIR, ISC, RAR, ANRE și certificările conexe pot alimenta un catalog cu intenție mare și concurență editorială redusă.

Inventarierea a început cu șase surse oficiale și **21.089 de apariții brute de itemi măsurate**. Această cifră nu reprezintă întrebări unice sau automat publicabile: include suprapuneri între specialități, teste de sesiune și un subset incomplet ARR. Următoarea etapă este deduplicarea, legarea răspunsurilor și verificarea drepturilor.

Acesta este fundamentul potrivit pentru a transforma e-test.ro într-un produs gratuit, util și scalabil pe zeci de examene.
