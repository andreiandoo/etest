# e-test.ro — Inventar de conținut

**Status:** inventariere începută  
**Data:** 19 septembrie 2026  
**Domeniu:** surse oficiale, documente, volum, răspunsuri, drepturi și fezabilitatea importului  
**Document strategic asociat:** [`e-test.ro_research_dossier.md`](./e-test.ro_research_dossier.md)

---

## 1. Cum se citește inventarul

Inventarul separă trei valori care nu trebuie confundate:

- **apariții brute de itemi** — toate întrebările numărate în fișierele sursă;
- **întrebări unice** — rezultat disponibil numai după normalizare și deduplicare;
- **întrebări publicabile** — subsetul care trece verificarea drepturilor, validarea răspunsului și controlul editorial.

### Stări de numărare

- `counted_exact` — numărul din document a fost determinat exact;
- `counted_raw_requires_dedupe` — număr exact de apariții, dar există suprapuneri;
- `counted_subset` — numai o parte a catalogului a fost numărată;
- `count_pending` — sursa a fost identificată, însă parserul sau verificarea manuală lipsesc.

### Drepturi

- 🟢 reutilizare permisă explicit, cu respectarea condițiilor sursei;
- 🟡 document public oficial, dar dreptul de republicare nu este clar;
- 🔴 restricție explicită sau conținut licențiat/confidențial;
- 🔵 se recomandă conținut original construit din programă/legislație.

Acest inventar nu este o opinie juridică. Publicarea se face numai după verificarea condițiilor aplicabile fiecărei surse și versiuni.

---

## 2. Situația măsurată acum

| ID | Verticală | Examen / instituție | Versiune | Format | Volum măsurat | Răspunsuri | Explicații | Drepturi | Import | Stare |
|---|---|---|---|---|---:|---|---|---|---|---|
| CNCAN-PE | Radioprotecție | permise de exercitare CNCAN, 21 specialități | curent la 2026-09-19 | 42 PDF text + fișiere auxiliare | 13.673 brut | da, fișiere separate | da, comentate | 🟡 | ridicat | `counted_raw_requires_dedupe` |
| ARR-ATEST | Transport | atestate ARR | curent la 2026-09-19 | PDF text, layout mixt | ≥4.621 în subset | neidentificat complet | nu în fișierele analizate | 🟡 | mixt | `counted_subset` |
| MMAP-VAN | Cinegetic | permis de vânător | pagină 2026 / document cu istoric mai vechi | DOC | 1.000 | da, prin bold | nu | 🟡 | mediu-ridicat | `counted_exact` |
| ANCOM-RAD | Radio | radioamator, radiotehnică | 2017, încă legat de pagina curentă | PDF text | 632 | da, marcaj `@` | nu | 🟢 | ridicat | `counted_exact` |
| CDR-DIET | Medical | dietetician autorizat | 2025 | PDF text, două coloane | 263 | nu a fost găsit barem | nu | 🟡 | ridicat, parser layout-aware | `counted_exact` |
| OAMR-GP | Medical | grad principal OAMGMAMR | sesiunea 2026, 9 specialități | 18 PDF text | 900 | da, grile separate | nu | 🟡 | ridicat | `counted_exact` |

**Total măsurat:** **21.089 apariții brute de itemi**. Totalul este o măsură de volum sursă, nu de conținut unic sau publicabil. CNCAN are suprapuneri mari între specialități, ARR este încă incomplet, iar testele OAMGMAMR pot reutiliza itemi între sesiuni.

---

## 3. CNCAN — permise de exercitare

**Sursă instituțională:** https://www.cncan.ro/  
**Exemplu întrebări RTG:** https://www.cncan.ro/assets/Surse-de-radiatii-ionizante/permise/Rntgendiagnostic-RTG/RTG-Intrebari.pdf  
**Exemplu răspunsuri RTG:** https://www.cncan.ro/assets/Surse-de-radiatii-ionizante/permise/Rntgendiagnostic-RTG/RTG-Raspunsuri.pdf

Au fost identificate 21 de bănci pe specialități, fiecare cu fișier de întrebări și fișier de „Răspunsuri corecte (comentate)”.

| Cod inventar | Specialitate / grup | Itemi bruți |
|---|---|---:|
| CNCAN-AFX | Analize fizice | 603 |
| CNCAN-AP | Acceleratori de particule | 566 |
| CNCAN-CRT | Curieterapie / brahiterapie | 767 |
| CNCAN-MN | Medicină nucleară | 752 |
| CNCAN-MRIVSD | Măsurări / surse, categorie generală | 769 |
| CNCAN-MRIVSII | Instalații industriale cu surse închise | 809 |
| CNCAN-MRIVSIM | Instalații medicale cu surse închise | 845 |
| CNCAN-MRIVXI | Generatori RX industriali | 603 |
| CNCAN-MRIVXM | Generatori RX medicali | 904 |
| CNCAN-MSD | Marcări / surse deschise | 676 |
| CNCAN-RAD | Radiochimie | 675 |
| CNCAN-RI | Radiologie intervențională | 638 |
| CNCAN-RTG | Röntgendiagnostic | 777 |
| CNCAN-RTGD | Röntgendiagnostic dentar | 483 |
| CNCAN-RTGF | Ftiziologie | 583 |
| CNCAN-RTT | Röntgenterapie | 484 |
| CNCAN-CNDSICB | Control bagaje cu surse închise | 347 |
| CNCAN-CNDXCB | Control bagaje cu generatori RX | 341 |
| CNCAN-TLTA | Teleterapie / acceleratori | 778 |
| CNCAN-TSD | Terapie cu surse deschise | 676 |
| CNCAN-VET | Radiologie veterinară | 597 |
| **Total brut** | **21 bănci** | **13.673** |

Observații operaționale:

- o mare parte din întrebările generale de radioprotecție se repetă între specialități;
- deduplicarea trebuie făcută atât exact, cât și fuzzy, păstrând relația many-to-many dintre întrebare și specialitate;
- răspunsurile și explicațiile se pot lega determinist după cod/număr, dar trebuie verificate diferențele de ediție;
- bibliografia apare separat pentru unele specialități și trebuie stocată la nivel de set/versiune;
- înaintea republicării este necesară confirmarea drepturilor.

**Următorul pas:** descărcare controlată, SHA-256 pe fiecare fișier, parser PDF, normalizare, deduplicare și solicitare de clarificare a drepturilor.

---

## 4. ARR — atestate profesionale

**Pagina oficială:** https://www.arr.ro/servicii_doc_164_eliberare-atestate_pg_0.htm

Pagina publică explicit „baza de întrebări” pentru mai multe atestate. Catalogul include bănci în română și engleză, studii de caz și documente temporare de corecții.

### Subset numărat exact

| Set | Apariții numărate | Pagini | Observație |
|---|---:|---:|---|
| ADR | 920 | 127 | întrebări numerotate |
| Instructori / profesori | 422 | 68 | fișier `aic_.pdf` |
| Agabaritic | 77 | 10 | bancă standard |
| Închiriere | 187 | 23 | bancă standard |
| Marfă | 355 | 44 | bancă standard |
| Persoane | 329 | 41 | bancă standard |
| Consilieri de siguranță | 1.589 | 213 | cea mai mare bancă ARR măsurată |
| Manager taxi / închiriere | 240 | 31 | bancă standard |
| Studii de caz CPI marfă | 122 | 19 | markeri de caz/item |
| Studii de caz CPI persoane | 137 | 22 | markeri de caz/item |
| CPC marfă, versiune engleză | 243 | 42 | numărătoarea cere validarea structurii |
| **Total subset** | **4.621** |  | catalogul complet este mai mare |

Alte seturi identificate, dar încă nenumerate robust: troleibuz, vehicule avariate, versiuni engleze pentru taxi/închiriere și studii pentru consilieri/manageri.

Observații operaționale:

- unele PDF-uri au întrebări nenumerotate sau structură atipică și cer parser dedicat;
- fișierele temporare de corecții conțin câte 6 întrebări și trebuie modelate ca **overlay/patch**, nu ca bănci separate;
- răspunsurile corecte nu au fost identificate ca marcaje în PDF-urile analizate; trebuie verificată platforma oficială de testare sau o sursă separată;
- drepturile de republicare nu sunt explicite.

**Următorul pas:** manifest complet al fișierelor, numărare pentru seturile rămase, identificarea baremelor și model de aplicare a corecțiilor pe versiuni.

---

## 5. Permis de vânător

**Pagina oficială:** https://mmediu.ro/domenii/mediu/management-cinegetic/permis-de-vanatoare/  
**Document:** https://www.mmediu.ro/app/webroot/uploads/files/Set%20de%20%C3%AEntreb%C4%83ri%20%C8%99i%20r%C4%83spunsuri%20privind%20examenul%20de%20v%C3%A2n%C4%83tor%20actualizate%20conform%20Legii%20nr.%20171%20din%202022.doc

- 1.000 de întrebări numerotate;
- trei variante pentru fiecare întrebare;
- răspunsul corect este indicat prin formatare bold;
- document Word de aproximativ 108 pagini;
- pagina este datată 16 iulie 2026, în timp ce antetul documentului păstrează referințe istorice mai vechi și mențiunea actualizării conform Legii nr. 171/2022.

Importul nu trebuie făcut prin extragere de text simplu, deoarece s-ar pierde marcajul răspunsului corect. Fluxul recomandat este DOC → DOCX, apoi parcurgerea paragrafelor și a run-urilor cu proprietatea bold păstrată.

**Următorul pas:** reconcilierea versiunii juridice, checksum, parser de formatare și verificarea drepturilor de republicare.

---

## 6. ANCOM — radioamator, radiotehnică

**Pagina oficială:** https://ancom.ro/category/autorizare-ro/radioamatori/  
**Banca inventariată:** https://www.ancom.ro/wp-content/uploads/2010/07/subiecte_radiotehnica_09_02_2017.pdf  
**Condiții de reutilizare:** https://www.ancom.ro/uncategorized-ro/disclaimer/

- 632 de întrebări, 109 pagini;
- patru variante de răspuns;
- un singur răspuns corect, marcat cu `@`;
- documentul este folosit pentru proba de electronică și radiotehnică la clasele III și II;
- fișierul este din 2017, dar pagina oficială curentă încă îl referențiază;
- disclaimerul ANCOM permite reproducerea cu indicarea sursei, în condițiile precizate acolo.

**Următorul pas:** primul proof-of-concept de import complet: sursă → parser → validare → quiz, cu versiunea `2017-02-09` vizibilă în produs.

---

## 7. Dietetician autorizat

**Metodologie:** https://www.colegiuldieteticienilor.ro/metodologia-examenului-national-de-dietetician-autorizat/  
**Pagina sesiunii 2025:** https://www.colegiuldieteticienilor.ro/examen-de-dietetician-autorizat-2025/  
**Banca PDF:** https://www.colegiuldieteticienilor.ro/wp-content/uploads/2025/10/Examen_dietetician_Decembrie-2025.pdf

- 263 de întrebări, 52 de pagini;
- itemi cu răspuns simplu și/sau multiplu;
- nu a fost identificat un barem al răspunsurilor corecte în PDF;
- examenul are 50 de întrebări și 90 de minute;
- metodologia indică, pentru examenele ulterioare, 75% întrebări din banca publicată și 25% întrebări nepublicate; pragul de promovare este 60% începând cu al doilea examen;
- layout-ul pe două coloane impune reconstrucția ordinii de lectură.

**Următorul pas:** căutarea baremului/sesiunilor anterioare și parser cu coordonate de pagină, urmat de verificare manuală pe eșantion.

---

## 8. OAMGMAMR — grad principal 2026

**Pagina oficială:** https://www.oamr.ro/teste-grila-grad-sesiunea2026/

Pagina publicată la 7 septembrie 2026 conține câte un test de 100 de întrebări și o grilă de corectură pentru nouă specialități:

1. Asistență medicală generală;
2. Balneofizioterapie;
3. Farmacie;
4. Igienă și sănătate publică;
5. Laborator;
6. Moașă;
7. Nutriție și dietetică;
8. Radiologie;
9. Stomatologie.

Totalul sesiunii este de 900 de apariții de itemi. Documentele sunt teste efectiv utilizate într-o sesiune și trebuie modelate ca `exam_session`, nu ca bancă oficială canonică. Arhiva anilor anteriori trebuie inventariată separat, iar duplicatele dintre ani detectate.

**Următorul pas:** descărcarea celor 18 PDF-uri, legarea test–grilă, validarea pe specialitate și extinderea inventarului la sesiunile anterioare.

---

## 9. Câmpuri obligatorii pentru registrul machine-ready

Fiecare document sursă va avea cel puțin:

```yaml
source_id: string
institution: string
exam: string
specialty: string|null
source_page_url: url
document_url: url
retrieved_at: datetime
published_at: date|null
effective_from: date|null
version_label: string|null
file_format: pdf|doc|docx|html|other
sha256: string
page_count: integer|null
item_count_raw: integer|null
count_status: counted_exact|counted_raw_requires_dedupe|counted_subset|count_pending
answer_key: embedded|separate|not_found|not_applicable
explanations: full|partial|none|unknown
rights_status: green|yellow|red|blue
import_difficulty: high_automation|mixed|manual
parser_version: string|null
review_status: discovered|downloaded|parsed|validated|rights_cleared|publishable
notes: text
```

La nivel de întrebare trebuie păstrate: identificatorul sursă, numărul original, textul original, variantele în ordine, răspunsul/răspunsurile corecte, explicația, coordonatele paginii, specialitățile asociate, hash-ul normalizat și relația cu versiunea documentului.

---

## 10. Ordinea următoarei tranșe de inventariere

1. **CNCAN:** manifest complet, deduplicare și clarificarea drepturilor;
2. **ARR:** numărarea fișierelor rămase și identificarea mecanismului de răspuns corect;
3. **OAMGMAMR:** arhivele 2021–2025 și deduplicare între sesiuni;
4. **ANCOM:** toate celelalte discipline/clase și versiuni publice;
5. **Dietetician:** sesiuni anterioare și bareme;
6. **Vânătoare:** reconcilierea versiunii documentului cu cadrul legal curent.

### Criteriul de ieșire din inventariere

O sursă poate intra în backlog-ul de import numai când are: fișier original arhivat, checksum, versiune, număr de itemi verificat, răspunsuri mapabile, statut al drepturilor și un eșantion validat manual. Intrarea în producție necesită suplimentar drepturi clarificate și validarea integrală a parserului.
