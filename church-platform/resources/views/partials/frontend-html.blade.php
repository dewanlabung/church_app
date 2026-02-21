<!-- NAV -->
<nav class="nav-bar">
  <div class="nav-inner">
    <div class="nav-brand" onclick="navigate('home')">
      <div class="nav-brand-icon">&#10013;</div>
      <span class="nav-brand-text" id="nav-church-name">{{ config('app.name', 'Grace Community Church') }}</span>
    </div>
    <ul class="nav-links" id="nav-links"></ul>
    <div class="nav-right">
      <button class="theme-toggle" id="theme-toggle" onclick="toggleTheme()" title="Toggle dark/light mode">&#9790;</button>
      <button class="nav-mobile-btn" id="hamburger-btn" onclick="toggleMobile()">&#9776;</button>
    </div>
  </div>
</nav>
<!-- MOBILE MENU (slide-in) -->
<div class="mobile-menu" id="mobile-menu">
  <div class="mobile-header">
    <span class="mobile-header-brand" id="mobile-brand-name">{{ config('app.name', 'Grace Community Church') }}</span>
    <button class="mobile-close" onclick="toggleMobile()">&#10005;</button>
  </div>
  <div class="mobile-nav-list" id="mobile-links"></div>
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
      <h2 class="section-title">&#128591; Prayer Requests</h2>
      <button class="section-action" onclick="openModal('prayer')">+ Submit Prayer</button>
    </div>
    <div class="cards-grid" id="home-prayers"></div>
    <div class="section-header">
      <h2 class="section-title">&#128197; Upcoming Events</h2>
      <button class="section-action" onclick="navigate('events')">View All</button>
    </div>
    <div class="cards-grid" id="home-events"></div>
    <div id="home-sermon"></div>
  </div>
  <!-- EVENTS -->
  <div class="page-section" id="page-events">
    <div class="section-header"><h2 class="section-title">&#128197; Church Events</h2></div>
    <div class="cards-grid" id="all-events"></div>
  </div>
  <!-- PRAYERS -->
  <div class="page-section" id="page-prayers">
    <div class="section-header">
      <h2 class="section-title">&#128591; Prayer Wall</h2>
      <button class="section-action" onclick="openModal('prayer')">+ Submit Prayer</button>
    </div>
    <p style="color:var(--text-secondary);margin-bottom:1.2rem;font-style:italic">"Pray for one another, that you may be healed." &mdash; James 5:16</p>
    <div class="cards-grid" id="all-prayers"></div>
  </div>
  <!-- LIBRARY -->
  <div class="page-section" id="page-library">
    <div class="section-header"><h2 class="section-title">&#128218; Book Library</h2></div>
    <div class="search-bar">
      <span>&#128269;</span>
      <input placeholder="Search books by title or author..." id="book-search" oninput="filterBooks()">
    </div>
    <div class="filter-btns" id="book-filters"></div>
    <div class="cards-grid" id="all-books"></div>
  </div>
  <!-- BIBLE STUDY -->
  <div class="page-section" id="page-studies">
    <div class="section-header"><h2 class="section-title">&#128214; Bible Study Hub</h2></div>
    <p style="color:var(--text-secondary);margin-bottom:1.2rem">Join a Bible study group and deepen your understanding of God's Word together.</p>
    <div class="cards-grid" id="all-studies"></div>
  </div>
  <!-- SERMONS -->
  <div class="page-section" id="page-sermons">
    <div class="section-header"><h2 class="section-title">&#127897;&#65039; Sermon Archive</h2></div>
    <div id="all-sermons"></div>
  </div>
  <!-- GIVING -->
  <div class="page-section" id="page-giving">
    <div class="section-header"><h2 class="section-title">&#128155; Online Giving</h2></div>
    <div class="giving-card">
      <h3 style="font-family:var(--font-display);font-size:1.7rem;color:var(--cream);margin-bottom:0.4rem">Give Generously</h3>
      <p style="color:var(--text-secondary);max-width:480px;margin:0 auto 1.2rem">"Each of you should give what you have decided in your heart to give, not reluctantly or under compulsion, for God loves a cheerful giver." &mdash; 2 Corinthians 9:7</p>
      <div class="giving-amounts" id="giving-amounts"></div>
      <div style="margin:1rem 0"><input class="form-input" placeholder="Or enter custom amount..." id="custom-amount" style="max-width:280px;text-align:center;margin:0 auto;display:block"></div>
      <button class="btn-primary" style="max-width:280px;margin:0 auto" onclick="submitDonation()">&#128155; Give Now</button>
    </div>
  </div>
  <!-- MINISTRIES -->
  <div class="page-section" id="page-ministries">
    <div class="section-header"><h2 class="section-title">&#129309; Ministries</h2></div>
    <p style="color:var(--text-secondary);margin-bottom:1.2rem">Use your God-given gifts to serve our community.</p>
    <div class="volunteer-grid" id="all-ministries"></div>
  </div>
  <!-- REVIEWS -->
  <div class="page-section" id="page-reviews">
    <div class="section-header">
      <h2 class="section-title">&#11088; Church Reviews</h2>
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
    <div class="section-header"><h2 class="section-title">&#9962; About Our Church</h2></div>
    <div class="blessing-card" style="margin-bottom:1.5rem" id="about-hero"></div>
    <div class="info-grid" id="about-info"></div>
  </div>
</main>
<!-- FOOTER -->
<footer class="site-footer">
  <div class="footer-brand" id="footer-name">{{ config('app.name', 'Grace Community Church') }}</div>
  <p class="footer-text" id="footer-info"></p>
  <p class="footer-text" style="margin-top:0.4rem">"And let us consider how we may spur one another on toward love and good deeds." &mdash; Hebrews 10:24</p>
</footer>
<!-- MODALS -->
<div class="modal-overlay" id="modal-prayer">
  <div class="modal-content">
    <div class="modal-title">Submit Prayer Request <button class="modal-close" onclick="closeModal('prayer')">&#10005;</button></div>
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
    <button class="btn-primary" onclick="submitPrayer()">&#128591; Submit Prayer Request</button>
  </div>
</div>
<div class="modal-overlay" id="modal-review">
  <div class="modal-content">
    <div class="modal-title">Write a Review <button class="modal-close" onclick="closeModal('review')">&#10005;</button></div>
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
    <button class="btn-primary" onclick="submitReview()">&#11088; Submit Review</button>
  </div>
</div>
<!-- TOAST -->
<div class="toast" id="toast"></div>
<!-- PWA INSTALL -->
<div class="pwa-install" id="pwa-install">
  <div class="pwa-install-text">
    <div class="pwa-install-title">Install App</div>
    <div class="pwa-install-desc">Add to home screen for quick access</div>
  </div>
  <button class="pwa-install-btn" id="pwa-install-btn">Install</button>
  <button class="pwa-install-close" onclick="dismissPwaInstall()">&#10005;</button>
</div>
