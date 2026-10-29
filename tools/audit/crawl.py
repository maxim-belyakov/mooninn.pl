"""Crawl mooninn.pl per language, list URLs and visible text that looks Polish on non-PL pages."""
import re, sys, json, urllib.request, html
from html.parser import HTMLParser

BASE = "https://mooninn.pl"
LANGS = sys.argv[1:] or ["ru", "en"]
PL_CHARS = re.compile(r"[ąćęłńśźżĄĆĘŁŃŚŹŻ]")
PL_WORDS = re.compile(r"\b(i|oraz|lub|dla|nie|jest|się|koszyk|zamów|wydanie|sklep|strona|główna|szukaj|zaloguj|wyślij|produkt|cena|dostawa|kontakt|polityka|regulamin|nowości|więcej|wszystkie|sortuj|według|ilość|dodaj|kategorie|informacje|twoje|konto|płatność|zwrot|o nas|przedsprzedaż|tom|autor|ilustracje|wydawnictwo)\b", re.I)

class P(HTMLParser):
    def __init__(s):
        super().__init__(); s.links=[]; s.text=[]; s.skip=0; s.attrs_text=[]
    def handle_starttag(s, t, a):
        a=dict(a)
        if t in ("script","style","noscript"): s.skip+=1
        if t=="a" and a.get("href"): s.links.append(a["href"])
        for k in ("placeholder","title","alt","aria-label","value"):
            if a.get(k) and t!="input" or (k=="placeholder" and a.get(k)): s.attrs_text.append(f"[{t}@{k}] {a[k]}")
    def handle_endtag(s, t):
        if t in ("script","style","noscript") and s.skip: s.skip-=1
    def handle_data(s, d):
        if not s.skip and d.strip(): s.text.append(d.strip())

def get(u):
    r=urllib.request.Request(u, headers={"User-Agent":"mooninn-audit"})
    with urllib.request.urlopen(r, timeout=30) as f: return f.status, f.geturl(), f.read().decode("utf-8","replace")

def main():
    report = {}
    for lang in LANGS:
        seen = set(); queue = [f"{BASE}/{lang}/"]; pages = {}
        while queue and len(seen) < 150:
            u = queue.pop(0).split("#")[0]
            if u in seen: continue
            seen.add(u)
            try: st, final, body = get(u)
            except Exception as e: pages[u] = {"error": str(e)}; continue
            p = P(); p.feed(body)
            title = re.search(r"<title>(.*?)</title>", body, re.S)
            sus = [t for t in p.text + p.attrs_text if PL_CHARS.search(t) or PL_WORDS.search(t)]
            pages[u] = {"title": html.unescape(title.group(1).strip()) if title else "", "suspect": sorted(set(sus))}
            for l in p.links:
                l = html.unescape(l).split("#")[0]
                if l.startswith("/"): l = BASE + l
                if l.startswith(f"{BASE}/{lang}/") and not re.search(r"(\?|\.(jpg|png|webp|css|js)$|/(module|cart|order|login|password|my-account|identity|logout))", l):
                    if l not in seen: queue.append(l)
        report[lang] = pages
    json.dump(report, sys.stdout, ensure_ascii=False, indent=1)


if __name__ == "__main__":
    main()
