# Attention Network — WordPress Business Operations Suite

[![Version](https://img.shields.io/badge/version-1.1.0-blue.svg)](attention-network.php)
[![License](https://img.shields.io/badge/license-GPLv2-green.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net)
[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-0073aa.svg)](https://wordpress.org)

**Attention Network** is a comprehensive, enterprise-grade **Business Operations & Growth Platform** for WordPress. It connects client websites directly to their Attention Network organization, empowering businesses across any local industry—from dining and retail to home contracting, medical/wellness clinics, auto repair, and professional services—to manage their digital operations from a single wp-admin dashboard.

---

## 🌟 Key Capabilities

### 1. Universal Business Hours & 24/7 Emergency Dispatch
- **7-Day Regular Hours**: Configure regular opening and closing hours by day of the week with multi-shift support.
- **24/7 Emergency Dispatch**: Instantly toggle urgent on-call status across all web and mobile surfaces.
- **Special Schedule Overrides**: Add holiday closures, special event schedules, or temporary maintenance notices.
- **Shortcodes**:
  - `[attention_hours]`: Full weekly schedule table.
  - `[attention_open_badge]`: Live dynamic badge showing current open/closed status or emergency dispatch alert.

### 2. Staff Directory & "Meet Your Pro" Security Cards
- **Verified Technician Credentials**: Display staff profiles with photos, titles, master state licenses, and verified background check clearance badges.
- **Client Direct Actions**: Direct booking links and tip links (Venmo, Cash App, etc.) for field technicians and service pros.
- **Shortcodes**:
  - `[attention_staff_directory]`: Full responsive team directory.
  - `[attention_pro name="..."]`: Single technician trust card.

### 3. Offers, Specials & Redeemable Coupons Hub
- **Promotional Campaigns**: Create percentage off, dollar discount, free service/inspection, and BOGO bundle offers.
- **Conversion Badges**: Add highlight badges (*"Most Popular"*, *"New Client Special"*, *"Limited Time"*).
- **Interactive Vouchers**: 1-click promo code copying with clipboard feedback, expiration countdowns, and terms & conditions.
- **Shortcodes**:
  - `[attention_offers category="HVAC" limit="6"]`: Grid of responsive coupon vouchers.
  - `[attention_offer id="..."]`: Single offer embed card.

### 4. Customer Reviews & Reputation Hub
- **Reputation Overview**: Live aggregate ratings (e.g. ★★★★★ 4.9 from verified clients) with Google Rich Snippet `AggregateRating` microdata.
- **Official Owner Responses**: Business owners and managers can draft, edit, and publish verified owner replies directly from wp-admin.
- **Shortcodes**:
  - `[attention_rating_badge]`: Compact trust seal for headers or footers.
  - `[attention_reviews min_rating="4" limit="6"]`: Testimonial grid showcasing verified customer reviews and owner responses.

### 5. Inbound Leads, Quotes & Emergency Dispatch Queue
- **Lead Intake Pipeline**: Inbound queue with workflow stages (`New` ➔ `Contacted` ➔ `Quoted` ➔ `Scheduled` ➔ `Completed`).
- **Internal Staff Notes**: Private staff notes on each lead, plus instant click-to-call and click-to-email actions.
- **Government-Grade Anti-Abuse**:
  1. *Anti-Spam Honeypot*: Invisible bot trap silently drops automated submissions.
  2. *Velocity Verifier*: Rejects sub-2-second bot form fills.
  3. *Transient IP Rate Limiting*: Caps submissions at 5 per 10 minutes per IP address.
- **Shortcode**:
  - `[attention_lead_form type="quote|emergency|consultation"]`: Mobile-first intake form with instant AJAX feedback.

### 6. Before & After Project Portfolio Showcase
- **Craftsmanship Transformation Sliders**: Interactive split comparison sliders for contractors, remodelers, roofers, auto detailers, and cosmetic clinics.
- **Shortcodes**:
  - `[attention_before_after before="..." after="..." label="..."]`: Touch/drag interactive split image slider.
  - `[attention_portfolio category="..." limit="6"]`: Full transformation showcase gallery.

### 7. Attention Push Notifications (Web Push)
- Broadcast web push notifications directly from WordPress to opted-in desktop & mobile browser subscribers.
- Segment broadcasts by interest tags or offer categories.
- **Shortcode**:
  - `[attention_push_prompt button_text="..."]`: Universal opt-in button.

### 8. Food, Beverage & Service Menus
- Full live menu management with multi-section support, dietary tags, ABV/IBU brewery stats, and modifier options.
- Layout modes: Standard Restaurant, Taplist Brewery Board, and Mobile QR view.
- **Shortcode**: `[attention_menu layout="standard|taplist|qr"]`
- **Gutenberg Block**: `attention-network/food-menu`

---

## 🔒 Enterprise & Government Security Standards

- **Role-Based Access Control**: Strict `current_user_can('manage_options')` checks on every administrative screen and AJAX handler.
- **CSRF Defense**: Cryptographic nonce validation (`check_ajax_referer('attention_network_admin_nonce', 'nonce')`).
- **Input Sanitization & Output Escaping**: Comprehensive context-aware encoding (`esc_html`, `esc_attr`, `esc_url`, `wp_json_encode`).
- **SSRF Defense**: `Attention_Network_API_Client::validate_api_url()` filters out RFC 1918 private subnets, loopbacks, and cloud metadata endpoints (`169.254.169.254`).
- **Zero Runtime Collisions**: Built-in developer stubs guarantee clean static analysis in IDEs without runtime overhead.

---

## 📦 Installation & Setup

1. Download or clone this repository into your WordPress plugins folder:
   ```bash
   cd wp-content/plugins/
   git clone https://github.com/DarylMeese/attention-network.git
   ```
2. In the WordPress Admin Panel, navigate to **Plugins** -> **Installed Plugins** and click **Activate** under **Attention Network**.
3. Go to **Attention Network** -> **Settings**:
   - Set **API Base URL** (e.g. `https://api.attentionnetwork.com` or local dev URL).
   - Enter your **Organization ID** and **API Key**.
   - Click **Test Connection** to verify live synchronization.

---

## 📄 License

Licensed under the GNU General Public License v2 (or later).
Copyright © 2026 Attention Network.
