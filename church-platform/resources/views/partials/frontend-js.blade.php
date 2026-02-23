<script>
var API = '/api';
var PAGES = ['home','events','prayers','library','studies','sermons','giving','ministries','reviews','testimonies','about'];
var NAV_LABELS = {home:'Home',events:'Events',prayers:'Prayers',library:'Library',studies:'Bible Study',sermons:'Sermons',giving:'Giving',ministries:'Ministries',reviews:'Reviews',testimonies:'Testimonies',about:'About'};
var NAV_ICONS = {home:'\u2302',events:'\uD83D\uDCC5',prayers:'\uD83D\uDE4F',library:'\uD83D\uDCDA',studies:'\uD83D\uDCD6',sermons:'\uD83C\uDF99\uFE0F',giving:'\uD83D\uDC9B',ministries:'\uD83E\uDD1D',reviews:'\u2B50',testimonies:'\u271D',about:'\u26EA'};
var BOOK_ICONS = ['\uD83D\uDCD8','\uD83D\uDCD7','\uD83D\uDCD5','\uD83D\uDCD9','\uD83D\uDCD3','\uD83D\uDCD4','\uD83D\uDCD2','\uD83D\uDCDA'];
var MINISTRY_ICONS = ['\uD83E\uDD1D','\uD83C\uDFB5','\uD83D\uDC76','\uD83C\uDF93','\uD83C\uDF5E','\uD83C\uDFE5','\uD83D\uDCD6','\uD83C\uDF0D','\uD83D\uDC92','\uD83C\uDFA8'];
var currentPage = 'home';
var prayedIds = {};
var selectedRating = 0;
var selectedGiving = null;
var allBooks = [];
var bookFilter = 'All';
var churchSettings = {};

/* ===== THEME TOGGLE ===== */
function getTheme() {
  return localStorage.getItem('church-theme') || 'dark';
}
function setTheme(theme) {
  document.documentElement.setAttribute('data-theme', theme);
  localStorage.setItem('church-theme', theme);
  var btn = document.getElementById('theme-toggle');
  btn.innerHTML = theme === 'dark' ? '\u263E' : '\u2600';
  btn.title = theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode';
  // Update PWA theme-color
  var meta = document.querySelector('meta[name="theme-color"]');
  if (meta) meta.content = theme === 'dark' ? '#0C0E12' : '#F8F6F2';
}
function toggleTheme() {
  setTheme(getTheme() === 'dark' ? 'light' : 'dark');
}
// Apply saved theme on load
(function() { setTheme(getTheme()); })();

/* ===== API ===== */
function apiCall(path, opts) {
  opts = opts || {};
  var defaultHeaders = {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') || {}).content || ''
  };
  if (opts.headers) {
    Object.keys(opts.headers).forEach(function(k) { defaultHeaders[k] = opts.headers[k]; });
    delete opts.headers;
  }
  return fetch(API + path, Object.assign({ headers: defaultHeaders }, opts)).then(function(res) {
    if (!res.ok && res.status === 404) return null;
    return res.json();
  }).catch(function(e) { console.error('API error:', path, e); return null; });
}

/* ===== NAV ===== */
function buildNav() {
  var nl = document.getElementById('nav-links');
  var ml = document.getElementById('mobile-links');
  nl.innerHTML = ''; ml.innerHTML = '';
  PAGES.forEach(function(p) {
    var cls = p === currentPage ? 'nav-link active' : 'nav-link';
    nl.innerHTML += '<li><button class="' + cls + '" onclick="navigate(\'' + p + '\')">' + NAV_LABELS[p] + '</button></li>';
    var mcls = p === currentPage ? 'mobile-nav-item active' : 'mobile-nav-item';
    ml.innerHTML += '<button class="' + mcls + '" onclick="navigate(\'' + p + '\')"><span class="mobile-nav-icon">' + NAV_ICONS[p] + '</span>' + NAV_LABELS[p] + '</button>';
  });
}

function navigate(page) {
  currentPage = page;
  PAGES.forEach(function(p) {
    document.getElementById('page-' + p).classList.toggle('active', p === page);
  });
  buildNav();
  closeMobile();
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

/* ===== MOBILE MENU ===== */
function toggleMobile() {
  var menu = document.getElementById('mobile-menu');
  menu.classList.toggle('open');
  document.body.style.overflow = menu.classList.contains('open') ? 'hidden' : '';
}
function closeMobile() {
  document.getElementById('mobile-menu').classList.remove('open');
  document.body.style.overflow = '';
}

/* ===== UTILS ===== */
function showToast(msg) {
  var t = document.getElementById('toast');
  t.textContent = msg; t.classList.add('show');
  setTimeout(function() { t.classList.remove('show'); }, 3000);
}
function openModal(type) { document.getElementById('modal-' + type).classList.add('open'); document.body.style.overflow = 'hidden'; }
function closeModal(type) { document.getElementById('modal-' + type).classList.remove('open'); document.body.style.overflow = ''; }
function renderStars(rating) { return '\u2605'.repeat(rating) + '\u2606'.repeat(5 - rating); }
function buildStarInput() {
  var c = document.getElementById('star-input'); c.innerHTML = '';
  for (var i = 1; i <= 5; i++) {
    var b = document.createElement('button');
    b.textContent = i <= selectedRating ? '\u2605' : '\u2606';
    b.className = i <= selectedRating ? 'filled' : '';
    b.setAttribute('data-val', i);
    b.onclick = function() { selectedRating = parseInt(this.getAttribute('data-val')); buildStarInput(); };
    c.appendChild(b);
  }
}
function fmtDate(d) {
  if (!d) return '';
  return new Date(d).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}
function esc(s) { if (!s) return ''; var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

/* ===== CARD RENDERERS ===== */
function prayerCard(p) {
  var prayed = prayedIds[p.id];
  return '<div class="card"><div class="prayer-name">' + esc(p.name) + '</div>' +
    (p.subject ? '<div style="font-size:0.85rem;color:var(--gold);margin-bottom:0.3rem;font-weight:600">' + esc(p.subject) + '</div>' : '') +
    '<div class="prayer-text">' + esc(p.description || p.request) + '</div>' +
    '<div class="prayer-footer"><span class="prayer-date">' + fmtDate(p.created_at) + '</span>' +
    '<button class="pray-btn ' + (prayed ? 'prayed' : '') + '" onclick="doPray(' + p.id + ')">\uD83D\uDE4F ' + (p.prayer_count || 0) + ' ' + (prayed ? 'Prayed' : 'Pray') + '</button></div></div>';
}
function eventCard(e) {
  return '<div class="card"><h3 class="card-title">' + esc(e.title) + '</h3>' +
    '<p class="card-meta">\uD83D\uDCC5 ' + fmtDate(e.start_date) + (e.start_time ? ' \u2022 \u23F0 ' + e.start_time.slice(0,5) : '') + '</p>' +
    (e.location ? '<p class="card-meta">\uD83D\uDCCD ' + esc(e.location) + '</p>' : '') +
    '<p class="card-desc">' + esc(e.description || '') + '</p></div>';
}
function bookCard(b, i) {
  return '<div class="book-card"><div class="book-cover">' + BOOK_ICONS[i % BOOK_ICONS.length] + '</div>' +
    '<div class="book-info"><div class="book-title">' + esc(b.title) + '</div>' +
    '<div class="book-author">' + esc(b.author) + '</div>' +
    (b.category ? '<span class="book-category">' + esc(b.category) + '</span>' : '') +
    (b.pages ? '<div class="card-desc">' + b.pages + ' pages</div>' : '') +
    '<div class="book-actions">' + (b.pdf_file ? '<button class="book-btn" onclick="openPdfViewer(\'/storage/' + esc(b.pdf_file) + '\', \'' + esc(b.title).replace(/'/g, "\\'") + '\')">\uD83D\uDCD6 Read</button>' : '<button class="book-btn" disabled>\uD83D\uDCD6 Read</button>') +
    (b.pdf_file ? '<button class="book-btn book-btn-pdf" onclick="downloadBook(' + b.id + ')">\uD83D\uDCE5 PDF</button>' : '') +
    '</div></div></div>';
}
function studyCard(s) {
  return '<div class="card"><span class="card-badge badge-study">' + esc(s.category || 'Study') + '</span>' +
    '<h3 class="card-title">' + esc(s.title) + '</h3>' +
    '<p class="card-desc">' + esc(s.description || '') + '</p>' +
    '<div class="study-meta" style="margin-top:0.8rem">' +
    (s.difficulty ? '<span>\uD83D\uDCCA ' + esc(s.difficulty) + '</span>' : '') +
    (s.duration_minutes ? '<span>\u23F1 ' + s.duration_minutes + ' min</span>' : '') +
    (s.author ? '<span>\uD83D\uDC64 ' + esc(s.author) + '</span>' : '') +
    '</div>' +
    (s.scripture_reference ? '<p class="card-meta" style="margin-top:0.4rem">\uD83D\uDCD6 ' + esc(s.scripture_reference) + '</p>' : '') +
    '</div>';
}
function sermonCard(s) {
  var playUrl = s.video_url || s.audio_url || '';
  return '<div class="sermon-card" style="margin-bottom:1rem">' +
    '<button class="sermon-play"' + (playUrl ? ' onclick="window.open(\'' + esc(playUrl) + '\')"' : '') + '>\u25B6</button>' +
    '<div style="flex:1"><h3 class="card-title">' + esc(s.title) + '</h3>' +
    '<p class="card-meta">' + esc(s.speaker) + ' \u2022 ' + fmtDate(s.sermon_date) + (s.duration ? ' \u2022 ' + s.duration : '') + '</p>' +
    '<p class="card-desc">' + esc(s.description || '') + '</p>' +
    (s.series ? '<span class="card-badge badge-worship" style="margin-top:0.4rem">' + esc(s.series) + '</span>' : '') +
    '</div></div>';
}
function reviewCard(r) {
  return '<div class="card"><div class="review-stars">' + renderStars(r.rating) + '</div>' +
    (r.title ? '<div class="card-title" style="font-size:0.95rem;margin-bottom:0.3rem">' + esc(r.title) + '</div>' : '') +
    '<div class="review-text">\u201C' + esc(r.content) + '\u201D</div>' +
    '<div class="review-author">\u2014 ' + esc(r.name) + ' \u2022 ' + fmtDate(r.created_at) + '</div></div>';
}

/* ===== ACTIONS ===== */
function doPray(id) {
  if (prayedIds[id]) return;
  apiCall('/prayer-requests/' + id + '/pray', { method: 'POST' }).then(function(res) {
    if (res) { prayedIds[id] = true; showToast('\uD83D\uDE4F Thank you for praying.'); loadPrayers(); }
  });
}
function submitPrayer() {
  var name = document.getElementById('prayer-name').value;
  var subject = document.getElementById('prayer-subject').value;
  var desc = document.getElementById('prayer-request').value;
  var isAnon = document.getElementById('prayer-anon').checked;
  var isPublic = document.getElementById('prayer-public').checked;
  if (!subject.trim() || !desc.trim()) { showToast('Please enter a subject and prayer request.'); return; }
  apiCall('/prayer-requests', {
    method: 'POST',
    body: JSON.stringify({ name: isAnon ? 'Anonymous' : (name || 'Church Member'), subject: subject, description: desc, is_public: isPublic })
  }).then(function(res) {
    if (res && res.prayer_request) {
      closeModal('prayer');
      document.getElementById('prayer-name').value = '';
      document.getElementById('prayer-subject').value = '';
      document.getElementById('prayer-request').value = '';
      document.getElementById('prayer-anon').checked = false;
      document.getElementById('prayer-public').checked = true;
      showToast('\uD83D\uDE4F Prayer request submitted. We\'re praying with you.');
      loadPrayers();
    } else {
      showToast('Failed to submit prayer request. Please try again.');
    }
  });
}
function submitReview() {
  var name = document.getElementById('review-name').value;
  var email = document.getElementById('review-email').value;
  var title = document.getElementById('review-title').value;
  var text = document.getElementById('review-text').value;
  if (!name.trim() || !email.trim() || !title.trim() || !text.trim() || !selectedRating) { showToast('Please fill in all fields and add a rating.'); return; }
  apiCall('/reviews', {
    method: 'POST',
    body: JSON.stringify({ name: name, email: email, rating: selectedRating, title: title, content: text })
  }).then(function(res) {
    if (res && res.success) {
      closeModal('review');
      document.getElementById('review-name').value = '';
      document.getElementById('review-email').value = '';
      document.getElementById('review-title').value = '';
      document.getElementById('review-text').value = '';
      selectedRating = 0; buildStarInput();
      showToast('\u2B50 Thank you for your review! It will appear once approved.');
      loadReviews();
    } else {
      showToast('Failed to submit review. Please try again.');
    }
  });
}
function submitDonation() {
  var custom = document.getElementById('custom-amount').value;
  var amount = custom ? parseFloat(custom) : selectedGiving;
  if (!amount || amount < 1) { showToast('Please select or enter an amount.'); return; }
  showToast('\uD83D\uDC9B Thank you for your generous gift of $' + amount + '! (Payment integration coming soon)');
}
function downloadBook(id) {
  apiCall('/books/' + id + '/download').then(function(res) {
    if (res && res.success && res.data && res.data.download_url) { window.open(res.data.download_url); }
    else { showToast('PDF not available for this book.'); }
  });
}
function filterBooks() {
  var search = document.getElementById('book-search').value.toLowerCase();
  var filtered = allBooks.filter(function(b) {
    var matchCat = bookFilter === 'All' || b.category === bookFilter;
    var matchSearch = !search || b.title.toLowerCase().indexOf(search) !== -1 || (b.author && b.author.toLowerCase().indexOf(search) !== -1);
    return matchCat && matchSearch;
  });
  document.getElementById('all-books').innerHTML = filtered.map(function(b, i) { return bookCard(b, i); }).join('') || '<p style="color:var(--text-muted)">No books found.</p>';
}
function setBookFilter(cat) {
  bookFilter = cat;
  document.querySelectorAll('.filter-btn').forEach(function(b) { b.classList.toggle('active', b.dataset.cat === cat); });
  filterBooks();
}

/* ===== DATA LOADERS ===== */
function loadAnnouncements() {
  return apiCall('/announcements/active').then(function(res) {
    var items = (res && res.data) ? res.data : [];
    if (items.length > 0) {
      document.getElementById('ticker-text').textContent = items.map(function(a) {
        return '\uD83D\uDCE2 ' + a.title + (a.content ? ': ' + a.content : '');
      }).join('   \u2022   ');
    }
  });
}
function loadVerse() {
  return apiCall('/verses/today').then(function(res) {
    if (res && res.verse) {
      document.getElementById('verse-text').textContent = '\u201C' + res.verse.verse_text + '\u201D';
      document.getElementById('verse-ref').textContent = '\u2014 ' + res.verse.reference;
    } else {
      document.getElementById('verse-text').textContent = '\u201CFor God so loved the world, that he gave his only Son, that whoever believes in him should not perish but have eternal life.\u201D';
      document.getElementById('verse-ref').textContent = '\u2014 John 3:16';
    }
  });
}
function loadBlessing() {
  return apiCall('/blessings/today').then(function(res) {
    if (res && res.blessing) {
      document.getElementById('blessing-title').textContent = res.blessing.title;
      document.getElementById('blessing-text').textContent = res.blessing.content;
      document.getElementById('blessing-author').textContent = res.blessing.author ? '\u2014 ' + res.blessing.author : '';
    }
  });
}
function loadPosts() {
  return apiCall('/posts/featured').then(function(res) {
    var posts = (res && res.data && res.data.data) ? res.data.data : [];
    if (posts.length > 0) {
      document.getElementById('ticker-text').textContent = posts.map(function(p) { return '\uD83D\uDCE2 ' + p.title + (p.excerpt ? ': ' + p.excerpt : ''); }).join('   \u2022   ');
      document.getElementById('home-posts').innerHTML =
        '<div class="section-header"><h2 class="section-title">\uD83D\uDCF0 Latest News</h2></div>' +
        '<div class="cards-grid">' + posts.slice(0, 3).map(function(p) {
          return '<div class="card"><span class="card-badge badge-worship">' + esc(p.category || 'News') + '</span>' +
            '<h3 class="card-title">' + esc(p.title) + '</h3>' +
            '<p class="card-desc">' + esc(p.excerpt || '') + '</p>' +
            '<p class="card-meta" style="margin-top:0.4rem">' + fmtDate(p.published_at) + (p.author_name ? ' \u2022 ' + esc(p.author_name) : '') + '</p></div>';
        }).join('') + '</div>';
    }
  });
}
function loadPrayers() {
  return apiCall('/prayer-requests/public').then(function(res) {
    var prayers = (res && res.data) ? res.data : [];
    document.getElementById('home-prayers').innerHTML = prayers.slice(0, 3).map(prayerCard).join('') || '<p class="loading">No prayer requests yet.</p>';
    document.getElementById('all-prayers').innerHTML = prayers.map(prayerCard).join('') || '<p class="loading">No prayer requests yet.</p>';
  });
}
function loadEvents() {
  return apiCall('/events/upcoming').then(function(res) {
    var events = (res && res.data) ? res.data : [];
    document.getElementById('home-events').innerHTML = events.slice(0, 3).map(eventCard).join('') || '<p class="loading">No upcoming events.</p>';
    document.getElementById('all-events').innerHTML = events.map(eventCard).join('') || '<p class="loading">No upcoming events.</p>';
  });
}
function loadBooks() {
  return apiCall('/books/featured').then(function(res) {
    allBooks = (res && res.data && res.data.data) ? res.data.data : [];
    var cats = ['All'];
    allBooks.forEach(function(b) { if (b.category && cats.indexOf(b.category) === -1) cats.push(b.category); });
    document.getElementById('book-filters').innerHTML = cats.map(function(c) {
      return '<button class="filter-btn ' + (c === bookFilter ? 'active' : '') + '" data-cat="' + c + '" onclick="setBookFilter(\'' + c + '\')">' + c + '</button>';
    }).join('');
    filterBooks();
  });
}
function loadStudies() {
  return apiCall('/bible-studies/featured').then(function(res) {
    var studies = (res && res.data && res.data.data) ? res.data.data : [];
    document.getElementById('all-studies').innerHTML = studies.map(studyCard).join('') || '<p class="loading">No active studies.</p>';
  });
}
function loadSermons() {
  return apiCall('/sermons/featured').then(function(res) {
    var sermons = (res && res.data && res.data.data) ? res.data.data : [];
    document.getElementById('all-sermons').innerHTML = sermons.map(sermonCard).join('') || '<p class="loading">No sermons yet.</p>';
    if (sermons.length > 0) {
      document.getElementById('home-sermon').innerHTML =
        '<div class="section-header"><h2 class="section-title">\uD83C\uDF99\uFE0F Latest Sermon</h2>' +
        '<button class="section-action" onclick="navigate(\'sermons\')">All Sermons</button></div>' +
        sermonCard(sermons[0]);
    }
  });
}
function loadReviews() {
  return apiCall('/reviews/approved').then(function(res) {
    var reviews = [];
    if (res && res.data) {
      reviews = res.data.data ? res.data.data : (Array.isArray(res.data) ? res.data : []);
    }
    var total = reviews.length;
    var sum = 0;
    reviews.forEach(function(r) { sum += (r.rating || 0); });
    var avg = total > 0 ? (sum / total).toFixed(1) : '0';
    document.getElementById('avg-rating').textContent = avg;
    document.getElementById('avg-stars').innerHTML = '<span class="review-stars">' + renderStars(Math.round(parseFloat(avg))) + '</span>';
    document.getElementById('total-reviews').textContent = total + ' reviews';
    document.getElementById('all-reviews').innerHTML = reviews.map(reviewCard).join('') || '<p class="loading">No reviews yet.</p>';
  });
}
function loadChurchSettings() {
  return apiCall('/settings').then(function(res) {
    churchSettings = (res && res.data) ? res.data : {};
    // Support both column naming conventions (church_address vs address)
    var s = churchSettings;
    var name = s.church_name || document.getElementById('nav-church-name').textContent;
    var addr = s.address || s.church_address || '';
    var phone = s.phone || s.church_phone || '';
    var email = s.email || s.church_email || '';
    var desc = s.mission_statement || s.description || s.church_description || '';
    document.getElementById('nav-church-name').textContent = name;
    document.getElementById('mobile-brand-name').textContent = name;
    document.getElementById('footer-name').textContent = name;
    document.getElementById('footer-info').textContent = addr + (phone ? ' \u2022 ' + phone : '');
    document.title = name;
    document.getElementById('about-hero').innerHTML =
      '<h2 class="blessing-title" style="font-size:1.8rem">' + esc(name) + '</h2>' +
      (s.tagline ? '<p style="color:var(--gold);font-style:italic;margin-bottom:0.5rem">' + esc(s.tagline) + '</p>' : '') +
      '<p class="blessing-text" style="font-size:1.05rem">' + esc(desc) + '</p>';
    document.getElementById('about-info').innerHTML =
      '<div class="info-card"><h3 class="info-card-title">\u26EA Service Times</h3><div class="info-card-text">' + esc(s.service_times || '') + '</div></div>' +
      '<div class="info-card"><h3 class="info-card-title">\uD83D\uDCCD Location</h3><p class="info-card-text">' + esc(addr) + '</p>' +
      (s.city ? '<p class="info-card-text">' + esc(s.city) + (s.state ? ', ' + esc(s.state) : '') + (s.zip_code ? ' ' + esc(s.zip_code) : '') + '</p>' : '') +
      '<p class="info-card-text" style="margin-top:0.4rem">\uD83D\uDCDE ' + esc(phone) + '</p><p class="info-card-text">\u2709\uFE0F ' + esc(email) + '</p></div>' +
      '<div class="info-card"><h3 class="info-card-title">\uD83D\uDC64 Leadership</h3><p class="info-card-text">Lead Pastor: ' + esc(s.pastor_name || '') + '</p>' +
      (s.pastor_title ? '<p class="info-card-text">' + esc(s.pastor_title) + '</p>' : '') + '</div>' +
      '<div class="info-card"><h3 class="info-card-title">\uD83C\uDF10 Connect Online</h3><p class="info-card-text">Follow us on social media and stay connected.</p><div class="social-links">' +
      (s.facebook_url ? '<a class="social-link" href="' + esc(s.facebook_url) + '" target="_blank">Facebook</a>' : '') +
      (s.youtube_url ? '<a class="social-link" href="' + esc(s.youtube_url) + '" target="_blank">YouTube</a>' : '') +
      (s.instagram_url ? '<a class="social-link" href="' + esc(s.instagram_url) + '" target="_blank">Instagram</a>' : '') +
      (s.twitter_url ? '<a class="social-link" href="' + esc(s.twitter_url) + '" target="_blank">Twitter</a>' : '') +
      '</div></div>';
  });
}
function loadMinistries() {
  return apiCall('/ministries').then(function(res) {
    var list = (res && res.data && res.data.data) ? res.data.data : [];
    document.getElementById('all-ministries').innerHTML = list.map(function(m, i) {
      return '<div class="volunteer-card" onclick="showToast(\'\uD83D\uDCCB Contact ' + esc(m.leader_name || 'the church') + ' to join ' + esc(m.name) + '!\')">' +
        '<div class="volunteer-icon">' + MINISTRY_ICONS[i % MINISTRY_ICONS.length] + '</div>' +
        '<div class="volunteer-name">' + esc(m.name) + '</div>' +
        (m.category ? '<div class="volunteer-spots">' + esc(m.category) + '</div>' : '') +
        (m.meeting_schedule ? '<div class="volunteer-spots">' + esc(m.meeting_schedule) + '</div>' : '') +
        '</div>';
    }).join('') || '<p class="loading">No ministries listed.</p>';
  });
}
function buildGiving() {
  var amounts = [10, 25, 50, 100, 250, 500];
  document.getElementById('giving-amounts').innerHTML = amounts.map(function(a) {
    return '<button class="giving-amount" onclick="selectGiving(' + a + ', this)">$' + a + '</button>';
  }).join('');
}
function selectGiving(amount, btn) {
  selectedGiving = amount;
  document.querySelectorAll('.giving-amount').forEach(function(b) { b.classList.remove('selected'); });
  btn.classList.add('selected');
  document.getElementById('custom-amount').value = '';
}

/* ===== TESTIMONIES ===== */
function testimonyCard(t) {
  return '<div class="card testimony-card" onclick="viewTestimony(\'' + esc(t.slug) + '\')" style="cursor:pointer">' +
    (t.featured_image ? '<div style="margin:-1.4rem -1.4rem 1rem;border-radius:var(--radius-lg) var(--radius-lg) 0 0;overflow:hidden;height:160px"><img src="/storage/' + esc(t.featured_image) + '" alt="' + esc(t.name) + '" style="width:100%;height:100%;object-fit:cover"></div>' : '') +
    '<div class="prayer-name" style="display:flex;align-items:center;gap:8px">\u271D ' + esc(t.name) + '</div>' +
    (t.born_again_date ? '<p class="card-meta">\uD83D\uDD25 Born Again: ' + fmtDate(t.born_again_date) + '</p>' : '') +
    (t.baptism_date ? '<p class="card-meta">\uD83D\uDCA7 Baptized: ' + fmtDate(t.baptism_date) + '</p>' : '') +
    '<p class="card-desc" style="margin-top:0.6rem">\u201C' + esc(t.excerpt || (t.testimony || '').substring(0, 150)) + '...\u201D</p>' +
    '<div class="prayer-footer" style="margin-top:0.8rem"><span class="prayer-date">' + fmtDate(t.published_at || t.created_at) + '</span>' +
    '<span style="font-size:0.78rem;color:var(--text-muted)">\uD83D\uDC41 ' + (t.view_count || 0) + ' views</span></div></div>';
}
function loadTestimonies() {
  return apiCall('/testimonies/approved').then(function(res) {
    var testimonies = (res && res.data) ? res.data : [];
    document.getElementById('all-testimonies').innerHTML = testimonies.map(testimonyCard).join('') || '<p class="loading">No testimonies shared yet. Be the first!</p>';
  });
}
function viewTestimony(slug) {
  apiCall('/testimonies/' + slug).then(function(res) {
    if (res && res.success && res.data) {
      var t = res.data;
      var html = '<div class="modal-content" style="max-width:640px">' +
        '<div class="modal-title">\u271D ' + esc(t.name) + '\'s Testimony <button class="modal-close" onclick="closeModal(\'testimony-view\')">\u2715</button></div>' +
        (t.featured_image ? '<div style="margin-bottom:1rem;border-radius:var(--radius-md);overflow:hidden"><img src="/storage/' + esc(t.featured_image) + '" alt="' + esc(t.name) + '" style="width:100%;max-height:250px;object-fit:cover"></div>' : '') +
        '<div style="display:flex;gap:1.5rem;margin-bottom:1rem;flex-wrap:wrap">' +
        (t.born_again_date ? '<div style="font-size:0.85rem;color:var(--text-secondary)"><strong style="color:var(--gold)">\uD83D\uDD25 Born Again:</strong> ' + fmtDate(t.born_again_date) + '</div>' : '') +
        (t.baptism_date ? '<div style="font-size:0.85rem;color:var(--text-secondary)"><strong style="color:var(--gold)">\uD83D\uDCA7 Baptized:</strong> ' + fmtDate(t.baptism_date) + '</div>' : '') +
        '</div>' +
        '<div style="font-family:var(--font-elegant);font-size:1.05rem;line-height:1.8;color:var(--text-primary);white-space:pre-wrap">' + esc(t.testimony) + '</div>' +
        '<div style="margin-top:1.2rem;padding-top:0.8rem;border-top:1px solid var(--border);font-size:0.82rem;color:var(--text-muted);display:flex;justify-content:space-between">' +
        '<span>' + fmtDate(t.published_at || t.created_at) + '</span>' +
        '<span>\uD83D\uDC41 ' + (t.view_count || 0) + ' views</span></div></div>';
      // Create a dynamic view modal
      var overlay = document.getElementById('modal-testimony-view');
      if (!overlay) {
        overlay = document.createElement('div');
        overlay.className = 'modal-overlay';
        overlay.id = 'modal-testimony-view';
        overlay.addEventListener('click', function(e) { if (e.target === overlay) closeModal('testimony-view'); });
        document.body.appendChild(overlay);
      }
      overlay.innerHTML = html;
      openModal('testimony-view');
    }
  });
}
function submitTestimony() {
  var name = document.getElementById('testimony-name').value;
  var bornAgain = document.getElementById('testimony-born-again').value;
  var baptism = document.getElementById('testimony-baptism').value;
  var text = document.getElementById('testimony-text').value;
  if (!name.trim() || !text.trim()) { showToast('Please enter your name and testimony.'); return; }
  if (text.trim().length < 20) { showToast('Please write at least 20 characters for your testimony.'); return; }
  apiCall('/testimonies', {
    method: 'POST',
    body: JSON.stringify({ name: name, born_again_date: bornAgain || null, baptism_date: baptism || null, testimony: text })
  }).then(function(res) {
    if (res && res.success) {
      closeModal('testimony');
      document.getElementById('testimony-name').value = '';
      document.getElementById('testimony-born-again').value = '';
      document.getElementById('testimony-baptism').value = '';
      document.getElementById('testimony-text').value = '';
      showToast('\u271D Thank you for sharing your testimony! It will appear after approval.');
      loadTestimonies();
    } else {
      showToast(res && res.message ? res.message : 'Failed to submit testimony. Please try again.');
    }
  });
}

/* ===== PWA ===== */
var deferredPrompt = null;
function dismissPwaInstall() {
  document.getElementById('pwa-install').classList.remove('show');
  sessionStorage.setItem('pwa-dismissed', '1');
}
if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register('/sw.js').catch(function(e) { console.log('SW registration skipped:', e.message); });
}
window.addEventListener('beforeinstallprompt', function(e) {
  e.preventDefault();
  deferredPrompt = e;
  if (!sessionStorage.getItem('pwa-dismissed')) {
    document.getElementById('pwa-install').classList.add('show');
  }
});
document.getElementById('pwa-install-btn').addEventListener('click', function() {
  if (deferredPrompt) {
    deferredPrompt.prompt();
    deferredPrompt.userChoice.then(function() { deferredPrompt = null; dismissPwaInstall(); });
  }
});

/* ===== AUTH ===== */
var authToken = localStorage.getItem('auth_token') || null;
var authUser = null;
try { authUser = JSON.parse(localStorage.getItem('auth_user')); } catch(e) {}

function updateAuthUI() {
  var loginBtn = document.getElementById('auth-login-btn');
  var userMenu = document.getElementById('auth-user-menu');
  var mobileLoginBtn = document.getElementById('mobile-login-btn');
  var mobileUserInfo = document.getElementById('mobile-user-info');
  if (authUser && authToken) {
    loginBtn.style.display = 'none';
    userMenu.style.display = '';
    var initials = (authUser.name || '?').split(' ').map(function(n) { return n[0]; }).join('').toUpperCase().slice(0, 2);
    document.getElementById('auth-avatar-text').textContent = initials;
    document.getElementById('auth-dropdown-name').textContent = authUser.name || '';
    document.getElementById('auth-dropdown-email').textContent = authUser.email || '';
    if (mobileLoginBtn) mobileLoginBtn.style.display = 'none';
    if (mobileUserInfo) { mobileUserInfo.style.display = ''; document.getElementById('mobile-user-name').textContent = authUser.name || ''; }
  } else {
    loginBtn.style.display = '';
    userMenu.style.display = 'none';
    if (mobileLoginBtn) mobileLoginBtn.style.display = '';
    if (mobileUserInfo) mobileUserInfo.style.display = 'none';
  }
}

function toggleUserDropdown() {
  var dd = document.getElementById('auth-dropdown');
  dd.classList.toggle('open');
}

// Close dropdown on outside click
document.addEventListener('click', function(e) {
  var dd = document.getElementById('auth-dropdown');
  var btn = document.getElementById('auth-avatar-btn');
  if (dd && btn && !btn.contains(e.target) && !dd.contains(e.target)) {
    dd.classList.remove('open');
  }
});

function showLoginForm() {
  document.getElementById('auth-login-form').style.display = '';
  document.getElementById('auth-register-form').style.display = 'none';
  document.getElementById('auth-modal-title').textContent = 'Sign In';
  document.getElementById('auth-error').style.display = 'none';
  document.getElementById('reg-error').style.display = 'none';
}

function showRegisterForm() {
  document.getElementById('auth-login-form').style.display = 'none';
  document.getElementById('auth-register-form').style.display = '';
  document.getElementById('auth-modal-title').textContent = 'Create Account';
  document.getElementById('auth-error').style.display = 'none';
  document.getElementById('reg-error').style.display = 'none';
}

function doLogin() {
  var email = document.getElementById('auth-email').value.trim();
  var password = document.getElementById('auth-password').value;
  var errEl = document.getElementById('auth-error');
  errEl.style.display = 'none';

  if (!email || !password) { errEl.textContent = 'Please enter email and password.'; errEl.style.display = ''; return; }

  var btn = document.getElementById('auth-submit-btn');
  btn.disabled = true; btn.textContent = 'Signing in...';

  apiCall('/login', {
    method: 'POST',
    body: JSON.stringify({ email: email, password: password })
  }).then(function(res) {
    btn.disabled = false; btn.textContent = 'Sign In';
    if (res && res.token) {
      authToken = res.token;
      authUser = res.user;
      localStorage.setItem('auth_token', authToken);
      localStorage.setItem('auth_user', JSON.stringify(authUser));
      updateAuthUI();
      closeModal('auth');
      showToast('Welcome back, ' + (authUser.name || '') + '!');
      document.getElementById('auth-email').value = '';
      document.getElementById('auth-password').value = '';
    } else {
      errEl.textContent = (res && res.message) || 'Invalid credentials.';
      errEl.style.display = '';
    }
  }).catch(function() {
    btn.disabled = false; btn.textContent = 'Sign In';
    errEl.textContent = 'Invalid credentials. Please try again.';
    errEl.style.display = '';
  });
}

function doRegister() {
  var name = document.getElementById('reg-name').value.trim();
  var email = document.getElementById('reg-email').value.trim();
  var password = document.getElementById('reg-password').value;
  var confirm = document.getElementById('reg-password-confirm').value;
  var errEl = document.getElementById('reg-error');
  errEl.style.display = 'none';

  if (!name || !email || !password || !confirm) { errEl.textContent = 'Please fill in all fields.'; errEl.style.display = ''; return; }
  if (password.length < 8) { errEl.textContent = 'Password must be at least 8 characters.'; errEl.style.display = ''; return; }
  if (password !== confirm) { errEl.textContent = 'Passwords do not match.'; errEl.style.display = ''; return; }

  var btn = document.getElementById('reg-submit-btn');
  btn.disabled = true; btn.textContent = 'Creating account...';

  apiCall('/register', {
    method: 'POST',
    body: JSON.stringify({ name: name, email: email, password: password, password_confirmation: confirm })
  }).then(function(res) {
    btn.disabled = false; btn.textContent = 'Create Account';
    if (res && res.token) {
      authToken = res.token;
      authUser = res.user;
      localStorage.setItem('auth_token', authToken);
      localStorage.setItem('auth_user', JSON.stringify(authUser));
      updateAuthUI();
      closeModal('auth');
      showToast('Welcome, ' + (authUser.name || '') + '! Account created successfully.');
      document.getElementById('reg-name').value = '';
      document.getElementById('reg-email').value = '';
      document.getElementById('reg-password').value = '';
      document.getElementById('reg-password-confirm').value = '';
    } else {
      errEl.textContent = (res && res.message) || 'Registration failed. Please try again.';
      errEl.style.display = '';
    }
  }).catch(function() {
    btn.disabled = false; btn.textContent = 'Create Account';
    errEl.textContent = 'Registration failed. Email may already be in use.';
    errEl.style.display = '';
  });
}

function doLogout() {
  var dd = document.getElementById('auth-dropdown');
  dd.classList.remove('open');

  if (authToken) {
    apiCall('/logout', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': 'Bearer ' + authToken,
        'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') || {}).content || ''
      }
    });
  }

  authToken = null;
  authUser = null;
  localStorage.removeItem('auth_token');
  localStorage.removeItem('auth_user');
  updateAuthUI();
  showToast('Signed out successfully.');
}

// Handle social auth callback (token passed via URL params)
(function handleSocialAuthCallback() {
  var params = new URLSearchParams(window.location.search);
  var token = params.get('auth_token');
  var user = params.get('auth_user');
  var error = params.get('auth_error');

  if (token && user) {
    try {
      authToken = token;
      authUser = JSON.parse(user);
      localStorage.setItem('auth_token', authToken);
      localStorage.setItem('auth_user', JSON.stringify(authUser));
      // Clean URL
      window.history.replaceState({}, '', window.location.pathname);
      setTimeout(function() { showToast('Welcome, ' + (authUser.name || '') + '!'); }, 500);
    } catch(e) {}
  } else if (error) {
    window.history.replaceState({}, '', window.location.pathname);
    setTimeout(function() { showToast(error); }, 500);
  }
})();

// Enter key support for auth forms
document.addEventListener('keydown', function(e) {
  if (e.key === 'Enter') {
    var authModal = document.getElementById('modal-auth');
    if (authModal && authModal.classList.contains('open')) {
      if (document.getElementById('auth-login-form').style.display !== 'none') {
        doLogin();
      } else {
        doRegister();
      }
    }
  }
});

/* ===== PDF VIEWER ===== */
var pdfDoc = null;
var pdfPage = 0;
var pdfTotal = 0;
var pdfScale = 0;
var pdfBaseScale = 0;
var pdfRendering = false;

function openPdfViewer(url, title) {
  var overlay = document.getElementById('pdf-viewer');
  document.getElementById('pdf-viewer-title').textContent = title || 'Book Viewer';
  document.getElementById('pdf-page-info').textContent = 'Loading...';
  document.getElementById('pdf-loading').style.display = 'flex';
  document.getElementById('pdf-prev').disabled = true;
  document.getElementById('pdf-next').disabled = true;
  overlay.classList.add('open');
  document.body.style.overflow = 'hidden';

  pdfDoc = null; pdfPage = 1; pdfTotal = 0; pdfScale = 0;

  if (typeof pdfjsLib !== 'undefined') {
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
  }

  pdfjsLib.getDocument(url).promise.then(function(doc) {
    pdfDoc = doc;
    pdfTotal = doc.numPages;
    document.getElementById('pdf-loading').style.display = 'none';
    renderPdfPage(pdfPage);
  }).catch(function(err) {
    document.getElementById('pdf-loading').textContent = 'Failed to load PDF. Try the download button instead.';
    console.error('PDF load error:', err);
  });
}

function renderPdfPage(num, flipDir) {
  if (!pdfDoc || pdfRendering) return;
  pdfRendering = true;
  pdfPage = num;
  document.getElementById('pdf-page-info').textContent = num + ' / ' + pdfTotal;
  document.getElementById('pdf-prev').disabled = (num <= 1);
  document.getElementById('pdf-next').disabled = (num >= pdfTotal);

  pdfDoc.getPage(num).then(function(page) {
    var canvas = document.getElementById('pdf-canvas');
    var ctx = canvas.getContext('2d');
    var container = document.getElementById('pdf-canvas-container');

    if (pdfScale === 0) {
      // Auto-fit: calculate scale based on available space
      var bodyEl = document.querySelector('.pdf-viewer-body');
      var availW = bodyEl.clientWidth - 140;
      var availH = bodyEl.clientHeight - 40;
      var vp = page.getViewport({ scale: 1 });
      var scaleW = availW / vp.width;
      var scaleH = availH / vp.height;
      pdfScale = Math.min(scaleW, scaleH, 2);
      pdfBaseScale = pdfScale;
    }

    var viewport = page.getViewport({ scale: pdfScale });
    canvas.width = viewport.width;
    canvas.height = viewport.height;

    page.render({ canvasContext: ctx, viewport: viewport }).promise.then(function() {
      pdfRendering = false;
      // Flip animation
      if (flipDir) {
        container.classList.add(flipDir === 'left' ? 'flip-left' : 'flip-right');
        setTimeout(function() { container.classList.remove('flip-left', 'flip-right'); }, 500);
      }
    });
  });
}

function pdfPrev() {
  if (pdfPage <= 1) return;
  renderPdfPage(pdfPage - 1, 'right');
}

function pdfNext() {
  if (pdfPage >= pdfTotal) return;
  renderPdfPage(pdfPage + 1, 'left');
}

function pdfZoom(delta, fit) {
  if (!pdfDoc) return;
  if (fit) {
    pdfScale = 0; // Will auto-fit
  } else {
    pdfScale = Math.max(0.5, Math.min(3, pdfScale + delta));
  }
  renderPdfPage(pdfPage);
}

function closePdfViewer() {
  document.getElementById('pdf-viewer').classList.remove('open');
  document.body.style.overflow = '';
  pdfDoc = null;
  var canvas = document.getElementById('pdf-canvas');
  var ctx = canvas.getContext('2d');
  ctx.clearRect(0, 0, canvas.width, canvas.height);
}

// Keyboard navigation for PDF viewer
document.addEventListener('keydown', function(e) {
  var overlay = document.getElementById('pdf-viewer');
  if (!overlay.classList.contains('open')) return;
  if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') { e.preventDefault(); pdfPrev(); }
  if (e.key === 'ArrowRight' || e.key === 'ArrowDown' || e.key === ' ') { e.preventDefault(); pdfNext(); }
  if (e.key === 'Escape') { closePdfViewer(); }
});

/* ===== INIT ===== */
buildNav();
buildStarInput();
buildGiving();
updateAuthUI();
Promise.allSettled([
  loadVerse(), loadBlessing(), loadAnnouncements(), loadPosts(), loadPrayers(), loadEvents(),
  loadBooks(), loadStudies(), loadSermons(), loadReviews(), loadTestimonies(), loadChurchSettings(), loadMinistries()
]);
document.querySelectorAll('.modal-overlay').forEach(function(m) {
  m.addEventListener('click', function(e) { if (e.target === m) m.classList.remove('open'); });
});
</script>
