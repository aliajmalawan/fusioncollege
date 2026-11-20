"""
Build the website's own small icon font: css/icons.css + css/fonts/*.woff2

The public pages used to load the whole Font Awesome 6.5.2 library from a CDN (about 290 KB of
fonts and CSS for 2,000+ icons). This keeps only the icons the site actually uses, so every page
loads about a tenth of that, from the site itself.

    python tools/build-icons.py

Run it again after adding an icon to the code (a new "fa-..." class in a page) or to iconChoices()
in includes/settings_helper.php, then commit css/icons.css and css/fonts/. The admin panel still
uses the full library from the CDN, so it can show any icon.

Needs: Python 3 with fontTools and brotli (pip install fonttools brotli) and internet the first
time (it downloads Font Awesome Free 6.5.2 from the npm registry into a temp folder).
"""
import hashlib, io, os, re, subprocess, sys, tarfile, tempfile, urllib.request

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
VERSION = '6.5.2'
CACHE = os.path.join(tempfile.gettempdir(), 'fontawesome-free-' + VERSION)
OUT_CSS = os.path.join(ROOT, 'css', 'icons.css')
OUT_FONTS = os.path.join(ROOT, 'css', 'fonts')
FONTS = {'solid': 'fa-solid-900', 'regular': 'fa-regular-400', 'brands': 'fa-brands-400'}


def fetch_fontawesome():
    if os.path.isfile(os.path.join(CACHE, 'package', 'css', 'all.css')):
        return
    url = 'https://registry.npmjs.org/@fortawesome/fontawesome-free/-/fontawesome-free-%s.tgz' % VERSION
    print('Downloading Font Awesome', VERSION)
    data = urllib.request.urlopen(url).read()
    with tarfile.open(fileobj=io.BytesIO(data)) as tar:
        tar.extractall(CACHE, filter='data')


def used_icon_names():
    """Every fa-* word in the public pages, their helpers and scripts, plus the icons saved in the database."""
    names = set()
    files = [os.path.join(ROOT, f) for f in os.listdir(ROOT) if f.endswith('.php')]
    files += [os.path.join(ROOT, 'includes', f) for f in os.listdir(os.path.join(ROOT, 'includes')) if f.endswith('.php')]
    files += [os.path.join(ROOT, 'js', 'main.js'), os.path.join(ROOT, 'database', 'fusion-content-import.sql'),
              os.path.join(ROOT, 'database', 'fusion-rebrand.sql')]
    for path in files:
        if os.path.isfile(path):
            names |= set(re.findall(r'\bfa-[a-z0-9-]+', open(path, encoding='utf-8', errors='ignore').read()))

    # Icons chosen in the admin panel (local database; the live site's choices come from iconChoices())
    mysql = r'C:\xampp\mysql\bin\mysql.exe'
    if os.path.isfile(mysql):
        sql = ('SELECT icon FROM feature_cards UNION SELECT icon FROM courses UNION '
               'SELECT icon FROM fee_items UNION SELECT icon FROM campuses')
        try:
            out = subprocess.run([mysql, '-uroot', '-h127.0.0.1', '-N', 'fusioncollege', '-e', sql],
                                 capture_output=True, text=True, timeout=20).stdout
            names |= set(re.findall(r'\bfa-[a-z0-9-]+', out))
        except Exception as e:
            print('(database not read:', e, ')')
    return names


def main():
    fetch_fontawesome()
    pkg = os.path.join(CACHE, 'package')
    css = open(os.path.join(pkg, 'css', 'all.css'), encoding='utf-8').read()
    names = used_icon_names()

    # Icon rules look like  .fa-house::before {\n  content: "\f015"; }  (brand icons use a single colon)
    icon_rule = re.compile(r'((?:\.fa-[a-z0-9-]+::?before,?\s*)+)\{\s*content:\s*"([^"]+)";\s*\}\s*')
    codepoints, kept = set(), []

    def keep_or_drop(m):
        selectors = re.findall(r'\.(fa-[a-z0-9-]+)::?before', m.group(1))
        wanted = [s for s in selectors if s in names]
        if not wanted:
            return ''
        value = m.group(2)
        codepoints.add(int(value.lstrip('\\'), 16) if value.startswith('\\') else ord(value))
        kept.extend(wanted)
        return ',\n'.join('.%s::before' % s for s in wanted) + ' {\n  content: "%s"; }\n\n' % value

    css = icon_rule.sub(keep_or_drop, css)

    # Drop the Font Awesome 4/5 compatibility fonts; point the three real ones at the subsets
    css = re.sub(r"@font-face\s*\{[^}]*font-family:\s*'(Font Awesome 5[^']*|FontAwesome)'[^}]*\}\s*", '', css)
    for style, base in FONTS.items():
        css = css.replace('url("../webfonts/%s.woff2") format("woff2"), url("../webfonts/%s.ttf") format("truetype")' % (base, base),
                          'url("fonts/%s-subset.woff2") format("woff2")' % base)

    os.makedirs(OUT_FONTS, exist_ok=True)
    unicodes = ','.join('U+%04X' % c for c in sorted(codepoints))
    for base in FONTS.values():
        out = os.path.join(OUT_FONTS, base + '-subset.woff2')
        subprocess.run([sys.executable, '-m', 'fontTools.subset', os.path.join(pkg, 'webfonts', base + '.ttf'),
                        '--unicodes=' + unicodes, '--flavor=woff2', '--layout-features=*', '--no-hinting',
                        '--output-file=' + out], check=True)

    # ?v=<content hash> so browsers fetch a rebuilt font at once (the header's preload link uses the same hash)
    for base in FONTS.values():
        digest = hashlib.md5(open(os.path.join(OUT_FONTS, base + '-subset.woff2'), 'rb').read()).hexdigest()[:8]
        css = css.replace('fonts/%s-subset.woff2"' % base, 'fonts/%s-subset.woff2?v=%s"' % (base, digest))

    # Minify: comments and spacing out (the file is CSS only, no strings with significant spaces besides content values)
    css = re.sub(r'/\*.*?\*/', '', css, flags=re.S)
    css = re.sub(r'\s+', ' ', css)
    css = re.sub(r'\s*([{};:,>])\s*', r'\1', css).replace(';}', '}')
    header = ('/* Font Awesome Free %s by @fontawesome - https://fontawesome.com  License - https://fontawesome.com/license/free '
              '(Icons: CC BY 4.0, Fonts: SIL OFL 1.1, Code: MIT License). Only the %d icons this site uses: built by tools/build-icons.py */\n'
              % (VERSION, len(set(kept))))
    open(OUT_CSS, 'w', encoding='utf-8', newline='\n').write(header + css.strip() + '\n')

    missing = sorted(n for n in names - set(kept) if not re.match(
        r'fa-(solid|regular|brands|fw|spin|pulse|beat|fade|bounce|flip|shake|rotate|xs|sm|lg|xl|2xl|\dx|10x|ul|li|border|inverse|stack|pull)', n))
    print('%d icons kept, %d glyphs. css/icons.css %.1f KB' % (len(set(kept)), len(codepoints), os.path.getsize(OUT_CSS) / 1024))
    for base in FONTS.values():
        print('  css/fonts/%s-subset.woff2 %.1f KB' % (base, os.path.getsize(os.path.join(OUT_FONTS, base + '-subset.woff2')) / 1024))
    if missing:
        print('Words that look like icons but are not Font Awesome icons (check for typos):', ', '.join(missing))


if __name__ == '__main__':
    main()
