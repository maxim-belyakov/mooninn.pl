"""Second pass: on RU pages flag Latin-only text, on EN pages flag Cyrillic or Polish text."""
import json, re, sys, urllib.request, collections
sys.path.insert(0, ".")
from crawl import P, get
r = json.load(open("/tmp/mooninn-audit.json"))
extra = {"ru": ["cart?action=show", "order", "login", "registration", "password-recovery", "my-account"],
         "en": ["cart?action=show", "order", "login", "registration", "password-recovery", "my-account"]}
OK = re.compile(r"^(Mooninn|MOONINN|DEMO|ISBN.*|[\d\s.,:/%+\-()zł€$x×]+|PL|EN|RU|Polski|English|Русский|E-mail|Email|BLIK|InPost|Facebook|Instagram|contact@mooninn\.pl|Seo Ha-eun|Mira Kowal|Yun Ji-ho|Anna Lis|DEMO-0\d|[A-Z]{2,4}|Manga|Manhwa)$")
for lang in ("ru", "en"):
    urls = list(r[lang]) + [f"https://mooninn.pl/{lang}/{e}" for e in extra[lang]]
    c = collections.defaultdict(set)
    for u in urls:
        try: st, final, body = get(u)
        except Exception as e: print("ERR", u, e); continue
        p = P(); p.feed(body)
        for t in p.text + [a.split("] ", 1)[1] for a in p.attrs_text if "option@value" not in a]:
            t = t.strip()
            if OK.match(t) or t.startswith("http"): continue
            if lang == "ru" and re.search(r"[A-Za-z]{3,}", t) and not re.search(r"[А-Яа-я]", t): c[t].add(u.split("/ru/")[1][:30])
            if lang == "en" and (re.search(r"[А-Яа-яąćęłńśźżŁŚŻŹ]", t)): c[t].add(u.split("/en/")[1][:30])
    print("=====", lang)
    for t, us in sorted(c.items(), key=lambda x: -len(x[1])): print(len(us), "|", t[:140], "|", sorted(us)[:3])
