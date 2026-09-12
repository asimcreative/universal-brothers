# Image Asset Register

**Status: TEMPORARY DEMO ASSETS.** Every photograph listed here is a stand-in
chosen so the client can see the finished visual experience. They are all real
photographs of the real places, correctly licensed and fully traceable — but
none of them is Universal Brothers' own photography, and none of them shows a
specific Universal Brothers hotel, office, group or event.

See [`../audits/IMAGE_ASSET_AUDIT.md`](../audits/IMAGE_ASSET_AUDIT.md) for what
still needs to be supplied by the client and what these are standing in for.

---

## How these were sourced

Discovery and licensing both go through Wikimedia Commons, via the
`en.wikipedia.org` imageinfo API (Commons' own API endpoint is unreachable from
the build machine, but en.wikipedia serves imageinfo for Commons-hosted files
because Commons is its shared file repository). That route was chosen over
picking images out of a search engine for one reason: it returns **machine-readable
licence and authorship metadata with every file**, so the register below is
generated from the same data the download used, and cannot drift from it.

Selection rules applied automatically (`.visual-audit/imgsource.php`):

- Licence must be one a commercial site can honour: CC0, public domain, CC BY,
  CC BY-SA. **GFDL is rejected** — it is a free licence, but it obliges you to
  ship the full licence text alongside the work, which is not appropriate for a
  client brochure site.
- **Provenance must be credible.** A Commons file whose own `Credit` field is a
  Google Images result URL has not established where the photograph came from,
  whatever licence tag it carries. One candidate was rejected on exactly this.
- Large enough to serve at 1600px, and landscape enough to use as a band.

Of the candidates gathered, 215 were rejected: 73 too small, 36 portrait/square,
14 on licence, 1 on provenance, the rest unresolvable.

Every selected photograph was then **looked at** before use — a filename is a
claim, not evidence. That pass replaced nine images that were technically valid
but weak: wrong subject (a side wall instead of Hagia Sophia), portrait-only
(the Burj Khalifa shaft), low resolution, or simply dull.

## How they are stored and served

- Files live in `public/images/photos/`, committed to the repository.
  `storage/app/public` is gitignored, so nothing placed there would ever reach
  the production server, which deploys by `git fetch` + `reset --hard`. The
  production host is also missing PHP's `fileinfo` extension, which makes the
  admin upload path unreliable there — these assets must not depend on it.
- Each photograph is rendered to **WebP at 1600px and 800px** and served with
  `srcset`/`sizes`, so a phone downloads the small rendition. Total library:
  **45 photographs, 11.9 MB** across both widths.
- `App\Support\SiteImagery` chooses which photograph a given page, package,
  city, Mashaer location or transport leg gets, from the package's **own data**.
  Nothing keys off a slug or an id.
- **An uploaded CMS image always wins.** The moment the client uploads a real
  cover for a package, or a hotel supplies its own photography, `SiteImagery::resolve()`
  uses it and this library steps aside. Nothing here has to be unpicked.

---

## Register

| Key | Subject | Used in | Source file | Licence | Attribution required | Author |
|---|---|---|---|---|---|---|
| `arafat` | Jabal al-Rahmah on the plain of Arafat | `SiteImagery.php`<br>`form.blade.php` | [Jabal-e-Rehmat (Mount of Mercy Mount Arafat).jpg](https://commons.wikimedia.org/wiki/File:Jabal-e-Rehmat_(Mount_of_Mercy_Mount_Arafat).jpg) | CC BY-SA 4.0 | Yes | Fahad Faisal |
| `azerbaijan-baku` | The Baku waterfront, Azerbaijan | `SiteImagery.php` | [Baku, Azerbaiyán, 2016-09-26, DD 207-209 PAN.jpg](https://commons.wikimedia.org/wiki/File:Baku,_Azerbaiy%C3%A1n,_2016-09-26,_DD_207-209_PAN.jpg) | CC BY-SA 4.0 | Yes | Diego Delso |
| `china-wall` | The Great Wall of China at Jinshanling | `SiteImagery.php` | [The Great Wall of China at Jinshanling-edit.jpg](https://commons.wikimedia.org/wiki/File:The_Great_Wall_of_China_at_Jinshanling-edit.jpg) | CC BY-SA 3.0 | Yes | Severin.stalder |
| `dubai` | The Dubai Marina skyline | `SiteImagery.php` | [Dubai Marina Skyline.jpg](https://commons.wikimedia.org/wiki/File:Dubai_Marina_Skyline.jpg) | CC BY 2.0 | Yes | Norlando Pobre |
| `egypt-pyramids` | The pyramids of Giza, Egypt | `SiteImagery.php` | [Pyramids of Giza, Egypt.jpg](https://commons.wikimedia.org/wiki/File:Pyramids_of_Giza,_Egypt.jpg) | CC BY-SA 4.0 | Yes | Kimberlym21 |
| `europe` | Paris at night | `SiteImagery.php` | [Paris Night.jpg](https://commons.wikimedia.org/wiki/File:Paris_Night.jpg) | CC BY-SA 4.0 | Yes | Benh LIEU SONG |
| `europe-alps` | The Matterhorn in the Swiss Alps | `SiteImagery.php` | [Matterhorn from Domhütte - 2.jpg](https://commons.wikimedia.org/wiki/File:Matterhorn_from_Domh%C3%BCtte_-_2.jpg) | CC BY-SA 3.0 | Yes | Photo: chil, on Camptocamp.org
Derivative work:Zacharie Grossen |
| `fairy-meadows` | Fairy Meadows beneath Nanga Parbat | `SiteImagery.php` | [Fairy Meadows GB Pakistan.jpg](https://commons.wikimedia.org/wiki/File:Fairy_Meadows_GB_Pakistan.jpg) | CC BY-SA 4.0 | Yes | Usmanbukhari3362 |
| `haram-courtyard` | The Great Mosque of Makkah and its courtyard | `SiteImagery.php`<br>`awards.blade.php`<br>`home.blade.php` | [Great Mosque of Mecca1.jpg](https://commons.wikimedia.org/wiki/File:Great_Mosque_of_Mecca1.jpg) | CC BY-SA 4.0 | Yes | Saudipics.com |
| `haram-dusk` | Masjid al-Haram at dusk with pilgrims circling the Kaaba | `SiteImagery.php`<br>`home.blade.php`<br>`category.blade.php`<br>`page.blade.php`<br>`testimonials.blade.php` | [Masjid al-Haram 2022.jpg](https://commons.wikimedia.org/wiki/File:Masjid_al-Haram_2022.jpg) | CC0 | No | مريم محمد الغلبان |
| `haram-panorama` | Panorama of Masjid al-Haram in Makkah | `page-cta.blade.php`<br>`category.blade.php`<br>`page.blade.php` | [Masjid al-Haram panorama.JPG](https://commons.wikimedia.org/wiki/File:Masjid_al-Haram_panorama.JPG) | CC BY 3.0 | Yes | Bluemangoa2z at Malayalam Wikipedia |
| `haramain-train` | The Haramain high-speed railway connecting Makkah, Jeddah and Madinah | `SiteImagery.php` | [Haramain High Speed Railway Station Interior 2022.jpg](https://commons.wikimedia.org/wiki/File:Haramain_High_Speed_Railway_Station_Interior_2022.jpg) | CC BY 2.0 | Yes | amanderson2 |
| `hong-kong` | The Hong Kong skyline | `SiteImagery.php` | [Hong Kong Skyline Restitch - Dec 2007.jpg](https://commons.wikimedia.org/wiki/File:Hong_Kong_Skyline_Restitch_-_Dec_2007.jpg) | CC BY 3.0 | Yes | Diliff |
| `hunza-attabad` | Attabad Lake in the Hunza valley in autumn | `SiteImagery.php`<br>`home.blade.php` | [Attabad Lake In autumn.png](https://commons.wikimedia.org/wiki/File:Attabad_Lake_In_autumn.png) | CC0 | No | Mr.KhalidJawed |
| `hunza-baltit` | Baltit Fort above Karimabad in the Hunza valley | `SiteImagery.php` | [Baltit Fort, Karimabad, Hunza, Gilgit Baltistan.jpg](https://commons.wikimedia.org/wiki/File:Baltit_Fort,_Karimabad,_Hunza,_Gilgit_Baltistan.jpg) | CC BY-SA 4.0 | Yes | Fassifarooq |
| `hunza-valley` | The Hunza valley in Gilgit-Baltistan | `SiteImagery.php`<br>`category.blade.php` | [Hunza Valley HDR.jpg](https://commons.wikimedia.org/wiki/File:Hunza_Valley_HDR.jpg) | CC BY-SA 3.0 | Yes | FaizanAhmad |
| `indonesia-bali` | Pura Ulun Danu Bratan on Lake Bratan, Bali | `SiteImagery.php` | [Pura Bratan Bali.jpg](https://commons.wikimedia.org/wiki/File:Pura_Bratan_Bali.jpg) | CC BY-SA 3.0 | Yes | Konstantinos Trovas |
| `islamabad-faisal` | Faisal Mosque in Islamabad | `SiteImagery.php` | [Islamabad - Faisal Mosque.jpg](https://commons.wikimedia.org/wiki/File:Islamabad_-_Faisal_Mosque.jpg) | CC BY-SA 4.0 | Yes | Ijlalahmed |
| `jamarat` | The Jamarat Bridge in Mina | `SiteImagery.php` | [Jamaraat Bridge 2.jpg](https://commons.wikimedia.org/wiki/File:Jamaraat_Bridge_2.jpg) | CC BY-SA 4.0 | Yes | saudipics |
| `jeddah-airport` | King Abdulaziz International Airport in Jeddah | `SiteImagery.php`<br>`affiliations.blade.php` | [JED-Outside-Terminal1.jpg](https://commons.wikimedia.org/wiki/File:JED-Outside-Terminal1.jpg) | CC BY-SA 4.0 | Yes | Tweenet |
| `jordan-petra` | Al-Khazneh, the Treasury at Petra, Jordan | `SiteImagery.php` | [Al khazneh.jpg](https://commons.wikimedia.org/wiki/File:Al_khazneh.jpg) | CC BY-SA 3.0 | Yes | Susanahajer |
| `kaaba-close` | The Kaaba within the Great Mosque of Makkah | `SiteImagery.php`<br>`home.blade.php`<br>`umrah-services.blade.php` | [The Ka'ba, Great Mosque of Mecca, Saudi Arabia (4).jpg](https://commons.wikimedia.org/wiki/File:The_Ka%27ba,_Great_Mosque_of_Mecca,_Saudi_Arabia_(4).jpg) | CC BY 2.0 | Yes | Richard Mortel |
| `kaaba-tawaf` | Pilgrims performing tawaf around the Kaaba during Hajj | `SiteImagery.php`<br>`photo.blade.php`<br>`hajj-services.blade.php`<br>`home.blade.php`<br>`category.blade.php` | [The Kaaba during Hajj.jpg](https://commons.wikimedia.org/wiki/File:The_Kaaba_during_Hajj.jpg) | CC BY-SA 4.0 | Yes | Adli Wahid |
| `kaghan-naran` | Siri Paye meadows above Shogran in the Kaghan valley | `SiteImagery.php` | [Siri Paye, Shogran, Kaghan Valley.jpg](https://commons.wikimedia.org/wiki/File:Siri_Paye,_Shogran,_Kaghan_Valley.jpg) | CC BY-SA 4.0 | Yes | Adeel ur Rehman Mughal |
| `karachi` | Frere Hall in Karachi, where Universal Brothers is based | `SiteImagery.php`<br>`contact.blade.php` | [Frere Hall Karachi. Pakistan.jpg](https://commons.wikimedia.org/wiki/File:Frere_Hall_Karachi._Pakistan.jpg) | CC BY-SA 4.0 | Yes | Asim Iftikhar Nagi |
| `karakoram-highway` | Nanga Parbat seen from the Karakoram Highway | `SiteImagery.php` | [Nanga Parbat From KKH.jpg](https://commons.wikimedia.org/wiki/File:Nanga_Parbat_From_KKH.jpg) | CC BY-SA 4.0 | Yes | Akbar Khan Niazi |
| `makkah-skyline` | The Makkah skyline and the towers overlooking Masjid al-Haram | `SiteImagery.php` | [3rd Ring Road makkah.jpg](https://commons.wikimedia.org/wiki/File:3rd_Ring_Road_makkah.jpg) | CC BY-SA 4.0 | Yes | King Eliot |
| `malam-jabba` | Malam Jabba in the Swat valley | `SiteImagery.php` | [Malam Jaba, Swat, Pakistan.JPG](https://commons.wikimedia.org/wiki/File:Malam_Jaba,_Swat,_Pakistan.JPG) | CC BY-SA 4.0 | Yes | Mehlab Jameel |
| `malaysia` | Kuala Lumpur at sunset, Malaysia | `SiteImagery.php` | [Sunset at Kuala Lumpur.jpg](https://commons.wikimedia.org/wiki/File:Sunset_at_Kuala_Lumpur.jpg) | CC BY-SA 4.0 | Yes | YongBoi |
| `maldives` | An island and lagoon in the Maldives | `SiteImagery.php` | [Bathala (Maldives) 8.JPG](https://commons.wikimedia.org/wiki/File:Bathala_(Maldives)_8.JPG) | CC BY-SA 4.0 | Yes | Gzzz |
| `mina-tents` | The air-conditioned tent city at Mina during Hajj | `SiteImagery.php`<br>`home.blade.php`<br>`media.blade.php` | [Haji pilgrimage mina tent city.jpg](https://commons.wikimedia.org/wiki/File:Haji_pilgrimage_mina_tent_city.jpg) | CC BY-SA 4.0 | Yes | Seeley International |
| `muzdalifah` | Pilgrims at Muzdalifah at dawn | `SiteImagery.php` | [Fajr in Muzdalifah.jpg](https://commons.wikimedia.org/wiki/File:Fajr_in_Muzdalifah.jpg) | CC BY-SA 4.0 | Yes | Arisdp |
| `nabawi-aerial` | Al-Masjid an-Nabawi in Madinah seen from above, with the Green Dome | `SiteImagery.php`<br>`home.blade.php`<br>`page.blade.php` | [Al-Masjid An-Nabawi (Bird's Eye View).jpg](https://commons.wikimedia.org/wiki/File:Al-Masjid_An-Nabawi_(Bird%27s_Eye_View).jpg) | CC0 | No | Konevi |
| `nabawi-dome` | The Green Dome of Al-Masjid an-Nabawi in Madinah | `SiteImagery.php`<br>`faqs.blade.php`<br>`home.blade.php` | [MasjidNabawi.jpg](https://commons.wikimedia.org/wiki/File:MasjidNabawi.jpg) | CC0 | No | Wurzelgnohm |
| `quba-mosque` | Quba Mosque in Madinah | `SiteImagery.php` | [Quba Mosque - panoramio.jpg](https://commons.wikimedia.org/wiki/File:Quba_Mosque_-_panoramio.jpg) | CC BY 3.0 | Yes | Tevfik Teker |
| `saudia-aircraft` | A Saudia Boeing 787 airliner | `SiteImagery.php` | [HZ-AR29 Boeing 787-10 Saudia Arabian Airlines LHR 3.1.25.jpg](https://commons.wikimedia.org/wiki/File:HZ-AR29_Boeing_787-10_Saudia_Arabian_Airlines_LHR_3.1.25.jpg) | CC BY-SA 2.0 | Yes | Colin Cooke Photo |
| `singapore` | The Singapore skyline at dusk | `SiteImagery.php` | [1 singapore city skyline dusk panorama 2011.jpg](https://commons.wikimedia.org/wiki/File:1_singapore_city_skyline_dusk_panorama_2011.jpg) | CC BY-SA 4.0 | Yes | chenisyuan |
| `skardu-deosai` | Sheosar Lake on the Deosai plains | `SiteImagery.php` | [Sheoser lake deosai national park.jpg](https://commons.wikimedia.org/wiki/File:Sheoser_lake_deosai_national_park.jpg) | CC BY-SA 4.0 | Yes | Jehan Sher |
| `skardu-shangrila` | Lower Kachura (Shangrila) Lake near Skardu | `SiteImagery.php` | [Shangrila, Lower Kachura Lake.jpg](https://commons.wikimedia.org/wiki/File:Shangrila,_Lower_Kachura_Lake.jpg) | CC BY-SA 4.0 | Yes | Hasanijaz |
| `skardu-skyline` | Skardu in autumn, Gilgit-Baltistan | `SiteImagery.php` | [An autumnal view of Skardu's skyline.jpg](https://commons.wikimedia.org/wiki/File:An_autumnal_view_of_Skardu%27s_skyline.jpg) | CC BY-SA 4.0 | Yes | Vasiq Eqbal |
| `south-africa` | Table Mountain above Cape Town, South Africa | `SiteImagery.php` | [Cape Town Mountain.jpg](https://commons.wikimedia.org/wiki/File:Cape_Town_Mountain.jpg) | CC0 | No | safaritravelplus |
| `sri-lanka` | Sigiriya rock fortress seen from the air, Sri Lanka | `SiteImagery.php` | [Sigiriya Luftbild (29781064900).jpg](https://commons.wikimedia.org/wiki/File:Sigiriya_Luftbild_(29781064900).jpg) | CC BY 2.0 | Yes | dronepicr |
| `thailand` | The Temple of the Emerald Buddha at the Grand Palace, Bangkok | `SiteImagery.php` | [Emerald Buddha Temple - 2017-06-11 (091).jpg](https://commons.wikimedia.org/wiki/File:Emerald_Buddha_Temple_-_2017-06-11_(091).jpg) | CC0 | No | Iudexvivorum |
| `turkey-cappadocia` | The rock landscape of Cappadocia, Turkey | `SiteImagery.php` | [Cappadocia Aktepe Panorama.JPG](https://commons.wikimedia.org/wiki/File:Cappadocia_Aktepe_Panorama.JPG) | CC BY-SA 3.0 | Yes | Bjørn Christian Tørrissen |
| `turkey-istanbul` | Panorama of Istanbul across the Bosphorus, Turkey | `SiteImagery.php` | [Istanbulpanorama.jpg](https://commons.wikimedia.org/wiki/File:Istanbulpanorama.jpg) | CC0 | No | Hunanuk |

---

## Attribution

38 of the 45 photographs are under licences that require the
photographer to be credited (CC BY / CC BY-SA). The site carries a `.photo-credits`
style for a per-page credit block; **this has not yet been placed in the
templates** — it is listed as an open item in the audit document, because the
right place for it depends on whether these images survive past the demo.

If any CC BY / CC BY-SA image here ships to production, the credit below must
appear somewhere on the site (a colophon or credits page is sufficient).
- **arafat** — Fahad Faisal, CC BY-SA 4.0. [Source](https://commons.wikimedia.org/wiki/File:Jabal-e-Rehmat_(Mount_of_Mercy_Mount_Arafat).jpg)
- **azerbaijan-baku** — Diego Delso, CC BY-SA 4.0. [Source](https://commons.wikimedia.org/wiki/File:Baku,_Azerbaiy%C3%A1n,_2016-09-26,_DD_207-209_PAN.jpg)
- **china-wall** — Severin.stalder, CC BY-SA 3.0. [Source](https://commons.wikimedia.org/wiki/File:The_Great_Wall_of_China_at_Jinshanling-edit.jpg)
- **dubai** — Norlando Pobre, CC BY 2.0. [Source](https://commons.wikimedia.org/wiki/File:Dubai_Marina_Skyline.jpg)
- **egypt-pyramids** — Kimberlym21, CC BY-SA 4.0. [Source](https://commons.wikimedia.org/wiki/File:Pyramids_of_Giza,_Egypt.jpg)
- **europe** — Benh LIEU SONG, CC BY-SA 4.0. [Source](https://commons.wikimedia.org/wiki/File:Paris_Night.jpg)
- **europe-alps** — Photo: chil, on Camptocamp.org
Derivative work:Zacharie Grossen, CC BY-SA 3.0. [Source](https://commons.wikimedia.org/wiki/File:Matterhorn_from_Domh%C3%BCtte_-_2.jpg)
- **fairy-meadows** — Usmanbukhari3362, CC BY-SA 4.0. [Source](https://commons.wikimedia.org/wiki/File:Fairy_Meadows_GB_Pakistan.jpg)
- **haram-courtyard** — Saudipics.com, CC BY-SA 4.0. [Source](https://commons.wikimedia.org/wiki/File:Great_Mosque_of_Mecca1.jpg)
- **haram-panorama** — Bluemangoa2z at Malayalam Wikipedia, CC BY 3.0. [Source](https://commons.wikimedia.org/wiki/File:Masjid_al-Haram_panorama.JPG)
- **haramain-train** — amanderson2, CC BY 2.0. [Source](https://commons.wikimedia.org/wiki/File:Haramain_High_Speed_Railway_Station_Interior_2022.jpg)
- **hong-kong** — Diliff, CC BY 3.0. [Source](https://commons.wikimedia.org/wiki/File:Hong_Kong_Skyline_Restitch_-_Dec_2007.jpg)
- **hunza-baltit** — Fassifarooq, CC BY-SA 4.0. [Source](https://commons.wikimedia.org/wiki/File:Baltit_Fort,_Karimabad,_Hunza,_Gilgit_Baltistan.jpg)
- **hunza-valley** — FaizanAhmad, CC BY-SA 3.0. [Source](https://commons.wikimedia.org/wiki/File:Hunza_Valley_HDR.jpg)
- **indonesia-bali** — Konstantinos Trovas, CC BY-SA 3.0. [Source](https://commons.wikimedia.org/wiki/File:Pura_Bratan_Bali.jpg)
- **islamabad-faisal** — Ijlalahmed, CC BY-SA 4.0. [Source](https://commons.wikimedia.org/wiki/File:Islamabad_-_Faisal_Mosque.jpg)
- **jamarat** — saudipics, CC BY-SA 4.0. [Source](https://commons.wikimedia.org/wiki/File:Jamaraat_Bridge_2.jpg)
- **jeddah-airport** — Tweenet, CC BY-SA 4.0. [Source](https://commons.wikimedia.org/wiki/File:JED-Outside-Terminal1.jpg)
- **jordan-petra** — Susanahajer, CC BY-SA 3.0. [Source](https://commons.wikimedia.org/wiki/File:Al_khazneh.jpg)
- **kaaba-close** — Richard Mortel, CC BY 2.0. [Source](https://commons.wikimedia.org/wiki/File:The_Ka%27ba,_Great_Mosque_of_Mecca,_Saudi_Arabia_(4).jpg)
- **kaaba-tawaf** — Adli Wahid, CC BY-SA 4.0. [Source](https://commons.wikimedia.org/wiki/File:The_Kaaba_during_Hajj.jpg)
- **kaghan-naran** — Adeel ur Rehman Mughal, CC BY-SA 4.0. [Source](https://commons.wikimedia.org/wiki/File:Siri_Paye,_Shogran,_Kaghan_Valley.jpg)
- **karachi** — Asim Iftikhar Nagi, CC BY-SA 4.0. [Source](https://commons.wikimedia.org/wiki/File:Frere_Hall_Karachi._Pakistan.jpg)
- **karakoram-highway** — Akbar Khan Niazi, CC BY-SA 4.0. [Source](https://commons.wikimedia.org/wiki/File:Nanga_Parbat_From_KKH.jpg)
- **makkah-skyline** — King Eliot, CC BY-SA 4.0. [Source](https://commons.wikimedia.org/wiki/File:3rd_Ring_Road_makkah.jpg)
- **malam-jabba** — Mehlab Jameel, CC BY-SA 4.0. [Source](https://commons.wikimedia.org/wiki/File:Malam_Jaba,_Swat,_Pakistan.JPG)
- **malaysia** — YongBoi, CC BY-SA 4.0. [Source](https://commons.wikimedia.org/wiki/File:Sunset_at_Kuala_Lumpur.jpg)
- **maldives** — Gzzz, CC BY-SA 4.0. [Source](https://commons.wikimedia.org/wiki/File:Bathala_(Maldives)_8.JPG)
- **mina-tents** — Seeley International, CC BY-SA 4.0. [Source](https://commons.wikimedia.org/wiki/File:Haji_pilgrimage_mina_tent_city.jpg)
- **muzdalifah** — Arisdp, CC BY-SA 4.0. [Source](https://commons.wikimedia.org/wiki/File:Fajr_in_Muzdalifah.jpg)
- **quba-mosque** — Tevfik Teker, CC BY 3.0. [Source](https://commons.wikimedia.org/wiki/File:Quba_Mosque_-_panoramio.jpg)
- **saudia-aircraft** — Colin Cooke Photo, CC BY-SA 2.0. [Source](https://commons.wikimedia.org/wiki/File:HZ-AR29_Boeing_787-10_Saudia_Arabian_Airlines_LHR_3.1.25.jpg)
- **singapore** — chenisyuan, CC BY-SA 4.0. [Source](https://commons.wikimedia.org/wiki/File:1_singapore_city_skyline_dusk_panorama_2011.jpg)
- **skardu-deosai** — Jehan Sher, CC BY-SA 4.0. [Source](https://commons.wikimedia.org/wiki/File:Sheoser_lake_deosai_national_park.jpg)
- **skardu-shangrila** — Hasanijaz, CC BY-SA 4.0. [Source](https://commons.wikimedia.org/wiki/File:Shangrila,_Lower_Kachura_Lake.jpg)
- **skardu-skyline** — Vasiq Eqbal, CC BY-SA 4.0. [Source](https://commons.wikimedia.org/wiki/File:An_autumnal_view_of_Skardu%27s_skyline.jpg)
- **sri-lanka** — dronepicr, CC BY 2.0. [Source](https://commons.wikimedia.org/wiki/File:Sigiriya_Luftbild_(29781064900).jpg)
- **turkey-cappadocia** — Bjørn Christian Tørrissen, CC BY-SA 3.0. [Source](https://commons.wikimedia.org/wiki/File:Cappadocia_Aktepe_Panorama.JPG)
