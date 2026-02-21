<!-- NAV -->
<nav class="nav-bar">
  <div class="nav-inner">
    <div class="nav-brand" onclick="navigate('home')">
      <div class="nav-brand-icon">✝</div>
      <span class="nav-brand-text" id="nav-church-name">{{ config('app.name', 'Grace Community Church') }}</span>
    </div>
    <ul class="nav-links" id="nav-links"></ul>
    <button class="nav-mobile-btn" onclick="toggleMobile()">☰</button>
  </div>
</nav>
<!-- MOBILE MENU -->
<div class="mobile-menu" id="mobile-menu">
  <button class="mobile-close" onclick="toggleMobile()">✕</button>
  <div id="mobile-links"></div>
</div>
<!-- MAIN -->
<main class="main-content">
  <!-- HOME -->
  <div class="page-section active" id="page-home">
    <div class="ticker">
      <span class="ticker-label">Announcements</span>
      <span class="ticker-text" id="ticker-text">Welcome to our church! Check back for announcements.</span>
    </div>
    <section class="hero-section">
      <div class="hero-label">Verse of the Day</div>
      <p class="verse-text" id="verse-text">"Loading..."</p>
      <span class="verse-ref" id="verse-ref"></span>
    </section>
    <section class="blessing-card">
      <h2 class="blessing-title" id="blessing-title">Today's Blessing</h2>
      <p class="blessing-text" id="blessing-text">Loading...</p>
      <p class="blessing-author" id="blessing-author"></p>
    </section>
    <div id="home-posts"></div>
    <div class="section-header">
      <h2 class="section-title">🙏 Prayer Requests</h2>
      <button class="section-action" onclick="openModal('prayer')">+ Submit Prayer</button>
    </div>
    <div class="cards-grid" id="home-prayers"></div>
    <div class="section-header">
      <h2 class="section-title">📅 Upcoming Events</h2>
      <button class="section-action" onclick="navigate('events')">View All</button>
    </div>
    <div class="cards-grid" id="home-events"></div>
    <div id="home-sermon"></div>
  </div>
  <!-- EVENTS -->
  <div class="page-section" id="page-events">
    <div class="section-header"><h2 class="section-title">📅 Church Events</h2></div>
    <div class="cards-grid" id="all-events"></div>
  </div>
  <!-- PRAYERS -->
  <div class="page-section" id="page-prayers">
    <div class="section-header">
      <h2 class="section-title">🙏 Prayer Wall</h2>
      <button class="section-action" onclick="openModal('prayer')">+ Submit Prayer</button>
    </div>
    <p style="color:var(--text-secondary);margin-bottom:1.2rem;font-style:italic">"Pray for one another, that you may be healed." — James 5:16</p>
    <div class="cards-grid" id="all-prayers"></div>
  </div>
  <!-- LIBRARY -->
  <div class="page-section" id="page-library">
    <div class="section-header"><h2 class="section-title">📚 Book Library</h2></div>
    <div class="search-bar">
      <span>🔍</span>
      <input placeholder="Search books by title or author..." id="book-search" oninput="filterBooks()">
    </div>
    <div class="filter-btns" id="book-filters"></div>
    <div class="cards-grid" id="all-books"></div>
  </div>
  <!-- BIBLE STUDY -->
  <div class="page-section" id="page-studies">
    <div class="section-header"><h2 class="section-title">📖 Bible Study Hub</h2></div>
    <p style="color:var(--text-secondary);margin-bottom:1.2rem">Join a Bible study group and deepen your understanding of God's Word together.</p>
    <div class="cards-grid" id="all-studies"></div>
  </div>
  <!-- SERMONS -->
  <div class="page-section" id="page-sermons">
    <div class="section-header"><h2 class="section-title">🎙️ Sermon Archive</h2></div>
    <div id="all-sermons"></div>
  </div>
  <!-- GIVING -->
  <div class="page-section" id="page-giving">
    <div class="section-header"><h2 class="section-title">💛 Online Giving</h2></div>
    <div class="giving-card">
      <h3 style="font-family:var(--font-display);font-size:1.7rem;color:var(--cream);margin-bottom:0.4rem">Give Generously</h3>
      <p style="color:var(--text-secondary);max-width:480px;margin:0 auto 1.2rem">"Each of you should give what you have decided in your heart to give, not reluctantly or under compulsion, for God loves a cheerful giver." — 2 Corinthians 9:7</p>
      <div class="giving-amounts" id="giving-amounts"></div>
      <div style="margin:1rem 0"><input class="form-input" placeholder="Or enter custom amount..." id="custom-amount" style="max-width:280px;text-align:center;margin:0 auto;display:block"></div>
      <button class="btn-primary" style="max-width:280px;margin:0 auto" onclick="submitDonation()">💛 Give Now</button>
    </div>
  </div>
  <!-- MINISTRIES -->
  <div class="page-section" id="page-ministries">
    <div class="section-header"><h2 class="section-title">🤝 Ministries</h2></div>
    <p style="color:var(--text-secondary);margin-bottom:1.2rem">Use your God-given gifts to serve our community.</p>
    <div class="volunteer-grid" id="all-ministries"></div>
  </div>
  <!-- REVIEWS -->
  <div class="page-section" id="page-reviews">
    <div class="section-header">
      <h2 class="section-title">⭐ Church Reviews</h2>
      <button class="section-action" onclick="openModal('review')">+ Write Review</button>
    </div>
    <div style="margin-bottom:1.5rem;display:flex;align-items:center;gap:1rem">
      <span class="avg-rating" id="avg-rating">0</span>
      <div>
        <div id="avg-stars"></div>
        <span style="font-size:0.82rem;color:var(--text-muted)" id="total-reviews">0 reviews</span>
      </div>
    </div>
    <div class="cards-grid" id="all-reviews"></div>
  </div>
  <!-- ABOUT -->
  <div class="page-section" id="page-about">
    <div class="section-header"><h2 class="section-title">⛪ About Our Church</h2></div>
    <div class="blessing-card" style="margin-bottom:1.5rem" id="about-hero"></div>
    <div class="info-grid" id="about-info"></div>
  </div>
</main>
<!-- FOOTER -->
<footer class="site-footer">
  <div class="footer-brand" id="footer-name">{{ config('app.name', 'Grace Community Church') }}</div>
  <p class="footer-text" id="footer-info"></p>
  <p class="footer-text" style="margin-top:0.4rem">"And let us consider how we may spur one another on toward love and good deeds." — Hebrews 10:24</p>
</footer>
<!-- MODALS -->
<div class="modal-overlay" id="modal-prayer">
  <div class="modal-content">
    <div class="modal-title">Submit Prayer Request <button class="modal-close" onclick="closeModal('prayer')">✕</button></div>
    <div class="form-group">
      <label class="form-label">Your Name</label>
      <input class="form-input" id="prayer-name" placeholder="Enter your name">
    </div>
    <div class="form-checkbox-row">
      <input type="checkbox" class="form-checkbox" id="prayer-anon" onchange="document.getElementById('prayer-name').disabled=this.checked">
      <label style="font-size:0.88rem;color:var(--text-secondary)">Submit anonymously</label>
    </div>
    <div class="form-group">
      <label class="form-label">Subject</label>
      <input class="form-input" id="prayer-subject" placeholder="Brief subject for your prayer request">
    </div>
    <div class="form-group">
      <label class="form-label">Prayer Request</label>
      <textarea class="form-textarea" id="prayer-request" placeholder="Share your prayer request with the church family..."></textarea>
    </div>
    <div class="form-checkbox-row">
      <input type="checkbox" class="form-checkbox" id="prayer-public" checked>
      <label style="font-size:0.88rem;color:var(--text-secondary)">Make visible to the community</label>
    </div>
    <button class="btn-primary" onclick="submitPrayer()">🙏 Submit Prayer Request</button>
  </div>
</div>
<div class="modal-overlay" id="modal-review">
  <div class="modal-content">
    <div class="modal-title">Write a Review <button class="modal-close" onclick="closeModal('review')">✕</button></div>
    <div class="form-group">
      <label class="form-label">Your Name</label>
      <input class="form-input" id="review-name" placeholder="Enter your name">
    </div>
    <div class="form-group">
      <label class="form-label">Your Email</label>
      <input class="form-input" id="review-email" placeholder="Enter your email" type="email">
    </div>
    <div class="form-group">
      <label class="form-label">Rating</label>
      <div class="star-input" id="star-input"></div>
    </div>
    <div class="form-group">
      <label class="form-label">Review Title</label>
      <input class="form-input" id="review-title" placeholder="Title for your review">
    </div>
    <div class="form-group">
      <label class="form-label">Your Review</label>
      <textarea class="form-textarea" id="review-text" placeholder="Share your experience with our church..."></textarea>
    </div>
    <button class="btn-primary" onclick="submitReview()">⭐ Submit Review</button>
  </div>
</div>
<!-- TOAST -->
<div class="toast" id="toast"></div>
