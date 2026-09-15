// Injected into every page of the recording browser (never into the app).
// Draws what a screen recording needs and a headless browser does not show:
// a visible mouse cursor with a click ripple, chapter title cards, on-screen
// captions and a highlight ring around the thing being explained. Everything
// is pointer-events: none, so it never gets in the way of a click.

export const overlayScript = `(() => {
  if (window.__training) return;

  const css = \`
    #__tr-cursor { position: fixed; left: 0; top: 0; width: 26px; height: 26px; z-index: 2147483647; pointer-events: none;
      transform: translate(-3px, -2px); transition: left 40ms linear, top 40ms linear; filter: drop-shadow(0 2px 3px rgba(0,0,0,.45)); }
    #__tr-cursor svg { width: 26px; height: 26px; }
    .__tr-ripple { position: fixed; z-index: 2147483646; pointer-events: none; width: 44px; height: 44px; margin: -22px 0 0 -22px;
      border-radius: 50%; border: 3px solid #c9a227; animation: __tr-ripple 520ms ease-out forwards; }
    @keyframes __tr-ripple { from { transform: scale(.3); opacity: 1; } to { transform: scale(1.4); opacity: 0; } }
    #__tr-caption { position: fixed; left: 50%; bottom: 26px; transform: translateX(-50%); z-index: 2147483645; pointer-events: none;
      max-width: 1040px; width: max-content; background: rgba(16,27,69,.94); color: #fff; font: 600 21px/1.4 "Segoe UI", system-ui, sans-serif;
      padding: 11px 22px; border-radius: 12px; box-shadow: 0 8px 28px rgba(0,0,0,.35); border-left: 5px solid #c9a227; display: none; text-align: left; }
    #__tr-caption small { display: block; font: 700 12px/1.3 "Segoe UI", system-ui, sans-serif; letter-spacing: .08em; text-transform: uppercase; color: #e5c76b; margin-bottom: 2px; }
    #__tr-spot { position: fixed; z-index: 2147483644; pointer-events: none; border-radius: 10px; display: none;
      box-shadow: 0 0 0 4px #c9a227, 0 0 0 9999px rgba(10,18,45,.28); transition: all 260ms ease; }
    #__tr-card { position: fixed; inset: 0; z-index: 2147483647; pointer-events: none; display: none; align-items: center; justify-content: center;
      background: radial-gradient(circle at 30% 20%, #1d2c6b 0%, #101b45 60%, #0a1230 100%); color: #fff; font-family: "Segoe UI", system-ui, sans-serif; }
    #__tr-card .inner { max-width: 980px; padding: 0 60px; }
    #__tr-card .brand { font-weight: 700; font-size: 15px; letter-spacing: .16em; text-transform: uppercase; color: #e5c76b; }
    #__tr-card .num { margin-top: 26px; font-weight: 700; font-size: 22px; color: #cfd6ee; }
    #__tr-card h1 { margin: 8px 0 18px; font: 700 50px/1.15 "Playfair Display", Georgia, serif; color: #fff; }
    #__tr-card p { margin: 0; font-size: 25px; line-height: 1.45; color: #dfe4f5; }
    #__tr-card .cat { display: inline-block; margin-top: 30px; padding: 6px 16px; border: 1px solid rgba(229,199,107,.6); border-radius: 999px; color: #e5c76b; font-size: 16px; }
  \`;

  const cursorSvg = '<svg viewBox="0 0 24 24"><path d="M3 2l15.5 11.2-6.6 1.2 3.9 7.3-2.8 1.5-3.9-7.3L3.9 20z" fill="#fff" stroke="#101b45" stroke-width="1.6" stroke-linejoin="round"/></svg>';

  function mount() {
    if (document.getElementById('__tr-style') || !document.documentElement) return;
    const style = document.createElement('style');
    style.id = '__tr-style';
    style.textContent = css;
    document.documentElement.appendChild(style);
    for (const [id, html] of [['__tr-cursor', cursorSvg], ['__tr-caption', ''], ['__tr-spot', ''], ['__tr-card', '']]) {
      const el = document.createElement('div');
      el.id = id;
      el.setAttribute('aria-hidden', 'true');
      el.innerHTML = html;
      document.documentElement.appendChild(el);
    }
    const saved = JSON.parse(sessionStorage.getItem('__tr-mouse') || 'null');
    if (saved) place(saved.x, saved.y);
  }

  function place(x, y) {
    const c = document.getElementById('__tr-cursor');
    if (c) { c.style.left = x + 'px'; c.style.top = y + 'px'; }
  }

  document.addEventListener('mousemove', (e) => {
    mount();
    place(e.clientX, e.clientY);
    try { sessionStorage.setItem('__tr-mouse', JSON.stringify({ x: e.clientX, y: e.clientY })); } catch (err) {}
  }, true);

  document.addEventListener('mousedown', (e) => {
    const r = document.createElement('div');
    r.className = '__tr-ripple';
    r.style.left = e.clientX + 'px';
    r.style.top = e.clientY + 'px';
    document.documentElement.appendChild(r);
    setTimeout(() => r.remove(), 600);
  }, true);

  const esc = (s) => String(s ?? '').replace(/[&<>"]/g, (ch) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[ch]));

  window.__training = {
    mount,
    card(chapter) {
      mount();
      const card = document.getElementById('__tr-card');
      card.innerHTML = '<div class="inner"><div class="brand">Universal Brothers · Admin Video Training</div>'
        + '<div class="num">Chapter ' + chapter.number + ' of ' + chapter.total + '</div>'
        + '<h1>' + esc(chapter.title) + '</h1><p>' + esc(chapter.description) + '</p>'
        + '<span class="cat">' + esc(chapter.category) + '</span></div>';
      card.style.display = 'flex';
    },
    hideCard() { const card = document.getElementById('__tr-card'); if (card) card.style.display = 'none'; },
    caption(text, label) {
      mount();
      const el = document.getElementById('__tr-caption');
      el.innerHTML = (label ? '<small>' + esc(label) + '</small>' : '') + esc(text);
      el.style.bottom = document.querySelector('.builder-actionbar') ? '84px' : '26px';
      el.style.display = text ? 'block' : 'none';
    },
    spot(rect) {
      mount();
      const el = document.getElementById('__tr-spot');
      if (!rect) { el.style.display = 'none'; return; }
      const pad = 6;
      Object.assign(el.style, { display: 'block', left: (rect.x - pad) + 'px', top: (rect.y - pad) + 'px', width: (rect.width + pad * 2) + 'px', height: (rect.height + pad * 2) + 'px' });
    },
  };

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', mount); else mount();
})();`;
