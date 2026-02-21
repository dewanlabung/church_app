<style>
:root {
  --bg-primary: #0C0E12;
  --bg-secondary: #14171E;
  --bg-card: #1A1E28;
  --bg-card-hover: #1F2433;
  --bg-elevated: #242936;
  --gold: #C9A84C;
  --gold-light: #E8D48B;
  --gold-dark: #A07C28;
  --gold-glow: rgba(201, 168, 76, 0.15);
  --cream: #F5F0E8;
  --cream-dim: #B8B0A0;
  --text-primary: #E8E4DC;
  --text-secondary: #9A9488;
  --text-muted: #6B6560;
  --accent-blue: #4A7C9B;
  --accent-rose: #9B4A6A;
  --accent-green: #4A9B6A;
  --border: rgba(201, 168, 76, 0.12);
  --border-strong: rgba(201, 168, 76, 0.25);
  --shadow-sm: 0 2px 8px rgba(0,0,0,0.3);
  --shadow-md: 0 4px 20px rgba(0,0,0,0.4);
  --shadow-lg: 0 8px 40px rgba(0,0,0,0.5);
  --shadow-gold: 0 0 30px rgba(201, 168, 76, 0.1);
  --radius-sm: 8px;
  --radius-md: 12px;
  --radius-lg: 16px;
  --radius-xl: 24px;
  --font-display: 'Playfair Display', Georgia, serif;
  --font-body: 'Source Sans 3', 'Segoe UI', sans-serif;
  --font-elegant: 'Cormorant Garamond', Georgia, serif;
}
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
  background: var(--bg-primary); color: var(--text-primary);
  font-family: var(--font-body); line-height: 1.6;
  -webkit-font-smoothing: antialiased; min-height: 100vh;
}
body::before {
  content: ''; position: fixed; inset: 0; pointer-events: none; z-index: 0;
  background: radial-gradient(ellipse 80% 50% at 50% 0%, rgba(201,168,76,0.06) 0%, transparent 60%),
    radial-gradient(ellipse 60% 40% at 80% 100%, rgba(74,124,155,0.04) 0%, transparent 50%);
}
.nav-bar {
  position: sticky; top: 0; z-index: 100;
  background: rgba(12, 14, 18, 0.9); backdrop-filter: blur(20px);
  border-bottom: 1px solid var(--border); padding: 0 1.5rem;
}
.nav-inner {
  max-width: 1300px; margin: 0 auto;
  display: flex; align-items: center; justify-content: space-between; height: 68px;
}
.nav-brand {
  display: flex; align-items: center; gap: 10px;
  text-decoration: none; color: var(--gold); cursor: pointer;
}
.nav-brand-icon {
  width: 40px; height: 40px; border-radius: 50%;
  background: linear-gradient(135deg, var(--gold-dark), var(--gold));
  display: flex; align-items: center; justify-content: center;
  color: var(--bg-primary); font-size: 1.2rem; box-shadow: var(--shadow-gold);
}
.nav-brand-text { font-family: var(--font-display); font-size: 1.15rem; font-weight: 700; }
.nav-links { display: flex; gap: 2px; list-style: none; flex-wrap: wrap; }
.nav-link {
  padding: 7px 14px; border-radius: var(--radius-sm);
  font-size: 0.85rem; font-weight: 500; color: var(--text-secondary);
  cursor: pointer; transition: all 0.2s; border: none; background: none;
  font-family: var(--font-body); white-space: nowrap;
}
.nav-link:hover { color: var(--text-primary); background: rgba(255,255,255,0.04); }
.nav-link.active { color: var(--gold); background: var(--gold-glow); }
.nav-mobile-btn {
  display: none; background: none; border: none;
  color: var(--text-primary); cursor: pointer; padding: 8px; font-size: 1.5rem;
}
.mobile-menu {
  display: none; position: fixed; inset: 0; z-index: 200;
  background: rgba(12, 14, 18, 0.97); backdrop-filter: blur(20px);
  flex-direction: column; align-items: center; justify-content: center; gap: 6px;
}
.mobile-menu.open { display: flex; }
.mobile-menu .nav-link { font-size: 1.2rem; padding: 12px 28px; }
.mobile-close {
  position: absolute; top: 18px; right: 18px;
  background: none; border: none; color: var(--text-primary); cursor: pointer; font-size: 1.5rem;
}
@media (max-width: 900px) { .nav-links { display: none; } .nav-mobile-btn { display: block; } }
.main-content { position: relative; z-index: 1; max-width: 1300px; margin: 0 auto; padding: 1.5rem; }
.page-section { display: none; }
.page-section.active { display: block; }
.hero-section { text-align: center; padding: 3.5rem 1.5rem; position: relative; }
.hero-section::before {
  content: '\2726'; position: absolute; top: 0; left: 50%;
  transform: translateX(-50%); font-size: 1rem; color: var(--gold); opacity: 0.5;
}
.hero-label {
  font-family: var(--font-body); font-size: 0.72rem; font-weight: 600;
  letter-spacing: 0.2em; text-transform: uppercase; color: var(--gold);
  margin-bottom: 1.2rem; display: flex; align-items: center; justify-content: center; gap: 12px;
}
.hero-label::before, .hero-label::after { content: ''; width: 40px; height: 1px; background: var(--gold); opacity: 0.3; }
.verse-text {
  font-family: var(--font-elegant); font-size: clamp(1.4rem, 3.2vw, 2.3rem);
  font-weight: 400; font-style: italic; line-height: 1.5;
  color: var(--cream); max-width: 780px; margin: 0 auto 1.2rem; text-wrap: balance;
}
.verse-ref { font-family: var(--font-display); font-size: 0.95rem; font-weight: 600; color: var(--gold); letter-spacing: 0.05em; }
.blessing-card {
  background: linear-gradient(135deg, var(--bg-card), var(--bg-elevated));
  border: 1px solid var(--border); border-radius: var(--radius-xl);
  padding: 2.5rem; text-align: center; margin: 1.5rem 0;
  position: relative; overflow: hidden; box-shadow: var(--shadow-gold);
}
.blessing-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(90deg, transparent, var(--gold), transparent); }
.blessing-title { font-family: var(--font-display); font-size: 1.4rem; font-weight: 700; color: var(--gold-light); margin-bottom: 0.8rem; }
.blessing-text { font-family: var(--font-elegant); font-size: 1.15rem; color: var(--text-primary); max-width: 680px; margin: 0 auto; line-height: 1.8; }
.blessing-author { margin-top: 0.8rem; font-size: 0.85rem; color: var(--text-secondary); }
.section-header {
  display: flex; align-items: center; justify-content: space-between;
  margin: 2.5rem 0 1.2rem; padding-bottom: 0.8rem; border-bottom: 1px solid var(--border);
  flex-wrap: wrap; gap: 10px;
}
.section-title { font-family: var(--font-display); font-size: 1.5rem; font-weight: 700; color: var(--cream); display: flex; align-items: center; gap: 8px; }
.section-action {
  padding: 7px 18px; background: var(--gold-glow); border: 1px solid var(--border-strong);
  border-radius: var(--radius-sm); color: var(--gold); font-family: var(--font-body);
  font-size: 0.82rem; font-weight: 600; cursor: pointer; transition: all 0.2s;
}
.section-action:hover { background: var(--gold); color: var(--bg-primary); }
.cards-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.2rem; }
.card {
  background: var(--bg-card); border: 1px solid var(--border);
  border-radius: var(--radius-lg); padding: 1.4rem; transition: all 0.3s;
}
.card:hover { background: var(--bg-card-hover); border-color: var(--border-strong); transform: translateY(-2px); box-shadow: var(--shadow-md); }
.card-badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 0.68rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 0.6rem; }
.badge-worship { background: rgba(201,168,76,0.15); color: var(--gold); }
.badge-study { background: rgba(74,124,155,0.15); color: var(--accent-blue); }
.badge-outreach { background: rgba(74,155,106,0.15); color: var(--accent-green); }
.badge-fellowship { background: rgba(155,74,106,0.15); color: var(--accent-rose); }
.badge-other { background: rgba(150,150,150,0.15); color: #999; }
.card-title { font-family: var(--font-display); font-size: 1.1rem; font-weight: 600; color: var(--cream); margin-bottom: 0.4rem; }
.card-meta { font-size: 0.82rem; color: var(--text-secondary); margin-bottom: 0.3rem; }
.card-desc { font-size: 0.88rem; color: var(--text-muted); line-height: 1.6; }
.prayer-name { font-family: var(--font-display); font-size: 0.95rem; font-weight: 600; color: var(--gold-light); margin-bottom: 0.4rem; }
.prayer-text { font-size: 0.92rem; color: var(--text-primary); line-height: 1.6; margin-bottom: 0.8rem; }
.prayer-footer { display: flex; align-items: center; justify-content: space-between; }
.prayer-date { font-size: 0.78rem; color: var(--text-muted); }
.pray-btn {
  display: flex; align-items: center; gap: 5px; padding: 5px 12px;
  border-radius: var(--radius-sm); background: var(--gold-glow);
  border: 1px solid var(--border-strong); color: var(--gold);
  font-size: 0.82rem; font-weight: 600; cursor: pointer; transition: all 0.2s; font-family: var(--font-body);
}
.pray-btn:hover, .pray-btn.prayed { background: var(--gold); color: var(--bg-primary); }
.book-card {
  background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius-lg);
  padding: 1.4rem; display: flex; gap: 1rem; transition: all 0.3s;
}
.book-card:hover { border-color: var(--border-strong); transform: translateY(-2px); }
.book-cover { font-size: 2.8rem; flex-shrink: 0; }
.book-info { flex: 1; }
.book-title { font-family: var(--font-display); font-size: 1rem; font-weight: 600; color: var(--cream); margin-bottom: 2px; }
.book-author { font-size: 0.82rem; color: var(--text-secondary); margin-bottom: 0.4rem; }
.book-category { display: inline-block; padding: 2px 9px; border-radius: 12px; font-size: 0.68rem; font-weight: 600; background: rgba(74,124,155,0.15); color: var(--accent-blue); margin-bottom: 0.4rem; }
.book-actions { display: flex; gap: 6px; margin-top: 0.4rem; }
.book-btn {
  padding: 4px 12px; border-radius: var(--radius-sm); font-size: 0.78rem; font-weight: 600;
  cursor: pointer; transition: all 0.2s; font-family: var(--font-body);
  border: 1px solid var(--border); background: transparent; color: var(--text-secondary);
}
.book-btn:hover { border-color: var(--border-strong); color: var(--gold); }
.book-btn-pdf { background: var(--gold-glow); border-color: var(--border-strong); color: var(--gold); }
.book-btn-pdf:hover { background: var(--gold); color: var(--bg-primary); }
.study-meta { display: flex; gap: 1rem; font-size: 0.82rem; color: var(--text-secondary); flex-wrap: wrap; }
.sermon-card {
  background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius-lg);
  padding: 1.4rem; display: flex; align-items: flex-start; gap: 1rem; transition: all 0.3s;
}
.sermon-card:hover { border-color: var(--border-strong); }
.sermon-play {
  width: 46px; height: 46px; border-radius: 50%; background: var(--gold-glow);
  border: 1px solid var(--border-strong); color: var(--gold);
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0; cursor: pointer; transition: all 0.2s; font-size: 1rem;
}
.sermon-play:hover { background: var(--gold); color: var(--bg-primary); }
.review-stars { color: var(--gold); margin-bottom: 0.6rem; letter-spacing: 2px; }
.review-text { font-size: 0.92rem; color: var(--text-primary); line-height: 1.6; margin-bottom: 0.6rem; font-style: italic; }
.review-author { font-size: 0.82rem; color: var(--text-secondary); }
.avg-rating { font-family: var(--font-display); font-size: 2.8rem; font-weight: 800; color: var(--gold); }
.star-input button { background: none; border: none; cursor: pointer; color: var(--text-muted); font-size: 1.3rem; padding: 2px; transition: color 0.1s; }
.star-input button.filled { color: var(--gold); }
.star-input button:hover { color: var(--gold-light); }
.info-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(270px, 1fr)); gap: 1.2rem; }
.info-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 1.4rem; }
.info-card-title { font-family: var(--font-display); font-size: 0.95rem; font-weight: 600; color: var(--gold-light); margin-bottom: 0.6rem; }
.info-card-text { font-size: 0.88rem; color: var(--text-secondary); line-height: 1.7; }
.social-links { display: flex; gap: 8px; margin-top: 0.8rem; flex-wrap: wrap; }
.social-link {
  padding: 7px 14px; border-radius: var(--radius-sm); background: var(--bg-elevated);
  border: 1px solid var(--border); color: var(--text-secondary); font-size: 0.82rem;
  font-weight: 600; cursor: pointer; transition: all 0.2s; text-decoration: none;
}
.social-link:hover { color: var(--gold); border-color: var(--border-strong); }
.giving-card {
  background: linear-gradient(135deg, var(--bg-card), rgba(201,168,76,0.05));
  border: 1px solid var(--border-strong); border-radius: var(--radius-xl); padding: 2.5rem; text-align: center;
}
.giving-amounts { display: flex; gap: 8px; justify-content: center; flex-wrap: wrap; margin: 1.2rem 0; }
.giving-amount {
  padding: 9px 22px; border-radius: var(--radius-sm); background: var(--bg-elevated);
  border: 1px solid var(--border); color: var(--text-primary); font-size: 0.95rem;
  font-weight: 600; cursor: pointer; transition: all 0.2s; font-family: var(--font-body);
}
.giving-amount:hover, .giving-amount.selected { background: var(--gold-glow); border-color: var(--gold); color: var(--gold); }
.volunteer-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 1rem; }
.volunteer-card {
  background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius-lg);
  padding: 1.4rem; text-align: center; cursor: pointer; transition: all 0.3s;
}
.volunteer-card:hover { border-color: var(--gold); transform: translateY(-2px); }
.volunteer-icon { font-size: 2.2rem; margin-bottom: 0.5rem; }
.volunteer-name { font-family: var(--font-display); font-size: 0.95rem; font-weight: 600; color: var(--cream); margin-bottom: 0.2rem; }
.volunteer-spots { font-size: 0.78rem; color: var(--text-muted); }
.ticker {
  background: var(--gold-glow); border: 1px solid var(--border); border-radius: var(--radius-md);
  padding: 10px 18px; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 12px; overflow: hidden;
}
.ticker-label { font-size: 0.68rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.15em; color: var(--gold); white-space: nowrap; padding: 3px 9px; border-radius: 4px; background: rgba(201,168,76,0.15); }
.ticker-text { font-size: 0.85rem; color: var(--text-primary); white-space: nowrap; animation: ticker-scroll 30s linear infinite; }
@keyframes ticker-scroll { from { transform: translateX(100%); } to { transform: translateX(-100%); } }
.modal-overlay {
  position: fixed; inset: 0; z-index: 300; background: rgba(0,0,0,0.7);
  display: none; align-items: center; justify-content: center; padding: 1.5rem; backdrop-filter: blur(4px);
}
.modal-overlay.open { display: flex; }
.modal-content {
  background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius-xl);
  padding: 2rem; max-width: 560px; width: 100%; max-height: 80vh; overflow-y: auto; box-shadow: var(--shadow-lg);
}
.modal-title { font-family: var(--font-display); font-size: 1.3rem; font-weight: 700; color: var(--cream); margin-bottom: 1.2rem; display: flex; align-items: center; justify-content: space-between; }
.modal-close { background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.3rem; }
.form-group { margin-bottom: 1rem; }
.form-label { display: block; font-size: 0.78rem; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 5px; }
.form-input, .form-textarea {
  width: 100%; background: var(--bg-primary); border: 1px solid var(--border); border-radius: var(--radius-sm);
  padding: 10px 14px; color: var(--text-primary); font-family: var(--font-body); font-size: 0.92rem; outline: none; transition: border-color 0.2s;
}
.form-input:focus, .form-textarea:focus { border-color: var(--gold); }
.form-textarea { resize: vertical; min-height: 90px; }
.form-checkbox-row { display: flex; align-items: center; gap: 8px; margin-bottom: 1rem; }
.form-checkbox { accent-color: var(--gold); width: 16px; height: 16px; }
.btn-primary {
  background: linear-gradient(135deg, var(--gold-dark), var(--gold)); color: var(--bg-primary);
  border: none; padding: 11px 26px; border-radius: var(--radius-sm); font-family: var(--font-body);
  font-size: 0.92rem; font-weight: 700; cursor: pointer; transition: all 0.2s; width: 100%;
}
.btn-primary:hover { transform: translateY(-1px); box-shadow: var(--shadow-gold); }
.btn-secondary { background: var(--bg-elevated); color: var(--text-primary); border: 1px solid var(--border); padding: 8px 18px; border-radius: var(--radius-sm); font-family: var(--font-body); font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: all 0.2s; }
.btn-secondary:hover { border-color: var(--border-strong); }
.search-bar { display: flex; align-items: center; gap: 10px; background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 9px 14px; margin-bottom: 1.2rem; }
.search-bar:focus-within { border-color: var(--gold); }
.search-bar input { flex: 1; background: none; border: none; outline: none; color: var(--text-primary); font-family: var(--font-body); font-size: 0.92rem; }
.search-bar input::placeholder { color: var(--text-muted); }
.filter-btns { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 1.2rem; }
.filter-btn { padding: 5px 14px; border-radius: 20px; font-size: 0.78rem; font-weight: 600; background: var(--bg-elevated); border: 1px solid var(--border); color: var(--text-secondary); cursor: pointer; transition: all 0.2s; }
.filter-btn:hover { color: var(--text-primary); border-color: var(--border-strong); }
.filter-btn.active { background: var(--gold-glow); color: var(--gold); border-color: var(--gold); }
.toast {
  position: fixed; bottom: 1.5rem; right: 1.5rem; z-index: 400; background: var(--bg-elevated);
  border: 1px solid var(--border-strong); border-radius: var(--radius-md); padding: 12px 22px;
  color: var(--gold-light); font-weight: 600; box-shadow: var(--shadow-lg); animation: toast-in 0.3s ease-out; display: none;
}
.toast.show { display: block; }
@keyframes toast-in { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
.site-footer { margin-top: 3rem; padding: 2.5rem 1.5rem; border-top: 1px solid var(--border); text-align: center; }
.footer-brand { font-family: var(--font-display); font-size: 1.1rem; font-weight: 700; color: var(--gold); margin-bottom: 0.4rem; }
.footer-text { font-size: 0.82rem; color: var(--text-muted); }
.loading { text-align: center; padding: 3rem; color: var(--text-muted); }
.loading::after { content: ''; display: inline-block; width: 20px; height: 20px; border: 2px solid var(--gold); border-top-color: transparent; border-radius: 50%; animation: spin 0.8s linear infinite; margin-left: 8px; vertical-align: middle; }
@keyframes spin { to { transform: rotate(360deg); } }
@media (max-width: 600px) {
  .main-content { padding: 0.8rem; }
  .hero-section { padding: 2rem 0.5rem; }
  .blessing-card, .giving-card { padding: 1.8rem 1.2rem; }
  .cards-grid { grid-template-columns: 1fr; }
  .info-grid { grid-template-columns: 1fr; }
  .sermon-card { flex-direction: column; }
}
</style>
